<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constants for Landing Page Forms Styler
 * Centralized location for all plugin constants
 */
class LPFS_Constants {
    
    // Plugin info
    const PLUGIN_VERSION = '1.0.0';
    const TEXT_DOMAIN = 'landing-page-forms-styler';
    
    // Database keys
    const OPTION_KEY = 'lp_forms_styles';
    const CSS_FILE_KEY = 'lpfs_css_file';
    const ERROR_LOGS_KEY = 'lpfs_error_logs';
    const LOG_SETTINGS_KEY = 'lpfs_log_settings';
    
    // Cache keys
    const STYLES_CACHE_KEY = 'lpfs_cached_styles';
    const FONTS_CACHE_KEY = 'lpfs_google_fonts';
    
    // Cache expiration times
    const CACHE_EXPIRATION = 86400; // 24 hours
    const FONTS_CACHE_EXPIRATION = 86400; // 24 hours
    
    // Admin menu
    const MENU_POSITION = 60;
    const MENU_CAPABILITY = 'manage_options';
    const MENU_SLUG = 'lpfs-styles';
    
    // CSS generation
    const CSS_FILENAME = 'lpfs-styles.css';
    const CSS_DIR_NAME = 'lpfs-css';
    
    // Field validation limits
    const BORDER_RADIUS_MIN = 0;
    const BORDER_RADIUS_MAX = 100;
    const BORDER_WIDTH_MIN = 0;
    const BORDER_WIDTH_MAX = 20;
    const FONT_SIZE_MIN = 8;
    const FONT_SIZE_MAX = 72;
    const LINE_HEIGHT_MIN = 0.5;
    const LINE_HEIGHT_MAX = 3.0;
    
    // Default styles
    const DEFAULT_LABEL_MARGIN = '0.25rem';
    const DEFAULT_FIELD_PADDING = '0.5rem';
    const DEFAULT_FIELD_MARGIN = '1rem';
    const DEFAULT_BORDER_COLOR = '#ced4da';
    const DEFAULT_BORDER_RADIUS = '0.375rem';
    const DEFAULT_FONT_SIZE = '1rem';
    const DEFAULT_FONT_WEIGHT = '500';
    const DEFAULT_FORM_PADDING = '2rem';
    const DEFAULT_FORM_RADIUS = '0.5rem';
    const DEFAULT_BUTTON_BG = '#0073aa';
    const DEFAULT_BUTTON_COLOR = '#fff';
    const DEFAULT_BUTTON_HOVER_BG = '#005177';
    const DEFAULT_FOCUS_COLOR = '#80bdff';
    
    // Logger settings
    const MAX_LOG_ENTRIES = 100;
    
    // Font weights
    const FONT_WEIGHTS = [
        '100' => 'Thin',
        '200' => 'Extra Light',
        '300' => 'Light',
        '400' => 'Normal',
        '500' => 'Medium',
        '600' => 'Semi Bold',
        '700' => 'Bold',
        '800' => 'Extra Bold',
        '900' => 'Black'
    ];
    
    // Color keywords
    const VALID_COLOR_KEYWORDS = [
        'transparent',
        'inherit',
        'initial',
        'unset',
        'currentcolor'
    ];
}