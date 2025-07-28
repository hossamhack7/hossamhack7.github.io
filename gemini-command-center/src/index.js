import React from 'react';
import ReactDOM from 'react-dom';
import apiFetch from '@wordpress/api-fetch';
import App from './components/App';
import AgentApp from './components/AgentApp';
import './styles/main.css';

// Function to configure API fetch with nonce and root URL
function configureApiFetch() {
  if (window.gemini_cc_data && window.gemini_cc_data.nonce) {
    // Set up nonce middleware
    apiFetch.use((options, next) => {
      options.headers = {
        ...options.headers,
        'X-WP-Nonce': window.gemini_cc_data.nonce,
      };
      return next(options);
    });

    // Set the root path for API requests
    apiFetch.use(apiFetch.createRootURLMiddleware(window.gemini_cc_data.api_url));
    
    console.log('Gemini CC: API fetch configured with nonce and root URL');
    return true;
  } else {
    console.warn('Gemini CC: window.gemini_cc_data or nonce not available');
    return false;
  }
}

// Try to configure immediately
configureApiFetch();

// Initialize the appropriate component based on page
document.addEventListener('DOMContentLoaded', function() {
  // Ensure API fetch is configured (retry if needed)
  if (!configureApiFetch()) {
    console.error('Gemini CC: Failed to configure API fetch - plugin may not work correctly');
  }

  const rootElement = document.getElementById('gcc-react-root');
  const agentElement = document.getElementById('gcc-agent-root');

  if (rootElement) {
    ReactDOM.render(<App />, rootElement);
  }

  if (agentElement) {
    ReactDOM.render(<AgentApp />, agentElement);
  }
});