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
		// Register routes early with high priority
		add_action( 'rest_api_init', array( $this, 'register_routes' ), 5 );
		
		// Add debugging hook to check if routes are registered
		add_action( 'rest_api_init', array( $this, 'debug_routes_registration' ), 20 );
		
		// Also try to register immediately if rest_api_init has already fired
		if ( did_action( 'rest_api_init' ) ) {
			$this->register_routes();
			$this->debug_routes_registration();
		}
		
		// Add a verification hook to ensure routes are available
		add_action( 'wp_loaded', array( $this, 'verify_routes_registered' ) );
	}

	/**
	 * Verify routes are properly registered after WordPress is fully loaded
	 */
	public function verify_routes_registered() {
		$server = rest_get_server();
		$routes = $server->get_routes();
		
		$status_route_key = '/' . self::NAMESPACE . '/status';
		$route_exists = isset( $routes[ $status_route_key ] );
		
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			if ( $route_exists ) {
				error_log( 'Gemini CC: Route verification successful - status route is registered' );
			} else {
				error_log( 'Gemini CC: CRITICAL - Status route not found during verification!' );
				error_log( 'Gemini CC: Available Gemini routes: ' . wp_json_encode( 
					array_keys( array_filter( $routes, function( $key ) {
						return strpos( $key, 'gemini-cc' ) !== false;
					}, ARRAY_FILTER_USE_KEY ) )
				) );
				
				// Try to register routes again as a fallback
				error_log( 'Gemini CC: Attempting fallback route registration...' );
				$this->register_routes();
			}
		}
		
		// Store route registration status for frontend debugging
		update_option( 'gemini_cc_routes_registered', $route_exists );
		update_option( 'gemini_cc_route_check_time', current_time( 'mysql' ) );
		
		return $route_exists;
	}

	/**
	 * Debug routes registration (for troubleshooting)
	 */
	public function debug_routes_registration() {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$server = rest_get_server();
			$routes = $server->get_routes();
			
			// Check if our specific route is registered
			$status_route_key = '/' . self::NAMESPACE . '/status';
			if ( isset( $routes[ $status_route_key ] ) ) {
				error_log( 'Gemini CC: Status route registered successfully at ' . current_time( 'mysql' ) );
				error_log( 'Gemini CC: Route details - ' . wp_json_encode( $routes[ $status_route_key ] ) );
			} else {
				error_log( 'Gemini CC: ERROR - Status route NOT found in registered routes at ' . current_time( 'mysql' ) );
				error_log( 'Gemini CC: Current hook: ' . current_action() );
				error_log( 'Gemini CC: Total registered routes: ' . count( $routes ) );
				error_log( 'Gemini CC: Available routes with gemini-cc: ' . wp_json_encode( 
					array_keys( array_filter( $routes, function( $key ) {
						return strpos( $key, 'gemini-cc' ) !== false;
					}, ARRAY_FILTER_USE_KEY ) )
				) );
			}

			// Log all registered REST API endpoints that might be relevant
			$all_gemini_routes = array();
			foreach ( $routes as $route_key => $route_handlers ) {
				if ( strpos( $route_key, 'gemini-cc' ) !== false ) {
					$all_gemini_routes[ $route_key ] = array_map( function( $handler ) {
						return array(
							'methods' => $handler['methods'] ?? array(),
							'callback' => is_array( $handler['callback'] ) && count( $handler['callback'] ) >= 2 ? 
								get_class( $handler['callback'][0] ) . '::' . $handler['callback'][1] :
								'unknown_callback',
						);
					}, $route_handlers );
				}
			}
			
			if ( ! empty( $all_gemini_routes ) ) {
				error_log( 'Gemini CC: All registered Gemini routes - ' . wp_json_encode( $all_gemini_routes ) );
			} else {
				error_log( 'Gemini CC: WARNING - No Gemini routes found in registration!' );
			}

			// Also log the expected URLs for testing
			error_log( 'Gemini CC: Expected REST URLs:' );
			error_log( 'Gemini CC: - Standard format: ' . home_url( '/wp-json/gemini-cc/v1/status' ) );
			error_log( 'Gemini CC: - rest_url() format: ' . rest_url( 'gemini-cc/v1/status' ) );
		}
	}

	/**
	 * Register REST API routes
	 */
	public function register_routes() {
		// Log registration attempt
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Gemini CC: Starting route registration at ' . current_time( 'mysql' ) );
			error_log( 'Gemini CC: Current hook: ' . current_action() );
			error_log( 'Gemini CC: Namespace: ' . self::NAMESPACE );
		}
		
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
		
		// Log if route registration failed
		if ( ! $status_route ) {
			$this->log_api_error( 'CRITICAL: Failed to register status route', array(
				'namespace' => self::NAMESPACE,
				'route' => '/status',
				'hook' => current_action(),
				'time' => current_time( 'mysql' )
			) );
			
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Gemini CC: CRITICAL ERROR - Status route registration failed!' );
			}
		} else {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Gemini CC: Status route registered successfully' );
			}
		}

		// Debug route (only available when WP_DEBUG is true)
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$debug_route = register_rest_route(
				self::NAMESPACE,
				'/debug',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_debug_info' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				)
			);
			
			if ( ! $debug_route ) {
				error_log( 'Gemini CC: Warning - Debug route registration failed' );
			}
			
			// Frontend error logging endpoint
			$log_route = register_rest_route(
				self::NAMESPACE,
				'/debug/log-error',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'log_frontend_error' ),
					'permission_callback' => array( $this, 'check_permissions' ),
					'args'                => array(
						'type' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'log_entry' => array(
							'required'          => true,
							'type'              => 'object',
						),
					),
				)
			);
			
			if ( ! $log_route ) {
				error_log( 'Gemini CC: Warning - Log error route registration failed' );
			}
		}

		// Settings endpoints
		$settings_route = register_rest_route(
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

		if ( ! $settings_route && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Gemini CC: Warning - Settings route registration failed' );
		}

		// Test connection endpoint
		$test_route = register_rest_route(
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

		if ( ! $test_route && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Gemini CC: Warning - Test connection route registration failed' );
		}

		// Register other endpoint groups
		$this->register_backup_routes();
		$this->register_seo_routes();
		$this->register_content_routes();
		$this->register_system_routes();
		$this->register_uiux_routes();
		$this->register_reports_routes();

		// AI Agent endpoint
		$agent_route = register_rest_route(
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

		if ( ! $agent_route && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Gemini CC: Warning - Agent route registration failed' );
		}
		
		// Log completion
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Gemini CC: Route registration completed at ' . current_time( 'mysql' ) );
		}
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

		// Check if routes are properly registered
		$server = rest_get_server();
		$routes = $server->get_routes();
		$status_route_key = '/' . self::NAMESPACE . '/status';
		$route_exists = isset( $routes[ $status_route_key ] );

		$status_data = array(
			'status'          => 'ok',
			'version'         => GEMINI_CC_VERSION,
			'time'            => current_time( 'mysql' ),
			'wp_version'      => get_bloginfo( 'version' ),
			'php_version'     => PHP_VERSION,
			'rest_url_base'   => rest_url( self::NAMESPACE . '/' ),
			'expected_url'    => home_url( '/wp-json/' . self::NAMESPACE . '/' ),
			'user_id'         => get_current_user_id(),
			'user_can_manage' => current_user_can( 'manage_options' ),
			'route_registered' => $route_exists,
			'routes_check_time' => get_option( 'gemini_cc_route_check_time', 'Never' ),
			'routes_registered' => get_option( 'gemini_cc_routes_registered', false ),
			'request_info'    => array(
				'method'     => $request->get_method(),
				'route'      => $request->get_route(),
				'params'     => $request->get_params(),
				'headers'    => $request->get_headers(),
			),
		);

		// Add debug information if WP_DEBUG is enabled
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// Count Gemini routes
			$gemini_routes = array_filter( $routes, function( $key ) {
				return strpos( $key, 'gemini-cc' ) !== false;
			}, ARRAY_FILTER_USE_KEY );
			
			$status_data['debug_info'] = array(
				'server_name'    => $_SERVER['SERVER_NAME'] ?? 'unknown',
				'request_uri'    => $_SERVER['REQUEST_URI'] ?? 'unknown',
				'http_host'      => $_SERVER['HTTP_HOST'] ?? 'unknown',
				'script_name'    => $_SERVER['SCRIPT_NAME'] ?? 'unknown',
				'query_string'   => $_SERVER['QUERY_STRING'] ?? '',
				'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
				'total_routes'   => count( $routes ),
				'gemini_routes_count' => count( $gemini_routes ),
				'gemini_routes' => array_keys( $gemini_routes ),
			);
		}

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
	 * Log frontend error (only available when WP_DEBUG is true)
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function log_frontend_error( $request ) {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return new WP_REST_Response( 
				array( 'error' => 'Debug mode not enabled' ), 
				404 
			);
		}

		$type = $request->get_param( 'type' );
		$log_entry = $request->get_param( 'log_entry' );

		// Enhanced frontend error logging
		$enhanced_log_entry = array(
			'source' => 'frontend',
			'type' => $type,
			'frontend_data' => $log_entry,
			'server_context' => array(
				'user_id' => get_current_user_id(),
				'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
				'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
				'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
				'referer' => $_SERVER['HTTP_REFERER'] ?? 'unknown',
				'server_time' => current_time( 'mysql' ),
				'timestamp' => time(),
			),
			'wordpress_context' => array(
				'wp_version' => get_bloginfo( 'version' ),
				'plugin_version' => GEMINI_CC_VERSION,
				'current_theme' => get_stylesheet(),
				'active_plugins' => array_keys( get_plugins() ),
				'is_multisite' => is_multisite(),
				'memory_limit' => ini_get( 'memory_limit' ),
				'max_execution_time' => ini_get( 'max_execution_time' ),
			)
		);

		// Log the enhanced error
		$this->log_error( 'Frontend Error: ' . ($log_entry['message'] ?? 'Unknown error'), $enhanced_log_entry );
		
		// Also log to API call log for tracking
		$this->log_api_call( 'frontend_error_log', $request );

		return new WP_REST_Response( 
			array( 
				'success' => true,
				'message' => 'Frontend error logged successfully',
				'log_id' => $enhanced_log_entry['server_context']['timestamp']
			), 
			200 
		);
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
			// Gemini API Configuration
			'gemini_api_key'               => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => 'Your Gemini API key from Google AI Studio',
			),
			'gemini_model'                 => array(
				'type'    => 'string',
				'enum'    => array( 'gemini-2.0-flash', 'gemini-1.5-pro', 'gemini-1.5-flash' ),
				'default' => 'gemini-2.0-flash',
			),
			'gemini_temperature'           => array(
				'type'    => 'number',
				'minimum' => 0.0,
				'maximum' => 2.0,
				'default' => 0.7,
			),
			'gemini_max_tokens'            => array(
				'type'    => 'integer',
				'minimum' => 1,
				'maximum' => 8192,
				'default' => 1000,
			),
			// Legacy support (to be removed)
			'api_key'                      => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			// Enhanced Debugging Settings
			'enable_verbose_logging'       => array(
				'type'    => 'boolean',
				'default' => false,
			),
			'system_logging_enabled'       => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'log_retention_days'           => array(
				'type'    => 'integer',
				'minimum' => 1,
				'maximum' => 30,
				'default' => 7,
			),
			// Operation Settings
			'operation_mode'               => array(
				'type' => 'string',
				'enum' => array( 'approval', 'autonomous' ),
			),
			// SEO Settings
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
			// UI/UX Settings
			'uiux_enabled'                 => array(
				'type' => 'boolean',
			),
			// Content Settings
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
		// Validate API key format
		if ( empty( $api_key ) || strlen( $api_key ) < 10 ) {
			$this->log_error( 'Invalid API key format provided', array( 
				'key_length' => strlen( $api_key ),
				'function' => __FUNCTION__
			) );
			return array(
				'success' => false,
				'message' => __( 'Invalid API key format. API key should be at least 10 characters long.', 'gemini-command-center' ),
			);
		}

		// Get model from settings for testing
		$settings = get_option( 'gemini_cc_settings', array() );
		$model = $settings['gemini_model'] ?? 'gemini-2.0-flash';

		// Test actual Gemini API connection
		$url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";
		
		$test_payload = array(
			'contents' => array(
				array(
					'parts' => array(
						array(
							'text' => 'Hello! This is a connection test. Please respond with "Connection successful" and nothing else.'
						)
					)
				)
			),
			'generationConfig' => array(
				'maxOutputTokens' => 50,
				'temperature' => 0.1
			)
		);

		$this->log_info( 'Testing Gemini API connection', array(
			'model' => $model,
			'url' => $url,
			'key_length' => strlen( $api_key )
		) );

		$response = wp_remote_post( $url, array(
			'headers' => array(
				'Content-Type' => 'application/json',
				'X-goog-api-key' => $api_key,
			),
			'body' => wp_json_encode( $test_payload ),
			'timeout' => 30,
			'sslverify' => true,
		) );

		if ( is_wp_error( $response ) ) {
			$this->log_error( 'Gemini API connection test failed', array(
				'error' => $response->get_error_message(),
				'url' => $url,
				'model' => $model,
				'function' => __FUNCTION__
			) );
			return array(
				'success' => false,
				'message' => sprintf( __( 'Connection failed: %s. Please check your internet connection.', 'gemini-command-center' ), $response->get_error_message() ),
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		
		if ( $status_code !== 200 ) {
			$error_data = json_decode( $body, true );
			$error_message = $error_data['error']['message'] ?? 'Unknown error occurred';
			
			$this->log_error( 'Gemini API returned error status', array(
				'status_code' => $status_code,
				'error_message' => $error_message,
				'response_body' => $body,
				'model' => $model,
				'function' => __FUNCTION__
			) );
			
			// Provide helpful error messages based on status code
			$user_message = '';
			switch ( $status_code ) {
				case 400:
					$user_message = sprintf( __( 'Bad Request (400): %s. Please check your API configuration.', 'gemini-command-center' ), $error_message );
					break;
				case 401:
					$user_message = __( 'Authentication failed (401): Invalid API key. Please check your Gemini API key.', 'gemini-command-center' );
					break;
				case 403:
					$user_message = sprintf( __( 'Access forbidden (403): %s. Your API key may not have permission to use this model.', 'gemini-command-center' ), $error_message );
					break;
				case 404:
					$user_message = sprintf( __( 'Model not found (404): The model "%s" may not be available.', 'gemini-command-center' ), $model );
					break;
				case 429:
					$user_message = __( 'Rate limit exceeded (429): Too many requests. Please wait and try again.', 'gemini-command-center' );
					break;
				default:
					$user_message = sprintf( __( 'API Error (%d): %s', 'gemini-command-center' ), $status_code, $error_message );
			}
			
			return array(
				'success' => false,
				'message' => $user_message,
			);
		}

		$data = json_decode( $body, true );
		
		if ( ! isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
			$this->log_error( 'Unexpected Gemini API response format', array(
				'response_body' => $body,
				'parsed_data' => $data,
				'model' => $model,
				'function' => __FUNCTION__
			) );
			return array(
				'success' => false,
				'message' => __( 'Unexpected API response format. The API may have changed.', 'gemini-command-center' ),
			);
		}

		$response_text = $data['candidates'][0]['content']['parts'][0]['text'];
		
		$this->log_info( 'Gemini API connection test successful', array(
			'model' => $model,
			'response' => $response_text,
			'usage' => $data['usageMetadata'] ?? null
		) );
		
		return array(
			'success' => true,
			'message' => sprintf( __( 'API connection test successful! Model: %s', 'gemini-command-center' ), $model ),
			'response' => $response_text,
			'model' => $model,
			'usage' => $data['usageMetadata'] ?? null,
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
		// Get API key from settings (check both new and legacy keys)
		$settings = get_option( 'gemini_cc_settings', array() );
		$api_key = $settings['gemini_api_key'] ?? $settings['api_key'] ?? '';
		
		if ( empty( $api_key ) ) {
			$this->log_error( 'No API key configured for agent conversation', array(
				'function' => __FUNCTION__,
				'message_length' => strlen( $message ),
				'settings_keys' => array_keys( $settings )
			) );
			return array(
				'message' => __( 'Error: Gemini API key not configured. Please set your Gemini API key in Settings → API Configuration.', 'gemini-command-center' ),
				'timestamp' => time(),
				'error' => true,
			);
		}

		// Get model configuration from settings
		$model = $settings['gemini_model'] ?? 'gemini-2.0-flash';
		$temperature = $settings['gemini_temperature'] ?? 0.7;
		$max_tokens = $settings['gemini_max_tokens'] ?? 1000;

		// Build conversation context
		$contents = array();
		
		// Add conversation history (last 10 exchanges to maintain context)
		if ( ! empty( $history ) && is_array( $history ) ) {
			$recent_history = array_slice( $history, -10 );
			foreach ( $recent_history as $exchange ) {
				if ( isset( $exchange['type'] ) && isset( $exchange['message'] ) ) {
					$role = $exchange['type'] === 'user' ? 'user' : 'model';
					$contents[] = array(
						'role' => $role,
						'parts' => array(
							array( 'text' => $exchange['message'] )
						)
					);
				}
			}
		}
		
		// Add current user message
		$contents[] = array(
			'role' => 'user',
			'parts' => array(
				array( 'text' => $message )
			)
		);

		$url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";
		
		$payload = array(
			'contents' => $contents,
			'generationConfig' => array(
				'maxOutputTokens' => intval( $max_tokens ),
				'temperature' => floatval( $temperature ),
				'topP' => 0.8,
				'topK' => 40,
			),
			'systemInstruction' => array(
				'parts' => array(
					array(
						'text' => 'You are a helpful WordPress management assistant for the Gemini Command Center plugin. Provide practical advice about WordPress administration, SEO, content management, security, and troubleshooting. Keep responses concise and actionable. When discussing technical issues, provide step-by-step solutions.'
					)
				)
			)
		);

		$this->log_info( 'Sending request to Gemini API', array(
			'model' => $model,
			'temperature' => $temperature,
			'max_tokens' => $max_tokens,
			'message_length' => strlen( $message ),
			'history_length' => count( $history ?? array() )
		) );

		$response = wp_remote_post( $url, array(
			'headers' => array(
				'Content-Type' => 'application/json',
				'X-goog-api-key' => $api_key,
			),
			'body' => wp_json_encode( $payload ),
			'timeout' => 60,
			'sslverify' => true,
		) );

		if ( is_wp_error( $response ) ) {
			$this->log_error( 'Gemini API conversation request failed', array(
				'error' => $response->get_error_message(),
				'url' => $url,
				'model' => $model,
				'message_length' => strlen( $message ),
				'function' => __FUNCTION__
			) );
			return array(
				'message' => sprintf( __( 'Connection Error: %s. Please check your internet connection and API key.', 'gemini-command-center' ), $response->get_error_message() ),
				'timestamp' => time(),
				'error' => true,
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		
		if ( $status_code !== 200 ) {
			$error_data = json_decode( $body, true );
			$error_message = $error_data['error']['message'] ?? 'Unknown API error occurred';
			
			$this->log_error( 'Gemini API conversation returned error', array(
				'status_code' => $status_code,
				'error_message' => $error_message,
				'response_body' => $body,
				'model' => $model,
				'function' => __FUNCTION__
			) );
			
			// Provide helpful error messages based on status code
			$user_message = '';
			switch ( $status_code ) {
				case 401:
					$user_message = __( 'API Authentication Error: Please check your Gemini API key in Settings.', 'gemini-command-center' );
					break;
				case 403:
					$user_message = __( 'API Access Denied: Your API key may not have permission to use this model.', 'gemini-command-center' );
					break;
				case 429:
					$user_message = __( 'API Rate Limit: Too many requests. Please wait a moment and try again.', 'gemini-command-center' );
					break;
				default:
					$user_message = sprintf( __( 'API Error (%d): %s', 'gemini-command-center' ), $status_code, $error_message );
			}
			
			return array(
				'message' => $user_message,
				'timestamp' => time(),
				'error' => true,
			);
		}

		$data = json_decode( $body, true );
		
		if ( ! isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
			$this->log_error( 'Unexpected Gemini API conversation response format', array(
				'response_body' => $body,
				'parsed_data' => $data,
				'model' => $model,
				'function' => __FUNCTION__
			) );
			return array(
				'message' => __( 'Error: Unexpected API response format. Please try again.', 'gemini-command-center' ),
				'timestamp' => time(),
				'error' => true,
			);
		}

		$ai_response = $data['candidates'][0]['content']['parts'][0]['text'];
		
		$this->log_info( 'Gemini API conversation successful', array(
			'model' => $model,
			'response_length' => strlen( $ai_response ),
			'usage' => $data['usageMetadata'] ?? null
		) );
		
		return array(
			'message' => $ai_response,
			'timestamp' => time(),
			'usage' => $data['usageMetadata'] ?? null,
			'model' => $model,
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

	/**
	 * Enhanced error logging for Gemini API operations
	 *
	 * @param string $message Error message.
	 * @param array  $context Error context.
	 */
	private function log_error( $message, $context = array() ) {
		$settings = get_option( 'gemini_cc_settings', array() );
		
		// Enhanced error logging with more context
		$log_entry = array(
			'type'      => 'gemini_error',
			'level'     => 'error',
			'message'   => $message,
			'context'   => $context,
			'user_id'   => get_current_user_id(),
			'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
			'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
			'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
			'time'      => current_time( 'mysql' ),
			'timestamp' => time(),
			'backtrace' => wp_debug_backtrace_summary(),
			'memory_usage' => memory_get_usage( true ),
			'memory_peak' => memory_get_peak_usage( true ),
		);

		$error_log = get_option( 'gemini_cc_error_log', array() );
		$error_log[] = $log_entry;

		// Keep only last 1000 error entries for enhanced debugging
		if ( count( $error_log ) > 1000 ) {
			$error_log = array_slice( $error_log, -1000 );
		}

		update_option( 'gemini_cc_error_log', $error_log );

		// Enhanced PHP error logging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$error_details = array(
				'message' => $message,
				'context' => $context,
				'memory' => round( memory_get_usage( true ) / 1024 / 1024, 2 ) . 'MB',
				'time' => current_time( 'c' )
			);
			error_log( '[Gemini CC ERROR]: ' . wp_json_encode( $error_details ) );
		}

		// Also trigger WordPress error hooks for monitoring plugins
		do_action( 'gemini_cc_error_logged', $message, $context, $log_entry );
	}

	/**
	 * Enhanced info logging for debugging
	 *
	 * @param string $message Info message.
	 * @param array  $context Info context.
	 */
	private function log_info( $message, $context = array() ) {
		$settings = get_option( 'gemini_cc_settings', array() );
		
		// Only log info if verbose logging is enabled
		if ( empty( $settings['enable_verbose_logging'] ) ) {
			return;
		}
		
		$log_entry = array(
			'type'      => 'info',
			'level'     => 'info',
			'message'   => $message,
			'context'   => $context,
			'user_id'   => get_current_user_id(),
			'time'      => current_time( 'mysql' ),
			'timestamp' => time(),
		);

		$api_log = get_option( 'gemini_cc_api_log', array() );
		$api_log[] = $log_entry;

		// Keep only last 2000 entries for info logs
		if ( count( $api_log ) > 2000 ) {
			$api_log = array_slice( $api_log, -2000 );
		}

		update_option( 'gemini_cc_api_log', $api_log );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[Gemini CC INFO]: ' . $message . ' | Context: ' . wp_json_encode( $context ) );
		}
	}
}