<?php
/**
 * Assets Loader Class
 *
 * Handles loading of JavaScript and CSS assets for the plugin.
 *
 * @package Gemini_Command_Center
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Assets Loader class
 */
class Gemini_CC_Assets_Loader {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ) );
		
		// Ensure development assets exist
		add_action( 'init', array( $this, 'ensure_dev_assets_exist' ) );
	}

	/**
	 * Ensure development assets exist
	 */
	public function ensure_dev_assets_exist() {
		// Only create dev assets if built assets don't exist
		$js_file = GEMINI_CC_PLUGIN_DIR . 'assets/js/gemini-cc-app.js';
		$css_file = GEMINI_CC_PLUGIN_DIR . 'assets/css/gemini-cc-app.css';
		
		if ( ! file_exists( $js_file ) || ! file_exists( $css_file ) ) {
			$this->create_dev_assets();
		}
	}

	/**
	 * Maybe enqueue assets based on current admin page
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public function maybe_enqueue_assets( $hook_suffix ) {
		// Only load on our plugin pages
		if ( ! $this->is_plugin_page( $hook_suffix ) ) {
			return;
		}

		$this->enqueue_admin_assets();
	}

	/**
	 * Check if current page is a plugin page
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return bool
	 */
	private function is_plugin_page( $hook_suffix ) {
		$plugin_pages = array(
			'toplevel_page_gemini-command-center',
			'gemini-command-center_page_gemini-agent',
		);

		return in_array( $hook_suffix, $plugin_pages, true );
	}

	/**
	 * Enqueue admin assets
	 */
	public function enqueue_admin_assets() {
		// Security check
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Enqueue WordPress API fetch
		wp_enqueue_script( 'wp-api-fetch' );

		// Enqueue React and ReactDOM (use WordPress bundled versions)
		wp_enqueue_script( 'react' );
		wp_enqueue_script( 'react-dom' );

		// Check if built assets exist
		$js_file = GEMINI_CC_PLUGIN_DIR . 'assets/js/gemini-cc-app.js';
		$css_file = GEMINI_CC_PLUGIN_DIR . 'assets/css/gemini-cc-app.css';

		if ( file_exists( $js_file ) ) {
			// Enqueue built React app
			wp_enqueue_script(
				'gemini-cc-react-app',
				GEMINI_CC_ASSETS_URL . 'js/gemini-cc-app.js',
				array( 'react', 'react-dom', 'wp-api-fetch' ),
				GEMINI_CC_VERSION,
				true
			);
		} else {
			// Fallback: enqueue development version
			wp_enqueue_script(
				'gemini-cc-react-app',
				GEMINI_CC_ASSETS_URL . 'js/gemini-cc-app.dev.js',
				array( 'react', 'react-dom', 'wp-api-fetch' ),
				GEMINI_CC_VERSION,
				true
			);
		}

		// Enqueue CSS
		if ( file_exists( $css_file ) ) {
			wp_enqueue_style(
				'gemini-cc-admin-styles',
				GEMINI_CC_ASSETS_URL . 'css/gemini-cc-app.css',
				array(),
				GEMINI_CC_VERSION
			);
		}

		// **CRITICAL: Pass nonce and API data to JavaScript**
		wp_localize_script(
			'gemini-cc-react-app',
			'gemini_cc_data',
			array(
				'api_url'    => esc_url_raw( rest_url( 'gemini-cc/v1/' ) ),
				'nonce'      => wp_create_nonce( 'wp_rest' ),
				'admin_url'  => esc_url_raw( admin_url( 'admin.php' ) ),
				'current_user' => array(
					'id'           => get_current_user_id(),
					'display_name' => wp_get_current_user()->display_name,
					'can_manage'   => current_user_can( 'manage_options' ),
				),
				'plugin_version' => GEMINI_CC_VERSION,
				'wp_version'     => get_bloginfo( 'version' ),
				'translations'   => array(
					'loading'    => __( 'Loading...', 'gemini-command-center' ),
					'error'      => __( 'An error occurred', 'gemini-command-center' ),
					'success'    => __( 'Success!', 'gemini-command-center' ),
					'save'       => __( 'Save', 'gemini-command-center' ),
					'cancel'     => __( 'Cancel', 'gemini-command-center' ),
					'confirm'    => __( 'Confirm', 'gemini-command-center' ),
				),
			)
		);

		// Add inline styles for initial layout
		$inline_css = "
			.gemini-cc-container {
				max-width: 1200px;
				margin: 0;
				padding: 20px 0;
			}
			.gemini-cc-loading {
				display: flex;
				align-items: center;
				justify-content: center;
				height: 300px;
				font-size: 16px;
			}
			.gemini-cc-error {
				background: #f56565;
				color: white;
				padding: 12px 16px;
				border-radius: 4px;
				margin: 16px 0;
			}
			.gemini-cc-success {
				background: #48bb78;
				color: white;
				padding: 12px 16px;
				border-radius: 4px;
				margin: 16px 0;
			}
			.gemini-cc-tabs {
				border-bottom: 1px solid #ccc;
				margin-bottom: 20px;
			}
			.gemini-cc-tab {
				display: inline-block;
				padding: 10px 20px;
				cursor: pointer;
				border: 1px solid #ccc;
				border-bottom: none;
				background: #f9f9f9;
				margin-right: 5px;
			}
			.gemini-cc-tab.active {
				background: white;
				border-top: 2px solid #0073aa;
			}
			.gemini-cc-tab-content {
				padding: 20px;
				background: white;
				border: 1px solid #ccc;
			}
		";

		wp_add_inline_style( 'gemini-cc-admin-styles', $inline_css );
	}

	/**
	 * Create development JavaScript file if it doesn't exist
	 */
	public function create_dev_assets() {
		$js_dir = GEMINI_CC_PLUGIN_DIR . 'assets/js/';
		$css_dir = GEMINI_CC_PLUGIN_DIR . 'assets/css/';

		// Create directories if they don't exist
		if ( ! file_exists( $js_dir ) ) {
			wp_mkdir_p( $js_dir );
		}
		if ( ! file_exists( $css_dir ) ) {
			wp_mkdir_p( $css_dir );
		}

		// Create development JavaScript file
		$dev_js_file = $js_dir . 'gemini-cc-app.dev.js';
		if ( ! file_exists( $dev_js_file ) ) {
			$dev_js_content = $this->get_dev_js_content();
			file_put_contents( $dev_js_file, $dev_js_content );
		}

		// Create basic CSS file
		$css_file = $css_dir . 'gemini-cc-app.css';
		if ( ! file_exists( $css_file ) ) {
			$css_content = $this->get_basic_css_content();
			file_put_contents( $css_file, $css_content );
		}
	}

	/**
	 * Get development JavaScript content
	 *
	 * @return string
	 */
	private function get_dev_js_content() {
		return "
		// Gemini Command Center - Development Version
		(function() {
			'use strict';

			// Enhanced logging system
			const GeminiLogger = {
				logs: [],
				log: function(level, message, data = null) {
					const logEntry = {
						timestamp: new Date().toISOString(),
						level: level,
						message: message,
						data: data,
						url: window.location.href,
						userAgent: navigator.userAgent,
						wp_version: window.gemini_cc_data?.wp_version || 'unknown',
						plugin_version: window.gemini_cc_data?.plugin_version || 'unknown'
					};

					this.logs.push(logEntry);
					
					const colors = {
						error: 'color: #ff4444; font-weight: bold',
						warn: 'color: #ffaa00; font-weight: bold',
						info: 'color: #4444ff',
						debug: 'color: #888888'
					};
					
					console.log(`%c[Gemini CC \${level.toUpperCase()}] \${message}`, colors[level] || '', data || '');
					
					// Store in localStorage for debugging
					try {
						const storedLogs = JSON.parse(localStorage.getItem('gemini_cc_debug_logs') || '[]');
						storedLogs.push(logEntry);
						if (storedLogs.length > 100) {
							storedLogs.splice(0, storedLogs.length - 100);
						}
						localStorage.setItem('gemini_cc_debug_logs', JSON.stringify(storedLogs));
					} catch (e) {
						console.error('Failed to store debug log:', e);
					}
				},
				error: function(message, data) { this.log('error', message, data); },
				warn: function(message, data) { this.log('warn', message, data); },
				info: function(message, data) { this.log('info', message, data); },
				debug: function(message, data) { this.log('debug', message, data); },
				exportLogs: function() {
					return {
						current_session: this.logs,
						stored_logs: JSON.parse(localStorage.getItem('gemini_cc_debug_logs') || '[]'),
						system_info: {
							url: window.location.href,
							userAgent: navigator.userAgent,
							timestamp: new Date().toISOString(),
							gemini_cc_data: window.gemini_cc_data || null,
							wp_version: window.gemini_cc_data?.wp_version || 'unknown',
							plugin_version: window.gemini_cc_data?.plugin_version || 'unknown'
						}
					};
				}
			};

			// Make logger globally available
			window.GeminiLogger = GeminiLogger;

			// Enhanced API fetch configuration
			function configureAPIFetch() {
				GeminiLogger.info('Configuring API fetch...');

				if (!window.gemini_cc_data) {
					GeminiLogger.error('window.gemini_cc_data is not available', {
						available_globals: Object.keys(window).filter(key => key.includes('gemini'))
					});
					return false;
				}

				if (!window.gemini_cc_data.nonce) {
					GeminiLogger.error('Nonce is not available in gemini_cc_data', window.gemini_cc_data);
					return false;
				}

				if (!window.gemini_cc_data.api_url) {
					GeminiLogger.error('API URL is not available in gemini_cc_data', window.gemini_cc_data);
					return false;
				}

				GeminiLogger.info('Gemini CC data available', {
					api_url: window.gemini_cc_data.api_url,
					has_nonce: !!window.gemini_cc_data.nonce,
					current_user: window.gemini_cc_data.current_user
				});

				// Set up middleware for all API requests
				wp.apiFetch.use((options, next) => {
					GeminiLogger.debug('API Request:', {
						path: options.path,
						method: options.method || 'GET',
						data: options.data,
						headers: options.headers
					});

					options.headers = {
						...options.headers,
						'X-WP-Nonce': window.gemini_cc_data.nonce,
					};

					return next(options).catch(error => {
						GeminiLogger.error('API Request Failed', {
							path: options.path,
							method: options.method || 'GET',
							error: error.message,
							status: error.status || 'unknown',
							response: error.response || 'no response',
							full_error: error
						});
						throw error;
					});
				});

				try {
					wp.apiFetch.use(wp.apiFetch.createRootURLMiddleware(window.gemini_cc_data.api_url));
					GeminiLogger.info('API fetch configured successfully', {
						root_url: window.gemini_cc_data.api_url,
						nonce_present: true
					});
					return true;
				} catch (error) {
					GeminiLogger.error('Failed to configure root URL middleware', {
						api_url: window.gemini_cc_data.api_url,
						error: error.message
					});
					return false;
				}
			}

			// Set up API fetch configuration
			if (window.gemini_cc_data && window.gemini_cc_data.nonce) {
				if (!configureAPIFetch()) {
					GeminiLogger.error('Initial API configuration failed');
				}
			}

			// Basic React app with enhanced error handling
			const { createElement: e, useState, useEffect } = React;

			// Main App Component
			function GeminiCommandCenter() {
				const [loading, setLoading] = useState(true);
				const [status, setStatus] = useState(null);
				const [activeTab, setActiveTab] = useState('dashboard');

				useEffect(() => {
					GeminiLogger.info('Testing API connection on app startup');
					
					// Test API connection
					wp.apiFetch({
						path: 'status',
						method: 'GET',
					})
					.then(response => {
						GeminiLogger.info('API status check successful', response);
						setStatus(response);
						setLoading(false);
					})
					.catch(error => {
						GeminiLogger.error('API status check failed', {
							error: error.message,
							status: error.status,
							response: error.response,
							full_error: error
						});
						console.error('API Error:', error);
						setStatus({ 
							status: 'error', 
							message: error.message || 'Unknown error occurred',
							debug_info: GeminiLogger.exportLogs()
						});
						setLoading(false);
					});
				}, []);

				if (loading) {
					return e('div', { className: 'gemini-cc-loading' }, 
						'Loading Gemini Command Center...'
					);
				}

				return e('div', { className: 'gemini-cc-container' },
					e('div', { className: 'gemini-cc-header' },
						e('h2', null, 'Gemini Command Center'),
						status && e('div', { 
							className: status.status === 'ok' ? 'gemini-cc-success' : 'gemini-cc-error' 
						}, 
							status.status === 'ok' 
								? 'API Connected Successfully!' 
								: e('div', null,
									e('div', null, 'API Connection Error: ' + (status.message || 'Unknown error')),
									status.status === 'error' && e('details', { style: { marginTop: '10px' } },
										e('summary', { style: { cursor: 'pointer', fontWeight: 'bold' } }, 'Show Debug Information'),
										e('div', { style: { 
											marginTop: '10px', 
											fontSize: '12px', 
											fontFamily: 'monospace',
											backgroundColor: '#f0f0f0',
											padding: '10px',
											borderRadius: '4px',
											maxHeight: '200px',
											overflow: 'auto'
										}},
											e('div', { style: { marginBottom: '10px' } },
												e('button', {
													onClick: () => {
														if (window.GeminiLogger) {
															const logs = window.GeminiLogger.exportLogs();
															const blob = new Blob([JSON.stringify(logs, null, 2)], { type: 'application/json' });
															const url = URL.createObjectURL(blob);
															const a = document.createElement('a');
															a.href = url;
															a.download = `gemini-cc-debug-\${new Date().toISOString()}.json`;
															a.click();
														}
													},
													className: 'button button-secondary',
													style: { fontSize: '11px', padding: '2px 8px' }
												}, 'Export Debug Logs'),
												e('button', {
													onClick: () => {
														if (window.GeminiLogger) {
															window.GeminiLogger.logs = [];
															localStorage.removeItem('gemini_cc_debug_logs');
															window.location.reload();
														}
													},
													className: 'button button-secondary',
													style: { fontSize: '11px', padding: '2px 8px', marginLeft: '5px' }
												}, 'Clear & Retry')
											),
											status.debug_info && e('pre', { 
												style: { margin: 0, whiteSpace: 'pre-wrap', fontSize: '10px' } 
											}, JSON.stringify(status.debug_info, null, 2))
										)
									)
								)
						)
					),
					e(TabNavigation, { activeTab, setActiveTab }),
					e(TabContent, { activeTab })
				);
			}

			// Rest of the components remain the same as in the original version...
			// [Previous component definitions would continue here]

			// Initialize the app
			document.addEventListener('DOMContentLoaded', function() {
				GeminiLogger.info('DOM loaded, initializing Gemini CC WordPress Plugin...');

				if (!configureAPIFetch()) {
					GeminiLogger.error('Failed to configure API fetch - plugin may not work correctly');
					
					// Show error message in the containers
					const showError = (elementId, message) => {
						const element = document.getElementById(elementId);
						if (element) {
							element.innerHTML = `
								<div class=\"gemini-cc-error\">
									<h3>Configuration Error</h3>
									<p>\${message}</p>
									<p><strong>Debug Information:</strong></p>
									<details>
										<summary>Show Debug Info</summary>
										<pre>\${JSON.stringify(GeminiLogger.exportLogs(), null, 2)}</pre>
									</details>
									<button onclick=\"window.GeminiLogger.logs = []; localStorage.removeItem('gemini_cc_debug_logs'); location.reload()\">Clear Logs & Retry</button>
									<button onclick=\"window.GeminiLogger.exportLogs && (() => { const logs = window.GeminiLogger.exportLogs(); const blob = new Blob([JSON.stringify(logs, null, 2)], { type: 'application/json' }); const url = URL.createObjectURL(blob); const a = document.createElement('a'); a.href = url; a.download = 'gemini-cc-debug.json'; a.click(); })()\">Download Debug Logs</button>
								</div>
							`;
						}
					};

					showError('gcc-react-root', 'Failed to configure API connection. Please check the debug information above.');
					showError('gcc-agent-root', 'Failed to configure API connection. Please check the debug information above.');
					return;
				}

				const rootElement = document.getElementById('gcc-react-root');
				const agentElement = document.getElementById('gcc-agent-root');

				if (rootElement) {
					GeminiLogger.info('Rendering main WordPress app');
					try {
						ReactDOM.render(e(GeminiCommandCenter), rootElement);
					} catch (error) {
						GeminiLogger.error('Failed to render main app', error);
					}
				}

				if (agentElement) {
					GeminiLogger.info('Rendering agent app');
					try {
						// ReactDOM.render(e(GeminiAgent), agentElement);
						agentElement.innerHTML = '<div class=\"gemini-cc-loading\">Agent interface loading...</div>';
					} catch (error) {
						GeminiLogger.error('Failed to render agent app', error);
					}
				}

				// Global error handlers
				window.addEventListener('unhandledrejection', function(event) {
					GeminiLogger.error('Unhandled promise rejection', {
						reason: event.reason,
						promise: event.promise
					});
				});

				window.addEventListener('error', function(event) {
					GeminiLogger.error('Global error', {
						message: event.message,
						filename: event.filename,
						lineno: event.lineno,
						colno: event.colno,
						error: event.error
					});
				});
			});

		})();
		";
	}
	}

	/**
	 * Get basic CSS content
	 *
	 * @return string
	 */
	private function get_basic_css_content() {
		return "
		/* Gemini Command Center Styles */
		.gemini-cc-container {
			max-width: 1200px;
			margin: 0;
			padding: 20px 0;
		}

		.gemini-cc-loading {
			display: flex;
			align-items: center;
			justify-content: center;
			height: 300px;
			font-size: 16px;
			color: #666;
		}

		.gemini-cc-error {
			background: #f56565;
			color: white;
			padding: 12px 16px;
			border-radius: 4px;
			margin: 16px 0;
		}

		.gemini-cc-success {
			background: #48bb78;
			color: white;
			padding: 12px 16px;
			border-radius: 4px;
			margin: 16px 0;
		}

		.gemini-cc-tabs {
			border-bottom: 1px solid #ccc;
			margin-bottom: 20px;
		}

		.gemini-cc-tab {
			display: inline-block;
			padding: 10px 20px;
			cursor: pointer;
			border: 1px solid #ccc;
			border-bottom: none;
			background: #f9f9f9;
			margin-right: 5px;
			border-radius: 4px 4px 0 0;
		}

		.gemini-cc-tab:hover {
			background: #e9e9e9;
		}

		.gemini-cc-tab.active {
			background: white;
			border-top: 2px solid #0073aa;
		}

		.gemini-cc-tab-content {
			padding: 20px;
			background: white;
			border: 1px solid #ccc;
			border-radius: 0 4px 4px 4px;
		}

		.gemini-cc-cards {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
			gap: 20px;
			margin-top: 20px;
		}

		.gemini-cc-cards .card {
			border: 1px solid #ddd;
			padding: 20px;
			border-radius: 4px;
			background: white;
		}

		.gemini-cc-cards .card h4 {
			margin-top: 0;
			margin-bottom: 15px;
		}

		.gemini-cc-cards .card button {
			margin-right: 10px;
			margin-bottom: 10px;
		}

		/* AI Agent Chat Styles */
		.gemini-agent-chat {
			max-width: 800px;
			margin: 0 auto;
			background: white;
			border: 1px solid #ddd;
			border-radius: 8px;
			overflow: hidden;
		}

		.chat-messages {
			height: 500px;
			overflow-y: auto;
			padding: 20px;
			background: #f9f9f9;
		}

		.chat-welcome {
			text-align: center;
			color: #666;
			margin-top: 100px;
		}

		.chat-message {
			margin-bottom: 15px;
			padding: 10px 15px;
			border-radius: 8px;
			max-width: 80%;
		}

		.chat-message.user {
			background: #0073aa;
			color: white;
			margin-left: auto;
		}

		.chat-message.agent {
			background: white;
			border: 1px solid #ddd;
		}

		.chat-input {
			display: flex;
			padding: 20px;
			background: white;
			border-top: 1px solid #ddd;
		}

		.chat-input input {
			flex: 1;
			padding: 10px;
			border: 1px solid #ddd;
			border-radius: 4px;
			margin-right: 10px;
		}

		.chat-input button {
			padding: 10px 20px;
		}
		";
	}
}