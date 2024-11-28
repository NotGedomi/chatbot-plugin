(function ($) {
    'use strict';

    $(document).ready(function () {
        // Elementos del DOM
        const $chatbotWidget = $('.chatbot-widget');
        const $chatbotContainer = $('#chatbot-container');
        const $chatbotMessages = $('#chatbot-messages');
        const $chatbotInput = $('#chatbot-input');
        const $suggestedQuestions = $('.chatbot-suggested-question');
        const $chatbotToggle = $('#chatbot-toggle');
        const $chatbotClose = $('.chatbot-close');
        const $typingIndicator = $('.typing-indicator');

        // Variables de estado
        let isChatVisible = false;
        let hasStartedChat = false;
        const logoUrl = $chatbotWidget.data('logo');

        // Función para mostrar/ocultar chat
        function toggleChat() {
            isChatVisible = !isChatVisible;

            if (isChatVisible) {
                $chatbotContainer.css('display', 'flex').hide().fadeIn(300, function () {
                    if (!hasStartedChat) {
                        addMessage('Chatbot', '¡Hola! 👋 ¿En qué puedo ayudarte hoy?');
                        hasStartedChat = true;
                    }
                });
            } else {
                $chatbotContainer.fadeOut(300, function () {
                    $chatbotContainer.css('display', 'none');
                });
            }
        }

        // Función para sanitizar HTML
        function sanitizeHTML(html) {
            const allowedTags = {
                'a': ['href', 'target'],
                'b': [],
                'strong': [],
                'i': [],
                'em': [],
                'p': [],
                'br': [],
                'img': ['src', 'alt', 'width', 'height'],
                'ul': [],
                'ol': [],
                'li': [],
                'h1': [],
                'h2': [],
                'h3': [],
                'h4': [],
                'h5': [],
                'h6': [],
                'blockquote': []
            };

            // Crear un elemento temporal
            const temp = document.createElement('div');
            temp.innerHTML = html;

            // Función recursiva para limpiar elementos
            function cleanElement(element) {
                // Convertir HTMLCollection a Array para evitar problemas con la modificación en vivo
                const children = Array.from(element.children);
                
                children.forEach(child => {
                    // Si el tag no está permitido, reemplazarlo con su contenido de texto
                    if (!allowedTags.hasOwnProperty(child.tagName.toLowerCase())) {
                        child.outerHTML = child.textContent;
                        return;
                    }

                    // Limpiar atributos
                    const allowed = allowedTags[child.tagName.toLowerCase()];
                    Array.from(child.attributes).forEach(attr => {
                        if (!allowed.includes(attr.name)) {
                            child.removeAttribute(attr.name);
                        }
                    });

                    // Limpiar recursivamente los hijos
                    cleanElement(child);
                });
            }

            cleanElement(temp);
            return temp.innerHTML;
        }

        // Función para agregar mensaje
        function addMessage(sender, message) {
            let messageHtml;
            if (sender === 'Chatbot') {
                // Sanitizar el HTML de la respuesta
                const sanitizedMessage = sanitizeHTML(message);
                messageHtml = `
                    <div class="message bot">
                        <img src="${logoUrl}" class="bot-avatar" alt="Bot">
                        <div class="message-content">${sanitizedMessage}</div>
                    </div>`;
            } else {
                messageHtml = `
                    <div class="message user">
                        <div class="message-content">${message}</div>
                    </div>`;
            }
            $chatbotMessages.append(messageHtml);
            scrollToBottom();
        }

        // Función para hacer scroll al último mensaje
        function scrollToBottom() {
            $chatbotMessages.scrollTop($chatbotMessages[0].scrollHeight);
        }

        // Función para mostrar indicador de escritura
        function showTypingIndicator() {
            const typingHtml = `
                <div class="message bot typing">
                    <img src="${logoUrl}" class="bot-avatar" alt="Bot">
                    <div class="typing-indicator">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>`;
            $chatbotMessages.append(typingHtml);
            scrollToBottom();
        }        

        // Función para ocultar indicador de escritura
        function hideTypingIndicator() {
            $chatbotMessages.find('.typing').remove();
        }        

        // Función para obtener respuesta
        function getResponse(question) {
            showTypingIndicator();
            $.ajax({
                url: chatbot_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'chatbot_get_response',
                    nonce: chatbot_ajax.nonce,
                    question: question
                },
                success: function (response) {
                    hideTypingIndicator();
                    if (response.success) {
                        setTimeout(() => {
                            addMessage('Chatbot', response.data);
                            
                            // Activar los enlaces después de agregar el mensaje
                            $chatbotMessages.find('.message.bot a').attr('target', '_blank');
                            
                            // Ajustar tamaño de imágenes si existen
                            $chatbotMessages.find('.message.bot img').on('load', function() {
                                const maxWidth = $('.message-content').width() * 0.8;
                                if ($(this).width() > maxWidth) {
                                    $(this).css({
                                        'width': '100%',
                                        'height': 'auto'
                                    });
                                }
                                scrollToBottom();
                            });
                        }, 500);
                    } else {
                        addMessage('Chatbot', 'Error: No se pudo obtener una respuesta.');
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    hideTypingIndicator();
                    console.error('AJAX Error:', textStatus, errorThrown);
                    addMessage('Chatbot', 'Error: No se pudo conectar con el servidor.');
                }
            });
        }

        // Event Listeners
        $chatbotToggle.on('click', toggleChat);
        $chatbotClose.on('click', toggleChat);

        $chatbotInput.on('keypress', function (e) {
            if (e.which === 13) {
                const question = $(this).val().trim();
                if (question) {
                    addMessage('Tú', question);
                    $(this).val('');
                    getResponse(question);
                    $('#chatbot-suggested-questions').slideUp(300);
                }
            }
        });

        $suggestedQuestions.on('click', function () {
            const question = $(this).text().trim(); // Obtén el texto de la pregunta
            addMessage('Tú', question);            // Agrega el mensaje al chat
            getResponse(question);                 // Envía la pregunta al chatbot
            $('#chatbot-suggested-questions').slideUp(300); // Oculta las preguntas sugeridas
        });           

        // Cerrar chat al hacer clic fuera
        $(document).on('click', function (e) {
            if (isChatVisible &&
                !$(e.target).closest('.chatbot-widget').length) {
                toggleChat();
            }
        });

        // Ajustar altura del contenedor de mensajes en dispositivos móviles
        function adjustMobileHeight() {
            if (window.innerWidth <= 768) {
                const viewportHeight = window.innerHeight;
                const headerHeight = $('.chatbot-header').outerHeight();
                const inputHeight = $('#chatbot-input-container').outerHeight();
                const suggestedHeight = $('#chatbot-suggested-questions').outerHeight() || 0;
                
                $chatbotMessages.css('height', `${viewportHeight - headerHeight - inputHeight - suggestedHeight - 20}px`);
            } else {
                $chatbotMessages.css('height', '');
            }
        }

        // Llamar a la función cuando se carga la página y cuando se redimensiona la ventana
        adjustMobileHeight();
        $(window).on('resize', adjustMobileHeight);
    });
})(jQuery);