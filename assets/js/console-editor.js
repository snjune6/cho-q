(function () {
    'use strict';

    window.ChoQEditor = {
        instance: null,
        initAttempts: 0,
        maxAttempts: 30,

        init() {
            const customField = document.getElementById('customField');
            const form = document.getElementById('consoleForm');
            if (!customField || !form) return;

            const syncVisibility = () => {
                const selected = form.querySelector('input[name="status_key"]:checked');
                const isCustom = selected?.value === 'custom';
                customField.hidden = !isCustom;
                if (isCustom) {
                    ChoQEditor.ensureEditor();
                }
            };

            form.querySelectorAll('input[name="status_key"]').forEach((radio) => {
                radio.addEventListener('change', syncVisibility);
            });

            syncVisibility();
        },

        getCarCode() {
            return window.ChoQPage?.carCode || document.getElementById('consoleForm')?.dataset.car || '';
        },

        ensureEditor() {
            if (ChoQEditor.instance) return;

            if (typeof Jodit === 'undefined') {
                ChoQEditor.initAttempts += 1;
                if (ChoQEditor.initAttempts <= ChoQEditor.maxAttempts) {
                    setTimeout(ChoQEditor.ensureEditor, 100);
                }
                return;
            }

            const textarea = document.getElementById('customMessageEditor');
            if (!textarea || textarea.dataset.joditAttached === '1') return;

            const carCode = ChoQEditor.getCarCode();

            try {
                ChoQEditor.instance = Jodit.make(textarea, {
                    height: 220,
                    toolbarAdaptive: false,
                    toolbarButtonSize: 'small',
                    buttons: 'bold,italic,underline,ul,ol,image,eraser',
                    showCharsCounter: true,
                    limitChars: 200,
                    askBeforePasteHTML: false,
                    defaultActionOnPaste: 'insert_clear_html',
                    insertImageAsBase64URI: false,
                    uploader: {
                        url: '/api/upload.php?car=' + encodeURIComponent(carCode),
                        insertImageAsBase64URI: false,
                        imagesExtensions: ['jpg', 'jpeg', 'png', 'gif', 'webp'],
                        format: 'json',
                    },
                    statusbar: true,
                    spellcheck: true,
                });
                textarea.dataset.joditAttached = '1';
            } catch (err) {
                console.error('Jodit init failed:', err);
            }
        },

        getValue() {
            if (ChoQEditor.instance) {
                return ChoQEditor.instance.value;
            }
            const textarea = document.getElementById('customMessageEditor');
            return textarea ? textarea.value : '';
        },
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ChoQEditor.init);
    } else {
        ChoQEditor.init();
    }
})();
