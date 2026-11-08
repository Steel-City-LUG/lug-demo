<?php
/**
 * Plugin Name: LUG Demo Guest Access
 * Description: Temporary, unprivileged visitor sessions for the tools demo only.
 * Version: 1.0.1
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

	public static function personal_layouts( $response, $handler, $request ) {
		if ( ! self::enabled() || ! self::guest( wp_get_current_user() ) || null !== $response ) { return $response; }
		$route = $request->get_route();
		if ( 0 !== strpos( $route, '/lug-layout/v1/' ) ) { return $response; }
		$denied = new WP_Error( 'lug_demo_personal_layouts_only', 'Demo visitors can use personal layouts only.', array( 'status' => 403 ) );
		if ( 0 === strpos( $route, '/lug-layout/v1/shared/' ) ) { return $denied; }
		if ( '/lug-layout/v1/layouts' === $route ) {
			if ( 'GET' === $request->get_method() ) { $request->set_param( 'user_id', get_current_user_id() ); return $response; }
			if ( 'POST' !== $request->get_method() ) { return $denied; }
			// Insert private from the outset; the regular creator defaults to members.
			global $wpdb;
			$uid = get_current_user_id();
			$name = sanitize_text_field( $request->get_param( 'name' ) );
			if ( ! $name ) { return new WP_Error( 'missing_name', 'A layout name is required.', array( 'status' => 400 ) ); }
			$json = array( 'schemaVersion' => 1, 'groups' => array(), 'units' => array( 'canonical' => 'inches', 'display' => 'inches' ), 'canvas' => array( 'panX' => 0, 'panY' => 0, 'zoom' => 1 ), 'layers' => array( 'tables' => array(), 'equipment' => array(), 'baseplates' => array(), 'mocs' => array(), 'annotations' => array() ), 'legend' => array( 'collapsed' => false ), 'meta' => array( 'createdBy' => $uid, 'lastEditedBy' => $uid, 'permissions' => array( 'edit_policy' => 'owner', 'editor_ids' => array() ) ) );
			$ok = $wpdb->insert( lug_layout_table_name(), array( 'name' => $name, 'description' => wp_kses_post( (string) $request->get_param( 'description' ) ), 'owner_user_id' => $uid, 'updated_by_user_id' => $uid, 'event_id' => null, 'visibility' => 'private', 'layout_json' => wp_json_encode( $json ), 'schema_version' => 1 ) );
			return $ok ? new WP_REST_Response( array( 'id' => (int) $wpdb->insert_id ), 200 ) : new WP_Error( 'layout_create_failed', 'Could not create layout.', array( 'status' => 500 ) );
		}
		if ( preg_match( '#^/lug-layout/v1/layouts/(\d+)(?:/|$)#', $route, $match ) ) {
			$row = lug_layout_get_row( (int) $match[1] );
			if ( ! $row || (int) $row->owner_user_id !== get_current_user_id() || 'private' !== $row->visibility ) { return $denied; }
			if ( str_ends_with( $route, '/duplicate' ) ) { return $denied; }
			if ( ! in_array( $request->get_method(), array( 'GET', 'HEAD' ), true ) ) {
				foreach ( array( 'visibility' => 'private', 'edit_policy' => 'owner', 'editPolicy' => 'owner' ) as $key => $allowed ) {
					if ( $request->has_param( $key ) && $allowed !== $request->get_param( $key ) ) { return $denied; }
				}
				foreach ( array( 'editor_ids', 'editorIds', 'event_id' ) as $key ) {
					if ( $request->get_param( $key ) ) { return $denied; }
				}
			}
		}
		return $response;
	}
}

add_filter( 'rest_request_before_callbacks', array( 'LUG_Demo_Guest_Access', 'personal_layouts' ), PHP_INT_MAX, 3 );

// Cross-LUG sharing includes public bearer-token routes, so disable it for the
// entire demo host, including anonymous requests, rather than only logged-in guests.
add_filter( 'pre_option_lug_layout_collaboration_enabled', static function ( $value ) {
	return LUG_Demo_Guest_Access::enabled() ? 0 : $value;
}, PHP_INT_MAX );

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
