<?php
/**
 * Plugin Name: Hostlinks Certificate Generator
 * Plugin URI:  https://digitalsolution.com
 * Description: Hostlinks add-on: generate completion certificates from event data with PDF download or email.
 * Version:     1.0.14
 * Author:      Digital Solution
 * License:     GPL2
 * Requires PHP: 8.0
 * Requires Plugins: hostlinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HLC_VERSION', '1.0.14' );
define( 'HLC_PLUGIN_FILE', __FILE__ );
define( 'HLC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HLC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

$hlc_autoload = HLC_PLUGIN_DIR . 'vendor/autoload.php';
if ( is_readable( $hlc_autoload ) ) {
	require_once $hlc_autoload;
}

require_once HLC_PLUGIN_DIR . 'includes/class-hlc-activator.php';
require_once HLC_PLUGIN_DIR . 'includes/class-hlc-access.php';
require_once HLC_PLUGIN_DIR . 'includes/class-hlc-certificate-data.php';
require_once HLC_PLUGIN_DIR . 'includes/class-hlc-bridge.php';
require_once HLC_PLUGIN_DIR . 'includes/class-hlc-pdf.php';
require_once HLC_PLUGIN_DIR . 'includes/class-hlc-rest.php';
require_once HLC_PLUGIN_DIR . 'includes/class-hlc-shortcode.php';
require_once HLC_PLUGIN_DIR . 'includes/class-hlc-admin.php';
require_once HLC_PLUGIN_DIR . 'includes/class-hlc-bootstrap.php';
require_once HLC_PLUGIN_DIR . 'includes/class-hlc-updater.php';

HLC_Updater::init( __FILE__, 'spkldbrd', 'hostlinks-certificate-generator' );

register_activation_hook( __FILE__, array( 'HLC_Activator', 'activate' ) );

add_action( 'plugins_loaded', array( 'HLC_Bootstrap', 'init' ) );
