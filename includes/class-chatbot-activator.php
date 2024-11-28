<?php
class Chatbot_Activator {
    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
    
        // Tabla para preguntas y respuestas
        $table_qa = $wpdb->prefix . 'chatbot_qa';
        $sql = "CREATE TABLE $table_qa (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            question text NOT NULL,
            answer longtext NOT NULL,
            keywords text,
            is_predefined tinyint(1) DEFAULT 0 NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
    
        // Tabla para configuración con los nuevos campos
        $table_config = $wpdb->prefix . 'chatbot_config';
        $sql_config = "CREATE TABLE $table_config (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            logo_url text,
            default_response text NOT NULL DEFAULT 'Lo siento, no tengo una respuesta para esa pregunta.',
            whatsapp_number varchar(20),
            whatsapp_message text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
    
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Ejecutamos ambas creaciones de tablas
        dbDelta($sql);
        dbDelta($sql_config);
        
        // Insertar configuración inicial si no existe
        $existing_config = $wpdb->get_var("SELECT COUNT(*) FROM $table_config");
        if ($existing_config == 0) {
            $wpdb->insert(
                $table_config,
                array(
                    'logo_url' => '',
                    'default_response' => 'Lo siento, no tengo una respuesta para esa pregunta.',
                    'whatsapp_number' => '',
                    'whatsapp_message' => 'Hola, tengo una consulta'
                ),
                array('%s', '%s', '%s', '%s')
            );
        }
    }
}