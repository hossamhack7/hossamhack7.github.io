// Popup JavaScript for Gemini Command Center Extension
class GeminiPopup {
  constructor() {
    this.currentTab = null;
    this.settings = {};
    this.pageAnalysis = null;
    this.logs = [];
    
    this.init();
  }
  
  async init() {
    try {
      // Get current tab
      const tabs = await chrome.tabs.query({ active: true, currentWindow: true });
      this.currentTab = tabs[0];
      
      // Load settings
      await this.loadSettings();
      
      // Load debug logs
      await this.loadDebugLogs();
      
      // Setup event listeners
      this.setupEventListeners();
      
      // Update UI
      this.updateUI();
      
      // Get page analysis
      await this.getPageAnalysis();
      
      this.addLog('info', 'Popup initialized successfully');
      
    } catch (error) {
      console.error('Failed to initialize popup:', error);
      this.addLog('error', `Initialization failed: ${error.message}`);
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
        }
      };
      
      this.addLog('info', 'Settings loaded');
    } catch (error) {
      this.addLog('error', `Failed to load settings: ${error.message}`);
    }
  }
  
  async loadDebugLogs() {
    try {
      const response = await chrome.runtime.sendMessage({ type: 'GET_DEBUG_LOGS' });
      if (response.success) {
        this.logs = [
          ...response.logs.background_logs,
          ...response.logs.content_logs
        ].sort((a, b) => new Date(a.timestamp) - new Date(b.timestamp));
        
        this.updateDebugLogs();
      }
    } catch (error) {
      this.addLog('error', `Failed to load debug logs: ${error.message}`);
    }
  }
  
  setupEventListeners() {
    // Toggle widget
    document.getElementById('toggle-widget').addEventListener('click', () => {
      this.toggleWidget();
    });
    
    // Analyze page
    document.getElementById('analyze-page').addEventListener('click', () => {
      this.analyzePage();
    });
    
    // SEO check
    document.getElementById('seo-check').addEventListener('click', () => {
      this.performSEOCheck();
    });
    
    // Inject script
    document.getElementById('inject-script').addEventListener('click', () => {
      this.injectScript();
    });
    
    // Clear logs
    document.getElementById('clear-logs').addEventListener('click', () => {
      this.clearLogs();
    });
    
    // Export logs
    document.getElementById('export-logs').addEventListener('click', () => {
      this.exportLogs();
    });
    
    // Auto-inject toggle
    document.getElementById('auto-inject-toggle').addEventListener('change', (e) => {
      this.updateAutoInject(e.target.checked);
    });
    
    // Listen for background notifications
    chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
      if (message.type === 'BACKGROUND_NOTIFICATION') {
        this.handleBackgroundNotification(message);
      }
    });
  }
  
  updateUI() {
    // Update connection status
    this.updateConnectionStatus();
    
    // Update API key status
    this.updateAPIKeyStatus();
    
    // Update auto-inject toggle
    document.getElementById('auto-inject-toggle').checked = this.settings.auto_inject;
    
    // Update current page info
    this.updatePageInfo();
  }
  
  updateConnectionStatus() {
    const statusElement = document.getElementById('connection-status');
    const indicatorElement = document.getElementById('connection-indicator');
    
    if (this.currentTab && this.isSupportedPage()) {
      statusElement.textContent = 'Supported';
      indicatorElement.className = 'status-indicator success';
    } else {
      statusElement.textContent = 'Not supported';
      indicatorElement.className = 'status-indicator warning';
    }
  }
  
  updateAPIKeyStatus() {
    const statusElement = document.getElementById('api-key-status');
    const indicatorElement = document.getElementById('api-indicator');
    
    if (this.settings.api_key && this.settings.api_key.length > 10) {
      statusElement.textContent = 'Configured';
      indicatorElement.className = 'status-indicator success';
    } else {
      statusElement.textContent = 'Not configured';
      indicatorElement.className = 'status-indicator error';
    }
  }
  
  updatePageInfo() {
    if (this.currentTab) {
      // You could add page-specific info here
    }
  }
  
  isSupportedPage() {
    if (!this.currentTab || !this.currentTab.url) return false;
    
    const url = this.currentTab.url;
    return url.startsWith('http://') || url.startsWith('https://');
  }
  
  async toggleWidget() {
    try {
      if (!this.isSupportedPage()) {
        this.addLog('warn', 'Cannot toggle widget on this page');
        return;
      }
      
      await chrome.tabs.sendMessage(this.currentTab.id, {
        type: 'TOGGLE_WIDGET'
      });
      
      this.addLog('info', 'Widget toggled');
      
    } catch (error) {
      this.addLog('error', `Failed to toggle widget: ${error.message}`);
      
      // Try to inject script first
      try {
        await this.injectScript();
        await chrome.tabs.sendMessage(this.currentTab.id, {
          type: 'TOGGLE_WIDGET'
        });
        this.addLog('info', 'Widget toggled after script injection');
      } catch (injectError) {
        this.addLog('error', `Failed to inject and toggle: ${injectError.message}`);
      }
    }
  }
  
  async analyzePage() {
    try {
      if (!this.isSupportedPage()) {
        this.addLog('warn', 'Cannot analyze this page');
        return;
      }
      
      this.addLog('info', 'Starting page analysis...');
      
      const response = await chrome.tabs.sendMessage(this.currentTab.id, {
        type: 'ANALYZE_PAGE'
      });
      
      if (response.success) {
        this.pageAnalysis = response.analysis;
        this.updateAnalysisUI();
        this.addLog('info', 'Page analysis completed');
      }
      
    } catch (error) {
      this.addLog('error', `Page analysis failed: ${error.message}`);
      
      // Try to inject script first
      try {
        await this.injectScript();
        await this.analyzePage();
      } catch (injectError) {
        this.addLog('error', `Failed to inject and analyze: ${injectError.message}`);
      }
    }
  }
  
  async performSEOCheck() {
    try {
      await this.analyzePage();
      
      if (this.pageAnalysis && this.pageAnalysis.seo) {
        const seo = this.pageAnalysis.seo;
        this.addLog('info', `SEO Check: Score ${seo.score}/100, ${seo.issues.length} issues found`);
        
        // Show detailed results
        if (seo.issues.length > 0) {
          this.addLog('warn', `SEO Issues: ${seo.issues.join(', ')}`);
        }
      }
      
    } catch (error) {
      this.addLog('error', `SEO check failed: ${error.message}`);
    }
  }
  
  async injectScript() {
    try {
      if (!this.isSupportedPage()) {
        this.addLog('warn', 'Cannot inject script on this page');
        return;
      }
      
      this.addLog('info', 'Injecting Gemini scripts...');
      
      await chrome.runtime.sendMessage({
        type: 'INJECT_GEMINI_SCRIPT'
      });
      
      this.addLog('info', 'Scripts injected successfully');
      
    } catch (error) {
      this.addLog('error', `Script injection failed: ${error.message}`);
    }
  }
  
  async clearLogs() {
    try {
      await chrome.runtime.sendMessage({ type: 'CLEAR_DEBUG_LOGS' });
      this.logs = [];
      this.updateDebugLogs();
      this.addLog('info', 'Debug logs cleared');
    } catch (error) {
      this.addLog('error', `Failed to clear logs: ${error.message}`);
    }
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
        
        this.addLog('info', 'Debug logs exported successfully');
      }
      
    } catch (error) {
      this.addLog('error', `Failed to export logs: ${error.message}`);
    }
  }
  
  async updateAutoInject(enabled) {
    try {
      this.settings.auto_inject = enabled;
      
      await chrome.storage.local.set({
        'gemini_settings': this.settings
      });
      
      this.addLog('info', `Auto-inject ${enabled ? 'enabled' : 'disabled'}`);
      
    } catch (error) {
      this.addLog('error', `Failed to update auto-inject: ${error.message}`);
    }
  }
  
  updateAnalysisUI() {
    if (!this.pageAnalysis) return;
    
    // Update SEO score
    const seoScore = this.pageAnalysis.seo?.score || 0;
    document.getElementById('seo-score').textContent = seoScore;
    
    // Update error count
    const errorCount = this.pageAnalysis.seo?.issues?.length || 0;
    document.getElementById('errors-count').textContent = errorCount;
    
    // Update colors based on scores
    const seoElement = document.getElementById('seo-score');
    if (seoScore >= 80) {
      seoElement.style.color = '#44aa44';
    } else if (seoScore >= 60) {
      seoElement.style.color = '#ffaa00';
    } else {
      seoElement.style.color = '#ff4444';
    }
  }
  
  addLog(level, message) {
    const logEntry = {
      timestamp: new Date().toISOString(),
      level,
      message,
      id: 'popup_' + Date.now()
    };
    
    this.logs.push(logEntry);
    
    // Keep only last 50 logs in popup
    if (this.logs.length > 50) {
      this.logs.splice(0, this.logs.length - 50);
    }
    
    this.updateDebugLogs();
  }
  
  updateDebugLogs() {
    const logsContainer = document.getElementById('debug-logs');
    
    // Show only recent logs (last 20)
    const recentLogs = this.logs.slice(-20);
    
    logsContainer.innerHTML = recentLogs.map(log => {
      const time = new Date(log.timestamp).toLocaleTimeString();
      return `<div class="log-entry ${log.level}">[${time}] ${log.message}</div>`;
    }).join('');
    
    // Scroll to bottom
    logsContainer.scrollTop = logsContainer.scrollHeight;
  }
  
  async getPageAnalysis() {
    try {
      if (!this.isSupportedPage()) return;
      
      const response = await chrome.tabs.sendMessage(this.currentTab.id, {
        type: 'GET_PAGE_INFO'
      });
      
      if (response.success) {
        this.pageAnalysis = { basic: response.pageInfo };
        this.updateAnalysisUI();
      }
      
    } catch (error) {
      // Content script might not be injected yet, that's ok
      this.addLog('debug', 'Could not get page analysis (script not injected)');
    }
  }
  
  handleBackgroundNotification(message) {
    switch (message.notification_type) {
      case 'DEBUG_LOG_ADDED':
        this.logs.push(message.data);
        this.updateDebugLogs();
        break;
        
      case 'DEBUG_LOGS_CLEARED':
        this.logs = [];
        this.updateDebugLogs();
        break;
        
      case 'STORAGE_CHANGED':
        this.loadSettings();
        break;
    }
  }
}

// Initialize popup when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
  new GeminiPopup();
});