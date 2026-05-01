(function ($) {
    'use strict';

    function coverImageHtml(url) {
        return '<img src="' + url + '" class="bsc-admin-cover-image" alt="">';
    }

    function coverRemoveButton(fieldId) {
        return '<button type="button" class="button bsc-cover-remove bsc-admin-cover-remove" data-field="' + fieldId + '">Eliminar</button>';
    }

    $(document).on('click', '.bsc-cover-select', function () {
        var fieldId = $(this).data('field');
        var $button = $(this);
        var $field = $button.closest('.bsc-cover-field');

        var frame = wp.media({
            title: 'Seleccionar imagen de cover',
            button: { text: 'Usar esta imagen' },
            multiple: false,
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            var thumbUrl = attachment.sizes && attachment.sizes.thumbnail
                ? attachment.sizes.thumbnail.url
                : attachment.url;

            $('#' + fieldId).val(attachment.id);
            $field.find('.bsc-cover-preview').html(coverImageHtml(thumbUrl));
            $button.text('Cambiar imagen');

            if (!$field.find('.bsc-cover-remove').length) {
                $button.after(coverRemoveButton(fieldId));
            }
        });

        frame.open();
    });

    $(document).on('click', '.bsc-cover-remove', function () {
        var fieldId = $(this).data('field');
        var $field = $(this).closest('.bsc-cover-field');

        $('#' + fieldId).val('');
        $field.find('.bsc-cover-preview').empty();
        $field.find('.bsc-cover-select').text('Seleccionar imagen');
        $(this).remove();
    });
}(jQuery));
