<?php
/**
 * Plugin Name: Gemini Command Center
 * Plugin URI: https://github.com/hossamhack7/hossamhack7.github.io
 * Description: A comprehensive, AI-powered management suite for WordPress with advanced SEO, content creation, and analytics features.
 * Version: 1.0.0
 * Author: Hossam Hack
 * Author URI: https://computex2buy.me
 * Text Domain: gemini-command-center
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.4
 * Requires PHP: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Network: false
 *
 * @package GeminiCommandCenter
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('GEMINI_CC_VERSION', '1.0.0');
define('GEMINI_CC_PLUGIN_FILE', __FILE__);
define('GEMINI_CC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GEMINI_CC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GEMINI_CC_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
class Gemini_Command_Center {
    
    /**
     * Single instance of the class
     */
    protected static $_instance = null;
    
    /**
     * Main instance
     * 
     * @return Gemini_Command_Center
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'), 0);
        add_action('plugins_loaded', array($this, 'plugins_loaded'));
        
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Load core modules
        require_once GEMINI_CC_PLUGIN_DIR . 'includes/class-assets-loader.php';
        require_once GEMINI_CC_PLUGIN_DIR . 'includes/class-api-registrar.php';
        require_once GEMINI_CC_PLUGIN_DIR . 'includes/class-admin-menu.php';
        require_once GEMINI_CC_PLUGIN_DIR . 'includes/class-settings-manager.php';
        require_once GEMINI_CC_PLUGIN_DIR . 'includes/class-backup-manager.php';
        require_once GEMINI_CC_PLUGIN_DIR . 'includes/class-seo-manager.php';
        require_once GEMINI_CC_PLUGIN_DIR . 'includes/class-content-manager.php';
        require_once GEMINI_CC_PLUGIN_DIR . 'includes/class-uiux-manager.php';
        require_once GEMINI_CC_PLUGIN_DIR . 'includes/class-memory-manager.php';
        require_once GEMINI_CC_PLUGIN_DIR . 'includes/class-agent-manager.php';
    }
    
    /**
     * Initialize the plugin when WordPress initializes
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('gemini-command-center', false, dirname(GEMINI_CC_PLUGIN_BASENAME) . '/languages');
        
        // Initialize modules
        if (is_admin()) {
            new Gemini_CC_Assets_Loader();
            new Gemini_CC_Admin_Menu();
        }
        
        new Gemini_CC_API_Registrar();
        new Gemini_CC_Settings_Manager();
        new Gemini_CC_Backup_Manager();
        new Gemini_CC_SEO_Manager();
        new Gemini_CC_Content_Manager();
        new Gemini_CC_UIUX_Manager();
        new Gemini_CC_Memory_Manager();
        new Gemini_CC_Agent_Manager();
    }
    
    /**
     * When WP has loaded all plugins
     */
    public function plugins_loaded() {
        do_action('gemini_cc_loaded');
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create custom database tables
        $this->create_database_tables();
        
        // Set default options
        if (!get_option('gemini_cc_settings')) {
            $default_settings = array(
                'gemini_api_key' => '',
                'operation_mode' => 'approval',
                'seo_internal_links_enabled' => true,
                'seo_internal_links_max' => 5,
                'ab_testing_enabled' => false,
                'ab_testing_duration' => 14,
                'competitive_analysis_region' => 'google.com',
                'uiux_enabled' => false,
                'ai_writer_default_status' => 'draft',
                'backup_schedule' => 'disabled',
                'backup_retention' => 5,
                'usage_control_enabled' => false,
                'system_log_enabled' => true
            );
            update_option('gemini_cc_settings', $default_settings);
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('gemini_cc_backup_cron');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create custom database tables
     */
    private function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Memory table for AI agent
        $memory_table_name = $wpdb->prefix . 'gemini_cc_memory';
        $memory_sql = "CREATE TABLE $memory_table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            fact_type varchar(50) NOT NULL,
            fact_content text NOT NULL,
            importance_score int(3) DEFAULT 50,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY fact_type (fact_type),
            KEY importance_score (importance_score)
        ) $charset_collate;";
        
        // System log table
        $log_table_name = $wpdb->prefix . 'gemini_cc_logs';
        $log_sql = "CREATE TABLE $log_table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            log_level varchar(20) NOT NULL DEFAULT 'info',
            message text NOT NULL,
            context text,
            user_id bigint(20) UNSIGNED,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY log_level (log_level),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($memory_sql);
        dbDelta($log_sql);
    }
}

/**
 * Main instance of Gemini Command Center
 * 
 * @return Gemini_Command_Center
 */
function gemini_cc() {
    return Gemini_Command_Center::instance();
}

// Initialize the plugin
gemini_cc();