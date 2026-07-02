(function ($) {
  'use strict';

  var config = window.bscProductEditData || {};
  var catTree = Array.isArray(config.catTree) ? config.catTree : [];
  var currentCats = Array.isArray(config.currentCats)
    ? config.currentCats.map(function (id) { return parseInt(id, 10); }).filter(Boolean)
    : [];
  var rootSlugs = Array.isArray(config.rootSlugs) ? config.rootSlugs : [];
  var rootLabels = config.rootLabels || {};
  var strings = config.strings || {};

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function mainPreviewHtml(url) {
    return '<img src="' + url + '" class="bsc-admin-product-edit__preview-image" alt="">';
  }

  function galleryThumbHtml(url) {
    return '<img src="' + url + '" class="bsc-admin-product-edit__gallery-thumb" alt="">';
  }

  var mainFrame;
  $(document).on('click', '#bsc-select-main-image', function () {
    if (mainFrame) {
      mainFrame.open();
      return;
    }

    mainFrame = wp.media({
      title: strings.mainImageTitle || 'Imagen principal',
      button: { text: strings.mainImageButton || 'Usar imagen' },
      multiple: false,
    });

    mainFrame.on('select', function () {
      var attachment = mainFrame.state().get('selection').first().toJSON();
      $('#bsc-thumbnail-id').val(attachment.id);
      $('#bsc-remove-thumbnail-flag').val('');
      $('#bsc-main-image-preview').html(mainPreviewHtml(attachment.url));
      $('#bsc-remove-main-image').removeClass('is-hidden');
    });

    mainFrame.open();
  });

  $(document).on('click', '#bsc-remove-main-image', function () {
    $('#bsc-thumbnail-id').val('');
    $('#bsc-remove-thumbnail-flag').val('1');
    $('#bsc-main-image-preview').empty();
    $(this).addClass('is-hidden');
  });

  var galleryFrame;
  $(document).on('click', '#bsc-select-gallery', function () {
    if (galleryFrame) {
      galleryFrame.open();
      return;
    }

    galleryFrame = wp.media({
      title: strings.galleryTitle || 'Galeria del producto',
      button: { text: strings.galleryButton || 'Usar estas imagenes' },
      multiple: true,
    });

    galleryFrame.on('select', function () {
      var selection = galleryFrame.state().get('selection');
      var ids = selection.map(function (attachment) {
        return attachment.id;
      });

      $('#bsc-gallery-ids').val(ids.join(','));

      var html = selection.map(function (attachment) {
        var thumb = attachment.attributes.sizes && attachment.attributes.sizes.thumbnail
          ? attachment.attributes.sizes.thumbnail.url
          : attachment.attributes.url;
        return galleryThumbHtml(thumb);
      }).join('');

      $('#bsc-gallery-preview').html(html);
    });

    galleryFrame.open();
  });

  function baseRegularPrice() {
    return $('[name="_regular_price"]').val() || '';
  }

  function variantNumberField(label, dataAttr, value) {
    var $label = $('<label>', {
      class: 'bsc-admin-product-edit__field bsc-admin-product-edit__variant-price',
    });
    var $input = $('<input>', {
      type: 'number',
      min: '0',
      step: '1',
      value: value || '',
      class: 'bsc-admin-product-edit__number-input',
    }).attr(dataAttr, '1');

    if (/precio|oferta/i.test(label)) {
      $input.attr('inputmode', 'numeric');
    }

    $label.append(
      $('<span>', { class: 'bsc-admin-product-edit__field-label', text: label }),
      $input
    );

    return $label;
  }

  function variantEnabledField(dataAttr, hiddenAttr, checked) {
    var $label = $('<label>', {
      class: 'bsc-admin-product-edit__field bsc-admin-product-edit__variant-inline-enabled',
    });
    var $hidden = $('<input>', {
      type: 'hidden',
      value: '0',
    }).attr(hiddenAttr, '1');
    var $checkbox = $('<input>', {
      type: 'checkbox',
      value: '1',
      checked: checked !== false,
    }).attr(dataAttr, '1');

    $label.append(
      $('<span>', { class: 'bsc-admin-product-edit__field-label', text: 'Activa' }),
      $hidden,
      $checkbox
    );

    return $label;
  }

  function disableVariantMode(mode) {
    var selector = mode === 'color' ? '[data-bsc-color-variants]' : '[data-bsc-size-variants]';
    var $root = $(selector);

    if (!$root.length) {
      return;
    }

    $root.find('[data-bsc-color-variants-toggle], [data-bsc-size-variants-toggle]').prop('checked', false);
    $root.find('[data-bsc-color-variants-panel], [data-bsc-size-variants-panel]').addClass('is-hidden');
  }

  function initColorVariants() {
    var $root = $('[data-bsc-color-variants]');

    if (!$root.length) {
      return;
    }

    var defaultColor = '#F7C0CD';
    var $toggle = $root.find('[data-bsc-color-variants-toggle]');
    var $panel = $root.find('[data-bsc-color-variants-panel]');
    var $list = $root.find('[data-bsc-color-variants-list]');

    function normalizeHex(value) {
      var color = String(value || '').trim();

      if (!/^#[0-9a-f]{6}$/i.test(color)) {
        return defaultColor;
      }

      return color.toUpperCase();
    }

    function syncPreview($row) {
      var color = normalizeHex($row.find('[data-bsc-color-variant-hex]').val());

      $row.find('[data-bsc-color-variant-preview]').css('background-color', color);
    }

    function reindexRows() {
      $list.find('[data-bsc-color-variant-row]').each(function (index) {
        var prefix = '_bsc_color_variants[' + index + ']';
        var $row = $(this);

        $row.find('[data-bsc-color-variant-hex]').attr('name', prefix + '[hex]');
        $row.find('[data-bsc-color-variant-name]').attr('name', prefix + '[name]');
        $row.find('[data-bsc-color-variant-regular-price]').attr('name', prefix + '[regular_price]');
        $row.find('[data-bsc-color-variant-sale-price]').attr('name', prefix + '[sale_price]');
        $row.find('[data-bsc-color-variant-stock-bodega]').attr('name', prefix + '[stock_bodega]');
        $row.find('[data-bsc-color-variant-stock-tienda]').attr('name', prefix + '[stock_tienda]');
        $row.find('[data-bsc-color-variant-enabled-hidden]').attr('name', prefix + '[enabled]');
        $row.find('[data-bsc-color-variant-enabled]').attr('name', prefix + '[enabled]');
        $row.toggleClass('is-disabled', !$row.find('[data-bsc-color-variant-enabled]').is(':checked'));
        syncPreview($row);
      });
    }

    function createRow() {
      var $row = $('<div>', {
        class: 'bsc-admin-product-edit__color-row',
        'data-bsc-color-variant-row': '1',
      });
      var $colorLabel = $('<label>', {
        class: 'bsc-admin-product-edit__field bsc-admin-product-edit__color-field',
      });
      var $picker = $('<span>', {
        class: 'bsc-admin-product-edit__color-picker',
      });
      var $colorInput = $('<input>', {
        type: 'color',
        value: defaultColor,
        'data-bsc-color-variant-hex': '1',
      });
      var $preview = $('<span>', {
        class: 'bsc-admin-product-edit__color-preview',
        'data-bsc-color-variant-preview': '1',
      }).css('background-color', defaultColor);
      var $nameLabel = $('<label>', {
        class: 'bsc-admin-product-edit__field bsc-admin-product-edit__color-name',
      });
      var $nameInput = $('<input>', {
        type: 'text',
        class: 'regular-text',
        placeholder: 'Ej: Rosado claro',
        'data-bsc-color-variant-name': '1',
      });
      var $removeButton = $('<button>', {
        type: 'button',
        class: 'button bsc-admin-product-edit__color-remove',
        text: 'Quitar',
        'data-bsc-color-variant-remove': '1',
      });

      $picker.append($colorInput, $preview);
      $colorLabel.append(
        $('<span>', { class: 'bsc-admin-product-edit__field-label', text: 'Color' }),
        $picker
      );
      $nameLabel.append(
        $('<span>', { class: 'bsc-admin-product-edit__field-label', text: 'Nombre del color' }),
        $nameInput
      );
      $row.append(
        $colorLabel,
        $nameLabel,
        variantNumberField('Precio regular', 'data-bsc-color-variant-regular-price', baseRegularPrice()),
        variantNumberField('Oferta', 'data-bsc-color-variant-sale-price', ''),
        variantNumberField('Stock Bodega', 'data-bsc-color-variant-stock-bodega', '0'),
        variantNumberField('Stock Tienda', 'data-bsc-color-variant-stock-tienda', '0'),
        variantEnabledField('data-bsc-color-variant-enabled', 'data-bsc-color-variant-enabled-hidden', true),
        $removeButton
      );

      return $row;
    }

    function addRow() {
      var $row = createRow();

      $list.append($row);
      reindexRows();
      $row.find('[data-bsc-color-variant-name]').trigger('focus');
    }

    function syncPanelState() {
      var enabled = $toggle.is(':checked');

      if (enabled) {
        disableVariantMode('size');
      }

      $panel.toggleClass('is-hidden', !enabled);

      if (enabled && !$list.find('[data-bsc-color-variant-row]').length) {
        addRow();
      }
    }

    $root
      .on('change', '[data-bsc-color-variants-toggle]', syncPanelState)
      .on('click', '[data-bsc-color-variant-add]', addRow)
      .on('click', '[data-bsc-color-variant-remove]', function () {
        $(this).closest('[data-bsc-color-variant-row]').remove();
        reindexRows();
      })
      .on('input change', '[data-bsc-color-variant-hex]', function () {
        syncPreview($(this).closest('[data-bsc-color-variant-row]'));
      })
      .on('change', '[data-bsc-color-variant-enabled]', function () {
        $(this).closest('[data-bsc-color-variant-row]').toggleClass('is-disabled', !$(this).is(':checked'));
      });

    reindexRows();
    syncPanelState();
  }

  function initSizeVariants() {
    var $root = $('[data-bsc-size-variants]');

    if (!$root.length) {
      return;
    }

    var $toggle = $root.find('[data-bsc-size-variants-toggle]');
    var $panel = $root.find('[data-bsc-size-variants-panel]');
    var $list = $root.find('[data-bsc-size-variants-list]');

    function reindexRows() {
      $list.find('[data-bsc-size-variant-row]').each(function (index) {
        var prefix = '_bsc_size_variants[' + index + ']';
        var $row = $(this);

        $row.find('[data-bsc-size-variant-name]').attr('name', prefix + '[name]');
        $row.find('[data-bsc-size-variant-regular-price]').attr('name', prefix + '[regular_price]');
        $row.find('[data-bsc-size-variant-sale-price]').attr('name', prefix + '[sale_price]');
        $row.find('[data-bsc-size-variant-stock-bodega]').attr('name', prefix + '[stock_bodega]');
        $row.find('[data-bsc-size-variant-stock-tienda]').attr('name', prefix + '[stock_tienda]');
        $row.find('[data-bsc-size-variant-enabled-hidden]').attr('name', prefix + '[enabled]');
        $row.find('[data-bsc-size-variant-enabled]').attr('name', prefix + '[enabled]');
        $row.toggleClass('is-disabled', !$row.find('[data-bsc-size-variant-enabled]').is(':checked'));
      });
    }

    function createRow() {
      var $row = $('<div>', {
        class: 'bsc-admin-product-edit__size-row',
        'data-bsc-size-variant-row': '1',
      });
      var $nameLabel = $('<label>', {
        class: 'bsc-admin-product-edit__field bsc-admin-product-edit__size-name',
      });
      var $nameInput = $('<input>', {
        type: 'text',
        class: 'regular-text',
        placeholder: 'Ej: 50 ml',
        'data-bsc-size-variant-name': '1',
      });
      var $removeButton = $('<button>', {
        type: 'button',
        class: 'button bsc-admin-product-edit__size-remove',
        text: 'Quitar',
        'data-bsc-size-variant-remove': '1',
      });

      $nameLabel.append(
        $('<span>', { class: 'bsc-admin-product-edit__field-label', text: 'Tamano' }),
        $nameInput
      );
      $row.append(
        $nameLabel,
        variantNumberField('Precio regular', 'data-bsc-size-variant-regular-price', baseRegularPrice()),
        variantNumberField('Oferta', 'data-bsc-size-variant-sale-price', ''),
        variantNumberField('Stock Bodega', 'data-bsc-size-variant-stock-bodega', '0'),
        variantNumberField('Stock Tienda', 'data-bsc-size-variant-stock-tienda', '0'),
        variantEnabledField('data-bsc-size-variant-enabled', 'data-bsc-size-variant-enabled-hidden', true),
        $removeButton
      );

      return $row;
    }

    function addRow() {
      var $row = createRow();

      $list.append($row);
      reindexRows();
      $row.find('[data-bsc-size-variant-name]').trigger('focus');
    }

    function syncPanelState() {
      var enabled = $toggle.is(':checked');

      if (enabled) {
        disableVariantMode('color');
      }

      $panel.toggleClass('is-hidden', !enabled);

      if (enabled && !$list.find('[data-bsc-size-variant-row]').length) {
        addRow();
      }
    }

    $root
      .on('change', '[data-bsc-size-variants-toggle]', syncPanelState)
      .on('click', '[data-bsc-size-variant-add]', addRow)
      .on('click', '[data-bsc-size-variant-remove]', function () {
        $(this).closest('[data-bsc-size-variant-row]').remove();
        reindexRows();
      })
      .on('change', '[data-bsc-size-variant-enabled]', function () {
        $(this).closest('[data-bsc-size-variant-row]').toggleClass('is-disabled', !$(this).is(':checked'));
      });

    reindexRows();
    syncPanelState();
  }

  function initCategoryTree() {
    if (!$('#bsc-root-tabs').length || !$('#bsc-cat-branches').length) {
      return;
    }

    var byId = {};
    var bySlug = {};
    var activeRoot = null;
    var canManageCategories = Boolean(config.canManageCategories);
    var ajaxUrl = config.ajaxUrl || window.ajaxurl || '';
    var categoryNonce = config.categoryNonce || '';
    var $formTitle = $('[data-bsc-cat-form-title]');
    var $message = $('[data-bsc-cat-message]');
    var $termId = $('[data-bsc-cat-term-id]');
    var $name = $('[data-bsc-cat-name]');
    var $slug = $('[data-bsc-cat-slug]');
    var $parent = $('[data-bsc-cat-parent]');
    var $createButton = $('[data-bsc-cat-create]');
    var $updateButton = $('[data-bsc-cat-update]');
    var $cancelButton = $('[data-bsc-cat-cancel]');
    var $deleteButton = $('[data-bsc-cat-delete]');
    var $selectedSummary = $('[data-bsc-cat-selected-summary]');
    var $selectedCount = $('[data-bsc-cat-selected-count]');
    var $parentCombo = $();

    function esc(value) {
      return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
    }

    function normalizeSearchText(value) {
      return String(value || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/\s+/g, ' ')
        .trim();
    }

    function getParentOptionData(option) {
      var $option = $(option);
      var label = String($option.text() || '');
      var depth = 0;

      while (label.indexOf('-- ') === 0) {
        depth += 1;
        label = label.substring(3);
      }

      return {
        value: String($option.val()),
        label: label,
        rawLabel: String($option.text() || ''),
        depth: Math.min(depth, 4),
        disabled: $option.prop('disabled'),
        selected: $option.is(':selected'),
      };
    }

    function parentSelectedLabel() {
      var selectedOption = $parent.find('option:selected')[0];

      if (!selectedOption) {
        return 'Sin padre';
      }

      return getParentOptionData(selectedOption).label || 'Sin padre';
    }

    function renderParentOptions(query) {
      if (!$parentCombo.length) {
        return;
      }

      var terms = normalizeSearchText(query).split(' ').filter(Boolean);
      var selectedValue = String($parent.val() || '0');
      var html = '';
      var matches = 0;

      $parent.find('option').each(function () {
        var optionData = getParentOptionData(this);
        var searchText = normalizeSearchText(optionData.rawLabel + ' ' + optionData.label + ' ' + optionData.value);
        var isMatch = !terms.length || terms.every(function (term) {
          return searchText.indexOf(term) !== -1;
        });

        if (!isMatch) {
          return;
        }

        matches += 1;
        html += '<button type="button" role="option" class="bsc-admin-product-edit__parent-option'
          + (optionData.value === selectedValue ? ' is-selected' : '')
          + (optionData.disabled ? ' is-disabled' : '')
          + '" data-bsc-parent-option data-value="' + esc(optionData.value) + '"'
          + ' data-depth="' + optionData.depth + '"'
          + ' aria-selected="' + (optionData.value === selectedValue ? 'true' : 'false') + '"'
          + (optionData.disabled ? ' disabled aria-disabled="true"' : '')
          + '><span>' + esc(optionData.label) + '</span></button>';
      });

      if (!matches) {
        html = '<div class="bsc-admin-product-edit__parent-empty">No hay coincidencias.</div>';
      }

      $parentCombo.find('[data-bsc-parent-options]').html(html);
    }

    function closeParentCombo() {
      if (!$parentCombo.length) {
        return;
      }

      $parentCombo.removeClass('is-open');
      $parentCombo.find('[data-bsc-parent-toggle]').attr('aria-expanded', 'false');
      $parentCombo.find('[data-bsc-parent-panel]').attr('hidden', 'hidden');
    }

    function openParentCombo() {
      if (!$parentCombo.length) {
        return;
      }

      renderParentOptions('');
      $parentCombo.addClass('is-open');
      $parentCombo.find('[data-bsc-parent-toggle]').attr('aria-expanded', 'true');
      $parentCombo.find('[data-bsc-parent-panel]').removeAttr('hidden');
      $parentCombo.find('[data-bsc-parent-search]').val('').trigger('focus');
    }

    function ensureParentCombo() {
      if (!$parent.length || $parentCombo.length) {
        return;
      }

      var comboId = 'bsc-cat-parent-combo';
      var listboxId = comboId + '-listbox';

      $parent.addClass('bsc-admin-product-edit__parent-native');
      $parentCombo = $('<div>', {
        class: 'bsc-admin-product-edit__parent-combo',
        id: comboId,
        'data-bsc-parent-combo': '1',
      });

      $parentCombo.append(
        $('<button>', {
          type: 'button',
          class: 'bsc-admin-product-edit__parent-toggle',
          'aria-haspopup': 'listbox',
          'aria-expanded': 'false',
          'aria-controls': listboxId,
          'data-bsc-parent-toggle': '1',
        }).append(
          $('<span>', {
            class: 'bsc-admin-product-edit__parent-value',
            text: parentSelectedLabel(),
            'data-bsc-parent-value': '1',
          }),
          $('<span>', {
            class: 'bsc-admin-product-edit__parent-caret',
            'aria-hidden': 'true',
          })
        ),
        $('<div>', {
          class: 'bsc-admin-product-edit__parent-panel',
          hidden: 'hidden',
          'data-bsc-parent-panel': '1',
        }).append(
          $('<input>', {
            type: 'text',
            class: 'bsc-admin-product-edit__parent-search',
            placeholder: 'Buscar padre...',
            autocomplete: 'off',
            'aria-label': 'Buscar padre',
            'data-bsc-parent-search': '1',
          }),
          $('<div>', {
            class: 'bsc-admin-product-edit__parent-options',
            id: listboxId,
            role: 'listbox',
            'data-bsc-parent-options': '1',
          })
        )
      );

      $parent.after($parentCombo);

      $parentCombo
        .on('click', '[data-bsc-parent-toggle]', function (event) {
          event.preventDefault();

          if ($parentCombo.hasClass('is-open')) {
            closeParentCombo();
          } else {
            openParentCombo();
          }
        })
        .on('input', '[data-bsc-parent-search]', function () {
          renderParentOptions($(this).val());
        })
        .on('keydown', '[data-bsc-parent-search]', function (event) {
          if (event.key === 'Escape') {
            event.preventDefault();
            closeParentCombo();
            $parentCombo.find('[data-bsc-parent-toggle]').trigger('focus');
          }
        })
        .on('click', '[data-bsc-parent-option]', function (event) {
          event.preventDefault();

          if ($(this).prop('disabled')) {
            return;
          }

          $parent.val($(this).attr('data-value')).trigger('change');
          closeParentCombo();
          $parentCombo.find('[data-bsc-parent-toggle]').trigger('focus');
        });

      $(document).on('mousedown.bscParentCombo', function (event) {
        if ($parentCombo.length && !$parentCombo[0].contains(event.target)) {
          closeParentCombo();
        }
      });

      $parent.on('change.bscParentCombo', syncParentCombo);
    }

    function syncParentCombo() {
      ensureParentCombo();

      if (!$parentCombo.length) {
        return;
      }

      $parentCombo.find('[data-bsc-parent-value]').text(parentSelectedLabel());
      renderParentOptions($parentCombo.find('[data-bsc-parent-search]').val() || '');
    }

    function indexTree(nodes, parentId) {
      nodes.forEach(function (node) {
        node.parentId = parentId;
        byId[node.id] = node;
        bySlug[node.slug] = node;
        if (node.children && node.children.length) {
          indexTree(node.children, node.id);
        }
      });
    }

    function rebuildIndex() {
      byId = {};
      bySlug = {};
      indexTree(catTree, 0);
    }

    function flattenTree(nodes, depth, output) {
      nodes.forEach(function (node) {
        output.push({
          id: node.id,
          name: node.name,
          parentId: node.parentId || 0,
          depth: depth,
        });

        if (node.children && node.children.length) {
          flattenTree(node.children, depth + 1, output);
        }
      });

      return output;
    }

    function isSelfOrDescendant(candidateId, termId) {
      var node = byId[candidateId];
      while (node) {
        if (node.id === termId) {
          return true;
        }

        node = byId[node.parentId];
      }

      return false;
    }

    function selectedParentDefault() {
      var rootNode = bySlug[activeRoot];
      return rootNode ? rootNode.id : 0;
    }

    function populateParentSelect(selectedParentId, excludedTermId) {
      if (!$parent.length) {
        return;
      }

      var flat = flattenTree(catTree, 0, []);
      var html = '<option value="0">Sin padre</option>';

      flat.forEach(function (node) {
        var disabled = excludedTermId && isSelfOrDescendant(node.id, excludedTermId);
        var prefix = new Array(node.depth + 1).join('-- ');
        html += '<option value="' + node.id + '"' + (disabled ? ' disabled' : '') + '>'
          + esc(prefix + node.name)
          + '</option>';
      });

      $parent.html(html);
      $parent.val(String(selectedParentId || 0));
      syncParentCombo();
    }

    function getRootSlug(termId) {
      var node = byId[termId];
      while (node && node.parentId !== 0) {
        node = byId[node.parentId];
      }
      return node && rootSlugs.indexOf(node.slug) !== -1 ? node.slug : null;
    }

    function ensureActiveRoot() {
      if (!activeRoot || !bySlug[activeRoot]) {
        activeRoot = detectInitialRoot();
      }
    }

    function detectInitialRoot() {
      var detected = null;
      currentCats.some(function (termId) {
        var rootSlug = getRootSlug(termId);
        if (rootSlug) {
          detected = rootSlug;
          return true;
        }
        return false;
      });
      return detected || rootSlugs[0] || null;
    }

    function showCategoryMessage(message, isError) {
      if (!$message.length) {
        return;
      }

      $message
        .text(message || '')
        .toggleClass('is-hidden', !message)
        .toggleClass('is-error', Boolean(isError))
        .toggleClass('is-success', Boolean(message) && !isError);
    }

    function setCategoryButtons(isEditing) {
      $createButton.toggleClass('is-hidden', isEditing);
      $updateButton.toggleClass('is-hidden', !isEditing);
      $cancelButton.toggleClass('is-hidden', !isEditing);
      $deleteButton.toggleClass('is-hidden', !isEditing);
    }

    function resetCategoryForm(parentId) {
      $termId.val('');
      $name.val('');
      $slug.val('');
      $formTitle.text('Agregar categoria');
      setCategoryButtons(false);
      populateParentSelect(parentId === undefined ? selectedParentDefault() : parentId, 0);
    }

    function editCategory(termId) {
      var node = byId[termId];
      if (!node) {
        return;
      }

      $termId.val(node.id);
      $name.val(node.name);
      $slug.val(node.slug);
      $formTitle.text('Editar categoria');
      setCategoryButtons(true);
      populateParentSelect(node.parentId || 0, node.id);
      $name.trigger('focus');
    }

    function requestCategory(action, data) {
      data = data || {};
      data.action = action;
      data.nonce = categoryNonce;

      return $.ajax({
        url: ajaxUrl,
        method: 'POST',
        dataType: 'json',
        data: data,
      });
    }

    function refreshTreeFromResponse(response, successMessage, selectTermId) {
      if (!response || !response.success) {
        var message = response && response.data && response.data.message
          ? response.data.message
          : 'No se pudo guardar la categoria.';
        showCategoryMessage(message, true);
        return;
      }

      if (response.data && Array.isArray(response.data.tree)) {
        catTree = response.data.tree;
      }

      if (response.data && response.data.deletedTermId) {
        var deletedTermId = parseInt(response.data.deletedTermId, 10);
        currentCats = currentCats.filter(function (termId) {
          return termId !== deletedTermId;
        });
      }

      rebuildIndex();

      if (response.data && response.data.term) {
        var newRoot = getRootSlug(parseInt(response.data.term.id, 10));
        if (newRoot) {
          activeRoot = newRoot;
        }
      }

      if (selectTermId) {
        var selectedNode = byId[selectTermId];
        var selectedParent = selectedNode ? byId[selectedNode.parentId] : null;
        if (selectedParent && selectedParent.parentId !== 0 && currentCats.indexOf(selectTermId) === -1) {
          currentCats.push(selectTermId);
        }
      }

      ensureActiveRoot();
      renderTabs();
      renderBranches();
      resetCategoryForm();
      showCategoryMessage(successMessage, false);
    }

    function renderTabs() {
      var html = rootSlugs.map(function (slug) {
        return '<button type="button" class="button' + (slug === activeRoot ? ' button-primary' : '') + '" data-root="' + esc(slug) + '">' + esc(rootLabels[slug] || slug) + '</button>';
      }).join('');

      $('#bsc-root-tabs').html(html);
    }

    function termActionsHtml(node) {
      if (!canManageCategories) {
        return '';
      }

      return '<details class="bsc-admin-product-edit__term-menu">'
        + '<summary aria-label="Acciones para ' + esc(node.name) + '">...</summary>'
        + '<span class="bsc-admin-product-edit__term-actions">'
        + '<button type="button" class="button-link bsc-admin-product-edit__term-action" data-bsc-cat-edit-term="' + node.id + '" aria-label="Editar ' + esc(node.name) + '">Editar</button>'
        + '<button type="button" class="button-link bsc-admin-product-edit__term-action" data-bsc-cat-add-child="' + node.id + '" aria-label="Agregar hija a ' + esc(node.name) + '">+ hija</button>'
        + '<button type="button" class="button-link-delete bsc-admin-product-edit__term-action bsc-admin-product-edit__term-action--delete" data-bsc-cat-delete-term="' + node.id + '" aria-label="Eliminar ' + esc(node.name) + '">Eliminar</button>'
        + '</span>'
        + '</details>';
    }

    function leafHtml(node) {
      var checked = currentCats.indexOf(node.id) !== -1 ? ' checked' : '';
      var selectedClass = checked ? ' is-selected' : '';
      return '<div class="bsc-admin-product-edit__term-row' + selectedClass + '" data-term-id="' + node.id + '" data-search="' + esc(normalizeSearchText(node.name + ' ' + node.slug + ' ' + node.id)) + '">'
        + '<label class="bsc-admin-product-edit__leaf">'
        + '<input type="checkbox" class="bsc-cat-check" value="' + node.id + '"' + checked + '> '
        + '<span>' + esc(node.name) + '</span>'
        + '</label>'
        + termActionsHtml(node)
        + '</div>';
    }

    function renderBranches() {
      ensureActiveRoot();
      var rootNode = bySlug[activeRoot];
      var $container = $('#bsc-cat-branches');

      if (!rootNode || !rootNode.children || !rootNode.children.length) {
        $container.html('<p class="bsc-admin-product-edit__empty">' + esc(strings.noCategories || 'No hay categorias para este grupo.') + '</p>');
        resetCategoryForm(rootNode ? rootNode.id : 0);
        syncHiddenInputs();
        return;
      }

      var html = '<div class="bsc-admin-product-edit__branch-grid">';

      rootNode.children.forEach(function (branch) {
        html += '<div class="bsc-branch-section bsc-admin-product-edit__branch">';
        html += '<div class="bsc-admin-product-edit__branch-header">';
        html += '<strong class="bsc-admin-product-edit__branch-title">' + esc(branch.name) + '</strong>';
        html += termActionsHtml(branch);
        html += '</div>';
        html += '<input type="text" class="bsc-branch-search bsc-admin-product-edit__branch-search" placeholder="' + esc(strings.searchPlaceholder || 'Buscar...') + '">';
        html += '<div class="bsc-branch-items">';

        if (!branch.children || !branch.children.length) {
          html += '<em class="bsc-admin-product-edit__subgroup-empty">' + esc(strings.noSubcategories || 'Sin subcategorias') + '</em>';
        } else {
          branch.children.forEach(function (child) {
            if (child.children && child.children.length) {
              html += '<div class="bsc-subgroup bsc-admin-product-edit__subgroup">';
              html += '<div class="bsc-admin-product-edit__subgroup-header">';
              html += '<span>' + esc(child.name) + '</span>';
              html += termActionsHtml(child);
              html += '</div>';
              child.children.forEach(function (grandChild) {
                html += leafHtml(grandChild);
              });
              html += '</div>';
            } else {
              html += leafHtml(child);
            }
          });
        }

        html += '</div></div>';
      });

      html += '</div>';
      $container.html(html);
      if (!$termId.val()) {
        resetCategoryForm(selectedParentDefault());
      }
      syncHiddenInputs();
    }

    function syncHiddenInputs() {
      var $container = $('#bsc-cat-hidden-inputs');
      $container.empty();

      currentCats = currentCats
        .map(function (termId) { return parseInt(termId, 10); })
        .filter(function (termId, index, ids) {
          return termId > 0 && ids.indexOf(termId) === index;
        });

      currentCats.forEach(function (termId) {
        $('<input>', {
          type: 'hidden',
          name: 'product_cat[]',
          value: termId,
        }).appendTo($container);
      });

      renderSelectedSummary();
    }

    function renderSelectedSummary() {
      var selectedNodes = currentCats
        .map(function (termId) { return byId[termId]; })
        .filter(Boolean);

      if ($selectedCount.length) {
        $selectedCount.text(selectedNodes.length + (selectedNodes.length === 1 ? ' seleccionada' : ' seleccionadas'));
      }

      if (!$selectedSummary.length) {
        return;
      }

      if (!selectedNodes.length) {
        $selectedSummary.html('<span class="bsc-admin-product-edit__selected-empty">Sin categorias seleccionadas</span>');
        return;
      }

      $selectedSummary.html(selectedNodes.map(function (node) {
        return '<span class="bsc-admin-product-edit__selected-chip">' + esc(node.name) + '</span>';
      }).join(''));
    }

    function updateCurrentCategorySelection(checkbox) {
      var termId = parseInt($(checkbox).val(), 10);
      if (!termId) {
        return;
      }

      if (checkbox.checked && currentCats.indexOf(termId) === -1) {
        currentCats.push(termId);
      }

      if (!checkbox.checked) {
        currentCats = currentCats.filter(function (currentTermId) {
          return currentTermId !== termId;
        });
      }

      $(checkbox).closest('.bsc-admin-product-edit__term-row').toggleClass('is-selected', checkbox.checked);
      syncHiddenInputs();
    }

    function filterBranch($section, query) {
      var terms = normalizeSearchText(query).split(' ').filter(Boolean);

      $section.find('.bsc-branch-items .bsc-admin-product-edit__term-row').each(function () {
        var text = $(this).attr('data-search') || normalizeSearchText($(this).text());
        var matches = !terms.length || terms.every(function (term) {
          return text.indexOf(term) !== -1;
        });
        $(this).toggleClass('is-hidden', !matches);
      });

      $section.find('.bsc-admin-product-edit__subgroup').each(function () {
        var anyVisible = $(this).find('.bsc-admin-product-edit__term-row').toArray().some(function (item) {
          return !item.classList.contains('is-hidden');
        });

        $(this).toggleClass('is-hidden', Boolean(query) && !anyVisible);
      });
    }

    function saveCategory(action) {
      if (!$name.val().trim()) {
        showCategoryMessage('Escribe el nombre de la categoria.', true);
        $name.trigger('focus');
        return;
      }

      requestCategory(action, {
        term_id: $termId.val(),
        name: $name.val(),
        slug: $slug.val(),
        parent: $parent.val(),
      })
        .done(function (response) {
          var selectTermId = action === 'bsc_product_category_create' && response && response.data && response.data.term
            ? parseInt(response.data.term.id, 10)
            : 0;
          refreshTreeFromResponse(response, strings.categorySaved || 'Categoria guardada.', selectTermId);
        })
        .fail(function (xhr) {
          refreshTreeFromResponse(xhr.responseJSON, '');
        });
    }

    function deleteCategory(termId) {
      var node = byId[termId];
      if (!node) {
        return;
      }

      var confirmed = window.confirm('Eliminar "' + node.name + '"? Si tiene subcategorias, WordPress las movera al padre.');
      if (!confirmed) {
        return;
      }

      requestCategory('bsc_product_category_delete', {
        term_id: termId,
      })
        .done(function (response) {
          refreshTreeFromResponse(response, strings.categoryDeleted || 'Categoria eliminada.');
        })
        .fail(function (xhr) {
          refreshTreeFromResponse(xhr.responseJSON, '');
        });
    }

    rebuildIndex();
    activeRoot = detectInitialRoot();

    $(document)
      .on('click', '#bsc-root-tabs .button', function () {
        activeRoot = $(this).attr('data-root');
        renderTabs();
        renderBranches();
      })
      .on('change', '.bsc-cat-check', function () {
        updateCurrentCategorySelection(this);
      })
      .on('input', '.bsc-branch-search', function () {
        filterBranch($(this).closest('.bsc-branch-section'), $(this).val());
      })
      .on('click', '[data-bsc-cat-add-child]', function () {
        resetCategoryForm(parseInt($(this).attr('data-bsc-cat-add-child'), 10) || selectedParentDefault());
        $name.trigger('focus');
      })
      .on('click', '[data-bsc-cat-edit-term]', function () {
        editCategory(parseInt($(this).attr('data-bsc-cat-edit-term'), 10));
      })
      .on('click', '[data-bsc-cat-delete-term]', function () {
        deleteCategory(parseInt($(this).attr('data-bsc-cat-delete-term'), 10));
      })
      .on('click', '[data-bsc-cat-create]', function () {
        saveCategory('bsc_product_category_create');
      })
      .on('click', '[data-bsc-cat-update]', function () {
        saveCategory('bsc_product_category_update');
      })
      .on('click', '[data-bsc-cat-delete]', function () {
        deleteCategory(parseInt($termId.val(), 10));
      })
      .on('click', '[data-bsc-cat-cancel]', function () {
        resetCategoryForm();
        showCategoryMessage('', false);
      });

    renderTabs();
    renderBranches();
  }

  $(function () {
    initColorVariants();
    initSizeVariants();
    initCategoryTree();
  });
}(jQuery));
