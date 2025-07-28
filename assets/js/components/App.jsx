import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import Dashboard from './Dashboard';
import Loader from './Loader';

const App = () => {
    const [loading, setLoading] = useState(true);
    const [status, setStatus] = useState(null);
    const [error, setError] = useState(null);

    useEffect(() => {
        checkPluginStatus();
    }, []);

    const checkPluginStatus = async () => {
        try {
            const response = await apiFetch({
                path: '/gemini-cc/v1/status',
                method: 'GET',
                headers: {
                    'X-WP-Nonce': window.geminiCC?.nonce || ''
                }
            });
            
            setStatus(response);
        } catch (err) {
            setError(err.message || __('Failed to connect to plugin API', 'gemini-command-center'));
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return <Loader message={__('Initializing Gemini Command Center...', 'gemini-command-center')} />;
    }

    if (error) {
        return (
            <div className="gcc-error">
                <h2>{__('Connection Error', 'gemini-command-center')}</h2>
                <p>{error}</p>
                <button 
                    className="button button-primary"
                    onClick={checkPluginStatus}
                >
                    {__('Retry', 'gemini-command-center')}
                </button>
            </div>
        );
    }

    return (
        <div className="gcc-app">
            <Dashboard status={status} />
        </div>
    );
};

export default App;