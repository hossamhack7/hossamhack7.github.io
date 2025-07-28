import React from 'react';
import { __ } from '@wordpress/i18n';

const ReportsCenter = () => {
  return (
    <div className="gemini-cc-tab-content">
      <h3>{__('Reports & Analytics Hub', 'gemini-command-center')}</h3>
      <p>{__('Performance tracking, impact measurement, and comprehensive analytics.', 'gemini-command-center')}</p>
      
      <div className="reports-tools-grid">
        <div className="tool-card">
          <h4>📊 {__('KPI Dashboard', 'gemini-command-center')}</h4>
          <p>{__('View key performance indicators and metrics', 'gemini-command-center')}</p>
          <button className="button button-primary">
            {__('View KPIs', 'gemini-command-center')}
          </button>
        </div>
        
        <div className="tool-card">
          <h4>📈 {__('Impact Analysis', 'gemini-command-center')}</h4>
          <p>{__('Measure the impact of AI-powered optimizations', 'gemini-command-center')}</p>
          <button className="button button-primary">
            {__('Generate Report', 'gemini-command-center')}
          </button>
        </div>
        
        <div className="tool-card">
          <h4>🔍 {__('SEO Performance', 'gemini-command-center')}</h4>
          <p>{__('Track SEO improvements and ranking changes', 'gemini-command-center')}</p>
          <button className="button button-primary">
            {__('SEO Report', 'gemini-command-center')}
          </button>
        </div>
        
        <div className="tool-card">
          <h4>📝 {__('Content Performance', 'gemini-command-center')}</h4>
          <p>{__('Analyze content effectiveness and engagement', 'gemini-command-center')}</p>
          <button className="button button-primary">
            {__('Content Report', 'gemini-command-center')}
          </button>
        </div>
      </div>
    </div>
  );
};

export default ReportsCenter;