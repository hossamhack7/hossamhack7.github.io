import { render } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import App from './components/App';
import AgentChat from './components/AgentChat';
import SettingsHub from './components/SettingsHub';
import SystemDashboard from './components/SystemDashboard';
import './styles/main.scss';

// Get the current page from URL or global variable
const getCurrentPage = () => {
    const urlParams = new URLSearchParams(window.location.search);
    const page = urlParams.get('page');
    return page || 'gemini-command-center';
};

// Render appropriate component based on page
const renderComponent = () => {
    const page = getCurrentPage();
    
    switch (page) {
        case 'gemini-agent':
            const agentRoot = document.getElementById('gcc-agent-root');
            if (agentRoot) {
                render(<AgentChat />, agentRoot);
            }
            break;
            
        case 'gemini-cc-settings':
            const settingsRoot = document.getElementById('gcc-settings-root');
            if (settingsRoot) {
                render(<SettingsHub />, settingsRoot);
            }
            break;
            
        case 'gemini-cc-system':
            const systemRoot = document.getElementById('gcc-system-root');
            if (systemRoot) {
                render(<SystemDashboard />, systemRoot);
            }
            break;
            
        default:
            const mainRoot = document.getElementById('gcc-react-root');
            if (mainRoot) {
                render(<App />, mainRoot);
            }
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', renderComponent);