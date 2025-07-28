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
			
			console.log(`%c[Gemini CC ${level.toUpperCase()}] ${message}`, colors[level] || '', data || '');
			
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
				path: 'settings',
				method: 'GET',
			})
			.then(response => {
				setSettings(response || {});
			})
			.catch(error => {
				GeminiLogger.error('Settings Error:', error);
				setSettings({});
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
						path: 'settings',
						method: 'POST',
						data: settings,
					})
					.then(() => {
						setSaving(false);
						alert('Settings saved!');
					})
					.catch(error => {
						setSaving(false);
						GeminiLogger.error('Save Error:', error);
						alert('Error saving settings. Please try again.');
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

	// Main App Component
	function GeminiCommandCenter() {
		const [loading, setLoading] = useState(true);
		const [status, setStatus] = useState(null);
		const [activeTab, setActiveTab] = useState('dashboard');

		useEffect(() => {
			GeminiLogger.info('Testing API connection on app startup');
			
			// Test API connection using the correct path
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
													a.download = `gemini-cc-debug-${new Date().toISOString()}.json`;
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
				path: 'agent/converse',
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
				GeminiLogger.error('Agent Error:', error);
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
						<div class="gemini-cc-error">
							<h3>Configuration Error</h3>
							<p>${message}</p>
							<p><strong>Debug Information:</strong></p>
							<details>
								<summary>Show Debug Info</summary>
								<pre>${JSON.stringify(GeminiLogger.exportLogs(), null, 2)}</pre>
							</details>
							<button onclick="window.GeminiLogger.logs = []; localStorage.removeItem('gemini_cc_debug_logs'); location.reload()">Clear Logs & Retry</button>
							<button onclick="window.GeminiLogger.exportLogs && (() => { const logs = window.GeminiLogger.exportLogs(); const blob = new Blob([JSON.stringify(logs, null, 2)], { type: 'application/json' }); const url = URL.createObjectURL(blob); const a = document.createElement('a'); a.href = url; a.download = 'gemini-cc-debug.json'; a.click(); })()">Download Debug Logs</button>
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
				ReactDOM.render(e(GeminiAgent), agentElement);
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