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
    
    // Golden Ratio
    const GOLDEN_RATIO = 1.618;
    
    // Golden Ratio Spacing (in pixels for consistency with existing code)
    const GOLDEN_SPACING = [
        'xs'   => 6,    // 16 / φ² ≈ 6
        'sm'   => 10,   // 16 / φ ≈ 10  
        'base' => 16,   // Base unit
        'md'   => 26,   // 16 × φ ≈ 26
        'lg'   => 42,   // 16 × φ² ≈ 42
        'xl'   => 68,   // 16 × φ³ ≈ 68
    ];
    
    // Control groups for collapsible sections
    const CONTROL_GROUPS = [
        'general' => [
            'title' => 'General Settings',
            'fields' => ['title', 'custom_class']
        ],
        'input_styles' => [
            'title' => 'Input Field Styles',
            'fields' => [
                'input_border_radius', 'input_border_width', 'input_border_color',
                'input_text_color', 'input_bg_color', 'input_focus_border_color',
                'input_font_family'
            ]
        ],
        'button_styles' => [
            'title' => 'Button Styles', 
            'fields' => [
                'button_border_radius', 'button_border_color', 'button_bg_color',
                'button_text_color', 'button_hover_bg_color', 'button_hover_text_color',
                'button_hover_border_color', 'button_font_size', 'button_font_weight',
                'button_line_height', 'button_font_family'
            ]
        ],
        'label_styles' => [
            'title' => 'Label Styles',
            'fields' => ['label_color', 'label_font_family']
        ]
    ];
    
    // Predesigned Templates
    const STYLE_TEMPLATES = [
        'modern_minimal' => [
            'name' => 'Modern Minimal',
            'description' => 'Clean and minimalist design with subtle shadows',
            'settings' => [
                'input_border_radius' => '8',
                'input_border_width' => '1',
                'input_border_color' => '#e1e8ed',
                'input_text_color' => '#2c3e50',
                'input_bg_color' => '#ffffff',
                'input_focus_border_color' => '#3498db',
                'input_font_family' => 'Inter',
                'label_color' => '#2c3e50',
                'label_font_family' => 'Inter',
                'button_border_radius' => '8',
                'button_border_width' => '1',
                'button_border_color' => '#3498db',
                'button_bg_color' => '#3498db',
                'button_text_color' => '#ffffff',
                'button_hover_bg_color' => '#2980b9',
                'button_hover_text_color' => '#ffffff',
                'button_hover_border_color' => '#2980b9',
                'button_font_size' => '16',
                'button_font_weight' => '600',
                'button_line_height' => '1.5',
                'button_font_family' => 'Inter'
            ]
        ],
        'elegant_dark' => [
            'name' => 'Elegant Dark',
            'description' => 'Sophisticated dark theme with gold accents',
            'settings' => [
                'input_border_radius' => '4',
                'input_border_width' => '1',
                'input_border_color' => '#444444',
                'input_text_color' => '#ffffff',
                'input_bg_color' => '#2a2a2a',
                'input_focus_border_color' => '#d4af37',
                'input_font_family' => 'Playfair Display',
                'label_color' => '#d4af37',
                'label_font_family' => 'Playfair Display',
                'button_border_radius' => '4',
                'button_border_color' => '#d4af37',
                'button_bg_color' => '#d4af37',
                'button_text_color' => '#1a1a1a',
                'button_hover_bg_color' => '#b8941f',
                'button_hover_text_color' => '#1a1a1a',
                'button_hover_border_color' => '#b8941f',
                'button_font_size' => '18',
                'button_font_weight' => '700',
                'button_line_height' => '1.4',
                'button_font_family' => 'Playfair Display'
            ]
        ],
        'academic_classic' => [
            'name' => 'Academic Classic',
            'description' => 'Traditional university style with serif fonts',
            'settings' => [
                'input_border_radius' => '2',
                'input_border_width' => '1',
                'input_border_color' => '#8b7355',
                'input_text_color' => '#333333',
                'input_bg_color' => '#fafafa',
                'input_focus_border_color' => '#5d4e37',
                'input_font_family' => 'Merriweather',
                'label_color' => '#5d4e37',
                'label_font_family' => 'Merriweather',
                'button_border_radius' => '2',
                'button_border_color' => '#8b7355',
                'button_bg_color' => '#8b7355',
                'button_text_color' => '#ffffff',
                'button_hover_bg_color' => '#5d4e37',
                'button_hover_text_color' => '#ffffff',
                'button_hover_border_color' => '#5d4e37',
                'button_font_size' => '16',
                'button_font_weight' => '600',
                'button_line_height' => '1.5',
                'button_font_family' => 'Merriweather'
            ]
        ],
        'corporate_blue' => [
            'name' => 'Corporate Blue',
            'description' => 'Professional and trustworthy design',
            'settings' => [
                'input_border_radius' => '3',
                'input_border_width' => '1',
                'input_border_color' => '#cfd8dc',
                'input_text_color' => '#263238',
                'input_bg_color' => '#f5f5f5',
                'input_focus_border_color' => '#1976d2',
                'input_font_family' => 'Roboto',
                'label_color' => '#455a64',
                'label_font_family' => 'Roboto',
                'button_border_radius' => '3',
                'button_border_color' => '#1976d2',
                'button_bg_color' => '#1976d2',
                'button_text_color' => '#ffffff',
                'button_hover_bg_color' => '#1565c0',
                'button_hover_text_color' => '#ffffff',
                'button_hover_border_color' => '#1565c0',
                'button_font_size' => '15',
                'button_font_weight' => '500',
                'button_line_height' => '1.5',
                'button_font_family' => 'Roboto'
            ]
        ],
        'university_crimson' => [
            'name' => 'University Crimson',
            'description' => 'Distinguished academic style with crimson accents',
            'settings' => [
                'input_border_radius' => '4',
                'input_border_width' => '1',
                'input_border_color' => '#cccccc',
                'input_text_color' => '#212529',
                'input_bg_color' => '#ffffff',
                'input_focus_border_color' => '#a51c30',
                'input_font_family' => 'Source Sans Pro',
                'label_color' => '#212529',
                'label_font_family' => 'Source Sans Pro',
                'button_border_radius' => '4',
                'button_border_color' => '#a51c30',
                'button_bg_color' => '#a51c30',
                'button_text_color' => '#ffffff',
                'button_hover_bg_color' => '#871729',
                'button_hover_text_color' => '#ffffff',
                'button_hover_border_color' => '#871729',
                'button_font_size' => '16',
                'button_font_weight' => '600',
                'button_line_height' => '1.5',
                'button_font_family' => 'Source Sans Pro'
            ]
        ],
        'ivy_league' => [
            'name' => 'Ivy League',
            'description' => 'Prestigious style with deep green and gold',
            'settings' => [
                'input_border_radius' => '3',
                'input_border_width' => '1',
                'input_border_color' => '#d4d4d4',
                'input_text_color' => '#1a472a',
                'input_bg_color' => '#ffffff',
                'input_focus_border_color' => '#1a472a',
                'input_font_family' => 'Roboto',
                'label_color' => '#1a472a',
                'label_font_family' => 'Playfair Display',
                'button_border_radius' => '3',
                'button_border_color' => '#1a472a',
                'button_bg_color' => '#1a472a',
                'button_text_color' => '#ffffff',
                'button_hover_bg_color' => '#0f2818',
                'button_hover_text_color' => '#ffffff',
                'button_hover_border_color' => '#0f2818',
                'button_font_size' => '16',
                'button_font_weight' => '500',
                'button_line_height' => '1.5',
                'button_font_family' => 'Roboto'
            ]
        ],
        'state_university' => [
            'name' => 'State University',
            'description' => 'Clean and accessible design for public institutions',
            'settings' => [
                'input_border_radius' => '4',
                'input_border_width' => '2',
                'input_border_color' => '#003366',
                'input_text_color' => '#333333',
                'input_bg_color' => '#f8f9fa',
                'input_focus_border_color' => '#0066cc',
                'input_font_family' => 'Open Sans',
                'label_color' => '#003366',
                'label_font_family' => 'Open Sans',
                'button_border_radius' => '4',
                'button_border_color' => '#003366',
                'button_bg_color' => '#003366',
                'button_text_color' => '#ffffff',
                'button_hover_bg_color' => '#002244',
                'button_hover_text_color' => '#ffffff',
                'button_hover_border_color' => '#002244',
                'button_font_size' => '16',
                'button_font_weight' => '600',
                'button_line_height' => '1.5',
                'button_font_family' => 'Open Sans'
            ]
        ],
        'stem_institute' => [
            'name' => 'STEM Institute',
            'description' => 'Modern tech-focused design for STEM programs',
            'settings' => [
                'input_border_radius' => '2',
                'input_border_width' => '1',
                'input_border_color' => '#e0e0e0',
                'input_text_color' => '#424242',
                'input_bg_color' => '#fafafa',
                'input_focus_border_color' => '#ff6f00',
                'input_font_family' => 'Roboto',
                'label_color' => '#424242',
                'label_font_family' => 'Roboto',
                'button_border_radius' => '2',
                'button_border_color' => '#ff6f00',
                'button_bg_color' => '#ff6f00',
                'button_text_color' => '#ffffff',
                'button_hover_bg_color' => '#e65100',
                'button_hover_text_color' => '#ffffff',
                'button_hover_border_color' => '#e65100',
                'button_font_size' => '15',
                'button_font_weight' => '500',
                'button_line_height' => '1.5',
                'button_font_family' => 'Roboto'
            ]
        ]
    ];
}