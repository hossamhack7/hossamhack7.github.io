import React from 'react';
import ReactDOM from 'react-dom';
import apiFetch from '@wordpress/api-fetch';
import App from './components/App';
import AgentApp from './components/AgentApp';
import './styles/main.css';

// Configure API fetch with nonce
if (window.gemini_cc_data && window.gemini_cc_data.nonce) {
  apiFetch.use((options, next) => {
    options.headers = {
      ...options.headers,
      'X-WP-Nonce': window.gemini_cc_data.nonce,
    };
    return next(options);
  });

  // Set the root path for API requests
  apiFetch.use(apiFetch.createRootURLMiddleware(window.gemini_cc_data.api_url));
}

// Initialize the appropriate component based on page
document.addEventListener('DOMContentLoaded', function() {
  const rootElement = document.getElementById('gcc-react-root');
  const agentElement = document.getElementById('gcc-agent-root');

  if (rootElement) {
    ReactDOM.render(<App />, rootElement);
  }

  if (agentElement) {
    ReactDOM.render(<AgentApp />, agentElement);
  }
});