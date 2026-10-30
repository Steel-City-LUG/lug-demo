<?php
/** Run with wp eval-file; uses only the canonical local runtime. */
if (!defined('ABSPATH') || home_url('/') !== 'http://localhost:8080/' || wp_get_environment_type() !== 'local') throw new RuntimeException('LOCAL ONLY');
add_filter('home_url',static fn($url)=>str_replace('http://localhost:8080','https://lugtools.steelcitylug.com',$url));
require dirname(__DIR__).'/includes/class-lug-demo-mail-safety.php';
if(apply_filters('pre_wp_mail',null,[])!==false)throw new RuntimeException('Mail guard missing; refusing transport test.');
if(wp_mail('nobody@example.org','LUG Demo blocked mail assertion','This must never be delivered.')!==false)throw new RuntimeException('Mail not blocked.');
$request=new WP_REST_Request('POST','/lug-newsletter/v1/settings');
$result=apply_filters('rest_pre_dispatch',null,rest_get_server(),$request);
if(!is_wp_error($result)||$result->get_error_data()['status']!==403)throw new RuntimeException('Newsletter REST not denied.');
wp_set_current_user(1);
if(current_user_can('send_lug_newsletters')||current_user_can('edit_lug_newsletters'))throw new RuntimeException('Newsletter capabilities not denied.');
// Exercise core PHPMailer with every pre-mail short circuit removed. The guard
// clears recipients before core can invoke any mail/SMTP transport.
remove_all_filters('pre_wp_mail');
if(wp_mail('nobody@example.org','LUG Demo transport block assertion','This must never be delivered.')!==false)throw new RuntimeException('Transport fallback not blocked.');
echo "PASS local WordPress mail, transport fallback, API and administrator denial; no mail delivered.\n";
