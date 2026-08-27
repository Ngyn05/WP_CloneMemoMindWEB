<?php
if (!defined('ABSPATH')) exit;

function mm_blog_route_parts($route){
  return preg_match('#^/blogs/([^/]+)/([^/]+)/$#',(string)$route,$m) ? [$m[1],$m[2]] : null;
}

function mm_blog_inner_html($html,$view){
  if(!$view instanceof DOMElement) return '';
  $out=''; foreach($view->childNodes as $node)$out.=$html->saveHTML($node);
  return $out;
}

function mm_blog_snapshot_data($route,$view){
  $path=get_template_directory().'/views/'.$view;
  if(!is_readable($path)) return null;
  $raw=file_get_contents($path); if(!$raw) return null;
  $data=['route'=>$route,'view'=>$view,'title'=>'','content'=>'','excerpt'=>'','cover'=>'','date'=>''];
  if(preg_match('#<h1\b[^>]*class=["\'][^"\']*memomind-article__entry-title[^"\']*["\'][^>]*>(.*?)</h1>#is',$raw,$m))
    $data['title']=trim(wp_strip_all_tags(html_entity_decode($m[1],ENT_QUOTES|ENT_HTML5,'UTF-8')));
  if(preg_match('#<div\b[^>]*class=["\'][^"\']*memomind-article__content[^"\']*["\'][^>]*>(.*?)</div>\s*<nav\b[^>]*class=["\'][^"\']*memomind-article__pager#is',$raw,$m)){
    $content=preg_replace('#<(style|script|template)\b[^>]*>.*?</\1>#is','',$m[1]);
    $data['content']=str_replace('__MM_ASSET__',trailingslashit(get_template_directory_uri()).'assets',trim($content));
  }
  if(preg_match('#<meta\b[^>]*name=["\']description["\'][^>]*content=["\'](.*?)["\']#is',$raw,$m) || preg_match('#<meta\b[^>]*content=["\'](.*?)["\'][^>]*name=["\']description["\']#is',$raw,$m))
    $data['excerpt']=html_entity_decode($m[1],ENT_QUOTES|ENT_HTML5,'UTF-8');
  if(preg_match('#<img\b[^>]*class=["\'][^"\']*memomind-article__cover[^"\']*["\'][^>]*src=["\']([^"\']+)["\']#is',$raw,$m) || preg_match('#<img\b[^>]*src=["\']([^"\']+)["\'][^>]*class=["\'][^"\']*memomind-article__cover#is',$raw,$m))
    $data['cover']=$m[1];
  if(preg_match('/"datePublished"\s*:\s*"([^"]+)"/i',$raw,$m)) $data['date']=$m[1];
  return $data['title'] && $data['content'] ? $data : null;
}

function mm_blog_category_name($slug){
  return ['buyers-guide'=>'Hướng dẫn mua hàng','in-the-moment'=>'Khoảnh khắc thực tế','news'=>'Tin tức','tech-hub'=>'Công nghệ'][$slug] ?? ucwords(str_replace('-',' ',$slug));
}

function mm_blog_attach_cover($post_id,$cover){
  if(!$cover || get_post_thumbnail_id($post_id)) return;
  $relative=preg_replace('#^__MM_ASSET__/#','',$cover);
  $relative=preg_replace('~[?#].*$~','',$relative);
  $source=get_template_directory().'/assets/'.ltrim($relative,'/');
  if(!is_readable($source)) return;
  require_once ABSPATH.'wp-admin/includes/image.php';
  $upload=wp_upload_bits(basename($source),null,file_get_contents($source));
  if(!empty($upload['error'])) return;
  $type=wp_check_filetype($upload['file']);
  $attachment=wp_insert_attachment(['post_mime_type'=>$type['type'],'post_title'=>sanitize_text_field(pathinfo($source,PATHINFO_FILENAME)),'post_status'=>'inherit'],$upload['file'],$post_id);
  if(is_wp_error($attachment)) return;
  wp_update_attachment_metadata($attachment,wp_generate_attachment_metadata($attachment,$upload['file']));
  set_post_thumbnail($post_id,$attachment);
}

function mm_import_blog_posts($routes){
  $changed=0; $map=get_option('mm_blog_post_map',[]); if(!is_array($map))$map=[];
  foreach($routes as $route=>$view){
    $parts=mm_blog_route_parts($route); if(!$parts) continue;
    $data=mm_blog_snapshot_data($route,$view); if(!$data) continue;
    [$group,$slug]=$parts;
    $post=null;
    if(!empty($map[$view])) $post=get_post((int)$map[$view]);
    if(!$post) $post=get_page_by_path($slug,OBJECT,'post');
    $payload=['post_type'=>'post','post_status'=>'publish','post_name'=>$slug,'post_title'=>$data['title'],'post_excerpt'=>$data['excerpt']];
    if($data['date']){ $time=strtotime($data['date']); if($time){$payload['post_date']=wp_date('Y-m-d H:i:s',$time);$payload['post_date_gmt']=get_gmt_from_date($payload['post_date']);} }
    if($post){
      $payload['ID']=$post->ID;
      $last_hash=get_post_meta($post->ID,'_mm_imported_content_hash',true);
      if(trim($post->post_content)==='' || $last_hash===md5($post->post_content)) $payload['post_content']=$data['content'];
      $post_id=wp_update_post(wp_slash($payload),true);
    }else{
      $payload['post_content']=$data['content'];
      $post_id=wp_insert_post(wp_slash($payload),true); $changed++;
    }
    if(is_wp_error($post_id) || !$post_id) continue;
    $term=term_exists(mm_blog_category_name($group),'category');
    if(!$term)$term=wp_insert_term(mm_blog_category_name($group),'category',['slug'=>$group]);
    if(!is_wp_error($term)) wp_set_post_categories($post_id,[(int)(is_array($term)?$term['term_id']:$term)],false);
    update_post_meta($post_id,'_mm_blog_route',$route);
    update_post_meta($post_id,'_mm_snapshot_view',$view);
    update_post_meta($post_id,'_mm_imported_content_hash',md5(get_post_field('post_content',$post_id)));
    update_post_meta($post_id,'_mm_reading_minutes',max(1,(int)ceil(str_word_count(wp_strip_all_tags($data['content']))/220)));
    $map[$view]=$post_id; mm_blog_attach_cover($post_id,$data['cover']);
  }
  update_option('mm_blog_post_map',$map,false);
  return $changed;
}

function mm_blog_apply_post_to_snapshot($html,$view){
  if($view==='pages__memomind-blog.html') return mm_blog_apply_index_posts($html);
  if(!str_starts_with($view,'blogs__') || !preg_match('#__[^_]+\.html$#',$view)) return $html;
  $post_id=0;
  $route=function_exists('mm_current_route') ? mm_current_route() : '';
  if($route){
    $matches=get_posts(['post_type'=>'post','post_status'=>'publish','posts_per_page'=>1,'fields'=>'ids','meta_key'=>'_mm_blog_route','meta_value'=>$route]);
    $post_id=(int)($matches[0]??0);
  }
  $map=get_option('mm_blog_post_map',[]);
  if(!$post_id) $post_id=(int)($map[$view]??0);
  if(!$post_id){
    $slug=preg_replace('#^.*__#','',substr($view,0,-5));
    $post=get_page_by_path($slug,OBJECT,'post'); $post_id=$post?$post->ID:0;
  }
  if(!$post_id) return $html;
  $post=get_post($post_id);
  if(!$post || $post->post_status!=='publish') return false;
  $raw_title=trim(get_the_title($post));
  $title=esc_html($raw_title!=='' ? $raw_title : 'N/A');
  $raw_content=trim((string)$post->post_content);
  $content=$raw_content!=='' ? apply_filters('the_content',$raw_content) : '<p>N/A</p>';
  $headings=[];
  $content=preg_replace_callback('#<h([23])\b([^>]*)>(.*?)</h\1>#is',function($m)use(&$headings){
    $label=trim(wp_strip_all_tags($m[3]));
    $id='mm-section-'.(count($headings)+1).'-'.sanitize_title($label ? $label : 'na');
    $attrs=preg_replace("/\\s+id=(\\\"|').*?\\1/is",'',$m[2]);
    $headings[]=['level'=>(int)$m[1],'id'=>$id,'label'=>$label ? $label : 'N/A'];
    return '<h'.$m[1].$attrs.' id="'.esc_attr($id).'">'.$m[3].'</h'.$m[1].'>';
  },$content);
  $html=preg_replace('#(<h1\b[^>]*class=["\'][^"\']*memomind-article__entry-title[^"\']*["\'][^>]*>).*?(</h1>)#is','$1'.$title.'$2',$html,1);
  $html=preg_replace('#(<div\b[^>]*class=["\'][^"\']*memomind-article__content[^"\']*["\'][^>]*>).*?(</div>\s*<nav\b[^>]*class=["\'][^"\']*memomind-article__pager)#is','$1'.$content.'$2',$html,1);
  $toc='';
  foreach($headings as $heading) $toc.='<li class="memomind-article__toc-item"><a class="memomind-article__toc-link" href="#'.esc_attr($heading['id']).'" title="'.esc_attr($heading['label']).'"><span class="memomind-article__toc-title">'.esc_html($heading['label']).'</span></a></li>';
  if($toc==='') $toc='<li class="memomind-article__toc-item"><span class="memomind-article__toc-link"><span class="memomind-article__toc-title">N/A</span></span></li>';
  $html=preg_replace('#(<aside\b[^>]*class=["\'][^"\']*memomind-article__toc[^"\']*["\'][^>]*>\s*<ol\b[^>]*>).*?(</ol>)#is','$1'.$toc.'$2',$html,1);
  $terms=get_the_category($post_id);
  $category=$terms ? $terms[0]->name : 'N/A';
  $category_url=$terms ? '/blogs/'.$terms[0]->slug.'/' : '#';
  $date=get_the_date('d/m/Y',$post_id) ?: 'N/A';
  $minutes=$raw_content!=='' ? (int)get_post_meta($post_id,'_mm_reading_minutes',true) : 0;
  $meta='<div aria-label="Thông tin bài viết" class="memomind-article__keywords"><a class="memomind-article__keyword-chip" href="'.esc_url($category_url).'">'.esc_html($category).'</a><span class="memomind-article__date-chip">'.esc_html($date).'</span><span class="memomind-article__date-chip">'.($minutes>0 ? esc_html($minutes.' phút đọc') : 'N/A').'</span></div>';
  $html=preg_replace('#<div\b[^>]*class=["\'][^"\']*memomind-article__keywords[^"\']*["\'][^>]*>.*?</div>#is',$meta,$html,1);
  $share_url=home_url($route ?: '/');
  $share_title=$raw_title!=='' ? $raw_title : 'N/A';
  $html=preg_replace_callback('#<a\b[^>]*class=["\'][^"\']*memomind-article__share-button[^"\']*["\'][^>]*>#is',static function($m)use($share_url,$share_title){
    $tag=$m[0];
    $label=wp_strip_all_tags(html_entity_decode($tag,ENT_QUOTES|ENT_HTML5,'UTF-8'));
    if(stripos($tag,'Facebook')!==false) $href='https://www.facebook.com/sharer/sharer.php?u='.rawurlencode($share_url);
    elseif(stripos($tag,'email')!==false) $href='mailto:?subject='.rawurlencode($share_title).'&body='.rawurlencode($share_url);
    else $href='https://twitter.com/intent/tweet?url='.rawurlencode($share_url).'&text='.rawurlencode($share_title);
    return preg_replace('/\s+href=("|\').*?\1/is',' href="'.esc_url($href).'"',$tag,1);
  },$html);
  $html=preg_replace_callback('#<button\b[^>]*data-memomind-copy-link=("|\').*?\1[^>]*>#is',static function($m)use($share_url){
    return preg_replace('/data-memomind-copy-link=("|\').*?\1/is','data-memomind-copy-link="'.esc_url($share_url).'"',$m[0],1);
  },$html,1);
  $thumb=get_the_post_thumbnail_url($post_id,'full');
  if($thumb){
    $html=preg_replace_callback('#<img\b[^>]*class=["\'][^"\']*memomind-article__cover[^>]*>#is',static function($m)use($thumb,$title){
      $tag=preg_replace('/\s+srcset=(["\']).*?\1/is','',$m[0]);
      $tag=preg_replace('/\s+sizes=(["\']).*?\1/is','',$tag);
      $tag=preg_replace('/src=(["\']).*?\1/is','src="'.esc_url($thumb).'"',$tag,1);
      return preg_replace('/alt=(["\']).*?\1/is','alt="'.esc_attr($title).'"',$tag,1);
    },$html,1);
  }else{
    $html=preg_replace('#<img\b[^>]*class=["\'][^"\']*memomind-article__cover[^>]*>#is','<div class="memomind-article__cover-placeholder">N/A</div>',$html,1);
  }
  return $html;
}

function mm_blog_apply_index_posts($html){
  $posts=get_posts(['post_type'=>'post','post_status'=>'publish','posts_per_page'=>-1,'meta_key'=>'_mm_blog_route','orderby'=>'date','order'=>'DESC']);
  if(!$posts) return $html;
  $cards='';
  foreach($posts as $index=>$post){
    $route=get_post_meta($post->ID,'_mm_blog_route',true);
    if(!$route) continue;
    $terms=get_the_category($post->ID);
    $category=$terms ? $terms[0]->name : 'Tin tức';
    $image=get_the_post_thumbnail_url($post->ID,'large');
    $excerpt=get_the_excerpt($post);
    if(!$excerpt) $excerpt=wp_trim_words(wp_strip_all_tags($post->post_content),28,'…');
    $hidden=$index>=8 ? ' hidden=""' : '';
    $cards.='<a class="memomind-blog-index__post-card" data-memomind-blog-post="" data-published="'.esc_attr(get_post_timestamp($post)).'"'.$hidden.' href="'.esc_url($route).'">';
    if($image) $cards.='<div class="memomind-blog-index__post-media"><img alt="'.esc_attr(get_the_title($post)).'" loading="lazy" src="'.esc_url($image).'" /></div>';
    $cards.='<div class="memomind-blog-index__post-body"><div class="memomind-blog-index__meta"><span class="memomind-blog-index__chip">'.esc_html($category).'</span><span class="memomind-blog-index__date">'.esc_html(get_the_date('d/m/Y',$post)).'</span></div><h3 class="memomind-blog-index__post-title">'.esc_html(get_the_title($post)).'</h3><p class="memomind-blog-index__post-excerpt">'.esc_html($excerpt).'</p></div></a>';
  }
  return preg_replace('#(<div class="memomind-blog-index__post-grid" data-memomind-blog-grid="">).*?(</div>\s*<div class="memomind-blog-index__actions">)#is','$1'.$cards.'$2',$html,1);
}

function mm_blog_default_view($group){
  $views=[
    'news'=>'blogs__news__memomind-one-kickstarter.html',
    'tech-hub'=>'blogs__tech-hub__how-do-ai-glasses-work.html',
    'buyers-guide'=>'blogs__buyers-guide__what-is-memomind-everything-you-should-know.html',
    'in-the-moment'=>'blogs__in-the-moment__memomind-ai-glasses-4-real-life-moments.html',
  ];
  return $views[$group] ?? $views['news'];
}

function mm_blog_bind_post($post_id,$post=null){
  if(wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;
  $post=$post instanceof WP_Post ? $post : get_post($post_id);
  if(!$post || $post->post_type!=='post' || !$post->post_name || $post->post_status==='auto-draft') return;
  $allowed=['buyers-guide','in-the-moment','news','tech-hub'];
  $group='news';
  foreach(wp_get_post_categories($post_id,['fields'=>'slugs']) as $slug){ if(in_array($slug,$allowed,true)){ $group=$slug; break; } }
  update_post_meta($post_id,'_mm_blog_route','/blogs/'.$group.'/'.$post->post_name.'/');
  $view=get_post_meta($post_id,'_mm_snapshot_view',true);
  if(!$view || !is_readable(get_template_directory().'/views/'.$view)) update_post_meta($post_id,'_mm_snapshot_view',mm_blog_default_view($group));
  if(!get_post_meta($post_id,'_mm_reading_minutes',true)) update_post_meta($post_id,'_mm_reading_minutes',max(1,(int)ceil(str_word_count(wp_strip_all_tags($post->post_content))/220)));
}
add_action('save_post_post','mm_blog_bind_post',20,2);
add_action('set_object_terms',function($object_id,$terms,$tt_ids,$taxonomy){
  if($taxonomy==='category' && get_post_type($object_id)==='post') mm_blog_bind_post($object_id);
},20,4);

function mm_blog_render_dynamic_route($route){
  if(!mm_blog_route_parts($route)) return false;
  $ids=get_posts(['post_type'=>'post','post_status'=>'publish','posts_per_page'=>1,'fields'=>'ids','meta_key'=>'_mm_blog_route','meta_value'=>$route]);
  if(!$ids) return false;
  $view=get_post_meta((int)$ids[0],'_mm_snapshot_view',true);
  return $view && function_exists('mm_render_snapshot') ? mm_render_snapshot($view) : false;
}

add_filter('use_block_editor_for_post_type',static fn($use,$type)=>$type==='post'?false:$use,10,2);
add_filter('tiny_mce_before_init',function($settings){
  global $post_type; if($post_type!=='post')return $settings;
  $settings['content_style']='body{max-width:760px;margin:30px auto;font-family:Manrope,Arial,sans-serif;color:#242424;font-size:17px;line-height:1.75}h2{font-size:30px;line-height:1.25;margin:48px 0 18px;padding-top:18px;border-top:1px solid #e6e6e6}h3{font-size:23px;margin-top:34px}img{max-width:100%;height:auto;border-radius:14px}blockquote{padding:18px 24px;border-left:4px solid #111;background:#f5f5f5}table{width:100%;border-collapse:collapse}td,th{padding:12px;border:1px solid #ddd}';
  return $settings;
});

add_action('add_meta_boxes',function(){
  add_meta_box('mm_blog_data','Thông tin giao diện bài viết','mm_blog_data_box','post','side','default');
});
function mm_blog_data_box($post){
  $route=get_post_meta($post->ID,'_mm_blog_route',true); $view=get_post_meta($post->ID,'_mm_snapshot_view',true); $minutes=(int)get_post_meta($post->ID,'_mm_reading_minutes',true);
  wp_nonce_field('mm_blog_meta','mm_blog_nonce');
  echo '<p><strong>URL ngoài website</strong><br><code style="word-break:break-all">'.esc_html($route?:'Chưa gắn route').'</code></p><p><label for="mm_reading_minutes"><strong>Thời gian đọc (phút)</strong></label><input class="widefat" id="mm_reading_minutes" name="mm_reading_minutes" type="number" min="1" value="'.esc_attr($minutes?:1).'"></p><p><strong>Snapshot giao diện</strong><br><small>'.esc_html($view?:'Chưa có').'</small></p>';
}
add_action('save_post_post',function($post_id){
  if(!isset($_POST['mm_blog_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mm_blog_nonce'])),'mm_blog_meta') || (defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE) || !current_user_can('edit_post',$post_id))return;
  if(isset($_POST['mm_reading_minutes']))update_post_meta($post_id,'_mm_reading_minutes',max(1,absint($_POST['mm_reading_minutes'])));
});

add_filter('manage_post_posts_columns',function($cols){$cols['mm_route']='URL giao diện';return $cols;});
add_action('manage_post_posts_custom_column',function($col,$id){if($col==='mm_route'){ $route=get_post_meta($id,'_mm_blog_route',true); echo $route?'<a href="'.esc_url(home_url($route)).'" target="_blank">'.esc_html($route).'</a>':'—';}},10,2);
