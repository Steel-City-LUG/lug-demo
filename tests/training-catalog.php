<?php
/** Validate this Core-only catalog refresh without booting or changing WordPress. */
$catalog_path = dirname( __DIR__ ) . '/fixtures/training-videos.php';
$catalog = require $catalog_path;
$source = str_replace( "\r\n", "\n", file_get_contents( $catalog_path ) );
$assertions = 0;
function catalog_expect( $condition, $message ) {
	global $assertions;
	++$assertions;
	if ( ! $condition ) { throw new RuntimeException( $message ); }
}
$counts = array( 'brick-by-numbers' => 8, 'lug-core' => 19, 'layout-designer' => 14, 'bricks-cubed' => 8, 'geobrick' => 9, 'lug-newsletter' => 10, 'socialposts' => 6, 'rotating-displays' => 6 );
catalog_expect( array_keys( $catalog['tools'] ) === array_keys( $counts ), 'The eight published training series must remain in order.' );
$total = 0;
foreach ( $counts as $tool => $count ) {
	$episodes = $catalog['tools'][ $tool ]['episodes'];
	catalog_expect( count( $episodes ) === $count, 'Incomplete training series: ' . $tool );
	catalog_expect( array_column( $episodes, 'number' ) === range( 1, $count ), 'Missing or reordered episodes: ' . $tool );
	foreach ( $episodes as $episode ) {
		catalog_expect( '' !== trim( $episode['title'] ) && '' !== trim( $episode['summary'] ), 'Missing episode metadata: ' . $tool );
		catalog_expect( ! empty( $episode['videos'] ), 'Missing public video: ' . $tool );
		foreach ( $episode['videos'] as $id ) { catalog_expect( 1 === preg_match( '/^[A-Za-z0-9_-]{11}$/D', $id ), 'Invalid YouTube ID: ' . $tool ); }
	}
	$total += count( $episodes );
}
catalog_expect( 80 === $total, 'The catalog must retain all 80 episodes.' );

// Freeze the seven unrelated catalog blocks from the reviewed 0.1.25 release.
$unchanged = array(
	'brick-by-numbers' => '4782eed205af51363b2f3c648d606002567c7f95bb836a3591b108c5a844f475',
	'layout-designer' => '44297e02f9407d947bdef67c5ab27dbf537820331e0375d928af14ed5f38e57f',
	'bricks-cubed' => 'ef9f9b99a938012e84efcb46566f8c2e75a696da275c39d64eaa688247fac0e8',
	'geobrick' => 'e8eb02b5f3dce4a3e2c9304e18fcf0f4e21e85bc053387d6f95afc28db37e373',
	'lug-newsletter' => '85be5a2217bfc7cda9d9a1ae19595f086ac9f66abeebaf84d2d5ca51b4ae5f3b',
	'socialposts' => 'b5c3c8f6fa1fe8747118253160b6e66c2edf761ccf219296462d05909c5f1cab',
	'rotating-displays' => 'e19871e306dec0dcf426423bdb4985399fa8c0e551e58f3b331aef5146db772e',
);
preg_match_all( "/^\t\t'([a-z0-9-]+)' => array\($/m", $source, $matches, PREG_OFFSET_CAPTURE );
$blocks = array();
foreach ( $matches[1] as $index => $match ) {
	$start = $matches[0][ $index ][1];
	$end = isset( $matches[0][ $index + 1 ] ) ? $matches[0][ $index + 1 ][1] : strrpos( $source, "\t),\n);" );
	$blocks[ $match[0] ] = substr( $source, $start, $end - $start );
}
foreach ( $unchanged as $tool => $hash ) { catalog_expect( hash( 'sha256', $blocks[ $tool ] ) === $hash, 'Unrelated training catalog changed: ' . $tool ); }
$core_prefix = strstr( $blocks['lug-core'], "\t\t\t\tarray(\n\t\t\t\t\t'number' => 16,", true );
catalog_expect( hash( 'sha256', $core_prefix ) === '3ef5eb7fc7f390caf8d8e03ceda9486187aacc4f8977a71723579593666d8354', 'The first fifteen LUG Core lessons must remain unchanged.' );
$queue_lesson = $catalog['tools']['lug-core']['episodes'][17];
catalog_expect( 'Collect Event MOC Cards and Sync Layouts' === $queue_lesson['title'] && 'WQnktyuTdFs' === $queue_lesson['videos']['Andrew'], 'The event-queue lesson must retain its accepted media.' );
$moc_lessons = array(
	16 => array( 'Create and Print MOC Cards', 'x4COV9Sof7c' ),
	17 => array( 'Set MOC Builder Names, Themes and Templates', 'xNyv6SXzgh4' ),
	19 => array( 'Set MOC Card Template Options for Your Club', 'R-t973vJkyA' ),
);
foreach ( $moc_lessons as $number => $expected ) {
	$episode = $catalog['tools']['lug-core']['episodes'][ $number - 1 ];
	catalog_expect( $episode['title'] === $expected[0] && $episode['videos']['Andrew'] === $expected[1], 'Missing reviewed replacement lesson: ' . $number );
}
$builder_summary = $catalog['tools']['lug-core']['episodes'][16]['summary'];
foreach ( array( 'default builder name', 'nickname or group credit', 'themes and reusable templates', 'public social links' ) as $topic ) {
	catalog_expect( false !== strpos( $builder_summary, $topic ), 'Builder-name lesson summary omits: ' . $topic );
}
echo $assertions . " training catalog assertions passed (eight series, 80 episodes)\n";
