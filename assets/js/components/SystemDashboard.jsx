import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import Loader from './Loader';

const SystemDashboard = () => {
    const [loading, setLoading] = useState(true);
    const [activeTab, setActiveTab] = useState('health');
    const [healthData, setHealthData] = useState(null);
    const [backups, setBackups] = useState([]);
    const [creatingBackup, setCreatingBackup] = useState(false);

    useEffect(() => {
        loadSystemData();
    }, []);

    const loadSystemData = async () => {
        try {
            const [healthResponse, backupsResponse] = await Promise.all([
                apiFetch({
                    path: '/gemini-cc/v1/system/health-check',
                    headers: {
                        'X-WP-Nonce': window.geminiCC?.nonce || ''
                    }
                }),
                apiFetch({
                    path: '/gemini-cc/v1/backup/list',
                    headers: {
                        'X-WP-Nonce': window.geminiCC?.nonce || ''
                    }
                })
            ]);

            setHealthData(healthResponse);
            setBackups(backupsResponse);
        } catch (err) {
            console.error('Failed to load system data:', err);
        } finally {
            setLoading(false);
        }
    };

    const createBackup = async (type = 'database') => {
        setCreatingBackup(true);
        try {
            const response = await apiFetch({
                path: '/gemini-cc/v1/backup/create',
                method: 'POST',
                data: { type },
                headers: {
                    'X-WP-Nonce': window.geminiCC?.nonce || ''
                }
            });

            if (response.success) {
                setBackups(prev => [response.backup, ...prev]);
                alert(__('Backup created successfully!', 'gemini-command-center'));
            }
        } catch (err) {
            alert(__('Failed to create backup', 'gemini-command-center'));
        } finally {
            setCreatingBackup(false);
        }
    };

    const getHealthStatus = (value, good, warning) => {
        if (value === good) return 'good';
        if (value === warning) return 'warning';
        return 'error';
    };

    const tabs = [
        { id: 'health', label: __('System Health', 'gemini-command-center') },
        { id: 'backups', label: __('Backup Center', 'gemini-command-center') },
        { id: 'logs', label: __('System Logs', 'gemini-command-center') }
    ];

    if (loading) {
        return <Loader message={__('Loading system dashboard...', 'gemini-command-center')} />;
    }

    return (
        <div className="gcc-system-dashboard">
            <div className="gcc-system-tabs">
                {tabs.map(tab => (
                    <button
                        key={tab.id}
                        className={`gcc-tab ${activeTab === tab.id ? 'active' : ''}`}
                        onClick={() => setActiveTab(tab.id)}
                    >
                        {tab.label}
                    </button>
                ))}
            </div>

            <div className="gcc-system-content">
                {activeTab === 'health' && (
                    <div className="gcc-tab-content">
                        <h3>{__('System Health Check', 'gemini-command-center')}</h3>
                        
                        {healthData && (
                            <div className="gcc-health-grid">
                                <div className="gcc-health-card">
                                    <h4>{__('WordPress', 'gemini-command-center')}</h4>
                                    <div className="gcc-health-item">
                                        <span className="label">{__('Version', 'gemini-command-center')}</span>
                                        <span className={`value status-${getHealthStatus(healthData.wordpress_version, healthData.wordpress_version, null)}`}>
                                            {healthData.wordpress_version}
                                        </span>
                                    </div>
                                </div>

                                <div className="gcc-health-card">
                                    <h4>{__('PHP', 'gemini-command-center')}</h4>
                                    <div className="gcc-health-item">
                                        <span className="label">{__('Version', 'gemini-command-center')}</span>
                                        <span className={`value status-${parseFloat(healthData.php_version) >= 8.0 ? 'good' : 'warning'}`}>
                                            {healthData.php_version}
                                        </span>
                                    </div>
                                </div>

                                <div className="gcc-health-card">
                                    <h4>{__('Memory', 'gemini-command-center')}</h4>
                                    <div className="gcc-health-item">
                                        <span className="label">{__('Limit', 'gemini-command-center')}</span>
                                        <span className="value">
                                            {healthData.memory_limit}
                                        </span>
                                    </div>
                                </div>

                                <div className="gcc-health-card">
                                    <h4>{__('File Uploads', 'gemini-command-center')}</h4>
                                    <div className="gcc-health-item">
                                        <span className="label">{__('Max Size', 'gemini-command-center')}</span>
                                        <span className="value">
                                            {healthData.upload_max_filesize}
                                        </span>
                                    </div>
                                </div>

                                <div className="gcc-health-card">
                                    <h4>{__('Execution Time', 'gemini-command-center')}</h4>
                                    <div className="gcc-health-item">
                                        <span className="label">{__('Max Time', 'gemini-command-center')}</span>
                                        <span className="value">
                                            {healthData.max_execution_time}s
                                        </span>
                                    </div>
                                </div>

                                <div className="gcc-health-card">
                                    <h4>{__('Backup Directory', 'gemini-command-center')}</h4>
                                    <div className="gcc-health-item">
                                        <span className="label">{__('Writable', 'gemini-command-center')}</span>
                                        <span className={`value status-${healthData.backup_directory_writable ? 'good' : 'error'}`}>
                                            {healthData.backup_directory_writable ? __('Yes', 'gemini-command-center') : __('No', 'gemini-command-center')}
                                        </span>
                                    </div>
                                </div>

                                <div className="gcc-health-card">
                                    <h4>{__('Gemini API', 'gemini-command-center')}</h4>
                                    <div className="gcc-health-item">
                                        <span className="label">{__('Status', 'gemini-command-center')}</span>
                                        <span className={`value status-${healthData.gemini_api_status === 'configured' ? 'good' : 'warning'}`}>
                                            {healthData.gemini_api_status === 'configured' 
                                                ? __('Configured', 'gemini-command-center')
                                                : __('Not Configured', 'gemini-command-center')
                                            }
                                        </span>
                                    </div>
                                </div>

                                <div className="gcc-health-card">
                                    <h4>{__('Plugin', 'gemini-command-center')}</h4>
                                    <div className="gcc-health-item">
                                        <span className="label">{__('Version', 'gemini-command-center')}</span>
                                        <span className="value status-good">
                                            {healthData.plugin_version}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        )}

                        <div className="gcc-health-actions">
                            <button 
                                className="button button-primary"
                                onClick={loadSystemData}
                            >
                                {__('Refresh Health Check', 'gemini-command-center')}
                            </button>
                        </div>
                    </div>
                )}

                {activeTab === 'backups' && (
                    <div className="gcc-tab-content">
                        <h3>{__('Backup Center', 'gemini-command-center')}</h3>
                        
                        <div className="gcc-backup-actions">
                            <button
                                className="button button-primary"
                                onClick={() => createBackup('database')}
                                disabled={creatingBackup}
                            >
                                {creatingBackup 
                                    ? __('Creating...', 'gemini-command-center')
                                    : __('Create Database Backup', 'gemini-command-center')
                                }
                            </button>
                            
                            <button
                                className="button button-secondary"
                                onClick={() => createBackup('full')}
                                disabled={creatingBackup}
                            >
                                {__('Create Full Backup', 'gemini-command-center')}
                            </button>
                        </div>

                        <div className="gcc-backups-list">
                            <h4>{__('Existing Backups', 'gemini-command-center')}</h4>
                            
                            {backups.length === 0 ? (
                                <p>{__('No backups found. Create your first backup above.', 'gemini-command-center')}</p>
                            ) : (
                                <table className="gcc-table">
                                    <thead>
                                        <tr>
                                            <th>{__('Filename', 'gemini-command-center')}</th>
                                            <th>{__('Type', 'gemini-command-center')}</th>
                                            <th>{__('Size', 'gemini-command-center')}</th>
                                            <th>{__('Created', 'gemini-command-center')}</th>
                                            <th>{__('Actions', 'gemini-command-center')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {backups.map((backup, index) => (
                                            <tr key={index}>
                                                <td>{backup.filename}</td>
                                                <td>
                                                    <span className={`gcc-backup-type ${backup.type}`}>
                                                        {backup.type === 'database' 
                                                            ? __('Database', 'gemini-command-center')
                                                            : __('Full', 'gemini-command-center')
                                                        }
                                                    </span>
                                                </td>
                                                <td>{backup.size}</td>
                                                <td>{backup.created_at}</td>
                                                <td>
                                                    <button className="button button-small">
                                                        {__('Download', 'gemini-command-center')}
                                                    </button>
                                                    <button className="button button-small">
                                                        {__('Restore', 'gemini-command-center')}
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            )}
                        </div>
                    </div>
                )}

                {activeTab === 'logs' && (
                    <div className="gcc-tab-content">
                        <h3>{__('System Logs', 'gemini-command-center')}</h3>
                        
                        <div className="gcc-logs-actions">
                            <button className="button button-secondary">
                                {__('Clear Logs', 'gemini-command-center')}
                            </button>
                            <button className="button button-secondary">
                                {__('Download Logs', 'gemini-command-center')}
                            </button>
                        </div>

                        <div className="gcc-logs-container">
                            <div className="gcc-log-entry">
                                <span className="gcc-log-time">2024-01-15 14:30:25</span>
                                <span className="gcc-log-level info">INFO</span>
                                <span className="gcc-log-message">
                                    {__('Gemini Command Center plugin activated', 'gemini-command-center')}
                                </span>
                            </div>
                            
                            <div className="gcc-log-entry">
                                <span className="gcc-log-time">2024-01-15 14:35:12</span>
                                <span className="gcc-log-level success">SUCCESS</span>
                                <span className="gcc-log-message">
                                    {__('Settings updated successfully', 'gemini-command-center')}
                                </span>
                            </div>
                            
                            <div className="gcc-log-entry">
                                <span className="gcc-log-time">2024-01-15 14:40:33</span>
                                <span className="gcc-log-level warning">WARNING</span>
                                <span className="gcc-log-message">
                                    {__('API rate limit approaching (80% used)', 'gemini-command-center')}
                                </span>
                            </div>

                            <p className="gcc-logs-placeholder">
                                {__('This is a demo view. In the full implementation, this would show real system logs from the database.', 'gemini-command-center')}
                            </p>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
};

export default SystemDashboard;