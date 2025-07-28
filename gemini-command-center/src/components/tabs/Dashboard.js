import React, { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

const Dashboard = () => {
  const [recommendations, setRecommendations] = useState([]);
  const [stats, setStats] = useState(null);

  useEffect(() => {
    // Fetch dashboard data
    loadDashboardData();
  }, []);

  const loadDashboardData = async () => {
    try {
      // This would be replaced with actual API calls
      setStats({
        posts: 42,
        pages: 15,
        tests: 3,
        backups: 7
      });

      setRecommendations([
        {
          id: 1,
          title: __('Run SEO Technical Audit', 'gemini-command-center'),
          description: __('Check your site for technical SEO issues', 'gemini-command-center'),
          action: 'seo-audit',
          priority: 'high'
        },
        {
          id: 2,
          title: __('Create Content Backup', 'gemini-command-center'),
          description: __('Your last backup was 3 days ago', 'gemini-command-center'),
          action: 'create-backup',
          priority: 'medium'
        },
        {
          id: 3,
          title: __('Optimize Orphan Pages', 'gemini-command-center'),
          description: __('5 pages need internal links', 'gemini-command-center'),
          action: 'link-building',
          priority: 'medium'
        }
      ]);
    } catch (error) {
      console.error('Error loading dashboard:', error);
    }
  };

  const handleQuickAction = (action) => {
    switch (action) {
      case 'seo-audit':
        // Navigate to SEO tab or trigger audit
        console.log('Starting SEO audit...');
        break;
      case 'create-backup':
        // Navigate to backup tab or create backup
        console.log('Creating backup...');
        break;
      case 'link-building':
        // Navigate to link building
        console.log('Opening link architect...');
        break;
      default:
        console.log('Unknown action:', action);
    }
  };

  return (
    <div className="gemini-cc-tab-content">
      <h3>{__('Action Center Dashboard', 'gemini-command-center')}</h3>
      <p>{__('Your command center for AI-powered WordPress management.', 'gemini-command-center')}</p>

      {/* Stats Overview */}
      {stats && (
        <div className="dashboard-stats">
          <div className="stat-card">
            <h4>{stats.posts}</h4>
            <p>{__('Posts', 'gemini-command-center')}</p>
          </div>
          <div className="stat-card">
            <h4>{stats.pages}</h4>
            <p>{__('Pages', 'gemini-command-center')}</p>
          </div>
          <div className="stat-card">
            <h4>{stats.tests}</h4>
            <p>{__('A/B Tests', 'gemini-command-center')}</p>
          </div>
          <div className="stat-card">
            <h4>{stats.backups}</h4>
            <p>{__('Backups', 'gemini-command-center')}</p>
          </div>
        </div>
      )}

      {/* Recommendations */}
      <div className="dashboard-recommendations">
        <h4>{__('Top Recommendations', 'gemini-command-center')}</h4>
        {recommendations.map(rec => (
          <div key={rec.id} className={`recommendation-card priority-${rec.priority}`}>
            <div className="recommendation-content">
              <h5>{rec.title}</h5>
              <p>{rec.description}</p>
            </div>
            <button 
              className="button button-primary"
              onClick={() => handleQuickAction(rec.action)}
            >
              {__('Take Action', 'gemini-command-center')}
            </button>
          </div>
        ))}
      </div>

      {/* Quick Actions */}
      <div className="dashboard-quick-actions">
        <h4>{__('Quick Actions', 'gemini-command-center')}</h4>
        <div className="quick-actions-grid">
          <button 
            className="button button-secondary"
            onClick={() => handleQuickAction('seo-audit')}
          >
            🔍 {__('SEO Audit', 'gemini-command-center')}
          </button>
          <button 
            className="button button-secondary"
            onClick={() => handleQuickAction('create-backup')}
          >
            💾 {__('Create Backup', 'gemini-command-center')}
          </button>
          <button 
            className="button button-secondary"
            onClick={() => console.log('Generate content...')}
          >
            📝 {__('Generate Content', 'gemini-command-center')}
          </button>
          <button 
            className="button button-secondary"
            onClick={() => console.log('System health...')}
          >
            ❤️ {__('System Health', 'gemini-command-center')}
          </button>
        </div>
      </div>
    </div>
  );
};

export default Dashboard;