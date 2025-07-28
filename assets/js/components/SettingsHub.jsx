import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import Loader from './Loader';

const SettingsHub = () => {
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [settings, setSettings] = useState({});
    const [activeTab, setActiveTab] = useState('general');
    const [testingConnection, setTestingConnection] = useState(false);
    const [connectionResult, setConnectionResult] = useState(null);

    useEffect(() => {
        loadSettings();
    }, []);

    const loadSettings = async () => {
        try {
            const response = await apiFetch({
                path: '/gemini-cc/v1/settings',
                headers: {
                    'X-WP-Nonce': window.geminiCC?.nonce || ''
                }
            });
            setSettings(response);
        } catch (err) {
            console.error('Failed to load settings:', err);
        } finally {
            setLoading(false);
        }
    };

    const saveSettings = async () => {
        setSaving(true);
        try {
            await apiFetch({
                path: '/gemini-cc/v1/settings',
                method: 'POST',
                data: { settings },
                headers: {
                    'X-WP-Nonce': window.geminiCC?.nonce || ''
                }
            });
            // Show success message
            alert(__('Settings saved successfully!', 'gemini-command-center'));
        } catch (err) {
            alert(__('Failed to save settings', 'gemini-command-center'));
        } finally {
            setSaving(false);
        }
    };

    const testConnection = async () => {
        if (!settings.gemini_api_key) {
            alert(__('Please enter an API key first', 'gemini-command-center'));
            return;
        }

        setTestingConnection(true);
        setConnectionResult(null);
        
        try {
            const response = await apiFetch({
                path: '/gemini-cc/v1/test-connection',
                method: 'POST',
                data: { api_key: settings.gemini_api_key },
                headers: {
                    'X-WP-Nonce': window.geminiCC?.nonce || ''
                }
            });
            
            setConnectionResult({
                success: true,
                message: response.message
            });
        } catch (err) {
            setConnectionResult({
                success: false,
                message: err.message || __('Connection failed', 'gemini-command-center')
            });
        } finally {
            setTestingConnection(false);
        }
    };

    const updateSetting = (key, value) => {
        setSettings(prev => ({
            ...prev,
            [key]: value
        }));
    };

    const tabs = [
        { id: 'general', label: __('General & API', 'gemini-command-center') },
        { id: 'seo', label: __('SEO Settings', 'gemini-command-center') },
        { id: 'uiux', label: __('UI/UX Settings', 'gemini-command-center') },
        { id: 'content', label: __('Content Settings', 'gemini-command-center') },
        { id: 'system', label: __('System & Data', 'gemini-command-center') }
    ];

    if (loading) {
        return <Loader message={__('Loading settings...', 'gemini-command-center')} />;
    }

    return (
        <div className="gcc-settings">
            <div className="gcc-settings-tabs">
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

            <div className="gcc-settings-content">
                {activeTab === 'general' && (
                    <div className="gcc-tab-content">
                        <h3>{__('General & API Settings', 'gemini-command-center')}</h3>
                        
                        <div className="gcc-field">
                            <label>{__('Gemini API Key', 'gemini-command-center')}</label>
                            <input
                                type="password"
                                value={settings.gemini_api_key || ''}
                                onChange={(e) => updateSetting('gemini_api_key', e.target.value)}
                                placeholder={__('Enter your Gemini API key...', 'gemini-command-center')}
                            />
                        </div>

                        <div className="gcc-field">
                            <button
                                className="button button-secondary"
                                onClick={testConnection}
                                disabled={testingConnection}
                            >
                                {testingConnection 
                                    ? __('Testing...', 'gemini-command-center')
                                    : __('Test Connection', 'gemini-command-center')
                                }
                            </button>
                            
                            {connectionResult && (
                                <div className={`gcc-notice ${connectionResult.success ? 'success' : 'error'}`}>
                                    {connectionResult.message}
                                </div>
                            )}
                        </div>

                        <div className="gcc-field">
                            <label>{__('Operation Mode', 'gemini-command-center')}</label>
                            <div className="gcc-radio-group">
                                <label>
                                    <input
                                        type="radio"
                                        value="approval"
                                        checked={settings.operation_mode === 'approval'}
                                        onChange={(e) => updateSetting('operation_mode', e.target.value)}
                                    />
                                    {__('Approval Mode (Recommended)', 'gemini-command-center')}
                                </label>
                                <label>
                                    <input
                                        type="radio"
                                        value="autonomous"
                                        checked={settings.operation_mode === 'autonomous'}
                                        onChange={(e) => updateSetting('operation_mode', e.target.value)}
                                    />
                                    {__('Autonomous Mode', 'gemini-command-center')}
                                </label>
                            </div>
                        </div>
                    </div>
                )}

                {activeTab === 'seo' && (
                    <div className="gcc-tab-content">
                        <h3>{__('SEO Settings', 'gemini-command-center')}</h3>
                        
                        <div className="gcc-field">
                            <label>
                                <input
                                    type="checkbox"
                                    checked={settings.seo_internal_links_enabled || false}
                                    onChange={(e) => updateSetting('seo_internal_links_enabled', e.target.checked)}
                                />
                                {__('Enable Internal Link Architect', 'gemini-command-center')}
                            </label>
                        </div>

                        <div className="gcc-field">
                            <label>{__('Max Internal Links per Post', 'gemini-command-center')}</label>
                            <input
                                type="number"
                                min="1"
                                max="20"
                                value={settings.seo_internal_links_max || 5}
                                onChange={(e) => updateSetting('seo_internal_links_max', parseInt(e.target.value))}
                            />
                        </div>

                        <div className="gcc-field">
                            <label>
                                <input
                                    type="checkbox"
                                    checked={settings.ab_testing_enabled || false}
                                    onChange={(e) => updateSetting('ab_testing_enabled', e.target.checked)}
                                />
                                {__('Enable A/B Testing', 'gemini-command-center')}
                            </label>
                        </div>

                        <div className="gcc-field">
                            <label>{__('Default Test Duration (days)', 'gemini-command-center')}</label>
                            <input
                                type="number"
                                min="1"
                                max="90"
                                value={settings.ab_testing_duration || 14}
                                onChange={(e) => updateSetting('ab_testing_duration', parseInt(e.target.value))}
                            />
                        </div>

                        <div className="gcc-field">
                            <label>{__('Default Search Region', 'gemini-command-center')}</label>
                            <select
                                value={settings.competitive_analysis_region || 'google.com'}
                                onChange={(e) => updateSetting('competitive_analysis_region', e.target.value)}
                            >
                                <option value="google.com">Google.com (Global)</option>
                                <option value="google.co.uk">Google.co.uk (UK)</option>
                                <option value="google.ca">Google.ca (Canada)</option>
                                <option value="google.com.au">Google.com.au (Australia)</option>
                            </select>
                        </div>
                    </div>
                )}

                {activeTab === 'uiux' && (
                    <div className="gcc-tab-content">
                        <h3>{__('UI/UX Settings', 'gemini-command-center')}</h3>
                        
                        <div className="gcc-field">
                            <label>
                                <input
                                    type="checkbox"
                                    checked={settings.uiux_enabled || false}
                                    onChange={(e) => updateSetting('uiux_enabled', e.target.checked)}
                                />
                                {__('Enable UI/UX Module', 'gemini-command-center')}
                            </label>
                            <p className="description">
                                {__('Globally enable or disable the injection of custom CSS styles', 'gemini-command-center')}
                            </p>
                        </div>

                        <div className="gcc-field">
                            <button className="button button-secondary">
                                {__('Reset All UI/UX Modifications', 'gemini-command-center')}
                            </button>
                            <p className="description">
                                {__('Clear all saved UI/UX style modifications', 'gemini-command-center')}
                            </p>
                        </div>
                    </div>
                )}

                {activeTab === 'content' && (
                    <div className="gcc-tab-content">
                        <h3>{__('Content Settings', 'gemini-command-center')}</h3>
                        
                        <div className="gcc-field">
                            <label>{__('AI Writer Default Post Status', 'gemini-command-center')}</label>
                            <select
                                value={settings.ai_writer_default_status || 'draft'}
                                onChange={(e) => updateSetting('ai_writer_default_status', e.target.value)}
                            >
                                <option value="draft">{__('Draft', 'gemini-command-center')}</option>
                                <option value="publish">{__('Publish', 'gemini-command-center')}</option>
                            </select>
                        </div>

                        <div className="gcc-field">
                            <label>{__('Social Amplifier Default Tone', 'gemini-command-center')}</label>
                            <input
                                type="text"
                                value={settings.social_default_tone || ''}
                                onChange={(e) => updateSetting('social_default_tone', e.target.value)}
                                placeholder={__('Professional, engaging, informative...', 'gemini-command-center')}
                            />
                        </div>
                    </div>
                )}

                {activeTab === 'system' && (
                    <div className="gcc-tab-content">
                        <h3>{__('System & Data Settings', 'gemini-command-center')}</h3>
                        
                        <div className="gcc-field">
                            <label>{__('Backup Schedule', 'gemini-command-center')}</label>
                            <select
                                value={settings.backup_schedule || 'disabled'}
                                onChange={(e) => updateSetting('backup_schedule', e.target.value)}
                            >
                                <option value="disabled">{__('Disabled', 'gemini-command-center')}</option>
                                <option value="daily">{__('Daily', 'gemini-command-center')}</option>
                                <option value="weekly">{__('Weekly', 'gemini-command-center')}</option>
                            </select>
                        </div>

                        <div className="gcc-field">
                            <label>{__('Number of Backups to Retain', 'gemini-command-center')}</label>
                            <input
                                type="number"
                                min="1"
                                max="50"
                                value={settings.backup_retention || 5}
                                onChange={(e) => updateSetting('backup_retention', parseInt(e.target.value))}
                            />
                        </div>

                        <div className="gcc-field">
                            <label>
                                <input
                                    type="checkbox"
                                    checked={settings.usage_control_enabled || false}
                                    onChange={(e) => updateSetting('usage_control_enabled', e.target.checked)}
                                />
                                {__('Enable Auto-Cooldown Mode', 'gemini-command-center')}
                            </label>
                            <p className="description">
                                {__('Automatically rate limit API requests to prevent overuse', 'gemini-command-center')}
                            </p>
                        </div>

                        <div className="gcc-field">
                            <label>
                                <input
                                    type="checkbox"
                                    checked={settings.system_log_enabled || false}
                                    onChange={(e) => updateSetting('system_log_enabled', e.target.checked)}
                                />
                                {__('Enable System Logging', 'gemini-command-center')}
                            </label>
                        </div>

                        <div className="gcc-field">
                            <button className="button button-secondary">
                                {__('Clear System Log', 'gemini-command-center')}
                            </button>
                        </div>

                        <div className="gcc-field-group">
                            <h4>{__('Import/Export Settings', 'gemini-command-center')}</h4>
                            <button className="button button-secondary">
                                {__('Export Settings', 'gemini-command-center')}
                            </button>
                            <button className="button button-secondary">
                                {__('Import Settings', 'gemini-command-center')}
                            </button>
                        </div>
                    </div>
                )}
            </div>

            <div className="gcc-settings-footer">
                <button
                    className="button button-primary"
                    onClick={saveSettings}
                    disabled={saving}
                >
                    {saving ? __('Saving...', 'gemini-command-center') : __('Save Settings', 'gemini-command-center')}
                </button>
            </div>
        </div>
    );
};

export default SettingsHub;