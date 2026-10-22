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

$_GET = array( 'tool' => 'lug-core' );
$coming_soon = $renderer->render();
if ( false === strpos( $coming_soon, 'Coming soon.' ) || false !== strpos( $coming_soon, 'Narrator:' ) ) {
	exit( "single-voice picker visibility failed\n" );
}

$_GET = array( 'tool' => 'layout-designer' );
$layout = $renderer->render();
if ( false === strpos( $layout, 'Narrator:' ) || false === strpos( $layout, 'Fourteen short videos' ) ) {
	exit( "layout voice picker or description failed\n" );
}

echo "training hub checks passed\n";
