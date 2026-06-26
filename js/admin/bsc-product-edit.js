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

    function esc(value) {
      return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
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

      return '<span class="bsc-admin-product-edit__term-actions">'
        + '<button type="button" class="button-link" data-bsc-cat-edit-term="' + node.id + '">Editar</button>'
        + '<button type="button" class="button-link" data-bsc-cat-add-child="' + node.id + '">Agregar hija</button>'
        + '<button type="button" class="button-link-delete" data-bsc-cat-delete-term="' + node.id + '">Eliminar</button>'
        + '</span>';
    }

    function leafHtml(node) {
      var checked = currentCats.indexOf(node.id) !== -1 ? ' checked' : '';
      return '<div class="bsc-admin-product-edit__term-row" data-term-id="' + node.id + '">'
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

      syncHiddenInputs();
    }

    function filterBranch($section, query) {
      $section.find('.bsc-branch-items .bsc-admin-product-edit__term-row').each(function () {
        var matches = !query || $(this).text().toLowerCase().indexOf(query) !== -1;
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
        filterBranch($(this).closest('.bsc-branch-section'), $(this).val().toLowerCase().trim());
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

  $(initCategoryTree);
}(jQuery));
