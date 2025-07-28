import React from 'react';
import { __ } from '@wordpress/i18n';

const ContentCenter = () => {
  return (
    <div className="gemini-cc-tab-content">
      <h3>{__('Content Management Center', 'gemini-command-center')}</h3>
      <p>{__('AI-powered content creation, optimization, and amplification tools.', 'gemini-command-center')}</p>
      
      <div className="content-tools-grid">
        <div className="tool-card">
          <h4>✍️ {__('AI Writer', 'gemini-command-center')}</h4>
          <p>{__('Generate high-quality articles with AI assistance', 'gemini-command-center')}</p>
          <button className="button button-primary">
            {__('Create Article', 'gemini-command-center')}
          </button>
        </div>
        
        <div className="tool-card">
          <h4>⚡ {__('On-Page SEO Optimizer', 'gemini-command-center')}</h4>
          <p>{__('Optimize existing content for better search rankings', 'gemini-command-center')}</p>
          <button className="button button-primary">
            {__('Optimize Content', 'gemini-command-center')}
          </button>
        </div>
        
        <div className="tool-card">
          <h4>🧪 {__('A/B Testing Engine', 'gemini-command-center')}</h4>
          <p>{__('Test different headlines and content variations', 'gemini-command-center')}</p>
          <button className="button button-primary">
            {__('Start Test', 'gemini-command-center')}
          </button>
        </div>
        
        <div className="tool-card">
          <h4>📱 {__('Social Media Amplifier', 'gemini-command-center')}</h4>
          <p>{__('Generate social media content for multiple platforms', 'gemini-command-center')}</p>
          <button className="button button-primary">
            {__('Amplify Content', 'gemini-command-center')}
          </button>
        </div>
      </div>
    </div>
  );
};

export default ContentCenter;