<?php
/**
 * Assets Loader
 *
 * @package GeminiCommandCenter
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class Gemini_CC_Assets_Loader
 */
class Gemini_CC_Assets_Loader {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our plugin pages
        if (!$this->is_gemini_cc_page($hook)) {
            return;
        }
        
        // Enqueue React and dependencies
        wp_enqueue_script(
            'gemini-cc-react',
            GEMINI_CC_PLUGIN_URL . 'assets/build/index.js',
            array('wp-element', 'wp-api-fetch', 'wp-components', 'wp-i18n'),
            GEMINI_CC_VERSION,
            true
        );
        
        // Localize script with data
        wp_localize_script('gemini-cc-react', 'geminiCC', array(
            'apiUrl' => rest_url('gemini-cc/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'adminUrl' => admin_url(),
            'pluginUrl' => GEMINI_CC_PLUGIN_URL,
            'currentUser' => wp_get_current_user()->ID,
            'strings' => array(
                'loading' => __('Loading...', 'gemini-command-center'),
                'error' => __('Error occurred', 'gemini-command-center'),
                'success' => __('Success', 'gemini-command-center'),
                'save' => __('Save', 'gemini-command-center'),
                'cancel' => __('Cancel', 'gemini-command-center'),
                'delete' => __('Delete', 'gemini-command-center'),
                'confirm' => __('Are you sure?', 'gemini-command-center')
            )
        ));
        
        // Make translations available to JS
        wp_set_script_translations('gemini-cc-react', 'gemini-command-center');
    }
    
    /**
     * Enqueue admin styles
     */
    public function enqueue_admin_styles($hook) {
        // Only load on our plugin pages
        if (!$this->is_gemini_cc_page($hook)) {
            return;
        }
        
        wp_enqueue_style(
            'gemini-cc-admin',
            GEMINI_CC_PLUGIN_URL . 'assets/build/style.css',
            array(),
            GEMINI_CC_VERSION
        );
        
        // Add custom CSS for admin
        wp_add_inline_style('gemini-cc-admin', $this->get_inline_admin_css());
    }
    
    /**
     * Check if current page is a Gemini CC page
     */
    private function is_gemini_cc_page($hook) {
        $gemini_pages = array(
            'toplevel_page_gemini-command-center',
            'gemini-command-center_page_gemini-agent',
            'gemini-command-center_page_gemini-cc-settings',
            'gemini-command-center_page_gemini-cc-system'
        );
        
        return in_array($hook, $gemini_pages);
    }
    
    /**
     * Get inline admin CSS
     */
    private function get_inline_admin_css() {
        return '
            .gemini-cc-root {
                margin: 20px 0;
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                overflow: hidden;
            }
            
            .gemini-cc-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 20px;
                text-align: center;
            }
            
            .gemini-cc-header h1 {
                margin: 0;
                font-size: 24px;
                font-weight: 600;
            }
            
            .gemini-cc-content {
                padding: 20px;
            }
            
            .gemini-cc-loading {
                text-align: center;
                padding: 40px;
                color: #666;
            }
            
            .gemini-cc-spinner {
                display: inline-block;
                width: 20px;
                height: 20px;
                border: 3px solid #f3f3f3;
                border-top: 3px solid #667eea;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        ';
    }
}