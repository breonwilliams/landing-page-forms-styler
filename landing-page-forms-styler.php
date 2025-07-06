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
require_once LPFS_PLUGIN_DIR . 'includes/class-constants.php';
require_once LPFS_PLUGIN_DIR . 'includes/class-logger.php';
require_once LPFS_PLUGIN_DIR . 'includes/class-admin.php';
require_once LPFS_PLUGIN_DIR . 'includes/class-frontend.php';
require_once LPFS_PLUGIN_DIR . 'includes/class-css-generator.php';

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

/**
 * Plugin activation hook
 * Regenerate CSS file on activation
 */
function lpfs_activate_plugin() {
    // Clear any cached styles
    delete_transient( LPFS_Constants::STYLES_CACHE_KEY );
    delete_transient( LPFS_Constants::FONTS_CACHE_KEY );
    
    // Generate CSS file if presets exist
    $presets = get_option( LPFS_Constants::OPTION_KEY, [] );
    if ( ! empty( $presets ) ) {
        $css_generator = new LPFS_CSS_Generator();
        $css_generator->generate_css_file();
    }
}
register_activation_hook( __FILE__, 'lpfs_activate_plugin' );
