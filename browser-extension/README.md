# Gemini Command Center - Universal Browser Extension

This directory contains the browser extension version of Gemini Command Center that works on any website.

## Key Features

### ✅ UNIVERSAL COMPATIBILITY
- Works on any HTTP/HTTPS website (not restricted to WordPress)
- Automatically detects website technologies and adapts functionality
- No backend dependencies - pure client-side operation

### 🚀 ENHANCED ERROR DETECTION & DEBUGGING
- **Comprehensive Logging System**: Tracks all API calls, errors, and user interactions
- **Real-time Monitoring**: Monitors console errors, network requests, and performance
- **Export Functionality**: Download debug logs for analysis
- **Session Tracking**: Maintains debugging context across page loads
- **Visual Error Reporting**: User-friendly error display with actionable solutions

### 🔧 ROBUST ARCHITECTURE
- **Content Script**: Runs on every page and provides universal functionality
- **Background Service Worker**: Handles cross-tab communication and data persistence
- **Popup Interface**: Quick access to tools and status information
- **Options Page**: Comprehensive settings and debugging interface

### 🛠️ CORE CAPABILITIES
- **SEO Analysis**: Page scoring, meta tag analysis, heading structure
- **Content Enhancement**: AI-powered suggestions and optimizations
- **Performance Monitoring**: Page load times, resource analysis
- **Accessibility Checking**: WCAG compliance analysis
- **Technology Detection**: Identifies CMS, frameworks, and libraries

## Installation

1. Open Chrome/Edge and navigate to `chrome://extensions/`
2. Enable "Developer mode"
3. Click "Load unpacked" and select this directory
4. Configure your Gemini API key in the extension options

## Usage

### Quick Access
- **Ctrl+Shift+G**: Toggle main widget
- **Ctrl+Shift+D**: Open debug panel
- **Ctrl+Shift+A**: Analyze current page

### Widget Features
- Real-time page analysis
- SEO scoring and recommendations
- Debug information export
- Quick action buttons

### Extension Popup
- Current page status
- API connection testing
- Debug log viewing
- Settings access

## Advanced Debugging

The extension includes a sophisticated debugging system:

### Error Detection
- JavaScript errors and exceptions
- Network request failures
- API response issues
- Performance problems

### Logging Levels
- **Error**: Critical issues requiring attention
- **Warning**: Potential problems or suboptimal conditions
- **Info**: General operational information
- **Debug**: Detailed technical information

### Export Options
- JSON format debug logs
- Complete system information
- Performance metrics
- Error stack traces

## Technical Implementation

### Universal Debugger (`src/universal-debugger.js`)
- Platform-agnostic logging system
- Network monitoring
- Performance measurement
- Error categorization

### Content Script (`content-script.js`)
- Universal DOM analysis
- Real-time page monitoring
- Technology detection
- Widget interface

### Background Service Worker (`background.js`)
- Cross-tab data synchronization
- API connection management
- Log aggregation
- Settings persistence

## Security & Privacy

- No data transmitted to external servers (except Gemini API when configured)
- All analysis performed locally
- User data stored only in browser extension storage
- Optional API key encryption

## Browser Compatibility

- Chrome 88+
- Edge 88+
- Firefox support planned for v2.0

## Troubleshooting

If the extension doesn't work on a specific site:

1. Check the popup for connection status
2. View debug logs in the options page
3. Try manual script injection via popup
4. Export logs for analysis

The enhanced debugging system will help identify the exact issue and provide guidance for resolution.