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
    // Test API connection on startup
    apiFetch({
      path: 'status',
      method: 'GET',
    })
    .then(response => {
      setStatus(response);
      setLoading(false);
    })
    .catch(error => {
      console.error('API Error:', error);
      setStatus({ 
        status: 'error', 
        message: error.message || __('Unknown error occurred', 'gemini-command-center')
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
              : __('API Connection Error: ', 'gemini-command-center') + (status.message || __('Unknown error', 'gemini-command-center'))
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