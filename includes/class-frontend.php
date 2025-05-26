<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Frontend functionality for Landing Page Forms Styler
 */
class LPFS_Frontend {
    const OPTION_KEY = 'lp_forms_styles';

    public function __construct() {
        add_action( 'wp_head', [ $this, 'load_google_fonts' ] );
        add_action( 'wp_head', [ $this, 'output_styles' ] );
    }

    /**
     * Load Google Fonts used in presets
     */
    public function load_google_fonts() {
        $presets = get_option( self::OPTION_KEY, [] );
        if ( empty( $presets ) ) {
            return;
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
                    
                    // Add font weights if button font
                    if ( $field === 'button_font_family' && !empty( $settings['button_font_weight'] ) ) {
                        $fonts_to_load[$font_name][] = $settings['button_font_weight'];
                    } else {
                        // Add common weights
                        $fonts_to_load[$font_name] = array_merge( $fonts_to_load[$font_name], ['400', '500', '600', '700'] );
                    }
                }
            }
        }

        if ( !empty( $fonts_to_load ) ) {
            $font_families = [];
            foreach ( $fonts_to_load as $font_name => $weights ) {
                $weights = array_unique( $weights );
                sort( $weights );
                $font_families[] = str_replace( ' ', '+', $font_name ) . ':wght@' . implode( ';', $weights );
            }
            
            $google_fonts_url = 'https://fonts.googleapis.com/css2?family=' . implode( '&family=', $font_families ) . '&display=swap';
            echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
            echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
            echo '<link href="' . esc_url( $google_fonts_url ) . '" rel="stylesheet">' . "\n";
        }
    }

    /**
     * Output scoped CSS for each form style preset
     */
    public function output_styles() {
        $presets = get_option( self::OPTION_KEY, [] );
        if ( empty( $presets ) ) {
            return;
        }

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
            
            // Font family styles
            if (isset($settings['input_font_family']) && !empty($settings['input_font_family'])) {
                echo ".{$class} input, .{$class} textarea, .{$class} select { font-family: '{$settings['input_font_family']}', sans-serif !important; }\n";
            }
            if (isset($settings['label_font_family']) && !empty($settings['label_font_family'])) {
                echo ".{$class} label { font-family: '{$settings['label_font_family']}', sans-serif !important; }\n";
            }
            if (isset($settings['button_font_family']) && !empty($settings['button_font_family'])) {
                echo ".{$class} button { font-family: '{$settings['button_font_family']}', sans-serif !important; }\n";
            }
        }

        echo "</style>\n";
    }
}

// Initialize frontend
new LPFS_Frontend();
