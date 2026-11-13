<?php
/** A training-catalog release must not reseed an already populated demo. */
define( 'ABSPATH', __DIR__ );
$source = file_get_contents( dirname( __DIR__ ) . '/lug-demo.php' );
if ( ! preg_match( "/define\( 'LUG_DEMO_VERSION', '([^']+)' \)/", $source, $match ) ) { throw new RuntimeException( 'Missing release version.' ); }
define( 'LUG_DEMO_VERSION', $match[1] );
$options = array( 'lug_demo_event_ids' => array( 101, 102 ), 'lug_demo_seeded_version' => '0.1.22', 'lug_demo_last_report' => 'preserve' );
$writes = 0;
function get_option( $name, $default = false ) { global $options; return $options[ $name ] ?? $default; }
function update_option( $name, $value, $autoload = null ) { global $options, $writes; if ( 'lug_demo_seeded_version' !== $name ) { throw new RuntimeException( 'Existing demo data changed.' ); } $options[ $name ] = $value; ++$writes; }
function current_user_can( $cap ) { return true; }
function current_time( $type ) { throw new RuntimeException( 'Presentation release attempted to seed.' ); }
function apply_filters( $name, $value ) { throw new RuntimeException( 'Presentation release invoked adapters.' ); }
require dirname( __DIR__ ) . '/includes/class-lug-demo.php';
$demo = new LUG_Demo();
$demo->maybe_seed_after_activation();
if ( LUG_DEMO_VERSION !== $options['lug_demo_seeded_version'] || array( 101, 102 ) !== $options['lug_demo_event_ids'] || 'preserve' !== $options['lug_demo_last_report'] || 1 !== $writes ) { throw new RuntimeException( 'Training upgrade did not preserve existing content.' ); }
$demo->maybe_seed_after_activation();
if ( 1 !== $writes ) { throw new RuntimeException( 'Training upgrade was not idempotent.' ); }
echo "2 training upgrade safety assertions passed\n";
