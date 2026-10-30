<?php
/** Read-only Newsletter product showcase, independent of demo-data resets. */
if (!defined('ABSPATH')) exit;

final class LUG_Demo_Newsletter {
    public function hooks() {
        add_shortcode('lug_demo_newsletter_showcase', [$this,'render']);
        add_action('init', [$this,'ensure_page'], 25);
    }

    public function ensure_page() {
        if ((!LUG_Demo_Mail_Safety::applies() && !current_user_can('manage_options')) || get_page_by_path('newsletter', OBJECT, 'page')) return;
        $id=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_name'=>'newsletter','post_title'=>'LUG Newsletter','post_content'=>'[lug_demo_newsletter_showcase]']);
        if ($id && !is_wp_error($id)) update_post_meta($id,'_lug_demo_newsletter_showcase',1);
    }

    public function render() {
        $data=(array)require LUG_DEMO_DIR.'fixtures/newsletter-showcase.php';
        $data['screenshots']=(array)json_decode(file_get_contents(LUG_DEMO_DIR.'fixtures/newsletter-screenshots.json'),true);
        $html='<article class="lug-demo-newsletter"><header><p class="lug-demo-newsletter-kicker">COMMUNICATION FOR YOUR LUG</p><h2>A newsletter workspace your LUG owns</h2><p>'.esc_html($data['short_description']).'</p></header><p>'.esc_html($data['long_description']).'</p><h2>What you can do</h2><ul>';
        foreach($data['features'] as $feature)$html.='<li>'.esc_html($feature).'</li>';
        $html.='</ul><p><strong>Screenshots and training only.</strong> Newsletter is disabled on this demo site. This site does not send email.</p><h2>Inside the workspace</h2><div class="lug-demo-newsletter-shots">';
        foreach($data['screenshots'] as $shot){
            $url=LUG_DEMO_URL.'assets/training/newsletter/'.$shot['file'];
            $html.='<figure><a href="'.esc_url($url).'"><img src="'.esc_url($url).'" alt="'.esc_attr($shot['alt']).'" loading="lazy" width="1920" height="1080"></a><figcaption>'.esc_html($shot['caption']).'</figcaption></figure>';
        }
        $html.='</div><h2>Learn the workflow</h2><p>Step-by-step training covers setup, composition, photos, subscribers, delivery and reports.</p>';
        $training=(array)require LUG_DEMO_DIR.'fixtures/training-videos.php';
        $tool=$training['tools']['lug-newsletter']??[];$playlist=$tool['playlists']['Andrew']??'';
        $published=array_filter($tool['episodes']??[],static function($episode){return !empty($episode['videos']['Andrew']);});
        if($playlist && $published){
            $html.='<p><a class="button" href="'.esc_url(home_url('/training/?tool=lug-newsletter')).'">Watch LUG Newsletter How-To</a> · <a href="'.esc_url('https://www.youtube.com/playlist?list='.$playlist).'">Open the YouTube playlist</a></p>';
        }else{
            $html.='<p>The narrated training series is being prepared. Published episodes will appear here after review.</p>';
        }
        $html.='<h2>Installation and availability</h2><p>Install through your connected LUG Platform site when Newsletter is available to your LUG. The plugin includes its own setup and operations guides. Check the product catalog for release availability.</p><p><a href="https://steelcitylug.com/lug-platform/tools/lug-newsletter/">Newsletter in the LUG Platform catalog</a></p></article>';
        return $html;
    }
}
