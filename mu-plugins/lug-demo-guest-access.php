<?php
/**
 * Plugin Name: LUG Demo Guest Access
 * Description: Temporary, unprivileged visitor sessions for the tools demo only.
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class LUG_Demo_Guest_Access {
	const META = '_lug_demo_guest_expires';
	const ACTION = 'lug_demo_guest';
	const LIFETIME = 7200;

	public static function enabled() {
		return 'lugtools.steelcitylug.com' === strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	}

	public static function guest( $user ) {
		return $user instanceof WP_User && ( get_user_meta( $user->ID, self::META, true ) || 0 === strpos( $user->user_login, 'lug-guest-' ) );
	}

	public static function valid( $user ) {
		return self::enabled() && self::guest( $user )
			&& (int) get_user_meta( $user->ID, self::META, true ) > time()
			&& in_array( 'subscriber', $user->roles, true )
			&& ! array_diff( $user->roles, array( 'subscriber', 'bbp_participant' ) )
			&& ! array_diff( array_keys( array_filter( $user->caps ) ), array( 'subscriber', 'bbp_participant' ) );
	}

	public static function form() {
		if ( ! self::enabled() || is_user_logged_in() ) { return ''; }
		return '<form class="lug-demo-guest-entry" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">'
			. '<input type="hidden" name="action" value="' . self::ACTION . '">'
			. wp_nonce_field( self::ACTION, '_lug_guest_nonce', true, false )
			. '<p>Try the tools as a regular member. Your demo session lasts two hours. Please do not enter personal information.</p>'
			. '<button type="submit" class="button button-primary">Try the tools — no password needed</button></form>';
	}

	public static function footer() {
		if ( ! self::enabled() || is_user_logged_in() ) { return; }
		echo '<aside style="padding:20px;margin:20px auto;max-width:700px;text-align:center;background:#f0f4f8;color:#172b4d;border-radius:12px" aria-label="Demo member access">' . self::form() . '</aside>';
	}

	public static function enter() {
		if ( ! self::enabled() ) { wp_die( 'Demo access is unavailable.', '', array( 'response' => 403 ) ); }
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { wp_die( 'Please use the demo sign-in button.', '', array( 'response' => 405 ) ); }
		nocache_headers();
		check_admin_referer( self::ACTION, '_lug_guest_nonce' );
		if ( is_user_logged_in() ) { wp_safe_redirect( home_url( '/' ) ); exit; }
		self::cleanup();
		// Serialize admissions so simultaneous requests cannot bypass the limits.
		$lock = '_lug_demo_guest_admission';
		if ( ! add_option( $lock, time(), '', false ) ) {
			if ( (int) get_option( $lock ) < time() - 30 ) { delete_option( $lock ); }
			wp_die( 'Please try again in a moment.', '', array( 'response' => 429 ) );
		}
		try {
			$ip_key = 'lug_guest_' . hash_hmac( 'sha256', (string) ( $_SERVER['REMOTE_ADDR'] ?? 'unknown' ), wp_salt() );
			$ip_count = (int) get_transient( $ip_key );
			$total = (int) get_transient( 'lug_guest_total' );
			if ( $ip_count >= 6 || $total >= 60 ) {
				throw new RuntimeException( 'Demo sign-in is busy. Please try again later.' );
			}
			set_transient( $ip_key, $ip_count + 1, HOUR_IN_SECONDS );
			set_transient( 'lug_guest_total', $total + 1, HOUR_IN_SECONDS );
			$login = 'lug-guest-' . bin2hex( random_bytes( 12 ) );
			$id = wp_insert_user( array(
				'user_login' => $login,
				'user_pass' => bin2hex( random_bytes( 32 ) ),
				'user_email' => $login . '@example.invalid',
				'display_name' => 'Demo visitor ' . substr( $login, -6 ),
				'role' => 'subscriber',
				'meta_input' => array( self::META => time() + self::LIFETIME ),
			) );
			if ( is_wp_error( $id ) ) { throw new RuntimeException( 'Could not start a demo session. Please try again.' ); }
		} catch ( Throwable $error ) {
			delete_option( $lock );
			wp_die( esc_html( $error->getMessage() ), '', array( 'response' => 429 ) );
		}
		delete_option( $lock );
		if ( ! self::valid( get_userdata( $id ) ) ) {
			wp_die( 'Demo member permissions could not be verified.', '', array( 'response' => 403 ) );
		}
		wp_set_current_user( $id );
		wp_set_auth_cookie( $id, false, is_ssl() );
		wp_safe_redirect( home_url( '/active-tools/' ) );
		exit;
	}

	public static function cleanup() {
		if ( ! self::enabled() ) { return; }
		$ids = get_users( array( 'fields' => 'ID', 'number' => 100, 'meta_query' => array( array( 'key' => self::META, 'value' => time(), 'compare' => '<=', 'type' => 'NUMERIC' ) ) ) );
		if ( ! $ids ) { return; }
		require_once ABSPATH . 'wp-admin/includes/user.php';
		foreach ( $ids as $id ) {
			$user = get_userdata( $id );
			// Never delete an account that has since gained a real role/capability.
			if ( $user && 0 === strpos( $user->user_login, 'lug-guest-' ) && ! array_diff( array_keys( array_filter( $user->caps ) ), array( 'subscriber', 'bbp_participant' ) ) ) {
				wp_delete_user( $id );
			}
		}
	}
}

add_action( 'admin_post_nopriv_lug_demo_guest', array( 'LUG_Demo_Guest_Access', 'enter' ) );
add_action( 'admin_post_lug_demo_guest', array( 'LUG_Demo_Guest_Access', 'enter' ) );
add_action( 'wp_footer', array( 'LUG_Demo_Guest_Access', 'footer' ) );
add_action( 'login_footer', array( 'LUG_Demo_Guest_Access', 'footer' ) );
add_action( 'wp_loaded', static function () {
	if ( ! LUG_Demo_Guest_Access::enabled() ) { return; }
	add_shortcode( 'lug_demo_accounts', array( 'LUG_Demo_Guest_Access', 'form' ) );
	if ( ! wp_next_scheduled( 'lug_demo_guest_cleanup' ) ) { wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'lug_demo_guest_cleanup' ); }
} );
add_action( 'lug_demo_guest_cleanup', array( 'LUG_Demo_Guest_Access', 'cleanup' ) );
add_filter( 'determine_current_user', static function ( $id ) {
	$user = $id ? get_userdata( $id ) : false;
	return LUG_Demo_Guest_Access::guest( $user ) && ! LUG_Demo_Guest_Access::valid( $user ) ? 0 : $id;
}, PHP_INT_MAX );
add_filter( 'authenticate', static function ( $user ) {
	return LUG_Demo_Guest_Access::guest( $user ) ? new WP_Error( 'authentication_failed', 'Please start a new demo session.' ) : $user;
}, PHP_INT_MAX );
add_filter( 'user_has_cap', static function ( $caps, $required, $args, $user ) {
	if ( ! LUG_Demo_Guest_Access::guest( $user ) ) { return $caps; }
	$allowed = LUG_Demo_Guest_Access::valid( $user ) ? array( 'read', 'exist', 'level_0', 'spectate', 'participate', 'read_private_forums', 'publish_topics', 'edit_topics', 'publish_replies', 'edit_replies', 'assign_topic_tags' ) : array();
	foreach ( $caps as $cap => $value ) { $caps[ $cap ] = $value && in_array( $cap, $allowed, true ); }
	return $caps;
}, PHP_INT_MAX, 4 );
add_filter( 'allow_password_reset', static function ( $allowed, $id ) { return LUG_Demo_Guest_Access::guest( get_userdata( $id ) ) ? false : $allowed; }, PHP_INT_MAX, 2 );
add_filter( 'wp_is_application_passwords_available_for_user', static function ( $allowed, $user ) { return LUG_Demo_Guest_Access::guest( $user ) ? false : $allowed; }, PHP_INT_MAX, 2 );
add_filter( 'auth_cookie_expiration', static function ( $seconds, $id ) { return LUG_Demo_Guest_Access::guest( get_userdata( $id ) ) ? LUG_Demo_Guest_Access::LIFETIME : $seconds; }, PHP_INT_MAX, 2 );
add_action( 'admin_init', static function () {
	if ( ! LUG_Demo_Guest_Access::guest( wp_get_current_user() ) || wp_doing_ajax() || 'admin-post.php' === basename( $_SERVER['PHP_SELF'] ?? '' ) ) { return; }
	wp_safe_redirect( home_url( '/active-tools/' ) ); exit;
} );
