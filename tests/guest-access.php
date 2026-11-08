<?php
// Run with the local WordPress PHP configuration. Uses disposable local users.
if ( ! function_exists( 'get_userdata' ) ) { require_once dirname( __DIR__, 4 ) . '/wp-load.php'; }
if ( ! in_array( wp_get_environment_type(), array( 'local', 'development' ), true ) ) { throw new RuntimeException( 'Local test only.' ); }
require_once dirname( __DIR__ ) . '/mu-plugins/lug-demo-guest-access.php';
require_once ABSPATH . 'wp-admin/includes/user.php';
$ids = array();
$assert = static function ( $ok, $message ) { if ( ! $ok ) { throw new RuntimeException( $message ); } };
try {
	$assert( ! LUG_Demo_Guest_Access::enabled(), 'Disabled on local/other hosts' );
	$host = static function () { return 'https://lugtools.steelcitylug.com'; };
	add_filter( 'pre_option_home', $host );
	foreach ( array( time() + 7200, time() - 1 ) as $expires ) {
		$id = wp_insert_user( array( 'user_login' => 'lug-guest-' . bin2hex( random_bytes( 12 ) ), 'user_pass' => bin2hex( random_bytes( 32 ) ), 'role' => 'subscriber', 'meta_input' => array( LUG_Demo_Guest_Access::META => $expires ) ) );
		$assert( ! is_wp_error( $id ), 'Guest creation' );
		$ids[] = $id;
	}
	$guest = get_userdata( $ids[0] );
	$assert( LUG_Demo_Guest_Access::valid( $guest ), 'Active subscriber accepted' );
	$assert( user_can( $guest, 'read' ), 'Member read access' );
	foreach ( array( 'manage_options', 'edit_users', 'install_plugins', 'edit_posts', 'upload_files', 'unfiltered_html' ) as $cap ) {
		$assert( ! user_can( $guest, $cap ), 'Denied ' . $cap );
	}
	$assert( is_wp_error( apply_filters( 'authenticate', $guest, $guest->user_login, 'unused' ) ), 'Password login denied' );
	$assert( ! apply_filters( 'allow_password_reset', true, $guest->ID ), 'Password reset denied' );
	$assert( ! apply_filters( 'wp_is_application_passwords_available_for_user', true, $guest ), 'Application passwords denied' );
	$assert( ! LUG_Demo_Guest_Access::valid( get_userdata( $ids[1] ) ), 'Expired guest denied' );
	$guest->add_cap( 'manage_options' );
	$assert( ! LUG_Demo_Guest_Access::valid( get_userdata( $guest->ID ) ), 'Unexpected direct capability invalidates session' );
	$assert( ! user_can( get_userdata( $guest->ID ), 'manage_options' ), 'Unexpected capability cannot be used' );
	$guest->remove_cap( 'manage_options' );
	$guest->set_role( 'administrator' );
	$assert( ! LUG_Demo_Guest_Access::valid( get_userdata( $guest->ID ) ), 'Unexpected role invalidates session' );
	LUG_Demo_Guest_Access::cleanup();
	$assert( ! get_userdata( $ids[1] ), 'Expired guest deleted' );
	$assert( (bool) get_userdata( $ids[0] ), 'Promoted account preserved during cleanup' );
	remove_filter( 'pre_option_home', $host );
	$assert( ! LUG_Demo_Guest_Access::valid( get_userdata( $ids[0] ) ), 'Foreign host rejected' );
	echo "Guest access security checks passed.\n";
} finally {
	foreach ( $ids as $id ) { if ( get_userdata( $id ) ) { wp_delete_user( $id ); } }
}
