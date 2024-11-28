<?php
/**
 * Plugin Name: Chatbot Plugin
 * Plugin URI: http://example.com/chatbot-plugin
 * Description: Un plugin de chatbot personalizado para WordPress
 * Version: 1.0.0
 * Author: Gedomi
 * Author URI: https://github.com/NotGedomi/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: chatbot-plugin
 * Domain Path: /languages
 */

// Si este archivo es llamado directamente, abortar.
if (!defined('WPINC')) {
    die;
}

// Definir constantes del plugin
define('CHATBOT_VERSION', '1.0.0');
define('CHATBOT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CHATBOT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Incluir archivos necesarios
require_once CHATBOT_PLUGIN_DIR . 'includes/class-chatbot-loader.php';
require_once CHATBOT_PLUGIN_DIR . 'includes/class-chatbot-i18n.php';
require_once CHATBOT_PLUGIN_DIR . 'includes/class-chatbot-activator.php';
require_once CHATBOT_PLUGIN_DIR . 'includes/class-chatbot-deactivator.php';
require_once CHATBOT_PLUGIN_DIR . 'includes/class-chatbot-admin.php';
require_once CHATBOT_PLUGIN_DIR . 'includes/class-chatbot-public.php';
require_once CHATBOT_PLUGIN_DIR . 'includes/class-chatbot-db.php';
require_once CHATBOT_PLUGIN_DIR . 'includes/class-chatbot.php';

/**
 * Comienza la ejecución del plugin.
 */
function run_chatbot_plugin() {
    $plugin = new Chatbot();
    $plugin->run();
}

/**
 * La función que se ejecuta durante la activación del plugin.
 */
function activate_chatbot_plugin() {
    Chatbot_Activator::activate();
}

/**
 * La función que se ejecuta durante la desactivación del plugin.
 */
function deactivate_chatbot_plugin() {
    Chatbot_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_chatbot_plugin');
register_deactivation_hook(__FILE__, 'deactivate_chatbot_plugin');

run_chatbot_plugin();