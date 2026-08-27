<?php
if (!defined('ABSPATH')) exit;

define('MM_THEME_VERSION','1.0.0');

add_action('after_setup_theme', function(){
  add_theme_support('title-tag');
  add_theme_support('post-thumbnails');
  add_theme_support('html5', ['search-form','gallery','caption','style','script']);
  add_theme_support('woocommerce');
  register_nav_menus(['primary'=>__('Primary Menu','memomind-clone'),'footer'=>__('Footer Menu','memomind-clone')]);
});

function mm_routes(){
  static $routes=null;
  if ($routes===null){
    $json=@file_get_contents(get_template_directory().'/routes.json');
    $routes=$json ? json_decode($json,true) : [];
  }
  return is_array($routes)?$routes:[];
}

function mm_current_route(){
  $path=parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
  $path='/' . trim((string)$path,'/') . '/';
  if ($path==='//') $path='/';
  return $path;
}

function mm_capture_hook($hook){
  ob_start(); do_action($hook); return ob_get_clean();
}

// Snapshots already contain their own SEO title and canonical metadata. Keep
// WordPress assets from wp_head(), but discard the duplicate generated title.
function mm_snapshot_wp_head(){
  $head=mm_capture_hook('wp_head');
  return preg_replace('#<title\b[^>]*>.*?</title>\s*#is','',$head);
}

function mm_render_snapshot($file){
  $is_support=$file==='@support';
  $path=get_template_directory().'/views/'.($is_support?'pages__about-us.html':$file);
  if(!is_readable($path)) return false;
  // WordPress reaches template_redirect with an unmatched snapshot route marked
  // as 404. Correct that state before wp_head() so SEO plugins do not emit
  // noindex/404 metadata for a page that is about to render successfully.
  global $wp_query;
  if($wp_query instanceof WP_Query) $wp_query->is_404=false;
  status_header(200);
  $html=file_get_contents($path);
  $asset=trailingslashit(get_template_directory_uri()).'assets';
  $theme=trailingslashit(get_template_directory_uri()).'assets';
  $replace=[
    '__MM_ASSET__'=>$asset,
    '__MM_THEME__'=>$theme,
    '__MM_HOME__'=>home_url(),
    '__MM_ADMIN_POST__'=>admin_url('admin-post.php'),
    '__MM_WP_HEAD__'=>mm_snapshot_wp_head(),
    '__MM_WP_FOOTER__'=>mm_capture_hook('wp_footer'),
  ];
  $html=strtr($html,$replace);
  if($is_support){
    $html=preg_replace('#<title\b[^>]*>.*?</title>#is','<title>Trung tâm hỗ trợ MemoMind</title>',$html,1);
    $html=preg_replace('#<main\b[^>]*>.*?</main>#is','<main class="anchor" id="main">'.mm_support_content().'</main>',$html,1);
  }
  // ES-module imports inside theme.js use the snapshot's import map. Point its
  // vendor/theme entries at the mirrored files; otherwise the whole module
  // aborts offline and drawers, galleries, accordions, and product controls do
  // not register at all.
  $html=str_replace(
    ['//www.memo-mind.com/cdn/shop/t/9/assets/vendor.min.js','//www.memo-mind.com/cdn/shop/t/9/assets/theme.js'],
    [$asset.'/cdn/shop/t/9/assets/vendor.min.js',$asset.'/cdn/shop/t/9/assets/theme.js'],
    $html
  );
  // Normalize mirrored relative asset paths from every snapshot depth.
  // The original snapshots use cdn/, ../cdn/, ../../cdn/ (and the same for s/).
  // A partial ../cdn/ replacement corrupts ../../cdn/ into ../<absolute URL>,
  // which breaks local fonts/backgrounds on nested WordPress routes.
  $html=str_replace(
    [
      '../../../cdn/','../../cdn/','../cdn/',
      '../../../s/','../../s/','../s/',
      '..\/..\/..\/cdn\/','..\/..\/cdn\/','..\/cdn\/',
      '..\/..\/..\/s\/','..\/..\/s\/','..\/s\/'
    ],
    [
      $asset.'/cdn/',$asset.'/cdn/',$asset.'/cdn/',
      $asset.'/s/',$asset.'/s/',$asset.'/s/',
      $asset.'\/cdn\/',$asset.'\/cdn\/',$asset.'\/cdn\/',
      $asset.'\/s\/',$asset.'\/s\/',$asset.'\/s\/'
    ],
    $html
  );
  // Root snapshots also contain bare relative cdn/ and s/ URLs.
  $html=str_replace(
    ['"cdn/','\'cdn/','(cdn/','"s/','\'s/','(s/'],
    ['"'.$asset.'/cdn/','\''.$asset.'/cdn/','('.$asset.'/cdn/','"'.$asset.'/s/','\''.$asset.'/s/','('.$asset.'/s/'],
    $html
  );

  // Vietnamese font fix. Several original XGIMI/Shopify font subsets do not
  // contain Vietnamese glyphs, which causes mixed fallback fonts after translation.
  // Reuse the full local Manrope files already bundled with the mirror and keep
  // the original family names so layout/CSS selectors remain unchanged.
  $font_fix='<style id="mm-vietnamese-font-fix">'
    .'@font-face{font-family:Manrope;src:url("'.$asset.'/s/manrope/v20/xn7_YHE41ni1AdIRqAuZuw1Bx9mbZk79FO_F.ttf") format("truetype");font-style:normal;font-weight:400;font-display:swap}'
    .'@font-face{font-family:Manrope;src:url("'.$asset.'/s/manrope/v20/xn7_YHE41ni1AdIRqAuZuw1Bx9mbZk79FO_F.ttf") format("truetype");font-style:normal;font-weight:500;font-display:swap}'
    .'@font-face{font-family:Manrope;src:url("'.$asset.'/s/manrope/v20/xn7_YHE41ni1AdIRqAuZuw1Bx9mbZk4jE-_F.ttf") format("truetype");font-style:normal;font-weight:600;font-display:swap}'
    .'@font-face{font-family:Manrope;src:url("'.$asset.'/s/manrope/v20/xn7_YHE41ni1AdIRqAuZuw1Bx9mbZk4aE-_F.ttf") format("truetype");font-style:normal;font-weight:700;font-display:swap}'
    .'@font-face{font-family:MiSansLight;src:url("'.$asset.'/cdn/shop/files/MiSansLight.ttf") format("truetype");font-style:normal;font-weight:400;font-display:swap}'
    .'@font-face{font-family:MiSans;src:url("'.$asset.'/cdn/shop/files/MiSansLight.ttf") format("truetype");font-style:normal;font-weight:400;font-display:swap}'
    .'@font-face{font-family:MiSansRegular;src:url("'.$asset.'/s/manrope/v20/xn7_YHE41ni1AdIRqAuZuw1Bx9mbZk79FO_F.ttf") format("truetype");font-style:normal;font-weight:400;font-display:swap}'
    .'@font-face{font-family:MiSansMedium;src:url("'.$asset.'/s/manrope/v20/xn7_YHE41ni1AdIRqAuZuw1Bx9mbZk4jE-_F.ttf") format("truetype");font-style:normal;font-weight:400;font-display:swap}'
    .'@font-face{font-family:MiSansBold;src:url("'.$asset.'/s/manrope/v20/xn7_YHE41ni1AdIRqAuZuw1Bx9mbZk4aE-_F.ttf") format("truetype");font-style:normal;font-weight:400;font-display:swap}'
    .'</style>';
  $html=preg_replace('#</head>#i',$font_fix.'</head>',$html,1);
  // The mirror also placed the original image query string after a width
  // descriptor ("image.webp 600w?v=... 600w"). Move it back onto the URL.
  $html=preg_replace('/\s+\d+w(\?[^,\s]+)\s+(\d+w)/i','$1 $2',$html);
  // Repair any remaining scraper suffixes such as "600wp"/"900wg".
  // They are invalid srcset width descriptors and make browsers drop every
  // responsive candidate. Preserve the intended trailing "w" descriptor.
  $html=preg_replace('/(\s\d+w)[pg](?=\?|[\s,])/i','$1',$html);
  // Normalize the two Shopify-only destinations that appear throughout the
  // snapshots. This also works when WordPress is installed in a subdirectory.
  $html=preg_replace('#href=(["\'])(?:\./|\.\./)*index\.htm\1#i','href=$1'.esc_url(home_url('/')).'$1',$html);
  $html=preg_replace('#href=(["\'])/account/?\1#i','href=$1'.esc_url(home_url('/my-account/')).'$1',$html);
  $html=preg_replace('#href=(["\'])https?://support\.memo-mind\.com/hc/en-gb/?\1#i','href=$1'.esc_url(home_url('/support/')).'$1',$html);
  // WordPress search query should populate the cloned search input.
  if (isset($_GET['s'])) $html=str_replace('name="s"', 'name="s" value="'.esc_attr(wp_unslash($_GET['s'])).'"', $html);
  nocache_headers();
  echo $html;
  return true;
}

function mm_support_content(){
  $markup=<<<'HTML'
<style>
body:has(.mm-support)>.shopify-section-group-header-group,body:has(.mm-support)>.shopify-section-group-footer-group,body:has(.mm-support) #shopify-section-sections--18668046647409__announcement-bar,body:has(.mm-support) #shopify-section-sections--18668046647409__header{display:none!important}.mm-support{font-family:Manrope,Arial,sans-serif;color:#111;background:#fff}.mm-support *{box-sizing:border-box}.mm-support__nav{height:88px;display:grid;grid-template-columns:1fr auto 1fr;align-items:center;padding:0 max(40px,9vw);background:#fff}.mm-support__logo{font-size:35px;font-weight:600;letter-spacing:-1.5px;color:#111;text-decoration:none}.mm-support__menu{display:flex;gap:42px}.mm-support__menu a,.mm-support__signin{font-size:16px;color:#111;text-decoration:none}.mm-support__signin{justify-self:end;color:#0868ce}.mm-support__hero{position:relative;min-height:760px;padding:290px 24px 80px;text-align:center;color:#fff;background:#222 url('__MM_SUPPORT_ASSET__/cdn/shop/files/banner_0425.webp') center/cover no-repeat}.mm-support__hero:before{content:'';position:absolute;inset:0;background:rgba(0,0,0,.2)}.mm-support__hero>*{position:relative}.mm-support__hero h1{font-size:clamp(34px,3vw,46px);line-height:1.45;margin:0 0 40px;font-weight:700}.mm-support__eyebrow,.mm-support__lead{display:none}.mm-support__search{display:flex;max-width:890px;height:74px;margin:auto;background:#fff;border:1px solid #ddd;border-radius:999px;overflow:hidden;text-align:left}.mm-support__search:before{content:'⌕';color:#aaa;font-size:38px;line-height:65px;padding-left:34px;transform:rotate(-20deg)}.mm-support__search input{flex:1;min-width:0;border:0;padding:0 22px;font:inherit;font-size:19px;outline:0}.mm-support__search button{display:none}.mm-support__body{max-width:1200px;margin:auto;padding:86px 24px}.mm-support__body>h2{text-align:center;font-size:clamp(30px,4vw,44px);margin:0 0 44px}.mm-support__grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}.mm-support__card{display:block;padding:30px;min-height:190px;border:1px solid #e2e2e2;border-radius:16px;color:inherit;text-decoration:none;transition:.2s}.mm-support__card:hover{transform:translateY(-3px);border-color:#999;box-shadow:0 14px 32px rgba(0,0,0,.07)}.mm-support__icon{display:grid;place-items:center;width:44px;height:44px;border-radius:50%;background:#f1e6d5;font-size:21px}.mm-support__card h3{font-size:21px;margin:22px 0 9px}.mm-support__card p{margin:0;color:#666;line-height:1.5}.mm-support__popular{margin-top:76px;padding-top:60px;border-top:1px solid #e6e6e6}.mm-support__popular h2{font-size:32px;margin:0 0 26px}.mm-support__links{display:grid;grid-template-columns:1fr 1fr;gap:12px 40px}.mm-support__links a{padding:15px 0;border-bottom:1px solid #e8e8e8;color:#111;text-decoration:none}.mm-support__links a:hover{text-decoration:underline}.mm-support__contact{margin-top:72px;padding:42px;border-radius:18px;background:#111;color:#fff;display:flex;align-items:center;justify-content:space-between;gap:28px}.mm-support__contact h2{margin:0 0 8px;font-size:30px}.mm-support__contact p{margin:0;color:#ccc}.mm-support__contact a{padding:14px 24px;border-radius:999px;background:#fff;color:#111;text-decoration:none;white-space:nowrap}@media(max-width:800px){.mm-support__nav{height:70px;padding:0 18px;grid-template-columns:1fr auto}.mm-support__logo{font-size:24px}.mm-support__menu{display:none}.mm-support__hero{min-height:620px;padding:190px 16px 60px;background-position:58% center}.mm-support__search{height:62px}.mm-support__body{padding:58px 16px}.mm-support__grid,.mm-support__links{grid-template-columns:1fr}.mm-support__contact{align-items:flex-start;flex-direction:column}}
.mm-support__search input{color:#555!important;background:#fff}.mm-support__search input::placeholder{color:#777;opacity:1}
</style>
<section class="mm-support">
 <nav class="mm-support__nav"><a class="mm-support__logo" href="__MM_HOME__/">MEMOMIND</a><div class="mm-support__menu"><a href="__MM_HOME__/">Trang chủ</a><a href="__MM_HOME__/pages/memomind-one/">MemoMind One</a><a href="__MM_HOME__/pages/about-us/">Về chúng tôi</a></div><a class="mm-support__signin" href="https://support.memo-mind.com/hc/en-gb/signin">Đăng nhập</a></nav>
 <div class="mm-support__hero"><div class="mm-support__eyebrow">MemoMind</div><h1>Chào mừng đến với<br>Trung tâm hỗ trợ MemoMind</h1><p class="mm-support__lead">Tìm câu trả lời về sản phẩm MemoMind, đơn hàng, giao hàng, đổi trả và thanh toán.</p><form class="mm-support__search" action="https://support.memo-mind.com/hc/en-gb/search" method="get"><input aria-label="Tìm kiếm hỗ trợ" name="query" placeholder="Tìm kiếm" type="search"><button type="submit">Tìm kiếm</button></form></div>
 <div class="mm-support__body"><h2>Tìm hỗ trợ bạn cần</h2><div class="mm-support__grid">
  <a class="mm-support__card" href="https://support.memo-mind.com/hc/en-gb/categories/57915429306649-Product-Support"><span class="mm-support__icon">◎</span><h3>Hỗ trợ sản phẩm</h3><p>Nhận hỗ trợ về tính năng sản phẩm, thiết lập và các vấn đề kỹ thuật.</p></a>
  <a class="mm-support__card" href="https://support.memo-mind.com/hc/en-gb/categories/57800442477209-Shipping-Delivery"><span class="mm-support__icon">◇</span><h3>Vận chuyển &amp; giao hàng</h3><p>Theo dõi đơn hàng và cập nhật tình trạng vận chuyển, giao hàng.</p></a>
  <a class="mm-support__card" href="https://support.memo-mind.com/hc/en-gb/categories/57915607719961-Account-Order"><span class="mm-support__icon">○</span><h3>Tài khoản &amp; đơn hàng</h3><p>Cài đặt tài khoản và quản lý đơn hàng.</p></a>
  <a class="mm-support__card" href="https://support.memo-mind.com/hc/en-gb/categories/57915638839577-Return-Exchange"><span class="mm-support__icon">↻</span><h3>Đổi trả &amp; hoàn hàng</h3><p>Tìm hiểu về chính sách trả hàng, đổi hàng và hoàn tiền.</p></a>
  <a class="mm-support__card" href="https://support.memo-mind.com/hc/en-gb/categories/57915647215897-Payment-Invoice"><span class="mm-support__icon">$</span><h3>Thanh toán &amp; hóa đơn</h3><p>Tìm thông tin về thanh toán, lập hóa đơn và chứng từ.</p></a>
 </div><section class="mm-support__popular"><h2>Câu hỏi phổ biến</h2><div class="mm-support__links">
  <a href="https://support.memo-mind.com/hc/en-gb/categories/57915429306649-Product-Support">MemoMind One có bao nhiêu kiểu dáng khác nhau?</a><a href="https://support.memo-mind.com/hc/en-gb/categories/57800442477209-Shipping-Delivery">Làm thế nào để theo dõi trạng thái đơn hàng?</a><a href="https://support.memo-mind.com/hc/en-gb/categories/57915429306649-Product-Support">Loại gọng nào phù hợp hơn với người có vòng đầu lớn?</a><a href="https://support.memo-mind.com/hc/en-gb/categories/57915638839577-Return-Exchange">Chính sách đổi trả của MemoMind như thế nào?</a><a href="https://support.memo-mind.com/hc/en-gb/categories/57915429306649-Product-Support">Tôi có thể chọn màu gọng không?</a><a href="https://support.memo-mind.com/hc/en-gb/categories/57915647215897-Payment-Invoice">Tại sao thanh toán của tôi không thực hiện được?</a>
 </div></section><div class="mm-support__contact"><div><h2>Vẫn cần hỗ trợ?</h2><p>Hỗ trợ khách hàng &amp; hỗ trợ kỹ thuật · Thứ Hai–Chủ Nhật, 9:00–18:00 CT</p></div><a href="mailto:Support@memo-mind.com">Liên hệ hỗ trợ</a></div></div>
</section>
HTML;
  return str_replace(
    ['__MM_SUPPORT_ASSET__','__MM_HOME__'],
    [esc_url(trailingslashit(get_template_directory_uri()).'assets'),esc_url(home_url())],
    $markup
  );
}

add_action('template_redirect', function(){
  if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) return;
  $route=mm_current_route();
  $routes=mm_routes();
  // WooCommerce native endpoints win only for checkout/account. The supplied cloned cart page remains the visual shell.
  if (preg_match('#^/(checkout|my-account)(/|$)#',$route) && class_exists('WooCommerce')) return;
  if (isset($routes[$route])) { mm_render_snapshot($routes[$route]); exit; }
  // tolerate missing trailing or captured .html links
  $alt=rtrim($route,'/').'.html';
  foreach($routes as $r=>$f){ if(rtrim($r,'/').'.html'===$alt){ mm_render_snapshot($f); exit; } }
}, 0);

add_action('wp_enqueue_scripts', function(){
  wp_enqueue_style('memomind-wp', get_stylesheet_uri(), [], MM_THEME_VERSION);
  wp_localize_script('jquery','MemoMindWP',[]);
});

// Contact modal: emails site admin and safely returns to the source page.
function mm_handle_contact(){
  if ($_SERVER['REQUEST_METHOD']!=='POST') wp_die('Invalid request',403);
  $data=[];
  foreach($_POST as $k=>$v){ if($k==='action') continue; $data[sanitize_key($k)]=is_scalar($v)?sanitize_textarea_field(wp_unslash($v)):''; }
  $lines=[]; foreach($data as $k=>$v){ if($v!=='') $lines[]=$k.': '.$v; }
  $subject='MemoMind website contact';
  $ok=wp_mail(get_option('admin_email'),$subject,implode("\n",$lines));
  $back=wp_get_referer() ?: home_url('/');
  wp_safe_redirect(add_query_arg('contact',$ok?'sent':'error',$back)); exit;
}
add_action('admin_post_nopriv_mm_contact','mm_handle_contact');
add_action('admin_post_mm_contact','mm_handle_contact');

function mm_handle_newsletter(){
  $email='';
  foreach($_POST as $k=>$v){ if(is_scalar($v) && is_email(wp_unslash($v))){$email=sanitize_email(wp_unslash($v)); break;} }
  if($email){
    $sub=get_option('mm_newsletter_subscribers',[]); if(!is_array($sub))$sub=[];
    $sub[$email]=current_time('mysql'); update_option('mm_newsletter_subscribers',$sub,false);
  }
  $back=wp_get_referer() ?: home_url('/'); wp_safe_redirect(add_query_arg('newsletter',$email?'ok':'invalid',$back)); exit;
}
add_action('admin_post_nopriv_mm_newsletter','mm_handle_newsletter');
add_action('admin_post_mm_newsletter','mm_handle_newsletter');

// Shopify-compatible cart API bridge used by the original product JS.
add_action('init', function(){
  $path=parse_url($_SERVER['REQUEST_URI'] ?? '',PHP_URL_PATH);
  if(!in_array($path,['/cart/add.js','/cart.js','/cart/update.js','/cart/change.js','/cart/clear.js'],true)) return;
  if(!class_exists('WooCommerce')) { status_header(501); wp_send_json(['status'=>501,'description'=>'WooCommerce is required for cart actions']); }
  if(function_exists('wc_load_cart')) wc_load_cart();
  if($path==='/cart/add.js'){
    $id=absint($_POST['id'] ?? 0); $qty=max(1,absint($_POST['quantity'] ?? 1));
    $map=get_option('mm_shopify_variant_map',[]); $pid=isset($map[$id])?absint($map[$id]):0;
    if(!$pid){ status_header(422); wp_send_json(['status'=>422,'description'=>'Variant is not mapped to a WooCommerce product']); }
    $key=WC()->cart->add_to_cart($pid,$qty); if(!$key){status_header(422);wp_send_json(['status'=>422,'description'=>'Unable to add item']);}
    $p=wc_get_product($pid); wp_send_json(['id'=>$id,'quantity'=>$qty,'title'=>$p?$p->get_name():'MemoMind One','key'=>$key]);
  }
  if($path==='/cart/clear.js'){ WC()->cart->empty_cart(); wp_send_json(mm_cart_payload()); }
  if($path==='/cart.js'){ wp_send_json(mm_cart_payload()); }
  if($path==='/cart/change.js'){
    $line=max(1,absint($_POST['line'] ?? 1)); $qty=max(0,absint($_POST['quantity'] ?? 0));
    $keys=array_keys(WC()->cart->get_cart()); if(isset($keys[$line-1])) WC()->cart->set_quantity($keys[$line-1],$qty,true);
    wp_send_json(mm_cart_payload());
  }
  if($path==='/cart/update.js'){
    $updates=$_POST['updates'] ?? []; if(is_array($updates)){ foreach($updates as $k=>$q){ WC()->cart->set_quantity(sanitize_text_field($k),max(0,absint($q)),false); } WC()->cart->calculate_totals(); }
    wp_send_json(mm_cart_payload());
  }
}, -1);

function mm_cart_payload(){
  $items=[]; foreach(WC()->cart->get_cart() as $key=>$ci){ $p=$ci['data']; $items[]=['key'=>$key,'quantity'=>$ci['quantity'],'title'=>$p->get_name(),'price'=>(int)round((float)$p->get_price()*100),'url'=>get_permalink($p->get_id())]; }
  return ['token'=>wp_get_session_token(),'item_count'=>WC()->cart->get_cart_contents_count(),'items'=>$items,'total_price'=>(int)round((float)WC()->cart->get_total('edit')*100),'currency'=>get_woocommerce_currency()];
}

// One-click importer for native WP pages/posts and two WooCommerce product shells.
add_action('admin_menu', function(){ add_theme_page('MemoMind Import','MemoMind Import','manage_options','memomind-import','mm_import_screen'); });
function mm_import_screen(){
  if(!current_user_can('manage_options')) return;
  if(isset($_POST['mm_import']) && check_admin_referer('mm_import_action')){ $result=mm_run_import(); echo '<div class="notice notice-success"><p>'.esc_html($result).'</p></div>'; }
  echo '<div class="wrap"><h1>MemoMind Theme Import</h1><p>This creates native WordPress page/post records for editing/SEO while the frontend renderer preserves the supplied clone layout.</p><form method="post">'; wp_nonce_field('mm_import_action'); submit_button('Create / update WordPress content','primary','mm_import'); echo '</form></div>';
}
function mm_run_import(){
  $routes=mm_routes(); $created=0;
  foreach($routes as $route=>$view){
    if($route==='/' || str_starts_with($route,'/fr/') || $route==='/fr/' || str_starts_with($route,'/products/') || str_starts_with($route,'/blogs/') || str_starts_with($route,'/collections/') || str_starts_with($route,'/policies/') || in_array($route,['/cart/','/search/'],true)) continue;
    if(!str_starts_with($route,'/pages/')) continue;
    $slug=trim(substr($route,7),'/'); if(!$slug) continue;
    if(!get_page_by_path($slug)) { wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>ucwords(str_replace('-',' ',$slug)),'post_name'=>$slug]); $created++; }
  }
  // blog articles as posts
  foreach($routes as $route=>$view){
    if(!preg_match('#^/blogs/([^/]+)/([^/]+)/$#',$route,$m)) continue;
    $slug=$m[2]; if(get_page_by_path($slug,OBJECT,'post')) continue;
    wp_insert_post(['post_type'=>'post','post_status'=>'publish','post_title'=>ucwords(str_replace('-',' ',$slug)),'post_name'=>$slug]); $created++;
  }
  if(class_exists('WooCommerce')) mm_create_products();
  flush_rewrite_rules(); return 'Import complete. Created '.$created.' WordPress content records.';
}
function mm_create_products(){
  $map=get_option('mm_shopify_variant_map',[]); if(!is_array($map))$map=[];
  $defs=[
    'memomind-one-standard'=>['MemoMind One - Standard',[43269620105329,43269621907569,43269620138097,43269621940337,43269620170865,43269621973105]],
    'memomind-one-custom'=>['MemoMind One - Custom',[43251053789297,43251092717681,43251092750449,43251092783217,43251092815985,43251092848753,43251053723761]],
  ];
  foreach($defs as $slug=>$def){
    $post=get_page_by_path($slug,OBJECT,'product');
    if(!$post){ $pid=wp_insert_post(['post_type'=>'product','post_status'=>'publish','post_title'=>$def[0],'post_name'=>$slug]); } else $pid=$post->ID;
    if(!$pid) continue;
    update_post_meta($pid,'_regular_price','30'); update_post_meta($pid,'_price','30'); update_post_meta($pid,'_stock_status','instock');
    wp_set_object_terms($pid,'simple','product_type'); foreach($def[1] as $vid)$map[(string)$vid]=$pid;
  }
  update_option('mm_shopify_variant_map',$map,false);
}
