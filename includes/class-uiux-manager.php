<?php
/**
 * UI/UX Manager
 *
 * @package GeminiCommandCenter
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class Gemini_CC_UIUX_Manager
 */
class Gemini_CC_UIUX_Manager {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_uiux_routes'));
        add_action('wp_enqueue_scripts', array($this, 'inject_custom_styles'));
    }
    
    /**
     * Register UI/UX related REST routes
     */
    public function register_uiux_routes() {
        register_rest_route('gemini-cc/v1', '/uiux/analyze-design', array(
            'methods' => 'POST',
            'callback' => array($this, 'analyze_design'),
            'permission_callback' => array($this, 'check_permissions')
        ));
        
        register_rest_route('gemini-cc/v1', '/uiux/save-styles', array(
            'methods' => 'POST',
            'callback' => array($this, 'save_styles'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => array(
                'styles' => array(
                    'required' => true,
                    'type' => 'object'
                )
            )
        ));
    }
    
    /**
     * Check permissions
     */
    public function check_permissions() {
        return current_user_can('manage_options');
    }
    
    /**
     * Analyze design using AI
     */
    public function analyze_design() {
        // This would integrate with Gemini API for design analysis
        $analysis = array(
            'overall_score' => 85,
            'recommendations' => array(
                'Improve color contrast for better accessibility',
                'Consider using a more modern font pairing',
                'Optimize button sizes for mobile devices'
            ),
            'accessibility_score' => 78,
            'mobile_score' => 92,
            'timestamp' => current_time('mysql')
        );
        
        return rest_ensure_response($analysis);
    }
    
    /**
     * Save custom styles
     */
    public function save_styles($request) {
        $styles = $request->get_param('styles');
        
        if (!is_array($styles)) {
            return new WP_Error('invalid_styles', __('Invalid styles format', 'gemini-command-center'), array('status' => 400));
        }
        
        // Sanitize and save styles
        $sanitized_styles = array();
        
        if (isset($styles['primary_color'])) {
            $sanitized_styles['primary_color'] = sanitize_hex_color($styles['primary_color']);
        }
        
        if (isset($styles['secondary_color'])) {
            $sanitized_styles['secondary_color'] = sanitize_hex_color($styles['secondary_color']);
        }
        
        if (isset($styles['heading_font'])) {
            $sanitized_styles['heading_font'] = sanitize_text_field($styles['heading_font']);
        }
        
        if (isset($styles['body_font'])) {
            $sanitized_styles['body_font'] = sanitize_text_field($styles['body_font']);
        }
        
        $result = update_option('gemini_cc_custom_styles', $sanitized_styles);
        
        if ($result) {
            return rest_ensure_response(array(
                'success' => true,
                'message' => __('Styles saved successfully', 'gemini-command-center')
            ));
        } else {
            return new WP_Error('save_failed', __('Failed to save styles', 'gemini-command-center'), array('status' => 500));
        }
    }
    
    /**
     * Inject custom styles into the frontend
     */
    public function inject_custom_styles() {
        $custom_styles = get_option('gemini_cc_custom_styles', array());
        
        if (empty($custom_styles)) {
            return;
        }
        
        $css = '';
        
        if (!empty($custom_styles['primary_color'])) {
            $css .= sprintf('
                .primary-color { color: %s !important; }
                .bg-primary { background-color: %s !important; }
                a { color: %s; }
            ', $custom_styles['primary_color'], $custom_styles['primary_color'], $custom_styles['primary_color']);
        }
        
        if (!empty($custom_styles['heading_font'])) {
            $css .= sprintf('
                h1, h2, h3, h4, h5, h6 { font-family: "%s", sans-serif !important; }
            ', esc_attr($custom_styles['heading_font']));
        }
        
        if (!empty($custom_styles['body_font'])) {
            $css .= sprintf('
                body, p, div { font-family: "%s", sans-serif !important; }
            ', esc_attr($custom_styles['body_font']));
        }
        
        if (!empty($css)) {
            wp_add_inline_style('wp-block-library', $css);
        }
    }
}