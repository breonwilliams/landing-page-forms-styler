<?php
/**
 * Uninstall Landing Page Forms Styler
 * 
 * This file is executed when the plugin is uninstalled.
 * It removes all plugin data from the database and file system.
 * 
 * @package Landing_Page_Forms_Styler
 */

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Define plugin constants if not already defined
if ( ! defined( 'LPFS_PLUGIN_DIR' ) ) {
    define( 'LPFS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

// Include necessary classes
require_once LPFS_PLUGIN_DIR . 'includes/class-constants.php';
require_once LPFS_PLUGIN_DIR . 'includes/class-logger.php';
require_once LPFS_PLUGIN_DIR . 'includes/class-css-generator.php';

/**
 * Remove all plugin data
 */
function lpfs_uninstall_plugin() {
    // Log uninstall action
    LPFS_Logger::info( 'Plugin uninstallation started' );
    
    // 1. Remove plugin options
    $options_to_remove = [
        LPFS_Constants::OPTION_KEY,           // Main presets data
        LPFS_Constants::CSS_FILE_KEY,         // CSS file info
        LPFS_Constants::ERROR_LOGS_KEY,       // Error logs
        LPFS_Constants::LOG_SETTINGS_KEY,     // Logger settings
    ];
    
    foreach ( $options_to_remove as $option ) {
        delete_option( $option );
    }
    
    // 2. Remove transients
    delete_transient( LPFS_Constants::STYLES_CACHE_KEY );
    delete_transient( LPFS_Constants::FONTS_CACHE_KEY );
    
    // 3. Remove generated CSS files
    $css_generator = new LPFS_CSS_Generator();
    $css_generator->delete_css_file();
    
    // Also remove the CSS directory if empty
    $upload_dir = wp_upload_dir();
    $css_dir = $upload_dir['basedir'] . '/' . LPFS_Constants::CSS_DIR_NAME;
    if ( is_dir( $css_dir ) ) {
        // Check if directory is empty
        $files = scandir( $css_dir );
        $files = array_diff( $files, ['.', '..'] );
        if ( empty( $files ) ) {
            rmdir( $css_dir );
        }
    }
    
    // 4. Remove any user meta if added in the future
    // Currently no user meta is stored
    
    // 5. Clear any scheduled events if added in the future
    // Currently no scheduled events
    
    // Final log entry (this will be removed with the logs)
    LPFS_Logger::info( 'Plugin uninstallation completed' );
}

// Run uninstall
lpfs_uninstall_plugin();