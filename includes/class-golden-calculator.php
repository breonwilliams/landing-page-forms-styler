<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Golden Ratio Calculator for Landing Page Forms Styler
 * Calculates design values based on golden ratio mathematics
 */
class LPFS_Golden_Calculator {
    
    /**
     * Calculate font size based on base unit and scale
     * 
     * @param int $base_unit Base unit in pixels
     * @param string $scale Scale factor key
     * @return int Calculated font size
     */
    public static function calculate_font_size( $base_unit, $scale = '0' ) {
        $factor = LPFS_Constants::SCALE_FACTORS[$scale] ?? 1;
        return round( $base_unit * $factor );
    }
    
    /**
     * Calculate spacing based on base unit and density
     * 
     * @param int $base_unit Base unit in pixels
     * @param string $density Density key
     * @param string $type Type of spacing (padding, margin)
     * @return int Calculated spacing
     */
    public static function calculate_spacing( $base_unit, $density = 'normal', $type = 'padding' ) {
        $multiplier = LPFS_Constants::DENSITY_MULTIPLIERS[$density] ?? 1;
        
        // Different base ratios for different spacing types
        $base_ratios = [
            'padding' => 0.5,      // Half of base unit
            'margin' => 1,         // Full base unit
            'gap' => 0.618,        // Golden ratio of base
            'form_padding' => 2    // Double base unit
        ];
        
        $base_ratio = $base_ratios[$type] ?? 1;
        return round( $base_unit * $base_ratio * $multiplier );
    }
    
    /**
     * Calculate border width based on base unit and style
     * 
     * @param int $base_unit Base unit in pixels
     * @param string $style Border style key
     * @return int Border width in pixels
     */
    public static function calculate_border_width( $base_unit, $style = 'normal' ) {
        return LPFS_Constants::BORDER_STYLES[$style] ?? 1;
    }
    
    /**
     * Calculate border radius based on base unit and style
     * 
     * @param int $base_unit Base unit in pixels
     * @param string $style Radius style key
     * @return string Border radius value with unit
     */
    public static function calculate_border_radius( $base_unit, $style = 'rounded' ) {
        $multiplier = LPFS_Constants::RADIUS_STYLES[$style] ?? 0.382;
        
        // Special case for pill style
        if ( $style === 'pill' ) {
            return '50px';
        }
        
        return round( $base_unit * $multiplier ) . 'px';
    }
    
    /**
     * Calculate line height based on font size
     * 
     * @param int $font_size Font size in pixels
     * @return float Line height ratio
     */
    public static function calculate_line_height( $font_size ) {
        // Use golden ratio for optimal readability
        return round( 1.618, 2 );
    }
    
    /**
     * Calculate all values from settings
     * 
     * @param array $settings User settings
     * @return array Calculated values
     */
    public static function calculate_all_values( $settings ) {
        $base_unit = $settings['base_unit'] ?? 16;
        $design_density = $settings['design_density'] ?? 'normal';
        $spacing_density = $settings['spacing_density'] ?? 'normal';
        
        // Calculate font sizes
        $label_font_size = self::calculate_font_size( $base_unit, $settings['label_scale'] ?? '0' );
        $input_font_size = self::calculate_font_size( $base_unit, $settings['input_scale'] ?? '0' );
        $button_font_size = self::calculate_font_size( $base_unit, $settings['button_scale'] ?? '0' );
        
        // Calculate spacing
        $input_padding = self::calculate_spacing( $base_unit, $spacing_density, 'padding' );
        $button_padding_v = self::calculate_spacing( $base_unit, $spacing_density, 'padding' );
        $button_padding_h = round( $button_padding_v * 1.618 ); // Golden ratio horizontal padding
        $field_margin = self::calculate_spacing( $base_unit, $spacing_density, 'margin' );
        $form_padding = self::calculate_spacing( $base_unit, $spacing_density, 'form_padding' );
        
        // Calculate borders
        $border_width = self::calculate_border_width( $base_unit, $settings['border_style'] ?? 'normal' );
        $border_radius = self::calculate_border_radius( $base_unit, $settings['border_radius_style'] ?? 'rounded' );
        
        // Calculate line heights
        $button_line_height = self::calculate_line_height( $button_font_size );
        
        return [
            // Typography
            'label_font_size' => $label_font_size . 'px',
            'input_font_size' => $input_font_size . 'px',
            'button_font_size' => $button_font_size . 'px',
            'button_line_height' => $button_line_height,
            
            // Spacing
            'input_padding' => $input_padding . 'px',
            'button_padding' => $button_padding_v . 'px ' . $button_padding_h . 'px',
            'field_margin_bottom' => $field_margin . 'px',
            'form_padding' => $form_padding . 'px',
            'label_margin_bottom' => round( $base_unit * 0.382 ) . 'px', // Golden ratio small spacing
            
            // Borders
            'input_border_width' => $border_width . 'px',
            'button_border_width' => $border_width . 'px',
            'input_border_radius' => $border_radius,
            'button_border_radius' => $border_radius,
            
            // Focus states
            'focus_outline_width' => round( $border_width * 1.618 ) . 'px',
            'focus_shadow_spread' => round( $base_unit * 0.236 ) . 'px', // Golden ratio tiny spacing
            
            // Transitions
            'transition_duration' => LPFS_Constants::TRANSITION_SPEEDS[$settings['transition_speed'] ?? 'normal'],
            
            // Disabled states
            'disabled_opacity' => $settings['disabled_style'] === 'opacity' || $settings['disabled_style'] === 'both' ? '0.618' : '1',
            'disabled_filter' => $settings['disabled_style'] === 'grayscale' || $settings['disabled_style'] === 'both' ? 'grayscale(100%)' : 'none',
        ];
    }
    
    /**
     * Merge calculated values with user overrides
     * 
     * @param array $calculated Calculated values
     * @param array $overrides User overrides from advanced settings
     * @return array Merged values
     */
    public static function apply_overrides( $calculated, $overrides ) {
        // Map override keys to calculated keys
        $override_map = [
            'input_border_radius' => 'input_border_radius',
            'input_border_width' => 'input_border_width',
            'button_border_radius' => 'button_border_radius',
            'button_border_width' => 'button_border_width',
            'button_font_size' => 'button_font_size',
            'button_line_height' => 'button_line_height',
        ];
        
        foreach ( $override_map as $override_key => $calculated_key ) {
            if ( ! empty( $overrides[$override_key] ) ) {
                // Add units if needed
                if ( in_array( $override_key, ['input_border_radius', 'input_border_width', 'button_border_radius', 'button_border_width', 'button_font_size'] ) ) {
                    $calculated[$calculated_key] = $overrides[$override_key] . 'px';
                } else {
                    $calculated[$calculated_key] = $overrides[$override_key];
                }
            }
        }
        
        return $calculated;
    }
}