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

	function initSelector(selector) {
		var input = selector.querySelector('[data-bsc-home-product-selected-input]');
		var chips = selector.querySelector('[data-bsc-home-product-chips]');
		var count = selector.querySelector('[data-bsc-home-product-count]');
		var clearButton = selector.querySelector('[data-bsc-home-product-clear]');
		var search = selector.querySelector('[data-bsc-home-product-search]');
		var rows = Array.prototype.slice.call(selector.querySelectorAll('[data-bsc-home-product-row]'));
		var checkboxes = rows.map(function (row) {
			return row.querySelector('input[type="checkbox"]');
		}).filter(Boolean);

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
			var query = search ? search.value.trim().toLowerCase() : '';
			rows.forEach(function (row) {
				var text = row.getAttribute('data-search') || '';
				row.hidden = query !== '' && text.indexOf(query) === -1;
			});
		}

		checkboxes.forEach(function (checkbox) {
			setRowState(checkbox);
			checkbox.addEventListener('change', function () {
				setRowState(checkbox);
				updateSelected();
			});
		});

		if (search) {
			search.addEventListener('input', filterRows);
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

	document.addEventListener('DOMContentLoaded', function () {
		Array.prototype.slice.call(document.querySelectorAll('[data-bsc-home-product-selector]')).forEach(initSelector);
	});
}());
