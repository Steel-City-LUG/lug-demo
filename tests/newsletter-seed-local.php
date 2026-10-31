<?php
/** Local integration test using temporary Newsletter tables; never activates Demo. */
if(!defined('WP_CLI')||!WP_CLI||home_url('/')!=='http://localhost:8080/'||wp_get_environment_type()!=='local')throw new RuntimeException('Canonical local WP-CLI only.');
define('LUG_DEMO_DIR',dirname(__DIR__).'/');
require LUG_DEMO_DIR.'includes/class-lug-demo-mail-safety.php';
require LUG_DEMO_DIR.'includes/class-lug-demo-newsletter-seed.php';
if(get_option(LUG_Demo_Newsletter_Seed::OPTION,false)!==false)throw new RuntimeException('Existing local Newsletter demo seed must be preserved.');
global $wpdb;$original=$wpdb->prefix;$prefix=$original.'lug_seed_test_'.bin2hex(random_bytes(4)).'_';$tables=[];$calls=0;$count=0;
$assert=function($ok,$message)use(&$count){if(!$ok)throw new RuntimeException($message);$count++;};
$home=static fn($url)=>str_replace('http://localhost:8080','https://lugtools.steelcitylug.com',$url);
$mail=static function($pre)use(&$calls){$calls++;return false;};
$seeder=new LUG_Demo_Newsletter_Seed();
try {
    $assert(is_wp_error($seeder->seed()),'Seeding must be denied on other hosts.');
    foreach(['subscribers','jobs','events'] as $suffix){$table=$prefix.'lug_nl_'.$suffix;$wpdb->query("CREATE TABLE `$table` LIKE `{$original}lug_nl_$suffix`");$tables[]=$table;}
    $wpdb->prefix=$prefix;add_filter('home_url',$home);add_filter('pre_wp_mail',$mail,PHP_INT_MAX);
    $wpdb->insert(LUG_Newsletter_Store::table('subscribers'),['email'=>'alex.newsletter-demo@example.org','status'=>'unsubscribed','source'=>'outside-fixture','name'=>'Protected contact','token'=>bin2hex(random_bytes(32)),'updated_at'=>gmdate('Y-m-d H:i:s')]);
    $protected=(int)$wpdb->insert_id;
    $state=$seeder->seed();$assert(!is_wp_error($state)&&$state['complete'],'Seed must complete.');
    $assert(count($state['drafts'])===3&&count($state['posts'])===4,'Three drafts and one simulated report required.');
    $assert(count($state['subscribers'])===4,'An existing contact must not be reseeded.');
    $assert($seeder->seed()===$state,'Repeated seeding must be idempotent.');
    $existing=LUG_Newsletter_Store::subscriber('alex.newsletter-demo@example.org');
    $assert((int)$existing['id']===$protected&&$existing['status']==='unsubscribed'&&$existing['source']==='outside-fixture','Existing consent/status must remain unchanged.');
    $assert((int)$wpdb->get_var('SELECT COUNT(*) FROM '.LUG_Newsletter_Store::table('jobs')." WHERE status<>'accepted'")===0,'No sample queue jobs may be actionable.');
    $assert((int)$wpdb->get_var('SELECT COUNT(*) FROM '.LUG_Newsletter_Store::table('events'))===3,'Simulated engagement rows required.');
    $assert($calls===0,'Seeding must never call WordPress mail.');
    foreach($state['posts'] as $id)$assert(get_post_meta($id,LUG_Demo_Newsletter_Seed::META,true)===LUG_Demo_Newsletter_Seed::SOURCE,'Sample posts must carry ownership markers.');
    $seeder->reset();
    $assert((int)$wpdb->get_var('SELECT COUNT(*) FROM '.LUG_Newsletter_Store::table('subscribers'))===1,'Reset must preserve the existing contact.');
    $assert((int)$wpdb->get_var('SELECT COUNT(*) FROM '.LUG_Newsletter_Store::table('jobs'))===0,'Reset removes only sample report jobs.');
    $assert(get_option(LUG_Demo_Newsletter_Seed::OPTION,false)===false,'Reset removes its registry.');
    echo "$count sample seeding assertions passed; no mail called and original Newsletter tables untouched.\n";
} finally {
    if($wpdb->prefix===$prefix){$seeder->reset();$wpdb->prefix=$original;}
    remove_filter('home_url',$home);remove_filter('pre_wp_mail',$mail,PHP_INT_MAX);
    foreach($tables as $table){if(strpos($table,$prefix.'lug_nl_')!==0)throw new RuntimeException('Unexpected temporary table.');$wpdb->query("DROP TABLE IF EXISTS `$table`");}
}
