import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const Dashboard = ({ status }) => {
    const [healthData, setHealthData] = useState(null);
    const [recommendations, setRecommendations] = useState([]);

    useEffect(() => {
        loadHealthData();
        generateRecommendations();
    }, []);

    const loadHealthData = async () => {
        try {
            const response = await apiFetch({
                path: '/gemini-cc/v1/system/health-check',
                headers: {
                    'X-WP-Nonce': window.geminiCC?.nonce || ''
                }
            });
            setHealthData(response);
        } catch (err) {
            console.error('Failed to load health data:', err);
        }
    };

    const generateRecommendations = () => {
        // Demo recommendations - would be AI-generated in full implementation
        const demoRecommendations = [
            {
                id: 1,
                title: __('Setup Gemini API Key', 'gemini-command-center'),
                description: __('Configure your Gemini API key to unlock AI-powered features', 'gemini-command-center'),
                action: __('Go to Settings', 'gemini-command-center'),
                actionUrl: 'admin.php?page=gemini-cc-settings',
                priority: 'high'
            },
            {
                id: 2,
                title: __('Run SEO Audit', 'gemini-command-center'),
                description: __('Analyze your site for SEO improvements and technical issues', 'gemini-command-center'),
                action: __('Start Audit', 'gemini-command-center'),
                actionUrl: 'admin.php?page=gemini-cc-system',
                priority: 'medium'
            },
            {
                id: 3,
                title: __('Create Content', 'gemini-command-center'),
                description: __('Generate AI-powered blog posts and optimize existing content', 'gemini-command-center'),
                action: __('Create Article', 'gemini-command-center'),
                actionUrl: 'admin.php?page=gemini-command-center',
                priority: 'medium'
            }
        ];
        
        setRecommendations(demoRecommendations);
    };

    const getPriorityColor = (priority) => {
        switch (priority) {
            case 'high': return '#dc3545';
            case 'medium': return '#fd7e14';
            case 'low': return '#28a745';
            default: return '#6c757d';
        }
    };

    return (
        <div className="gcc-dashboard">
            <div className="gcc-dashboard-header">
                <h2>{__('Action Center', 'gemini-command-center')}</h2>
                <p>{__('Your AI-powered command center for WordPress management', 'gemini-command-center')}</p>
            </div>

            <div className="gcc-dashboard-grid">
                {/* Status Overview */}
                <div className="gcc-card">
                    <h3>{__('System Status', 'gemini-command-center')}</h3>
                    {healthData ? (
                        <div className="gcc-status-grid">
                            <div className="gcc-status-item">
                                <span className="label">{__('WordPress', 'gemini-command-center')}</span>
                                <span className="value">{healthData.wordpress_version}</span>
                            </div>
                            <div className="gcc-status-item">
                                <span className="label">{__('PHP', 'gemini-command-center')}</span>
                                <span className="value">{healthData.php_version}</span>
                            </div>
                            <div className="gcc-status-item">
                                <span className="label">{__('Plugin', 'gemini-command-center')}</span>
                                <span className="value">{healthData.plugin_version}</span>
                            </div>
                            <div className="gcc-status-item">
                                <span className="label">{__('Gemini API', 'gemini-command-center')}</span>
                                <span className={`value status-${healthData.gemini_api_status}`}>
                                    {healthData.gemini_api_status === 'configured' 
                                        ? __('Ready', 'gemini-command-center')
                                        : __('Not Configured', 'gemini-command-center')
                                    }
                                </span>
                            </div>
                        </div>
                    ) : (
                        <p>{__('Loading system status...', 'gemini-command-center')}</p>
                    )}
                </div>

                {/* Top Recommendations */}
                <div className="gcc-card">
                    <h3>{__('Top Recommendations', 'gemini-command-center')}</h3>
                    <div className="gcc-recommendations">
                        {recommendations.slice(0, 3).map(rec => (
                            <div key={rec.id} className="gcc-recommendation">
                                <div className="gcc-recommendation-header">
                                    <span 
                                        className="gcc-priority-indicator"
                                        style={{ backgroundColor: getPriorityColor(rec.priority) }}
                                    ></span>
                                    <h4>{rec.title}</h4>
                                </div>
                                <p>{rec.description}</p>
                                <a 
                                    href={rec.actionUrl} 
                                    className="button button-primary button-small"
                                >
                                    {rec.action}
                                </a>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Quick Actions */}
                <div className="gcc-card">
                    <h3>{__('Quick Actions', 'gemini-command-center')}</h3>
                    <div className="gcc-quick-actions">
                        <a href="admin.php?page=gemini-agent" className="gcc-action-button">
                            <span className="dashicons dashicons-format-chat"></span>
                            {__('Chat with AI', 'gemini-command-center')}
                        </a>
                        <a href="admin.php?page=gemini-cc-settings" className="gcc-action-button">
                            <span className="dashicons dashicons-admin-settings"></span>
                            {__('Settings', 'gemini-command-center')}
                        </a>
                        <a href="admin.php?page=gemini-cc-system" className="gcc-action-button">
                            <span className="dashicons dashicons-shield"></span>
                            {__('System Health', 'gemini-command-center')}
                        </a>
                    </div>
                </div>

                {/* Welcome Message */}
                <div className="gcc-card gcc-welcome">
                    <h3>{__('Welcome to Gemini Command Center', 'gemini-command-center')}</h3>
                    <p>
                        {__('Your WordPress site is now powered by AI! Start by configuring your API key in Settings, then explore the powerful features available to boost your SEO, create content, and manage your site more effectively.', 'gemini-command-center')}
                    </p>
                    <a href="admin.php?page=gemini-cc-settings" className="button button-primary">
                        {__('Get Started', 'gemini-command-center')}
                    </a>
                </div>
            </div>
        </div>
    );
};

export default Dashboard;