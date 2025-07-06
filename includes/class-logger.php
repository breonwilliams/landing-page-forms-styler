<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Logger class for Landing Page Forms Styler
 * Provides centralized error logging functionality
 */
class LPFS_Logger {
    /**
     * Log levels
     */
    const ERROR = 'error';
    const WARNING = 'warning';
    const INFO = 'info';
    const DEBUG = 'debug';
    
    
    /**
     * Check if logging is enabled
     * 
     * @return bool
     */
    private static function is_logging_enabled() {
        $settings = get_option( LPFS_Constants::LOG_SETTINGS_KEY, [] );
        return ! empty( $settings['enabled'] );
    }
    
    /**
     * Get log level threshold
     * 
     * @return string
     */
    private static function get_log_level() {
        $settings = get_option( LPFS_Constants::LOG_SETTINGS_KEY, [] );
        return $settings['level'] ?? self::ERROR;
    }
    
    /**
     * Check if a message should be logged based on level
     * 
     * @param string $level Message level
     * @return bool
     */
    private static function should_log( $level ) {
        if ( ! self::is_logging_enabled() ) {
            return false;
        }
        
        $levels = [
            self::DEBUG => 0,
            self::INFO => 1,
            self::WARNING => 2,
            self::ERROR => 3
        ];
        
        $threshold = self::get_log_level();
        $threshold_value = $levels[$threshold] ?? 3;
        $level_value = $levels[$level] ?? 0;
        
        return $level_value >= $threshold_value;
    }
    
    /**
     * Log a message
     * 
     * @param string $message The message to log
     * @param string $level Log level
     * @param array $context Additional context data
     */
    public static function log( $message, $level = self::INFO, $context = [] ) {
        if ( ! self::should_log( $level ) ) {
            return;
        }
        
        $log_entry = [
            'timestamp' => current_time( 'mysql' ),
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'user_id' => get_current_user_id(),
            'url' => $_SERVER['REQUEST_URI'] ?? '',
        ];
        
        // Get existing logs
        $logs = get_option( LPFS_Constants::ERROR_LOGS_KEY, [] );
        
        // Add new entry
        array_unshift( $logs, $log_entry );
        
        // Keep only the most recent entries
        $logs = array_slice( $logs, 0, LPFS_Constants::MAX_LOG_ENTRIES );
        
        // Save logs
        update_option( LPFS_Constants::ERROR_LOGS_KEY, $logs );
        
        // Also log to PHP error log if it's an error
        if ( $level === self::ERROR && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'LPFS Error: ' . $message . ' | Context: ' . json_encode( $context ) );
        }
    }
    
    /**
     * Log an error
     * 
     * @param string $message Error message
     * @param array $context Additional context
     */
    public static function error( $message, $context = [] ) {
        self::log( $message, self::ERROR, $context );
    }
    
    /**
     * Log a warning
     * 
     * @param string $message Warning message
     * @param array $context Additional context
     */
    public static function warning( $message, $context = [] ) {
        self::log( $message, self::WARNING, $context );
    }
    
    /**
     * Log info
     * 
     * @param string $message Info message
     * @param array $context Additional context
     */
    public static function info( $message, $context = [] ) {
        self::log( $message, self::INFO, $context );
    }
    
    /**
     * Log debug info
     * 
     * @param string $message Debug message
     * @param array $context Additional context
     */
    public static function debug( $message, $context = [] ) {
        self::log( $message, self::DEBUG, $context );
    }
    
    /**
     * Get all logs
     * 
     * @return array
     */
    public static function get_logs() {
        return get_option( LPFS_Constants::ERROR_LOGS_KEY, [] );
    }
    
    /**
     * Clear all logs
     */
    public static function clear_logs() {
        delete_option( LPFS_Constants::ERROR_LOGS_KEY );
        self::info( 'Logs cleared by user' );
    }
    
    /**
     * Enable logging
     * 
     * @param string $level Log level threshold
     */
    public static function enable_logging( $level = self::ERROR ) {
        update_option( LPFS_Constants::LOG_SETTINGS_KEY, [
            'enabled' => true,
            'level' => $level
        ] );
    }
    
    /**
     * Disable logging
     */
    public static function disable_logging() {
        update_option( LPFS_Constants::LOG_SETTINGS_KEY, [
            'enabled' => false
        ] );
    }
}