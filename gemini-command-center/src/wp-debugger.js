// Enhanced WordPress-specific debugging system
const WordPressGeminiDebugger = {
  logs: [],
  log: function(level, message, data = null) {
    const timestamp = new Date().toISOString();
    const logEntry = {
      timestamp,
      level,
      message,
      data,
      url: window.location.href,
      userAgent: navigator.userAgent,
      wp_version: window.gemini_cc_data?.wp_version || 'unknown',
      plugin_version: window.gemini_cc_data?.plugin_version || 'unknown'
    };
    
    this.logs.push(logEntry);
    
    // Console logging with colors
    const styles = {
      error: 'color: #ff4444; font-weight: bold',
      warn: 'color: #ffaa00; font-weight: bold',
      info: 'color: #4444ff',
      debug: 'color: #888888'
    };
    
    console.log(`%c[Gemini CC ${level.toUpperCase()}] ${message}`, styles[level] || '', data || '');
    
    // Store in WordPress database via AJAX if possible
    if (window.wp && window.wp.ajax && level === 'error') {
      try {
        wp.ajax.post('gemini_cc_log_error', {
          log_entry: logEntry,
          _ajax_nonce: window.gemini_cc_data?.nonce
        }).catch(() => {
          // Silently fail if AJAX logging doesn't work
        });
      } catch (e) {
        // AJAX not available, continue with local logging
      }
    }
    
    // Store in localStorage for persistence
    try {
      const storedLogs = JSON.parse(localStorage.getItem('gemini_cc_debug_logs') || '[]');
      storedLogs.push(logEntry);
      // Keep only last 100 logs
      if (storedLogs.length > 100) {
        storedLogs.splice(0, storedLogs.length - 100);
      }
      localStorage.setItem('gemini_cc_debug_logs', JSON.stringify(storedLogs));
    } catch (e) {
      console.error('Failed to store debug log:', e);
    }
  },
  
  error: function(message, data) { this.log('error', message, data); },
  warn: function(message, data) { this.log('warn', message, data); },
  info: function(message, data) { this.log('info', message, data); },
  debug: function(message, data) { this.log('debug', message, data); },
  
  exportLogs: function() {
    return {
      current_session: this.logs,
      stored_logs: JSON.parse(localStorage.getItem('gemini_cc_debug_logs') || '[]'),
      system_info: {
        url: window.location.href,
        userAgent: navigator.userAgent,
        timestamp: new Date().toISOString(),
        gemini_cc_data: window.gemini_cc_data || null,
        wp_version: window.gemini_cc_data?.wp_version || 'unknown',
        plugin_version: window.gemini_cc_data?.plugin_version || 'unknown'
      }
    };
  },
  
  clearLogs: function() {
    this.logs = [];
    localStorage.removeItem('gemini_cc_debug_logs');
    this.info('Debug logs cleared');
  },
  
  downloadLogs: function() {
    try {
      const logs = this.exportLogs();
      const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
      const filename = `gemini-cc-wp-debug-${timestamp}.json`;
      
      const blob = new Blob([JSON.stringify(logs, null, 2)], { type: 'application/json' });
      const url = URL.createObjectURL(blob);
      
      const a = document.createElement('a');
      a.href = url;
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
      
      this.info('Debug logs downloaded successfully', { filename });
    } catch (error) {
      this.error('Failed to download logs', { error: error.message });
    }
  }
};

// Make debugger globally available with backward compatibility
window.GeminiDebugger = WordPressGeminiDebugger;
window.UniversalGeminiDebugger = WordPressGeminiDebugger;