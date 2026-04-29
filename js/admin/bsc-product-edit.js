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
    if (!catTree.length || !$('#bsc-root-tabs').length || !$('#bsc-cat-branches').length) {
      return;
    }

    var byId = {};
    var bySlug = {};
    var activeRoot = null;

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

    function getRootSlug(termId) {
      var node = byId[termId];
      while (node && node.parentId !== 0) {
        node = byId[node.parentId];
      }
      return node && rootSlugs.indexOf(node.slug) !== -1 ? node.slug : null;
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

    function esc(value) {
      return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
    }

    function renderTabs() {
      var html = rootSlugs.map(function (slug) {
        return '<button type="button" class="button' + (slug === activeRoot ? ' button-primary' : '') + '" data-root="' + esc(slug) + '">' + esc(rootLabels[slug] || slug) + '</button>';
      }).join('');

      $('#bsc-root-tabs').html(html);
    }

    function leafHtml(node) {
      var checked = currentCats.indexOf(node.id) !== -1 ? ' checked' : '';
      return '<label class="bsc-admin-product-edit__leaf">'
        + '<input type="checkbox" class="bsc-cat-check" value="' + node.id + '"' + checked + '> '
        + esc(node.name)
        + '</label>';
    }

    function renderBranches() {
      var rootNode = bySlug[activeRoot];
      var $container = $('#bsc-cat-branches');

      if (!rootNode || !rootNode.children || !rootNode.children.length) {
        $container.html('<p class="bsc-admin-product-edit__empty">' + esc(strings.noCategories || 'No hay categorias para este grupo.') + '</p>');
        syncHiddenInputs();
        return;
      }

      var html = '<div class="bsc-admin-product-edit__branch-grid">';

      rootNode.children.forEach(function (branch) {
        html += '<div class="bsc-branch-section bsc-admin-product-edit__branch">';
        html += '<strong class="bsc-admin-product-edit__branch-title">' + esc(branch.name) + '</strong>';
        html += '<input type="text" class="bsc-branch-search bsc-admin-product-edit__branch-search" placeholder="' + esc(strings.searchPlaceholder || 'Buscar...') + '">';
        html += '<div class="bsc-branch-items">';

        if (!branch.children || !branch.children.length) {
          html += '<em class="bsc-admin-product-edit__subgroup-empty">' + esc(strings.noSubcategories || 'Sin subcategorias') + '</em>';
        } else {
          branch.children.forEach(function (child) {
            if (child.children && child.children.length) {
              html += '<div class="bsc-subgroup bsc-admin-product-edit__subgroup">';
              html += '<div class="bsc-admin-product-edit__subgroup-header">' + esc(child.name) + '</div>';
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
      syncHiddenInputs();
    }

    function syncHiddenInputs() {
      var $container = $('#bsc-cat-hidden-inputs');
      $container.empty();

      $('.bsc-cat-check:checked').each(function () {
        $('<input>', {
          type: 'hidden',
          name: 'product_cat[]',
          value: $(this).val(),
        }).appendTo($container);
      });
    }

    function filterBranch($section, query) {
      $section.find('.bsc-branch-items .bsc-admin-product-edit__leaf').each(function () {
        var matches = !query || $(this).text().toLowerCase().indexOf(query) !== -1;
        $(this).toggleClass('is-hidden', !matches);
      });

      $section.find('.bsc-admin-product-edit__subgroup').each(function () {
        var anyVisible = $(this).find('.bsc-admin-product-edit__leaf').toArray().some(function (item) {
          return !item.classList.contains('is-hidden');
        });

        $(this).toggleClass('is-hidden', Boolean(query) && !anyVisible);
      });
    }

    indexTree(catTree, 0);
    activeRoot = detectInitialRoot();

    $(document)
      .on('click', '#bsc-root-tabs .button', function () {
        activeRoot = $(this).attr('data-root');
        renderTabs();
        renderBranches();
      })
      .on('change', '.bsc-cat-check', syncHiddenInputs)
      .on('input', '.bsc-branch-search', function () {
        filterBranch($(this).closest('.bsc-branch-section'), $(this).val().toLowerCase().trim());
      });

    renderTabs();
    renderBranches();
  }

  $(initCategoryTree);
}(jQuery));
