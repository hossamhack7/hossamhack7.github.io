import React, { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import TabNavigation from './TabNavigation';
import Dashboard from './tabs/Dashboard';
import Settings from './tabs/Settings';
import SEOCenter from './tabs/SEOCenter';
import ContentCenter from './tabs/ContentCenter';
import BackupCenter from './tabs/BackupCenter';
import ReportsCenter from './tabs/ReportsCenter';

const App = () => {
  const [loading, setLoading] = useState(true);
  const [status, setStatus] = useState(null);
  const [activeTab, setActiveTab] = useState('dashboard');

  useEffect(() => {
    // Test API connection on startup with enhanced error handling
    window.GeminiDebugger && window.GeminiDebugger.info('Testing API connection on app startup');
    
    apiFetch({
      path: 'status',
      method: 'GET',
    })
    .then(response => {
      window.GeminiDebugger && window.GeminiDebugger.info('API status check successful', response);
      setStatus(response);
      setLoading(false);
    })
    .catch(error => {
      window.GeminiDebugger && window.GeminiDebugger.error('API status check failed', {
        error: error.message,
        status: error.status,
        response: error.response,
        full_error: error
      });
      
      console.error('API Error:', error);
      setStatus({ 
        status: 'error', 
        message: error.message || __('Unknown error occurred', 'gemini-command-center'),
        debug_info: window.GeminiDebugger ? window.GeminiDebugger.exportLogs() : null
      });
      setLoading(false);
    });
  }, []);

  if (loading) {
    return (
      <div className="gemini-cc-loading">
        {__('Loading Gemini Command Center...', 'gemini-command-center')}
      </div>
    );
  }

  const renderTabContent = () => {
    switch (activeTab) {
      case 'dashboard':
        return <Dashboard />;
      case 'settings':
        return <Settings />;
      case 'seo':
        return <SEOCenter />;
      case 'content':
        return <ContentCenter />;
      case 'backup':
        return <BackupCenter />;
      case 'reports':
        return <ReportsCenter />;
      default:
        return <Dashboard />;
    }
  };

  return (
    <div className="gemini-cc-container">
      <div className="gemini-cc-header">
        <h2>{__('Gemini Command Center', 'gemini-command-center')}</h2>
        {status && (
          <div className={status.status === 'ok' ? 'gemini-cc-success' : 'gemini-cc-error'}>
            {status.status === 'ok' 
              ? __('API Connected Successfully!', 'gemini-command-center')
              : (
                <div>
                  <div>
                    {__('API Connection Error: ', 'gemini-command-center') + (status.message || __('Unknown error', 'gemini-command-center'))}
                  </div>
                  {status.status === 'error' && (
                    <details style={{marginTop: '10px'}}>
                      <summary style={{cursor: 'pointer', fontWeight: 'bold'}}>
                        {__('Show Debug Information', 'gemini-command-center')}
                      </summary>
                      <div style={{marginTop: '10px', fontSize: '12px', fontFamily: 'monospace', backgroundColor: '#f0f0f0', padding: '10px', borderRadius: '4px', maxHeight: '200px', overflow: 'auto'}}>
                        <div style={{marginBottom: '10px'}}>
                          <button 
                            onClick={() => {
                              if (window.GeminiDebugger) {
                                const logs = window.GeminiDebugger.exportLogs();
                                const blob = new Blob([JSON.stringify(logs, null, 2)], {type: 'application/json'});
                                const url = URL.createObjectURL(blob);
                                const a = document.createElement('a');
                                a.href = url;
                                a.download = `gemini-cc-debug-${new Date().toISOString()}.json`;
                                a.click();
                              }
                            }}
                            className="button button-secondary"
                            style={{fontSize: '11px', padding: '2px 8px'}}
                          >
                            {__('Export Debug Logs', 'gemini-command-center')}
                          </button>
                          <button 
                            onClick={() => {
                              if (window.GeminiDebugger) {
                                window.GeminiDebugger.clearLogs();
                                window.location.reload();
                              }
                            }}
                            className="button button-secondary"
                            style={{fontSize: '11px', padding: '2px 8px', marginLeft: '5px'}}
                          >
                            {__('Clear & Retry', 'gemini-command-center')}
                          </button>
                        </div>
                        {status.debug_info && (
                          <pre style={{margin: 0, whiteSpace: 'pre-wrap', fontSize: '10px'}}>
                            {JSON.stringify(status.debug_info, null, 2)}
                          </pre>
                        )}
                      </div>
                    </details>
                  )}
                </div>
              )
            }
          </div>
        )}
      </div>
      
      <TabNavigation activeTab={activeTab} setActiveTab={setActiveTab} />
      
      <div className="gemini-cc-content">
        {renderTabContent()}
      </div>
    </div>
  );
};

export default App;