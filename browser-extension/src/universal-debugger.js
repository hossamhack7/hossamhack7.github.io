// Enhanced Universal Debugging System for Gemini Command Center
class UniversalGeminiDebugger {
  constructor() {
    this.logs = [];
    this.maxLogs = 500;
    this.isExtension = typeof chrome !== 'undefined' && chrome.extension;
    this.storageKey = 'gemini_cc_debug_logs';
    this.sessionId = this.generateSessionId();
    
    this.info('Debugger initialized', {
      session_id: this.sessionId,
      environment: this.detectEnvironment(),
      is_extension: this.isExtension
    });
  }
  
  generateSessionId() {
    return 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
  }
  
  detectEnvironment() {
    const env = {
      userAgent: navigator.userAgent,
      url: window.location.href,
      domain: window.location.hostname,
      protocol: window.location.protocol,
      viewport: {
        width: window.innerWidth,
        height: window.innerHeight
      },
      screen: {
        width: screen.width,
        height: screen.height
      },
      timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
      language: navigator.language,
      cookiesEnabled: navigator.cookieEnabled,
      onlineStatus: navigator.onLine
    };
    
    // Check for common CMS/frameworks
    const detections = {};
    if (window.wp || document.querySelector('meta[name*="wordpress"]') || document.querySelector('link[href*="wp-content"]')) {
      detections.wordpress = true;
    }
    if (window.Drupal || document.querySelector('meta[name*="drupal"]')) {
      detections.drupal = true;
    }
    if (window.Shopify || document.querySelector('meta[name*="shopify"]')) {
      detections.shopify = true;
    }
    if (window.angular || document.querySelector('[ng-app]')) {
      detections.angular = true;
    }
    if (window.React || document.querySelector('[data-react]')) {
      detections.react = true;
    }
    if (window.Vue || document.querySelector('[data-v-]')) {
      detections.vue = true;
    }
    
    env.detected_technologies = detections;
    return env;
  }
  
  log(level, message, data = null, error = null) {
    const timestamp = new Date().toISOString();
    const logEntry = {
      id: this.generateLogId(),
      timestamp,
      session_id: this.sessionId,
      level,
      message,
      data,
      error: error ? this.serializeError(error) : null,
      environment: {
        url: window.location.href,
        domain: window.location.hostname,
        user_agent: navigator.userAgent,
        viewport: `${window.innerWidth}x${window.innerHeight}`
      },
      stack_trace: error ? error.stack : (new Error()).stack
    };
    
    this.logs.push(logEntry);
    
    // Maintain log size limit
    if (this.logs.length > this.maxLogs) {
      this.logs.splice(0, this.logs.length - this.maxLogs);
    }
    
    // Console output with enhanced formatting
    this.outputToConsole(logEntry);
    
    // Persist logs
    this.persistLogs();
    
    // Send to extension background if in extension context
    if (this.isExtension) {
      this.sendToBackground(logEntry);
    }
    
    return logEntry;
  }
  
  generateLogId() {
    return 'log_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6);
  }
  
  serializeError(error) {
    if (!error) return null;
    
    return {
      name: error.name,
      message: error.message,
      stack: error.stack,
      code: error.code,
      status: error.status,
      response: error.response,
      toString: error.toString()
    };
  }
  
  outputToConsole(logEntry) {
    const styles = {
      error: 'background: #ff4444; color: white; font-weight: bold; padding: 2px 5px; border-radius: 3px',
      warn: 'background: #ffaa00; color: white; font-weight: bold; padding: 2px 5px; border-radius: 3px',
      info: 'background: #4444ff; color: white; padding: 2px 5px; border-radius: 3px',
      debug: 'background: #888888; color: white; padding: 2px 5px; border-radius: 3px',
      success: 'background: #44aa44; color: white; font-weight: bold; padding: 2px 5px; border-radius: 3px'
    };
    
    const style = styles[logEntry.level] || styles.debug;
    const prefix = `%c[Gemini CC ${logEntry.level.toUpperCase()}]`;
    
    console.groupCollapsed(`${prefix} ${logEntry.message}`, style);
    console.log('Timestamp:', logEntry.timestamp);
    console.log('Session ID:', logEntry.session_id);
    console.log('Log ID:', logEntry.id);
    if (logEntry.data) {
      console.log('Data:', logEntry.data);
    }
    if (logEntry.error) {
      console.error('Error Details:', logEntry.error);
    }
    console.log('Environment:', logEntry.environment);
    console.groupEnd();
  }
  
  persistLogs() {
    try {
      if (this.isExtension && chrome.storage) {
        // Use extension storage
        chrome.storage.local.set({
          [this.storageKey]: {
            logs: this.logs,
            last_updated: Date.now()
          }
        }).catch(err => {
          console.error('Failed to persist logs to extension storage:', err);
        });
      } else {
        // Use localStorage
        localStorage.setItem(this.storageKey, JSON.stringify({
          logs: this.logs,
          last_updated: Date.now()
        }));
      }
    } catch (error) {
      console.error('Failed to persist logs:', error);
    }
  }
  
  sendToBackground(logEntry) {
    if (this.isExtension && chrome.runtime) {
      chrome.runtime.sendMessage({
        type: 'GEMINI_DEBUG_LOG',
        payload: logEntry
      }).catch(err => {
        // Background script might not be ready, that's ok
      });
    }
  }
  
  async loadStoredLogs() {
    try {
      if (this.isExtension && chrome.storage) {
        const result = await chrome.storage.local.get(this.storageKey);
        return result[this.storageKey]?.logs || [];
      } else {
        const stored = localStorage.getItem(this.storageKey);
        return stored ? JSON.parse(stored).logs || [] : [];
      }
    } catch (error) {
      this.error('Failed to load stored logs', { error: error.message });
      return [];
    }
  }
  
  // Public logging methods
  error(message, data, error) { return this.log('error', message, data, error); }
  warn(message, data) { return this.log('warn', message, data); }
  info(message, data) { return this.log('info', message, data); }
  debug(message, data) { return this.log('debug', message, data); }
  success(message, data) { return this.log('success', message, data); }
  
  // Enhanced export functionality
  async exportLogs(includeStored = true) {
    const currentLogs = this.logs;
    const storedLogs = includeStored ? await this.loadStoredLogs() : [];
    
    return {
      export_timestamp: new Date().toISOString(),
      session_id: this.sessionId,
      environment: this.detectEnvironment(),
      stats: {
        current_session_logs: currentLogs.length,
        stored_logs: storedLogs.length,
        total_logs: currentLogs.length + storedLogs.length
      },
      current_session: currentLogs,
      stored_logs: storedLogs,
      system_info: {
        is_extension: this.isExtension,
        chrome_version: typeof chrome !== 'undefined' ? chrome.runtime?.getManifest()?.version : 'N/A',
        extension_id: typeof chrome !== 'undefined' ? chrome.runtime?.id : 'N/A'
      }
    };
  }
  
  // Enhanced clear functionality
  async clearLogs(clearStored = true) {
    this.logs = [];
    
    if (clearStored) {
      try {
        if (this.isExtension && chrome.storage) {
          await chrome.storage.local.remove(this.storageKey);
        } else {
          localStorage.removeItem(this.storageKey);
        }
        this.info('All logs cleared successfully');
      } catch (error) {
        this.error('Failed to clear stored logs', { error: error.message });
      }
    } else {
      this.info('Current session logs cleared');
    }
  }
  
  // Download logs as file
  async downloadLogs(filename = null) {
    try {
      const logs = await this.exportLogs();
      const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
      const defaultFilename = `gemini-cc-debug-${timestamp}.json`;
      
      const blob = new Blob([JSON.stringify(logs, null, 2)], { type: 'application/json' });
      const url = URL.createObjectURL(blob);
      
      if (this.isExtension && chrome.downloads) {
        // Use extension download API
        chrome.downloads.download({
          url: url,
          filename: filename || defaultFilename,
          saveAs: true
        });
      } else {
        // Use traditional download
        const a = document.createElement('a');
        a.href = url;
        a.download = filename || defaultFilename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
      }
      
      URL.revokeObjectURL(url);
      this.success('Debug logs downloaded successfully', { filename: filename || defaultFilename });
    } catch (error) {
      this.error('Failed to download logs', { error: error.message }, error);
    }
  }
  
  // Real-time monitoring setup
  setupRealtimeMonitoring() {
    // Monitor console errors
    const originalError = console.error;
    console.error = (...args) => {
      this.error('Console Error Detected', { args });
      originalError.apply(console, args);
    };
    
    // Monitor unhandled promise rejections
    window.addEventListener('unhandledrejection', (event) => {
      this.error('Unhandled Promise Rejection', {
        reason: event.reason,
        promise: event.promise
      }, event.reason);
    });
    
    // Monitor general errors
    window.addEventListener('error', (event) => {
      this.error('JavaScript Error', {
        message: event.message,
        filename: event.filename,
        lineno: event.lineno,
        colno: event.colno
      }, event.error);
    });
    
    // Monitor network errors (for fetch/xhr)
    this.setupNetworkMonitoring();
    
    this.info('Real-time monitoring setup completed');
  }
  
  setupNetworkMonitoring() {
    // Monitor fetch requests
    const originalFetch = window.fetch;
    window.fetch = async (...args) => {
      const startTime = performance.now();
      const url = args[0];
      const options = args[1] || {};
      
      try {
        const response = await originalFetch(...args);
        const endTime = performance.now();
        
        this.debug('Network Request Completed', {
          url,
          method: options.method || 'GET',
          status: response.status,
          statusText: response.statusText,
          duration: `${(endTime - startTime).toFixed(2)}ms`,
          ok: response.ok
        });
        
        if (!response.ok) {
          this.warn('Network Request Failed', {
            url,
            method: options.method || 'GET',
            status: response.status,
            statusText: response.statusText
          });
        }
        
        return response;
      } catch (error) {
        const endTime = performance.now();
        this.error('Network Request Error', {
          url,
          method: options.method || 'GET',
          duration: `${(endTime - startTime).toFixed(2)}ms`,
          error: error.message
        }, error);
        throw error;
      }
    };
    
    // Monitor XMLHttpRequest
    const originalXHROpen = XMLHttpRequest.prototype.open;
    const originalXHRSend = XMLHttpRequest.prototype.send;
    
    XMLHttpRequest.prototype.open = function(method, url, async, user, password) {
      this._gemini_debug = { method, url, startTime: performance.now() };
      return originalXHROpen.call(this, method, url, async, user, password);
    };
    
    XMLHttpRequest.prototype.send = function(body) {
      const debug = this._gemini_debug;
      if (debug) {
        this.addEventListener('load', () => {
          const endTime = performance.now();
          window.UniversalGeminiDebugger?.debug('XHR Request Completed', {
            url: debug.url,
            method: debug.method,
            status: this.status,
            statusText: this.statusText,
            duration: `${(endTime - debug.startTime).toFixed(2)}ms`,
            responseType: this.responseType
          });
        });
        
        this.addEventListener('error', () => {
          const endTime = performance.now();
          window.UniversalGeminiDebugger?.error('XHR Request Error', {
            url: debug.url,
            method: debug.method,
            duration: `${(endTime - debug.startTime).toFixed(2)}ms`
          });
        });
      }
      
      return originalXHRSend.call(this, body);
    };
  }
  
  // Performance monitoring
  measurePerformance(name, func) {
    const startTime = performance.now();
    const startMark = `gemini-${name}-start`;
    const endMark = `gemini-${name}-end`;
    
    performance.mark(startMark);
    
    try {
      const result = func();
      
      if (result instanceof Promise) {
        return result.finally(() => {
          const endTime = performance.now();
          performance.mark(endMark);
          this.debug(`Performance: ${name}`, {
            duration: `${(endTime - startTime).toFixed(2)}ms`,
            type: 'async'
          });
        });
      } else {
        const endTime = performance.now();
        performance.mark(endMark);
        this.debug(`Performance: ${name}`, {
          duration: `${(endTime - startTime).toFixed(2)}ms`,
          type: 'sync'
        });
        return result;
      }
    } catch (error) {
      const endTime = performance.now();
      this.error(`Performance Error in ${name}`, {
        duration: `${(endTime - startTime).toFixed(2)}ms`,
        error: error.message
      }, error);
      throw error;
    }
  }
}

// Initialize the universal debugger
if (typeof window !== 'undefined') {
  window.UniversalGeminiDebugger = new UniversalGeminiDebugger();
  window.UniversalGeminiDebugger.setupRealtimeMonitoring();
  
  // Backward compatibility
  window.GeminiDebugger = window.UniversalGeminiDebugger;
}

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
  module.exports = UniversalGeminiDebugger;
}