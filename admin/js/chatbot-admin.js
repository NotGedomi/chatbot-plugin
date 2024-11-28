(function ($) {
    'use strict';

    $(document).ready(function () {
        // Variables para formularios
        const $qaForm = $('#chatbot-qa-form');
        const $configForm = $('#chatbot-config-form');
        const $qaList = $('#chatbot-qa-list');
        const $editModal = $('#chatbot-edit-modal');
        let mediaUploader = null;

        // Inicializar el gestor del logo
        function initLogoUploader() {
            const $uploadButton = $('#upload-chatbot-logo');
            const $removeButton = $('#remove-chatbot-logo');
            const $logoPreview = $('.logo-preview');
            const $logoUrl = $('#chatbot-logo-url');

            $uploadButton.on('click', function (e) {
                e.preventDefault();

                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }

                mediaUploader = wp.media({
                    title: 'Seleccionar Logo del Chatbot',
                    button: { text: 'Usar como logo' },
                    multiple: false,
                    library: { type: 'image' }
                });

                mediaUploader.on('select', function () {
                    const attachment = mediaUploader.state().get('selection').first().toJSON();
                    updateLogo(attachment.url);
                });

                mediaUploader.open();
            });

            $removeButton.on('click', function () {
                if (confirm('¿Estás seguro de que quieres eliminar el logo?')) {
                    updateLogo('');
                }
            });

            function updateLogo(url) {
                if (url) {
                    $logoPreview.html('<img src="' + url + '" alt="Logo del Chatbot">').show();
                    $uploadButton.text('Cambiar Logo');
                    $removeButton.show();
                } else {
                    $logoPreview.hide().empty();
                    $uploadButton.text('Seleccionar Logo');
                    $removeButton.hide();
                }

                $logoUrl.val(url);

                $.ajax({
                    url: chatbot_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'chatbot_update_logo',
                        nonce: chatbot_ajax.nonce,
                        logo_url: url
                    },
                    success: function (response) {
                        showNotification(
                            response.success ? 'success' : 'error',
                            response.success ? 'Logo actualizado correctamente.' :
                                'Error al actualizar el logo: ' + response.data
                        );
                    }
                });
            }
        }

        // Manejador del formulario de configuración
        $configForm.on('submit', function (e) {
            e.preventDefault();

            // Obtener contenido del editor TinyMCE para la respuesta por defecto
            const defaultResponse = tinymce.get('default-response') ? 
                tinymce.get('default-response').getContent() : 
                $('#default-response').val();

            $.ajax({
                url: chatbot_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'chatbot_update_config',
                    nonce: chatbot_ajax.nonce,
                    default_response: defaultResponse,
                    whatsapp_number: $('#whatsapp-number').val(),
                    whatsapp_message: $('#whatsapp-message').val()
                },
                success: function (response) {
                    showNotification(
                        response.success ? 'success' : 'error',
                        response.success ? 'Configuración actualizada correctamente.' :
                            'Error al actualizar la configuración: ' + response.data
                    );
                }
            });
        });

        // Gestor de keywords
        function initKeywordManager(inputId, tagsContainerId, hiddenInputId) {
            const $input = $('#' + inputId);
            const $tagsContainer = $('#' + tagsContainerId);
            const $hiddenInput = $('#' + hiddenInputId);
            let keywords = [];

            function renderTags() {
                $tagsContainer.empty();
                keywords.forEach(function (keyword) {
                    const $tag = $('<span class="keyword-tag">' + keyword +
                        '<button class="remove-tag">×</button></span>');
                    $tagsContainer.append($tag);
                });
            }

            function updateHiddenInput() {
                $hiddenInput.val(keywords.join(','));
            }

            $input.on('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ',') {
                    e.preventDefault();
                    const keyword = $(this).val().trim();
                    if (keyword && !keywords.includes(keyword)) {
                        keywords.push(keyword);
                        renderTags();
                        updateHiddenInput();
                    }
                    $(this).val('');
                }
            });

            $tagsContainer.on('click', '.remove-tag', function () {
                const keyword = $(this).parent().text().slice(0, -1);
                keywords = keywords.filter(function (k) { return k !== keyword; });
                renderTags();
                updateHiddenInput();
            });

            return {
                setKeywords: function (newKeywords) {
                    keywords = newKeywords.split(',').map(k => k.trim()).filter(k => k);
                    renderTags();
                    updateHiddenInput();
                },
                clearKeywords: function () {
                    keywords = [];
                    renderTags();
                    updateHiddenInput();
                }
            };
        }

        // Inicializar gestores de keywords
        const addKeywordManager = initKeywordManager(
            'chatbot-keywords-input',
            'chatbot-keywords-tags',
            'chatbot-keywords'
        );
        const editKeywordManager = initKeywordManager(
            'edit-keywords-input',
            'edit-keywords-tags',
            'edit-keywords'
        );

        // Manejador para añadir Q&A
        $qaForm.on('submit', function (e) {
            e.preventDefault();

            // Obtener contenido del editor TinyMCE
            const answer = tinymce.get('chatbot-answer') ? 
                tinymce.get('chatbot-answer').getContent() : 
                $('#chatbot-answer').val();

            $.ajax({
                url: chatbot_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'chatbot_admin_add_qa',
                    nonce: $('#chatbot_admin_nonce').val(),
                    question: $('#chatbot-question').val(),
                    answer: answer,
                    keywords: $('#chatbot-keywords').val(),
                    is_predefined: $('#chatbot-predefined').is(':checked') ? 1 : 0
                },
                success: function (response) {
                    if (response.success) {
                        showNotification('success', 'Pregunta y respuesta añadidas correctamente');
                        $qaForm[0].reset();
                        if (tinymce.get('chatbot-answer')) {
                            tinymce.get('chatbot-answer').setContent('');
                        }
                        addKeywordManager.clearKeywords();
                        location.reload();
                    } else {
                        showNotification('error', 'Error: ' + response.data);
                    }
                }
            });
        });

        // Manejadores para edición y eliminación
        $qaList.on('click', '.edit-qa', function () {
            const $row = $(this).closest('tr');
            const id = $(this).data('id');
            const answer = $row.find('.answer-text').html(); // Usar html() en lugar de text()

            $('#edit-id').val(id);
            $('#edit-question').val($row.find('.question-text').text());
            
            // Actualizar contenido del editor TinyMCE
            if (tinymce.get('edit-answer')) {
                tinymce.get('edit-answer').setContent(answer);
            } else {
                $('#edit-answer').val(answer);
            }
            
            $('#edit-predefined').prop('checked', $row.find('.predefined-toggle').is(':checked'));
            editKeywordManager.setKeywords($row.find('.keyword-list').data('keywords'));

            $editModal.addClass('open');
        });

        // Ocultar el modal si se cancela
        $('#cancel-edit').on('click', function () {
            $editModal.removeClass('open');
        });

        // Cerrar el modal cuando se hace clic fuera de él
        $(window).on('click', function (e) {
            if ($(e.target).is($editModal)) {
                $editModal.removeClass('open');
            }
        });

        // Manejador de edición
        $('#chatbot-edit-form').on('submit', function(e) {
            e.preventDefault();
            
            // Obtener contenido del editor TinyMCE
            const answer = tinymce.get('edit-answer') ? 
                tinymce.get('edit-answer').getContent() : 
                $('#edit-answer').val();

            $.ajax({
                url: chatbot_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'chatbot_admin_edit_qa',
                    nonce: $('#chatbot_admin_nonce').val(),
                    id: $('#edit-id').val(),
                    question: $('#edit-question').val(),
                    answer: answer,
                    keywords: $('#edit-keywords').val(),
                    is_predefined: $('#edit-predefined').is(':checked') ? 1 : 0
                },
                success: function(response) {
                    if (response.success) {
                        showNotification('success', 'Pregunta y respuesta actualizadas correctamente');
                        $editModal.removeClass('open');
                        location.reload();
                    } else {
                        showNotification('error', 'Error al actualizar: ' + response.data);
                    }
                }
            });
        });

        $qaList.on('click', '.delete-qa', function () {
            const id = $(this).data('id');
            if (confirm('¿Estás seguro de que quieres eliminar esta pregunta y respuesta?')) {
                deleteQA(id);
            }
        });

        // Función para mostrar notificaciones
        function showNotification(type, message) {
            const notice = $(`<div class="notice notice-${type} is-dismissible"><p>${message}</p></div>`)
                .hide()
                .insertAfter('.wp-header-end')
                .slideDown();

            setTimeout(function () {
                notice.slideUp(function () { notice.remove(); });
            }, 3000);
        }

        // Función para eliminar Q&A
        function deleteQA(id) {
            $.ajax({
                url: chatbot_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'chatbot_admin_delete_qa',
                    nonce: $('#chatbot_admin_nonce').val(),
                    id: id
                },
                success: function (response) {
                    if (response.success) {
                        showNotification('success', 'Elemento eliminado correctamente');
                        location.reload();
                    } else {
                        showNotification('error', 'Error al eliminar: ' + response.data);
                    }
                }
            });
        }

        // Inicialización
        initLogoUploader();
    });
})(jQuery);