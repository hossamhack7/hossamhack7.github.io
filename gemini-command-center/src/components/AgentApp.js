import React, { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

const AgentApp = () => {
  const [messages, setMessages] = useState([]);
  const [input, setInput] = useState('');
  const [sending, setSending] = useState(false);

  useEffect(() => {
    // Load conversation history if available
    const savedHistory = sessionStorage.getItem('gemini_agent_history');
    if (savedHistory) {
      try {
        setMessages(JSON.parse(savedHistory));
      } catch (error) {
        console.error('Error loading conversation history:', error);
      }
    }
  }, []);

  useEffect(() => {
    // Save conversation history
    if (messages.length > 0) {
      sessionStorage.setItem('gemini_agent_history', JSON.stringify(messages));
    }
  }, [messages]);

  const sendMessage = async () => {
    if (!input.trim() || sending) return;

    const userMessage = { 
      role: 'user', 
      content: input, 
      timestamp: Date.now() 
    };
    
    setMessages(prev => [...prev, userMessage]);
    setSending(true);
    setInput('');

    try {
      const response = await apiFetch({
        path: 'agent/converse',
        method: 'POST',
        data: {
          message: input,
          history: messages.slice(-10) // Send last 10 messages for context
        },
      });

      const agentMessage = { 
        role: 'agent', 
        content: response.message, 
        timestamp: Date.now() 
      };
      
      setMessages(prev => [...prev, agentMessage]);
    } catch (error) {
      console.error('Agent Error:', error);
      const errorMessage = { 
        role: 'agent', 
        content: __('Sorry, I encountered an error. Please try again.', 'gemini-command-center'),
        timestamp: Date.now() 
      };
      setMessages(prev => [...prev, errorMessage]);
    } finally {
      setSending(false);
    }
  };

  const clearConversation = () => {
    if (confirm(__('Are you sure you want to clear the conversation history?', 'gemini-command-center'))) {
      setMessages([]);
      sessionStorage.removeItem('gemini_agent_history');
    }
  };

  const handleKeyPress = (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  };

  return (
    <div className="gemini-cc-container">
      <div className="gemini-agent-header">
        <h2>{__('Gemini AI Agent', 'gemini-command-center')}</h2>
        <p>{__('Your intelligent WordPress management assistant', 'gemini-command-center')}</p>
        <button 
          className="button button-secondary clear-chat"
          onClick={clearConversation}
          disabled={messages.length === 0}
        >
          {__('Clear Chat', 'gemini-command-center')}
        </button>
      </div>

      <div className="gemini-agent-chat">
        <div className="chat-messages">
          {messages.length === 0 && (
            <div className="chat-welcome">
              <h3>{__('Welcome to Gemini AI Agent', 'gemini-command-center')}</h3>
              <p>{__('I can help you manage your WordPress site. Ask me anything!', 'gemini-command-center')}</p>
              <div className="suggested-questions">
                <h4>{__('Try asking me:', 'gemini-command-center')}</h4>
                <ul>
                  <li>"{__('How is my site performing?', 'gemini-command-center')}"</li>
                  <li>"{__('What SEO improvements should I make?', 'gemini-command-center')}"</li>
                  <li>"{__('Create a backup of my site', 'gemini-command-center')}"</li>
                  <li>"{__('Generate content ideas for my blog', 'gemini-command-center')}"</li>
                </ul>
              </div>
            </div>
          )}
          
          {messages.map(msg => (
            <div
              key={msg.timestamp}
              className={`chat-message ${msg.role}`}
            >
              <div className="message-header">
                <strong>
                  {msg.role === 'user' 
                    ? __('You', 'gemini-command-center')
                    : __('Gemini', 'gemini-command-center')
                  }
                </strong>
                <span className="message-time">
                  {new Date(msg.timestamp).toLocaleTimeString()}
                </span>
              </div>
              <div className="message-content">
                {msg.content}
              </div>
            </div>
          ))}
          
          {sending && (
            <div className="chat-message agent">
              <div className="message-header">
                <strong>{__('Gemini', 'gemini-command-center')}</strong>
              </div>
              <div className="message-content">
                <div className="typing-indicator">
                  <span></span>
                  <span></span>
                  <span></span>
                  {__('Thinking...', 'gemini-command-center')}
                </div>
              </div>
            </div>
          )}
        </div>

        <div className="chat-input">
          <div className="input-wrapper">
            <textarea
              value={input}
              onChange={(e) => setInput(e.target.value)}
              onKeyPress={handleKeyPress}
              placeholder={__('Ask me anything about your site...', 'gemini-command-center')}
              disabled={sending}
              rows="2"
            />
            <button
              onClick={sendMessage}
              disabled={!input.trim() || sending}
              className="button button-primary send-button"
            >
              {sending ? __('Sending...', 'gemini-command-center') : __('Send', 'gemini-command-center')}
            </button>
          </div>
          <div className="chat-help">
            <small>
              {__('Press Enter to send, Shift+Enter for new line', 'gemini-command-center')}
            </small>
          </div>
        </div>
      </div>
    </div>
  );
};

export default AgentApp;