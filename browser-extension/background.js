// Background Service Worker for Gemini Command Center Extension
import './src/universal-debugger.js';

class GeminiExtensionBackground {
  constructor() {
    this.debugLogs = [];
    this.maxBackgroundLogs = 1000;
    this.init();
  }
  
  init() {
    this.setupMessageHandlers();
    this.setupTabsHandlers();
    this.setupStorageHandlers();
    console.log('Gemini Command Center Background Service Worker initialized');
  }
  
  setupMessageHandlers() {
    chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
      this.handleMessage(message, sender, sendResponse);
      return true; // Keep message channel open for async response
    });
  }
  
  setupTabsHandlers() {
    chrome.tabs.onUpdated.addListener((tabId, changeInfo, tab) => {
      if (changeInfo.status === 'complete' && tab.url) {
        this.onTabReady(tabId, tab);
      }
    });
    
    chrome.tabs.onActivated.addListener((activeInfo) => {
      this.onTabActivated(activeInfo.tabId);
    });
  }
  
  setupStorageHandlers() {
    chrome.storage.onChanged.addListener((changes, namespace) => {
      this.onStorageChanged(changes, namespace);
    });
  }
  
  async handleMessage(message, sender, sendResponse) {
    try {
      switch (message.type) {
        case 'GEMINI_DEBUG_LOG':
          await this.handleDebugLog(message.payload, sender);
          sendResponse({ success: true });
          break;
          
        case 'GET_DEBUG_LOGS':
          const logs = await this.getDebugLogs();
          sendResponse({ success: true, logs });
          break;
          
        case 'CLEAR_DEBUG_LOGS':
          await this.clearDebugLogs();
          sendResponse({ success: true });
          break;
          
        case 'EXPORT_DEBUG_LOGS':
          const exportData = await this.exportDebugLogs();
          sendResponse({ success: true, data: exportData });
          break;
          
        case 'INJECT_GEMINI_SCRIPT':
          await this.injectGeminiScript(sender.tab.id);
          sendResponse({ success: true });
          break;
          
        case 'GET_TAB_INFO':
          const tabInfo = await this.getTabInfo(sender.tab.id);
          sendResponse({ success: true, tabInfo });
          break;
          
        case 'TEST_API_CONNECTION':
          const apiResult = await this.testApiConnection(message.payload);
          sendResponse({ success: true, result: apiResult });
          break;
          
        default:
          console.warn('Unknown message type:', message.type);
          sendResponse({ success: false, error: 'Unknown message type' });
      }
    } catch (error) {
      console.error('Error handling message:', error);
      sendResponse({ success: false, error: error.message });
    }
  }
  
  async handleDebugLog(logEntry, sender) {
    // Enhance log entry with sender information
    const enhancedLog = {
      ...logEntry,
      sender: {
        tab_id: sender.tab?.id,
        tab_url: sender.tab?.url,
        tab_title: sender.tab?.title,
        frame_id: sender.frameId,
        origin: sender.origin,
        extension_id: sender.id
      },
      background_timestamp: new Date().toISOString()
    };
    
    this.debugLogs.push(enhancedLog);
    
    // Maintain log size limit
    if (this.debugLogs.length > this.maxBackgroundLogs) {
      this.debugLogs.splice(0, this.debugLogs.length - this.maxBackgroundLogs);
    }
    
    // Store in extension storage
    await chrome.storage.local.set({
      'gemini_background_logs': {
        logs: this.debugLogs,
        last_updated: Date.now()
      }
    });
    
    // Notify popup/options if open
    this.notifyUIComponents('DEBUG_LOG_ADDED', enhancedLog);
  }
  
  async getDebugLogs() {
    try {
      const result = await chrome.storage.local.get(['gemini_background_logs', 'gemini_cc_debug_logs']);
      return {
        background_logs: result.gemini_background_logs?.logs || [],
        content_logs: result.gemini_cc_debug_logs?.logs || []
      };
    } catch (error) {
      console.error('Error getting debug logs:', error);
      return { background_logs: [], content_logs: [] };
    }
  }
  
  async clearDebugLogs() {
    this.debugLogs = [];
    await chrome.storage.local.remove(['gemini_background_logs', 'gemini_cc_debug_logs']);
    this.notifyUIComponents('DEBUG_LOGS_CLEARED');
  }
  
  async exportDebugLogs() {
    const logs = await this.getDebugLogs();
    const tabs = await chrome.tabs.query({});
    
    return {
      export_timestamp: new Date().toISOString(),
      extension_info: {
        version: chrome.runtime.getManifest().version,
        id: chrome.runtime.id
      },
      browser_info: {
        user_agent: navigator.userAgent,
        tabs_count: tabs.length,
        active_tabs: tabs.filter(tab => tab.active)
      },
      logs: logs,
      stats: {
        background_logs: logs.background_logs.length,
        content_logs: logs.content_logs.length,
        total_logs: logs.background_logs.length + logs.content_logs.length
      }
    };
  }
  
  async injectGeminiScript(tabId) {
    try {
      // Check if we can inject into this tab
      const tab = await chrome.tabs.get(tabId);
      if (!tab.url || tab.url.startsWith('chrome://') || tab.url.startsWith('chrome-extension://')) {
        throw new Error('Cannot inject into system pages');
      }
      
      // Inject the universal debugger
      await chrome.scripting.executeScript({
        target: { tabId },
        files: ['src/universal-debugger.js']
      });
      
      // Inject the main Gemini script
      await chrome.scripting.executeScript({
        target: { tabId },
        files: ['src/gemini-universal.js']
      });
      
      console.log('Gemini scripts injected successfully into tab:', tabId);
    } catch (error) {
      console.error('Error injecting Gemini script:', error);
      throw error;
    }
  }
  
  async getTabInfo(tabId) {
    try {
      const tab = await chrome.tabs.get(tabId);
      return {
        id: tab.id,
        url: tab.url,
        title: tab.title,
        domain: new URL(tab.url).hostname,
        protocol: new URL(tab.url).protocol,
        active: tab.active,
        pinned: tab.pinned,
        audible: tab.audible,
        muted: tab.mutedInfo?.muted || false
      };
    } catch (error) {
      console.error('Error getting tab info:', error);
      return null;
    }
  }
  
  async testApiConnection(config) {
    try {
      if (!config.api_key) {
        throw new Error('API key is required');
      }
      
      // Test connection to Gemini API
      const response = await fetch('https://generativelanguage.googleapis.com/v1beta/models', {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${config.api_key}`,
          'Content-Type': 'application/json'
        }
      });
      
      if (!response.ok) {
        throw new Error(`API connection failed: ${response.status} ${response.statusText}`);
      }
      
      const data = await response.json();
      return {
        success: true,
        message: 'API connection successful',
        models_available: data.models?.length || 0,
        test_timestamp: new Date().toISOString()
      };
    } catch (error) {
      return {
        success: false,
        message: error.message,
        test_timestamp: new Date().toISOString()
      };
    }
  }
  
  async onTabReady(tabId, tab) {
    // Log tab ready event
    console.log('Tab ready:', tab.url);
    
    // Check if this is a supported domain
    if (this.isSupportedDomain(tab.url)) {
      // Auto-inject Gemini scripts if enabled in settings
      const settings = await this.getSettings();
      if (settings.auto_inject) {
        try {
          await this.injectGeminiScript(tabId);
        } catch (error) {
          console.error('Auto-injection failed:', error);
        }
      }
    }
  }
  
  async onTabActivated(tabId) {
    // Update extension badge with debug info if needed
    const logs = await this.getDebugLogs();
    const totalLogs = logs.background_logs.length + logs.content_logs.length;
    
    if (totalLogs > 0) {
      chrome.action.setBadgeText({
        text: totalLogs.toString(),
        tabId: tabId
      });
      chrome.action.setBadgeBackgroundColor({
        color: totalLogs > 100 ? '#ff4444' : '#4444ff',
        tabId: tabId
      });
    } else {
      chrome.action.setBadgeText({ text: '', tabId: tabId });
    }
  }
  
  onStorageChanged(changes, namespace) {
    if (namespace === 'local') {
      // Notify UI components of storage changes
      this.notifyUIComponents('STORAGE_CHANGED', changes);
    }
  }
  
  isSupportedDomain(url) {
    if (!url) return false;
    
    // Skip chrome:// and extension pages
    if (url.startsWith('chrome://') || url.startsWith('chrome-extension://')) {
      return false;
    }
    
    // Support all HTTP/HTTPS domains
    return url.startsWith('http://') || url.startsWith('https://');
  }
  
  async getSettings() {
    try {
      const result = await chrome.storage.local.get('gemini_settings');
      return result.gemini_settings || {
        auto_inject: false,
        api_key: '',
        debug_level: 'info',
        enabled_features: {
          seo: true,
          content: true,
          debugging: true
        }
      };
    } catch (error) {
      console.error('Error getting settings:', error);
      return {};
    }
  }
  
  notifyUIComponents(type, data) {
    // Send message to popup if it's open
    chrome.runtime.sendMessage({
      type: 'BACKGROUND_NOTIFICATION',
      notification_type: type,
      data: data
    }).catch(() => {
      // Popup might not be open, that's ok
    });
  }
}

// Initialize the background service
const geminiBackground = new GeminiExtensionBackground();

// Handle extension installation
chrome.runtime.onInstalled.addListener((details) => {
  console.log('Gemini Command Center Extension installed:', details.reason);
  
  // Set default settings on first install
  if (details.reason === 'install') {
    chrome.storage.local.set({
      'gemini_settings': {
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
      }
    });
  }
});

// Handle extension startup
chrome.runtime.onStartup.addListener(() => {
  console.log('Gemini Command Center Extension started');
});