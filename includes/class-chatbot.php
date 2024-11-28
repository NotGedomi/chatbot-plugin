<?php
class Chatbot {
    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        if (defined('CHATBOT_VERSION')) {
            $this->version = CHATBOT_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'chatbot-plugin';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        require_once CHATBOT_PLUGIN_DIR . 'includes/class-chatbot-loader.php';
        require_once CHATBOT_PLUGIN_DIR . 'includes/class-chatbot-i18n.php';
        require_once CHATBOT_PLUGIN_DIR . 'includes/class-chatbot-admin.php';
        require_once CHATBOT_PLUGIN_DIR . 'includes/class-chatbot-public.php';

        $this->loader = new Chatbot_Loader();
    }

    private function set_locale() {
        $plugin_i18n = new Chatbot_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    private function define_admin_hooks() {
        $plugin_admin = new Chatbot_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');

        // Agregar acciones AJAX para el admin
        $this->loader->add_action('wp_ajax_chatbot_admin_add_qa', $plugin_admin, 'add_qa');
        $this->loader->add_action('wp_ajax_chatbot_admin_delete_qa', $plugin_admin, 'delete_qa');
        $this->loader->add_action('wp_ajax_chatbot_admin_edit_qa', $plugin_admin, 'edit_qa');
        $this->loader->add_action('wp_ajax_chatbot_admin_set_predefined', $plugin_admin, 'set_predefined_question');
    }

    private function define_public_hooks() {
        $plugin_public = new Chatbot_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        $this->loader->add_shortcode('chatbot', $plugin_public, 'chatbot_shortcode');

        // Agregar acciones AJAX para el frontend
        $this->loader->add_action('wp_ajax_chatbot_get_response', $plugin_public, 'get_response');
        $this->loader->add_action('wp_ajax_nopriv_chatbot_get_response', $plugin_public, 'get_response');
    }

    public function run() {
        $this->loader->run();
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_loader() {
        return $this->loader;
    }

    public function get_version() {
        return $this->version;
    }
}