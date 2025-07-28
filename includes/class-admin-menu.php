<?php
/**
 * Admin Menu Management
 *
 * @package GeminiCommandCenter
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class Gemini_CC_Admin_Menu
 */
class Gemini_CC_Admin_Menu {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __('Gemini Command Center', 'gemini-command-center'),
            __('Gemini Command Center', 'gemini-command-center'),
            'manage_options',
            'gemini-command-center',
            array($this, 'render_main_page'),
            'dashicons-rocket',
            30
        );
        
        // AI Chat submenu
        add_submenu_page(
            'gemini-command-center',
            __('Gemini Agent', 'gemini-command-center'),
            __('AI Chat', 'gemini-command-center'),
            'manage_options',
            'gemini-agent',
            array($this, 'render_agent_page')
        );
        
        // Settings submenu
        add_submenu_page(
            'gemini-command-center',
            __('Settings', 'gemini-command-center'),
            __('Settings', 'gemini-command-center'),
            'manage_options',
            'gemini-cc-settings',
            array($this, 'render_settings_page')
        );
        
        // System submenu
        add_submenu_page(
            'gemini-command-center',
            __('System & Diagnostics', 'gemini-command-center'),
            __('System', 'gemini-command-center'),
            'manage_options',
            'gemini-cc-system',
            array($this, 'render_system_page')
        );
    }
    
    /**
     * Render main dashboard page
     */
    public function render_main_page() {
        include_once GEMINI_CC_PLUGIN_DIR . 'admin-pages/main-dashboard.php';
    }
    
    /**
     * Render AI agent chat page
     */
    public function render_agent_page() {
        include_once GEMINI_CC_PLUGIN_DIR . 'admin-pages/agent-chat.php';
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        include_once GEMINI_CC_PLUGIN_DIR . 'admin-pages/settings.php';
    }
    
    /**
     * Render system page
     */
    public function render_system_page() {
        include_once GEMINI_CC_PLUGIN_DIR . 'admin-pages/system.php';
    }
}