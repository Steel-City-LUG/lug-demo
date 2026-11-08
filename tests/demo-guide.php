<?php
if ( ! function_exists( 'home_url' ) ) { require_once dirname( __DIR__, 4 ) . '/wp-load.php'; }
$guide = require dirname( __DIR__ ) . '/fixtures/demo-guide.php';
foreach ( array( 'welcome-to-the-demo-lug', 'getting-started', 'active-tools', 'plugin-tour', 'about-this-demo' ) as $slug ) {
	if ( empty( $guide[ $slug ] ) || ! str_contains( $guide[ $slug ], '[lug_demo_accounts]' ) || str_contains( $guide[ $slug ], 'use your own account' ) ) { throw new RuntimeException( 'Unclear visitor entry: ' . $slug ); }
}
foreach ( array( '/bbn/create/', '/geobrick/', '/rotating-displays/', '/officers/', '/member-map/', '/newsletter/' ) as $path ) {
	if ( ! str_contains( $guide['active-tools'], $path ) ) { throw new RuntimeException( 'Missing live tool or preview: ' . $path ); }
}
foreach ( array( 'Private, owner-only', 'Read-only samples', 'Video-only', 'Setup preview only', 'sending and scheduling are blocked' ) as $label ) {
	if ( ! str_contains( $guide['active-tools'], $label ) ) { throw new RuntimeException( 'Missing demo boundary: ' . $label ); }
}
$schema = file_get_contents( dirname( __DIR__ ) . '/fixtures/core-schema.sql' );
foreach ( require dirname( __DIR__ ) . '/fixtures/core-schema.php' as $query ) {
	if ( 1 !== substr_count( $query, 'CREATE TABLE IF NOT EXISTS' ) ) { throw new RuntimeException( 'Setup must execute one table definition at a time.' ); }
}
if ( preg_match( '/\b(?:INSERT INTO|REPLACE INTO|DROP TABLE|TRUNCATE TABLE)\b/i', $schema ) ) { throw new RuntimeException( 'Setup must contain schema only.' ); }
if ( str_contains( $schema, 'ENGINE=MyISAM' ) ) { throw new RuntimeException( 'New demo tables must support transactions.' ); }
echo "Visitor guide and schema safety checks passed.\n";
