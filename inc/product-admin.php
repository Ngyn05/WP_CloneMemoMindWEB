<?php
if (!defined('ABSPATH')) exit;

function mm_translate_product_option($value){
  $labels=[
    'Nomad'=>'Phiêu du','Gotham'=>'Đô thị','Archive'=>'Hoài cổ',
    'Amber'=>'Hổ phách','Haze'=>'Sương khói','Forest'=>'Xanh rừng',
    'Veil'=>'Sương bạc','Midnight'=>'Đen huyền','Lunar'=>'Bạc ánh trăng',
    'Non-Prescription Lenses'=>'Tròng không độ',
    'Prescription Lenses'=>'Tròng kính thuốc',
  ];
  return $labels[(string)$value]??(string)$value;
}

function mm_product_snapshot_data($slug){
  $view='products__'.$slug.'.html';
  $path=get_template_directory().'/views/'.$view;
  if(!is_readable($path)) return null;
  $raw=file_get_contents($path);
  if(!preg_match('/var productVariants\s*=\s*(\[.*?\]);/s',$raw,$match)) return null;
  $variants=json_decode($match[1],true);
  if(!is_array($variants)) return null;
  $title=''; $description=''; $images=[]; $variant_media=[];
  if(preg_match('#<script\b[^>]*id=["\'][^"\']*pre-glass-sku-variant-media[^"\']*["\'][^>]*>(.*?)</script>#is',$raw,$media_match)){
    $decoded=json_decode(trim(html_entity_decode($media_match[1],ENT_QUOTES|ENT_HTML5,'UTF-8')),true);
    if(is_array($decoded)) $variant_media=$decoded;
  }
  $dom=new DOMDocument();
  libxml_use_internal_errors(true); $dom->loadHTML('<?xml encoding="utf-8" ?>'.$raw); libxml_clear_errors();
  $xpath=new DOMXPath($dom);
  $titles=$xpath->query("//*[contains(concat(' ',normalize-space(@class),' '),' pre-glass-sku-product__title ')]");
  if($titles->length) $title=trim($titles->item(0)->textContent);
  $descriptions=$xpath->query("//*[contains(concat(' ',normalize-space(@class),' '),' pre-glass-sku-product__description ')]");
  if($descriptions->length && function_exists('mm_blog_inner_html')) $description=mm_blog_inner_html($dom,$descriptions->item(0));
  $media_images=$xpath->query("//*[contains(concat(' ',normalize-space(@class),' '),' pre-glass-sku-product__media ')]//img[@src]");
  foreach($media_images as $image){
    $src=trim($image->getAttribute('src')); if(!$src) continue;
    $key=preg_replace('/-\d+(?=\.[a-z0-9]+$)/i','',basename((string)parse_url($src,PHP_URL_PATH)));
    if(!isset($images[$key])) $images[$key]=['src'=>$src,'alt'=>trim($image->getAttribute('alt'))];
  }
  return ['slug'=>$slug,'view'=>$view,'title'=>$title?:ucwords(str_replace('-',' ',$slug)),'description'=>$description,'variants'=>$variants,'images'=>array_values($images),'variant_media'=>$variant_media];
}

function mm_product_local_image($url){
  $path=(string)parse_url('https:'.(str_starts_with($url,'//')?$url:'//'.ltrim($url,'/')),PHP_URL_PATH);
  $name=basename($path);
  $exact=get_template_directory().'/assets/cdn/shop/files/'.$name;
  if(is_readable($exact)) return $exact;
  $stem=pathinfo($name,PATHINFO_FILENAME); $ext=pathinfo($name,PATHINFO_EXTENSION);
  $candidates=glob(get_template_directory().'/assets/cdn/shop/files/'.$stem.'*.'.$ext);
  return $candidates && is_readable($candidates[0]) ? $candidates[0] : '';
}

function mm_product_attach_image($product_id,$url,$alt=''){
  $source=mm_product_local_image($url); if(!$source) return 0;
  $existing=get_posts(['post_type'=>'attachment','post_status'=>'inherit','posts_per_page'=>1,'fields'=>'ids','meta_key'=>'_mm_source_file','meta_value'=>basename($source)]);
  if($existing) return (int)$existing[0];
  require_once ABSPATH.'wp-admin/includes/image.php';
  $upload=wp_upload_bits(basename($source),null,file_get_contents($source));
  if(!empty($upload['error'])) return 0;
  $type=wp_check_filetype($upload['file']);
  $id=wp_insert_attachment(['post_mime_type'=>$type['type'],'post_title'=>sanitize_text_field($alt?:pathinfo($source,PATHINFO_FILENAME)),'post_excerpt'=>sanitize_text_field($alt),'post_status'=>'inherit'],$upload['file'],$product_id);
  if(is_wp_error($id)) return 0;
  wp_update_attachment_metadata($id,wp_generate_attachment_metadata($id,$upload['file']));
  update_post_meta($id,'_wp_attachment_image_alt',sanitize_text_field($alt));
  update_post_meta($id,'_mm_source_file',basename($source));
  return (int)$id;
}

function mm_import_woocommerce_products(){
  if(!class_exists('WooCommerce') || !class_exists('WC_Product_Variable')) return 0;
  $variant_map=[]; $count=0;
  foreach(['memomind-one-standard','memomind-one-custom'] as $slug){
    $data=mm_product_snapshot_data($slug); if(!$data) continue;
    $post=get_page_by_path($slug,OBJECT,'product');
    $product=$post ? new WC_Product_Variable($post->ID) : new WC_Product_Variable();
    $product->set_name($data['title']); $product->set_slug($slug); $product->set_status('publish');
    $product->set_description(wp_kses_post($data['description']));
    $product->set_short_description(wp_trim_words(wp_strip_all_tags($data['description']),35,'…'));
    $frames=[]; $lenses=[];
    foreach($data['variants'] as $variant){ $frames[]=mm_translate_product_option($variant['option1']??'N/A'); $lenses[]=mm_translate_product_option($variant['option2']??'N/A'); }
    $frame_attr=new WC_Product_Attribute(); $frame_attr->set_name('Kiểu gọng'); $frame_attr->set_options(array_values(array_unique($frames))); $frame_attr->set_visible(true); $frame_attr->set_variation(true);
    $lens_attr=new WC_Product_Attribute(); $lens_attr->set_name('Loại tròng kính'); $lens_attr->set_options(array_values(array_unique($lenses))); $lens_attr->set_visible(true); $lens_attr->set_variation(true);
    $product->set_attributes([$frame_attr,$lens_attr]);
    $product_id=$product->save(); if(!$product_id) continue;
    update_post_meta($product_id,'_mm_snapshot_view',$data['view']);
    update_post_meta($product_id,'_mm_product_route','/products/'.$slug.'/');
    $gallery=[]; $variation_ids=[];
    foreach($data['images'] as $image){
      $image_id=mm_product_attach_image($product_id,(string)$image['src'],(string)($image['alt']?:$data['title']));
      if($image_id) $gallery[]=$image_id;
    }
    foreach($data['variants'] as $variant){
      $shopify_id=(string)($variant['id']??''); if(!$shopify_id) continue;
      $found=get_posts(['post_type'=>'product_variation','post_status'=>['publish','private'],'posts_per_page'=>1,'fields'=>'ids','meta_key'=>'_mm_shopify_variant_id','meta_value'=>$shopify_id]);
      $variation=$found ? new WC_Product_Variation((int)$found[0]) : new WC_Product_Variation();
      $variation->set_parent_id($product_id);
      $variation->set_attributes(['kieu-gong'=>mm_translate_product_option($variant['option1']??'N/A'),'loai-trong-kinh'=>mm_translate_product_option($variant['option2']??'N/A')]);
      $variation->set_sku((string)($variant['sku']??''));
      $variation->set_regular_price(number_format(((float)($variant['price']??0))/100,2,'.',''));
      $variation->set_status('publish'); $variation->set_manage_stock(false); $variation->set_stock_status('instock');
      $image=$variant['featured_image']??[];
      $image_id=mm_product_attach_image($product_id,(string)($image['src']??''),(string)($image['alt']??$data['title']));
      if($image_id){$variation->set_image_id($image_id);$gallery[]=$image_id;}
      $variation_id=$variation->save(); update_post_meta($variation_id,'_mm_shopify_variant_id',$shopify_id); $variant_map[$shopify_id]=$variation_id; $variation_ids[]=$variation_id;
      update_post_meta($variation_id,'_mm_variation_gallery_ids',$image_id?(string)$image_id:'');
    }
    $gallery=array_values(array_unique(array_filter($gallery)));
    if($gallery){$product->set_image_id($gallery[0]);$product->set_gallery_image_ids(array_slice($gallery,1));$product->save();}
    WC_Product_Variable::sync($product_id); $count++;
  }
  update_option('mm_shopify_variant_map',$variant_map,false);
  update_option('mm_product_import_version',15,false);
  return $count;
}

add_action('wp_loaded',function(){
  if((int)get_option('mm_product_import_version',0)<15) mm_import_woocommerce_products();
},30);

function mm_product_apply_to_snapshot($html,$view){
  if(!preg_match('#^products__(.+)\.html$#',$view,$match) || !class_exists('WooCommerce')) return $html;
  $post=null;
  $route=function_exists('mm_current_route') ? mm_current_route() : '';
  if($route){
    $ids=get_posts(['post_type'=>'product','post_status'=>'publish','posts_per_page'=>1,'fields'=>'ids','meta_key'=>'_mm_product_route','meta_value'=>$route]);
    if($ids) $post=get_post((int)$ids[0]);
  }
  if(!$post) $post=get_page_by_path($match[1],OBJECT,'product');
  if(!$post) return false;
  $product=wc_get_product($post->ID); if(!$product || $product->get_status()!=='publish') return false;
  $title=$product->get_name()!=='' ? $product->get_name() : 'N/A';
  $description=$product->get_description()!=='' ? apply_filters('the_content',$product->get_description()) : '<p>N/A</p>';
  $html=preg_replace('#(<h1\b[^>]*class=["\'][^"\']*pre-glass-sku-product__title[^"\']*["\'][^>]*>).*?(</h1>)#is','$1'.esc_html($title).'$2',$html,1);
  $html=preg_replace('#<p\b[^>]*class=["\'][^"\']*pre-glass-sku-variant-campaign-notice[^"\']*["\'][^>]*>.*?</p>#is','',$html);
  $in_stock=$product->is_in_stock();
  $stock='<p class="mm-product-stock '.($in_stock?'is-in-stock':'is-out-of-stock').'">'.($in_stock?'Còn hàng':'Hết hàng').'</p>';
  $html=preg_replace('#(<p\b[^>]*class=["\'][^"\']*pre-glass-sku-variant-block__title[^"\']*["\'][^>]*>.*?</p>)#is','$1'.$stock,$html,1);
  $html=str_replace('</head>','<style id="mm-hide-product-prices">.pre-glass-sku-product .pre-glass-sku-product__price-row,.pre-glass-sku-product .pre-glass-sku-product-switch-card__pricing,.pre-glass-sku-product .pre-glass-sku-product-switch-card__line,.pre-glass-sku-product .pre-glass-sku-variant-option__price,.pre-glass-sku-product .pre-glass-sku-option-card__lens-price,.pre-glass-sku-product [id*="total-price"]{display:none!important}.pre-glass-sku-product .pre-glass-sku-product-switch-card{display:block!important;flex:0 1 auto!important;width:100%!important;max-width:none!important;margin:auto!important;overflow:visible!important;text-overflow:clip!important;white-space:normal!important;line-height:1.35;text-align:center!important}.pre-glass-sku-product .pre-glass-sku-product-switch-card{display:flex!important;height:auto!important;min-height:60px;align-items:center!important;justify-content:center!important;padding-left:12px!important;padding-right:12px!important}.mm-product-stock{display:inline-flex;align-items:center;margin:8px 0 18px;padding:7px 14px;border-radius:999px;font-size:14px;font-weight:600}.mm-product-stock.is-in-stock{color:#237a38;background:#e8f5eb}.mm-product-stock.is-out-of-stock{color:#a32929;background:#fbeaea}</style></head>',$html);
  $html=preg_replace('#(<div\b[^>]*class=["\'][^"\']*pre-glass-sku-product__description[^"\']*["\'][^>]*>).*?(</div></div></div>\s*<hr\b)#is','$1<div>'.$description.'$2',$html,1);
  
  $product_actions=<<<HTML
<div class="mm-product-actions">
  <div class="mm-action-buttons">
    <a href="https://zalo.me/0917834532" target="_blank" rel="noopener noreferrer" class="mm-btn-action mm-btn-zalo" aria-label="Tư vấn ngay qua Zalo">
      <svg class="mm-zalo-icon" viewBox="0 0 28 28" width="24" height="24" aria-hidden="true">
        <path d="M14 2C7.373 2 2 7.149 2 13.5c0 2.457.818 4.73 2.22 6.6L3.15 24.25c-.15.42.27.84.69.69L8 23.42c1.77 1.03 3.82 1.58 6 1.58 6.627 0 12-5.149 12-11.5S20.627 2 14 2z" fill="#0068FF"/>
        <text x="14" y="15.8" fill="#ffffff" font-family="Arial, Helvetica, sans-serif" font-size="7.8" font-weight="900" text-anchor="middle" letter-spacing="-0.2">Zalo</text>
      </svg>
      <span>TƯ VẤN NGAY</span>
    </a>
    <button type="button" class="mm-btn-action mm-btn-buy pre-glass-sku-btn-buy" aria-label="Mua ngay">
      <span>MUA NGAY</span>
    </button>
  </div>

  <div class="mm-quick-consult">
    <div class="mm-quick-consult__header">
      <span class="mm-quick-consult__icon" aria-hidden="true">📞</span>
      <p class="mm-quick-consult__title">Hãy để lại <strong>số điện thoại</strong>, chúng tôi sẽ gọi ngay cho bạn <strong>tư vấn miễn phí</strong>!</p>
    </div>
    <form class="mm-quick-consult__form" id="mm-quick-consult-form">
      <input type="tel" class="mm-quick-consult__input" name="phone" placeholder="Nhập sđt tư vấn miễn phí..." required autocomplete="tel">
      <button type="submit" class="mm-quick-consult__submit" id="mm-quick-consult-submit">GỬI ĐI</button>
    </form>
    <div class="mm-quick-consult__error" id="mm-quick-consult-error" style="display:none;"></div>
    <div class="mm-quick-consult__success" id="mm-quick-consult-success" style="display:none;">
      ✓ Đã nhận số điện thoại! MemoMind sẽ liên hệ tư vấn cho bạn ngay.
    </div>
  </div>
</div>
HTML;
  $html=preg_replace('#<div\b[^>]*class=["\'][^"\']*pre-glass-sku-product__cta[^"\']*["\'][^>]*>.*?(?=</div>\s*</section>)#is', $product_actions, $html, 1);

  $consult_popup=<<<HTML
<div aria-hidden="true" class="mm-consult-modal" id="mm-consult-modal">
  <button aria-label="Đóng" class="mm-consult-modal__backdrop" data-mm-consult-close type="button"></button>
  <div class="mm-consult-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="mm-consult-title">
    <button aria-label="Đóng" class="mm-consult-modal__close" data-mm-consult-close type="button">×</button>

    <div class="mm-consult-modal__view mm-consult-modal__view--form" id="mm-consult-view-form">
      <h2 class="mm-consult-modal__title" id="mm-consult-title">Thông tin mua hàng</h2>
      <p class="mm-consult-modal__notice">
        Nhân viên MemoMind sẽ liên hệ theo số điện thoại bạn đã cung cấp để xác nhận đơn hàng và giao hàng nhanh chóng.
      </p>

      <form id="mm-consult-form" novalidate>
        <div class="mm-consult-field">
          <label for="mm-input-phone">Số điện thoại <span class="mm-required">*</span></label>
          <input id="mm-input-phone" name="phone" type="tel" required placeholder="Nhập số điện thoại chính xác (VD: 0912 345 678)" autocomplete="tel">
        </div>

        <div class="mm-consult-row">
          <div class="mm-consult-field">
            <label for="mm-input-name">Họ và tên (không bắt buộc)</label>
            <input id="mm-input-name" name="name" type="text" placeholder="Họ và tên (không bắt buộc)" autocomplete="name">
          </div>
          <div class="mm-consult-field">
            <label for="mm-input-email">Email (không bắt buộc)</label>
            <input id="mm-input-email" name="email" type="email" placeholder="Email (không bắt buộc)" autocomplete="email">
          </div>
        </div>

        <div class="mm-consult-field">
          <label for="mm-input-address">Địa chỉ giao hàng (không bắt buộc)</label>
          <input id="mm-input-address" name="address" type="text" placeholder="Địa chỉ giao hàng (không bắt buộc)" autocomplete="street-address">
        </div>

        <div class="mm-consult-field">
          <label for="mm-input-note">Ghi chú đơn hàng (không bắt buộc)</label>
          <textarea id="mm-input-note" name="note" rows="3" placeholder="Ghi chú thêm về đơn hàng hoặc thời gian giao hàng..."></textarea>
        </div>

        <div class="mm-consult-summary">
          <h3 class="mm-consult-summary__head">Sản phẩm đã chọn</h3>
          <div class="mm-consult-summary__card">
            <div class="mm-consult-summary__top">
              <div class="mm-consult-summary__title-wrap">
                <span class="mm-consult-summary__label">Sản phẩm</span>
                <strong class="mm-consult-summary__name" data-mm-summary-title>MemoMind One</strong>
              </div>
              <div class="mm-consult-qty-control">
                <button type="button" class="mm-consult-qty-btn" data-mm-qty-action="dec" aria-label="Giảm số lượng">−</button>
                <span class="mm-consult-qty-num" data-mm-summary-qty>1</span>
                <button type="button" class="mm-consult-qty-btn" data-mm-qty-action="inc" aria-label="Tăng số lượng">+</button>
              </div>
            </div>
            <div class="mm-consult-summary__details">
              <div class="mm-consult-summary__meta-row" data-mm-summary-frame-row>
                <span class="mm-consult-summary__meta-label">Kiểu gọng:</span>
                <span class="mm-consult-summary__meta-value" data-mm-summary-frame>Hoài cổ</span>
              </div>
              <div class="mm-consult-summary__meta-row" data-mm-summary-lens-row>
                <span class="mm-consult-summary__meta-label" data-mm-summary-lens-label>Loại tròng kính:</span>
                <span class="mm-consult-summary__meta-value" data-mm-summary-lens>Tròng không độ</span>
              </div>
            </div>
          </div>
        </div>

        <div class="mm-consult-error" id="mm-consult-error" style="display:none;"></div>

        <button type="submit" class="mm-consult-submit" id="mm-consult-submit-btn">
          <span class="mm-consult-submit__text">Mua hàng</span>
        </button>
      </form>
    </div>

    <div class="mm-consult-modal__view mm-consult-modal__view--success" id="mm-consult-view-success" style="display:none;">
      <div class="mm-consult-success__icon">✓</div>
      <h2 class="mm-consult-success__title">Đặt hàng thành công</h2>
      <p class="mm-consult-success__code">Mã đơn hàng: <strong data-mm-success-code>#MM</strong></p>
      <p class="mm-consult-success__desc">Cảm ơn bạn đã mua hàng! Đơn hàng của bạn đã được ghi nhận vào hệ thống. Nhân viên MemoMind sẽ liên hệ để xác nhận và giao hàng sớm nhất.</p>
      <button type="button" class="mm-consult-success__btn" data-mm-consult-close>Hoàn tất</button>
    </div>

  </div>
</div>
HTML;

  $consult_style=<<<CSS
<style id="mm-consult-modal-style">
.mm-product-actions{margin-top:20px;width:100%;font-family:Manrope,Arial,sans-serif}
.mm-action-buttons{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px}
.mm-btn-action{display:flex;align-items:center;justify-content:center;gap:9px;height:50px;border-radius:999px;font-family:inherit;font-size:15px;font-weight:700;letter-spacing:.4px;text-decoration:none;cursor:pointer;box-sizing:border-box;transition:transform .2s,box-shadow .2s,border-color .2s,background .2s}
.mm-btn-zalo{background:#ffffff;color:#2a251e;border:1.5px solid #d5cbba;box-shadow:0 4px 14px rgba(0,0,0,.04)}
.mm-btn-zalo:hover{background:#fbf9f6;border-color:#c9b48f;color:#111111;transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,.08)}
.mm-btn-buy{background:linear-gradient(135deg,#E5D9C5 0%,#D2C1A5 100%);color:#2a251e;border:1.5px solid #d2c1a5;box-shadow:0 4px 14px rgba(210,193,165,.35)}
.mm-btn-buy:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(210,193,165,.5);opacity:.95}
.mm-zalo-icon{flex-shrink:0}
.mm-quick-consult{background:linear-gradient(135deg,#fbf9f6 0%,#f4eee2 100%);border:1.5px solid #ded4c5;border-radius:18px;padding:18px 20px;box-shadow:0 4px 18px rgba(0,0,0,.03);box-sizing:border-box}
.mm-quick-consult__header{display:flex;align-items:center;gap:10px;margin-bottom:12px}
.mm-quick-consult__icon{font-size:19px;line-height:1}
.mm-quick-consult__title{margin:0;font-size:14px;line-height:1.5;color:#3c372f}
.mm-quick-consult__title strong{color:#8c6e3b;font-weight:700}
.mm-quick-consult__form{display:flex;gap:10px;align-items:center}
.mm-quick-consult__input{flex:1;min-width:0;height:44px;border:1.5px solid #d5c8b5;border-radius:999px;padding:0 18px;background:#ffffff;font-family:inherit;font-size:14px;color:#111;outline:none;transition:border-color .2s,box-shadow .2s;box-sizing:border-box}
.mm-quick-consult__input:focus{border-color:#a88d5e;box-shadow:0 0 0 3px rgba(168,141,94,.15)}
.mm-quick-consult__submit{height:44px;padding:0 24px;border:none;border-radius:999px;background:#942727;color:#ffffff;font-family:inherit;font-size:13.5px;font-weight:700;letter-spacing:.5px;cursor:pointer;transition:background .2s,transform .15s;white-space:nowrap;box-sizing:border-box}
.mm-quick-consult__submit:hover{background:#b02f2f;transform:translateY(-1px)}
.mm-quick-consult__submit:disabled{opacity:.6;cursor:not-allowed}
.mm-quick-consult__error{margin-top:10px;padding:9px 14px;border-radius:8px;background:#fde8e8;color:#b91c1c;font-size:13px;line-height:1.4;text-align:center}
.mm-quick-consult__success{margin-top:10px;padding:10px 14px;border-radius:8px;background:#e9f5ec;color:#267c3a;font-size:13.5px;font-weight:600;text-align:center}
.mm-consult-modal{position:fixed;inset:0;z-index:999999;display:none;align-items:center;justify-content:center;padding:16px;box-sizing:border-box}
.mm-consult-modal.is-open{display:flex}
.mm-consult-modal__backdrop{position:fixed;inset:0;width:100%;height:100%;border:0;background:rgba(0,0,0,.55);backdrop-filter:blur(3px);cursor:pointer}
.mm-consult-modal__dialog{position:relative;z-index:2;width:100%;max-width:680px;max-height:calc(100vh - 32px);overflow-y:auto;box-sizing:border-box;padding:32px 30px;border-radius:20px;background:#fff;box-shadow:0 24px 70px rgba(0,0,0,.25);font-family:Manrope,Arial,sans-serif;color:#222;animation:mmModalFadeIn .25s ease-out}
@keyframes mmModalFadeIn{from{opacity:0;transform:translateY(12px) scale(.98)}to{opacity:1;transform:translateY(0) scale(1)}}
.mm-consult-modal__close{position:absolute;top:14px;right:16px;width:36px;height:36px;border:0;border-radius:50%;background:#f2f2f0;color:#555;font-size:24px;line-height:1;display:grid;place-items:center;cursor:pointer;transition:.2s}
.mm-consult-modal__close:hover{background:#e5e2dc;color:#111}
.mm-consult-modal__title{margin:0 0 10px;font-size:24px;font-weight:700;letter-spacing:-.3px;color:#111}
.mm-consult-modal__notice{margin:0 0 20px;padding:13px 16px;border-left:4px solid #c9b48f;border-radius:8px;background:#f7f2ea;font-size:13.5px;line-height:1.55;color:#444}
.mm-consult-field{margin-bottom:14px}
.mm-consult-field label{display:block;margin-bottom:6px;font-size:13px;font-weight:600;color:#333}
.mm-required{color:#c53030}
.mm-consult-field input,.mm-consult-field textarea{box-sizing:border-box;width:100%;padding:11px 14px;border:1px solid #d5d2cc;border-radius:10px;background:#fff;font-family:inherit;font-size:14.5px;color:#111;outline:none;transition:border-color .2s,box-shadow .2s}
.mm-consult-field input:focus,.mm-consult-field textarea:focus{border-color:#b99e74;box-shadow:0 0 0 3px rgba(185,158,116,.15)}
.mm-consult-field textarea{resize:vertical;min-height:72px}
.mm-consult-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.mm-consult-summary{margin:18px 0 20px;padding:18px 20px;border-radius:14px;background:#faf9f7;border:1px solid #ebe7df}
.mm-consult-summary__head{margin:0 0 12px;font-size:15px;font-weight:700;color:#111}
.mm-consult-summary__card{display:flex;flex-direction:column;gap:12px}
.mm-consult-summary__top{display:flex;align-items:center;justify-content:space-between;gap:12px}
.mm-consult-summary__title-wrap{display:flex;flex-direction:column;gap:3px}
.mm-consult-summary__label{font-size:12px;color:#777}
.mm-consult-summary__name{font-size:16px;font-weight:700;color:#111}
.mm-consult-qty-control{display:inline-flex;align-items:center;gap:10px}
.mm-consult-qty-btn{display:grid;place-items:center;width:30px;height:30px;padding:0;border:1px solid #cfcac1;border-radius:50%;background:#fff;color:#222;font-size:17px;font-weight:600;cursor:pointer;transition:.15s}
.mm-consult-qty-btn:hover{background:#ede8e0}
.mm-consult-qty-num{min-width:20px;text-align:center;font-size:15px;font-weight:700}
.mm-consult-summary__details{display:flex;flex-direction:column;gap:8px;padding-top:12px;border-top:1px dashed #ded9cf;font-size:13.5px}
.mm-consult-summary__meta-row{display:flex;align-items:center;justify-content:space-between;gap:10px}
.mm-consult-summary__meta-label{color:#666;font-weight:500}
.mm-consult-summary__meta-value{font-weight:700;color:#111;text-align:right}
.mm-consult-error{margin-bottom:14px;padding:10px 14px;border-radius:8px;background:#fde8e8;color:#b91c1c;font-size:13.5px;line-height:1.4}
.mm-consult-submit{width:100%;height:48px;border:0;border-radius:999px;background:#dfcfb2;color:#222;font-family:inherit;font-size:16px;font-weight:700;cursor:pointer;transition:background .2s,transform .15s;display:flex;align-items:center;justify-content:center}
.mm-consult-submit:hover:not(:disabled){background:#d2bd98;transform:translateY(-1px)}
.mm-consult-submit:disabled{opacity:.65;cursor:not-allowed}
.mm-consult-modal__view--success{text-align:center;padding:16px 8px 8px}
.mm-consult-success__icon{display:grid;place-items:center;width:64px;height:64px;margin:0 auto 16px;border-radius:50%;background:#e9f5ec;color:#267c3a;font-size:32px;font-weight:700}
.mm-consult-success__title{margin:0 0 10px;font-size:24px;font-weight:700;color:#111}
.mm-consult-success__code{margin:0 0 12px;font-size:15px;color:#555}
.mm-consult-success__code strong{color:#111}
.mm-consult-success__desc{margin:0 auto 24px;max-width:440px;font-size:14.5px;line-height:1.6;color:#555}
.mm-consult-success__btn{width:100%;max-width:300px;height:46px;margin:0 auto;border:0;border-radius:999px;background:#dfcfb2;color:#222;font-family:inherit;font-size:15px;font-weight:700;cursor:pointer;transition:.2s}
.mm-consult-success__btn:hover{background:#d2bd98}
body.mm-consult-open{overflow:hidden}
@media(max-width:600px){
  .mm-action-buttons{grid-template-columns:1fr;gap:10px}
  .mm-quick-consult{padding:16px 14px}
  .mm-quick-consult__form{flex-direction:row}
  .mm-consult-modal{padding:0}
  .mm-consult-modal__dialog{max-height:100vh;height:100%;border-radius:0;padding:24px 18px 30px}
  .mm-consult-row{grid-template-columns:1fr}
}
</style>
CSS;

  $ajax_url = esc_url(admin_url('admin-ajax.php'));
  $nonce = wp_create_nonce('mm_consultation_nonce');

  $consult_script=<<<JS
<script id="mm-consult-script">
(()=>{
  const modal = document.getElementById('mm-consult-modal');
  const formView = document.getElementById('mm-consult-view-form');
  const successView = document.getElementById('mm-consult-view-success');
  const form = document.getElementById('mm-consult-form');
  const errorBox = document.getElementById('mm-consult-error');
  const submitBtn = document.getElementById('mm-consult-submit-btn');
  const submitText = submitBtn ? submitBtn.querySelector('.mm-consult-submit__text') : null;
  const successCode = modal ? modal.querySelector('[data-mm-success-code]') : null;

  const titleEl = modal ? modal.querySelector('[data-mm-summary-title]') : null;
  const qtyEl = modal ? modal.querySelector('[data-mm-summary-qty]') : null;
  const frameEl = modal ? modal.querySelector('[data-mm-summary-frame]') : null;
  const lensEl = modal ? modal.querySelector('[data-mm-summary-lens]') : null;
  const lensLabel = modal ? modal.querySelector('[data-mm-summary-lens-label]') : null;
  const frameRow = modal ? modal.querySelector('[data-mm-summary-frame-row]') : null;
  const lensRow = modal ? modal.querySelector('[data-mm-summary-lens-row]') : null;

  const labelMap = {
    'Nomad':'Phiêu du','Gotham':'Đô thị','Archive':'Hoài cổ',
    'Amber':'Hổ phách','Haze':'Sương khói','Forest':'Xanh rừng',
    'Veil':'Sương bạc','Midnight':'Đen huyền','Lunar':'Bạc ánh trăng',
    'Non-Prescription Lenses':'Tròng không độ','Prescription Lenses':'Tròng kính thuốc'
  };

  const translate = (val) => {
    if(!val) return '';
    const trimmed = String(val).trim();
    return labelMap[trimmed] || trimmed;
  };

  let currentVariantId = 0;
  let currentQty = 1;
  let currentTitle = '';
  let currentFrame = '';
  let currentLens = '';

  const openModal = () => {
    if(!modal) return;
    formView.style.display = 'block';
    successView.style.display = 'none';
    errorBox.style.display = 'none';
    errorBox.textContent = '';
    if(submitBtn) submitBtn.disabled = false;
    if(submitText) submitText.textContent = 'Mua hàng';
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('mm-consult-open');
  };

  const closeModal = () => {
    if(!modal) return;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('mm-consult-open');
  };

  if(modal){
    modal.querySelectorAll('[data-mm-consult-close]').forEach(btn => {
      btn.addEventListener('click', closeModal);
    });
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
    });
    modal.querySelectorAll('[data-mm-qty-action]').forEach(btn => {
      btn.addEventListener('click', () => {
        const action = btn.dataset.mmQtyAction;
        if (action === 'inc') currentQty = Math.min(99, currentQty + 1);
        else if (action === 'dec') currentQty = Math.max(1, currentQty - 1);
        if(qtyEl) qtyEl.textContent = String(currentQty);
      });
    });
  }

  document.addEventListener('click', (event) => {
    const buyBtn = event.target.closest('.pre-glass-sku-btn-buy');
    if (!buyBtn) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    const root = document.querySelector('.pre-glass-sku-product') || document;
    const checkedRadio = root.querySelector('.pre-glass-sku-variant-hidden input:checked')
      || root.querySelector('.pre-glass-sku-variant-hidden input[type="radio"]')
      || root.querySelector('input[name*="variant"]:checked');
    currentVariantId = checkedRadio ? parseInt(checkedRadio.value, 10) : 0;
    let opt1 = checkedRadio ? (checkedRadio.getAttribute('data-option1') || checkedRadio.dataset.option1 || '') : '';
    let opt2 = checkedRadio ? (checkedRadio.getAttribute('data-option2') || checkedRadio.dataset.option2 || '') : '';
    const titleHeader = root.querySelector('.pre-glass-sku-product__title');
    currentTitle = titleHeader ? titleHeader.textContent.trim() : document.title.split('|')[0].trim();
    const pageQtyEl = root.querySelector('.pre-glass-sku-qty__value');
    currentQty = pageQtyEl ? (parseInt(pageQtyEl.textContent.trim(), 10) || 1) : 1;
    if (!opt1) {
      const selectedFrameCard = root.querySelector('.pre-glass-sku-option-group[data-option-index="0"] .is-selected, .pre-glass-sku-option-card.is-selected, .pre-glass-sku-variant-option.is-active');
      if (selectedFrameCard) opt1 = selectedFrameCard.getAttribute('data-option-value') || selectedFrameCard.querySelector('.pre-glass-sku-option-card__name, .pre-glass-sku-variant-option__name')?.textContent.trim() || '';
    }
    if (!opt1) {
      const checkedFrameInput = root.querySelector('input[data-option-index="0"]:checked, input[name*="option-template"][name$="-1"]:checked');
      if (checkedFrameInput) opt1 = checkedFrameInput.value;
    }
    if (!opt2) {
      const selectedLensCard = root.querySelector('.pre-glass-sku-option-group[data-option-index="1"] .is-selected, .pre-glass-sku-swatch-option.is-selected, .pre-glass-sku-option-card--lens.is-selected');
      if (selectedLensCard) opt2 = selectedLensCard.getAttribute('data-option-value') || selectedLensCard.getAttribute('title') || selectedLensCard.querySelector('.pre-glass-sku-option-card__name')?.textContent.trim() || '';
    }
    if (!opt2) {
      const checkedLensInput = root.querySelector('input[data-option-index="1"]:checked, input[name*="option-template"][name$="-2"]:checked');
      if (checkedLensInput) opt2 = checkedLensInput.value;
    }
    currentFrame = translate(opt1);
    currentLens = translate(opt2);
    const isCustom = /Tùy chỉnh|Custom/i.test(currentTitle) || root.querySelector('.pre-glass-sku-option-group--swatches');
    if (lensLabel) lensLabel.textContent = isCustom ? 'Màu sắc:' : 'Loại tròng kính:';
    if(titleEl) titleEl.textContent = currentTitle;
    if(qtyEl) qtyEl.textContent = String(currentQty);
    if(frameRow){ frameRow.style.display = 'flex'; if(frameEl) frameEl.textContent = currentFrame || 'Nomad (Phiêu du)'; }
    if(lensRow){ lensRow.style.display = 'flex'; if(lensEl) lensEl.textContent = currentLens || (isCustom ? 'Hổ phách' : 'Tròng không độ'); }
    openModal();
  }, true);

  const isValidPhone = (str) => {
    if(!str) return false;
    const clean = str.replace(/[\s.\-()]/g, '');
    return /^(0|\+84|84)(3[2-9]|5[25689]|7[06-9]|8[1-9]|9[0-9]|2[0-9]{2})[0-9]{7}$/.test(clean);
  };

  // Modal Form submit handler
  if(form){
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      if(errorBox){ errorBox.style.display = 'none'; errorBox.textContent = ''; }

      const phoneInput = form.querySelector('[name="phone"]');
      const phone = phoneInput ? phoneInput.value.trim() : '';

      if (!phone) {
        if(errorBox){
          errorBox.textContent = 'Vui lòng nhập số điện thoại để chúng tôi liên hệ giao hàng.';
          errorBox.style.display = 'block';
        }
        if (phoneInput) phoneInput.focus();
        return;
      }

      if (!isValidPhone(phone)) {
        if(errorBox){
          errorBox.textContent = 'Số điện thoại không hợp lệ. Vui lòng nhập đúng 10 số (VD: 0912 345 678).';
          errorBox.style.display = 'block';
        }
        if (phoneInput) phoneInput.focus();
        return;
      }

      if(submitBtn) submitBtn.disabled = true;
      if(submitText) submitText.textContent = 'Đang xử lý đặt hàng…';

      const formData = new FormData(form);
      formData.append('action', 'mm_submit_consultation');
      formData.append('nonce', '{$nonce}');
      formData.append('variant_id', String(currentVariantId));
      formData.append('quantity', String(currentQty));
      formData.append('product_title', currentTitle);
      formData.append('product_url', window.location.href);
      formData.append('frame', currentFrame);
      formData.append('lens', currentLens);

      try {
        const res = await fetch('{$ajax_url}', {
          method: 'POST',
          body: formData,
          credentials: 'same-origin'
        });
        const data = await res.json();

        if (data && data.success) {
          if(successCode) successCode.textContent = '#' + (data.data.order_number || data.data.order_id || '');
          if(formView) formView.style.display = 'none';
          if(successView) successView.style.display = 'block';
          form.reset();
        } else {
          throw new Error((data && data.data && data.data.message) || 'Có lỗi xảy ra khi đặt hàng. Vui lòng thử lại.');
        }
      } catch (err) {
        if(errorBox){
          errorBox.textContent = err.message || 'Không thể kết nối máy chủ. Vui lòng thử lại sau.';
          errorBox.style.display = 'block';
        }
        if(submitBtn) submitBtn.disabled = false;
        if(submitText) submitText.textContent = 'Mua hàng';
      }
    });
  }

  // Quick Phone Consultation Form handler
  const quickForm = document.getElementById('mm-quick-consult-form');
  if (quickForm) {
    quickForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const phoneInput = quickForm.querySelector('[name="phone"]');
      const phone = phoneInput ? phoneInput.value.trim() : '';
      const quickError = document.getElementById('mm-quick-consult-error');
      if (quickError) { quickError.style.display = 'none'; quickError.textContent = ''; }

      if (!phone) {
        if (quickError) {
          quickError.textContent = 'Vui lòng nhập số điện thoại để được tư vấn miễn phí.';
          quickError.style.display = 'block';
        }
        if (phoneInput) phoneInput.focus();
        return;
      }

      if (!isValidPhone(phone)) {
        if (quickError) {
          quickError.textContent = 'Số điện thoại không hợp lệ. Vui lòng nhập đúng 10 số (VD: 0912 345 678).';
          quickError.style.display = 'block';
        }
        if (phoneInput) phoneInput.focus();
        return;
      }

      const submitBtn = document.getElementById('mm-quick-consult-submit');
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'ĐANG GỬI...';
      }

      const root = document.querySelector('.pre-glass-sku-product') || document;
      const titleHeader = root.querySelector('.pre-glass-sku-product__title');
      const prodTitle = titleHeader ? titleHeader.textContent.trim() : document.title.split('|')[0].trim();

      const checkedRadio = root.querySelector('.pre-glass-sku-variant-hidden input:checked');
      const varId = checkedRadio ? parseInt(checkedRadio.value, 10) : 0;
      const opt1 = checkedRadio ? (checkedRadio.getAttribute('data-option1') || '') : '';
      const opt2 = checkedRadio ? (checkedRadio.getAttribute('data-option2') || '') : '';

      const formData = new FormData();
      formData.append('action', 'mm_submit_consultation');
      formData.append('nonce', '{$nonce}');
      formData.append('phone', phone);
      formData.append('name', 'Khách yêu cầu tư vấn nhanh');
      formData.append('note', 'Khách để lại số điện thoại trên khung Tư vấn nhanh ở trang sản phẩm.');
      formData.append('product_title', prodTitle);
      formData.append('product_url', window.location.href);
      formData.append('variant_id', String(varId));
      formData.append('frame', translate(opt1));
      formData.append('lens', translate(opt2));
      formData.append('quantity', '1');

      try {
        const res = await fetch('{$ajax_url}', {
          method: 'POST',
          body: formData,
          credentials: 'same-origin'
        });
        const data = await res.json();
        if (data && data.success) {
          quickForm.style.display = 'none';
          const successEl = document.getElementById('mm-quick-consult-success');
          if (successEl) successEl.style.display = 'block';
        } else {
          throw new Error((data && data.data && data.data.message) || 'Có lỗi xảy ra.');
        }
      } catch (err) {
        if (quickError) {
          quickError.textContent = err.message || 'Không thể gửi yêu cầu lúc này. Vui lòng thử lại sau.';
          quickError.style.display = 'block';
        } else {
          alert(err.message || 'Không thể gửi yêu cầu lúc này. Vui lòng thử lại sau.');
        }
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = 'GỬI ĐI';
        }
      }
    });
  }
})();
</script>
JS;

  $option_labels='<script id="mm-product-option-labels">(()=>{const labels={Nomad:"Phiêu du",Gotham:"Đô thị",Archive:"Hoài cổ",Amber:"Hổ phách",Haze:"Sương khói",Forest:"Xanh rừng",Veil:"Sương bạc",Midnight:"Đen huyền",Lunar:"Bạc ánh trăng","Non-Prescription Lenses":"Tròng không độ","Prescription Lenses":"Tròng kính thuốc"};const root=document.querySelector(".pre-glass-sku-product");if(!root)return;const walker=document.createTreeWalker(root,NodeFilter.SHOW_TEXT);let node;while(node=walker.nextNode()){const value=node.nodeValue.trim();if(labels[value])node.nodeValue=node.nodeValue.replace(value,labels[value])}})();</script>';
  $html=str_replace('</head>',$consult_style.'</head>',$html);
  $html=str_replace('</body>',$consult_popup.$consult_script.$option_labels.'</body>',$html);
  return $html;
}

function mm_product_bind_route($post_id,$post=null){
  if(wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;
  $post=$post instanceof WP_Post ? $post : get_post($post_id);
  if(!$post || $post->post_type!=='product' || !$post->post_name || $post->post_status==='auto-draft') return;
  update_post_meta($post_id,'_mm_product_route','/products/'.$post->post_name.'/');
  $view=get_post_meta($post_id,'_mm_snapshot_view',true);
  if(!$view || !is_readable(get_template_directory().'/views/'.$view)) update_post_meta($post_id,'_mm_snapshot_view','products__memomind-one-standard.html');
}
add_action('save_post_product','mm_product_bind_route',30,2);

function mm_product_render_dynamic_route($route){
  if(!preg_match('#^/products/[^/]+/$#',$route) || !class_exists('WooCommerce')) return false;
  $ids=get_posts(['post_type'=>'product','post_status'=>'publish','posts_per_page'=>1,'fields'=>'ids','meta_key'=>'_mm_product_route','meta_value'=>$route]);
  if(!$ids) return false;
  $view=get_post_meta((int)$ids[0],'_mm_snapshot_view',true);
  return $view && function_exists('mm_render_snapshot') ? mm_render_snapshot($view) : false;
}

// Disable default WooCommerce plain emails to prevent unstyled duplicate notifications
add_filter('woocommerce_email_enabled_new_order', '__return_false');
add_filter('woocommerce_email_enabled_customer_processing_order', '__return_false');

// Helper to build professional HTML email for MemoMind VN orders (No price, no images, full branding)
function mm_build_order_html_email($data, $is_customer = false){
  $order_number = esc_html($data['order_number'] ?? '');
  $name = esc_html($data['name'] ?: 'Khách hàng');
  $phone = esc_html($data['phone'] ?? '');
  $email = esc_html($data['email'] ?? '');
  $address = esc_html($data['address'] ?: 'Chưa cung cấp');
  $note = esc_html($data['note'] ?: 'Không có');
  $product_title = esc_html($data['product_title'] ?: 'MemoMind One');
  $product_url = esc_url($data['product_url'] ?? '');
  $frame = esc_html($data['frame'] ?: 'Hoài cổ (Archive)');
  $lens = esc_html($data['lens'] ?: 'Tròng không độ');
  $quantity = max(1, absint($data['quantity'] ?? 1));
  $is_custom = str_contains($product_title, 'Tùy chỉnh') || str_contains($product_title, 'Custom');
  $lens_label = $is_custom ? 'Màu sắc' : 'Loại tròng kính';
  $order_date = wp_date('H:i, d/m/Y');

  $is_quick = ($name === 'Khách yêu cầu tư vấn nhanh');

  if ($is_customer) {
    $title_text = "Xác nhận đơn hàng: #{$order_number}";
    $sub_text = "Cảm ơn bạn đã đặt mua kính thông minh <strong>MemoMind One</strong>. Đội ngũ MemoMind sẽ liên hệ qua số điện thoại để xác nhận đơn hàng và thời gian giao hàng.";
  } else {
    $title_text = $is_quick ? "Yêu cầu tư vấn nhanh: #{$order_number}" : "Đơn hàng mới: #{$order_number}";
    $sub_text = $is_quick 
      ? "Khách hàng vừa để lại số điện thoại trên khung <strong>Tư vấn miễn phí</strong> tại website MemoMind VN."
      : "Bạn vừa nhận được một đơn hàng mới từ website <strong>MemoMind VN</strong>.";
  }

  ob_start();
  ?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $title_text; ?></title>
</head>
<body style="margin:0;padding:24px 12px;background:#f5f4f0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#222;line-height:1.6;">
  <div style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e5e2dc;box-shadow:0 8px 30px rgba(0,0,0,0.06);">
    
    <!-- HEADER -->
    <div style="background:#181818;padding:28px 24px;text-align:center;">
      <div style="display:inline-block;font-size:25px;font-weight:700;letter-spacing:4px;color:#ffffff;">
        MEMOMIND <span style="display:inline-block;background:#333333;color:#dfcfb2;font-size:13px;padding:3px 8px;border-radius:6px;letter-spacing:1px;vertical-align:2px;font-weight:600;">VN</span>
      </div>
      <p style="margin:6px 0 0;color:#a0a0a0;font-size:13px;letter-spacing:0.5px;">Kính thông minh AI cho cuộc sống hiện đại</p>
    </div>

    <!-- BODY CONTENT -->
    <div style="padding:28px 24px;">
      <div style="margin-bottom:24px;padding-bottom:18px;border-bottom:1px solid #edebe6;">
        <h1 style="margin:0 0 6px;font-size:22px;color:#111111;font-weight:700;letter-spacing:-0.3px;"><?php echo $title_text; ?></h1>
        <p style="margin:0 0 12px;color:#777777;font-size:13.5px;">Thời gian: <?php echo $order_date; ?></p>
        <p style="margin:0;color:#444444;font-size:14.5px;line-height:1.55;"><?php echo $sub_text; ?></p>
      </div>

      <!-- CUSTOMER DETAILS -->
      <div style="margin-bottom:26px;">
        <h2 style="margin:0 0 12px;font-size:14px;color:#111111;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;border-left:3px solid #c9b48f;padding-left:10px;">Thông tin khách hàng</h2>
        <table style="width:100%;border-collapse:collapse;background:#faf9f7;border-radius:10px;overflow:hidden;border:1px solid #ebe7e0;">
          <tr>
            <td style="padding:11px 14px;color:#666666;width:130px;border-bottom:1px solid #ebe7e0;font-size:13.5px;">Họ và tên:</td>
            <td style="padding:11px 14px;color:#111111;font-weight:600;border-bottom:1px solid #ebe7e0;font-size:13.5px;"><?php echo $name; ?></td>
          </tr>
          <tr>
            <td style="padding:11px 14px;color:#666666;border-bottom:1px solid #ebe7e0;font-size:13.5px;">Số điện thoại:</td>
            <td style="padding:11px 14px;color:#111111;font-weight:700;font-size:15px;border-bottom:1px solid #ebe7e0;">
              <a href="tel:<?php echo $phone; ?>" style="color:#0f52ba;text-decoration:none;font-weight:700;"><?php echo $phone; ?> (Bấm để gọi)</a>
            </td>
          </tr>
          <?php if ($email): ?>
          <tr>
            <td style="padding:11px 14px;color:#666666;border-bottom:1px solid #ebe7e0;font-size:13.5px;">Email:</td>
            <td style="padding:11px 14px;color:#111111;border-bottom:1px solid #ebe7e0;font-size:13.5px;"><a href="mailto:<?php echo $email; ?>" style="color:#444;text-decoration:none;"><?php echo $email; ?></a></td>
          </tr>
          <?php endif; ?>
          <?php if ($address && $address !== 'Chưa cung cấp'): ?>
          <tr>
            <td style="padding:11px 14px;color:#666666;border-bottom:1px solid #ebe7e0;font-size:13.5px;">Địa chỉ nhận hàng:</td>
            <td style="padding:11px 14px;color:#111111;border-bottom:1px solid #ebe7e0;font-size:13.5px;"><?php echo $address; ?></td>
          </tr>
          <?php endif; ?>
          <tr>
            <td style="padding:11px 14px;color:#666666;font-size:13.5px;">Ghi chú:</td>
            <td style="padding:11px 14px;color:#333333;font-style:italic;font-size:13.5px;"><?php echo $note; ?></td>
          </tr>
        </table>
      </div>

      <!-- PRODUCT DETAILS (NO PRICE, NO IMAGES, WITH PRODUCT LINK) -->
      <div style="margin-bottom:20px;">
        <h2 style="margin:0 0 12px;font-size:14px;color:#111111;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;border-left:3px solid #c9b48f;padding-left:10px;">Chi tiết sản phẩm quan tâm</h2>
        <table style="width:100%;border-collapse:collapse;border:1px solid #ebe7e0;border-radius:10px;overflow:hidden;">
          <thead>
            <tr style="background:#f3f0e8;text-align:left;">
              <th style="padding:11px 14px;font-size:13px;color:#333333;font-weight:700;">Sản phẩm</th>
              <th style="padding:11px 14px;font-size:13px;color:#333333;font-weight:700;text-align:center;width:80px;">Số lượng</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td style="padding:14px;border-top:1px solid #ebe7e0;font-size:14px;background:#ffffff;">
                <div style="font-weight:700;color:#111111;font-size:15px;margin-bottom:6px;"><?php echo $product_title; ?></div>
                <div style="color:#555555;font-size:13.5px;line-height:1.6;margin-bottom:10px;">
                  <div>• <strong>Kiểu gọng:</strong> <?php echo $frame; ?></div>
                  <div>• <strong><?php echo $lens_label; ?>:</strong> <?php echo $lens; ?></div>
                </div>
                <?php if ($product_url): ?>
                <div>
                  <a href="<?php echo $product_url; ?>" target="_blank" style="display:inline-block;padding:6px 14px;background:#0068ff;color:#ffffff;border-radius:6px;font-size:13px;font-weight:600;text-decoration:none;">🔗 Xem link sản phẩm trên Website &rarr;</a>
                </div>
                <?php endif; ?>
              </td>
              <td style="padding:14px;border-top:1px solid #ebe7e0;text-align:center;font-weight:700;font-size:16px;color:#111111;background:#faf9f7;vertical-align:middle;">
                x<?php echo $quantity; ?>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>

    <!-- FOOTER -->
    <div style="background:#f7f6f3;padding:22px 24px;text-align:center;font-size:12.5px;color:#777777;border-top:1px solid #edebe6;">
      <p style="margin:0 0 6px;font-weight:600;color:#444444;">MEMOMIND VIỆT NAM — HỆ THỐNG PHÂN PHỐI KÍNH AI CHÍNH HÃNG</p>
      <p style="margin:0 0 6px;">Hotline: <strong>024.7305.3268</strong> (Miền Bắc) · <strong>028.7305.3268</strong> (Miền Nam)</p>
      <p style="margin:0;color:#999999;">Email: <a href="mailto:contact@memomind.vn" style="color:#666666;text-decoration:none;">contact@memomind.vn</a> · Website: <a href="https://memomind.vn" style="color:#666666;text-decoration:none;">memomind.vn</a></p>
    </div>

  </div>
</body>
</html>
  <?php
  return ob_get_clean();
}

// AJAX Order Submission - Saves order into WooCommerce Orders & Sends Clean HTML Email
function mm_handle_consultation_submission(){
  if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mm_consultation_nonce')) {
    wp_send_json_error(['message' => 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.']);
  }
  
  $phone = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));
  $clean_phone = preg_replace('/[\s.\-()]/', '', $phone);
  if (empty($clean_phone) || !preg_match('/^(0|\+84|84)(3[2-9]|5[25689]|7[06-9]|8[1-9]|9[0-9]|2[0-9]{2})[0-9]{7}$/', $clean_phone)) {
    wp_send_json_error(['message' => 'Số điện thoại không đúng định dạng. Vui lòng nhập đúng 10 số (VD: 0912 345 678).']);
  }
  
  $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
  $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
  $address = sanitize_text_field(wp_unslash($_POST['address'] ?? ''));
  $note = sanitize_textarea_field(wp_unslash($_POST['note'] ?? ''));
  $product_title = sanitize_text_field(wp_unslash($_POST['product_title'] ?? 'MemoMind One'));
  $product_url = esc_url_raw(wp_unslash($_POST['product_url'] ?? ''));
  $frame = sanitize_text_field(wp_unslash($_POST['frame'] ?? ''));
  $lens = sanitize_text_field(wp_unslash($_POST['lens'] ?? ''));
  $quantity = max(1, absint($_POST['quantity'] ?? 1));
  $shopify_id = absint($_POST['variant_id'] ?? 0);

  $order_id = 0;
  $order_number = '';

  if (class_exists('WooCommerce')) {
    try {
      $order = wc_create_order();
      
      $variation = null;
      if ($shopify_id) {
        $map = get_option('mm_shopify_variant_map', []);
        $wc_var_id = absint($map[(string)$shopify_id] ?? 0);
        if ($wc_var_id) {
          $variation = wc_get_product($wc_var_id);
        }
      }
      
      $item_id = 0;
      if ($variation) {
        $item_id = $order->add_product($variation, $quantity);
      } else {
        $posts = get_posts(['post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => 1]);
        if (!empty($posts)) {
          $p = wc_get_product($posts[0]->ID);
          if ($p) $item_id = $order->add_product($p, $quantity);
        }
      }

      if ($item_id) {
        if ($frame) wc_add_order_item_meta($item_id, 'Kiểu gọng', $frame);
        if ($lens) wc_add_order_item_meta($item_id, 'Màu sắc / Loại tròng', $lens);
        if ($product_url) wc_add_order_item_meta($item_id, 'Link sản phẩm', $product_url);
      }

      $order->set_address([
        'first_name' => $name ?: 'Khách hàng',
        'phone'      => $phone,
        'email'      => $email,
        'address_1'  => $address,
        'country'    => 'VN',
      ], 'billing');

      if ($address) {
        $order->set_address([
          'first_name' => $name ?: 'Khách hàng',
          'phone'      => $phone,
          'address_1'  => $address,
          'country'    => 'VN',
        ], 'shipping');
      }

      if ($note) {
        $order->set_customer_note($note);
      }

      $order->set_payment_method('cod');
      $order->set_payment_method_title('Thanh toán khi nhận hàng (COD)');
      $order_source_note = ($name === 'Khách yêu cầu tư vấn nhanh') 
        ? "Yêu cầu tư vấn nhanh từ số điện thoại: {$phone} (Link: {$product_url})" 
        : "Đơn hàng đặt trực tiếp từ popup Mua hàng trên website.";
      $order->add_order_note($order_source_note);
      $order->set_status('processing');
      $order->calculate_totals();
      $order_id = $order->save();
      $order_number = (string)$order->get_order_number();
    } catch (Exception $e) {
      error_log('MemoMind order creation error: '.$e->getMessage());
    }
  }

  if (!$order_number) {
    $order_number = $order_id ? (string)$order_id : ('MM-' . strtoupper(substr(uniqid(), -6)));
  }

  // Send professional HTML email notification (Admin & Customer)
  $order_data = [
    'order_number'  => $order_number,
    'name'          => $name,
    'phone'         => $phone,
    'email'         => $email,
    'address'       => $address,
    'note'          => $note,
    'product_title' => $product_title,
    'product_url'   => $product_url,
    'frame'         => $frame,
    'lens'          => $lens,
    'quantity'      => $quantity,
  ];

  $headers = ['Content-Type: text/html; charset=UTF-8'];
  $admin_email = get_option('admin_email');

  // Admin Notification Email
  $is_quick = ($name === 'Khách yêu cầu tư vấn nhanh');
  $admin_subject = $is_quick 
    ? "[MemoMind VN] Yêu cầu gọi lại tư vấn: #{$order_number} - {$phone}"
    : "[MemoMind VN] Đơn hàng mới: #{$order_number} - {$phone}";

  $admin_html = mm_build_order_html_email($order_data, false);
  wp_mail($admin_email, $admin_subject, $admin_html, $headers);

  // Customer Confirmation Email (if email was entered)
  if ($email && is_email($email)) {
    $cust_subject = "[MemoMind VN] Xác nhận đặt hàng thành công - Mã đơn #{$order_number}";
    $cust_html = mm_build_order_html_email($order_data, true);
    wp_mail($email, $cust_subject, $cust_html, $headers);
  }

  wp_send_json_success([
    'order_id'     => $order_id ?: $order_number,
    'order_number' => $order_number,
    'message'      => 'Đặt hàng thành công.'
  ]);
}
add_action('wp_ajax_nopriv_mm_submit_consultation', 'mm_handle_consultation_submission');
add_action('wp_ajax_mm_submit_consultation', 'mm_handle_consultation_submission');


