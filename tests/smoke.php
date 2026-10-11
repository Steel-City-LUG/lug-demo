<?php

/**
 * Local WordPress seed/reset smoke test.
 *
 * Run with the web runtime's PHP configuration:
 * php -c "C:\path\to\runtime\php.ini" tests/smoke.php
 */

$root = dirname( __DIR__, 4 );
require_once $root . '/wp-load.php';
require_once dirname( __DIR__ ) . '/lug-demo.php';

$demo  = new LUG_Demo();
$batch = LUG_Demo::BATCH_ID;
$front_before = array(
	'show_on_front' => get_option( 'show_on_front' ),
	'page_on_front' => get_option( 'page_on_front' ),
);

$demo->seed_accounts( $batch );
$demo->seed_member_profiles( $batch );
$demo->seed_officer_assignments( $batch );
$demo->seed_site_content( $batch );
$demo->seed_events( $batch );
$demo->seed_event_shifts( $batch );
$demo->seed_forums( $batch );
$demo->seed_activity( $batch );

global $wpdb;

$users = count(
	get_users(
		array(
			'fields'     => 'ids',
			'meta_key'   => LUG_Demo::USER_META_BATCH,
			'meta_value' => $batch,
			'number'     => -1,
		)
	)
);

$pages = (int) $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON m.post_id = p.ID WHERE m.meta_key = %s AND m.meta_value = %s",
		LUG_Demo::CONTENT_META_BATCH,
		$batch
	)
);

$events   = count( (array) get_option( LUG_Demo::OPTION_EVENTS, array() ) );
$shifts   = count( (array) get_option( LUG_Demo::OPTION_SHIFTS, array() ) );
$forums   = count( (array) get_option( LUG_Demo::OPTION_FORUMS, array() ) );
$activity = count( (array) get_option( LUG_Demo::OPTION_ACTIVITY, array() ) );

$event_table = function_exists( 'lug_event_provider_table' ) ? lug_event_provider_table() : 'lug_events';
$event_rows  = (array) $wpdb->get_results( "SELECT start_date, end_date, status, creator FROM `{$event_table}` WHERE id IN (" . implode( ',', array_map( 'absint', (array) get_option( LUG_Demo::OPTION_EVENTS, array() ) ) ) . ')', ARRAY_A );
$organizer   = get_user_by( 'login', 'demo1' );
$timeline    = array( 'past' => 0, 'current' => 0, 'future' => 0 );
$now         = current_time( 'timestamp' );
foreach ( $event_rows as $event_row ) {
	$start = strtotime( (string) $event_row['start_date'] );
	$end   = strtotime( (string) $event_row['end_date'] );
	if ( $end < $now ) {
		$timeline['past']++;
	} elseif ( $start <= $now && $end >= $now ) {
		$timeline['current']++;
	} elseif ( $start > $now ) {
		$timeline['future']++;
	}
}

printf( "seeded users=%d pages=%d events=%d shifts=%d forums=%d activity=%d\n", $users, $pages, $events, $shifts, $forums, $activity );
printf( "event_timeline past=%d current=%d future=%d\n", $timeline['past'], $timeline['current'], $timeline['future'] );

$demo->reset_event_shifts( $batch );
$demo->reset_events( $batch );
$demo->reset_forums( $batch );
$demo->reset_activity( $batch );
$demo->reset_site_content( $batch );
$demo->reset_officer_assignments( $batch );
$demo->reset_member_profiles( $batch );
$demo->reset_accounts( $batch );

$users_after = count(
	get_users(
		array(
			'fields'     => 'ids',
			'meta_key'   => LUG_Demo::USER_META_BATCH,
			'meta_value' => $batch,
			'number'     => -1,
		)
	)
);
$pages_after = (int) $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON m.post_id = p.ID WHERE m.meta_key = %s AND m.meta_value = %s",
		LUG_Demo::CONTENT_META_BATCH,
		$batch
	)
);

printf( "reset users=%d pages=%d events=%d shifts=%d\n", $users_after, $pages_after, count( (array) get_option( LUG_Demo::OPTION_EVENTS, array() ) ), count( (array) get_option( LUG_Demo::OPTION_SHIFTS, array() ) ) );

if (
	0 !== $users_after
	|| 0 !== $pages_after
	|| 1 !== $timeline['past']
	|| 1 !== $timeline['current']
	|| 1 !== $timeline['future']
	|| ! $organizer
	|| count( array_filter( $event_rows, function ( $event_row ) use ( $organizer ) { return (int) $event_row['creator'] === (int) $organizer->ID; } ) ) !== count( $event_rows )
	|| $front_before['show_on_front'] !== get_option( 'show_on_front' )
	|| (string) $front_before['page_on_front'] !== (string) get_option( 'page_on_front' )
) {
	exit( 1 );
}
