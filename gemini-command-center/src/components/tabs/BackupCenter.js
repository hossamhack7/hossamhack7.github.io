import React from 'react';
import { __ } from '@wordpress/i18n';

const BackupCenter = () => {
  return (
    <div className="gemini-cc-tab-content">
      <h3>{__('Security & Diagnostics Center', 'gemini-command-center')}</h3>
      <p>{__('Secure backups and comprehensive system health monitoring.', 'gemini-command-center')}</p>
      
      <div className="backup-tools-grid">
        <div className="tool-card">
          <h4>💾 {__('Backup Management', 'gemini-command-center')}</h4>
          <p>{__('Create and manage secure backups of your site', 'gemini-command-center')}</p>
          <button className="button button-primary">
            {__('Create Backup Now', 'gemini-command-center')}
          </button>
        </div>
        
        <div className="tool-card">
          <h4>📋 {__('System Log', 'gemini-command-center')}</h4>
          <p>{__('View and monitor system activity logs', 'gemini-command-center')}</p>
          <button className="button button-primary">
            {__('View Logs', 'gemini-command-center')}
          </button>
        </div>
        
        <div className="tool-card">
          <h4>❤️ {__('System Health', 'gemini-command-center')}</h4>
          <p>{__('Monitor your site\'s health and performance', 'gemini-command-center')}</p>
          <button className="button button-primary">
            {__('Health Check', 'gemini-command-center')}
          </button>
        </div>
        
        <div className="tool-card">
          <h4>🔒 {__('Security Status', 'gemini-command-center')}</h4>
          <p>{__('Monitor security status and vulnerabilities', 'gemini-command-center')}</p>
          <button className="button button-primary">
            {__('Security Scan', 'gemini-command-center')}
          </button>
        </div>
      </div>
    </div>
  );
};

export default BackupCenter;