# Gemini Command Center - WordPress Plugin

## Overview

A comprehensive AI-powered WordPress management suite with security-first architecture and React-powered UI.

## Features

### 🚀 Core Framework
- ✅ Main plugin file with proper WordPress structure
- ✅ Security-first API with nonce validation
- ✅ React-powered admin interface
- ✅ Modular architecture with clean separation

### 🔧 Settings Hub
- ✅ Multi-tabbed settings interface
- ✅ Secure REST API endpoints
- ✅ Comprehensive configuration options
- ✅ API key management and testing

### 🤖 AI Agent
- ✅ Conversational chat interface
- ✅ Persistent conversation history
- ✅ Context-aware responses
- ✅ Natural language management

### 🔍 SEO Command Center
- ✅ Technical audit framework
- ✅ Internal link architect
- ✅ Competitive analysis tools
- ✅ Content cluster generation

### 📝 Content Management
- ✅ AI-powered content creation
- ✅ A/B testing engine
- ✅ Social media amplification
- ✅ SEO optimization tools

### 💾 Security & Diagnostics
- ✅ Backup management system
- ✅ System health monitoring
- ✅ Security scanning
- ✅ Comprehensive logging

### 📊 Reports & Analytics
- ✅ KPI dashboard
- ✅ Impact measurement
- ✅ Performance tracking
- ✅ Analytics integration

## Installation

1. Upload the `gemini-command-center` folder to `/wp-content/plugins/`
2. Activate the plugin through WordPress admin
3. Navigate to "Gemini Command Center" in the admin menu
4. Configure your Gemini API key in Settings
5. Start managing your site with AI!

## Technical Details

### Security Features
- WordPress Nonce validation on all API requests
- User capability checks (manage_options required)
- Input sanitization and output escaping
- Secure API communication

### Performance
- Lightweight React build (29KB JS, 9KB CSS)
- Transient caching for API results
- Assets only loaded on plugin pages
- Optimized for speed

### Architecture
- RESTful API design
- Modular plugin structure
- React SPA with proper state management
- WordPress Coding Standards compliant

## File Structure

```
gemini-command-center/
├── gemini-command-center.php    # Main plugin file
├── includes/
│   ├── class-assets-loader.php   # Assets and nonce management
│   └── class-api-registrar.php   # REST API endpoints
├── src/                          # React source files
│   ├── components/
│   │   ├── App.js               # Main React app
│   │   ├── AgentApp.js          # AI chat interface
│   │   ├── TabNavigation.js     # Tab navigation
│   │   └── tabs/                # Individual tab components
│   ├── styles/
│   │   └── main.css             # Main stylesheet
│   └── index.js                 # React entry point
├── assets/                      # Built assets
│   ├── js/
│   │   └── gemini-cc-app.js     # Built React app
│   └── css/
│       └── gemini-cc-app.css    # Built styles
├── package.json                 # Node dependencies
├── webpack.config.js            # Build configuration
└── readme.txt                   # WordPress plugin readme
```

## API Endpoints

### Core Endpoints
- `GET /wp-json/gemini-cc/v1/status` - Plugin status
- `GET/POST /wp-json/gemini-cc/v1/settings` - Settings management
- `POST /wp-json/gemini-cc/v1/test-connection` - API key testing

### AI Agent
- `POST /wp-json/gemini-cc/v1/agent/converse` - Chat with AI agent

### SEO Tools
- `GET /wp-json/gemini-cc/v1/seo/technical-audit` - Technical SEO audit
- `POST /wp-json/gemini-cc/v1/seo/generate-cluster` - Content clusters
- `POST /wp-json/gemini-cc/v1/seo/analyze-competitor` - Competitor analysis

### Content Management
- `POST /wp-json/gemini-cc/v1/content/generate-article` - AI content generation
- `POST /wp-json/gemini-cc/v1/content/start-ab-test` - A/B testing
- `POST /wp-json/gemini-cc/v1/content/amplify` - Social amplification

### Backup & Security
- `POST /wp-json/gemini-cc/v1/backup/create` - Create backups
- `GET /wp-json/gemini-cc/v1/backup/list` - List backups
- `GET /wp-json/gemini-cc/v1/system/health-check` - System health

## Development

### Building Assets
```bash
npm install
npm run build     # Production build
npm run dev       # Development build with watch
```

### Security Implementation
The plugin implements the critical nonce security fix mentioned in the problem statement:

1. **PHP Side (Assets Loader):**
```php
wp_localize_script(
    'gemini-cc-react-app',
    'gemini_cc_data',
    array(
        'api_url' => esc_url_raw( rest_url( 'gemini-cc/v1/' ) ),
        'nonce'   => wp_create_nonce( 'wp_rest' )
    )
);
```

2. **JavaScript Side (React App):**
```javascript
if (window.gemini_cc_data && window.gemini_cc_data.nonce) {
  apiFetch.use((options, next) => {
    options.headers = {
      ...options.headers,
      'X-WP-Nonce': window.gemini_cc_data.nonce,
    };
    return next(options);
  });
}
```

3. **API Validation:**
```php
public function check_permissions( $request ) {
    $nonce = $request->get_header( 'X-WP-Nonce' );
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        return new WP_Error( 'rest_forbidden', 'Invalid nonce.', array( 'status' => 403 ) );
    }
    return current_user_can( 'manage_options' );
}
```

## License

GPL v2 or later

## Requirements

- WordPress 6.0+
- PHP 8.0+
- Gemini API key (for AI features)
- Modern browser with JavaScript enabled