(function ($) {
    'use strict';

    function setPreview($card, url) {
        var image = new Image();

        image.alt = '';
        image.src = url;

        $card.find('[data-bsc-header-menu-preview]').empty().append(image);
    }

    $(document).on('click', '[data-bsc-header-menu-select]', function () {
        var $card = $(this).closest('[data-bsc-header-menu-card]');
        var frame = wp.media({
            title: 'Seleccionar imagen del header',
            button: { text: 'Usar esta imagen' },
            multiple: false,
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            var previewUrl = attachment.sizes && attachment.sizes.medium
                ? attachment.sizes.medium.url
                : attachment.url;

            $card.find('[data-bsc-header-menu-image-id]').val(attachment.id);
            $card.find('[data-bsc-header-menu-status]').text('Imagen personalizada');
            $card.find('[data-bsc-header-menu-reset]').prop('disabled', false);
            setPreview($card, previewUrl);
        });

        frame.open();
    });

    $(document).on('click', '[data-bsc-header-menu-reset]', function () {
        var $card = $(this).closest('[data-bsc-header-menu-card]');
        var defaultImageUrl = $card.data('default-image-url');

        $card.find('[data-bsc-header-menu-image-id]').val('');
        $card.find('[data-bsc-header-menu-status]').text('Imagen por defecto');
        $(this).prop('disabled', true);

        if (defaultImageUrl) {
            setPreview($card, defaultImageUrl);
        }
    });
}(jQuery));
