(function ($) {
    'use strict';

    $(document).on('click', '.bsc-cover-select', function () {
        var fieldId = $(this).data('field');
        var $btn    = $(this);
        var $field  = $btn.closest('.bsc-cover-field');

        var frame = wp.media({
            title:    'Seleccionar imagen de cover',
            button:   { text: 'Usar esta imagen' },
            multiple: false,
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            var thumbUrl   = (attachment.sizes && attachment.sizes.thumbnail)
                ? attachment.sizes.thumbnail.url
                : attachment.url;

            $('#' + fieldId).val(attachment.id);
            $field.find('.bsc-cover-preview').html(
                '<img src="' + thumbUrl + '" style="max-width:100%;height:auto;display:block;border-radius:3px;" />'
            );
            $btn.text('Cambiar imagen');

            if (!$field.find('.bsc-cover-remove').length) {
                $btn.after(
                    '<button type="button" class="button bsc-cover-remove" ' +
                    'data-field="' + fieldId + '" style="margin-left:4px;">Eliminar</button>'
                );
            }
        });

        frame.open();
    });

    $(document).on('click', '.bsc-cover-remove', function () {
        var fieldId = $(this).data('field');
        var $field  = $(this).closest('.bsc-cover-field');

        $('#' + fieldId).val('');
        $field.find('.bsc-cover-preview').empty();
        $field.find('.bsc-cover-select').text('Seleccionar imagen');
        $(this).remove();
    });

}(jQuery));
