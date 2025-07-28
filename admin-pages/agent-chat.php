<?php
/**
 * AI Agent Chat Page
 *
 * @package GeminiCommandCenter
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Security check
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'gemini-command-center'));
}
?>

<div class="wrap">
    <div class="gemini-cc-root">
        <div class="gemini-cc-header">
            <h1><?php echo esc_html__('Gemini AI Agent', 'gemini-command-center'); ?></h1>
            <p><?php echo esc_html__('Conversational AI Assistant for Your WordPress Site', 'gemini-command-center'); ?></p>
        </div>
        <div class="gemini-cc-content">
            <div id="gcc-agent-root">
                <div class="gemini-cc-loading">
                    <div class="gemini-cc-spinner"></div>
                    <p><?php echo esc_html__('Loading AI Agent...', 'gemini-command-center'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>