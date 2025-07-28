// Universal Content Script for Gemini Command Center
// This script runs on every website and provides universal functionality

(function() {
  'use strict';
  
  // Check if already loaded to prevent double injection
  if (window.GeminiCommandCenterLoaded) {
    return;
  }
  window.GeminiCommandCenterLoaded = true;
  
  class GeminiUniversalContentScript {
    constructor() {
      this.isActive = false;
      this.widget = null;
      this.apiKey = null;
      this.debugger = null;
      this.features = {
        seo: true,
        content: true,
        debugging: true
      };
      
      this.init();
    }
    
    async init() {
      try {
        // Wait for debugger to be available
        await this.waitForDebugger();
        
        this.debugger = window.UniversalGeminiDebugger;
        this.debugger.info('Gemini Universal Content Script initializing', {
          url: window.location.href,
          domain: window.location.hostname,
          title: document.title
        });
        
        // Load settings from extension storage
        await this.loadSettings();
        
        // Setup communication with background script
        this.setupBackgroundCommunication();
        
        // Setup DOM observers
        this.setupDOMObservers();
        
        // Setup keyboard shortcuts
        this.setupKeyboardShortcuts();
        
        // Analyze current page
        this.analyzePage();
        
        this.debugger.success('Gemini Universal Content Script initialized successfully');
        
      } catch (error) {
        console.error('Failed to initialize Gemini Content Script:', error);
        this.debugger?.error('Initialization failed', { error: error.message }, error);
      }
    }
    
    async waitForDebugger(maxAttempts = 50) {
      for (let i = 0; i < maxAttempts; i++) {
        if (window.UniversalGeminiDebugger) {
          return;
        }
        await new Promise(resolve => setTimeout(resolve, 100));
      }
      throw new Error('Universal Debugger not available after waiting');
    }
    
    async loadSettings() {
      try {
        const response = await chrome.runtime.sendMessage({
          type: 'GET_SETTINGS'
        });
        
        if (response.success) {
          this.apiKey = response.settings.api_key;
          this.features = response.settings.enabled_features || this.features;
          this.isActive = !!this.apiKey;
          
          this.debugger.info('Settings loaded', {
            has_api_key: !!this.apiKey,
            features: this.features,
            is_active: this.isActive
          });
        }
      } catch (error) {
        this.debugger.warn('Failed to load settings from extension', { error: error.message });
        // Fall back to localStorage if extension communication fails
        this.loadLocalSettings();
      }
    }
    
    loadLocalSettings() {
      try {
        const stored = localStorage.getItem('gemini_cc_settings');
        if (stored) {
          const settings = JSON.parse(stored);
          this.apiKey = settings.api_key;
          this.features = settings.enabled_features || this.features;
          this.isActive = !!this.apiKey;
          
          this.debugger.info('Local settings loaded', {
            has_api_key: !!this.apiKey,
            features: this.features
          });
        }
      } catch (error) {
        this.debugger.warn('Failed to load local settings', { error: error.message });
      }
    }
    
    setupBackgroundCommunication() {
      // Listen for messages from background script
      chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
        this.handleBackgroundMessage(message, sender, sendResponse);
        return true;
      });
      
      this.debugger.debug('Background communication setup completed');
    }
    
    handleBackgroundMessage(message, sender, sendResponse) {
      try {
        switch (message.type) {
          case 'TOGGLE_WIDGET':
            this.toggleWidget();
            sendResponse({ success: true });
            break;
            
          case 'ANALYZE_PAGE':
            const analysis = this.analyzePage();
            sendResponse({ success: true, analysis });
            break;
            
          case 'UPDATE_SETTINGS':
            this.updateSettings(message.settings);
            sendResponse({ success: true });
            break;
            
          case 'INJECT_CSS':
            this.injectCSS(message.css);
            sendResponse({ success: true });
            break;
            
          case 'GET_PAGE_INFO':
            const pageInfo = this.getPageInfo();
            sendResponse({ success: true, pageInfo });
            break;
            
          default:
            this.debugger.warn('Unknown background message type', { type: message.type });
            sendResponse({ success: false, error: 'Unknown message type' });
        }
      } catch (error) {
        this.debugger.error('Error handling background message', { 
          type: message.type, 
          error: error.message 
        }, error);
        sendResponse({ success: false, error: error.message });
      }
    }
    
    setupDOMObservers() {
      // Observe DOM changes for dynamic content
      const observer = new MutationObserver((mutations) => {
        this.onDOMChange(mutations);
      });
      
      observer.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['class', 'id', 'data-*']
      });
      
      // Observe URL changes for SPAs
      let lastUrl = location.href;
      new MutationObserver(() => {
        const url = location.href;
        if (url !== lastUrl) {
          lastUrl = url;
          this.onURLChange(url);
        }
      }).observe(document, { subtree: true, childList: true });
      
      this.debugger.debug('DOM observers setup completed');
    }
    
    setupKeyboardShortcuts() {
      document.addEventListener('keydown', (event) => {
        // Ctrl+Shift+G to toggle Gemini widget
        if (event.ctrlKey && event.shiftKey && event.key === 'G') {
          event.preventDefault();
          this.toggleWidget();
        }
        
        // Ctrl+Shift+D to open debug panel
        if (event.ctrlKey && event.shiftKey && event.key === 'D') {
          event.preventDefault();
          this.openDebugPanel();
        }
        
        // Ctrl+Shift+A to analyze page
        if (event.ctrlKey && event.shiftKey && event.key === 'A') {
          event.preventDefault();
          this.analyzePage(true);
        }
      });
      
      this.debugger.debug('Keyboard shortcuts setup completed');
    }
    
    onDOMChange(mutations) {
      // Handle significant DOM changes
      const significantChanges = mutations.filter(mutation => 
        mutation.type === 'childList' && mutation.addedNodes.length > 0
      );
      
      if (significantChanges.length > 0) {
        this.debugger.debug('Significant DOM changes detected', {
          changes_count: significantChanges.length,
          added_nodes: significantChanges.reduce((acc, mut) => acc + mut.addedNodes.length, 0)
        });
        
        // Re-analyze page if major changes
        if (significantChanges.length > 5) {
          setTimeout(() => this.analyzePage(), 1000);
        }
      }
    }
    
    onURLChange(newUrl) {
      this.debugger.info('URL changed detected', {
        old_url: this.currentUrl,
        new_url: newUrl
      });
      
      this.currentUrl = newUrl;
      
      // Re-analyze page for new URL
      setTimeout(() => this.analyzePage(), 500);
    }
    
    analyzePage(showResults = false) {
      try {
        const analysis = {
          timestamp: new Date().toISOString(),
          url: window.location.href,
          domain: window.location.hostname,
          title: document.title,
          meta: this.analyzeMetaTags(),
          headings: this.analyzeHeadings(),
          links: this.analyzeLinks(),
          images: this.analyzeImages(),
          forms: this.analyzeForms(),
          performance: this.analyzePerformance(),
          seo: this.analyzeSEO(),
          accessibility: this.analyzeAccessibility(),
          technologies: this.detectTechnologies()
        };
        
        this.debugger.info('Page analysis completed', {
          headings_count: analysis.headings.length,
          links_count: analysis.links.internal + analysis.links.external,
          images_count: analysis.images.total,
          forms_count: analysis.forms.length,
          seo_score: analysis.seo.score
        });
        
        if (showResults) {
          this.showAnalysisResults(analysis);
        }
        
        // Send analysis to background script
        chrome.runtime.sendMessage({
          type: 'PAGE_ANALYSIS',
          payload: analysis
        }).catch(() => {
          // Background script might not be available
        });
        
        return analysis;
        
      } catch (error) {
        this.debugger.error('Page analysis failed', { error: error.message }, error);
        return null;
      }
    }
    
    analyzeMetaTags() {
      const meta = {};
      document.querySelectorAll('meta').forEach(tag => {
        const name = tag.getAttribute('name') || tag.getAttribute('property');
        const content = tag.getAttribute('content');
        if (name && content) {
          meta[name] = content;
        }
      });
      return meta;
    }
    
    analyzeHeadings() {
      const headings = [];
      document.querySelectorAll('h1, h2, h3, h4, h5, h6').forEach(heading => {
        headings.push({
          level: parseInt(heading.tagName.charAt(1)),
          text: heading.textContent.trim(),
          id: heading.id,
          classes: heading.className
        });
      });
      return headings;
    }
    
    analyzeLinks() {
      const internal = [];
      const external = [];
      const currentDomain = window.location.hostname;
      
      document.querySelectorAll('a[href]').forEach(link => {
        const href = link.getAttribute('href');
        const text = link.textContent.trim();
        const title = link.getAttribute('title');
        
        try {
          const url = new URL(href, window.location.href);
          const linkData = {
            href,
            text,
            title,
            domain: url.hostname
          };
          
          if (url.hostname === currentDomain) {
            internal.push(linkData);
          } else {
            external.push(linkData);
          }
        } catch (e) {
          // Invalid URL, skip
        }
      });
      
      return { internal: internal.length, external: external.length, details: { internal, external } };
    }
    
    analyzeImages() {
      const images = [];
      document.querySelectorAll('img').forEach(img => {
        images.push({
          src: img.src,
          alt: img.alt,
          width: img.width,
          height: img.height,
          loading: img.loading,
          has_alt: !!img.alt
        });
      });
      
      const withoutAlt = images.filter(img => !img.has_alt).length;
      
      return {
        total: images.length,
        without_alt: withoutAlt,
        details: images
      };
    }
    
    analyzeForms() {
      const forms = [];
      document.querySelectorAll('form').forEach(form => {
        const inputs = form.querySelectorAll('input, textarea, select').length;
        forms.push({
          action: form.action,
          method: form.method,
          inputs_count: inputs,
          has_labels: form.querySelectorAll('label').length > 0
        });
      });
      return forms;
    }
    
    analyzePerformance() {
      if (typeof performance !== 'undefined' && performance.timing) {
        const timing = performance.timing;
        return {
          load_time: timing.loadEventEnd - timing.navigationStart,
          dom_ready: timing.domContentLoadedEventEnd - timing.navigationStart,
          first_paint: performance.getEntriesByType('paint')[0]?.startTime || 0
        };
      }
      return null;
    }
    
    analyzeSEO() {
      let score = 100;
      const issues = [];
      
      // Title analysis
      const title = document.title;
      if (!title) {
        score -= 20;
        issues.push('Missing title tag');
      } else if (title.length < 30) {
        score -= 10;
        issues.push('Title too short (< 30 characters)');
      } else if (title.length > 60) {
        score -= 10;
        issues.push('Title too long (> 60 characters)');
      }
      
      // Meta description
      const metaDesc = document.querySelector('meta[name=\"description\"]');
      if (!metaDesc) {
        score -= 20;
        issues.push('Missing meta description');
      } else {
        const content = metaDesc.getAttribute('content');
        if (content.length < 120) {
          score -= 10;
          issues.push('Meta description too short');
        } else if (content.length > 160) {
          score -= 10;
          issues.push('Meta description too long');
        }
      }
      
      // H1 analysis
      const h1s = document.querySelectorAll('h1');
      if (h1s.length === 0) {
        score -= 15;
        issues.push('Missing H1 tag');
      } else if (h1s.length > 1) {
        score -= 10;
        issues.push('Multiple H1 tags');
      }
      
      // Images without alt
      const imagesWithoutAlt = document.querySelectorAll('img:not([alt])').length;
      if (imagesWithoutAlt > 0) {
        score -= Math.min(imagesWithoutAlt * 5, 20);
        issues.push(`${imagesWithoutAlt} images without alt text`);
      }
      
      return {
        score: Math.max(score, 0),
        issues,
        recommendations: this.getSEORecommendations(issues)
      };
    }
    
    getSEORecommendations(issues) {
      const recommendations = [];
      
      issues.forEach(issue => {
        if (issue.includes('title')) {
          recommendations.push('Optimize title tag length (30-60 characters) and include target keywords');
        }
        if (issue.includes('meta description')) {
          recommendations.push('Add compelling meta description (120-160 characters) with call-to-action');
        }
        if (issue.includes('H1')) {
          recommendations.push('Use exactly one H1 tag per page with primary keyword');
        }
        if (issue.includes('alt text')) {
          recommendations.push('Add descriptive alt text to all images for accessibility and SEO');
        }
      });
      
      return [...new Set(recommendations)]; // Remove duplicates
    }
    
    analyzeAccessibility() {
      const issues = [];
      let score = 100;
      
      // Check for missing alt attributes
      const imagesWithoutAlt = document.querySelectorAll('img:not([alt])').length;
      if (imagesWithoutAlt > 0) {
        score -= imagesWithoutAlt * 5;
        issues.push(`${imagesWithoutAlt} images missing alt attributes`);
      }
      
      // Check for form labels
      const inputsWithoutLabels = document.querySelectorAll('input:not([aria-label]):not([aria-labelledby])').length;
      const totalLabels = document.querySelectorAll('label').length;
      const totalInputs = document.querySelectorAll('input').length;
      
      if (totalInputs > totalLabels) {
        score -= (totalInputs - totalLabels) * 10;
        issues.push(`${totalInputs - totalLabels} form inputs without labels`);
      }
      
      // Check for heading hierarchy
      const headings = Array.from(document.querySelectorAll('h1, h2, h3, h4, h5, h6'))
        .map(h => parseInt(h.tagName.charAt(1)));
      
      let lastLevel = 0;
      headings.forEach(level => {
        if (level > lastLevel + 1) {
          score -= 5;
          issues.push('Improper heading hierarchy detected');
        }
        lastLevel = level;
      });
      
      return {
        score: Math.max(score, 0),
        issues
      };
    }
    
    detectTechnologies() {
      const technologies = {};
      
      // Check for common frameworks and libraries
      if (typeof jQuery !== 'undefined') technologies.jquery = true;
      if (typeof React !== 'undefined') technologies.react = true;
      if (typeof Vue !== 'undefined') technologies.vue = true;
      if (typeof angular !== 'undefined') technologies.angular = true;
      
      // Check for CMS indicators
      if (document.querySelector('meta[name*="generator"]')) {
        const generator = document.querySelector('meta[name*="generator"]').content;
        if (generator.toLowerCase().includes('wordpress')) technologies.wordpress = true;
        if (generator.toLowerCase().includes('drupal')) technologies.drupal = true;
      }
      
      // Check for analytics
      if (typeof gtag !== 'undefined' || typeof ga !== 'undefined') {
        technologies.google_analytics = true;
      }
      
      return technologies;
    }
    
    toggleWidget() {
      if (this.widget) {
        this.closeWidget();
      } else {
        this.openWidget();
      }
    }
    
    openWidget() {
      if (this.widget) return;
      
      this.widget = this.createWidget();
      document.body.appendChild(this.widget);
      
      this.debugger.info('Gemini widget opened');
    }
    
    closeWidget() {
      if (this.widget) {
        this.widget.remove();
        this.widget = null;
        this.debugger.info('Gemini widget closed');
      }
    }
    
    createWidget() {
      const widget = document.createElement('div');
      widget.id = 'gemini-cc-widget';
      widget.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        width: 350px;
        height: 500px;
        background: white;
        border: 2px solid #0073aa;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        z-index: 999999;
        font-family: -apple-system, BlinkMacSystemFont, sans-serif;
        overflow: hidden;
        resize: both;
      `;
      
      widget.innerHTML = this.getWidgetHTML();
      this.setupWidgetEvents(widget);
      
      return widget;
    }
    
    getWidgetHTML() {
      const analysis = this.analyzePage();
      
      return `
        <div style="background: #0073aa; color: white; padding: 10px; display: flex; justify-content: space-between; align-items: center;">
          <h3 style="margin: 0; font-size: 16px;">Gemini Command Center</h3>
          <button id="close-widget" style="background: none; border: none; color: white; cursor: pointer; font-size: 18px;">&times;</button>
        </div>
        <div style="padding: 15px; height: calc(100% - 50px); overflow-y: auto;">
          <div class="analysis-summary">
            <h4>Page Analysis</h4>
            <div style="background: #f0f0f0; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
              <div><strong>SEO Score:</strong> ${analysis?.seo?.score || 'N/A'}/100</div>
              <div><strong>Images:</strong> ${analysis?.images?.total || 0} (${analysis?.images?.without_alt || 0} without alt)</div>
              <div><strong>Links:</strong> ${(analysis?.links?.internal || 0) + (analysis?.links?.external || 0)} total</div>
            </div>
          </div>
          
          <div class="quick-actions">
            <h4>Quick Actions</h4>
            <button class="action-btn" data-action="analyze">📊 Analyze Page</button>
            <button class="action-btn" data-action="seo-check">🔍 SEO Check</button>
            <button class="action-btn" data-action="debug-info">🐛 Debug Info</button>
            <button class="action-btn" data-action="export-logs">📥 Export Logs</button>
          </div>
          
          <div class="debug-info" style="margin-top: 15px;">
            <h4>Debug Information</h4>
            <div style="background: #f9f9f9; padding: 8px; border-radius: 4px; font-size: 12px; font-family: monospace;">
              <div><strong>URL:</strong> ${window.location.href}</div>
              <div><strong>Title:</strong> ${document.title}</div>
              <div><strong>Technologies:</strong> ${Object.keys(analysis?.technologies || {}).join(', ') || 'None detected'}</div>
            </div>
          </div>
        </div>
      `;
    }
    
    setupWidgetEvents(widget) {
      // Close button
      widget.querySelector('#close-widget').addEventListener('click', () => {
        this.closeWidget();
      });
      
      // Action buttons
      widget.querySelectorAll('.action-btn').forEach(btn => {
        btn.style.cssText = `
          display: block;
          width: 100%;
          padding: 8px;
          margin: 5px 0;
          background: #0073aa;
          color: white;
          border: none;
          border-radius: 4px;
          cursor: pointer;
          font-size: 14px;
        `;
        
        btn.addEventListener('click', () => {
          this.handleWidgetAction(btn.dataset.action);
        });
        
        btn.addEventListener('mouseover', () => {
          btn.style.background = '#005a87';
        });
        
        btn.addEventListener('mouseout', () => {
          btn.style.background = '#0073aa';
        });
      });
    }
    
    handleWidgetAction(action) {
      switch (action) {
        case 'analyze':
          this.analyzePage(true);
          break;
        case 'seo-check':
          this.showSEOReport();
          break;
        case 'debug-info':
          this.openDebugPanel();
          break;
        case 'export-logs':
          this.exportDebugLogs();
          break;
      }
    }
    
    showAnalysisResults(analysis) {
      const modal = this.createModal('Page Analysis Results', this.getAnalysisHTML(analysis));
      document.body.appendChild(modal);
    }
    
    showSEOReport() {
      const analysis = this.analyzePage();
      const modal = this.createModal('SEO Report', this.getSEOReportHTML(analysis.seo));
      document.body.appendChild(modal);
    }
    
    openDebugPanel() {
      if (this.debugger) {
        const logs = this.debugger.exportLogs();
        const modal = this.createModal('Debug Information', this.getDebugHTML(logs));
        document.body.appendChild(modal);
      }
    }
    
    async exportDebugLogs() {
      if (this.debugger) {
        await this.debugger.downloadLogs();
      }
    }
    
    createModal(title, content) {
      const modal = document.createElement('div');
      modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1000000;
        display: flex;
        align-items: center;
        justify-content: center;
      `;
      
      modal.innerHTML = `
        <div style="background: white; border-radius: 8px; max-width: 80%; max-height: 80%; overflow: auto; font-family: -apple-system, BlinkMacSystemFont, sans-serif;">
          <div style="background: #0073aa; color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0;">${title}</h3>
            <button class="close-modal" style="background: none; border: none; color: white; cursor: pointer; font-size: 24px;">&times;</button>
          </div>
          <div style="padding: 20px;">
            ${content}
          </div>
        </div>
      `;
      
      modal.querySelector('.close-modal').addEventListener('click', () => {
        modal.remove();
      });
      
      modal.addEventListener('click', (e) => {
        if (e.target === modal) {
          modal.remove();
        }
      });
      
      return modal;
    }
    
    getAnalysisHTML(analysis) {
      return `
        <h4>Page Analysis for ${analysis.domain}</h4>
        <div style="background: #f0f0f0; padding: 15px; border-radius: 4px; margin: 10px 0;">
          <h5>SEO Analysis</h5>
          <p><strong>Score:</strong> ${analysis.seo.score}/100</p>
          <p><strong>Issues:</strong> ${analysis.seo.issues.length}</p>
          <ul>
            ${analysis.seo.issues.map(issue => `<li>${issue}</li>`).join('')}
          </ul>
          
          <h5>Recommendations</h5>
          <ul>
            ${analysis.seo.recommendations.map(rec => `<li>${rec}</li>`).join('')}
          </ul>
        </div>
        
        <div style="background: #f9f9f9; padding: 15px; border-radius: 4px; margin: 10px 0;">
          <h5>Content Analysis</h5>
          <p><strong>Headings:</strong> ${analysis.headings.length}</p>
          <p><strong>Internal Links:</strong> ${analysis.links.internal}</p>
          <p><strong>External Links:</strong> ${analysis.links.external}</p>
          <p><strong>Images:</strong> ${analysis.images.total} (${analysis.images.without_alt} without alt)</p>
          <p><strong>Forms:</strong> ${analysis.forms.length}</p>
        </div>
      `;
    }
    
    getSEOReportHTML(seo) {
      return `
        <h4>SEO Report</h4>
        <div style="text-align: center; margin: 20px 0;">
          <div style="display: inline-block; width: 100px; height: 100px; border-radius: 50%; background: ${seo.score >= 80 ? '#44aa44' : seo.score >= 60 ? '#ffaa00' : '#ff4444'}; color: white; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: bold;">
            ${seo.score}
          </div>
          <p>SEO Score</p>
        </div>
        
        <h5>Issues Found (${seo.issues.length})</h5>
        <ul>
          ${seo.issues.map(issue => `<li style="color: #cc0000;">${issue}</li>`).join('')}
        </ul>
        
        <h5>Recommendations</h5>
        <ul>
          ${seo.recommendations.map(rec => `<li style="color: #0066cc;">${rec}</li>`).join('')}
        </ul>
      `;
    }
    
    getDebugHTML(logs) {
      return `
        <h4>Debug Information</h4>
        <div style="margin: 10px 0;">
          <button onclick="navigator.clipboard.writeText('${JSON.stringify(logs, null, 2).replace(/'/g, "\\'")}'); alert('Debug info copied to clipboard!');" style="background: #0073aa; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">Copy to Clipboard</button>
        </div>
        <pre style="background: #f0f0f0; padding: 15px; border-radius: 4px; overflow: auto; max-height: 400px; font-size: 12px;">${JSON.stringify(logs, null, 2)}</pre>
      `;
    }
    
    updateSettings(settings) {
      this.apiKey = settings.api_key;
      this.features = settings.enabled_features || this.features;
      this.isActive = !!this.apiKey;
      
      this.debugger.info('Settings updated', {
        has_api_key: !!this.apiKey,
        features: this.features
      });
    }
    
    injectCSS(css) {
      const style = document.createElement('style');
      style.textContent = css;
      document.head.appendChild(style);
      
      this.debugger.info('CSS injected', { css_length: css.length });
    }
    
    getPageInfo() {
      return {
        url: window.location.href,
        title: document.title,
        domain: window.location.hostname,
        meta: this.analyzeMetaTags(),
        headings_count: document.querySelectorAll('h1, h2, h3, h4, h5, h6').length,
        links_count: document.querySelectorAll('a[href]').length,
        images_count: document.querySelectorAll('img').length,
        forms_count: document.querySelectorAll('form').length
      };
    }
  }
  
  // Initialize the content script
  const geminiContentScript = new GeminiUniversalContentScript();
  
  // Make it globally available for debugging
  window.GeminiContentScript = geminiContentScript;
  
})();