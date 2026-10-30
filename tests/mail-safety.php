<?php
/** Standalone boundary checks; no mail transport or database is used. */
define('ABSPATH', __DIR__);
$site='https://lugtools.steelcitylug.com'; $hooks=[]; $removed=[]; $checks=0;
function home_url($path){global $site; return $site.$path;}
function wp_parse_url($url,$part){return parse_url($url,$part);}
function add_filter($name,$callback,$priority=10,$args=1){global $hooks;$hooks[$name]=[$callback,$priority,$args];}
function add_action($name,$callback,$priority=10,$args=1){add_filter($name,$callback,$priority,$args);}
function remove_all_actions($name){global $removed;$removed[]=$name;}
class WP_Error{public function __construct(public $code,public $message,public $data){}}
function check($condition,$message){global $checks;$checks++;if(!$condition)throw new RuntimeException($message);}
require dirname(__DIR__).'/includes/class-lug-demo-mail-safety.php';
check(wp_mail('nobody@example.org','Blocked test','Never delivered')===false,'Early pluggable mail block must return failure before integration hooks.');
check(isset($hooks['pre_wp_mail'])&&$hooks['pre_wp_mail'][0]==='__return_false','Every WordPress email must fail closed.');
check($hooks['pre_wp_mail'][1]===PHP_INT_MAX,'Mail guard must run last.');
check(isset($hooks['phpmailer_init']),'Transport fallback must be installed.');
$mailer=new class{public $cleared=false;public function clearAllRecipients(){$this->cleared=true;}};
LUG_Demo_Mail_Safety::stop_transport($mailer);check($mailer->cleared,'Transport fallback must clear every recipient before transport.');
$caps=LUG_Demo_Mail_Safety::deny_newsletter_caps(['manage_options'=>true,'send_lug_newsletters'=>true]);
check($caps['manage_options']&&!$caps['send_lug_newsletters']&&!$caps['edit_lug_newsletters']&&!$caps['manage_lug_newsletter']&&!$caps['manage_lug_audience'],'Deny Newsletter even for administrators.');
foreach(['/lug-newsletter/v1/settings','/lug-newsletter/v2/send','/lug-newsletter/v1'] as $route){
 $request=new class($route){public function __construct(private $route){}public function get_route(){return $this->route;}};
 $result=LUG_Demo_Mail_Safety::deny_newsletter_api(null,null,$request);
 check($result instanceof WP_Error&&$result->data['status']===403,'All Newsletter API methods must be denied.');
}
$request=new class{public function get_route(){return '/wp/v2/pages';}};
check(LUG_Demo_Mail_Safety::deny_newsletter_api('unchanged',null,$request)==='unchanged','Other REST tools must remain available.');
check(LUG_Demo_Mail_Safety::deny_worker_schedule(null,(object)['hook'=>'lug_newsletter_worker'])===false,'Newsletter cannot schedule a worker.');
check(LUG_Demo_Mail_Safety::deny_worker_schedule(null,(object)['hook'=>'other'])===null,'Other cron events are preserved.');
LUG_Demo_Mail_Safety::stop_worker();check($removed===['lug_newsletter_worker'],'Existing queue callbacks are removed.');
foreach(['https://steelcitylug.com','https://test.steelcitylug.com','http://localhost:8080','https://lugtools.steelcitylug.com.evil.example'] as $site){$hooks=[];LUG_Demo_Mail_Safety::boot();check(!$hooks,'Safety policy must be scoped to the configured demo site.');}
echo "$checks mail safety assertions passed\n";
