<?php
/**
 * Plugin Name: LUG Demo
 * Description: Repeatable demo environment for lugtools.steelcitylug.com.
 * Version: 0.1.1
 * Author: Steel City LUG
 * License: GPL-2.0-or-later
 * Text Domain: lug-demo
 *
 * @package LUG_Demo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LUG_DEMO_VERSION', '0.1.1' );
define( 'LUG_DEMO_FILE', __FILE__ );
define( 'LUG_DEMO_DIR', plugin_dir_path( __FILE__ ) );

require_once LUG_DEMO_DIR . 'includes/class-lug-demo.php';
require_once LUG_DEMO_DIR . 'includes/class-lug-demo-admin.php';

add_action(
	'plugins_loaded',
	static function () {
		$plugin = new LUG_Demo();
		$plugin->hooks();
	}
);
