<?php
/*
Plugin Name: Landing Page Forms Styler
Description: Create and apply re-usable form style presets via CSS classes.
Version:     1.0.0
Author:      Breon Williams
Author URI:  https://breonwilliams.com
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: landing-page-forms-styler
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Plugin constants
if ( ! defined( 'LPFS_PLUGIN_DIR' ) ) {
    define( 'LPFS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'LPFS_PLUGIN_URL' ) ) {
    define( 'LPFS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Include core classes
require_once LPFS_PLUGIN_DIR . 'includes/class-admin.php';
require_once LPFS_PLUGIN_DIR . 'includes/class-frontend.php';

/**
 * Initialize the plugin
 */
function lpfs_init_plugin() {
    // Admin functionality
    if ( is_admin() && class_exists( 'LPFS_Admin' ) ) {
        new LPFS_Admin();
    }

    // Frontend functionality
    if ( class_exists( 'LPFS_Frontend' ) ) {
        new LPFS_Frontend();
    }
}
add_action( 'plugins_loaded', 'lpfs_init_plugin' );
