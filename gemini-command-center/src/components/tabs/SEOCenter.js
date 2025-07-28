import React from 'react';
import { __ } from '@wordpress/i18n';

const SEOCenter = () => {
  return (
    <div className="gemini-cc-tab-content">
      <h3>{__('SEO Command Center', 'gemini-command-center')}</h3>
      <p>{__('Technical audits, link building, and competitive analysis tools.', 'gemini-command-center')}</p>
      
      <div className="seo-tools-grid">
        <div className="tool-card">
          <h4>🔍 {__('Technical Audit', 'gemini-command-center')}</h4>
          <p>{__('Analyze your site for technical SEO issues', 'gemini-command-center')}</p>
          <button className="button button-primary">
            {__('Run Audit', 'gemini-command-center')}
          </button>
        </div>
        
        <div className="tool-card">
          <h4>🔗 {__('Internal Link Architect', 'gemini-command-center')}</h4>
          <p>{__('Build internal links to improve site structure', 'gemini-command-center')}</p>
          <button className="button button-primary">
            {__('Find Opportunities', 'gemini-command-center')}
          </button>
        </div>
        
        <div className="tool-card">
          <h4>🎯 {__('Competitive Analysis', 'gemini-command-center')}</h4>
          <p>{__('Analyze competitor strategies and keywords', 'gemini-command-center')}</p>
          <button className="button button-primary">
            {__('Analyze Competitor', 'gemini-command-center')}
          </button>
        </div>
        
        <div className="tool-card">
          <h4>🌐 {__('Topical Authority Planner', 'gemini-command-center')}</h4>
          <p>{__('Plan content clusters for topical authority', 'gemini-command-center')}</p>
          <button className="button button-primary">
            {__('Create Cluster', 'gemini-command-center')}
          </button>
        </div>
      </div>
    </div>
  );
};

export default SEOCenter;