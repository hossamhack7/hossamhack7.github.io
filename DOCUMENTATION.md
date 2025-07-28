# Gemini Command Center - WordPress Plugin

A comprehensive, AI-powered management suite for WordPress with advanced SEO, content creation, and analytics features powered by Google's Gemini AI.

## 🚀 Features

### AI-Powered Core
- **Conversational AI Assistant**: Chat-based interface for natural site management
- **Content Generation**: AI-powered blog posts, social media content, and optimization
- **Smart Recommendations**: Context-aware suggestions based on site analysis
- **Memory System**: Persistent learning and personalized assistance

### SEO Management
- **Technical Audits**: Automated sitemap, robots.txt, and broken link detection
- **Internal Link Architect**: Orphan page detection and intelligent linking suggestions
- **Competitive Analysis**: AI-powered competitor research and strategy recommendations
- **Topical Authority Planner**: Content cluster generation for improved rankings

### Content Creation Tools
- **AI Writer**: Generate high-quality articles with custom topics, keywords, and tone
- **On-Page SEO Optimizer**: Real-time content optimization suggestions
- **A/B Testing Engine**: Headline testing with performance tracking
- **Social Media Amplifier**: Generate platform-specific social content

### System Management
- **Smart Backup System**: Automated database and full-site backups
- **Health Monitoring**: Real-time system diagnostics and performance tracking
- **Security Center**: Vulnerability detection and security recommendations
- **Usage Analytics**: API usage tracking and rate limiting

### UI/UX Enhancement
- **Design Analysis**: AI-powered design audits and accessibility checks
- **Safe Customization**: CSS injection without theme file modification
- **Color Palette Generator**: AI-suggested color schemes
- **Font Pairing**: Intelligent typography recommendations

## 🛠️ Installation

### Requirements
- WordPress 6.0 or higher
- PHP 8.0 or higher
- Gemini API key (free from Google AI Studio)
- Modern browser with JavaScript enabled

### Quick Start

1. **Upload Plugin**: Extract files to `/wp-content/plugins/gemini-command-center/`
2. **Activate**: Enable the plugin through WordPress admin
3. **API Key**: Navigate to Gemini Command Center > Settings and enter your Gemini API key
4. **Test Connection**: Use the "Test Connection" button to verify API access
5. **Explore**: Follow the guided tour to discover features

### Getting a Gemini API Key

1. Visit [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Sign in with your Google account
3. Click "Create API Key" 
4. Copy the generated key to plugin settings

## 📁 File Structure

```
gemini-command-center/
├── gemini-command-center.php          # Main plugin file
├── readme.txt                         # WordPress plugin readme
├── package.json                       # NPM dependencies
├── webpack.config.js                  # Build configuration
├── includes/                          # PHP classes
│   ├── class-admin-menu.php           # Admin menu management
│   ├── class-api-registrar.php        # REST API endpoints
│   ├── class-assets-loader.php        # Script/style loading
│   ├── class-settings-manager.php     # Settings management
│   ├── class-backup-manager.php       # Backup functionality
│   ├── class-seo-manager.php          # SEO tools
│   ├── class-content-manager.php      # Content generation
│   ├── class-uiux-manager.php         # UI/UX features
│   ├── class-memory-manager.php       # AI memory system
│   └── class-agent-manager.php        # Conversational AI
├── admin-pages/                       # PHP admin templates
│   ├── main-dashboard.php             # Main dashboard
│   ├── agent-chat.php                 # AI chat interface
│   ├── settings.php                   # Settings page
│   └── system.php                     # System dashboard
├── assets/
│   ├── js/                            # React source code
│   │   ├── components/                # React components
│   │   ├── styles/                    # SCSS stylesheets
│   │   └── index.js                   # Main entry point
│   └── build/                         # Compiled assets
│       ├── index.js                   # Built JavaScript
│       └── style.css                  # Built CSS
└── uploads/
    └── gemini-cc-backups/             # Backup storage
```

## 🔧 Development

### Building the React App

```bash
# Install dependencies
npm install

# Development build (with watch)
npm run dev

# Production build
npm run build

# Linting
npm run lint
npm run lint:fix
```

### Architecture

**Backend (PHP)**
- WordPress plugin architecture with modular class system
- Secure REST API with nonce verification and capability checks
- Database tables for AI memory and system logs
- Proper sanitization and escaping throughout

**Frontend (React)**
- Single Page Application with React 18
- WordPress Components for UI consistency
- SCSS for styling with component-based architecture
- Real-time API communication with error handling

**Security**
- All endpoints protected with WordPress nonces
- Capability checks (`manage_options`) on sensitive operations
- Input sanitization and output escaping
- No direct file system modifications
- Safe CSS injection without theme editing

## 🔌 API Endpoints

### Core Endpoints
- `GET /gemini-cc/v1/status` - Plugin status
- `GET /gemini-cc/v1/settings` - Retrieve settings
- `POST /gemini-cc/v1/settings` - Update settings
- `POST /gemini-cc/v1/test-connection` - Test Gemini API

### System Management
- `GET /gemini-cc/v1/system/health-check` - System diagnostics
- `POST /gemini-cc/v1/backup/create` - Create backup
- `GET /gemini-cc/v1/backup/list` - List backups

### Content & SEO
- `POST /gemini-cc/v1/content/generate-article` - Generate content
- `POST /gemini-cc/v1/content/save-draft` - Save as draft
- `GET /gemini-cc/v1/seo/technical-audit` - SEO audit
- `GET /gemini-cc/v1/links/orphan-pages` - Find orphan pages

### AI Agent
- `POST /gemini-cc/v1/agent/converse` - Chat with AI

## 🎨 User Interface

### Main Dashboard
- System status overview
- AI-generated recommendations
- Quick action buttons
- Welcome guide for new users

### Settings Hub
- **General & API**: API key configuration and operation modes
- **SEO Settings**: Internal linking, A/B testing, competitive analysis
- **UI/UX Settings**: Design module controls and customization
- **Content Settings**: AI writer preferences and social media tones
- **System & Data**: Backup schedules, logging, import/export

### AI Chat Interface
- Real-time conversation with persistent history
- Quick action buttons for common tasks
- Typing indicators and message timestamps
- Context-aware responses based on site data

### System Dashboard
- **Health Check**: Real-time system diagnostics
- **Backup Center**: Create, list, and manage backups
- **System Logs**: Scrollable log viewer with filtering

## 🔒 Security & Privacy

### Data Handling
- API responses cached locally with WordPress transients
- No user tracking or analytics collection
- Gemini API calls only when explicitly requested
- Local storage of conversation history (session-based)

### Security Measures
- WordPress nonce verification on all AJAX requests
- Capability checks before any administrative actions
- Input sanitization using WordPress functions
- Output escaping to prevent XSS
- Database queries use prepared statements

### Privacy Compliance
- Content sent to Gemini API is processed according to Google's privacy policy
- No personal data collection beyond WordPress standards
- User can disable AI features while keeping other functionality
- Option to clear conversation history and cached data

## 🤝 Support & Contributing

### Getting Help
- Check the plugin documentation
- Visit WordPress.org support forums
- Submit issues on GitHub repository

### Contributing
- Fork the repository
- Create feature branches
- Follow WordPress coding standards
- Submit pull requests with detailed descriptions

### Reporting Issues
- Use GitHub Issues for bug reports
- Include WordPress and PHP versions
- Provide steps to reproduce
- Include relevant error messages

## 📄 License

**GPL v2 or later** - This plugin is licensed under the GNU General Public License v2 or later. See LICENSE file for details.

---

**Developed by Hossam Hack** - Bringing AI-powered automation to WordPress.

For more information, visit: [https://computex2buy.me](https://computex2buy.me)