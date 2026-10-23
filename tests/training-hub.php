<?php
/**
 * Targeted training hub checks. Run with the local WordPress PHP configuration.
 */

$root = dirname( __DIR__, 4 );
require_once $root . '/wp-load.php';
require_once dirname( __DIR__ ) . '/lug-demo.php';

$renderer = new LUG_Demo_Training();
$reflection = new ReflectionClass( $renderer );
$exists     = $reflection->getMethod( 'training_page_exists' );
$exists->setAccessible( true );
$training   = get_page_by_path( 'training', OBJECT, 'page' );
if ( $training && 'publish' !== $training->post_status && $exists->invoke( $renderer ) ) {
	exit( "redirect page-status guard failed\n" );
}

$_GET = array( 'tool' => 'nope', 'voice' => 'Emma' );
$fallback = $renderer->render();
if ( false === strpos( $fallback, 'LUG Layout Designer' ) || false === strpos( $fallback, 'Start here' ) ) {
	exit( "tool whitelist fallback failed\n" );
}

// LUG Core is now available as a single-playlist training tool.
$_GET = array( 'tool' => 'lug-core' );
$core = $renderer->render();
if ( false === strpos( $core, '>LUG Core<' ) || false === strpos( $core, 'Start here' ) || false === strpos( $core, 'fn_MZxEb3Js' ) || false === strpos( $core, 'Set Up LUG Core and Who Can Do What' ) || false !== strpos( $core, 'Coming soon.' ) ) {
	exit( "LUG Core training playlist failed\n" );
}

// The legacy page is dropped from page lists only while /training/ is published.
$legacy  = new WP_Post( (object) array( 'ID' => -1, 'post_name' => 'layout-designer-training' ) );
$other   = new WP_Post( (object) array( 'ID' => -2, 'post_name' => 'plugin-tour' ) );
$listed  = $renderer->hide_legacy_page( array( $legacy, $other ) );
$expect  = $exists->invoke( $renderer ) ? array( 'plugin-tour' ) : array( 'layout-designer-training', 'plugin-tour' );
if ( wp_list_pluck( $listed, 'post_name' ) !== $expect ) {
	exit( "legacy page list filter failed\n" );
}

$_GET = array( 'tool' => 'layout-designer' );
$layout = $renderer->render();
if ( false === strpos( $layout, 'Narrator:' ) || false === strpos( $layout, 'Fourteen short videos' ) ) {
	exit( "layout voice picker or description failed\n" );
}

echo "training hub checks passed\n";
