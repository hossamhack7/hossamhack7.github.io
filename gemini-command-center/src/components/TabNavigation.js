import React from 'react';
import { __ } from '@wordpress/i18n';

const TabNavigation = ({ activeTab, setActiveTab }) => {
  const tabs = [
    { id: 'dashboard', label: __('Dashboard', 'gemini-command-center'), icon: '🏠' },
    { id: 'settings', label: __('Settings', 'gemini-command-center'), icon: '⚙️' },
    { id: 'seo', label: __('SEO Center', 'gemini-command-center'), icon: '🔍' },
    { id: 'content', label: __('Content', 'gemini-command-center'), icon: '📝' },
    { id: 'backup', label: __('Backup', 'gemini-command-center'), icon: '💾' },
    { id: 'reports', label: __('Reports', 'gemini-command-center'), icon: '📊' }
  ];

  return (
    <div className="gemini-cc-tabs">
      {tabs.map(tab => (
        <div
          key={tab.id}
          className={`gemini-cc-tab ${activeTab === tab.id ? 'active' : ''}`}
          onClick={() => setActiveTab(tab.id)}
        >
          <span className="tab-icon">{tab.icon}</span>
          <span className="tab-label">{tab.label}</span>
        </div>
      ))}
    </div>
  );
};

export default TabNavigation;