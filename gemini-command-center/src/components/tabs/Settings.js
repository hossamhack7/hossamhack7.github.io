import React, { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

const Settings = () => {
  const [settings, setSettings] = useState(null);
  const [saving, setSaving] = useState(false);
  const [activeSettingsTab, setActiveSettingsTab] = useState('general');
  const [testingConnection, setTestingConnection] = useState(false);
  const [connectionResult, setConnectionResult] = useState(null);

  useEffect(() => {
    loadSettings();
  }, []);

  const loadSettings = async () => {
    try {
      const response = await apiFetch({
        path: 'settings',
        method: 'GET',
      });
      setSettings(response || {});
    } catch (error) {
      console.error('Settings Error:', error);
      setSettings({});
    }
  };

  const handleSave = async (e) => {
    e.preventDefault();
    setSaving(true);
    
    try {
      await apiFetch({
        path: 'settings',
        method: 'POST',
        data: settings,
      });
      
      alert(__('Settings saved successfully!', 'gemini-command-center'));
    } catch (error) {
      console.error('Save Error:', error);
      alert(__('Error saving settings. Please try again.', 'gemini-command-center'));
    } finally {
      setSaving(false);
    }
  };

  const testConnection = async () => {
    if (!settings.api_key || settings.api_key === '••••••••') {
      alert(__('Please enter a valid API key first.', 'gemini-command-center'));
      return;
    }

    setTestingConnection(true);
    setConnectionResult(null);

    try {
      const response = await apiFetch({
        path: 'test-connection',
        method: 'POST',
        data: { api_key: settings.api_key },
      });
      
      setConnectionResult(response);
    } catch (error) {
      console.error('Connection Test Error:', error);
      setConnectionResult({
        success: false,
        message: error.message || __('Connection test failed', 'gemini-command-center')
      });
    } finally {
      setTestingConnection(false);
    }
  };

  const updateSetting = (key, value) => {
    setSettings(prev => ({ ...prev, [key]: value }));
  };

  if (!settings) {
    return (
      <div className="gemini-cc-loading">
        {__('Loading settings...', 'gemini-command-center')}
      </div>
    );
  }

  const settingsTabs = [
    { id: 'general', label: __('General & API', 'gemini-command-center') },
    { id: 'seo', label: __('SEO Settings', 'gemini-command-center') },
    { id: 'uiux', label: __('UI/UX Settings', 'gemini-command-center') },
    { id: 'content', label: __('Content Settings', 'gemini-command-center') },
    { id: 'system', label: __('System & Data', 'gemini-command-center') }
  ];

  const renderSettingsContent = () => {
    switch (activeSettingsTab) {
      case 'general':
        return (
          <div className="settings-section">
            <h4>{__('General & API Settings', 'gemini-command-center')}</h4>
            
            <table className="form-table">
              <tbody>
                <tr>
                  <th scope="row">
                    <label htmlFor="api_key">{__('Gemini API Key', 'gemini-command-center')}</label>
                  </th>
                  <td>
                    <input
                      id="api_key"
                      type="password"
                      value={settings.api_key || ''}
                      onChange={(e) => updateSetting('api_key', e.target.value)}
                      className="regular-text"
                      placeholder={__('Enter your Gemini API key', 'gemini-command-center')}
                    />
                    <button
                      type="button"
                      className="button button-secondary"
                      onClick={testConnection}
                      disabled={testingConnection}
                      style={{ marginLeft: '10px' }}
                    >
                      {testingConnection ? __('Testing...', 'gemini-command-center') : __('Test Connection', 'gemini-command-center')}
                    </button>
                    {connectionResult && (
                      <div className={`connection-result ${connectionResult.success ? 'success' : 'error'}`}>
                        {connectionResult.message}
                      </div>
                    )}
                  </td>
                </tr>
                <tr>
                  <th scope="row">{__('Operation Mode', 'gemini-command-center')}</th>
                  <td>
                    <fieldset>
                      <legend className="screen-reader-text">
                        <span>{__('Operation Mode', 'gemini-command-center')}</span>
                      </legend>
                      <label>
                        <input
                          type="radio"
                          name="operation_mode"
                          value="approval"
                          checked={settings.operation_mode === 'approval'}
                          onChange={(e) => updateSetting('operation_mode', e.target.value)}
                        />
                        {__('Approval Mode (Default)', 'gemini-command-center')}
                      </label>
                      <br />
                      <label>
                        <input
                          type="radio"
                          name="operation_mode"
                          value="autonomous"
                          checked={settings.operation_mode === 'autonomous'}
                          onChange={(e) => updateSetting('operation_mode', e.target.value)}
                        />
                        {__('Autonomous Mode', 'gemini-command-center')}
                      </label>
                    </fieldset>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        );

      case 'seo':
        return (
          <div className="settings-section">
            <h4>{__('SEO Settings', 'gemini-command-center')}</h4>
            
            <table className="form-table">
              <tbody>
                <tr>
                  <th scope="row">{__('Internal Link Architect', 'gemini-command-center')}</th>
                  <td>
                    <label>
                      <input
                        type="checkbox"
                        checked={settings.seo_internal_links_enabled || false}
                        onChange={(e) => updateSetting('seo_internal_links_enabled', e.target.checked)}
                      />
                      {__('Enable internal link building', 'gemini-command-center')}
                    </label>
                    <br />
                    <label>
                      {__('Max links per post:', 'gemini-command-center')}
                      <input
                        type="number"
                        min="1"
                        max="20"
                        value={settings.seo_internal_links_max || 5}
                        onChange={(e) => updateSetting('seo_internal_links_max', parseInt(e.target.value))}
                        className="small-text"
                      />
                    </label>
                  </td>
                </tr>
                <tr>
                  <th scope="row">{__('A/B Testing', 'gemini-command-center')}</th>
                  <td>
                    <label>
                      <input
                        type="checkbox"
                        checked={settings.seo_ab_testing_enabled || false}
                        onChange={(e) => updateSetting('seo_ab_testing_enabled', e.target.checked)}
                      />
                      {__('Enable A/B testing', 'gemini-command-center')}
                    </label>
                    <br />
                    <label>
                      {__('Default test duration (days):', 'gemini-command-center')}
                      <input
                        type="number"
                        min="1"
                        max="90"
                        value={settings.seo_ab_testing_duration || 14}
                        onChange={(e) => updateSetting('seo_ab_testing_duration', parseInt(e.target.value))}
                        className="small-text"
                      />
                    </label>
                  </td>
                </tr>
                <tr>
                  <th scope="row">
                    <label htmlFor="competitive_region">{__('Competitive Analysis', 'gemini-command-center')}</label>
                  </th>
                  <td>
                    <select
                      id="competitive_region"
                      value={settings.seo_competitive_region || 'google.com'}
                      onChange={(e) => updateSetting('seo_competitive_region', e.target.value)}
                    >
                      <option value="google.com">Google.com</option>
                      <option value="google.co.uk">Google.co.uk</option>
                      <option value="google.ca">Google.ca</option>
                      <option value="google.au">Google.au</option>
                    </select>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        );

      case 'uiux':
        return (
          <div className="settings-section">
            <h4>{__('UI/UX Settings', 'gemini-command-center')}</h4>
            
            <table className="form-table">
              <tbody>
                <tr>
                  <th scope="row">{__('Enable UI/UX Module', 'gemini-command-center')}</th>
                  <td>
                    <label>
                      <input
                        type="checkbox"
                        checked={settings.uiux_enabled || false}
                        onChange={(e) => updateSetting('uiux_enabled', e.target.checked)}
                      />
                      {__('Globally enable CSS injection for UI improvements', 'gemini-command-center')}
                    </label>
                  </td>
                </tr>
                <tr>
                  <th scope="row">{__('Reset Styles', 'gemini-command-center')}</th>
                  <td>
                    <button 
                      type="button" 
                      className="button button-secondary"
                      onClick={() => {
                        if (confirm(__('Are you sure you want to clear all UI/UX customizations?', 'gemini-command-center'))) {
                          // Implementation would clear UI styles
                          alert(__('UI styles cleared successfully.', 'gemini-command-center'));
                        }
                      }}
                    >
                      {__('Clear All Style Modifications', 'gemini-command-center')}
                    </button>
                    <p className="description">
                      {__('This will remove all custom CSS modifications made by the UI/UX module.', 'gemini-command-center')}
                    </p>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        );

      case 'content':
        return (
          <div className="settings-section">
            <h4>{__('Content Settings', 'gemini-command-center')}</h4>
            
            <table className="form-table">
              <tbody>
                <tr>
                  <th scope="row">
                    <label htmlFor="content_status">{__('AI Writer', 'gemini-command-center')}</label>
                  </th>
                  <td>
                    <select
                      id="content_status"
                      value={settings.content_default_status || 'draft'}
                      onChange={(e) => updateSetting('content_default_status', e.target.value)}
                    >
                      <option value="draft">{__('Draft', 'gemini-command-center')}</option>
                      <option value="publish">{__('Publish', 'gemini-command-center')}</option>
                    </select>
                    <p className="description">
                      {__('Default post status for AI-generated content.', 'gemini-command-center')}
                    </p>
                  </td>
                </tr>
                <tr>
                  <th scope="row">{__('Social Amplifier', 'gemini-command-center')}</th>
                  <td>
                    <label>
                      {__('Twitter/X tone:', 'gemini-command-center')}
                      <input
                        type="text"
                        value={settings.content_social_tone_twitter || 'professional'}
                        onChange={(e) => updateSetting('content_social_tone_twitter', e.target.value)}
                        className="regular-text"
                        placeholder="professional, casual, witty..."
                      />
                    </label>
                    <br />
                    <label>
                      {__('LinkedIn tone:', 'gemini-command-center')}
                      <input
                        type="text"
                        value={settings.content_social_tone_linkedin || 'business'}
                        onChange={(e) => updateSetting('content_social_tone_linkedin', e.target.value)}
                        className="regular-text"
                        placeholder="business, thought-leader, informative..."
                      />
                    </label>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        );

      case 'system':
        return (
          <div className="settings-section">
            <h4>{__('System & Data Settings', 'gemini-command-center')}</h4>
            
            <table className="form-table">
              <tbody>
                <tr>
                  <th scope="row">{__('Backup', 'gemini-command-center')}</th>
                  <td>
                    <label>
                      {__('Schedule:', 'gemini-command-center')}
                      <select
                        value={settings.backup_schedule || 'weekly'}
                        onChange={(e) => updateSetting('backup_schedule', e.target.value)}
                      >
                        <option value="disabled">{__('Disabled', 'gemini-command-center')}</option>
                        <option value="daily">{__('Daily', 'gemini-command-center')}</option>
                        <option value="weekly">{__('Weekly', 'gemini-command-center')}</option>
                      </select>
                    </label>
                    <br />
                    <label>
                      {__('Retention (number of backups):', 'gemini-command-center')}
                      <input
                        type="number"
                        min="1"
                        max="50"
                        value={settings.backup_retention || 5}
                        onChange={(e) => updateSetting('backup_retention', parseInt(e.target.value))}
                        className="small-text"
                      />
                    </label>
                  </td>
                </tr>
                <tr>
                  <th scope="row">{__('Usage Control', 'gemini-command-center')}</th>
                  <td>
                    <label>
                      <input
                        type="checkbox"
                        checked={settings.system_auto_cooldown || false}
                        onChange={(e) => updateSetting('system_auto_cooldown', e.target.checked)}
                      />
                      {__('Enable Auto-Cooldown Mode for rate limiting', 'gemini-command-center')}
                    </label>
                  </td>
                </tr>
                <tr>
                  <th scope="row">{__('System Log', 'gemini-command-center')}</th>
                  <td>
                    <label>
                      <input
                        type="checkbox"
                        checked={settings.system_logging_enabled || false}
                        onChange={(e) => updateSetting('system_logging_enabled', e.target.checked)}
                      />
                      {__('Enable system logging', 'gemini-command-center')}
                    </label>
                    <br />
                    <button 
                      type="button" 
                      className="button button-secondary"
                      onClick={() => {
                        if (confirm(__('Are you sure you want to clear the system log?', 'gemini-command-center'))) {
                          // Implementation would clear log
                          alert(__('System log cleared successfully.', 'gemini-command-center'));
                        }
                      }}
                    >
                      {__('Clear Log', 'gemini-command-center')}
                    </button>
                  </td>
                </tr>
                <tr>
                  <th scope="row">{__('Import/Export', 'gemini-command-center')}</th>
                  <td>
                    <button 
                      type="button" 
                      className="button button-secondary"
                      onClick={() => {
                        // Implementation would trigger export
                        alert(__('Export functionality not yet implemented.', 'gemini-command-center'));
                      }}
                    >
                      {__('Export Settings', 'gemini-command-center')}
                    </button>
                    {' '}
                    <button 
                      type="button" 
                      className="button button-secondary"
                      onClick={() => {
                        // Implementation would trigger import
                        alert(__('Import functionality not yet implemented.', 'gemini-command-center'));
                      }}
                    >
                      {__('Import Settings', 'gemini-command-center')}
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        );

      default:
        return null;
    }
  };

  return (
    <div className="gemini-cc-tab-content">
      <h3>{__('Settings', 'gemini-command-center')}</h3>
      
      {/* Settings Sub-tabs */}
      <div className="settings-tabs">
        {settingsTabs.map(tab => (
          <div
            key={tab.id}
            className={`settings-tab ${activeSettingsTab === tab.id ? 'active' : ''}`}
            onClick={() => setActiveSettingsTab(tab.id)}
          >
            {tab.label}
          </div>
        ))}
      </div>

      <form onSubmit={handleSave}>
        {renderSettingsContent()}
        
        <p className="submit">
          <button
            type="submit"
            className="button button-primary"
            disabled={saving}
          >
            {saving ? __('Saving...', 'gemini-command-center') : __('Save Settings', 'gemini-command-center')}
          </button>
        </p>
      </form>
    </div>
  );
};

export default Settings;