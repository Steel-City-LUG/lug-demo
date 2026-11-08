<?php
/** Idempotent, demo-only setup. wp eval-file tools/provision-demo.php */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! class_exists( 'LUG_Demo' ) || 'lugtools.steelcitylug.com' !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
	throw new RuntimeException( 'Run only through WP-CLI on the tools demo.' );
}
global $wpdb;
$created = array();
// Exported CREATE statements only: no member records or copied site data.
foreach ( require dirname( __DIR__ ) . '/fixtures/core-schema.php' as $sql ) {
	if ( ! preg_match( '/CREATE TABLE IF NOT EXISTS `([a-z_]+)`/', $sql, $match ) ) { continue; }
	$table = $match[1];
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) { continue; }
	if ( false === $wpdb->query( rtrim( trim( $sql ), ';' ) ) ) { throw new RuntimeException( 'Could not initialize ' . $table ); }
	$created[] = $table;
}
$organizer = get_user_by( 'login', 'lug-demo-organizer' );
if ( ! $organizer ) { throw new RuntimeException( 'Missing internal fixture owner; do not recreate privileged accounts.' ); }
$demo = new LUG_Demo();
if ( ! get_option( LUG_Demo::OPTION_EVENTS ) ) {
	$demo->seed_events( LUG_Demo::BATCH_ID );
	$demo->seed_event_shifts( LUG_Demo::BATCH_ID );
}
$demo->seed_member_profiles( LUG_Demo::BATCH_ID );
$demo->seed_officer_assignments( LUG_Demo::BATCH_ID );
foreach ( (array) get_option( LUG_Demo::OPTION_EVENTS, array() ) as $event_id ) {
	$wpdb->update( 'lug_events', array( 'name' => 'Demo public display planning night (upcoming)' ), array( 'id' => (int) $event_id, 'name' => 'Summer public display planning night (upcoming)' ) );
}
if ( function_exists( 'lug_ispy_table_name' ) ) {
	$wpdb->update( lug_ispy_table_name( 'events' ), array( 'name' => 'I-Spy: Steel City Landmarks', 'message' => 'Find each landmark in the demonstration display and check it off on your phone.' ), array( 'name' => 'Build Along: Steel City Landmarks' ) );
}

// BBN's standalone creator is opt-in; a link alone does not register its route.
$bbn = (array) get_option( 'bbn_settings', array() );
$bbn['public_experience'] = array_merge( (array) ( $bbn['public_experience'] ?? array() ), array( 'creator_route_enabled' => true, 'creator_route_path' => '/bbn/create/' ) );
update_option( 'bbn_settings', $bbn );
if ( class_exists( 'BBN_Events' ) ) { BBN_Events::register_rewrite_rules(); }

// /officers/ is owned by the Officers plugin's native router.
$old_roster = get_page_by_path( 'officers' );
if ( $old_roster && LUG_Demo::BATCH_ID === get_post_meta( $old_roster->ID, LUG_Demo::CONTENT_META_BATCH, true ) && ! get_page_by_path( 'officer-roster' ) ) {
	wp_update_post( array( 'ID' => $old_roster->ID, 'post_name' => 'officer-roster' ) );
}

$categories = array( 1 => 'Sets', 2 => 'Equipment', 3 => 'Parts', 4 => 'Baseplates', 5 => 'MOCs', 6 => 'Tables' );
foreach ( $categories as $id => $name ) {
	if ( ! $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM lug_inventory_categories WHERE id=%d', $id ) ) ) {
		if ( false === $wpdb->insert( 'lug_inventory_categories', array( 'id' => $id, 'name' => $name, 'desc' => 'Demonstration ' . strtolower( $name ) ) ) ) { throw new RuntimeException( 'Category setup failed.' ); }
	}
}
foreach ( array( array( 2, 'Demo display transport cart', 2 ), array( 6, 'Demo folding display table', 6 ), array( 4, 'Demo green 32x32 baseplate', 12 ) ) as $item ) {
	if ( ! $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM lug_inventory WHERE description=%s AND owner=0', $item[1] ) ) ) {
		if ( false === $wpdb->insert( 'lug_inventory', array( 'cat_id' => $item[0], 'description' => $item[1], 'quantity' => $item[2], 'image' => '', 'status' => 0, 'owner' => 0, 'addedby' => $organizer->ID ) ) ) { throw new RuntimeException( 'Inventory sample failed.' ); }
	}
}

$pages = array(
	'rotating-displays' => array( 'Rotating Displays', '<p>Try the member workflow: open the demo display, choose a free future date range, reserve it, then cancel your reservation. All locations are fictional.</p>[lug_displays]' ),
	'officer-roster' => array( 'Demo officer roster', '<p>Inspect the fictional roster. Appointments, elections and nominations are administrator workflows and are not editable by demo guests.</p>[lug_officers]' ),
	'member-map' => array( 'Demo member ZIP map', '<p>These ZIP clusters come from fictional demo profiles. Explore the map; do not enter your home address.</p>[lug_user_zip_map]' ),
	'member-notifications' => array( 'Welcome messages and notifications', '<h2>Example welcome message</h2><blockquote><p>Welcome to the demo club! Start by choosing an interest badge, finding an event and exploring the member tools.</p></blockquote><p>A real club can send welcome messages and event updates to members. This public demo does not deliver email. Member notifications are a workflow preview; use fictional information only.</p><p><a href="/my-badges/">Choose an interest badge</a> or <a href="/events/">explore events</a>.</p>' ),
	'integrations' => array( 'Integration previews', '<h2>Forum to Discord bridge</h2><p>In a real club, an approved forum announcement can notify a configured Discord channel. This demo has no connected webhook and sends nothing to Discord.</p><h2>Discord Connect</h2><p>A member connects their own Discord identity; the club can synchronize membership roles. Real OAuth and role synchronization are disabled here. Do not enter Discord credentials.</p><h2>LUG Platform</h2><p>The connector links an approved club site to its hub. Pairing and cross-site connections require administrator setup and are not available to public guests.</p><p>These are setup previews, not live external integrations.</p>' ),
);
foreach ( $pages as $slug => $page ) {
	$existing = get_page_by_path( $slug );
	if ( $existing ) {
		if ( LUG_Demo::BATCH_ID !== get_post_meta( $existing->ID, LUG_Demo::CONTENT_META_BATCH, true ) ) { throw new RuntimeException( 'Unowned page collision: ' . $slug ); }
		$id = wp_update_post( array( 'ID' => $existing->ID, 'post_title' => $page[0], 'post_content' => $page[1] ), true );
	} else {
		$id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $page[0], 'post_content' => $page[1], 'post_author' => $organizer->ID ), true );
	}
	if ( is_wp_error( $id ) ) { throw new RuntimeException( 'Page setup failed: ' . $slug ); }
	update_post_meta( $id, LUG_Demo::CONTENT_META_BATCH, LUG_Demo::BATCH_ID );
	if ( 'rotating-displays' === $slug ) { update_option( 'lug_rd_page_id', $id ); }
}
$location_table = $wpdb->prefix . 'lug_locations';
$display_table = $wpdb->prefix . 'lug_displays';
$location_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $location_table WHERE name=%s", 'Demo Community Library' ) );
if ( ! $location_id ) {
	if ( false === $wpdb->insert( $location_table, array( 'name' => 'Demo Community Library', 'city' => 'Pittsburgh', 'region' => 'PA', 'description' => 'Fictional demonstration location.', 'created_by' => $organizer->ID ) ) ) { throw new RuntimeException( 'Display location setup failed.' ); }
	$location_id = $wpdb->insert_id;
}
if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $display_table WHERE name=%s", 'Demo library showcase' ) ) ) {
	if ( false === $wpdb->insert( $display_table, array( 'name' => 'Demo library showcase', 'location' => 'Demo Community Library', 'location_id' => $location_id, 'dimensions' => '48 x 24 x 24 inches', 'description' => 'Try reserving a free date range, then cancel your reservation.', 'width' => 48, 'height' => 24, 'depth' => 24, 'created_by' => $organizer->ID, 'is_active' => 1 ) ) ) { throw new RuntimeException( 'Display sample setup failed.' ); }
}
flush_rewrite_rules( false );
echo wp_json_encode( array( 'created_schema_tables' => $created, 'demo_events' => get_option( LUG_Demo::OPTION_EVENTS ), 'member_demo_pages' => array_keys( $pages ), 'shared_layouts_enabled' => function_exists( 'lug_layout_shared_layouts_enabled' ) && lug_layout_shared_layouts_enabled() ) ) . PHP_EOL;
