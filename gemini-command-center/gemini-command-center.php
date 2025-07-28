<?php
/**
 * Plugin Name: Gemini Command Center
 * Plugin URI: https://github.com/hossamhack7/hossamhack7.github.io
 * Description: Comprehensive AI-powered management suite for WordPress with security-first architecture and React-powered UI.
 * Version: 1.0.0
 * Author: Advanced WordPress Developer
 * Text Domain: gemini-command-center
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.5
 * Requires PHP: 8.0
 * Network: false
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'GEMINI_CC_VERSION', '1.0.0' );
define( 'GEMINI_CC_PLUGIN_FILE', __FILE__ );
define( 'GEMINI_CC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GEMINI_CC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'GEMINI_CC_INCLUDES_DIR', GEMINI_CC_PLUGIN_DIR . 'includes/' );
define( 'GEMINI_CC_ASSETS_URL', GEMINI_CC_PLUGIN_URL . 'assets/' );

/**
 * Main Gemini Command Center class
 */
class Gemini_Command_Center {

	/**
	 * Single instance of the class
	 *
	 * @var Gemini_Command_Center
	 */
	private static $instance = null;

	/**
	 * Assets loader instance
	 *
	 * @var Gemini_CC_Assets_Loader
	 */
	public $assets_loader;

	/**
	 * API registrar instance
	 *
	 * @var Gemini_CC_API_Registrar
	 */
	public $api_registrar;

	/**
	 * Get single instance
	 *
	 * @return Gemini_Command_Center
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
		$this->initialize_modules();
	}

	/**
	 * Load required dependencies
	 */
	private function load_dependencies() {
		require_once GEMINI_CC_INCLUDES_DIR . 'class-assets-loader.php';
		require_once GEMINI_CC_INCLUDES_DIR . 'class-api-registrar.php';
	}

	/**
	 * Initialize WordPress hooks
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		
		// Security check for plugin activation
		register_activation_hook( GEMINI_CC_PLUGIN_FILE, array( $this, 'activate_plugin' ) );
		register_deactivation_hook( GEMINI_CC_PLUGIN_FILE, array( $this, 'deactivate_plugin' ) );
	}

	/**
	 * Initialize plugin modules
	 */
	private function initialize_modules() {
		$this->assets_loader = new Gemini_CC_Assets_Loader();
		$this->api_registrar = new Gemini_CC_API_Registrar();
	}

	/**
	 * Load plugin text domain for internationalization
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'gemini-command-center', false, dirname( plugin_basename( GEMINI_CC_PLUGIN_FILE ) ) . '/languages' );
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		// Check user capability
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		add_menu_page(
			__( 'Gemini Command Center', 'gemini-command-center' ),
			__( 'Gemini Command Center', 'gemini-command-center' ),
			'manage_options',
			'gemini-command-center',
			array( $this, 'render_admin_page' ),
			'dashicons-rocket',
			30
		);

		// Add AI Chat submenu
		add_submenu_page(
			'gemini-command-center',
			__( 'AI Chat', 'gemini-command-center' ),
			__( 'AI Chat', 'gemini-command-center' ),
			'manage_options',
			'gemini-agent',
			array( $this, 'render_agent_page' )
		);
	}

	/**
	 * Render main admin page
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'gemini-command-center' ) );
		}

		// Enqueue assets for this page
		$this->assets_loader->enqueue_admin_assets();

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Gemini Command Center', 'gemini-command-center' ) . '</h1>';
		echo '<div id="gcc-react-root"></div>';
		echo '</div>';
	}

	/**
	 * Render AI agent page
	 */
	public function render_agent_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'gemini-command-center' ) );
		}

		// Enqueue assets for this page
		$this->assets_loader->enqueue_admin_assets();

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Gemini AI Agent', 'gemini-command-center' ) . '</h1>';
		echo '<div id="gcc-agent-root"></div>';
		echo '</div>';
	}

	/**
	 * Plugin activation
	 */
	public function activate_plugin() {
		// Check minimum requirements
		if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
			deactivate_plugins( plugin_basename( GEMINI_CC_PLUGIN_FILE ) );
			wp_die( __( 'Gemini Command Center requires PHP 8.0 or higher.', 'gemini-command-center' ) );
		}

		if ( version_compare( get_bloginfo( 'version' ), '6.0', '<' ) ) {
			deactivate_plugins( plugin_basename( GEMINI_CC_PLUGIN_FILE ) );
			wp_die( __( 'Gemini Command Center requires WordPress 6.0 or higher.', 'gemini-command-center' ) );
		}

		// Create database tables if needed
		$this->create_database_tables();

		// Set default options
		if ( ! get_option( 'gemini_cc_settings' ) ) {
			update_option( 'gemini_cc_settings', $this->get_default_settings() );
		}
	}

	/**
	 * Plugin deactivation
	 */
	public function deactivate_plugin() {
		// Clean up temporary data
		delete_transient( 'gemini_cc_system_health' );
		delete_transient( 'gemini_cc_technical_audit' );
	}

	/**
	 * Create database tables
	 */
	private function create_database_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Memory table for AI agent
		$table_name = $wpdb->prefix . 'gemini_cc_memory';
		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			memory_key varchar(255) NOT NULL,
			memory_value longtext NOT NULL,
			memory_type varchar(50) DEFAULT 'fact',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY memory_key (memory_key),
			KEY memory_type (memory_type)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Get default settings
	 */
	private function get_default_settings() {
		return array(
			'api_key' => '',
			'operation_mode' => 'approval',
			'seo_internal_links_enabled' => true,
			'seo_internal_links_max' => 5,
			'seo_ab_testing_enabled' => false,
			'seo_ab_testing_duration' => 14,
			'seo_competitive_region' => 'google.com',
			'uiux_enabled' => false,
			'content_default_status' => 'draft',
			'content_social_tone_twitter' => 'professional',
			'content_social_tone_linkedin' => 'business',
			'backup_schedule' => 'weekly',
			'backup_retention' => 5,
			'system_auto_cooldown' => true,
			'system_logging_enabled' => true,
		);
	}
}

/**
 * Initialize the plugin
 */
function gemini_command_center_init() {
	return Gemini_Command_Center::get_instance();
}

// Initialize plugin
add_action( 'plugins_loaded', 'gemini_command_center_init' );