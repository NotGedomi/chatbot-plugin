<?php if (!defined('ABSPATH'))
    exit;
$logo_url = $this->db->get_logo_url();
if (empty($logo_url)) {
    $logo_url = CHATBOT_PLUGIN_URL . 'public/images/default-bot.png';
}
$whatsapp_number = $this->config->whatsapp_number;
$whatsapp_message = urlencode($this->config->whatsapp_message);
$suggested_questions = $this->db->get_suggested_questions();
?>

<div class="chatbot-widget" data-logo="<?php echo esc_url($logo_url); ?>">
    <!-- Toggle button with logo -->
    <button id="chatbot-toggle" class="chatbot-toggle">
        <div class="toggle-logo-container">
            <img src="<?php echo esc_url($logo_url); ?>" alt="Chat" class="toggle-logo">
        </div>
    </button>

    <!-- Main chat container -->
    <div id="chatbot-container">
        <!-- Chatbot Whatsapp button-->
        <div class="chatbot-whatsapp-contact">
            <?php if (!empty($whatsapp_number)): ?>
                <a href="https://wa.me/<?php echo esc_attr($whatsapp_number); ?>?text=<?php echo $whatsapp_message; ?>"
                    target="_blank" class="whatsapp-button">
                    <img src="<?php echo CHATBOT_PLUGIN_URL . 'public/assets/icons/whatsapp.svg'; ?>" alt="WhatsApp"
                        class="whatsapp-icon">Chatea por WhatsApp
                </a>
            <?php endif; ?>
        </div>
        <!-- Chat header -->
        <div class="chatbot-header">

            <div class="chatbot-header-info">
                <div class="header-logo-container">
                    <img src="<?php echo esc_url($logo_url); ?>" alt="Chat" class="header-logo">
                </div>
                <span class="chatbot-header-title">Asistente Virtual</span>
            </div>
            <button class="chatbot-close" aria-label="Cerrar chat">&times;</button>
        </div>

        <!-- Message area -->
        <div id="chatbot-messages" class="messages-container">
            <!-- Messages will be added dynamically via JavaScript -->
        </div>

        <!-- Suggested questions -->
        <?php if (!empty($suggested_questions)): ?>
            <div id="chatbot-suggested-questions">
                <?php foreach ($suggested_questions as $question): ?>
                    <button class="chatbot-suggested-question">
                        <?php echo esc_html($question->question); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Input container -->
        <div id="chatbot-input-container">
            <input type="text" id="chatbot-input" placeholder="Escribe tu mensaje aquí..." aria-label="Mensaje">
            <div class="input-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18">
                    <path fill="currentColor" d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z" />
                </svg>
            </div>
        </div>
    </div>
</div>