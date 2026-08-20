(function () {
	'use strict';

	if (typeof tkpeAdmin === 'undefined') {
		return;
	}

	const state = {
		products: [],
		currentPage: 1,
		productsPerPage: 20,
		totalProducts: 0,
		totalPages: 0,
		search: '',
		category: '',
		type: '',
		stockStatus: '',
		status: '',
		requestController: null,
		searchTimer: null,
	};

	const elements = {
		search: document.getElementById('tkpe-search'),
		category: document.getElementById('tkpe-category'),
		type: document.getElementById('tkpe-type'),
		stockStatus: document.getElementById('tkpe-stock-status'),
		status: document.getElementById('tkpe-status'),
		productsPerPage: document.getElementById('tkpe-per-page'),
		selectAll: document.getElementById('tkpe-select-all'),
		productRows: document.getElementById('tkpe-product-rows'),
		pagination: document.getElementById('tkpe-pagination'),
		loading: document.getElementById('tkpe-loading'),
		emptyState: document.getElementById('tkpe-empty-state'),
		resultsSummary: document.getElementById('tkpe-results-summary'),
	};

	/**
	 * Initialize the product editor.
	 */
	function init() {
		bindEvents();
		loadProducts();
	}

	/**
	 * Bind UI events.
	 */
	function bindEvents() {
		elements.search.addEventListener('input', handleSearchInput);

		elements.category.addEventListener('change', handleFilterChange);
		elements.type.addEventListener('change', handleFilterChange);
		elements.stockStatus.addEventListener('change', handleFilterChange);
		elements.status.addEventListener('change', handleFilterChange);
		elements.productsPerPage.addEventListener('change', handlePerPageChange);

		elements.selectAll.addEventListener('change', handleSelectAllChange);

		elements.pagination.addEventListener('click', handlePaginationClick);
	}

	/**
	 * Handle search input with debounce.
	 */
	function handleSearchInput() {
		window.clearTimeout(state.searchTimer);

		state.searchTimer = window.setTimeout(function () {
			state.search = elements.search.value.trim();
			state.currentPage = 1;

			loadProducts();
		}, 350);
	}

	/**
	 * Handle regular filter changes.
	 */
	function handleFilterChange() {
		state.search = elements.search.value.trim();
		state.category = elements.category.value;
		state.type = elements.type.value;
		state.stockStatus = elements.stockStatus.value;
		state.status = elements.status.value;
		state.currentPage = 1;

		loadProducts();
	}

	/**
	 * Handle products-per-page change.
	 */
	function handlePerPageChange() {
		const selectedValue = parseInt(elements.productsPerPage.value, 10);

		if ([20, 50, 100].indexOf(selectedValue) === -1) {
			state.productsPerPage = 20;
		} else {
			state.productsPerPage = selectedValue;
		}

		state.currentPage = 1;

		loadProducts();
	}

	/**
	 * Build the REST request URL.
	 *
	 * @returns {string} REST request URL.
	 */
	function buildRequestUrl() {
		const url = new URL(tkpeAdmin.restUrl);

		url.searchParams.set('page', String(state.currentPage));
		url.searchParams.set('per_page', String(state.productsPerPage));

		if (state.search !== '') {
			url.searchParams.set('search', state.search);
		}

		if (state.category !== '') {
			url.searchParams.set('category', state.category);
		}

		if (state.type !== '') {
			url.searchParams.set('type', state.type);
		}

		if (state.stockStatus !== '') {
			url.searchParams.set('stock_status', state.stockStatus);
		}

		if (state.status !== '') {
			url.searchParams.set('status', state.status);
		}

		return url.toString();
	}

	/**
	 * Retrieve products from the REST API.
	 */
	async function loadProducts() {
		if (state.requestController) {
			state.requestController.abort();
		}

		state.requestController = new AbortController();

		setLoading(true);
		hideEmptyState();

		try {
			const response = await fetch(buildRequestUrl(), {
				method: 'GET',
				credentials: 'same-origin',
				headers: {
					'X-WP-Nonce': tkpeAdmin.nonce,
					'Accept': 'application/json',
				},
				signal: state.requestController.signal,
			});

			if (!response.ok) {
				throw new Error('REST request failed.');
			}

			const data = await response.json();

			if (
				!data ||
				!Array.isArray(data.products) ||
				typeof data.total === 'undefined' ||
				typeof data.pages === 'undefined'
			) {
				throw new Error('Invalid REST response.');
			}

			replaceStateFromResponse(data);
			render();

		} catch (error) {
			if (error.name === 'AbortError') {
				return;
			}

			state.products = [];
			state.totalProducts = 0;
			state.totalPages = 0;

			renderError();
		} finally {
			setLoading(false);
		}
	}

	/**
	 * Replace the current page state.
	 *
	 * @param {Object} data REST response.
	 */
	function replaceStateFromResponse(data) {
		state.products = Array.isArray(data.products) ? data.products : [];
		state.currentPage = Number(data.page) || 1;
		state.productsPerPage = Number(data.per_page) || 20;
		state.totalProducts = Number(data.total) || 0;
		state.totalPages = Number(data.pages) || 0;
	}

	/**
	 * Render the current application state.
	 */
	function render() {
		renderRows();
		renderSummary();
		renderPagination();

		elements.selectAll.checked = false;
		elements.selectAll.indeterminate = false;

		if (state.products.length === 0) {
			showEmptyState();
		} else {
			hideEmptyState();
		}
	}

	/**
	 * Render product rows.
	 */
	function renderRows() {
		elements.productRows.replaceChildren();

		if (state.products.length === 0) {
			const row = document.createElement('tr');
			const cell = document.createElement('td');

			cell.colSpan = 6;
			cell.className = 'tkpe-table-message';
			cell.textContent = tkpeAdmin.i18n.noProducts;

			row.appendChild(cell);
			elements.productRows.appendChild(row);

			return;
		}

		state.products.forEach(function (product) {
			elements.productRows.appendChild(createProductRow(product));
		});
	}

	/**
	 * Create a product table row.
	 *
	 * @param {Object} product Product data.
	 * @returns {HTMLTableRowElement} Product row.
	 */
	function createProductRow(product) {
		const row = document.createElement('tr');

		/**
		 * Checkbox.
		 */
		const checkboxCell = document.createElement('th');
		checkboxCell.scope = 'row';
		checkboxCell.className = 'check-column';

		const checkbox = document.createElement('input');
		checkbox.type = 'checkbox';
		checkbox.className = 'tkpe-product-checkbox';
		checkbox.value = String(product.id);
		checkbox.setAttribute('aria-label', product.name);

		checkbox.addEventListener('change', updateSelectAllState);

		checkboxCell.appendChild(checkbox);
		row.appendChild(checkboxCell);

		/**
		 * Product.
		 */
		const productCell = document.createElement('td');
		productCell.className = 'tkpe-product-cell';

		const productWrapper = document.createElement('div');
		productWrapper.className = 'tkpe-product-info';

		if (product.image) {
			const image = document.createElement('img');

			image.src = product.image;
			image.alt = '';
			image.className = 'tkpe-product-image';
			image.loading = 'lazy';
			image.width = 48;
			image.height = 48;

			productWrapper.appendChild(image);
		} else {
			const imagePlaceholder = document.createElement('span');

			imagePlaceholder.className = 'tkpe-product-image tkpe-product-image-placeholder';
			imagePlaceholder.setAttribute('aria-hidden', 'true');

			productWrapper.appendChild(imagePlaceholder);
		}

		const productName = document.createElement('span');
		productName.className = 'tkpe-product-name';
		productName.textContent = product.name || '';

		productWrapper.appendChild(productName);
		productCell.appendChild(productWrapper);
		row.appendChild(productCell);

		/**
		 * Categories.
		 */
		const categoryCell = document.createElement('td');
		categoryCell.textContent = getCategoryText(product.categories);

		row.appendChild(categoryCell);

		/**
		 * Status.
		 */
		const statusCell = document.createElement('td');
		statusCell.appendChild(createStatusBadge(product.status));

		row.appendChild(statusCell);

		/**
		 * Stock.
		 */
		const stockCell = document.createElement('td');
		stockCell.appendChild(createStockBadge(product.stock_status));

		row.appendChild(stockCell);

		/**
		 * Price.
		 */
		const priceCell = document.createElement('td');
		priceCell.appendChild(createPriceContent(product));

		row.appendChild(priceCell);

		return row;
	}

	/**
	 * Create a status badge.
	 *
	 * @param {string} status Product status.
	 * @returns {HTMLSpanElement} Status badge.
	 */
	function createStatusBadge(status) {
		const badge = document.createElement('span');

		badge.className = 'tkpe-status-badge';
		badge.textContent = formatStatus(status);

		return badge;
	}

	/**
	 * Create a stock status badge.
	 *
	 * @param {string} status Stock status.
	 * @returns {HTMLSpanElement} Stock badge.
	 */
	function createStockBadge(status) {
		const badge = document.createElement('span');

		badge.className = 'tkpe-stock-badge tkpe-stock-' + sanitizeClassName(status);
		badge.textContent = formatStockStatus(status);

		return badge;
	}

	/**
	 * Create the price content.
	 *
	 * @param {Object} product Product data.
	 * @returns {HTMLDivElement} Price content.
	 */
	function createPriceContent(product) {
		const wrapper = document.createElement('div');
		wrapper.className = 'tkpe-price-info';

		const regular = document.createElement('div');
		const regularLabel = document.createElement('span');

		regularLabel.className = 'tkpe-price-label';
		regularLabel.textContent = tkpeAdmin.i18n.regular + ': ';

		regular.appendChild(regularLabel);
		regular.appendChild(
			document.createTextNode(
				product.regular_price !== '' ? product.regular_price : '—'
			)
		);

		wrapper.appendChild(regular);

		const sale = document.createElement('div');
		const saleLabel = document.createElement('span');

		saleLabel.className = 'tkpe-price-label';
		saleLabel.textContent = tkpeAdmin.i18n.sale + ': ';

		sale.appendChild(saleLabel);

		if (product.sale_price !== '') {
			sale.appendChild(document.createTextNode(product.sale_price));
		} else {
			const noSale = document.createElement('span');

			noSale.className = 'tkpe-no-sale';
			noSale.textContent = tkpeAdmin.i18n.noSalePrice;

			sale.appendChild(noSale);
		}

		wrapper.appendChild(sale);

		return wrapper;
	}

	/**
	 * Get category display text.
	 *
	 * @param {Array} categories Product categories.
	 * @returns {string} Category text.
	 */
	function getCategoryText(categories) {
		if (!Array.isArray(categories) || categories.length === 0) {
			return tkpeAdmin.i18n.uncategorized;
		}

		return categories.join(', ');
	}

	/**
	 * Format a product status.
	 *
	 * @param {string} status Product status.
	 * @returns {string} Human-readable status.
	 */
	function formatStatus(status) {
		const labels = {
			publish: 'Published',
			draft: 'Draft',
			pending: 'Pending',
			private: 'Private',
		};

		return labels[status] || status || '—';
	}

	/**
	 * Format a stock status.
	 *
	 * @param {string} status Stock status.
	 * @returns {string} Human-readable stock status.
	 */
	function formatStockStatus(status) {
		const labels = {
			instock: 'In stock',
			outofstock: 'Out of stock',
			onbackorder: 'On backorder',
		};

		return labels[status] || status || '—';
	}

	/**
	 * Keep class names predictable.
	 *
	 * @param {string} value Class value.
	 * @returns {string} Safe class value.
	 */
	function sanitizeClassName(value) {
		return String(value || '')
			.toLowerCase()
			.replace(/[^a-z0-9_-]/g, '');
	}

	/**
	 * Render results summary.
	 */
	function renderSummary() {
		if (state.totalProducts === 0) {
			elements.resultsSummary.textContent = '';
			return;
		}

		const firstProduct =
			(state.currentPage - 1) * state.productsPerPage + 1;

		const lastProduct = Math.min(
			state.currentPage * state.productsPerPage,
			state.totalProducts
		);

		elements.resultsSummary.textContent =
			firstProduct +
			'–' +
			lastProduct +
			' of ' +
			state.totalProducts +
			' ' +
			tkpeAdmin.i18n.products;
	}

	/**
	 * Render server-side pagination.
	 */
	function renderPagination() {
		elements.pagination.replaceChildren();

		if (state.totalPages <= 1) {
			return;
		}

		const fragment = document.createDocumentFragment();

		if (state.currentPage > 1) {
			fragment.appendChild(
				createPaginationButton(
					state.currentPage - 1,
					tkpeAdmin.i18n.previous
				)
			);
		}

		const pages = getPaginationPages(
			state.currentPage,
			state.totalPages
		);

		pages.forEach(function (page) {
			if (page === 'ellipsis') {
				const ellipsis = document.createElement('span');

				ellipsis.className = 'tkpe-pagination-ellipsis';
				ellipsis.textContent = '…';

				fragment.appendChild(ellipsis);

				return;
			}

			const button = createPaginationButton(
				page,
				String(page)
			);

			if (page === state.currentPage) {
				button.classList.add('is-current');
				button.setAttribute('aria-current', 'page');
			}

			fragment.appendChild(button);
		});

		if (state.currentPage < state.totalPages) {
			fragment.appendChild(
				createPaginationButton(
					state.currentPage + 1,
					tkpeAdmin.i18n.next
				)
			);
		}

		elements.pagination.appendChild(fragment);
	}

	/**
	 * Create a pagination button.
	 *
	 * @param {number} page Page number.
	 * @param {string} label Button label.
	 * @returns {HTMLButtonElement} Pagination button.
	 */
	function createPaginationButton(page, label) {
		const button = document.createElement('button');

		button.type = 'button';
		button.className = 'button tkpe-page-button';
		button.dataset.page = String(page);
		button.textContent = label;

		return button;
	}

	/**
	 * Create a compact pagination page list.
	 *
	 * @param {number} current Current page.
	 * @param {number} total Total pages.
	 * @returns {Array} Pagination pages.
	 */
	function getPaginationPages(current, total) {
		if (total <= 7) {
			return createNumberRange(1, total);
		}

		const pages = [1];

		if (current > 4) {
			pages.push('ellipsis');
		}

		const start = Math.max(2, current - 1);
		const end = Math.min(total - 1, current + 1);

		for (let page = start; page <= end; page++) {
			pages.push(page);
		}

		if (current < total - 3) {
			pages.push('ellipsis');
		}

		pages.push(total);

		return pages;
	}

	/**
	 * Create a number range.
	 *
	 * @param {number} start Start number.
	 * @param {number} end End number.
	 * @returns {Array<number>} Number range.
	 */
	function createNumberRange(start, end) {
		const numbers = [];

		for (let number = start; number <= end; number++) {
			numbers.push(number);
		}

		return numbers;
	}

	/**
	 * Handle pagination click.
	 *
	 * @param {MouseEvent} event Click event.
	 */
	function handlePaginationClick(event) {
		const button = event.target.closest('.tkpe-page-button');

		if (!button) {
			return;
		}

		const page = parseInt(button.dataset.page, 10);

		if (
			Number.isNaN(page) ||
			page < 1 ||
			page > state.totalPages ||
			page === state.currentPage
		) {
			return;
		}

		state.currentPage = page;

		loadProducts();
	}

	/**
	 * Handle select-all.
	 */
	function handleSelectAllChange() {
		const checkboxes = elements.productRows.querySelectorAll(
			'.tkpe-product-checkbox'
		);

		checkboxes.forEach(function (checkbox) {
			checkbox.checked = elements.selectAll.checked;
		});
	}

	/**
	 * Update select-all state.
	 */
	function updateSelectAllState() {
		const checkboxes = Array.from(
			elements.productRows.querySelectorAll(
				'.tkpe-product-checkbox'
			)
		);

		if (checkboxes.length === 0) {
			elements.selectAll.checked = false;
			elements.selectAll.indeterminate = false;
			return;
		}

		const checkedCount = checkboxes.filter(function (checkbox) {
			return checkbox.checked;
		}).length;

		elements.selectAll.checked = checkedCount === checkboxes.length;
		elements.selectAll.indeterminate =
			checkedCount > 0 && checkedCount < checkboxes.length;
	}

	/**
	 * Show loading state.
	 *
	 * @param {boolean} isLoading Loading state.
	 */
	function setLoading(isLoading) {
		elements.loading.hidden = !isLoading;

		if (isLoading) {
			elements.productRows.classList.add('is-loading');
		} else {
			elements.productRows.classList.remove('is-loading');
		}
	}

	/**
	 * Show the empty state.
	 */
	function showEmptyState() {
		elements.emptyState.hidden = false;
	}

	/**
	 * Hide the empty state.
	 */
	function hideEmptyState() {
		elements.emptyState.hidden = true;
	}

	/**
	 * Render an error state.
	 */
	function renderError() {
		elements.productRows.replaceChildren();

		const row = document.createElement('tr');
		const cell = document.createElement('td');

		cell.colSpan = 6;
		cell.className = 'tkpe-table-message tkpe-error-message';
		cell.textContent = tkpeAdmin.i18n.error;

		row.appendChild(cell);
		elements.productRows.appendChild(row);

		elements.resultsSummary.textContent = '';
		elements.pagination.replaceChildren();
		elements.selectAll.checked = false;
		elements.selectAll.indeterminate = false;
		hideEmptyState();
	}

	init();
})();