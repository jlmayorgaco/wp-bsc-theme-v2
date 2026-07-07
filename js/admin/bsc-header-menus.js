(function ($) {
	'use strict';

	var config = window.bscHeaderMenus || {};
	var categorySearchCache = {};

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

	function searchCategoryChoices(input) {
		var wrapper = input.closest('.bsc-header-menu-item__category');
		var select = wrapper ? wrapper.querySelector('[data-bsc-header-menu-category-select]') : null;
		var query = input.value.trim();
		var selectedValue = select ? select.value : '';
		var cacheKey = query.toLowerCase() + '|' + selectedValue;

		if (!select) {
			return;
		}

		if (query.length < getCategorySearchMinLength()) {
			select.classList.remove('is-filtered-empty', 'is-loading');
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
		}
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

			if (target instanceof HTMLElement && target.matches('[data-bsc-header-menu-category-search]')) {
				scheduleCategorySearch(target);
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
