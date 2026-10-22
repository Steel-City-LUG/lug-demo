<?php
if ( ! defined( 'ABSPATH' ) ) exit;
final class LUG_Demo_Training {
 public function hooks() { add_shortcode( 'lug_demo_training', array( $this, 'render' ) ); add_action( 'template_redirect', array( $this, 'redirect_old' ) ); }
 private function data() { $file=LUG_DEMO_DIR.'fixtures/training-videos.php'; return wp_parse_args((array)(file_exists($file)?require $file:array()),array('channel'=>'','tools'=>array())); }
 public function redirect_old() { if ( is_page('layout-designer-training') ) { $args=array('tool'=>'layout-designer'); if(isset($_GET['voice'])) $args['voice']=sanitize_text_field(wp_unslash($_GET['voice'])); wp_safe_redirect(add_query_arg($args,home_url('/training/')),301); exit; } }
 public function render() {
  $data=$this->data(); $tools=(array)$data['tools']; if(!$tools) return '';
  $tool_id=isset($_GET['tool'])?sanitize_key(wp_unslash($_GET['tool'])):''; if(!isset($tools[$tool_id])) foreach($tools as $id=>$tool){if(!empty($tool['episodes'])){$tool_id=$id;break;}}
  $tool=(array)$tools[$tool_id]; $voices=(array)($tool['playlists']??array()); $voice=isset($_GET['voice'])?sanitize_text_field(wp_unslash($_GET['voice'])):''; if(!isset($voices[$voice])) $voice=(string)array_key_first($voices); $episodes=(array)($tool['episodes']??array());
  $out='<div class="lug-demo-training"><nav aria-label="Training tools">'; foreach($tools as $id=>$candidate){$out.='<a class="lug-demo-training-tool" href="'.esc_url(add_query_arg('tool',$id,home_url('/training/'))).'"'.($id===$tool_id?' aria-current="page"':'').'>'.esc_html($candidate['name']).'</a> ';} $out.='</nav>';
  if(!$episodes){return $out.'<h2>'.esc_html($tool['name']).'</h2><p>'.esc_html__('Coming soon.','lug-demo').'</p></div>';}
  if(count($voices)>1){$out.='<nav aria-label="Narrator voice">'; foreach($voices as $name=>$playlist){$out.='<a href="'.esc_url(add_query_arg(array('tool'=>$tool_id,'voice'=>$name),home_url('/training/'))).'"'.($name===$voice?' aria-current="page"':'').'>'.esc_html($name).'</a> ';} $out.='</nav>';}
  $playlist='https://www.youtube.com/playlist?list='.rawurlencode($voices[$voice]); $first=$episodes[0]; if(!empty($first['videos'][$voice])) $out.='<section><h2>Start here</h2><iframe src="'.esc_url('https://www.youtube-nocookie.com/embed/'.rawurlencode($first['videos'][$voice]).'?list='.rawurlencode($voices[$voice])).'" title="'.esc_attr($first['title']).'" loading="lazy"></iframe><p>'.esc_html($first['summary']).'</p></section>';
  $items=array(); $out.='<section><h2>All episodes</h2><ol>'; foreach($episodes as $episode){$id=$episode['videos'][$voice]??''; $url=$id?'https://www.youtube.com/watch?v='.rawurlencode($id).'&list='.rawurlencode($voices[$voice]):$playlist; $out.='<li value="'.absint($episode['number']).'"><h3>'.esc_html($episode['title']).'</h3><p>'.esc_html($episode['summary']).'</p><a href="'.esc_url($url).'">'.esc_html($id?'Watch on YouTube':'Open the playlist').'</a></li>'; if($id)$items[]=array('@type'=>'ListItem','position'=>absint($episode['number']),'name'=>$episode['title'],'url'=>'https://www.youtube.com/watch?v='.$id); } $out.='</ol></section><p><a href="'.esc_url($playlist).'">'.esc_html__('Full playlist','lug-demo').'</a></p>';
  return $out.'<script type="application/ld+json">'.wp_json_encode(array('@context'=>'https://schema.org','@type'=>'ItemList','name'=>$tool['name'].' training videos','itemListElement'=>$items)).'</script></div>';
 }
}
