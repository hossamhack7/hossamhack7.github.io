import React from 'react';
import ReactDOM from 'react-dom';
import apiFetch from '@wordpress/api-fetch';
import App from './components/App';
import AgentApp from './components/AgentApp';
import './styles/main.css';
import './wp-debugger.js'; // Import WordPress-specific debugger

// Function to configure API fetch with enhanced error handling
function configureApiFetch() {
  GeminiDebugger.info('Configuring API fetch...');
  
  if (!window.gemini_cc_data) {
    GeminiDebugger.error('window.gemini_cc_data is not available', {
      available_globals: Object.keys(window).filter(key => key.includes('gemini'))
    });
    return false;
  }
  
  if (!window.gemini_cc_data.nonce) {
    GeminiDebugger.error('Nonce is not available in gemini_cc_data', window.gemini_cc_data);
    return false;
  }
  
  if (!window.gemini_cc_data.api_url) {
    GeminiDebugger.error('API URL is not available in gemini_cc_data', window.gemini_cc_data);
    return false;
  }
  
  GeminiDebugger.info('Gemini CC data available', {
    api_url: window.gemini_cc_data.api_url,
    has_nonce: !!window.gemini_cc_data.nonce,
    current_user: window.gemini_cc_data.current_user
  });

  // Enhanced nonce middleware with error tracking
  apiFetch.use((options, next) => {
    // Log API request details
    GeminiDebugger.debug('API Request:', {
      path: options.path,
      method: options.method || 'GET',
      data: options.data,
      headers: options.headers
    });
    
    options.headers = {
      ...options.headers,
      'X-WP-Nonce': window.gemini_cc_data.nonce,
    };
    
    return next(options).catch(error => {
      // Enhanced error logging
      GeminiDebugger.error('API Request Failed', {
        path: options.path,
        method: options.method || 'GET',
        error: error.message,
        status: error.status || 'unknown',
        response: error.response || 'no response',
        full_error: error
      });
      
      // Re-throw the error so it can be handled by the calling code
      throw error;
    });
  });

  // Set the root path for API requests with validation
  try {
    apiFetch.use(apiFetch.createRootURLMiddleware(window.gemini_cc_data.api_url));
    GeminiDebugger.info('API fetch configured successfully', {
      root_url: window.gemini_cc_data.api_url,
      nonce_present: true
    });
    return true;
  } catch (error) {
    GeminiDebugger.error('Failed to configure root URL middleware', {
      api_url: window.gemini_cc_data.api_url,
      error: error.message
    });
    return false;
  }
}

// Try to configure immediately with better error handling
const initialConfigResult = configureApiFetch();
if (!initialConfigResult) {
  GeminiDebugger.error('Initial API configuration failed');
}

// Enhanced initialization with better error detection
document.addEventListener('DOMContentLoaded', function() {
  GeminiDebugger.info('DOM loaded, initializing Gemini CC WordPress Plugin...');
  
  // Ensure API fetch is configured (retry if needed)
  if (!configureApiFetch()) {
    GeminiDebugger.error('Failed to configure API fetch - plugin may not work correctly');
    
    // Show error to user
    const showErrorMessage = (elementId, message) => {
      const element = document.getElementById(elementId);
      if (element) {
        element.innerHTML = `
          <div class="gemini-cc-error">
            <h3>Configuration Error</h3>
            <p>${message}</p>
            <p><strong>Debug Information:</strong></p>
            <details>
              <summary>Show Debug Info</summary>
              <pre>${JSON.stringify(GeminiDebugger.exportLogs(), null, 2)}</pre>
            </details>
            <button onclick="window.GeminiDebugger.clearLogs(); location.reload()">Clear Logs & Retry</button>
            <button onclick="window.GeminiDebugger.downloadLogs()">Download Debug Logs</button>
          </div>
        `;
      }
    };
    
    showErrorMessage('gcc-react-root', 'Failed to configure API connection. Please check the debug information above.');
    showErrorMessage('gcc-agent-root', 'Failed to configure API connection. Please check the debug information above.');
    return;
  }

  const rootElement = document.getElementById('gcc-react-root');
  const agentElement = document.getElementById('gcc-agent-root');

  if (rootElement) {
    GeminiDebugger.info('Rendering main WordPress app');
    try {
      ReactDOM.render(<App />, rootElement);
    } catch (error) {
      GeminiDebugger.error('Failed to render main app', error);
    }
  }

  if (agentElement) {
    GeminiDebugger.info('Rendering agent app');
    try {
      ReactDOM.render(<AgentApp />, agentElement);
    } catch (error) {
      GeminiDebugger.error('Failed to render agent app', error);
    }
  }
  
  // Add global error handler for unhandled promise rejections
  window.addEventListener('unhandledrejection', function(event) {
    GeminiDebugger.error('Unhandled promise rejection', {
      reason: event.reason,
      promise: event.promise
    });
  });
  
  // Add global error handler
  window.addEventListener('error', function(event) {
    GeminiDebugger.error('Global error', {
      message: event.message,
      filename: event.filename,
      lineno: event.lineno,
      colno: event.colno,
      error: event.error
    });
  });
});