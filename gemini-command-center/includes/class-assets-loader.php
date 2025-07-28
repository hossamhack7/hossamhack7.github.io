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

			// Set up API fetch with nonce
			if (window.gemini_cc_data && window.gemini_cc_data.nonce) {
				wp.apiFetch.use((options, next) => {
					options.headers = {
						...options.headers,
						'X-WP-Nonce': window.gemini_cc_data.nonce,
					};
					return next(options);
				});
			}

			// Basic React app
			const { createElement: e, useState, useEffect } = React;

			// Main App Component
			function GeminiCommandCenter() {
				const [loading, setLoading] = useState(true);
				const [status, setStatus] = useState(null);
				const [activeTab, setActiveTab] = useState('dashboard');

				useEffect(() => {
					// Test API connection
					wp.apiFetch({
						path: 'gemini-cc/v1/status',
						method: 'GET',
					})
					.then(response => {
						setStatus(response);
						setLoading(false);
					})
					.catch(error => {
						console.error('API Error:', error);
						setStatus({ status: 'error', message: error.message });
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
							status.status === 'ok' ? 'API Connected Successfully!' : 'API Connection Error: ' + (status.message || 'Unknown error')
						)
					),
					e(TabNavigation, { activeTab, setActiveTab }),
					e(TabContent, { activeTab })
				);
			}

			// Tab Navigation Component
			function TabNavigation({ activeTab, setActiveTab }) {
				const tabs = [
					{ id: 'dashboard', label: 'Dashboard' },
					{ id: 'settings', label: 'Settings' },
					{ id: 'seo', label: 'SEO Center' },
					{ id: 'content', label: 'Content' },
					{ id: 'backup', label: 'Backup' },
					{ id: 'reports', label: 'Reports' }
				];

				return e('div', { className: 'gemini-cc-tabs' },
					tabs.map(tab => 
						e('div', {
							key: tab.id,
							className: 'gemini-cc-tab' + (activeTab === tab.id ? ' active' : ''),
							onClick: () => setActiveTab(tab.id)
						}, tab.label)
					)
				);
			}

			// Tab Content Component
			function TabContent({ activeTab }) {
				switch (activeTab) {
					case 'dashboard':
						return e(Dashboard);
					case 'settings':
						return e(Settings);
					case 'seo':
						return e(SEOCenter);
					case 'content':
						return e(ContentCenter);
					case 'backup':
						return e(BackupCenter);
					case 'reports':
						return e(ReportsCenter);
					default:
						return e('div', { className: 'gemini-cc-tab-content' },
							e('h3', null, 'Welcome to Gemini Command Center'),
							e('p', null, 'Select a tab to get started.')
						);
				}
			}

			// Dashboard Component
			function Dashboard() {
				return e('div', { className: 'gemini-cc-tab-content' },
					e('h3', null, 'Action Center Dashboard'),
					e('p', null, 'Your command center for AI-powered WordPress management.'),
					e('div', { className: 'gemini-cc-cards' },
						e('div', { className: 'card' },
							e('h4', null, 'Quick Actions'),
							e('button', { className: 'button button-primary' }, 'Run SEO Audit'),
							e('button', { className: 'button' }, 'Generate Content'),
							e('button', { className: 'button' }, 'Create Backup')
						)
					)
				);
			}

			// Settings Component
			function Settings() {
				const [settings, setSettings] = useState(null);
				const [saving, setSaving] = useState(false);

				useEffect(() => {
					wp.apiFetch({
						path: 'gemini-cc/v1/settings',
						method: 'GET',
					})
					.then(response => {
						setSettings(response || {});
					})
					.catch(error => {
						console.error('Settings Error:', error);
					});
				}, []);

				if (!settings) {
					return e('div', { className: 'gemini-cc-loading' }, 'Loading settings...');
				}

				return e('div', { className: 'gemini-cc-tab-content' },
					e('h3', null, 'Settings'),
					e('form', {
						onSubmit: (e) => {
							e.preventDefault();
							setSaving(true);
							wp.apiFetch({
								path: 'gemini-cc/v1/settings',
								method: 'POST',
								data: settings,
							})
							.then(() => {
								setSaving(false);
								alert('Settings saved!');
							})
							.catch(error => {
								setSaving(false);
								console.error('Save Error:', error);
							});
						}
					},
						e('table', { className: 'form-table' },
							e('tr', null,
								e('th', { scope: 'row' }, 'API Key'),
								e('td', null,
									e('input', {
										type: 'password',
										value: settings.api_key || '',
										onChange: (e) => setSettings({...settings, api_key: e.target.value}),
										className: 'regular-text'
									})
								)
							)
						),
						e('p', { className: 'submit' },
							e('button', {
								type: 'submit',
								className: 'button button-primary',
								disabled: saving
							}, saving ? 'Saving...' : 'Save Settings')
						)
					)
				);
			}

			// Placeholder components
			function SEOCenter() {
				return e('div', { className: 'gemini-cc-tab-content' },
					e('h3', null, 'SEO Command Center'),
					e('p', null, 'Technical audits, link building, and competitive analysis.')
				);
			}

			function ContentCenter() {
				return e('div', { className: 'gemini-cc-tab-content' },
					e('h3', null, 'Content Management'),
					e('p', null, 'AI writing, optimization, and social amplification.')
				);
			}

			function BackupCenter() {
				return e('div', { className: 'gemini-cc-tab-content' },
					e('h3', null, 'Backup & Diagnostics'),
					e('p', null, 'Secure backups and system health monitoring.')
				);
			}

			function ReportsCenter() {
				return e('div', { className: 'gemini-cc-tab-content' },
					e('h3', null, 'Reports & Analytics'),
					e('p', null, 'Performance tracking and impact measurement.')
				);
			}

			// AI Agent Component for agent page
			function GeminiAgent() {
				const [messages, setMessages] = useState([]);
				const [input, setInput] = useState('');
				const [sending, setSending] = useState(false);

				const sendMessage = () => {
					if (!input.trim() || sending) return;

					const userMessage = { role: 'user', content: input, timestamp: Date.now() };
					setMessages(prev => [...prev, userMessage]);
					setSending(true);
					setInput('');

					wp.apiFetch({
						path: 'gemini-cc/v1/agent/converse',
						method: 'POST',
						data: {
							message: input,
							history: messages
						},
					})
					.then(response => {
						const agentMessage = { role: 'agent', content: response.message, timestamp: Date.now() };
						setMessages(prev => [...prev, agentMessage]);
						setSending(false);
					})
					.catch(error => {
						console.error('Agent Error:', error);
						const errorMessage = { role: 'agent', content: 'Sorry, I encountered an error. Please try again.', timestamp: Date.now() };
						setMessages(prev => [...prev, errorMessage]);
						setSending(false);
					});
				};

				return e('div', { className: 'gemini-cc-container' },
					e('div', { className: 'gemini-agent-chat' },
						e('div', { className: 'chat-messages' },
							messages.length === 0 && e('div', { className: 'chat-welcome' },
								e('h3', null, 'Welcome to Gemini AI Agent'),
								e('p', null, 'I can help you manage your WordPress site. Ask me anything!')
							),
							messages.map(msg => 
								e('div', {
									key: msg.timestamp,
									className: 'chat-message ' + msg.role
								},
									e('strong', null, msg.role === 'user' ? 'You: ' : 'Gemini: '),
									msg.content
								)
							),
							sending && e('div', { className: 'chat-message agent' },
								e('strong', null, 'Gemini: '),
								'Thinking...'
							)
						),
						e('div', { className: 'chat-input' },
							e('input', {
								type: 'text',
								value: input,
								onChange: (e) => setInput(e.target.value),
								onKeyPress: (e) => e.key === 'Enter' && sendMessage(),
								placeholder: 'Ask me anything about your site...',
								disabled: sending
							}),
							e('button', {
								onClick: sendMessage,
								disabled: !input.trim() || sending,
								className: 'button button-primary'
							}, 'Send')
						)
					)
				);
			}

			// Initialize the appropriate component based on page
			document.addEventListener('DOMContentLoaded', function() {
				const rootElement = document.getElementById('gcc-react-root');
				const agentElement = document.getElementById('gcc-agent-root');

				if (rootElement) {
					ReactDOM.render(e(GeminiCommandCenter), rootElement);
				}

				if (agentElement) {
					ReactDOM.render(e(GeminiAgent), agentElement);
				}
			});

		})();
		";
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