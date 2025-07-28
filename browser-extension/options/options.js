// Options page JavaScript for Gemini Command Center Extension
class GeminiOptions {
  constructor() {
    this.settings = {};
    this.stats = {};
    this.logs = [];
    
    this.init();
  }
  
  async init() {
    try {
      await this.loadSettings();
      await this.loadStats();
      await this.loadLogs();
      
      this.setupEventListeners();
      this.updateUI();
      
      this.showMessage('Options page loaded successfully', 'success');
      
    } catch (error) {
      console.error('Failed to initialize options:', error);
      this.showMessage(`Initialization failed: ${error.message}`, 'error');
    }
  }
  
  async loadSettings() {
    try {
      const result = await chrome.storage.local.get('gemini_settings');
      this.settings = result.gemini_settings || {
        auto_inject: false,
        api_key: '',
        debug_level: 'info',
        enabled_features: {
          seo: true,
          content: true,
          debugging: true
        },
        first_install: true,
        install_date: new Date().toISOString()
      };
    } catch (error) {
      console.error('Failed to load settings:', error);
      throw error;
    }
  }
  
  async loadStats() {
    try {
      const result = await chrome.storage.local.get('gemini_stats');
      this.stats = result.gemini_stats || {
        pages_analyzed: 0,
        errors_found: 0,
        logs_count: 0,
        active_days: 0
      };
    } catch (error) {
      console.error('Failed to load stats:', error);
      this.stats = {
        pages_analyzed: 0,
        errors_found: 0,
        logs_count: 0,
        active_days: 0
      };
    }
  }
  
  async loadLogs() {
    try {
      const response = await chrome.runtime.sendMessage({ type: 'GET_DEBUG_LOGS' });
      if (response.success) {
        this.logs = [
          ...response.logs.background_logs,
          ...response.logs.content_logs
        ].sort((a, b) => new Date(a.timestamp) - new Date(b.timestamp));
        
        this.updateDebugLogs();
        this.updateLogStats();
      }
    } catch (error) {
      console.error('Failed to load logs:', error);
      this.logs = [];
    }
  }
  
  setupEventListeners() {
    // Save settings
    document.getElementById('save-settings').addEventListener('click', () => {
      this.saveSettings();
    });
    
    // Reset settings
    document.getElementById('reset-settings').addEventListener('click', () => {
      this.resetSettings();
    });
    
    // Test API
    document.getElementById('test-api').addEventListener('click', () => {
      this.testAPI();
    });
    
    // Export settings
    document.getElementById('export-settings').addEventListener('click', () => {
      this.exportSettings();
    });
    
    // Import settings
    document.getElementById('import-settings').addEventListener('click', () => {
      this.importSettings();
    });
    
    // Refresh logs
    document.getElementById('refresh-logs').addEventListener('click', () => {
      this.loadLogs();
    });
    
    // Export logs
    document.getElementById('export-logs').addEventListener('click', () => {
      this.exportLogs();
    });
    
    // Clear logs
    document.getElementById('clear-logs').addEventListener('click', () => {
      this.clearLogs();
    });
    
    // Auto-save on form changes
    this.setupAutoSave();
  }
  
  setupAutoSave() {
    const formElements = [
      'auto-inject',
      'debug-level',
      'feature-seo',
      'feature-content',
      'feature-debugging'
    ];
    
    formElements.forEach(id => {
      const element = document.getElementById(id);
      if (element) {
        element.addEventListener('change', () => {
          this.autoSave();
        });
      }
    });
    
    // API key with debounce
    let apiKeyTimeout;
    document.getElementById('api-key').addEventListener('input', () => {
      clearTimeout(apiKeyTimeout);
      apiKeyTimeout = setTimeout(() => {
        this.autoSave();
      }, 1000);
    });
  }
  
  updateUI() {
    // Update form fields
    document.getElementById('api-key').value = this.settings.api_key || '';
    document.getElementById('auto-inject').checked = this.settings.auto_inject || false;
    document.getElementById('debug-level').value = this.settings.debug_level || 'info';
    
    // Update feature checkboxes
    const features = this.settings.enabled_features || {};
    document.getElementById('feature-seo').checked = features.seo !== false;
    document.getElementById('feature-content').checked = features.content !== false;
    document.getElementById('feature-debugging').checked = features.debugging !== false;
    
    // Update stats
    this.updateStats();
  }
  
  updateStats() {
    document.getElementById('pages-analyzed').textContent = this.stats.pages_analyzed || 0;
    document.getElementById('errors-found').textContent = this.stats.errors_found || 0;
    document.getElementById('logs-count').textContent = this.logs.length;
    
    // Calculate active days
    if (this.settings.install_date) {
      const installDate = new Date(this.settings.install_date);
      const today = new Date();
      const diffTime = Math.abs(today - installDate);
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
      document.getElementById('extension-active-days').textContent = diffDays;
    } else {
      document.getElementById('extension-active-days').textContent = '0';
    }
  }
  
  updateLogStats() {
    this.stats.logs_count = this.logs.length;
    this.updateStats();
  }
  
  async saveSettings() {
    try {
      // Collect form data
      const newSettings = {
        ...this.settings,
        api_key: document.getElementById('api-key').value,
        auto_inject: document.getElementById('auto-inject').checked,
        debug_level: document.getElementById('debug-level').value,
        enabled_features: {
          seo: document.getElementById('feature-seo').checked,
          content: document.getElementById('feature-content').checked,
          debugging: document.getElementById('feature-debugging').checked
        }
      };
      
      // Save to storage
      await chrome.storage.local.set({
        'gemini_settings': newSettings
      });
      
      this.settings = newSettings;
      this.showMessage('Settings saved successfully!', 'success');
      
    } catch (error) {
      console.error('Failed to save settings:', error);
      this.showMessage(`Failed to save settings: ${error.message}`, 'error');
    }
  }
  
  async autoSave() {
    try {
      await this.saveSettings();
    } catch (error) {
      // Silently fail auto-save to avoid annoying the user
      console.error('Auto-save failed:', error);
    }
  }
  
  async resetSettings() {
    if (!confirm('Are you sure you want to reset all settings to defaults? This cannot be undone.')) {
      return;
    }
    
    try {
      const defaultSettings = {
        auto_inject: false,
        api_key: '',
        debug_level: 'info',
        enabled_features: {
          seo: true,
          content: true,
          debugging: true
        },
        first_install: false,
        install_date: this.settings.install_date || new Date().toISOString()
      };
      
      await chrome.storage.local.set({
        'gemini_settings': defaultSettings
      });
      
      this.settings = defaultSettings;
      this.updateUI();
      this.showMessage('Settings reset to defaults', 'success');
      
    } catch (error) {
      console.error('Failed to reset settings:', error);
      this.showMessage(`Failed to reset settings: ${error.message}`, 'error');
    }
  }
  
  async testAPI() {
    const apiKey = document.getElementById('api-key').value;
    const resultDiv = document.getElementById('api-test-result');
    
    if (!apiKey.trim()) {
      resultDiv.innerHTML = '<div class="status-error">Please enter an API key first</div>';
      return;
    }
    
    resultDiv.innerHTML = '<div class="status-message">Testing API connection...</div>';
    
    try {
      const response = await chrome.runtime.sendMessage({
        type: 'TEST_API_CONNECTION',
        payload: { api_key: apiKey }
      });
      
      if (response.success) {
        if (response.result.success) {
          resultDiv.innerHTML = `
            <div class="api-test-result status-success">
              <strong>✅ Connection Successful!</strong><br>
              Models available: ${response.result.models_available}<br>
              Tested at: ${new Date(response.result.test_timestamp).toLocaleString()}
            </div>
          `;
        } else {
          resultDiv.innerHTML = `
            <div class="api-test-result status-error">
              <strong>❌ Connection Failed</strong><br>
              Error: ${response.result.message}<br>
              Tested at: ${new Date(response.result.test_timestamp).toLocaleString()}
            </div>
          `;
        }
      } else {
        resultDiv.innerHTML = `
          <div class="api-test-result status-error">
            <strong>❌ Test Failed</strong><br>
            Error: ${response.error || 'Unknown error'}
          </div>
        `;
      }
      
    } catch (error) {
      console.error('API test failed:', error);
      resultDiv.innerHTML = `
        <div class="api-test-result status-error">
          <strong>❌ Test Error</strong><br>
          Error: ${error.message}
        </div>
      `;
    }
  }
  
  async exportSettings() {
    try {
      const exportData = {
        settings: this.settings,
        stats: this.stats,
        export_timestamp: new Date().toISOString(),
        version: '1.0.0'
      };
      
      const blob = new Blob([JSON.stringify(exportData, null, 2)], {
        type: 'application/json'
      });
      
      const url = URL.createObjectURL(blob);
      const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
      
      await chrome.downloads.download({
        url: url,
        filename: `gemini-cc-settings-${timestamp}.json`,
        saveAs: true
      });
      
      this.showMessage('Settings exported successfully', 'success');
      
    } catch (error) {
      console.error('Failed to export settings:', error);
      this.showMessage(`Failed to export settings: ${error.message}`, 'error');
    }
  }
  
  importSettings() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.json';
    
    input.addEventListener('change', async (e) => {
      const file = e.target.files[0];
      if (!file) return;
      
      try {
        const text = await file.text();
        const importData = JSON.parse(text);
        
        if (!importData.settings) {
          throw new Error('Invalid settings file format');
        }
        
        if (!confirm('Are you sure you want to import these settings? Current settings will be overwritten.')) {
          return;
        }
        
        await chrome.storage.local.set({
          'gemini_settings': importData.settings
        });
        
        if (importData.stats) {
          await chrome.storage.local.set({
            'gemini_stats': importData.stats
          });
        }
        
        // Reload page to reflect changes
        window.location.reload();
        
      } catch (error) {
        console.error('Failed to import settings:', error);
        this.showMessage(`Failed to import settings: ${error.message}`, 'error');
      }
    });
    
    input.click();
  }
  
  async exportLogs() {
    try {
      const response = await chrome.runtime.sendMessage({ type: 'EXPORT_DEBUG_LOGS' });
      
      if (response.success) {
        const blob = new Blob([JSON.stringify(response.data, null, 2)], {
          type: 'application/json'
        });
        
        const url = URL.createObjectURL(blob);
        const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
        
        await chrome.downloads.download({
          url: url,
          filename: `gemini-cc-debug-${timestamp}.json`,
          saveAs: true
        });
        
        this.showMessage('Debug logs exported successfully', 'success');
      }
      
    } catch (error) {
      console.error('Failed to export logs:', error);
      this.showMessage(`Failed to export logs: ${error.message}`, 'error');
    }
  }
  
  async clearLogs() {
    if (!confirm('Are you sure you want to clear all debug logs? This cannot be undone.')) {
      return;
    }
    
    try {
      await chrome.runtime.sendMessage({ type: 'CLEAR_DEBUG_LOGS' });
      this.logs = [];
      this.updateDebugLogs();
      this.updateLogStats();
      this.showMessage('Debug logs cleared successfully', 'success');
      
    } catch (error) {
      console.error('Failed to clear logs:', error);
      this.showMessage(`Failed to clear logs: ${error.message}`, 'error');
    }
  }
  
  updateDebugLogs() {
    const logsContainer = document.getElementById('debug-logs');
    
    if (this.logs.length === 0) {
      logsContainer.innerHTML = '<div class="log-entry info">No logs available</div>';
      return;
    }
    
    // Show only recent logs (last 50)
    const recentLogs = this.logs.slice(-50);
    
    logsContainer.innerHTML = recentLogs.map(log => {
      const time = new Date(log.timestamp).toLocaleString();
      const domain = log.environment?.domain || 'unknown';
      return `<div class="log-entry ${log.level}">[${time}] [${domain}] ${log.message}</div>`;
    }).join('');
    
    // Scroll to bottom
    logsContainer.scrollTop = logsContainer.scrollHeight;
  }
  
  showMessage(message, type = 'info') {
    const messageDiv = document.getElementById('status-message');
    messageDiv.className = `status-message status-${type}`;
    messageDiv.textContent = message;
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
      messageDiv.textContent = '';
      messageDiv.className = '';
    }, 5000);
  }
}

// Initialize options page when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
  new GeminiOptions();
});