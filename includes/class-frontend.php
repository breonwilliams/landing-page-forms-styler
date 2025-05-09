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
        add_action( 'wp_head', [ $this, 'output_styles' ] );
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
            echo ".{$class} input, .{$class} select, .{$class} textarea, .{$class} button { width:100%; padding:0.5rem; margin-bottom:1rem; border:1px solid #ced4da; border-radius:0.375rem; font-size:1rem; }\n";
            echo ".{$class} input[type=\"radio\"], .{$class} input[type=\"checkbox\"] { width:auto; margin-right:0.5rem; }\n";
            echo ".{$class} input[type=\"checkbox\"], input[type=\"radio\"] { margin-bottom:0; }\n";
            echo ".{$class} .form-group { margin-bottom:1rem; }\n";
            echo ".{$class} .form-inline { display:flex; align-items:center; gap:1rem; flex-wrap:wrap; }\n";
            echo ".{$class} .form-check { display:flex; align-items:center; margin-bottom:0.5rem; }\n";
            echo ".{$class} .form-check input { margin-right:0.5rem; }\n";
            echo ".{$class} .form-actions { display:flex; gap:1rem; }\n";
            echo ".{$class} input[type=\"range\"] { width:100%; }\n";

            // User overrides
            if ( isset( $settings['input_border_radius'] ) ) {
                echo ".{$class} input, .{$class} textarea, .{$class} select { border-radius:{$settings['input_border_radius']}px; }\n";
            }
            if ( isset( $settings['input_border_width'] ) ) {
                echo ".{$class} input, .{$class} textarea, .{$class} select { border-width:{$settings['input_border_width']}px; }\n";
            }
            if ( isset( $settings['input_border_color'] ) ) {
                echo ".{$class} input, .{$class} textarea, .{$class} select { border-color:{$settings['input_border_color']}; }\n";
            }
            if ( isset( $settings['input_text_color'] ) ) {
                echo ".{$class} input, .{$class} textarea, .{$class} select { color:{$settings['input_text_color']}; }\n";
            }
            if ( isset( $settings['input_bg_color'] ) ) {
                echo ".{$class} input, .{$class} textarea, .{$class} select { background-color:{$settings['input_bg_color']}; }\n";
            }
            if ( isset( $settings['input_focus_border_color'] ) ) {
                echo ".{$class} input:focus, .{$class} textarea:focus, .{$class} select:focus { border-color:{$settings['input_focus_border_color']}; }\n";
            }
            if ( isset( $settings['label_color'] ) ) {
                echo ".{$class} label { color:{$settings['label_color']}; }\n";
            }
            if ( isset( $settings['button_border_radius'] ) ) {
                echo ".{$class} button { border-radius:{$settings['button_border_radius']}px; }\n";
            }
            if ( isset( $settings['button_bg_color'] ) ) {
                echo ".{$class} button { background-color:{$settings['button_bg_color']}; }\n";
            }
            if ( isset( $settings['button_border_color'] ) ) {
                echo ".{$class} button { border-color:{$settings['button_border_color']}; }\n";
            }
            if ( isset( $settings['button_text_color'] ) ) {
                echo ".{$class} button { color:{$settings['button_text_color']}; }\n";
            }
            if ( isset( $settings['button_hover_bg_color'] ) ) {
                echo ".{$class} button:hover { background-color:{$settings['button_hover_bg_color']}; }\n";
            }
            if ( isset( $settings['button_hover_text_color'] ) ) {
                echo ".{$class} button:hover { color:{$settings['button_hover_text_color']}; }\n";
            }
        }

        echo "</style>\n";
    }
}

// Initialize frontend
new LPFS_Frontend();
