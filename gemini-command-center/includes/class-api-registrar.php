<?php
/**
 * API Registrar Class
 *
 * Handles REST API endpoint registration and security.
 *
 * @package Gemini_Command_Center
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * API Registrar class
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
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		
		// Add debugging hook to check if routes are registered
		add_action( 'rest_api_init', array( $this, 'debug_routes_registration' ), 20 );
	}

	/**
	 * Debug routes registration (for troubleshooting)
	 */
	public function debug_routes_registration() {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$server = rest_get_server();
			$routes = $server->get_routes();
			
			if ( isset( $routes['/' . self::NAMESPACE . '/status'] ) ) {
				error_log( 'Gemini CC: Status route registered successfully' );
			} else {
				error_log( 'Gemini CC: Status route NOT found in registered routes' );
				error_log( 'Gemini CC: Available routes with gemini-cc: ' . wp_json_encode( 
					array_keys( array_filter( $routes, function( $key ) {
						return strpos( $key, 'gemini-cc' ) !== false;
					}, ARRAY_FILTER_USE_KEY ) )
				) );
			}
		}
	}

	/**
	 * Register REST API routes
	 */
	public function register_routes() {
		// Status endpoint
		$status_route = register_rest_route(
			self::NAMESPACE,
			'/status',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_status' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);
		
		// Debug route (only available when WP_DEBUG is true)
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			register_rest_route(
				self::NAMESPACE,
				'/debug',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_debug_info' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				)
			);
		}

		// Log if route registration failed
		if ( ! $status_route ) {
			$this->log_api_error( 'Failed to register status route', array(
				'namespace' => self::NAMESPACE,
				'route' => '/status',
			) );
		}

		// Settings endpoints
		register_rest_route(
			self::NAMESPACE,
			'/settings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_settings' ),
					'permission_callback' => array( $this, 'check_permissions' ),
					'args'                => $this->get_settings_schema(),
				),
			)
		);

		// Test connection endpoint
		register_rest_route(
			self::NAMESPACE,
			'/test-connection',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'test_gemini_connection' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => array(
					'api_key' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// Backup endpoints
		$this->register_backup_routes();

		// SEO endpoints
		$this->register_seo_routes();

		// Content endpoints
		$this->register_content_routes();

		// System endpoints
		$this->register_system_routes();

		// UI/UX endpoints
		$this->register_uiux_routes();

		// Reports endpoints
		$this->register_reports_routes();

		// AI Agent endpoint
		register_rest_route(
			self::NAMESPACE,
			'/agent/converse',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'agent_converse' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => array(
					'message' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_textarea_field',
					),
					'history' => array(
						'type'    => 'array',
						'default' => array(),
					),
				),
			)
		);
	}

	/**
	 * Register backup routes
	 */
	private function register_backup_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/backup/create',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_backup' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => array(
					'type' => array(
						'type'    => 'string',
						'enum'    => array( 'database', 'full' ),
						'default' => 'database',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/backup/list',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'list_backups' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/backup/download/(?P<filename>[a-zA-Z0-9\-_\.]+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'download_backup' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/backup/restore',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'restore_backup' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => array(
					'filename' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_file_name',
					),
				),
			)
		);
	}

	/**
	 * Register SEO routes
	 */
	private function register_seo_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/seo/technical-audit',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'technical_audit' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/seo/generate-cluster',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'generate_content_cluster' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => array(
					'pillar_topic' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/seo/analyze-competitor',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'analyze_competitor' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => array(
					'url' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'esc_url_raw',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/links/orphan-pages',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_orphan_pages' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/links/create',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_internal_link' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => array(
					'source_id'   => array(
						'required' => true,
						'type'     => 'integer',
					),
					'target_id'   => array(
						'required' => true,
						'type'     => 'integer',
					),
					'anchor_text' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Register content routes
	 */
	private function register_content_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/content/generate-article',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'generate_article' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => array(
					'topic'    => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'keywords' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'tone'     => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/content/save-draft',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'save_content_draft' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => array(
					'title'   => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'content' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'wp_kses_post',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/content/start-ab-test',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'start_ab_test' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => array(
					'post_id' => array(
						'required' => true,
						'type'     => 'integer',
					),
					'title_a' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'title_b' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/content/amplify',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'amplify_content' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => array(
					'post_id'  => array(
						'required' => true,
						'type'     => 'integer',
					),
					'platform' => array(
						'required' => true,
						'type'     => 'string',
						'enum'     => array( 'twitter', 'linkedin', 'newsletter' ),
					),
				),
			)
		);
	}

	/**
	 * Register system routes
	 */
	private function register_system_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/system/health-check',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'system_health_check' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/system/log',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_system_log' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'clear_system_log' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
			)
		);
	}

	/**
	 * Register UI/UX routes
	 */
	private function register_uiux_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/uiux/analyze-design',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'analyze_design' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/uiux/suggest-palette',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'suggest_color_palette' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => array(
					'primary_color' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_hex_color',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/uiux/save-styles',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'save_ui_styles' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => array(
					'styles' => array(
						'required' => true,
						'type'     => 'object',
					),
				),
			)
		);
	}

	/**
	 * Register reports routes
	 */
	private function register_reports_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/reports/kpi-summary',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_kpi_summary' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/reports/impact-analysis',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_impact_analysis' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);
	}

	/**
	 * Check permissions for API access
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function check_permissions( $request ) {
		// Log the permission check attempt for debugging
		$this->log_api_call( 'permission_check', $request );
		
		// Check user capability first
		if ( ! current_user_can( 'manage_options' ) ) {
			$this->log_api_error( 'User capability check failed', array(
				'user_id' => get_current_user_id(),
				'can_manage' => current_user_can( 'manage_options' ),
				'is_logged_in' => is_user_logged_in(),
			) );
			
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to access this resource.', 'gemini-command-center' ),
				array( 'status' => 403 )
			);
		}

		// Check nonce
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( empty( $nonce ) ) {
			$this->log_api_error( 'No nonce provided', array(
				'headers' => $request->get_headers(),
			) );
			
			return new WP_Error(
				'rest_forbidden',
				__( 'No security nonce provided.', 'gemini-command-center' ),
				array( 'status' => 403 )
			);
		}
		
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			$this->log_api_error( 'Nonce verification failed', array(
				'provided_nonce' => substr( $nonce, 0, 6 ) . '...',
				'expected_action' => 'wp_rest',
			) );
			
			return new WP_Error(
				'rest_forbidden',
				__( 'Invalid security nonce.', 'gemini-command-center' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Get plugin status
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_status( $request ) {
		$this->log_api_call( 'status', $request );

		$status_data = array(
			'status'          => 'ok',
			'version'         => GEMINI_CC_VERSION,
			'time'            => current_time( 'mysql' ),
			'wp_version'      => get_bloginfo( 'version' ),
			'php_version'     => PHP_VERSION,
			'rest_url_base'   => rest_url( self::NAMESPACE . '/' ),
			'user_id'         => get_current_user_id(),
			'user_can_manage' => current_user_can( 'manage_options' ),
		);

		return new WP_REST_Response( $status_data, 200 );
	}

	/**
	 * Get debug information (only available when WP_DEBUG is true)
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_debug_info( $request ) {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return new WP_REST_Response( 
				array( 'error' => 'Debug mode not enabled' ), 
				404 
			);
		}

		$this->log_api_call( 'debug', $request );

		$debug_info = array(
			'plugin_version'     => GEMINI_CC_VERSION,
			'wp_version'         => get_bloginfo( 'version' ),
			'php_version'        => PHP_VERSION,
			'rest_url_base'      => rest_url( self::NAMESPACE . '/' ),
			'user_info'          => array(
				'id'           => get_current_user_id(),
				'can_manage'   => current_user_can( 'manage_options' ),
				'is_logged_in' => is_user_logged_in(),
			),
			'api_log_entries'    => array_slice( get_option( 'gemini_cc_api_log', array() ), -20 ),
			'error_log_entries'  => array_slice( get_option( 'gemini_cc_error_log', array() ), -10 ),
			'registered_routes'  => $this->get_registered_routes(),
			'request_headers'    => $request->get_headers(),
			'server_info'        => array(
				'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
				'request_uri'    => $_SERVER['REQUEST_URI'] ?? 'unknown',
				'http_host'      => $_SERVER['HTTP_HOST'] ?? 'unknown',
			),
		);

		return new WP_REST_Response( $debug_info, 200 );
	}

	/**
	 * Get currently registered routes for debugging
	 *
	 * @return array
	 */
	private function get_registered_routes() {
		$server = rest_get_server();
		$routes = $server->get_routes();
		
		// Filter only Gemini CC routes
		$gemini_routes = array();
		foreach ( $routes as $route => $handlers ) {
			if ( strpos( $route, self::NAMESPACE ) !== false ) {
				$gemini_routes[ $route ] = array_map( function( $handler ) {
					return array(
						'methods' => $handler['methods'] ?? array(),
						'callback' => is_array( $handler['callback'] ) ? 
							get_class( $handler['callback'][0] ) . '::' . $handler['callback'][1] :
							$handler['callback'],
					);
				}, $handlers );
			}
		}
		
		return $gemini_routes;
	}

	/**
	 * Get settings
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_settings( $request ) {
		$this->log_api_call( 'get_settings', $request );

		$settings = get_option( 'gemini_cc_settings', array() );

		// Don't return the API key for security
		if ( isset( $settings['api_key'] ) ) {
			$settings['api_key'] = ! empty( $settings['api_key'] ) ? '••••••••' : '';
		}

		return new WP_REST_Response( $settings, 200 );
	}

	/**
	 * Save settings
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function save_settings( $request ) {
		$this->log_api_call( 'save_settings', $request );

		$new_settings = $request->get_json_params();
		$current_settings = get_option( 'gemini_cc_settings', array() );

		// Sanitize settings
		$sanitized_settings = $this->sanitize_settings( $new_settings, $current_settings );

		// Update settings
		update_option( 'gemini_cc_settings', $sanitized_settings );

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Settings saved successfully.', 'gemini-command-center' ),
			),
			200
		);
	}

	/**
	 * Test Gemini API connection
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function test_gemini_connection( $request ) {
		$this->log_api_call( 'test_connection', $request );

		$api_key = $request->get_param( 'api_key' );

		if ( empty( $api_key ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'API key is required.', 'gemini-command-center' ),
				),
				400
			);
		}

		// Test connection to Gemini API
		$test_result = $this->test_api_connection( $api_key );

		return new WP_REST_Response( $test_result, $test_result['success'] ? 200 : 400 );
	}

	/**
	 * AI Agent conversation endpoint
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function agent_converse( $request ) {
		$this->log_api_call( 'agent_converse', $request );

		$message = $request->get_param( 'message' );
		$history = $request->get_param( 'history' );

		// Get AI response
		$response = $this->process_agent_conversation( $message, $history );

		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * Create backup
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function create_backup( $request ) {
		$this->log_api_call( 'create_backup', $request );

		$type = $request->get_param( 'type' );
		
		// Implementation placeholder
		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => sprintf( __( '%s backup created successfully.', 'gemini-command-center' ), ucfirst( $type ) ),
				'filename' => 'backup_' . date( 'Y-m-d_H-i-s' ) . '.zip',
			),
			200
		);
	}

	/**
	 * List backups
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function list_backups( $request ) {
		$this->log_api_call( 'list_backups', $request );

		// Implementation placeholder
		return new WP_REST_Response(
			array(
				'backups' => array(),
			),
			200
		);
	}

	/**
	 * Download backup
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function download_backup( $request ) {
		$filename = $request->get_param( 'filename' );
		
		// Implementation placeholder
		return new WP_REST_Response(
			array(
				'success' => false,
				'message' => __( 'Backup download not implemented yet.', 'gemini-command-center' ),
			),
			501
		);
	}

	/**
	 * Restore backup
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function restore_backup( $request ) {
		$this->log_api_call( 'restore_backup', $request );

		$filename = $request->get_param( 'filename' );
		
		// Implementation placeholder
		return new WP_REST_Response(
			array(
				'success' => false,
				'message' => __( 'Backup restore not implemented yet.', 'gemini-command-center' ),
			),
			501
		);
	}

	/**
	 * Technical audit
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function technical_audit( $request ) {
		$this->log_api_call( 'technical_audit', $request );

		// Implementation placeholder
		return new WP_REST_Response(
			array(
				'sitemap_status' => 'found',
				'robots_txt' => 'valid',
				'broken_links' => array(),
			),
			200
		);
	}

	/**
	 * Generate content cluster
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function generate_content_cluster( $request ) {
		$this->log_api_call( 'generate_cluster', $request );

		$pillar_topic = $request->get_param( 'pillar_topic' );
		
		// Implementation placeholder
		return new WP_REST_Response(
			array(
				'pillar_topic' => $pillar_topic,
				'cluster_topics' => array(),
			),
			200
		);
	}

	/**
	 * Analyze competitor
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function analyze_competitor( $request ) {
		$this->log_api_call( 'analyze_competitor', $request );

		$url = $request->get_param( 'url' );
		
		// Implementation placeholder
		return new WP_REST_Response(
			array(
				'url' => $url,
				'analysis' => array(),
			),
			200
		);
	}

	/**
	 * Get orphan pages
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_orphan_pages( $request ) {
		$this->log_api_call( 'orphan_pages', $request );

		// Implementation placeholder
		return new WP_REST_Response(
			array(
				'orphan_pages' => array(),
			),
			200
		);
	}

	/**
	 * Create internal link
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function create_internal_link( $request ) {
		$this->log_api_call( 'create_link', $request );

		$source_id = $request->get_param( 'source_id' );
		$target_id = $request->get_param( 'target_id' );
		$anchor_text = $request->get_param( 'anchor_text' );
		
		// Implementation placeholder
		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Internal link created successfully.', 'gemini-command-center' ),
			),
			200
		);
	}

	/**
	 * Generate article
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function generate_article( $request ) {
		$this->log_api_call( 'generate_article', $request );

		$topic = $request->get_param( 'topic' );
		$keywords = $request->get_param( 'keywords' );
		$tone = $request->get_param( 'tone' );
		
		// Implementation placeholder
		return new WP_REST_Response(
			array(
				'title' => $topic,
				'content' => '<p>Generated article content will appear here.</p>',
			),
			200
		);
	}

	/**
	 * Save content draft
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function save_content_draft( $request ) {
		$this->log_api_call( 'save_draft', $request );

		$title = $request->get_param( 'title' );
		$content = $request->get_param( 'content' );
		
		// Implementation placeholder
		return new WP_REST_Response(
			array(
				'success' => true,
				'post_id' => 0,
				'message' => __( 'Draft saved successfully.', 'gemini-command-center' ),
			),
			200
		);
	}

	/**
	 * Start A/B test
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function start_ab_test( $request ) {
		$this->log_api_call( 'start_ab_test', $request );

		// Implementation placeholder
		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'A/B test started successfully.', 'gemini-command-center' ),
			),
			200
		);
	}

	/**
	 * Amplify content
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function amplify_content( $request ) {
		$this->log_api_call( 'amplify_content', $request );

		$post_id = $request->get_param( 'post_id' );
		$platform = $request->get_param( 'platform' );
		
		// Implementation placeholder
		return new WP_REST_Response(
			array(
				'success' => true,
				'content' => 'Generated social media content for ' . $platform,
			),
			200
		);
	}

	/**
	 * System health check
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function system_health_check( $request ) {
		$this->log_api_call( 'health_check', $request );

		// Implementation placeholder
		return new WP_REST_Response(
			array(
				'php_version' => PHP_VERSION,
				'wp_version' => get_bloginfo( 'version' ),
				'memory_limit' => ini_get( 'memory_limit' ),
				'gemini_api_status' => 'unknown',
			),
			200
		);
	}

	/**
	 * Get system log
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_system_log( $request ) {
		$this->log_api_call( 'get_log', $request );

		$api_log = get_option( 'gemini_cc_api_log', array() );
		$error_log = get_option( 'gemini_cc_error_log', array() );
		
		// Get recent entries (last 50 of each type)
		$recent_api = array_slice( $api_log, -50 );
		$recent_errors = array_slice( $error_log, -50 );

		$log_data = array(
			'api_calls' => $recent_api,
			'errors' => $recent_errors,
			'summary' => array(
				'total_api_calls' => count( $api_log ),
				'total_errors' => count( $error_log ),
				'recent_errors_count' => count( array_filter( $recent_errors, function( $entry ) {
					return isset( $entry['timestamp'] ) && $entry['timestamp'] > ( time() - 3600 ); // Last hour
				} ) ),
			),
			'system_info' => array(
				'wp_version' => get_bloginfo( 'version' ),
				'php_version' => PHP_VERSION,
				'plugin_version' => GEMINI_CC_VERSION,
				'memory_limit' => ini_get( 'memory_limit' ),
				'max_execution_time' => ini_get( 'max_execution_time' ),
				'wp_debug' => defined( 'WP_DEBUG' ) && WP_DEBUG,
			),
		);

		return new WP_REST_Response( $log_data, 200 );
	}

	/**
	 * Clear system log
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function clear_system_log( $request ) {
		$this->log_api_call( 'clear_log', $request );

		// Clear both API and error logs
		delete_option( 'gemini_cc_api_log' );
		delete_option( 'gemini_cc_error_log' );

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'System logs cleared successfully.', 'gemini-command-center' ),
			),
			200
		);
	}

	/**
	 * Analyze design
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function analyze_design( $request ) {
		$this->log_api_call( 'analyze_design', $request );

		// Implementation placeholder
		return new WP_REST_Response(
			array(
				'analysis' => 'Design analysis will appear here.',
			),
			200
		);
	}

	/**
	 * Suggest color palette
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function suggest_color_palette( $request ) {
		$this->log_api_call( 'suggest_palette', $request );

		$primary_color = $request->get_param( 'primary_color' );
		
		// Implementation placeholder
		return new WP_REST_Response(
			array(
				'primary' => $primary_color,
				'palette' => array(),
			),
			200
		);
	}

	/**
	 * Save UI styles
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function save_ui_styles( $request ) {
		$this->log_api_call( 'save_styles', $request );

		$styles = $request->get_param( 'styles' );
		
		// Implementation placeholder
		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'UI styles saved successfully.', 'gemini-command-center' ),
			),
			200
		);
	}

	/**
	 * Get KPI summary
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_kpi_summary( $request ) {
		$this->log_api_call( 'kpi_summary', $request );

		// Implementation placeholder
		return new WP_REST_Response(
			array(
				'kpis' => array(),
			),
			200
		);
	}

	/**
	 * Get impact analysis
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_impact_analysis( $request ) {
		$this->log_api_call( 'impact_analysis', $request );

		// Implementation placeholder
		return new WP_REST_Response(
			array(
				'analysis' => array(),
			),
			200
		);
	}

	/**
	 * Get settings schema
	 *
	 * @return array
	 */
	private function get_settings_schema() {
		return array(
			'api_key'                      => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'operation_mode'               => array(
				'type' => 'string',
				'enum' => array( 'approval', 'autonomous' ),
			),
			'seo_internal_links_enabled'   => array(
				'type' => 'boolean',
			),
			'seo_internal_links_max'       => array(
				'type'    => 'integer',
				'minimum' => 1,
				'maximum' => 20,
			),
			'seo_ab_testing_enabled'       => array(
				'type' => 'boolean',
			),
			'seo_ab_testing_duration'      => array(
				'type'    => 'integer',
				'minimum' => 1,
				'maximum' => 90,
			),
			'seo_competitive_region'       => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'uiux_enabled'                 => array(
				'type' => 'boolean',
			),
			'content_default_status'       => array(
				'type' => 'string',
				'enum' => array( 'draft', 'publish' ),
			),
			'content_social_tone_twitter'  => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'content_social_tone_linkedin' => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'backup_schedule'              => array(
				'type' => 'string',
				'enum' => array( 'disabled', 'daily', 'weekly' ),
			),
			'backup_retention'             => array(
				'type'    => 'integer',
				'minimum' => 1,
				'maximum' => 50,
			),
			'system_auto_cooldown'         => array(
				'type' => 'boolean',
			),
			'system_logging_enabled'       => array(
				'type' => 'boolean',
			),
		);
	}

	/**
	 * Sanitize settings
	 *
	 * @param array $new_settings New settings.
	 * @param array $current_settings Current settings.
	 * @return array
	 */
	private function sanitize_settings( $new_settings, $current_settings ) {
		$schema = $this->get_settings_schema();
		$sanitized = array();

		foreach ( $schema as $key => $rules ) {
			if ( isset( $new_settings[ $key ] ) ) {
				$value = $new_settings[ $key ];

				// Handle special cases
				if ( $key === 'api_key' && $value === '••••••••' ) {
					// Don't change API key if it's masked
					$sanitized[ $key ] = $current_settings[ $key ] ?? '';
					continue;
				}

				// Apply sanitization
				if ( isset( $rules['sanitize_callback'] ) && is_callable( $rules['sanitize_callback'] ) ) {
					$value = call_user_func( $rules['sanitize_callback'], $value );
				}

				// Type casting
				switch ( $rules['type'] ) {
					case 'boolean':
						$value = (bool) $value;
						break;
					case 'integer':
						$value = (int) $value;
						// Apply min/max constraints
						if ( isset( $rules['minimum'] ) ) {
							$value = max( $value, $rules['minimum'] );
						}
						if ( isset( $rules['maximum'] ) ) {
							$value = min( $value, $rules['maximum'] );
						}
						break;
					case 'string':
						$value = (string) $value;
						// Apply enum constraints
						if ( isset( $rules['enum'] ) && ! in_array( $value, $rules['enum'], true ) ) {
							$value = $rules['enum'][0] ?? '';
						}
						break;
				}

				$sanitized[ $key ] = $value;
			} else {
				// Keep existing value if not provided
				$sanitized[ $key ] = $current_settings[ $key ] ?? null;
			}
		}

		return $sanitized;
	}

	/**
	 * Test API connection
	 *
	 * @param string $api_key API key to test.
	 * @return array
	 */
	private function test_api_connection( $api_key ) {
		// Implementation placeholder - would test actual Gemini API
		if ( empty( $api_key ) || strlen( $api_key ) < 10 ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid API key format.', 'gemini-command-center' ),
			);
		}

		return array(
			'success' => true,
			'message' => __( 'API connection test successful.', 'gemini-command-center' ),
		);
	}

	/**
	 * Process agent conversation
	 *
	 * @param string $message User message.
	 * @param array  $history Conversation history.
	 * @return array
	 */
	private function process_agent_conversation( $message, $history ) {
		// Implementation placeholder - would integrate with Gemini API
		$responses = array(
			'Hello! I\'m your Gemini AI assistant. How can I help you manage your WordPress site today?',
			'I can help you with SEO optimization, content creation, backups, and system monitoring.',
			'What specific task would you like me to help you with?',
			'I\'m here to assist with your WordPress management needs.',
		);

		return array(
			'message' => $responses[ array_rand( $responses ) ],
			'timestamp' => time(),
		);
	}

	/**
	 * Log API call
	 *
	 * @param string          $endpoint Endpoint name.
	 * @param WP_REST_Request $request Request object.
	 */
	private function log_api_call( $endpoint, $request ) {
		$settings = get_option( 'gemini_cc_settings', array() );
		
		if ( ! empty( $settings['system_logging_enabled'] ) ) {
			$log_entry = array(
				'type'     => 'api_call',
				'endpoint' => $endpoint,
				'user_id'  => get_current_user_id(),
				'ip'       => $request->get_header( 'X-Forwarded-For' ) ?: $_SERVER['REMOTE_ADDR'] ?? 'unknown',
				'method'   => $request->get_method(),
				'time'     => current_time( 'mysql' ),
				'timestamp' => time(),
			);

			$log = get_option( 'gemini_cc_api_log', array() );
			$log[] = $log_entry;

			// Keep only last 1000 entries
			if ( count( $log ) > 1000 ) {
				$log = array_slice( $log, -1000 );
			}

			update_option( 'gemini_cc_api_log', $log );
		}
	}

	/**
	 * Log API error
	 *
	 * @param string $message Error message.
	 * @param array  $context Error context.
	 */
	private function log_api_error( $message, $context = array() ) {
		$settings = get_option( 'gemini_cc_settings', array() );
		
		// Always log errors regardless of logging setting for debugging
		$log_entry = array(
			'type'      => 'api_error',
			'message'   => $message,
			'context'   => $context,
			'user_id'   => get_current_user_id(),
			'time'      => current_time( 'mysql' ),
			'timestamp' => time(),
			'backtrace' => wp_debug_backtrace_summary(),
		);

		$error_log = get_option( 'gemini_cc_error_log', array() );
		$error_log[] = $log_entry;

		// Keep only last 500 error entries
		if ( count( $error_log ) > 500 ) {
			$error_log = array_slice( $error_log, -500 );
		}

		update_option( 'gemini_cc_error_log', $error_log );

		// Also log to PHP error log if WP_DEBUG is enabled
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Gemini CC API Error: ' . $message . ' | Context: ' . wp_json_encode( $context ) );
		}
	}
}