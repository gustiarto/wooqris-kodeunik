jQuery(function ($) {
    'use strict';

    const wizard = {
        currentStep: 1,
        totalSteps: 4,
        params: window.qris_wizard_params || {},

        init: function () {
            this.attachEvents();
        },

        attachEvents: function () {
            $('.qris-wizard-next-step').on('click', this.handleNextStep.bind(this));
            $('#qris_scanner_upload').on('change', this.handleQRScan.bind(this));
        },

        handleNextStep: function (e) {
            const $button = $(e.currentTarget);
            const isSaving = this.currentStep === 2;

            if (isSaving) {
                if ($('#qris_string_input').val().trim() === '') {
                    alert(this.params.error_qris_empty);
                    return;
                }
                this.saveSettings($button);
            } else {
                this.goToStep(this.currentStep + 1);
            }
        },

        goToStep: function (step) {
            if (step > this.totalSteps || step < 1) {
                return;
            }

            this.currentStep = step;

            $('.qris-setup-step').removeClass('active').hide();
            $(`.qris-setup-step[data-step="${this.currentStep}"]`).addClass('active').show();

            $('.qris-setup-steps li').removeClass('active');
            $(`.qris-setup-steps li[data-step="${this.currentStep}"]`).addClass('active');

            this.updateFooter();
        },

        updateFooter: function () {
            const $nextButton = $('.qris-wizard-next-step');
            const $skipButton = $('.qris-skip-wizard');

            if (this.currentStep === 1) {
                $nextButton.text('Mulai');
            } else if (this.currentStep < this.totalSteps) {
                $nextButton.text('Lanjutkan');
            }

            if (this.currentStep === this.totalSteps) {
                $nextButton.hide();
                $skipButton.hide();
            } else {
                $nextButton.show();
                $skipButton.show();
            }
        },

        handleQRScan: function (e) {
            const file = e.target.files[0];
            if (!file) {
                return;
            }

            const reader = new FileReader();
            reader.onload = function (event) {
                const img = new Image();
                img.onload = function () {
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d', { willReadFrequently: true });
                    canvas.width = img.width;
                    canvas.height = img.height;
                    ctx.drawImage(img, 0, 0, img.width, img.height);
                    const imageData = ctx.getImageData(0, 0, img.width, img.height);
                    const code = jsQR(imageData.data, imageData.width, imageData.height);

                    if (code) {
                        $('#qris_string_input').val(code.data);
                        alert(wizard.params.scan_success);
                    } else {
                        alert(wizard.params.scan_fail);
                    }
                };
                img.src = event.target.result;
            };
            reader.readAsDataURL(file);
        },

        saveSettings: function ($button) {
            const originalText = $button.text();
            $button.text(this.params.saving).prop('disabled', true);

            $.post(this.params.ajax_url, {
                action: 'qris_save_wizard_settings',
                nonce: this.params.nonce,
                qris_string: $('#qris_string_input').val()
            })
            .done(() => {
                this.goToStep(this.currentStep + 1);
            })
            .fail(() => {
                alert('An error occurred. Please try again.');
            })
            .always(() => {
                $button.text(originalText).prop('disabled', false);
            });
        }
    };

    wizard.init();
});
