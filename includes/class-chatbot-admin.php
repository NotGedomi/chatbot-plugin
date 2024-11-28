<?php
class Chatbot_Admin {
    private $plugin_name;
    private $version;
    private $db;
    private $items_per_page = 10;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->db = new Chatbot_DB();

        // Agregar handlers AJAX
        add_action('wp_ajax_chatbot_update_logo', array($this, 'update_logo'));
        add_action('wp_ajax_chatbot_admin_add_qa', array($this, 'add_qa'));
        add_action('wp_ajax_chatbot_admin_delete_qa', array($this, 'delete_qa'));
        add_action('wp_ajax_chatbot_admin_edit_qa', array($this, 'edit_qa'));
        add_action('wp_ajax_chatbot_admin_set_predefined', array($this, 'set_predefined_question'));
        add_action('wp_ajax_chatbot_update_config', array($this, 'update_config'));

        // Enqueue TinyMCE y otros scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            CHATBOT_PLUGIN_URL . 'admin/css/chatbot-admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    public function enqueue_scripts() {
        // Cargar TinyMCE
        wp_enqueue_script('tinymce');
        
        // Cargar el script principal de administración del plugin
        wp_enqueue_script(
            $this->plugin_name,
            CHATBOT_PLUGIN_URL . 'admin/js/chatbot-admin.js',
            array('jquery'),
            $this->version,
            false
        );

        // Localizar variables necesarias para la llamada AJAX
        wp_localize_script($this->plugin_name, 'chatbot_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('chatbot_admin_nonce')
        ));
    }

    public function add_plugin_admin_menu() {
        add_menu_page(
            'Chatbot Settings',
            'Chatbot',
            'manage_options',
            $this->plugin_name,
            array($this, 'display_plugin_setup_page'),
            'dashicons-format-chat'
        );
    }

    public function display_plugin_setup_page() {
        // Paginación
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $this->items_per_page;
        $total_items = $this->db->get_qa_count();
        $total_pages = ceil($total_items / $this->items_per_page);
        $qa_pairs = $this->db->get_qa_paginated($offset, $this->items_per_page);

        // Incluir la vista de administración
        include_once CHATBOT_PLUGIN_DIR . 'admin/partials/chatbot-admin-display.php';
    }

    public function update_logo() {
        check_ajax_referer('chatbot_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permiso denegado');
        }

        $logo_url = sanitize_url($_POST['logo_url']);
        $result = $this->db->update_logo_url($logo_url);

        if ($result !== false) {
            wp_send_json_success('Logo actualizado correctamente');
        } else {
            wp_send_json_error('Error al actualizar el logo');
        }
    }

    public function update_config() {
        check_ajax_referer('chatbot_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permiso denegado');
        }
    
        $default_response = sanitize_text_field($_POST['default_response']);
        $whatsapp_number = sanitize_text_field($_POST['whatsapp_number']);
        $whatsapp_message = sanitize_text_field($_POST['whatsapp_message']);
    
        $result = $this->db->update_config(array(
            'default_response' => $default_response,
            'whatsapp_number' => $whatsapp_number,
            'whatsapp_message' => $whatsapp_message
        ));
    
        if ($result) {
            wp_send_json_success('Configuración actualizada correctamente');
        } else {
            wp_send_json_error('Error al actualizar la configuración');
        }
    }

    // Método para agregar una nueva pregunta y respuesta
    public function add_qa() {
        check_ajax_referer('chatbot_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permiso denegado');
        }

        $question = sanitize_text_field($_POST['question']);
        $answer = wp_kses_post($_POST['answer']); // Permitir HTML seguro
        $keywords = sanitize_text_field($_POST['keywords']);
        $is_predefined = isset($_POST['is_predefined']) && $_POST['is_predefined'] === '1' ? 1 : 0;

        if (empty($question) || empty($answer)) {
            wp_send_json_error('La pregunta y la respuesta son obligatorias');
        }

        $result = $this->db->add_qa($question, $answer, $keywords, $is_predefined);

        if ($result) {
            wp_send_json_success('Pregunta y respuesta añadidas correctamente');
        } else {
            wp_send_json_error('Error al añadir la pregunta y respuesta');
        }
    }

    // Método para eliminar una pregunta y respuesta
    public function delete_qa() {
        check_ajax_referer('chatbot_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permiso denegado');
        }

        $id = intval($_POST['id']);
        $result = $this->db->delete_qa($id);

        if ($result) {
            wp_send_json_success('Pregunta y respuesta eliminadas correctamente');
        } else {
            wp_send_json_error('Error al eliminar la pregunta y respuesta');
        }
    }

    // Método para editar una pregunta y respuesta
    public function edit_qa() {
        check_ajax_referer('chatbot_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permiso denegado');
        }

        $id = intval($_POST['id']);
        $question = sanitize_text_field($_POST['question']);
        $answer = wp_kses_post($_POST['answer']); // Permitir HTML seguro
        $keywords = sanitize_text_field($_POST['keywords']);
        $is_predefined = isset($_POST['is_predefined']) && $_POST['is_predefined'] === '1' ? 1 : 0;

        if (empty($question) || empty($answer)) {
            wp_send_json_error('La pregunta y la respuesta son obligatorias');
        }

        $result = $this->db->update_qa($id, $question, $answer, $keywords, $is_predefined);

        if ($result) {
            wp_send_json_success('Pregunta y respuesta actualizadas correctamente');
        } else {
            wp_send_json_error('Error al actualizar la pregunta y respuesta');
        }
    }

    // Método para establecer una pregunta como predefinida
    public function set_predefined_question() {
        check_ajax_referer('chatbot_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permiso denegado');
        }

        $id = intval($_POST['id']);
        $is_predefined = isset($_POST['is_predefined']) && $_POST['is_predefined'] === '1' ? 1 : 0;

        $result = $this->db->set_predefined_question($id, $is_predefined);

        if ($result) {
            wp_send_json_success('Estado de pregunta predefinida actualizado correctamente');
        } else {
            wp_send_json_error('Error al actualizar el estado de pregunta predefinida');
        }
    }
}
