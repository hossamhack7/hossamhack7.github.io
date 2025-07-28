<?php
/**
 * REST API Registrar
 *
 * @package GeminiCommandCenter
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class Gemini_CC_API_Registrar
 */
class Gemini_CC_API_Registrar {
    
    /**
     * API namespace
     */
    const NAMESPACE = 'gemini-cc/v1';
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Status endpoint
        register_rest_route(self::NAMESPACE, '/status', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_status'),
            'permission_callback' => array($this, 'check_permissions')
        ));
        
        // Settings endpoints
        register_rest_route(self::NAMESPACE, '/settings', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_settings'),
                'permission_callback' => array($this, 'check_permissions')
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'update_settings'),
                'permission_callback' => array($this, 'check_permissions'),
                'args' => array(
                    'settings' => array(
                        'required' => true,
                        'type' => 'object'
                    )
                )
            )
        ));
        
        // Test connection endpoint
        register_rest_route(self::NAMESPACE, '/test-connection', array(
            'methods' => 'POST',
            'callback' => array($this, 'test_connection'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => array(
                'api_key' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));
        
        // System endpoints
        register_rest_route(self::NAMESPACE, '/system/health-check', array(
            'methods' => 'GET',
            'callback' => array($this, 'health_check'),
            'permission_callback' => array($this, 'check_permissions')
        ));
        
        // Agent conversation endpoint
        register_rest_route(self::NAMESPACE, '/agent/converse', array(
            'methods' => 'POST',
            'callback' => array($this, 'agent_converse'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => array(
                'message' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field'
                ),
                'history' => array(
                    'required' => false,
                    'type' => 'array',
                    'default' => array()
                )
            )
        ));
    }
    
    /**
     * Check permissions for API access
     */
    public function check_permissions() {
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        // Verify nonce for security
        if (!wp_verify_nonce($_REQUEST['_wpnonce'] ?? '', 'wp_rest')) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get plugin status
     */
    public function get_status() {
        return rest_ensure_response(array(
            'status' => 'ok',
            'version' => GEMINI_CC_VERSION,
            'timestamp' => current_time('mysql')
        ));
    }
    
    /**
     * Get plugin settings
     */
    public function get_settings() {
        $settings = get_option('gemini_cc_settings', array());
        
        // Don't expose sensitive data like API keys in full
        if (isset($settings['gemini_api_key']) && !empty($settings['gemini_api_key'])) {
            $settings['gemini_api_key_set'] = true;
            $settings['gemini_api_key'] = str_repeat('*', 20);
        } else {
            $settings['gemini_api_key_set'] = false;
        }
        
        return rest_ensure_response($settings);
    }
    
    /**
     * Update plugin settings
     */
    public function update_settings($request) {
        $new_settings = $request->get_param('settings');
        
        if (!is_array($new_settings)) {
            return new WP_Error('invalid_settings', __('Invalid settings format', 'gemini-command-center'), array('status' => 400));
        }
        
        // Sanitize settings
        $sanitized_settings = array();
        
        // Text fields
        $text_fields = array(
            'gemini_api_key', 'operation_mode', 'competitive_analysis_region',
            'ai_writer_default_status', 'backup_schedule'
        );
        
        foreach ($text_fields as $field) {
            if (isset($new_settings[$field])) {
                $sanitized_settings[$field] = sanitize_text_field($new_settings[$field]);
            }
        }
        
        // Boolean fields
        $boolean_fields = array(
            'seo_internal_links_enabled', 'ab_testing_enabled', 'uiux_enabled',
            'usage_control_enabled', 'system_log_enabled'
        );
        
        foreach ($boolean_fields as $field) {
            if (isset($new_settings[$field])) {
                $sanitized_settings[$field] = (bool) $new_settings[$field];
            }
        }
        
        // Integer fields
        $integer_fields = array(
            'seo_internal_links_max', 'ab_testing_duration', 'backup_retention'
        );
        
        foreach ($integer_fields as $field) {
            if (isset($new_settings[$field])) {
                $sanitized_settings[$field] = intval($new_settings[$field]);
            }
        }
        
        // Merge with existing settings
        $current_settings = get_option('gemini_cc_settings', array());
        $updated_settings = array_merge($current_settings, $sanitized_settings);
        
        // Update settings
        $result = update_option('gemini_cc_settings', $updated_settings);
        
        if ($result) {
            return rest_ensure_response(array(
                'success' => true,
                'message' => __('Settings updated successfully', 'gemini-command-center')
            ));
        } else {
            return new WP_Error('update_failed', __('Failed to update settings', 'gemini-command-center'), array('status' => 500));
        }
    }
    
    /**
     * Test Gemini API connection
     */
    public function test_connection($request) {
        $api_key = $request->get_param('api_key');
        
        if (empty($api_key)) {
            return new WP_Error('missing_api_key', __('API key is required', 'gemini-command-center'), array('status' => 400));
        }
        
        // Make a simple test request to Gemini API
        $response = wp_remote_post('https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $api_key, array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'contents' => array(
                    array(
                        'parts' => array(
                            array('text' => 'Hello, this is a test connection. Please respond with "Connection successful".')
                        )
                    )
                )
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return new WP_Error('connection_failed', __('Failed to connect to Gemini API', 'gemini-command-center'), array('status' => 500));
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code === 200) {
            return rest_ensure_response(array(
                'success' => true,
                'message' => __('Successfully connected to Gemini API', 'gemini-command-center')
            ));
        } else {
            return new WP_Error('api_error', sprintf(__('API returned error: %s', 'gemini-command-center'), $body), array('status' => $status_code));
        }
    }
    
    /**
     * System health check
     */
    public function health_check() {
        $health_data = array(
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'max_execution_time' => ini_get('max_execution_time'),
            'backup_directory_writable' => is_writable(GEMINI_CC_PLUGIN_DIR . 'uploads/gemini-cc-backups/'),
            'plugin_version' => GEMINI_CC_VERSION,
            'timestamp' => current_time('mysql')
        );
        
        // Check Gemini API status
        $settings = get_option('gemini_cc_settings', array());
        if (!empty($settings['gemini_api_key'])) {
            $health_data['gemini_api_status'] = 'configured';
        } else {
            $health_data['gemini_api_status'] = 'not_configured';
        }
        
        return rest_ensure_response($health_data);
    }
    
    /**
     * Agent conversation endpoint
     */
    public function agent_converse($request) {
        $message = $request->get_param('message');
        $history = $request->get_param('history');
        
        // This is a placeholder - would integrate with actual Gemini API
        $response = array(
            'response' => sprintf(__('Thank you for your message: "%s". This is a demo response from the Gemini Command Center AI Agent. The full implementation would process your request using the Gemini API.', 'gemini-command-center'), $message),
            'timestamp' => current_time('mysql')
        );
        
        return rest_ensure_response($response);
    }
}