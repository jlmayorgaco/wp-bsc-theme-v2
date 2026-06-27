(function () {
	'use strict';

	function setRowState(checkbox) {
		var row = checkbox.closest('[data-bsc-home-product-row]');
		if (!row) {
			return;
		}

		row.classList.toggle('is-selected', checkbox.checked);
	}

	function createChip(checkbox, onRemove) {
		var chip = document.createElement('span');
		var label = checkbox.getAttribute('data-label') || checkbox.value;
		var sku = checkbox.getAttribute('data-sku') || '';
		var text = sku ? label + ' / ' + sku : label;
		chip.className = 'bsc-home-product-selector__chip';

		var chipText = document.createElement('span');
		chipText.textContent = text;
		chip.appendChild(chipText);

		var remove = document.createElement('button');
		remove.type = 'button';
		remove.className = 'bsc-home-product-selector__chip-remove';
		remove.setAttribute('aria-label', 'Quitar ' + label);
		remove.textContent = 'x';
		remove.addEventListener('click', function () {
			checkbox.checked = false;
			setRowState(checkbox);
			onRemove();
		});
		chip.appendChild(remove);

		return chip;
	}

	function normalizeSearchText(value) {
		return String(value || '')
			.toLowerCase()
			.normalize('NFD')
			.replace(/[\u0300-\u036f]/g, '')
			.replace(/[^\w-]+/g, ' ')
			.replace(/\s+/g, ' ')
			.trim();
	}

	function getRowSearchText(row) {
		var checkbox = row.querySelector('input[type="checkbox"]');
		var parts = [
			row.getAttribute('data-search') || '',
			checkbox ? checkbox.getAttribute('data-label') : '',
			checkbox ? checkbox.getAttribute('data-sku') : '',
			checkbox ? checkbox.value : ''
		];

		return normalizeSearchText(parts.join(' '));
	}

	function initSelector(selector) {
		var input = selector.querySelector('[data-bsc-home-product-selected-input]');
		var chips = selector.querySelector('[data-bsc-home-product-chips]');
		var count = selector.querySelector('[data-bsc-home-product-count]');
		var visibleCount = selector.querySelector('[data-bsc-home-product-visible-count]');
		var clearButton = selector.querySelector('[data-bsc-home-product-clear]');
		var search = selector.querySelector('[data-bsc-home-product-search]');
		var list = selector.querySelector('[data-bsc-home-product-list]');
		var rows = Array.prototype.slice.call(selector.querySelectorAll('[data-bsc-home-product-row]'));
		var checkboxes = rows.map(function (row) {
			return row.querySelector('input[type="checkbox"]');
		}).filter(Boolean);
		var noResults = null;

		if (list && rows.length) {
			noResults = document.createElement('p');
			noResults.className = 'bsc-home-product-selector__empty bsc-home-product-selector__empty--filter';
			noResults.textContent = 'No hay productos que coincidan con la busqueda.';
			noResults.hidden = true;
			list.appendChild(noResults);
		}

		function selectedCheckboxes() {
			return checkboxes.filter(function (checkbox) {
				return checkbox.checked;
			});
		}

		function updateSelected() {
			var selected = selectedCheckboxes();
			input.value = selected.map(function (checkbox) {
				return checkbox.value;
			}).join(',');

			if (count) {
				count.textContent = selected.length + (selected.length === 1 ? ' seleccionado' : ' seleccionados');
			}

			if (!chips) {
				return;
			}

			chips.innerHTML = '';
			if (!selected.length) {
				var empty = document.createElement('span');
				empty.className = 'bsc-home-product-selector__chip bsc-home-product-selector__chip--empty';
				empty.textContent = 'Ninguno seleccionado';
				chips.appendChild(empty);
				return;
			}

			selected.forEach(function (checkbox) {
				chips.appendChild(createChip(checkbox, updateSelected));
			});
		}

		function filterRows() {
			var query = normalizeSearchText(search ? search.value : '');
			var terms = query === '' ? [] : query.split(' ');
			var matchedRows = 0;

			rows.forEach(function (row) {
				var text = row.__bscHomeProductSearchText || '';
				var isVisible = !terms.length || terms.every(function (term) {
					return text.indexOf(term) !== -1;
				});

				row.classList.toggle('is-hidden', !isVisible);
				row.hidden = !isVisible;

				if (isVisible) {
					matchedRows += 1;
				}
			});

			if (noResults) {
				noResults.hidden = query === '' || matchedRows > 0;
			}

			if (visibleCount) {
				if (query === '') {
					visibleCount.textContent = rows.length + (rows.length === 1 ? ' producto disponible' : ' productos disponibles');
				} else {
					visibleCount.textContent = matchedRows + ' de ' + rows.length + (matchedRows === 1 ? ' coincidencia' : ' coincidencias');
				}
			}
		}

		rows.forEach(function (row) {
			row.__bscHomeProductSearchText = getRowSearchText(row);
		});

		checkboxes.forEach(function (checkbox) {
			setRowState(checkbox);
			checkbox.addEventListener('change', function () {
				setRowState(checkbox);
				updateSelected();
			});
		});

		if (search) {
			search.setAttribute('autocomplete', 'off');
			search.addEventListener('input', filterRows);
			search.addEventListener('keyup', filterRows);
			search.addEventListener('search', filterRows);
			search.addEventListener('change', filterRows);
			search.addEventListener('keydown', function (event) {
				if (event.key === 'Enter') {
					event.preventDefault();
					filterRows();
				}

				if (event.key === 'Escape') {
					search.value = '';
					filterRows();
				}
			});
		}

		if (clearButton) {
			clearButton.addEventListener('click', function () {
				checkboxes.forEach(function (checkbox) {
					checkbox.checked = false;
					setRowState(checkbox);
				});
				updateSelected();
				filterRows();
			});
		}

		updateSelected();
		filterRows();
	}

	function initTabGroup(tabGroup) {
		var tabs = Array.prototype.slice.call(tabGroup.querySelectorAll('[data-bsc-home-product-tab]'));
		var panels = Array.prototype.slice.call(tabGroup.querySelectorAll('[data-bsc-home-product-panel]'));

		if (!tabs.length || !panels.length) {
			return;
		}

		function activateTab(tab, shouldFocus) {
			var key = tab.getAttribute('data-bsc-home-product-tab');

			tabs.forEach(function (item) {
				var isActive = item === tab;
				item.classList.toggle('is-active', isActive);
				item.setAttribute('aria-selected', isActive ? 'true' : 'false');
				item.setAttribute('tabindex', isActive ? '0' : '-1');
			});

			panels.forEach(function (panel) {
				var isActive = panel.getAttribute('data-bsc-home-product-panel') === key;
				panel.classList.toggle('is-active', isActive);
				panel.hidden = !isActive;
			});

			if (shouldFocus) {
				tab.focus();
			}
		}

		tabs.forEach(function (tab, index) {
			tab.addEventListener('click', function () {
				activateTab(tab, false);
			});

			tab.addEventListener('keydown', function (event) {
				var nextIndex = index;

				if (event.key === 'ArrowRight') {
					nextIndex = (index + 1) % tabs.length;
				} else if (event.key === 'ArrowLeft') {
					nextIndex = (index - 1 + tabs.length) % tabs.length;
				} else if (event.key === 'Home') {
					nextIndex = 0;
				} else if (event.key === 'End') {
					nextIndex = tabs.length - 1;
				} else {
					return;
				}

				event.preventDefault();
				activateTab(tabs[nextIndex], true);
			});
		});

		activateTab(tabs.find(function (tab) {
			return tab.classList.contains('is-active');
		}) || tabs[0], false);
	}

	function initSelectors() {
		Array.prototype.slice.call(document.querySelectorAll('[data-bsc-home-product-selector]')).forEach(initSelector);
		Array.prototype.slice.call(document.querySelectorAll('[data-bsc-home-product-tabs]')).forEach(initTabGroup);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initSelectors);
	} else {
		initSelectors();
	}
}());
