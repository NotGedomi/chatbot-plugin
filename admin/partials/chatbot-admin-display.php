<?php
// Prevenir acceso directo al archivo
if (!defined('ABSPATH'))
    exit;

// Obtener la configuración actual
$config = $this->db->get_config();
if (!$config || !is_object($config)) {
    $config = (object) [
        'whatsapp_number' => '',
        'whatsapp_message' => '',
        'default_response' => ''
    ];
}
$logo_url = $this->db->get_logo_url();
$has_logo = !empty($logo_url);
?>

<div class="wrap chatbot-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <!-- Sección de Logo -->
    <div class="chatbot-section logo-section">
        <h2>Configuración del Logo</h2>
        <div class="logo-settings">
            <!-- Previsualización y controles del logo -->
            <div class="logo-preview-wrapper">
                <div class="logo-preview" <?php echo $has_logo ? '' : 'style="display:none;"'; ?>>
                    <?php if ($has_logo): ?>
                        <img src="<?php echo esc_url($logo_url); ?>" alt="Logo del Chatbot">
                    <?php endif; ?>
                </div>
                <div class="logo-actions">
                    <input type="hidden" id="chatbot-logo-url" name="chatbot_logo_url"
                        value="<?php echo esc_attr($logo_url); ?>">
                    <button type="button" class="button button-primary" id="upload-chatbot-logo">
                        <?php echo $has_logo ? 'Cambiar Logo' : 'Seleccionar Logo'; ?>
                    </button>
                    <?php if ($has_logo): ?>
                        <button type="button" class="button" id="remove-chatbot-logo">Eliminar Logo</button>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Información del logo -->
            <div class="logo-description">
                <p>Este logo se utilizará como:</p>
                <ul>
                    <li>Botón flotante para abrir/cerrar el chat</li>
                    <li>Avatar del chatbot en los mensajes</li>
                </ul>
                <p class="description">Tamaño recomendado: 120x120 píxeles. Preferiblemente una imagen cuadrada.</p>
            </div>
        </div>
    </div>

    <!-- Sección de Configuración General -->
    <div class="chatbot-section config-section">
        <h2>Configuración General</h2>
        <form id="chatbot-config-form" class="config-form">
            <?php wp_nonce_field('chatbot_admin_nonce', 'chatbot_admin_nonce'); ?>

            <!-- Respuesta por defecto -->
            <div class="form-group">
                <label for="default-response">Respuesta por defecto:</label>
                <?php 
                wp_editor(
                    $config->default_response,
                    'default-response',
                    array(
                        'media_buttons' => true,
                        'textarea_name' => 'default_response',
                        'textarea_rows' => 7,
                        'teeny' => false,
                        'quicktags' => true
                    )
                );
                ?>
                <p class="description">Esta respuesta se mostrará cuando no se encuentre una coincidencia.</p>
            </div>

            <!-- Configuración de WhatsApp -->
            <div class="form-group">
                <label for="whatsapp-number">Número de WhatsApp:</label>
                <input type="text" id="whatsapp-number" name="whatsapp_number" class="regular-text"
                    value="<?php echo isset($config->whatsapp_number) ? esc_attr($config->whatsapp_number) : ''; ?>"
                    placeholder="Ejemplo: 51999999999">
                <p class="description">Ingresa el número con código de país, sin espacios ni símbolos.</p>
            </div>

            <div class="form-group">
                <label for="whatsapp-message">Mensaje predeterminado de WhatsApp:</label>
                <textarea id="whatsapp-message" name="whatsapp_message"
                    class="regular-text"><?php echo esc_textarea($config->whatsapp_message); ?></textarea>
                <p class="description">Este mensaje se usará como plantilla al abrir WhatsApp.</p>
            </div>

            <div class="form-group submit-group">
                <button type="submit" class="button button-primary">Guardar Configuración</button>
            </div>
        </form>
    </div>

    <!-- Sección de Shortcode -->
    <div class="chatbot-section shortcode-section">
        <h2>Shortcode</h2>
        <div class="shortcode-info">
            <p>Usa este shortcode para mostrar el chatbot en cualquier página o post:</p>
            <code>[chatbot]</code>
        </div>
    </div>

    <!-- Sección de Preguntas y Respuestas -->
    <div class="chatbot-section qa-section">
        <h2>Preguntas y Respuestas</h2>

        <!-- Formulario para añadir nuevas Q&A -->
        <div class="qa-form-wrapper">
            <h3>Añadir Nueva Pregunta y Respuesta</h3>
            <form id="chatbot-qa-form" class="qa-form">
                <?php wp_nonce_field('chatbot_admin_nonce', 'chatbot_admin_nonce'); ?>

                <div class="form-group">
                    <label for="chatbot-question">Pregunta:</label>
                    <input type="text" id="chatbot-question" name="question" required class="regular-text">
                </div>

                <div class="form-group">
                    <label for="chatbot-answer">Respuesta:</label>
                    <?php 
                    wp_editor(
                        '',
                        'chatbot-answer',
                        array(
                            'media_buttons' => true,
                            'textarea_name' => 'answer',
                            'textarea_rows' => 10,
                            'teeny' => false,
                            'quicktags' => true
                        )
                    );
                    ?>
                </div>

                <div class="form-group">
                    <label for="chatbot-keywords-input">Palabras clave:</label>
                    <input type="text" id="chatbot-keywords-input" class="regular-text"
                        placeholder="Presiona Enter o agrega una coma después de cada palabra clave">
                    <div id="chatbot-keywords-tags" class="keyword-tags"></div>
                    <input type="hidden" id="chatbot-keywords" name="keywords">
                </div>

                <div class="form-group checkbox">
                    <label for="chatbot-predefined">
                        <input type="checkbox" id="chatbot-predefined" name="is_predefined">
                        Mostrar como pregunta predeterminada
                    </label>
                </div>

                <div class="form-group submit-group">
                    <button type="submit" class="button button-primary">Añadir</button>
                </div>
            </form>
        </div>

        <!-- Lista de Q&A existentes -->
        <div class="qa-list-wrapper">
            <h3>Preguntas y Respuestas Existentes</h3>
            <table id="chatbot-qa-list" class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Pregunta</th>
                        <th>Respuesta</th>
                        <th>Palabras clave</th>
                        <th>Predeterminada</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($qa_pairs as $qa): ?>
                        <tr>
                            <td class="question-cell">
                                <span class="question-text"><?php echo esc_html($qa->question); ?></span>
                            </td>
                            <td class="answer-cell">
                                <div class="answer-text"><?php echo wp_kses_post($qa->answer); ?></div>
                            </td>
                            <td class="keyword-cell">
                                <div class="keyword-list" data-keywords="<?php echo esc_attr($qa->keywords); ?>">
                                    <?php
                                    $keywords = explode(',', $qa->keywords);
                                    foreach ($keywords as $keyword):
                                        if (!empty(trim($keyword))):
                                            ?>
                                            <span class="keyword-tag">
                                                <?php echo esc_html(trim($keyword)); ?>
                                            </span>
                                            <?php
                                        endif;
                                    endforeach;
                                    ?>
                                </div>
                            </td>
                            <td class="predefined-cell">
                                <input type="checkbox" class="predefined-toggle" data-id="<?php echo esc_attr($qa->id); ?>"
                                    <?php checked($qa->is_predefined, 1); ?> >
                            </td>
                            <td class="actions-cell">
                                <button class="button edit-qa" data-id="<?php echo esc_attr($qa->id); ?>">
                                    Editar
                                </button>
                                <button class="button delete-qa" data-id="<?php echo esc_attr($qa->id); ?>">
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Paginación -->
            <?php if ($total_pages > 1): ?>
                <div class="tablenav">
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('&laquo;'),
                            'next_text' => __('&raquo;'),
                            'total' => $total_pages,
                            'current' => $current_page
                        ));
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de Edición de Pregunta y Respuesta -->
<div id="chatbot-edit-modal" class="chatbot-modal">
    <div class="chatbot-modal-content">
        <h3>Editar Pregunta y Respuesta</h3>
        <form id="chatbot-edit-form">
            <?php wp_nonce_field('chatbot_admin_nonce', 'chatbot_admin_nonce'); ?>
            <input type="hidden" id="edit-id" name="id">

            <div class="form-group">
                <label for="edit-question">Pregunta:</label>
                <input type="text" id="edit-question" name="question" required class="regular-text">
            </div>

            <div class="form-group">
                <label for="edit-answer">Respuesta:</label>
                <?php 
                wp_editor(
                    '',
                    'edit-answer',
                    array(
                        'media_buttons' => true,
                        'textarea_name' => 'answer',
                        'textarea_rows' => 10,
                        'teeny' => false,
                        'quicktags' => true
                    )
                );
                ?>
            </div>

            <div class="form-group">
                <label for="edit-keywords-input">Palabras clave:</label>
                <input type="text" id="edit-keywords-input" class="regular-text">
                <div id="edit-keywords-tags" class="keyword-tags"></div>
                <input type="hidden" id="edit-keywords" name="keywords">
            </div>

            <div class="form-group checkbox">
                <label for="edit-predefined">
                    <input type="checkbox" id="edit-predefined" name="is_predefined">
                    Mostrar como pregunta predeterminada
                </label>
            </div>

            <div class="form-group submit-group">
                <button type="submit" class="button button-primary">Guardar Cambios</button>
                <button type="button" class="button" id="cancel-edit">Cancelar</button>
            </div>
        </form>
    </div>
</div>