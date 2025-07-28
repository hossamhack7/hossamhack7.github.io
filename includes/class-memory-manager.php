<?php
/**
 * Memory Manager for AI Agent
 *
 * @package GeminiCommandCenter
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class Gemini_CC_Memory_Manager
 */
class Gemini_CC_Memory_Manager {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Memory management hooks can be added here
    }
    
    /**
     * Store a memory fact
     */
    public static function store_fact($fact_type, $fact_content, $importance_score = 50) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gemini_cc_memory';
        
        return $wpdb->insert(
            $table_name,
            array(
                'fact_type' => sanitize_text_field($fact_type),
                'fact_content' => sanitize_textarea_field($fact_content),
                'importance_score' => intval($importance_score),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('%s', '%s', '%d', '%s', '%s')
        );
    }
    
    /**
     * Retrieve memory facts
     */
    public static function get_facts($fact_type = null, $limit = 10) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gemini_cc_memory';
        
        $where_clause = '';
        $prepare_values = array();
        
        if ($fact_type) {
            $where_clause = ' WHERE fact_type = %s';
            $prepare_values[] = $fact_type;
        }
        
        $prepare_values[] = intval($limit);
        
        $query = "SELECT * FROM $table_name $where_clause ORDER BY importance_score DESC, updated_at DESC LIMIT %d";
        
        if (!empty($prepare_values)) {
            $query = $wpdb->prepare($query, $prepare_values);
        }
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Update a memory fact
     */
    public static function update_fact($fact_id, $fact_content, $importance_score = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gemini_cc_memory';
        
        $update_data = array(
            'fact_content' => sanitize_textarea_field($fact_content),
            'updated_at' => current_time('mysql')
        );
        
        $format = array('%s', '%s');
        
        if ($importance_score !== null) {
            $update_data['importance_score'] = intval($importance_score);
            $format[] = '%d';
        }
        
        return $wpdb->update(
            $table_name,
            $update_data,
            array('id' => intval($fact_id)),
            $format,
            array('%d')
        );
    }
    
    /**
     * Delete a memory fact
     */
    public static function delete_fact($fact_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gemini_cc_memory';
        
        return $wpdb->delete(
            $table_name,
            array('id' => intval($fact_id)),
            array('%d')
        );
    }
    
    /**
     * Get context block for AI agent
     */
    public static function get_context_block() {
        // Get recent high-importance facts
        $facts = self::get_facts(null, 5);
        
        $context_parts = array();
        $context_parts[] = 'SYSTEM CONTEXT:';
        $context_parts[] = sprintf('WordPress Site: %s', get_bloginfo('name'));
        $context_parts[] = sprintf('Current Date: %s', current_time('mysql'));
        
        if (!empty($facts)) {
            $context_parts[] = 'REMEMBERED FACTS:';
            foreach ($facts as $fact) {
                $context_parts[] = sprintf('- [%s] %s', $fact->fact_type, $fact->fact_content);
            }
        }
        
        return implode("\n", $context_parts);
    }
}