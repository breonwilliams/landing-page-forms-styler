<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Frontend functionality for Landing Page Forms Styler
 */
class LPFS_Frontend {

    public function __construct() {
        add_action( 'wp_head', [ $this, 'load_google_fonts' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );
    }

    /**
     * Load Google Fonts used in presets (optimized)
     */
    public function load_google_fonts() {
        // Check if we should load fonts
        if ( ! $this->should_load_fonts() ) {
            return;
        }
        
        // Try to get cached font data
        $font_data = get_transient( LPFS_Constants::FONTS_CACHE_KEY );
        
        if ( $font_data === false ) {
            $font_data = $this->generate_font_data();
            // Cache for 24 hours
            set_transient( LPFS_Constants::FONTS_CACHE_KEY, $font_data, DAY_IN_SECONDS );
        }
        
        if ( ! empty( $font_data ) ) {
            // Add preconnect hints for performance
            echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
            echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
            
            // Load fonts with display=swap for better performance
            echo '<link href="' . esc_url( $font_data ) . '" rel="stylesheet">' . "\n";
        }
    }
    
    /**
     * Check if we should load Google Fonts on this page
     * 
     * @return bool
     */
    private function should_load_fonts() {
        // Allow filtering to disable font loading on specific pages
        $should_load = apply_filters( 'lpfs_load_google_fonts', true );
        
        if ( ! $should_load ) {
            return false;
        }
        
        // Check if any presets exist
        $presets = get_option( LPFS_Constants::OPTION_KEY, [] );
        return ! empty( $presets );
    }
    
    /**
     * Generate Google Fonts URL from presets
     * 
     * @return string|false
     */
    private function generate_font_data() {
        $presets = get_option( LPFS_Constants::OPTION_KEY, [] );
        if ( empty( $presets ) ) {
            return false;
        }

        $fonts_to_load = [];
        
        foreach ( $presets as $preset ) {
            $settings = $preset['settings'] ?? [];
            
            // Collect all font families used
            $font_fields = ['input_font_family', 'label_font_family', 'button_font_family'];
            foreach ( $font_fields as $field ) {
                if ( !empty( $settings[$field] ) ) {
                    $font_name = $settings[$field];
                    if ( !isset( $fonts_to_load[$font_name] ) ) {
                        $fonts_to_load[$font_name] = [];
                    }
                    
                    // Add specific weight for button fonts
                    if ( $field === 'button_font_family' && !empty( $settings['button_font_weight'] ) ) {
                        $fonts_to_load[$font_name][] = $settings['button_font_weight'];
                    }
                    
                    // Add default weights needed
                    $fonts_to_load[$font_name][] = '400'; // Normal
                    $fonts_to_load[$font_name][] = '500'; // Medium (for labels)
                    
                    // Add bold if needed
                    if ( $field === 'button_font_family' ) {
                        $fonts_to_load[$font_name][] = '600';
                        $fonts_to_load[$font_name][] = '700';
                    }
                }
            }
        }

        if ( empty( $fonts_to_load ) ) {
            return false;
        }
        
        $font_families = [];
        foreach ( $fonts_to_load as $font_name => $weights ) {
            // Remove duplicates and sort
            $weights = array_unique( $weights );
            sort( $weights );
            
            // Only load weights that are actually needed
            $weights = array_filter( $weights, function( $weight ) {
                return in_array( $weight, ['100', '200', '300', '400', '500', '600', '700', '800', '900'] );
            });
            
            if ( ! empty( $weights ) ) {
                $font_families[] = str_replace( ' ', '+', $font_name ) . ':wght@' . implode( ';', $weights );
            }
        }
        
        if ( empty( $font_families ) ) {
            return false;
        }
        
        return 'https://fonts.googleapis.com/css2?family=' . implode( '&family=', $font_families ) . '&display=swap';
    }

    /**
     * Enqueue styles - use static CSS file if available, otherwise inline
     */
    public function enqueue_styles() {
        $css_file = get_option( LPFS_Constants::CSS_FILE_KEY );
        
        if ( $css_file && isset( $css_file['url'], $css_file['version'] ) ) {
            // Enqueue static CSS file
            wp_enqueue_style( 
                'lpfs-form-styles', 
                $css_file['url'], 
                [], 
                $css_file['version'] 
            );
        } else {
            // Fall back to inline styles
            add_action( 'wp_head', [ $this, 'output_styles' ] );
        }
    }

    /**
     * Output scoped CSS for each form style preset (fallback method)
     */
    public function output_styles() {
        // Try to get cached styles first
        $cached_styles = get_transient( LPFS_Constants::STYLES_CACHE_KEY );
        
        if ( $cached_styles !== false ) {
            echo $cached_styles;
            return;
        }
        
        // Generate styles if not cached
        $presets = get_option( LPFS_Constants::OPTION_KEY, [] );
        if ( empty( $presets ) ) {
            return;
        }

        // Start buffering output
        ob_start();
        echo "<style type=\"text/css\">\n";

        foreach ( $presets as $preset ) {
            $class    = sanitize_html_class( $preset['custom_class'] );
            $settings = $preset['settings'] ?? [];

            // Default base styles
            echo ".{$class} label { display:block; margin-bottom:0.25rem; font-weight:500; }\n";
            echo ".{$class} input, .{$class} select, .{$class} textarea, .{$class} button { width:100%; padding:0.5rem; margin-bottom:1rem; border:1px solid #ced4da; border-radius:0.375rem; font-size:1rem; height:auto !important; }\n";
            echo ".{$class} input[type=\"radio\"], .{$class} input[type=\"checkbox\"] { width:auto; margin-right:0.5rem; }\n";
            echo ".{$class} input[type=\"checkbox\"], input[type=\"radio\"] { margin-bottom:0; }\n";
            echo ".{$class} .form-group { margin-bottom:1rem; }\n";
            echo ".{$class} .form-inline { display:flex; align-items:center; gap:1rem; flex-wrap:wrap; }\n";
            echo ".{$class} .form-check { display:flex; align-items:center; margin-bottom:0.5rem; }\n";
            echo ".{$class} .form-check input { margin-right:0.5rem; }\n";
            echo ".{$class} .form-actions { display:flex; gap:1rem; }\n";
            echo ".{$class} input[type=\"range\"] { width:100%; }\n";

                        // User overrides (with !important)
            if (isset($settings['input_border_radius'])) {
                echo ".{$class} input, .{$class} textarea, .{$class} select { border-radius: {$settings['input_border_radius']}px !important; }\n";
            }
            if (isset($settings['input_border_width'])) {
                echo ".{$class} input, .{$class} textarea, .{$class} select { border-width: {$settings['input_border_width']}px !important; }\n";
            }
            if (isset($settings['input_border_color'])) {
                echo ".{$class} input, .{$class} textarea, .{$class} select { border-color: {$settings['input_border_color']} !important; }\n";
            }
            if (isset($settings['input_text_color'])) {
                echo ".{$class} input, .{$class} textarea, .{$class} select { color: {$settings['input_text_color']} !important; }\n";
            }
            if (isset($settings['input_bg_color'])) {
                echo ".{$class} input, .{$class} textarea, .{$class} select { background-color: {$settings['input_bg_color']} !important; }\n";
            }
            if (isset($settings['input_focus_border_color'])) {
                echo ".{$class} input:focus, .{$class} textarea:focus, .{$class} select:focus { border-color: {$settings['input_focus_border_color']} !important; }\n";
            }
            if (isset($settings['label_color'])) {
                echo ".{$class} label { color: {$settings['label_color']} !important; }\n";
            }
            if (isset($settings['button_border_radius'])) {
                echo ".{$class} button { border-radius: {$settings['button_border_radius']}px !important; }\n";
            }
            if (isset($settings['button_bg_color'])) {
                echo ".{$class} button { background-color: {$settings['button_bg_color']} !important; }\n";
            }
            if (isset($settings['button_border_color'])) {
                echo ".{$class} button { border-color: {$settings['button_border_color']} !important; }\n";
            }
            if (isset($settings['button_text_color'])) {
                echo ".{$class} button { color: {$settings['button_text_color']} !important; }\n";
            }
            if (isset($settings['button_hover_bg_color'])) {
                echo ".{$class} button:hover { background-color: {$settings['button_hover_bg_color']} !important; }\n";
            }
            if (isset($settings['button_hover_text_color'])) {
                echo ".{$class} button:hover { color: {$settings['button_hover_text_color']} !important; }\n";
            }
            if (isset($settings['button_hover_border_color'])) {
                echo ".{$class} button:hover { border-color: {$settings['button_hover_border_color']} !important; }\n";
            }
            if (isset($settings['button_font_size'])) {
                echo ".{$class} button { font-size: {$settings['button_font_size']}px !important; }\n";
            }
            if (isset($settings['button_font_weight'])) {
                echo ".{$class} button { font-weight: {$settings['button_font_weight']} !important; }\n";
            }
            if (isset($settings['button_line_height'])) {
                echo ".{$class} button { line-height: {$settings['button_line_height']} !important; }\n";
            }
            
            // Font family styles with proper CSS escaping
            if (isset($settings['input_font_family']) && !empty($settings['input_font_family'])) {
                $escaped_font = $this->escape_css_font_family($settings['input_font_family']);
                echo ".{$class} input, .{$class} textarea, .{$class} select { font-family: {$escaped_font}, sans-serif !important; }\n";
            }
            if (isset($settings['label_font_family']) && !empty($settings['label_font_family'])) {
                $escaped_font = $this->escape_css_font_family($settings['label_font_family']);
                echo ".{$class} label { font-family: {$escaped_font}, sans-serif !important; }\n";
            }
            if (isset($settings['button_font_family']) && !empty($settings['button_font_family'])) {
                $escaped_font = $this->escape_css_font_family($settings['button_font_family']);
                echo ".{$class} button { font-family: {$escaped_font}, sans-serif !important; }\n";
            }
        }

        echo "</style>\n";
        
        // Get the buffered content
        $styles = ob_get_clean();
        
        // Cache the generated styles
        set_transient( LPFS_Constants::STYLES_CACHE_KEY, $styles, LPFS_Constants::CACHE_EXPIRATION );
        
        // Output the styles
        echo $styles;
    }

    /**
     * Escape font family names for safe CSS output
     * 
     * @param string $font_family The font family to escape
     * @return string The escaped font family
     */
    private function escape_css_font_family($font_family) {
        // Remove any existing quotes
        $font_family = str_replace(array('"', "'"), '', $font_family);
        
        // Escape special CSS characters
        $font_family = preg_replace('/[^a-zA-Z0-9\s\-]/', '', $font_family);
        
        // If font family contains spaces or starts with digit, wrap in quotes
        if (preg_match('/\s/', $font_family) || preg_match('/^\d/', $font_family)) {
            return '"' . $font_family . '"';
        }
        
        return $font_family;
    }
}

// Initialize frontend
new LPFS_Frontend();
