<?php
class Chatbot_DB {
    private $wpdb;
    private $table_qa;
    private $table_config;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_qa = $wpdb->prefix . 'chatbot_qa';
        $this->table_config = $wpdb->prefix . 'chatbot_config';
    }

    // Métodos para preguntas y respuestas
    public function add_qa($question, $answer, $keywords, $is_predefined) {
        return $this->wpdb->insert(
            $this->table_qa,
            array(
                'question' => $question,
                'answer' => $answer,
                'keywords' => $keywords,
                'is_predefined' => $is_predefined ? 1 : 0,
            ),
            array('%s', '%s', '%s', '%d')
        );
    }

    public function get_all_qa() {
        return $this->wpdb->get_results(
            "SELECT * FROM {$this->table_qa} ORDER BY created_at DESC"
        );
    }

    public function get_qa($id) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->table_qa} WHERE id = %d", $id)
        );
    }

    public function get_qa_count() {
        return $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_qa}");
    }

    public function get_qa_paginated($offset, $items_per_page) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_qa} ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $items_per_page,
                $offset
            )
        );
    }

    public function update_qa($id, $question, $answer, $keywords, $is_predefined) {
        return $this->wpdb->update(
            $this->table_qa,
            array(
                'question' => $question,
                'answer' => $answer,
                'keywords' => $keywords,
                'is_predefined' => $is_predefined ? 1 : 0,
            ),
            array('id' => $id),
            array('%s', '%s', '%s', '%d'),
            array('%d')
        );
    }

    public function delete_qa($id) {
        return $this->wpdb->delete(
            $this->table_qa,
            array('id' => $id),
            array('%d')
        );
    }

    public function get_suggested_questions() {
        return $this->wpdb->get_results(
            "SELECT * FROM {$this->table_qa} WHERE is_predefined = 1 ORDER BY created_at DESC"
        );
    }

    public function set_predefined_question($id, $is_predefined) {
        return $this->wpdb->update(
            $this->table_qa,
            array('is_predefined' => $is_predefined ? 1 : 0),
            array('id' => $id),
            array('%d'),
            array('%d')
        );
    }

    // Métodos para la configuración
    public function get_config() {
        return $this->wpdb->get_row("SELECT * FROM {$this->table_config} WHERE id = 1");
    }

    public function get_logo_url() {
        return $this->wpdb->get_var(
            "SELECT logo_url FROM {$this->table_config} WHERE id = 1"
        );
    }

    public function update_logo_url($url) {
        return $this->wpdb->update(
            $this->table_config,
            array('logo_url' => $url),
            array('id' => 1),
            array('%s'),
            array('%d')
        );
    }

    public function update_config($data) {
        return $this->wpdb->update(
            $this->table_config,
            array(
                'default_response' => $data['default_response'],
                'whatsapp_number' => $data['whatsapp_number'],
                'whatsapp_message' => $data['whatsapp_message']
            ),
            array('id' => 1),
            array('%s', '%s', '%s'),
            array('%d')
        );
    }
}