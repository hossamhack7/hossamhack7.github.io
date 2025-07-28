<?php
/**
 * Settings Manager
 *
 * @package GeminiCommandCenter
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class Gemini_CC_Settings_Manager
 */
class Gemini_CC_Settings_Manager {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Settings hooks can be added here
    }
    
    /**
     * Get setting value
     */
    public static function get_setting($key, $default = null) {
        $settings = get_option('gemini_cc_settings', array());
        return isset($settings[$key]) ? $settings[$key] : $default;
    }
    
    /**
     * Update setting value
     */
    public static function update_setting($key, $value) {
        $settings = get_option('gemini_cc_settings', array());
        $settings[$key] = $value;
        return update_option('gemini_cc_settings', $settings);
    }
}