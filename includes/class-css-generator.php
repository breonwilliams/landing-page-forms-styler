<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * CSS Generator for Landing Page Forms Styler
 * Generates static CSS files from presets
 */
class LPFS_CSS_Generator {
    
    /**
     * Get the upload directory for CSS files
     * 
     * @return array Upload directory info
     */
    public function get_css_directory() {
        $upload_dir = wp_upload_dir();
        $css_dir = $upload_dir['basedir'] . '/' . LPFS_Constants::CSS_DIR_NAME;
        $css_url = $upload_dir['baseurl'] . '/' . LPFS_Constants::CSS_DIR_NAME;
        
        return [
            'path' => $css_dir,
            'url' => $css_url
        ];
    }
    
    /**
     * Ensure the CSS directory exists
     * 
     * @return bool True if directory exists or was created
     */
    private function ensure_directory() {
        $dir_info = $this->get_css_directory();
        $css_dir = $dir_info['path'];
        
        if ( ! file_exists( $css_dir ) ) {
            $result = wp_mkdir_p( $css_dir );
            if ( ! $result ) {
                LPFS_Logger::error( 'Failed to create CSS directory', [
                    'directory' => $css_dir
                ] );
            }
            return $result;
        }
        
        return true;
    }
    
    /**
     * Generate CSS content from presets
     * 
     * @return string Generated CSS content
     */
    private function generate_css_content() {
        $presets = get_option( LPFS_Constants::OPTION_KEY, [] );
        if ( empty( $presets ) ) {
            return '';
        }
        
        $css = '';
        
        foreach ( $presets as $preset ) {
            $class = sanitize_html_class( $preset['custom_class'] );
            $settings = $preset['settings'] ?? [];
            
            // Default base styles
            $css .= ".{$class} label { display:block; margin-bottom:0.25rem; font-weight:500; }\n";
            $css .= ".{$class} input, .{$class} select, .{$class} textarea, .{$class} button { width:100%; padding:0.5rem; margin-bottom:1rem; border:1px solid #ced4da; border-radius:0.375rem; font-size:1rem; height:auto !important; }\n";
            $css .= ".{$class} input[type=\"radio\"], .{$class} input[type=\"checkbox\"] { width:auto; margin-right:0.5rem; }\n";
            $css .= ".{$class} input[type=\"checkbox\"], input[type=\"radio\"] { margin-bottom:0; }\n";
            $css .= ".{$class} .form-group { margin-bottom:1rem; }\n";
            $css .= ".{$class} .form-inline { display:flex; align-items:center; gap:1rem; flex-wrap:wrap; }\n";
            $css .= ".{$class} .form-check { display:flex; align-items:center; margin-bottom:0.5rem; }\n";
            $css .= ".{$class} .form-check input { margin-right:0.5rem; }\n";
            $css .= ".{$class} .form-actions { display:flex; gap:1rem; }\n";
            $css .= ".{$class} input[type=\"range\"] { width:100%; }\n";
            
            // User overrides (with !important)
            if (isset($settings['input_border_radius'])) {
                $css .= ".{$class} input, .{$class} textarea, .{$class} select { border-radius: {$settings['input_border_radius']}px !important; }\n";
            }
            if (isset($settings['input_border_width'])) {
                $css .= ".{$class} input, .{$class} textarea, .{$class} select { border-width: {$settings['input_border_width']}px !important; }\n";
            }
            if (isset($settings['input_border_color'])) {
                $css .= ".{$class} input, .{$class} textarea, .{$class} select { border-color: {$settings['input_border_color']} !important; }\n";
            }
            if (isset($settings['input_text_color'])) {
                $css .= ".{$class} input, .{$class} textarea, .{$class} select { color: {$settings['input_text_color']} !important; }\n";
            }
            if (isset($settings['input_bg_color'])) {
                $css .= ".{$class} input, .{$class} textarea, .{$class} select { background-color: {$settings['input_bg_color']} !important; }\n";
            }
            if (isset($settings['input_focus_border_color'])) {
                $css .= ".{$class} input:focus, .{$class} textarea:focus, .{$class} select:focus { border-color: {$settings['input_focus_border_color']} !important; }\n";
            }
            if (isset($settings['label_color'])) {
                $css .= ".{$class} label { color: {$settings['label_color']} !important; }\n";
            }
            if (isset($settings['button_border_radius'])) {
                $css .= ".{$class} button { border-radius: {$settings['button_border_radius']}px !important; }\n";
            }
            if (isset($settings['button_bg_color'])) {
                $css .= ".{$class} button { background-color: {$settings['button_bg_color']} !important; }\n";
            }
            if (isset($settings['button_border_color'])) {
                $css .= ".{$class} button { border-color: {$settings['button_border_color']} !important; }\n";
            }
            if (isset($settings['button_text_color'])) {
                $css .= ".{$class} button { color: {$settings['button_text_color']} !important; }\n";
            }
            if (isset($settings['button_hover_bg_color'])) {
                $css .= ".{$class} button:hover { background-color: {$settings['button_hover_bg_color']} !important; }\n";
            }
            if (isset($settings['button_hover_text_color'])) {
                $css .= ".{$class} button:hover { color: {$settings['button_hover_text_color']} !important; }\n";
            }
            if (isset($settings['button_hover_border_color'])) {
                $css .= ".{$class} button:hover { border-color: {$settings['button_hover_border_color']} !important; }\n";
            }
            if (isset($settings['button_font_size'])) {
                $css .= ".{$class} button { font-size: {$settings['button_font_size']}px !important; }\n";
            }
            if (isset($settings['button_font_weight'])) {
                $css .= ".{$class} button { font-weight: {$settings['button_font_weight']} !important; }\n";
            }
            if (isset($settings['button_line_height'])) {
                $css .= ".{$class} button { line-height: {$settings['button_line_height']} !important; }\n";
            }
            
            // Font family styles with proper CSS escaping
            if (isset($settings['input_font_family']) && !empty($settings['input_font_family'])) {
                $escaped_font = $this->escape_css_font_family($settings['input_font_family']);
                $css .= ".{$class} input, .{$class} textarea, .{$class} select { font-family: {$escaped_font}, sans-serif !important; }\n";
            }
            if (isset($settings['label_font_family']) && !empty($settings['label_font_family'])) {
                $escaped_font = $this->escape_css_font_family($settings['label_font_family']);
                $css .= ".{$class} label { font-family: {$escaped_font}, sans-serif !important; }\n";
            }
            if (isset($settings['button_font_family']) && !empty($settings['button_font_family'])) {
                $escaped_font = $this->escape_css_font_family($settings['button_font_family']);
                $css .= ".{$class} button { font-family: {$escaped_font}, sans-serif !important; }\n";
            }
            
            $css .= "\n";
        }
        
        return $css;
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
    
    /**
     * Generate and save CSS file
     * 
     * @return bool|string False on failure, CSS file URL on success
     */
    public function generate_css_file() {
        if ( ! $this->ensure_directory() ) {
            return false;
        }
        
        $dir_info = $this->get_css_directory();
        $css_file = $dir_info['path'] . '/' . LPFS_Constants::CSS_FILENAME;
        $css_url = $dir_info['url'] . '/' . LPFS_Constants::CSS_FILENAME;
        
        $css_content = $this->generate_css_content();
        
        // Add timestamp comment for cache busting
        $css_content = "/* Generated by Landing Page Forms Styler on " . current_time('mysql') . " */\n\n" . $css_content;
        
        // Write CSS file
        global $wp_filesystem;
        if ( empty( $wp_filesystem ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }
        
        if ( ! $wp_filesystem->put_contents( $css_file, $css_content, FS_CHMOD_FILE ) ) {
            LPFS_Logger::error( 'Failed to write CSS file', [
                'file' => $css_file,
                'content_length' => strlen( $css_content )
            ] );
            return false;
        }
        
        LPFS_Logger::info( 'CSS file generated successfully', [
            'file' => $css_file,
            'size' => strlen( $css_content )
        ] );
        
        // Store file info in option
        update_option( LPFS_Constants::CSS_FILE_KEY, [
            'url' => $css_url,
            'version' => time()
        ] );
        
        return $css_url;
    }
    
    /**
     * Delete CSS file
     * 
     * @return bool True on success
     */
    public function delete_css_file() {
        $dir_info = $this->get_css_directory();
        $css_file = $dir_info['path'] . '/' . LPFS_Constants::CSS_FILENAME;
        
        if ( file_exists( $css_file ) ) {
            global $wp_filesystem;
            if ( empty( $wp_filesystem ) ) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
                WP_Filesystem();
            }
            
            $wp_filesystem->delete( $css_file );
        }
        
        delete_option( LPFS_Constants::CSS_FILE_KEY );
        
        return true;
    }
}