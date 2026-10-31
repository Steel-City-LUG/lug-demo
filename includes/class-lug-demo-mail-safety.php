<?php
/** Public demo safety: no email delivery and no interactive Newsletter workspace. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class LUG_Demo_Mail_Safety {
	public static function applies() {
		return 'lugtools.steelcitylug.com' === strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
	}
	public static function boot() {
		if ( ! self::applies() ) { return; }
		add_filter( 'pre_wp_mail', '__return_false', PHP_INT_MAX );
		add_action( 'phpmailer_init', [ __CLASS__, 'stop_transport' ], PHP_INT_MAX );
		add_filter( 'user_has_cap', [ __CLASS__, 'deny_newsletter_caps' ], PHP_INT_MAX );
		add_filter( 'rest_pre_dispatch', [ __CLASS__, 'deny_newsletter_api' ], PHP_INT_MAX, 3 );
		add_filter( 'pre_schedule_event', [ __CLASS__, 'deny_worker_schedule' ], PHP_INT_MAX, 2 );
		add_filter( 'pre_reschedule_event', [ __CLASS__, 'deny_worker_schedule' ], PHP_INT_MAX, 2 );
		add_action( 'plugins_loaded', [ __CLASS__, 'stop_worker' ], PHP_INT_MAX );
		add_action( 'init', [ __CLASS__, 'stop_worker' ], PHP_INT_MAX );
	}
	public static function stop_transport( $mailer ) {
		// PHPMailer rejects a recipient-free message before opening any transport.
		// Do not throw here: WordPress calls phpmailer_init outside its try block.
		$mailer->clearAllRecipients();
	}
	public static function deny_newsletter_caps( $caps ) {
		foreach ( [ 'edit_lug_newsletters', 'send_lug_newsletters', 'manage_lug_audience', 'manage_lug_newsletter' ] as $cap ) { $caps[ $cap ] = false; }
		return $caps;
	}
	public static function deny_newsletter_api( $result, $server, $request ) {
		if ( preg_match( '#^/lug-newsletter/v[0-9]+(?:/|$)#', $request->get_route() ) ) {
			return new WP_Error( 'lug_demo_newsletter_disabled', 'Newsletter offers read-only samples, screenshots and training on this demo site. Sending and editing are disabled.', [ 'status' => 403 ] );
		}
		return $result;
	}
	public static function deny_worker_schedule( $result, $event ) {
		return 'lug_newsletter_worker' === $event->hook ? false : $result;
	}
	public static function stop_worker() { remove_all_actions( 'lug_newsletter_worker' ); }
}
// WordPress loads active plugins before its pluggable mail function. On this
// dedicated public demo, stop before any mail integration can intercept wp_mail.
if ( LUG_Demo_Mail_Safety::applies() && ! function_exists( 'wp_mail' ) ) {
	function wp_mail( $to, $subject, $message, $headers = '', $attachments = [], $embeds = [] ) {
		return false;
	}
}
LUG_Demo_Mail_Safety::boot();
