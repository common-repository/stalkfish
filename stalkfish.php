<?php
/**
 * Plugin Name: Stalkfish
 * Plugin URI: https://stalkfish.com
 * Description: Stalkfish actively tracks error, crashes, and activity log on your WordPress site and sends them to your Stalkfish dashboard.
 * Version: 1.2.1
 * Requires at least: 5.6
 * Requires PHP: 7.1
 * Author: Ram Ratan Maurya
 * Author URI: https://twitter.com/mauryaratan
 * License: GPLv2+
 * Text Domain: stalkfish
 * Domain Path: /languages
 *
 * @package Stalkfish
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'STALKFISH_VERSION', '1.2.1' );
define( 'STALKFISH_PLUGIN_FILE', __FILE__ );
define( 'STALKFISH_PLUGIN_DIR_PATH', plugin_dir_path( STALKFISH_PLUGIN_FILE ) );
define( 'STALKFISH_PLUGIN_DIR_URL', plugin_dir_url( STALKFISH_PLUGIN_FILE ) );
define( 'STALKFISH_ASSETS_URL', STALKFISH_PLUGIN_DIR_URL . 'assets' );
define( 'STALKFISH_MINIMUM_PHP_VERSION', '7.1' );

if ( ! defined( 'STALKFISH_APP_URL' ) ) {
	define( 'STALKFISH_APP_URL', 'https://app.stalkfish.com' );
}

// Third party dependencies.
$vendor_file = __DIR__ . '/third-party/vendor/scoper-autoload.php';

if ( is_readable( $vendor_file ) ) {
	require_once $vendor_file;
}

// Auto-loads classes.
require_once dirname( __FILE__ ) . '/autoloader.php';
$autoloader = new \Stalkfish\Autoloader();
$autoloader->add_namespace( '\Stalkfish', dirname( STALKFISH_PLUGIN_FILE ) . '/includes/' );
$autoloader->register();

// Main plugin activation happens there so that this file is still parsable in PHP < 7.0.
require __DIR__ . '/includes/activator.php';
