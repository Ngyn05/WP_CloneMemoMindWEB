<?php
if (!defined('ABSPATH')) exit;

define('MM_THEME_VERSION','1.0.0');
require_once get_template_directory().'/inc/blog-admin.php';
require_once get_template_directory().'/inc/product-admin.php';
require_once get_template_directory().'/inc/floating-contact.php';

add_action('after_setup_theme', function(){
  add_theme_support('title-tag');
  add_theme_support('post-thumbnails');
  add_theme_support('html5', ['search-form','gallery','caption','style','script']);
  add_theme_support('woocommerce');
  register_nav_menus(['primary'=>__('Primary Menu','memomind-clone'),'footer'=>__('Footer Menu','memomind-clone')]);
});

add_action('init', function(){
  if (get_option('woocommerce_coming_soon') === 'yes') {
    update_option('woocommerce_coming_soon', 'no');
  }
});

// Production-safe crawl rules. WordPress appends the active sitemap URL, so
// this remains correct when the site is moved from Local to its public host.
add_filter('robots_txt',function($output,$public){
  if(!$public) return "User-agent: *\nDisallow: /\n";
  return "User-agent: *\nAllow: /\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\nDisallow: /cart/\nDisallow: /checkout/\nDisallow: /my-account/\nDisallow: /search/\n\nUser-agent: OAI-SearchBot\nAllow: /\nDisallow: /cart/\nDisallow: /checkout/\nDisallow: /my-account/\nDisallow: /search/\n\nUser-agent: GPTBot\nDisallow: /\n\nSitemap: ".home_url('/wp-sitemap.xml')."\n";
},20,2);

add_filter('wp_robots',function($robots){
  $private_route=is_search()
    || (function_exists('is_cart') && is_cart())
    || (function_exists('is_checkout') && is_checkout())
    || (function_exists('is_account_page') && is_account_page());
  if($private_route){
    $robots['noindex']=true;
    unset($robots['index']);
  }else{
    $robots['index']=true;
    $robots['follow']=true;
    $robots['max-image-preview']='large';
    $robots['max-snippet']=-1;
    $robots['max-video-preview']=-1;
  }
  return $robots;
});

add_action('send_headers',function(){
  if(headers_sent()) return;
  header('X-Content-Type-Options: nosniff');
  header('Referrer-Policy: strict-origin-when-cross-origin');
  header('X-Frame-Options: SAMEORIGIN');
  // The Kivi virtual try-on runs in a cross-origin iframe and requires camera
  // delegation from the top-level document. Keep unrelated sensors disabled.
  header('Permissions-Policy: camera=(self "https://meta.kivisense.com"), geolocation=(), payment=(self)');
  if(is_ssl()) header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
});
remove_action('wp_head','wp_generator');

// Cart, checkout and account URLs are utility endpoints, not landing pages.
// Keep them out of Yoast sitemaps because they redirect or intentionally carry
// noindex, and sitemap entries must be canonical indexable URLs only.
add_filter('wpseo_exclude_from_sitemap_by_post_ids',function($ids){
  foreach(['cart','checkout','my-account'] as $slug){
    $page=get_page_by_path($slug,OBJECT,'page');
    if($page instanceof WP_Post) $ids[]=(int)$page->ID;
  }
  return array_values(array_unique(array_map('intval',$ids)));
});

function mm_routes(){
  static $routes=null;
  if ($routes===null){
    $json=@file_get_contents(get_template_directory().'/routes.json');
    $routes=$json ? json_decode($json,true) : [];
    // The Vietnamese site is single-language. Ignore legacy French snapshots.
    if (is_array($routes)) {
      $routes=array_filter($routes, static fn($file,$route)=>!str_starts_with($route,'/fr/'), ARRAY_FILTER_USE_BOTH);
    }
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

function mm_html_upsert_meta($html,$attribute,$key,$content){
  $tag='<meta '.$attribute.'="'.esc_attr($key).'" content="'.esc_attr($content).'">';
  $pattern='#<meta\b(?=[^>]*\b'.preg_quote($attribute,'#').'=["\']'.preg_quote($key,'#').'["\'])[^>]*>#i';
  return preg_match($pattern,$html)
    ? preg_replace($pattern,$tag,$html,1)
    : preg_replace('#</head>#i',$tag."\n</head>",$html,1);
}

function mm_snapshot_description($html,$file){
  if(preg_match('#<meta\b(?=[^>]*\bname=["\']description["\'])[^>]*>#i',$html,$meta)
    && preg_match('#\bcontent=(["\'])(.*?)\1#is',$meta[0],$match)){
    $description=trim(wp_strip_all_tags(html_entity_decode($match[2],ENT_QUOTES|ENT_HTML5,'UTF-8')));
    if($description!=='') return $description;
  }
  $fallbacks=[
    'blogs__buyers-guide.html'=>'Hướng dẫn chọn và so sánh kính AI MemoMind One, từ thiết kế, kính thuốc đến quyền riêng tư và trải nghiệm sử dụng thực tế.',
    'blogs__in-the-moment.html'=>'Khám phá cách kính AI MemoMind One hỗ trợ ghi nhớ, dịch thuật, phụ đề, công việc và những tình huống thường ngày.',
    'blogs__news.html'=>'Tin tức mới nhất về MemoMind One, hoạt động sản phẩm, sự kiện công nghệ và các cập nhật từ MemoMind.',
    'blogs__tech-hub.html'=>'Kiến thức chuyên sâu về kính AI, màn hình, âm thanh, pin, hiệu năng và công nghệ trên MemoMind One.',
    'policies__privacy-policy.html'=>'Chính sách bảo mật của MemoMind: cách chúng tôi thu thập, sử dụng, lưu trữ và bảo vệ dữ liệu của khách hàng.',
    'policies__terms-of-service.html'=>'Điều khoản sử dụng website, sản phẩm và dịch vụ MemoMind dành cho khách hàng tại Việt Nam.',
  ];
  if(isset($fallbacks[$file])) return $fallbacks[$file];
  if(preg_match('#<title\b[^>]*>(.*?)</title>#is',$html,$match)){
    return trim(wp_strip_all_tags($match[1])).' — Thông tin chính thức từ MemoMind Việt Nam.';
  }
  return 'Thông tin sản phẩm, hướng dẫn và hỗ trợ khách hàng MemoMind tại Việt Nam.';
}

function mm_enhance_snapshot_seo($html,$file){
  $route=mm_current_route();
  $canonical=home_url($route);
  $site=untrailingslashit(home_url('/'));
  $asset=trailingslashit(get_template_directory_uri()).'assets';
  $description=str_starts_with($route,'/support/')
    ? 'Thông tin hỗ trợ, bảo hành, vận chuyển, thanh toán và giải đáp về sản phẩm MemoMind One tại Việt Nam.'
    : mm_snapshot_description($html,$file);

  // This long-form article uses one page-level H1; its four chapter headings
  // were imported as additional H1 elements and must remain H2 semantically.
  if($file==='blogs__in-the-moment__memomind-ai-glasses-4-real-life-moments.html'){
    $html=preg_replace_callback(
      '#<h1\b([^>]*\bclass=["\'][^"\']*\bpost-title\b[^"\']*["\'][^>]*)>(.*?)</h1>#is',
      static fn($match)=>'<h2'.$match[1].'>'.$match[2].'</h2>',
      $html
    );
  }

  // Move mirrored first-party URLs to the actual Vietnamese site. Assets use
  // the local mirror, while page/schema URLs use the canonical WordPress host.
  $html=str_replace(
    ['https://www.memo-mind.com/cdn/','http://www.memo-mind.com/cdn/','https:\/\/www.memo-mind.com\/cdn\/','http:\/\/www.memo-mind.com\/cdn\/'],
    [$asset.'/cdn/',$asset.'/cdn/',str_replace('/','\\/',$asset.'/cdn/'),str_replace('/','\\/',$asset.'/cdn/')],
    $html
  );
  $html=str_replace(
    ['https://www.memo-mind.com','http://www.memo-mind.com','https:\/\/www.memo-mind.com','http:\/\/www.memo-mind.com'],
    [$site,$site,str_replace('/','\\/',$site),str_replace('/','\\/',$site)],
    $html
  );

  $canonical_tag='<link rel="canonical" href="'.esc_url($canonical).'">';
  $html=preg_match('#<link\b(?=[^>]*\brel=["\']canonical["\'])[^>]*>#i',$html)
    ? preg_replace('#<link\b(?=[^>]*\brel=["\']canonical["\'])[^>]*>#i',$canonical_tag,$html,1)
    : preg_replace('#</head>#i',$canonical_tag."\n</head>",$html,1);

  $indexable=!in_array($route,['/search/','/cart/','/checkout/','/my-account/','/pages/data-sharing-opt-out/'],true);
  $robots=$indexable
    ? 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1'
    : 'noindex, follow, max-image-preview:large';
  $html=mm_html_upsert_meta($html,'name','description',$description);
  $html=mm_html_upsert_meta($html,'name','robots',$robots);
  $html=mm_html_upsert_meta($html,'property','og:description',$description);
  $html=mm_html_upsert_meta($html,'property','og:url',$canonical);
  $html=mm_html_upsert_meta($html,'property','og:site_name','MemoMind Việt Nam');
  $html=mm_html_upsert_meta($html,'property','og:locale','vi_VN');
  $html=mm_html_upsert_meta($html,'name','twitter:description',$description);
  $html=mm_html_upsert_meta($html,'name','twitter:card','summary_large_image');

  // One stable favicon declaration. The bundled SVG is square and crawlable;
  // do not advertise the SVG duplicate as an Apple PNG.
  $favicon=esc_url($asset.'/cdn/shop/files/MemoMind_Website_icon_efebfcc3-e8b6-4bb4-ae22-20568ee93b54.svg');
  $html=preg_replace('#<link\b(?=[^>]*\brel=["\'](?:shortcut icon|icon|apple-touch-icon)["\'])[^>]*>\s*#i','',$html);
  $icons='<link rel="icon" href="'.$favicon.'" type="image/svg+xml" sizes="any">'
    .'<link rel="apple-touch-icon" href="'.esc_url($asset.'/apple-touch-icon.png').'" sizes="180x180">'
    .'<link rel="manifest" href="'.esc_url($asset.'/site.webmanifest').'">'
    .'<meta name="theme-color" content="#ede5da">';
  $html=preg_replace('#</head>#i',$icons."\n</head>",$html,1);

  if($route==='/'){
    // Replace the imported global identity graph with one consistent graph for
    // the Vietnamese storefront. Legal name and tax ID stay omitted until the
    // owner supplies verified legal data.
    $html=preg_replace('#<script\b[^>]*type=["\']application/ld\+json["\'][^>]*>\s*\[\s*\{\s*["\']@context["\'][\s\S]*?</script>#i','',$html,1);
    $graph=[
      '@context'=>'https://schema.org',
      '@graph'=>[
        [
          '@type'=>'WebSite','@id'=>$site.'/#website','url'=>$site.'/','name'=>'MemoMind Việt Nam',
          'alternateName'=>['MemoMind','MEMOMIND VN'],'publisher'=>['@id'=>$site.'/#organization'],
        ],
        [
          '@type'=>'OnlineStore','@id'=>$site.'/#organization','name'=>'MemoMind Việt Nam','url'=>$site.'/',
          'logo'=>['@type'=>'ImageObject','url'=>$favicon],
          'description'=>'Đơn vị cung cấp kính AI MemoMind và hỗ trợ khách hàng tại Việt Nam.',
          'telephone'=>'+841900638400','email'=>'contact@memomind.vn',
          'address'=>[
            ['@type'=>'PostalAddress','streetAddress'=>'226 Đường Láng, Phường Thịnh Quang, Quận Đống Đa','addressLocality'=>'Hà Nội','addressCountry'=>'VN'],
            ['@type'=>'PostalAddress','streetAddress'=>'137 Hòa Hưng, Phường Hòa Hưng','addressLocality'=>'Thành phố Hồ Chí Minh','addressCountry'=>'VN'],
          ],
          'contactPoint'=>['@type'=>'ContactPoint','contactType'=>'customer service','telephone'=>'+841900638400','email'=>'contact@memomind.vn','availableLanguage'=>['vi','en']],
          'sameAs'=>['https://www.instagram.com/memomind.official/','https://www.youtube.com/@MemoMind.official'],
        ],
      ],
    ];
    $json='<script type="application/ld+json" id="mm-site-identity-schema">'.wp_json_encode($graph,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).'</script>';
    $html=preg_replace('#</head>#i',$json."\n</head>",$html,1);
  }

  if(str_starts_with($route,'/products/')){
    // Imported Shopify offers contained USD campaign deposits and translated
    // schema type names. Prices are intentionally hidden on this consultation
    // storefront, so publishing an Offer would contradict the visible page.
    $html=preg_replace('#<meta\b(?=[^>]*\bproperty=["\']product:(?:price:amount|price:currency|availability)["\'])[^>]*>\s*#i','',$html);
    $html=preg_replace('#<script\b[^>]*type=["\']application/ld\+json["\'][^>]*>\s*\{(?=[^{}]*["\']@context["\'])[^<]*["\']@type["\']\s*:\s*["\']ProductGroup["\'][^<]*</script>#i','',$html,1);
    $is_custom=str_contains($file,'custom');
    $product=[
      '@context'=>'https://schema.org','@type'=>'ProductGroup','@id'=>$canonical.'#product',
      'name'=>$is_custom?'MemoMind One Custom':'MemoMind One Standard','url'=>$canonical,
      'description'=>$description,'brand'=>['@type'=>'Brand','name'=>'MemoMind'],
      'category'=>'Kính thông minh AI','productGroupID'=>$is_custom?'MM-ONE-CUSTOM':'MM-ONE-STANDARD',
    ];
    if(preg_match('#<meta\b(?=[^>]*\bproperty=["\']og:image["\'])[^>]*\bcontent=(["\'])(.*?)\1#i',$html,$match)) $product['image']=html_entity_decode($match[2],ENT_QUOTES|ENT_HTML5,'UTF-8');
    $json='<script type="application/ld+json" id="mm-product-schema">'.wp_json_encode($product,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).'</script>';
    $html=preg_replace('#</head>#i',$json."\n</head>",$html,1);
  }
  return $html;
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
  // Remove Shopify-account/payment bootstraps that do not apply to the native
  // WordPress storefront. Their dynamic module graph is not part of the mirror
  // and otherwise creates repeated local 404s and unnecessary main-thread work.
  $html=preg_replace('#<script\b[^>]*(?:id=["\'](?:shop-js-analytics|captcha-bootstrap)["\']|src=["\'][^"\']*(?:shop-js/modules|shopify_pay|origin_trials|load_feature)[^"\']*["\'])[^>]*>.*?</script>\s*#is','',$html);
  $html=preg_replace('#<script\b[^>]*>[^<]*(?:shop-js/modules|featureAssets\[["\']shop-js["\']\])[^<]*</script>\s*#is','',$html);
  $html=preg_replace('#<script\b[^>]*(?:data-source-attribution=["\']shopify\.dynamic_checkout[^"\']*["\']|id=["\']shopify-features["\']|src=["\'][^"\']*checkouts/internal/[^"\']*["\'])[^>]*>.*?</script>\s*#is','',$html);
  $html=preg_replace('#<meta\b[^>]*name=["\']shopify-checkout-api-token["\'][^>]*>\s*#i','',$html);
  $html=preg_replace('#<(?:link|style)\b[^>]*id=["\']shopify-accelerated-checkout[^"\']*["\'][^>]*>(?:.*?</style>)?\s*#is','',$html);
  // Search is disabled for this single-language storefront. Removing the
  // orphaned Shopify drawer also prevents PredictiveSearch from connecting to
  // a missing trigger and throwing on every page load.
  $html=preg_replace('#<section\b[^>]*class=["\'][^"\']*shopify-section--search-drawer[^"\']*["\'][^>]*>.*?</section>\s*#is','',$html,1);
  if(function_exists('mm_blog_apply_post_to_snapshot')){
    $html=mm_blog_apply_post_to_snapshot($html,$file);
    if($html===false) return false;
  }
  if(function_exists('mm_product_apply_to_snapshot')){
    $html=mm_product_apply_to_snapshot($html,$file);
    if($html===false) return false;
  }
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
  // The shared About/Collection hero derivatives are heavily compressed.
  // Force the sharp masters everywhere this banner appears.
  if(in_array($file,['collections__all.html','pages__about-us.html'],true)){
    $html=preg_replace_callback(
      '#<img\b[^>]*ABOUT_US_KV-PC_2x_m_compressed\.webp[^>]*>#i',
      static fn($match)=>preg_replace('/\s+srcset=(["\']).*?\1/i','',$match[0]),
      $html,
      1
    );
    $html=preg_replace_callback(
      '#<source\b[^>]*ABOUT_US_KV-APP_2x_m_compressed[^>]*>#i',
      static function($match) use ($asset){
        $mobile_master=$asset.'/cdn/shop/files/ABOUT_US_KV-APP_2x_m_compressed-3.webp';
        return preg_replace('/srcset=(["\']).*?\1/i','srcset="'.$mobile_master.'"',$match[0]);
      },
      $html,
      1
    );
  }
  // Use one public contact address everywhere, including visible text,
  // mailto links, metadata and form placeholders embedded in snapshots.
  $html=preg_replace(
    '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i',
    'contact@memomind.vn',
    $html
  );
  // Kickstarter was only used by the original campaign. Route every campaign
  // purchase CTA into the corresponding local WooCommerce product instead.
  $product_path=str_contains($file,'memomind-one-custom')
    ? '/products/memomind-one-custom/'
    : '/products/memomind-one-standard/';
  $product_url=esc_url(home_url($product_path));
  $html=preg_replace_callback(
    '#<a\b[^>]*href=["\']https?://(?:www\.)?kickstarter\.com/[^"\']*["\'][^>]*>.*?</a>#is',
    static function($match) use ($product_url){
      $link=$match[0];
      if(!preg_match('/Mua trên Kickstarter|Ủng hộ trên Kickstarter|Tiết kiệm đến 43% trên Kickstarter/i',$link)) return $link;
      $link=preg_replace('#href=(["\'])https?://(?:www\.)?kickstarter\.com/.*?\1#i','href="'.$product_url.'"',$link,1);
      $link=preg_replace('/\s+target=(["\'])_blank\1/i','',$link);
      $link=preg_replace('#\s+title=(["\'])https?://(?:www\.)?kickstarter\.com/.*?\1#i','',$link);
      $link=str_replace(
        ['MemoMind One | Tiết kiệm đến 43% trên Kickstarter','Mua trên Kickstarter','Ủng hộ trên Kickstarter'],
        ['MemoMind One | Mua ngay','Mua ngay','Mua ngay'],
        $link
      );
      return $link;
    },
    $html
  );
  // Any remaining Kickstarter links are editorial links inside older posts.
  // Keep their anchor text, but send visitors to the local product instead.
  $html=preg_replace(
    '#href=(["\'])https?://(?:www\.)?kickstarter\.com/[^"\']*\1#i',
    'href="'.$product_url.'"',
    $html
  );
  $html=str_replace('"Back on Kickstarter"','"Mua ngay"',$html);
  if($is_support){
    $support_titles=[
      'cac-kieu-gong-memomind-one'=>'Các kiểu gọng MemoMind One',
      'gong-phu-hop-nguoi-dau-lon'=>'Chọn gọng MemoMind cho người có vòng đầu lớn',
      'chon-mau-gong'=>'Các màu gọng MemoMind One',
      'theo-doi-don-hang'=>'Theo dõi đơn hàng MemoMind',
      'chinh-sach-doi-tra'=>'Chính sách đổi trả MemoMind',
      'thanh-toan-khong-thanh-cong'=>'Hỗ trợ thanh toán MemoMind',
      'chinh-sach-bao-hanh'=>'Chính sách bảo hành MemoMind One',
      'van-chuyen-giao-hang'=>'Chính sách vận chuyển MemoMind',
      'phuong-thuc-thanh-toan'=>'Phương thức thanh toán MemoMind',
    ];
    $support_slug=trim(substr(mm_current_route(),strlen('/support/')),'/');
    $support_title=$support_titles[$support_slug] ?? 'Trung tâm hỗ trợ MemoMind';
    $html=preg_replace('#<title\b[^>]*>.*?</title>#is','<title>'.esc_html($support_title).' | MemoMind Việt Nam</title>',$html,1);
    $html=preg_replace('#<main\b[^>]*>.*?</main>#is','<main class="anchor" id="main">'.mm_support_content().'</main>',$html,1);
  }
  // Support content is injected after the first normalization pass.
  $html=preg_replace(
    '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i',
    'contact@memomind.vn',
    $html
  );
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
    .'html,body{max-width:100%;overflow-x:clip}'
    .'.header__account-link,.header__search-link,.menu-drawer__footer-item:has(a[href*="/my-account/"]),.menu-drawer__footer-item:has(a[href="/account"]),a[href="/account"],a[href*="/my-account/"],a[href*="/search/"]{display:none!important}'
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
  // Unified Vietnamese customer-support footer.
  $support_footer=<<<'HTML'
<footer class="mm-site-footer">
  <div class="mm-site-footer__top">
    <a class="mm-site-footer__brand" href="__MM_HOME__/" aria-label="MemoMind - Trang chủ">MEMOMIND <span><svg viewBox="0 0 30 20" width="22" height="14.6" style="border-radius:2.5px;flex-shrink:0;box-shadow:0 0 2px rgba(0,0,0,0.5);" aria-hidden="true"><rect width="30" height="20" fill="#da251d"/><polygon points="15,3 16.8,8.2 22.3,8.2 17.8,11.5 19.5,16.8 15,13.5 10.5,16.8 12.2,11.5 7.7,8.2 13.2,8.2" fill="#ff0"/></svg>VN</span></a>
    <p>Kính AI thông minh cho cuộc sống hiện đại</p>
  </div>
  <div class="mm-site-footer__divider"></div>
  <h2>HỖ TRỢ KHÁCH HÀNG</h2>
  <div class="mm-site-footer__grid">
    <article class="mm-site-footer__card">
      <div class="mm-site-footer__card-head"><span class="mm-site-footer__icon" aria-hidden="true">🏛️</span><span class="mm-site-footer__badge">MIỀN BẮC</span></div>
      <h3>HỖ TRỢ KHÁCH HÀNG MIỀN BẮC</h3>
      <p class="mm-site-footer__address"><span aria-hidden="true">⌖</span> 226 Đường Láng, Phường Thịnh Quang, Quận Đống Đa, Hà Nội</p>
      <a class="mm-site-footer__phone" href="tel:02473053268"><span aria-hidden="true">☎</span> 024.7305.3268</a>
    </article>
    <article class="mm-site-footer__card">
      <div class="mm-site-footer__card-head"><span class="mm-site-footer__icon" aria-hidden="true">🏢</span><span class="mm-site-footer__badge">MIỀN NAM</span></div>
      <h3>HỖ TRỢ KHÁCH HÀNG MIỀN NAM</h3>
      <p class="mm-site-footer__address"><span aria-hidden="true">⌖</span> 137 Hòa Hưng, Phường Hòa Hưng, TP. Hồ Chí Minh</p>
      <a class="mm-site-footer__phone" href="tel:02873053268"><span aria-hidden="true">☎</span> 028.7305.3268</a>
    </article>
    <article class="mm-site-footer__card mm-site-footer__card--primary">
      <div class="mm-site-footer__card-head"><span class="mm-site-footer__icon" aria-hidden="true">☎</span><span class="mm-site-footer__badge">HOTLINE TỔNG ĐÀI</span></div>
      <h3>TƯ VẤN & HỖ TRỢ TOÀN QUỐC</h3>
      <a class="mm-site-footer__contact" href="tel:1900638400">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink:0;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
        <span>1900.63.8400</span>
      </a>
      <p>Hỗ trợ và tư vấn khách hàng mọi lúc, mọi nơi.</p>
    </article>
  </div>
  <nav class="mm-site-footer__links" aria-label="Liên kết cuối trang">
    <a href="__MM_HOME__/about-us/">Giới thiệu</a><span></span>
    <a href="__MM_HOME__/contact/">Liên hệ</a><span></span>
    <a href="__MM_HOME__/support/chinh-sach-bao-hanh/">Bảo hành</a><span></span>
    <a href="__MM_HOME__/support/chinh-sach-doi-tra/">Đổi trả &amp; hoàn tiền</a><span></span>
    <a href="__MM_HOME__/support/van-chuyen-giao-hang/">Vận chuyển</a><span></span>
    <a href="__MM_HOME__/support/phuong-thuc-thanh-toan/">Thanh toán</a><span></span>
    <a href="__MM_HOME__/policies/privacy-policy/">Bảo mật</a><span></span>
    <a href="__MM_HOME__/policies/terms-of-service/">Điều khoản</a>
  </nav>
  <div class="mm-site-footer__legal"><p>Email: <a href="mailto:contact@memomind.vn">contact@memomind.vn</a> · Giờ hỗ trợ: Thứ Hai–Chủ Nhật, 9:00–18:00</p><p>© 2026 MemoMind Việt Nam. Thông tin sản phẩm có thể được cập nhật.</p></div>
</footer>
<style id="mm-site-footer-style">
.memomind-footer__subscribe,.memomind-footer__panel:has(.memomind-footer__panel-subscribe){display:none!important}.memomind-footer__desktop-grid{grid-template-columns:1fr 1fr 1fr 1.4fr!important}.memomind-footer__contact-panel{grid-column:auto!important}.mm-site-footer{box-sizing:border-box;background:#222;color:#fff;padding:30px clamp(22px,4vw,64px) 12px;font-family:Manrope,Arial,sans-serif}.mm-site-footer *{box-sizing:border-box}.mm-site-footer__top{display:flex;align-items:center;justify-content:space-between;gap:24px;min-height:64px}.mm-site-footer__brand{display:inline-flex;align-items:center;color:#fff;text-decoration:none;font-size:31px;font-weight:600;letter-spacing:5px}.mm-site-footer__brand span{display:inline-flex;align-items:center;gap:6px;margin-left:8px;padding:4px 10px;border:1px solid #555;border-radius:8px;background:#2e2e2e;color:#eee;font-size:15px;font-weight:700;letter-spacing:1px;vertical-align:middle}.mm-site-footer__top p{margin:0;color:#aaa;font-size:17px}.mm-site-footer__divider{height:1px;background:#3d3d3d;margin:0 0 52px}.mm-site-footer h2{margin:0 0 42px;font-size:24px}.mm-site-footer__grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:30px}.mm-site-footer__card{min-height:305px;padding:30px;border:1px solid #484848;border-radius:17px;background:#292929}.mm-site-footer__card--primary{border-color:#5a5a5a;background:#2c2c2c}.mm-site-footer__card-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:22px}.mm-site-footer__icon{display:grid;place-items:center;width:54px;height:54px;border:1px solid #666;border-radius:13px;background:#333;color:#eee;font-size:23px}.mm-site-footer__badge{padding:7px 13px;border:1px solid #606060;border-radius:999px;background:#333;color:#ddd;font-size:13px;font-weight:700}.mm-site-footer__card h3{margin:0 0 14px;font-size:20px}.mm-site-footer__card p{min-height:52px;margin:0;color:#bbb;font-size:15px;line-height:1.65}.mm-site-footer__address{display:flex;gap:10px}.mm-site-footer__address span{flex:0 0 auto;color:#ddd;font-size:18px}.mm-site-footer__phone{display:block;margin-top:21px;padding-top:20px;border-top:1px solid #444;color:#fff;text-decoration:none;font-size:21px;font-weight:700}.mm-site-footer__phone span{margin-right:9px;color:#aaa}.mm-site-footer__contact{display:flex;align-items:center;justify-content:center;gap:10px;margin:14px 0 28px;padding:15px 22px;border-radius:999px;background:linear-gradient(135deg,#1b6ef3 0%,#0852d4 100%);color:#ffffff;text-align:center;text-decoration:none;font-size:19px;font-weight:800;letter-spacing:.5px;box-shadow:0 6px 20px rgba(27,110,243,.35);transition:transform .2s,box-shadow .2s;border:none}.mm-site-footer__contact:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(27,110,243,.5);color:#ffffff}.mm-site-footer__card--primary p{min-height:0;text-align:center}.mm-site-footer__links{display:flex;align-items:center;justify-content:center;flex-wrap:wrap;gap:14px 24px;padding:28px 0 0}.mm-site-footer__links a{color:#aaa;text-decoration:none;font-size:15px}.mm-site-footer__links a:hover{color:#fff}.mm-site-footer__links span{width:1px;height:18px;background:#4a4a4a}.mm-site-footer__legal{margin-top:24px;padding-top:20px;border-top:1px solid #3d3d3d;text-align:center;color:#888;font-size:13px;line-height:1.6}.mm-site-footer__legal p{margin:4px 0}.mm-site-footer__legal a{color:#aaa}@media(max-width:900px){.mm-site-footer__grid{grid-template-columns:1fr}.mm-site-footer__card{min-height:0}.mm-site-footer__top{align-items:flex-start;flex-direction:column}.mm-site-footer__divider{margin-top:20px;margin-bottom:36px}.mm-site-footer__links{flex-wrap:wrap;gap:14px 20px}}@media(max-width:520px){.mm-site-footer{padding:28px 16px 12px}.mm-site-footer__brand{font-size:24px}.mm-site-footer__top p{font-size:14px}.mm-site-footer h2{font-size:21px;margin-bottom:26px}.mm-site-footer__grid{gap:16px}.mm-site-footer__card{padding:22px}.mm-site-footer__links span{display:none}.mm-site-footer__links{align-items:flex-start;flex-direction:column}}
</style>
<style id="mm-global-hide-prices">
/* Global hide all prices across the website */
.mrn__price,
.mrn__price-main,
.mrn__price-original,
.mrn__saving,
.mrn__saving-value,
.home-purchase-showcase__price,
.home-purchase-showcase__price-box,
.home-purchase-showcase__current-price,
.home-purchase-showcase__compare-price,
.home-purchase-showcase__price-label,
.home-purchase-showcase__price-value,
.glass-deposit-sale__price,
.glass-deposit-sale__compare-price,
.memomind-article__product-price-row,
.memomind-article__product-price,
.memomind-article__product-compare-price,
.pre-glass-sku-product .pre-glass-sku-product__price-row,
.pre-glass-sku-product .pre-glass-sku-product-switch-card__pricing,
.pre-glass-sku-product .pre-glass-sku-product-switch-card__line,
.pre-glass-sku-product .pre-glass-sku-variant-option__price,
.pre-glass-sku-product .pre-glass-sku-option-card__lens-price,
.pre-glass-sku-product [id*="total-price"],
.price,
.amount,
.woocommerce-Price-amount,
.woocommerce-Price-currencySymbol,
[data-base-price-cents],
.woocommerce-site-visibility-badge,
.woocommerce-store-coming-soon-notice,
#woocommerce-site-visibility-badge,
.components-notice-banner,
.site-visibility-badge,
.woocommerce-site-visibility-notice{
  display:none!important
}
</style>
HTML;
  $support_footer=str_replace('__MM_HOME__',esc_url(home_url()),$support_footer);
  if(str_contains($html,'<!-- BEGIN sections: footer-group -->')){
    $html=str_replace('<!-- BEGIN sections: footer-group -->',$support_footer.'<!-- BEGIN sections: footer-group -->',$html);
  } else {
    $html=preg_replace('#</body>#i',$support_footer.'</body>',$html,1);
  }
  // Normalize the two Shopify-only destinations that appear throughout the
  // snapshots. This also works when WordPress is installed in a subdirectory.
  $html=preg_replace('#href=(["\'])(?:\./|\.\./)*index\.htm\1#i','href=$1'.esc_url(home_url('/')).'$1',$html);
  // Replace stray header cart link/dot with clean Vietnam flag icon
  $vn_flag='<li class="header__vn-flag-item" style="display:inline-flex;align-items:center;margin-left:10px;"><svg viewBox="0 0 30 20" width="24" height="16" style="border-radius:3px;flex-shrink:0;display:block;box-shadow:0 1px 3px rgba(0,0,0,0.25);" aria-label="Việt Nam"><rect width="30" height="20" fill="#da251d"/><polygon points="15,3 16.8,8.2 22.3,8.2 17.8,11.5 19.5,16.8 15,13.5 10.5,16.8 12.2,11.5 7.7,8.2 13.2,8.2" fill="#ff0"/></svg></li>';
  $html=preg_replace('#<li\b[^>]*class=["\'][^"\']*header__cart-link[^"\']*["\'][^>]*>.*?</li>#is', $vn_flag, $html);
  $html=preg_replace('#<section\b[^>]*class=(["\'])[^"\']*shopify-section--cart-drawer[^"\']*\1[^>]*>.*?</section>#is','',$html);
  // Public pages use clean root-level slugs instead of Shopify's /pages/ prefix.
  $html=preg_replace_callback(
    '#href=(["\'])/pages/([^"\']+)/?\1#i',
    static fn($match)=>'href='.$match[1].esc_url(home_url('/'.$match[2].'/')).$match[1],
    $html
  );
  $html=preg_replace('#href=(["\'])/account/?\1#i','href=$1'.esc_url(home_url('/my-account/')).'$1',$html);
  $html=preg_replace('#href=(["\'])https?://support\.memo-mind\.com/hc/en-gb/?\1#i','href=$1'.esc_url(home_url('/support/')).'$1',$html);
  // WordPress search query should populate the cloned search input.
  if (isset($_GET['s'])) $html=str_replace('name="s"', 'name="s" value="'.esc_attr(wp_unslash($_GET['s'])).'"', $html);
  // Strip any Coming soon mode notice banner
  $html = preg_replace('#<div\b[^>]*class=["\'][^"\']*(?:woocommerce-site-visibility|store-coming-soon|site-visibility)[^"\']*["\'][^>]*>.*?</div>#is', '', $html);
  // Inject floating contact widget & office modal
  if (function_exists('mm_get_floating_contact_markup')) {
    $floating_widget = mm_get_floating_contact_markup();
    $html = preg_replace('#</body>#i', $floating_widget . '</body>', $html, 1);
  }
  $html=mm_enhance_snapshot_seo($html,$file);
  nocache_headers();
  echo $html;
  return true;
}

function mm_support_content(){
  $support_articles=[
    'cac-kieu-gong-memomind-one'=>[
      'MemoMind One có bao nhiêu kiểu dáng khác nhau?',
      '<p>Phiên bản Standard có 3 lựa chọn gọng: <strong>Nomad, Gotham và Archive</strong>. Các mẫu này hỗ trợ tròng kính có độ.</p>'
    ],
    'gong-phu-hop-nguoi-dau-lon'=>[
      'Loại gọng nào phù hợp hơn với người có vòng đầu lớn?',
      '<p>Dựa trên thông số kích thước, MemoMind thường khuyên dùng gọng <strong>Nomad</strong> để có độ ôm thoải mái hơn. Bạn vẫn nên đối chiếu số đo đầu và sở thích đeo với thông số chi tiết của từng mẫu trước khi chọn.</p>'
    ],
    'chon-mau-gong'=>[
      'Tôi có thể chọn màu gọng không?',
      '<p>Gọng cơ bản không có tùy chọn màu. Với phiên bản Custom, mẫu <strong>Archive có 6 màu</strong>, còn Nomad hiện có một màu. Phiên bản Custom hiện chưa hỗ trợ tròng kính có độ.</p>'
    ],
    'theo-doi-don-hang'=>[
      'Làm thế nào để theo dõi trạng thái đơn hàng?',
      '<p>Sau khi đơn được gửi, MemoMind sẽ gửi mã vận đơn qua email. Bạn có thể dùng mã đơn hàng hoặc mã vận đơn để kiểm tra trạng thái; nếu cần thêm trợ giúp, hãy liên hệ đội ngũ hỗ trợ MemoMind.</p>'
    ],
    'chinh-sach-doi-tra'=>[
      'Chính sách đổi trả của MemoMind như thế nào?',
      '<p>Tròng kính có độ được sản xuất riêng theo thông tin khách hàng cung cấp, vì vậy hãy kiểm tra kỹ đơn kính trước khi xác nhận.</p><h3>Trường hợp không phải lỗi chất lượng</h3><p>Sản phẩm cá nhân hóa không hỗ trợ trả hoặc đổi do thay đổi sở thích, cảm giác trong thời gian làm quen, cảm nhận thị giác cá nhân hoặc đổi ý sau khi nhận hàng.</p><h3>Lỗi chất lượng</h3><p>Nếu tròng kính có lỗi sản xuất hoặc không đúng với đơn kính đã xác nhận, MemoMind sẽ thay thế hoặc đưa ra giải pháp hậu mãi phù hợp mà không thu thêm phí.</p>'
    ],
    'thanh-toan-khong-thanh-cong'=>[
      'Tại sao thanh toán của tôi không thực hiện được?',
      '<p>Hãy kiểm tra lại thông tin thanh toán, số dư hoặc hạn mức, địa chỉ thanh toán và xác thực từ ngân hàng. Nếu giao dịch vẫn thất bại, thử phương thức khác hoặc liên hệ ngân hàng phát hành thẻ và đội ngũ hỗ trợ MemoMind.</p>'
    ],
    'chinh-sach-bao-hanh'=>[
      'Chính sách bảo hành MemoMind One',
      '<p>MemoMind tiếp nhận yêu cầu bảo hành đối với lỗi kỹ thuật hoặc lỗi sản xuất trong thời hạn được xác nhận trên đơn hàng. Khách hàng cần cung cấp mã đơn, thông tin liên hệ và mô tả tình trạng sản phẩm.</p><h3>Quy trình hỗ trợ</h3><p>Liên hệ hotline 1900.63.8400 hoặc email contact@memomind.vn để được kiểm tra điều kiện áp dụng và hướng dẫn gửi sản phẩm. Thời hạn và phạm vi bảo hành cụ thể được xác nhận khi tư vấn đơn hàng.</p>'
    ],
    'van-chuyen-giao-hang'=>[
      'Chính sách vận chuyển và giao hàng',
      '<p>MemoMind hỗ trợ giao hàng tại Việt Nam. Thời gian và phí giao hàng phụ thuộc địa chỉ nhận, tình trạng sản phẩm và phương thức vận chuyển được xác nhận khi chốt đơn.</p><h3>Theo dõi đơn hàng</h3><p>Sau khi đơn được bàn giao cho đơn vị vận chuyển, khách hàng sẽ nhận thông tin theo dõi qua thông tin liên hệ đã cung cấp.</p>'
    ],
    'phuong-thuc-thanh-toan'=>[
      'Phương thức thanh toán',
      '<p>Phương thức, số tiền và hướng dẫn thanh toán được nhân viên MemoMind xác nhận trực tiếp với khách hàng trước khi xử lý đơn. Không chuyển tiền vào tài khoản không được xác nhận qua kênh liên hệ chính thức.</p><h3>Cần hỗ trợ?</h3><p>Liên hệ hotline 1900.63.8400 hoặc email contact@memomind.vn để kiểm tra thông tin thanh toán của đơn hàng.</p>'
    ],
  ];
  $support_slug=trim(substr(mm_current_route(),strlen('/support/')),'/');
  if($support_slug!=='' && isset($support_articles[$support_slug])){
    [$title,$body]=$support_articles[$support_slug];
    $home=esc_url(home_url('/support/'));
    return '<style>.mm-support-article{font-family:Manrope,Arial,sans-serif;max-width:900px;margin:0 auto;padding:80px 24px 120px;color:#111}.mm-support-article__back{display:inline-block;margin-bottom:42px;color:#555;text-decoration:none}.mm-support-article h1{font-size:clamp(34px,5vw,56px);line-height:1.15;margin:0 0 36px}.mm-support-article h3{font-size:22px;margin:32px 0 10px}.mm-support-article p{font-size:18px;line-height:1.75;color:#444}.mm-support-article__contact{margin-top:54px;padding-top:28px;border-top:1px solid #ddd}</style><article class="mm-support-article"><a class="mm-support-article__back" href="'.$home.'">← Trung tâm hỗ trợ</a><h1>'.esc_html($title).'</h1>'.$body.'<p class="mm-support-article__contact">Bạn vẫn cần hỗ trợ? Email: <a href="mailto:contact@memomind.vn">contact@memomind.vn</a></p></article>';
  }
  $markup=<<<'HTML'
<style>
body:has(.mm-support)>.shopify-section-group-header-group,body:has(.mm-support) #shopify-section-sections--18668046647409__announcement-bar,body:has(.mm-support) #shopify-section-sections--18668046647409__header{display:none!important}.mm-support{font-family:Manrope,Arial,sans-serif;color:#111;background:#fff}.mm-support *{box-sizing:border-box}.mm-support__nav{height:88px;display:grid;grid-template-columns:1fr auto 1fr;align-items:center;padding:0 max(40px,9vw);background:#fff}.mm-support__logo{font-size:35px;font-weight:600;letter-spacing:-1.5px;color:#111;text-decoration:none}.mm-support__menu{display:flex;gap:42px}.mm-support__menu a,.mm-support__signin{font-size:16px;color:#111;text-decoration:none}.mm-support__signin{justify-self:end;color:#0868ce}.mm-support__hero{position:relative;min-height:760px;padding:290px 24px 80px;text-align:center;color:#fff;background:#222 url('__MM_SUPPORT_ASSET__/cdn/shop/files/banner_0425.webp') center/cover no-repeat}.mm-support__hero:before{content:'';position:absolute;inset:0;background:rgba(0,0,0,.2)}.mm-support__hero>*{position:relative}.mm-support__hero h1{font-size:clamp(34px,3vw,46px);line-height:1.45;margin:0 0 40px;font-weight:700}.mm-support__eyebrow,.mm-support__lead{display:none}.mm-support__search{display:flex;max-width:890px;height:74px;margin:auto;background:#fff;border:1px solid #ddd;border-radius:999px;overflow:hidden;text-align:left}.mm-support__search:before{content:'⌕';color:#aaa;font-size:38px;line-height:65px;padding-left:34px;transform:rotate(-20deg)}.mm-support__search input{flex:1;min-width:0;border:0;padding:0 22px;font:inherit;font-size:19px;outline:0}.mm-support__search button{display:none}.mm-support__body{max-width:1200px;margin:auto;padding:86px 24px}.mm-support__body>h2{text-align:center;font-size:clamp(30px,4vw,44px);margin:0 0 44px}.mm-support__grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}.mm-support__card{display:block;padding:30px;min-height:190px;border:1px solid #e2e2e2;border-radius:16px;color:inherit;text-decoration:none;transition:.2s}.mm-support__card:hover{transform:translateY(-3px);border-color:#999;box-shadow:0 14px 32px rgba(0,0,0,.07)}.mm-support__icon{display:grid;place-items:center;width:44px;height:44px;border-radius:50%;background:#f1e6d5;font-size:21px}.mm-support__card h3{font-size:21px;margin:22px 0 9px}.mm-support__card p{margin:0;color:#666;line-height:1.5}.mm-support__popular{margin-top:76px;padding-top:60px;border-top:1px solid #e6e6e6}.mm-support__popular h2{font-size:32px;margin:0 0 26px}.mm-support__links{display:grid;grid-template-columns:1fr 1fr;gap:12px 40px}.mm-support__links a{padding:15px 0;border-bottom:1px solid #e8e8e8;color:#111;text-decoration:none}.mm-support__links a:hover{text-decoration:underline}.mm-support__contact{margin-top:72px;padding:42px;border-radius:18px;background:#111;color:#fff;display:flex;align-items:center;justify-content:space-between;gap:28px}.mm-support__contact h2{margin:0 0 8px;font-size:30px}.mm-support__contact p{margin:0;color:#ccc}.mm-support__contact a{padding:14px 24px;border-radius:999px;background:#fff;color:#111;text-decoration:none;white-space:nowrap}@media(max-width:800px){.mm-support__nav{height:70px;padding:0 18px;grid-template-columns:1fr auto}.mm-support__logo{font-size:24px}.mm-support__menu{display:none}.mm-support__hero{min-height:620px;padding:190px 16px 60px;background-position:58% center}.mm-support__search{height:62px}.mm-support__body{padding:58px 16px}.mm-support__grid,.mm-support__links{grid-template-columns:1fr}.mm-support__contact{align-items:flex-start;flex-direction:column}}
.mm-support__search{display:none!important}.mm-support__search input{color:#555!important;background:#fff}.mm-support__search input::placeholder{color:#777;opacity:1}
</style>
<section class="mm-support">
 <nav class="mm-support__nav"><a class="mm-support__logo" href="__MM_HOME__/">MEMOMIND</a><div class="mm-support__menu"><a href="__MM_HOME__/">Trang chủ</a><a href="__MM_HOME__/pages/memomind-one/">MemoMind One</a><a href="__MM_HOME__/pages/about-us/">Về chúng tôi</a></div></nav>
 <div class="mm-support__hero"><div class="mm-support__eyebrow">MemoMind</div><h1>Chào mừng đến với<br>Trung tâm hỗ trợ MemoMind</h1><p class="mm-support__lead">Tìm câu trả lời về sản phẩm MemoMind, đơn hàng, giao hàng, đổi trả và thanh toán.</p></div>
 <div class="mm-support__body"><h2>Tìm hỗ trợ bạn cần</h2><div class="mm-support__grid">
  <a class="mm-support__card" href="__MM_HOME__/support/cac-kieu-gong-memomind-one/"><span class="mm-support__icon">◎</span><h3>Hỗ trợ sản phẩm</h3><p>Nhận hỗ trợ về tính năng sản phẩm, thiết lập và các vấn đề kỹ thuật.</p></a>
  <a class="mm-support__card" href="__MM_HOME__/support/theo-doi-don-hang/"><span class="mm-support__icon">◇</span><h3>Vận chuyển &amp; giao hàng</h3><p>Theo dõi đơn hàng và cập nhật tình trạng vận chuyển, giao hàng.</p></a>
  <a class="mm-support__card" href="__MM_HOME__/support/theo-doi-don-hang/"><span class="mm-support__icon">○</span><h3>Tài khoản &amp; đơn hàng</h3><p>Cài đặt tài khoản và quản lý đơn hàng.</p></a>
  <a class="mm-support__card" href="__MM_HOME__/support/chinh-sach-doi-tra/"><span class="mm-support__icon">↻</span><h3>Đổi trả &amp; hoàn hàng</h3><p>Tìm hiểu về chính sách trả hàng, đổi hàng và hoàn tiền.</p></a>
  <a class="mm-support__card" href="__MM_HOME__/support/thanh-toan-khong-thanh-cong/"><span class="mm-support__icon">$</span><h3>Thanh toán &amp; hóa đơn</h3><p>Tìm thông tin về thanh toán, lập hóa đơn và chứng từ.</p></a>
 </div><section class="mm-support__popular"><h2>Câu hỏi phổ biến</h2><div class="mm-support__links">
  <a href="__MM_HOME__/support/cac-kieu-gong-memomind-one/">MemoMind One có bao nhiêu kiểu dáng khác nhau?</a><a href="__MM_HOME__/support/theo-doi-don-hang/">Làm thế nào để theo dõi trạng thái đơn hàng?</a><a href="__MM_HOME__/support/gong-phu-hop-nguoi-dau-lon/">Loại gọng nào phù hợp hơn với người có vòng đầu lớn?</a><a href="__MM_HOME__/support/chinh-sach-doi-tra/">Chính sách đổi trả của MemoMind như thế nào?</a><a href="__MM_HOME__/support/chon-mau-gong/">Tôi có thể chọn màu gọng không?</a><a href="__MM_HOME__/support/thanh-toan-khong-thanh-cong/">Tại sao thanh toán của tôi không thực hiện được?</a>
 </div></section><div class="mm-support__contact"><div><h2>Vẫn cần hỗ trợ?</h2><p>Hỗ trợ khách hàng &amp; hỗ trợ kỹ thuật · Thứ Hai–Chủ Nhật, 9:00–18:00 (giờ Việt Nam)</p></div><a href="mailto:contact@memomind.vn">Liên hệ hỗ trợ</a></div></div>
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
  // Search is disabled: normalize legacy WordPress ?s= URLs to the homepage.
  if (array_key_exists('s', $_GET)) {
    wp_safe_redirect(home_url('/'), 301);
    exit;
  }
  $route=mm_current_route();
  if($route==='/cart/') {
    wp_safe_redirect(home_url('/collections/all/'),301);
    exit;
  }
  // Checkout is handled directly through the AJAX consultation popup.
  if($route==='/checkout/') {
    wp_safe_redirect(home_url('/collections/all/'),302);
    exit;
  }
  if($route==='/data-sharing-opt-out/' || $route==='/pages/data-sharing-opt-out/'){
    wp_safe_redirect(home_url('/policies/privacy-policy/'),301);
    exit;
  }
  // Redirect legacy Shopify page URLs to clean WordPress-style root slugs.
  if (str_starts_with($route,'/pages/')) {
    $clean_route='/'.trim(substr($route,strlen('/pages/')),'/').'/';
    wp_safe_redirect(home_url($clean_route),301);
    exit;
  }
  // Preserve old inbound links while keeping Vietnamese as the only locale.
  if (str_starts_with($route,'/fr/')) {
    $vietnamese_route=substr($route,3);
    wp_safe_redirect(home_url($vietnamese_route ?: '/'),301);
    exit;
  }
  $routes=mm_routes();
  if(function_exists('mm_product_render_dynamic_route') && mm_product_render_dynamic_route($route)) exit;
  if(function_exists('mm_blog_render_dynamic_route') && mm_blog_render_dynamic_route($route)) exit;
  if(str_starts_with($route,'/support/')) { mm_render_snapshot('@support'); exit; }
  $legacy_page_route='/pages/'.trim($route,'/').'/';
  if($route!=='/' && isset($routes[$legacy_page_route])) { mm_render_snapshot($routes[$legacy_page_route]); exit; }
  // Keep WooCommerce account and order confirmation endpoints functional.
  if (preg_match('#^/(checkout|my-account)(/|$)#',$route) && class_exists('WooCommerce')) return;
  if (isset($routes[$route]) && mm_render_snapshot($routes[$route])) exit;
  // tolerate missing trailing or captured .html links
  $alt=rtrim($route,'/').'.html';
  foreach($routes as $r=>$f){ if(rtrim($r,'/').'.html'===$alt && mm_render_snapshot($f)) exit; }
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
add_action('wp_loaded', function(){
  $path=parse_url($_SERVER['REQUEST_URI'] ?? '',PHP_URL_PATH);
  if(!in_array($path,['/cart/add.js','/cart.js','/cart/update.js','/cart/change.js','/cart/clear.js'],true)) return;
  if(!class_exists('WooCommerce')) { status_header(501); wp_send_json(['status'=>501,'description'=>'WooCommerce is required for cart actions']); }
  if(function_exists('wc_load_cart')) wc_load_cart();
  if($path==='/cart/add.js'){
    $id=absint($_POST['id'] ?? 0); $qty=max(1,absint($_POST['quantity'] ?? 1));
    $map=get_option('mm_shopify_variant_map',[]); $mapped_id=isset($map[$id])?absint($map[$id]):0;
    if(!$mapped_id){ status_header(422); wp_send_json(['status'=>422,'description'=>'Variant is not mapped to a WooCommerce product']); }
    $mapped=wc_get_product($mapped_id); $pid=$mapped && $mapped->is_type('variation') ? $mapped->get_parent_id() : $mapped_id;
    $variation_id=$mapped && $mapped->is_type('variation') ? $mapped_id : 0;
    $attributes=$variation_id ? $mapped->get_variation_attributes() : [];
    $key=WC()->cart->add_to_cart($pid,$qty,$variation_id,$attributes); if(!$key){status_header(422);wp_send_json(['status'=>422,'description'=>'Unable to add item']);}
    wp_send_json(['id'=>$id,'quantity'=>$qty,'title'=>$mapped?$mapped->get_name():'MemoMind One','key'=>$key]);
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
}, 20);

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
  // Import complete snapshot data into native, editable WordPress posts.
  if(function_exists('mm_import_blog_posts')) $created+=mm_import_blog_posts($routes);
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
