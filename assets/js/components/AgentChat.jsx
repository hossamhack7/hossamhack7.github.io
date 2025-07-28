import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const AgentChat = () => {
    const [messages, setMessages] = useState([
        {
            id: 1,
            type: 'agent',
            content: __('Hello! I\'m your Gemini AI Assistant. I can help you manage your WordPress site, optimize SEO, create content, and much more. What would you like to work on today?', 'gemini-command-center'),
            timestamp: new Date().toISOString()
        }
    ]);
    const [inputValue, setInputValue] = useState('');
    const [isTyping, setIsTyping] = useState(false);
    const messagesEndRef = useRef(null);

    useEffect(() => {
        scrollToBottom();
    }, [messages]);

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    const sendMessage = async () => {
        if (!inputValue.trim()) return;

        const userMessage = {
            id: Date.now(),
            type: 'user',
            content: inputValue,
            timestamp: new Date().toISOString()
        };

        setMessages(prev => [...prev, userMessage]);
        setInputValue('');
        setIsTyping(true);

        try {
            // Get conversation history for context
            const history = messages.map(msg => ({
                [msg.type]: msg.content
            }));

            const response = await apiFetch({
                path: '/gemini-cc/v1/agent/converse',
                method: 'POST',
                data: {
                    message: inputValue,
                    history: history.slice(-10) // Send last 10 exchanges for context
                },
                headers: {
                    'X-WP-Nonce': window.geminiCC?.nonce || ''
                }
            });

            const agentMessage = {
                id: Date.now() + 1,
                type: 'agent',
                content: response.response,
                timestamp: response.timestamp
            };

            setMessages(prev => [...prev, agentMessage]);
        } catch (err) {
            const errorMessage = {
                id: Date.now() + 1,
                type: 'agent',
                content: __('Sorry, I encountered an error. Please try again.', 'gemini-command-center'),
                timestamp: new Date().toISOString()
            };
            setMessages(prev => [...prev, errorMessage]);
        } finally {
            setIsTyping(false);
        }
    };

    const handleKeyPress = (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    };

    const clearChat = () => {
        setMessages([
            {
                id: 1,
                type: 'agent',
                content: __('Chat cleared. How can I help you today?', 'gemini-command-center'),
                timestamp: new Date().toISOString()
            }
        ]);
    };

    const formatTimestamp = (timestamp) => {
        return new Date(timestamp).toLocaleTimeString([], { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
    };

    return (
        <div className="gcc-agent-chat">
            <div className="gcc-chat-header">
                <div className="gcc-agent-info">
                    <div className="gcc-agent-avatar">
                        <span className="dashicons dashicons-admin-users"></span>
                    </div>
                    <div className="gcc-agent-details">
                        <h3>{__('Gemini AI Assistant', 'gemini-command-center')}</h3>
                        <span className="gcc-status online">
                            {__('Online & Ready', 'gemini-command-center')}
                        </span>
                    </div>
                </div>
                <div className="gcc-chat-actions">
                    <button 
                        className="button button-secondary"
                        onClick={clearChat}
                        title={__('Clear Chat', 'gemini-command-center')}
                    >
                        <span className="dashicons dashicons-trash"></span>
                    </button>
                </div>
            </div>

            <div className="gcc-chat-messages">
                {messages.map(message => (
                    <div 
                        key={message.id} 
                        className={`gcc-message gcc-message-${message.type}`}
                    >
                        <div className="gcc-message-content">
                            <div className="gcc-message-text">
                                {message.content}
                            </div>
                            <div className="gcc-message-time">
                                {formatTimestamp(message.timestamp)}
                            </div>
                        </div>
                    </div>
                ))}
                
                {isTyping && (
                    <div className="gcc-message gcc-message-agent">
                        <div className="gcc-message-content">
                            <div className="gcc-typing-indicator">
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                        </div>
                    </div>
                )}
                
                <div ref={messagesEndRef} />
            </div>

            <div className="gcc-chat-input">
                <div className="gcc-input-container">
                    <textarea
                        value={inputValue}
                        onChange={(e) => setInputValue(e.target.value)}
                        onKeyPress={handleKeyPress}
                        placeholder={__('Ask me anything about your WordPress site...', 'gemini-command-center')}
                        rows="1"
                        disabled={isTyping}
                    />
                    <button
                        className="gcc-send-button"
                        onClick={sendMessage}
                        disabled={!inputValue.trim() || isTyping}
                    >
                        <span className="dashicons dashicons-arrow-up-alt2"></span>
                    </button>
                </div>
                
                <div className="gcc-quick-actions">
                    <button 
                        className="gcc-quick-action"
                        onClick={() => setInputValue(__('Help me optimize my SEO', 'gemini-command-center'))}
                        disabled={isTyping}
                    >
                        {__('SEO Help', 'gemini-command-center')}
                    </button>
                    <button 
                        className="gcc-quick-action"
                        onClick={() => setInputValue(__('Create a blog post about', 'gemini-command-center'))}
                        disabled={isTyping}
                    >
                        {__('Create Content', 'gemini-command-center')}
                    </button>
                    <button 
                        className="gcc-quick-action"
                        onClick={() => setInputValue(__('Run a backup of my site', 'gemini-command-center'))}
                        disabled={isTyping}
                    >
                        {__('Backup Site', 'gemini-command-center')}
                    </button>
                    <button 
                        className="gcc-quick-action"
                        onClick={() => setInputValue(__('Check my site health', 'gemini-command-center'))}
                        disabled={isTyping}
                    >
                        {__('Health Check', 'gemini-command-center')}
                    </button>
                </div>
            </div>
        </div>
    );
};

export default AgentChat;