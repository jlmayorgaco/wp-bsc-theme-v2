(function ($) {
	'use strict';

	var config = window.bscHeaderMenus || {};
	var categorySearchCache = {};
	var categoryChoices = Array.isArray(config.categoryChoices) ? config.categoryChoices.slice() : [];

	function setPreview($card, url) {
		var image = new Image();

		image.alt = '';
		image.src = url;

		$card.find('[data-bsc-header-menu-preview]').empty().append(image);
	}

	function getCategorySearchMinLength() {
		var minLength = parseInt(config.minSearchLength, 10);

		return Number.isNaN(minLength) ? 2 : minLength;
	}

	function getCategorySearchDelay() {
		var delay = parseInt(config.searchDelay, 10);

		return Number.isNaN(delay) ? 250 : delay;
	}

	function normalizeCategorySearch(value) {
		return String(value || '')
			.normalize('NFD')
			.replace(/[\u0300-\u036f]/g, '')
			.toLowerCase()
			.trim();
	}

	function getLocalCategoryChoices(query, limit) {
		var normalizedQuery = normalizeCategorySearch(query);
		var filtered = categoryChoices.filter(function (choice) {
			var haystack = choice && choice.search
				? String(choice.search)
				: [choice.label, choice.name, choice.slug].join(' ');

			return normalizedQuery === '' || normalizeCategorySearch(haystack).indexOf(normalizedQuery) !== -1;
		});

		return typeof limit === 'number' ? filtered.slice(0, limit) : filtered;
	}

	function replaceIndexedName(name, sectionIndex, itemIndex) {
		var nextName = String(name || '').replace(
			/\[sections\]\[[^\]]+\]/,
			'[sections][' + sectionIndex + ']'
		);

		if (typeof itemIndex !== 'undefined') {
			nextName = nextName.replace(
				/\[items\]\[[^\]]+\]/,
				'[items][' + itemIndex + ']'
			);
		}

		return nextName;
	}

	function replaceIndexedId(value, sectionIndex, itemIndex) {
		var nextValue = String(value || '')
			.replace(/__SECTION_INDEX__/g, sectionIndex)
			.replace(/section-(?:__SECTION_INDEX__|\d+)/g, 'section-' + sectionIndex);

		if (typeof itemIndex !== 'undefined') {
			nextValue = nextValue
				.replace(/__ITEM_INDEX__/g, itemIndex)
				.replace(/item-(?:__ITEM_INDEX__|\d+)/g, 'item-' + itemIndex);
		}

		return nextValue;
	}

	function updateIndexedAttributes(element, sectionIndex, itemIndex) {
		Array.prototype.slice.call(element.querySelectorAll('[name]')).forEach(function (field) {
			field.name = replaceIndexedName(field.name, sectionIndex, itemIndex);
		});

		Array.prototype.slice.call(element.querySelectorAll('[id]')).forEach(function (field) {
			field.id = replaceIndexedId(field.id, sectionIndex, itemIndex);
		});

		Array.prototype.slice.call(element.querySelectorAll('label[for]')).forEach(function (label) {
			label.setAttribute('for', replaceIndexedId(label.getAttribute('for'), sectionIndex, itemIndex));
		});
	}

	function getSections(editor) {
		return Array.prototype.slice.call(editor.querySelectorAll(':scope > [data-bsc-header-menu-sections] > [data-bsc-header-menu-section]'));
	}

	function getItems(section) {
		var itemsWrap = section.querySelector('[data-bsc-header-menu-items]');

		return itemsWrap
			? Array.prototype.slice.call(itemsWrap.querySelectorAll(':scope > [data-bsc-header-menu-item]'))
			: [];
	}

	function renumberEditor(editor) {
		getSections(editor).forEach(function (section, sectionIndex) {
			var sectionNumber = section.querySelector('[data-bsc-header-menu-section-number]');
			var numbered = section.querySelector('[data-bsc-header-menu-section-numbered]');

			if (sectionNumber) {
				sectionNumber.textContent = String(sectionIndex + 1);
			}

			updateIndexedAttributes(section, sectionIndex);

			getItems(section).forEach(function (item, itemIndex) {
				var itemNumber = item.querySelector('[data-bsc-header-menu-item-number]');

				if (itemNumber) {
					itemNumber.textContent = String(itemIndex + 1);
				}

				item.classList.toggle('is-numbered', !!(numbered && numbered.checked));
				updateIndexedAttributes(item, sectionIndex, itemIndex);
			});
		});
	}

	function getTemplateContent(editor, selector) {
		var template = editor.querySelector(selector);

		if (!template || !template.content || !template.content.firstElementChild) {
			return null;
		}

		return template.content.firstElementChild.cloneNode(true);
	}

	function addItem(section, shouldFocus) {
		var editor = section.closest('[data-bsc-header-menu-editor]');
		var itemsWrap = section.querySelector('[data-bsc-header-menu-items]');
		var item = editor ? getTemplateContent(editor, '[data-bsc-header-menu-item-template]') : null;

		if (!item || !itemsWrap) {
			return;
		}

		itemsWrap.appendChild(item);
		renumberEditor(editor);

		if (shouldFocus) {
			var label = item.querySelector('[data-bsc-header-menu-item-label]');

			if (label) {
				label.focus();
			}
		}
	}

	function addSection(editor) {
		var sectionsWrap = editor.querySelector('[data-bsc-header-menu-sections]');
		var section = getTemplateContent(editor, '[data-bsc-header-menu-section-template]');

		if (!section || !sectionsWrap) {
			return;
		}

		sectionsWrap.appendChild(section);
		addItem(section, false);
		renumberEditor(editor);

		var title = section.querySelector('[data-bsc-header-menu-section-title]');

		if (title) {
			title.focus();
		}
	}

	function moveNode(node, direction) {
		var parent = node.parentNode;

		if (!parent) {
			return;
		}

		if (direction < 0 && node.previousElementSibling) {
			parent.insertBefore(node, node.previousElementSibling);
		}

		if (direction > 0 && node.nextElementSibling) {
			parent.insertBefore(node.nextElementSibling, node);
		}
	}

	function createCategoryOption(choice, selectedValue) {
		var option = document.createElement('option');
		var value = String(choice && choice.id ? choice.id : '');

		option.value = value;
		option.textContent = choice && choice.label ? choice.label : '';
		option.selected = value !== '' && value === String(selectedValue || '');

		if (choice && choice.name) {
			option.setAttribute('data-name', choice.name);
		}

		if (choice && choice.slug) {
			option.setAttribute('data-slug', choice.slug);
		}

		return option;
	}

	function renderCategoryChoices(select, choices, selectedValue) {
		var currentSelected = select.options[select.selectedIndex];
		var emptyOption = document.createElement('option');
		var hasSelected = !selectedValue;
		var fragment = document.createDocumentFragment();

		emptyOption.value = '';
		emptyOption.textContent = 'Selecciona categoria';
		fragment.appendChild(emptyOption);

		if (selectedValue && currentSelected && currentSelected.value === String(selectedValue)) {
			fragment.appendChild(currentSelected.cloneNode(true));
			hasSelected = true;
		}

		(choices || []).forEach(function (choice) {
			var value = String(choice && choice.id ? choice.id : '');

			if (value === '') {
				return;
			}

			if (value === String(selectedValue || '')) {
				hasSelected = true;
			}

			if (Array.prototype.some.call(fragment.querySelectorAll('option'), function (option) {
				return option.value === value;
			})) {
				return;
			}

			fragment.appendChild(createCategoryOption(choice, selectedValue));
		});

		if (!hasSelected && currentSelected && currentSelected.value) {
			fragment.appendChild(currentSelected.cloneNode(true));
		}

		select.replaceChildren(fragment);
		select.value = selectedValue || '';
		select.classList.toggle('is-filtered-empty', (choices || []).length === 0);
	}

	function setCategoryHelp(input, message, isError) {
		var wrapper = input.closest('.bsc-header-menu-item__category');
		var help = wrapper ? wrapper.querySelector('[data-bsc-header-menu-category-help]') : null;

		if (!help) {
			return;
		}

		help.textContent = message;
		help.classList.toggle('is-error', !!isError);
	}

	function searchCategoryChoices(input) {
		var wrapper = input.closest('.bsc-header-menu-item__category');
		var select = wrapper ? wrapper.querySelector('[data-bsc-header-menu-category-select]') : null;
		var query = input.value.trim();
		var selectedValue = select ? select.value : '';
		var cacheKey = query.toLowerCase() + '|' + selectedValue;

		if (!select) {
			return;
		}

		if (categoryChoices.length) {
			var localChoices = getLocalCategoryChoices(query, query === '' ? 50 : 30);

			renderCategoryChoices(select, localChoices, selectedValue);
			setCategoryHelp(
				input,
				localChoices.length
					? localChoices.length + ' categoria(s) disponible(s) en el selector.'
					: 'No encontramos categorias con esa busqueda. Puedes crear una nueva.',
				localChoices.length === 0
			);
			return;
		}

		if (query.length < getCategorySearchMinLength()) {
			select.classList.remove('is-filtered-empty', 'is-loading');
			setCategoryHelp(input, 'Escribe al menos ' + getCategorySearchMinLength() + ' caracteres para buscar.', false);
			return;
		}

		if (categorySearchCache[cacheKey]) {
			renderCategoryChoices(select, categorySearchCache[cacheKey], selectedValue);
			return;
		}

		if (input._bscHeaderMenuCategoryRequest) {
			input._bscHeaderMenuCategoryRequest.abort();
		}

		select.classList.add('is-loading');

		input._bscHeaderMenuCategoryRequest = $.ajax({
			url: config.ajaxUrl || window.ajaxurl,
			dataType: 'json',
			data: {
				action: 'bsc_header_menu_search_categories',
				nonce: config.nonce || '',
				q: query,
				selected: selectedValue
			}
		}).done(function (response) {
			var choices = response && response.success && response.data
				? response.data.choices || []
				: [];

			categorySearchCache[cacheKey] = choices;
			renderCategoryChoices(select, choices, selectedValue);
			setCategoryHelp(
				input,
				choices.length ? choices.length + ' categoria(s) encontrada(s).' : 'No encontramos categorias con esa busqueda.',
				choices.length === 0
			);
		}).fail(function () {
			setCategoryHelp(input, 'No se pudieron cargar las categorias. Recarga la pagina e intenta de nuevo.', true);
		}).always(function () {
			select.classList.remove('is-loading');
			input._bscHeaderMenuCategoryRequest = null;
		});
	}

	function scheduleCategorySearch(input) {
		if (input._bscHeaderMenuCategoryTimer) {
			window.clearTimeout(input._bscHeaderMenuCategoryTimer);
		}

		input._bscHeaderMenuCategoryTimer = window.setTimeout(function () {
			searchCategoryChoices(input);
		}, getCategorySearchDelay());
	}

	function syncLabelFromCategory(select) {
		var item = select.closest('[data-bsc-header-menu-item]');
		var label = item ? item.querySelector('[data-bsc-header-menu-item-label]') : null;
		var search = item ? item.querySelector('[data-bsc-header-menu-category-search]') : null;
		var option = select.options[select.selectedIndex];
		var categoryName = option ? option.getAttribute('data-name') || '' : '';
		var categoryLabel = option ? option.textContent.trim() : '';

		if (label && label.value.trim() === '' && categoryName !== '') {
			label.value = categoryName;
		}

		if (search) {
			search.value = select.value ? categoryLabel : '';
			setCategoryHelp(
				search,
				select.value ? 'Categoria asignada: ' + categoryLabel : 'Selecciona una categoria destino.',
				false
			);
		}
	}

	function slugifyCategory(value) {
		return normalizeCategorySearch(value)
			.replace(/[^a-z0-9]+/g, '-')
			.replace(/^-+|-+$/g, '');
	}

	function populateCategoryParentSelect(select) {
		var selectedValue = select.value;
		var fragment = document.createDocumentFragment();
		var emptyOption = document.createElement('option');

		emptyOption.value = '';
		emptyOption.textContent = 'Sin categoria padre';
		fragment.appendChild(emptyOption);

		categoryChoices.forEach(function (choice) {
			var option = createCategoryOption(choice, selectedValue);

			option.textContent = choice.label || choice.name || choice.slug || '';
			fragment.appendChild(option);
		});

		select.replaceChildren(fragment);
		select.value = selectedValue;
	}

	function updateCreateCategorySlug(panel) {
		var name = panel.querySelector('[data-bsc-header-menu-category-create-name]');
		var parent = panel.querySelector('[data-bsc-header-menu-category-create-parent]');
		var slug = panel.querySelector('[data-bsc-header-menu-category-create-slug]');
		var parentOption = parent && parent.options[parent.selectedIndex];
		var parentSlug = parentOption ? parentOption.getAttribute('data-slug') || '' : '';
		var nameSlug = name ? slugifyCategory(name.value) : '';

		if (!slug || slug.dataset.slugEdited === '1') {
			return;
		}

		slug.value = [parentSlug, nameSlug].filter(Boolean).join('-');
	}

	function toggleCreateCategoryPanel(item, shouldOpen) {
		var panel = item.querySelector('[data-bsc-header-menu-category-create]');
		var parent = panel ? panel.querySelector('[data-bsc-header-menu-category-create-parent]') : null;
		var name = panel ? panel.querySelector('[data-bsc-header-menu-category-create-name]') : null;
		var selectedCategory = item.querySelector('[data-bsc-header-menu-category-select]');

		if (!panel) {
			return;
		}

		panel.hidden = !shouldOpen;

		if (!shouldOpen) {
			return;
		}

		if (parent) {
			populateCategoryParentSelect(parent);
			if (selectedCategory && selectedCategory.value) {
				parent.value = selectedCategory.value;
			}
		}

		updateCreateCategorySlug(panel);

		if (name) {
			name.focus();
		}
	}

	function addCategoryChoice(choice) {
		var choiceId = String(choice && choice.id ? choice.id : '');
		var exists = categoryChoices.some(function (categoryChoice) {
			return String(categoryChoice && categoryChoice.id ? categoryChoice.id : '') === choiceId;
		});

		if (!choiceId || exists) {
			return;
		}

		categoryChoices.push(choice);
		categoryChoices.sort(function (a, b) {
			return String(a.label || '').localeCompare(String(b.label || ''), 'es', { sensitivity: 'base' });
		});
		categorySearchCache = {};
	}

	function createAndAssignCategory(item) {
		var panel = item.querySelector('[data-bsc-header-menu-category-create]');
		var name = panel ? panel.querySelector('[data-bsc-header-menu-category-create-name]') : null;
		var parent = panel ? panel.querySelector('[data-bsc-header-menu-category-create-parent]') : null;
		var slug = panel ? panel.querySelector('[data-bsc-header-menu-category-create-slug]') : null;
		var submit = panel ? panel.querySelector('[data-bsc-header-menu-category-create-submit]') : null;
		var status = panel ? panel.querySelector('[data-bsc-header-menu-category-create-status]') : null;
		var select = item.querySelector('[data-bsc-header-menu-category-select]');

		if (!panel || !name || !parent || !slug || !submit || !status || !select) {
			return;
		}

		if (name.value.trim() === '') {
			status.textContent = 'Escribe el nombre de la categoria.';
			status.classList.add('is-error');
			name.focus();
			return;
		}

		submit.disabled = true;
		status.textContent = 'Creando categoria...';
		status.classList.remove('is-error', 'is-success');

		$.ajax({
			url: config.ajaxUrl || window.ajaxurl,
			method: 'POST',
			dataType: 'json',
			data: {
				action: 'bsc_header_menu_create_category',
				nonce: config.createNonce || '',
				name: name.value.trim(),
				slug: slug.value.trim(),
				parent: parent.value
			}
		}).done(function (response) {
			var choice = response && response.success && response.data ? response.data.choice : null;

			if (!choice) {
				status.textContent = response && response.data && response.data.message
					? response.data.message
					: 'No se pudo crear la categoria.';
				status.classList.add('is-error');
				return;
			}

			addCategoryChoice(choice);
			renderCategoryChoices(select, [choice], String(choice.id));
			select.value = String(choice.id);
			syncLabelFromCategory(select);
			status.textContent = response.data.message || 'Categoria creada y asignada.';
			status.classList.add('is-success');
			setCategoryHelp(
				item.querySelector('[data-bsc-header-menu-category-search]'),
				response.data.message || 'Categoria creada y asignada.',
				false
			);
			window.setTimeout(function () {
				toggleCreateCategoryPanel(item, false);
			}, 700);
		}).fail(function (xhr) {
			var response = xhr && xhr.responseJSON;
			var message = response && response.data && response.data.message
				? response.data.message
				: 'No se pudo crear la categoria.';

			status.textContent = message;
			status.classList.add('is-error');
		}).always(function () {
			submit.disabled = false;
		});
	}

	function initEditor(editor) {
		renumberEditor(editor);

		editor.addEventListener('click', function (event) {
			var target = event.target;
			var section;
			var item;

			if (!(target instanceof HTMLElement)) {
				return;
			}

			if (target.matches('[data-bsc-header-menu-add-section]')) {
				addSection(editor);
				return;
			}

			if (target.matches('[data-bsc-header-menu-category-load]')) {
				item = target.closest('[data-bsc-header-menu-item]');
				if (item) {
					var categoryInput = item.querySelector('[data-bsc-header-menu-category-search]');
					var categorySelect = item.querySelector('[data-bsc-header-menu-category-select]');

					if (categoryInput && categorySelect) {
						renderCategoryChoices(categorySelect, getLocalCategoryChoices(''), categorySelect.value);
						setCategoryHelp(categoryInput, categoryChoices.length + ' categoria(s) cargada(s).', false);
					}
				}
				return;
			}

			if (target.matches('[data-bsc-header-menu-category-create-toggle]')) {
				item = target.closest('[data-bsc-header-menu-item]');
				if (item) {
					toggleCreateCategoryPanel(item, true);
				}
				return;
			}

			if (target.matches('[data-bsc-header-menu-category-create-cancel]')) {
				item = target.closest('[data-bsc-header-menu-item]');
				if (item) {
					toggleCreateCategoryPanel(item, false);
				}
				return;
			}

			if (target.matches('[data-bsc-header-menu-category-create-submit]')) {
				item = target.closest('[data-bsc-header-menu-item]');
				if (item) {
					createAndAssignCategory(item);
				}
				return;
			}

			if (target.matches('[data-bsc-header-menu-add-item]')) {
				section = target.closest('[data-bsc-header-menu-section]');
				if (section) {
					addItem(section, true);
				}
				return;
			}

			if (target.matches('[data-bsc-header-menu-remove-section]')) {
				section = target.closest('[data-bsc-header-menu-section]');
				if (section) {
					section.remove();
					renumberEditor(editor);
				}
				return;
			}

			if (target.matches('[data-bsc-header-menu-remove-item]')) {
				item = target.closest('[data-bsc-header-menu-item]');
				if (item) {
					item.remove();
					renumberEditor(editor);
				}
				return;
			}

			if (target.matches('[data-bsc-header-menu-section-up]')) {
				section = target.closest('[data-bsc-header-menu-section]');
				if (section) {
					moveNode(section, -1);
					renumberEditor(editor);
				}
				return;
			}

			if (target.matches('[data-bsc-header-menu-section-down]')) {
				section = target.closest('[data-bsc-header-menu-section]');
				if (section) {
					moveNode(section, 1);
					renumberEditor(editor);
				}
				return;
			}

			if (target.matches('[data-bsc-header-menu-item-up]')) {
				item = target.closest('[data-bsc-header-menu-item]');
				if (item) {
					moveNode(item, -1);
					renumberEditor(editor);
				}
				return;
			}

			if (target.matches('[data-bsc-header-menu-item-down]')) {
				item = target.closest('[data-bsc-header-menu-item]');
				if (item) {
					moveNode(item, 1);
					renumberEditor(editor);
				}
			}
		});

		editor.addEventListener('input', function (event) {
			var target = event.target;

			if (!(target instanceof HTMLElement)) {
				return;
			}

			if (target.matches('[data-bsc-header-menu-category-search]')) {
				scheduleCategorySearch(target);
			}

			if (target.matches('[data-bsc-header-menu-category-create-name]')) {
				var createPanel = target.closest('[data-bsc-header-menu-category-create]');
				if (createPanel) {
					updateCreateCategorySlug(createPanel);
				}
			}

			if (target.matches('[data-bsc-header-menu-category-create-slug]')) {
				target.dataset.slugEdited = target.value.trim() === '' ? '0' : '1';
			}
		});

		editor.addEventListener('focusin', function (event) {
			var target = event.target;

			if (target instanceof HTMLElement && target.matches('[data-bsc-header-menu-category-search]')) {
				var wrapper = target.closest('.bsc-header-menu-item__category');
				var select = wrapper ? wrapper.querySelector('[data-bsc-header-menu-category-select]') : null;

				if (select && categoryChoices.length) {
					renderCategoryChoices(select, getLocalCategoryChoices('', 50), select.value);
					setCategoryHelp(target, 'Empieza a escribir o abre el selector para elegir una categoria.', false);
				}
			}
		});

		editor.addEventListener('change', function (event) {
			var target = event.target;

			if (!(target instanceof HTMLElement)) {
				return;
			}

			if (target.matches('[data-bsc-header-menu-category-select]')) {
				syncLabelFromCategory(target);
			}

			if (target.matches('[data-bsc-header-menu-section-numbered]')) {
				renumberEditor(editor);
			}

			if (target.matches('[data-bsc-header-menu-category-create-parent]')) {
				var createPanel = target.closest('[data-bsc-header-menu-category-create]');
				if (createPanel) {
					updateCreateCategorySlug(createPanel);
				}
			}
		});
	}

	function initEditors() {
		Array.prototype.slice.call(document.querySelectorAll('[data-bsc-header-menu-editor]')).forEach(initEditor);
	}

	$(document).on('click', '[data-bsc-header-menu-select]', function () {
		var $card = $(this).closest('[data-bsc-header-menu-card]');
		var frame;

		if (!window.wp || !window.wp.media) {
			return;
		}

		frame = wp.media({
			title: 'Seleccionar imagen del header',
			button: { text: 'Usar esta imagen' },
			multiple: false
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

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initEditors);
	} else {
		initEditors();
	}
}(jQuery));
