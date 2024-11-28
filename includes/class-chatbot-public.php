<?php
class Chatbot_Public
{
    private $plugin_name;
    private $version;
    private $db;
    private $config;

    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->db = new Chatbot_DB();
        $this->config = $this->db->get_config();
    }

    public function enqueue_styles()
    {
        wp_enqueue_style(
            $this->plugin_name,
            CHATBOT_PLUGIN_URL . 'public/css/chatbot-public.css',
            array(),
            $this->version,
            'all'
        );
    }

    public function enqueue_scripts()
    {
        wp_enqueue_script(
            'fuse',
            CHATBOT_PLUGIN_URL . 'public/js/fuse.min.js',
            array(),
            '6.6.2',
            false
        );

        wp_enqueue_script(
            $this->plugin_name,
            CHATBOT_PLUGIN_URL . 'public/js/chatbot-public.js',
            array('jquery', 'fuse'),
            $this->version,
            false
        );

        wp_localize_script($this->plugin_name, 'chatbot_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('chatbot_public_nonce'),
            'logo_url' => $this->db->get_logo_url()
        ));
    }

    public function chatbot_shortcode()
    {
        ob_start();
        include CHATBOT_PLUGIN_DIR . 'public/partials/chatbot-public-display.php';
        return ob_get_clean();
    }

    public function get_response()
    {
        check_ajax_referer('chatbot_public_nonce', 'nonce');

        if (!isset($_POST['question']) || empty($_POST['question'])) {
            wp_send_json_error('La pregunta es obligatoria');
        }

        $question = sanitize_text_field($_POST['question']);
        $qa_pairs = $this->db->get_all_qa();

        $best_match = null;
        $highest_similarity = 0;

        foreach ($qa_pairs as $qa) {
            $similarity = 0;

            if (!empty($qa->keywords)) {
                $keywords = array_map('trim', explode(',', strtolower($qa->keywords)));
                foreach ($keywords as $keyword) {
                    if (!empty($keyword) && stripos(strtolower($question), $keyword) !== false) {
                        $similarity += 0.6;
                    }
                }
            }

            $text_similarity = $this->calculate_similarity($question, $qa->question);
            $similarity += ($text_similarity * 0.4);

            if ($similarity > $highest_similarity) {
                $highest_similarity = $similarity;
                $best_match = $qa;
            }
        }

        if ($best_match && $highest_similarity > 0.3) {
            wp_send_json_success($best_match->answer);
        } else {
            // Importante: No sanitizamos la respuesta predeterminada para mantener el HTML
            wp_send_json_success(wpautop($this->config->default_response));
        }
    }

    private function calculate_similarity($str1, $str2)
    {
        $str1 = strtolower(trim($str1));
        $str2 = strtolower(trim($str2));

        if ($str1 === $str2) {
            return 1;
        }

        $distance = levenshtein($str1, $str2);
        $maxLength = max(strlen($str1), strlen($str2));
        return 1 - ($distance / $maxLength);
    }
}