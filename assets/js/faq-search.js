/**
 * FAQ Search Filter with Highlighting
 * Filters FAQ accordions based on search input with WCAG 2.1 accessibility
 * Updated for WordPress 6.9+ core accordion block
 */

(function() {
	'use strict';

	let searchTimeout;
	const DEBOUNCE_DELAY = 300;

	/**
	 * Initialize FAQ search when DOM is ready
	 */
	function init() {
		const firstHeading = document.querySelector('h2.wp-block-heading');
		if (!firstHeading) {
			return;
		}

		injectSearchInterface(firstHeading);
		const searchInput = document.getElementById('faq-search-input');
		const clearButton = document.getElementById('faq-search-clear');

		if (searchInput) {
			searchInput.addEventListener('input', handleSearchInput);
			searchInput.addEventListener('keydown', handleKeydown);
		}

		if (clearButton) {
			clearButton.addEventListener('click', clearSearch);
		}
	}

	/**
	 * Inject search interface HTML before first H2 heading
	 */
	function injectSearchInterface(firstHeading) {
		const searchHTML = `
			<div class="faq-search-wrapper">
				<div class="faq-search-container">
					<label for="faq-search-input" class="faq-search-label">
						Search FAQs
					</label>
					<div class="faq-search-input-wrapper">
						<svg class="faq-search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" aria-hidden="true" focusable="false">
							<path d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"/>
						</svg>
						<input
							type="search"
							id="faq-search-input"
							class="faq-search-input"
							placeholder="Type to search questions and answers..."
							autocomplete="off"
							aria-describedby="faq-search-results-summary"
						/>
						<button
							type="button"
							id="faq-search-clear"
							class="faq-search-clear"
							aria-label="Clear search"
							hidden
						>
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" aria-hidden="true" focusable="false">
								<path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/>
							</svg>
						</button>
					</div>
				</div>
				<div
					id="faq-search-results-summary"
					class="faq-search-results-summary"
					role="status"
					aria-live="polite"
					aria-atomic="true"
				></div>
			</div>
		`;

		firstHeading.insertAdjacentHTML('beforebegin', searchHTML);
	}

	/**
	 * Handle search input with debouncing
	 */
	function handleSearchInput(e) {
		const clearButton = document.getElementById('faq-search-clear');
		const hasValue = e.target.value.trim().length > 0;

		if (clearButton) {
			clearButton.hidden = !hasValue;
		}

		clearTimeout(searchTimeout);
		searchTimeout = setTimeout(() => {
			filterFAQs(e.target.value.trim());
		}, DEBOUNCE_DELAY);
	}

	/**
	 * Handle keyboard shortcuts
	 */
	function handleKeydown(e) {
		if (e.key === 'Escape') {
			clearSearch();
		}
	}

	/**
	 * Clear search and show all FAQs
	 */
	function clearSearch() {
		const searchInput = document.getElementById('faq-search-input');
		const clearButton = document.getElementById('faq-search-clear');

		if (searchInput) {
			searchInput.value = '';
			searchInput.focus();
		}

		if (clearButton) {
			clearButton.hidden = true;
		}

		filterFAQs('');
	}

	/**
	 * Filter FAQ items based on search query
	 * Works with WordPress 6.9+ core accordion block
	 */
	function filterFAQs(query) {
		const faqItems = document.querySelectorAll('.wp-block-accordion-item');
		const summary = document.getElementById('faq-search-results-summary');

		if (!faqItems.length) {
			return;
		}

		const normalizedQuery = query.toLowerCase();
		let visibleCount = 0;
		const totalCount = faqItems.length;

		faqItems.forEach(item => {
			const questionTitle = item.querySelector('.wp-block-accordion-heading__toggle-title');
			const panel = item.querySelector('.wp-block-accordion-panel');

			if (!questionTitle) {
				return;
			}

			if (!query) {
				item.style.display = '';
				item.setAttribute('aria-hidden', 'false');
				visibleCount++;
				return;
			}

			// Get text content for searching
			const questionText = questionTitle.textContent.toLowerCase();
			const panelText = panel ? panel.textContent.toLowerCase() : '';

			// Check if query matches question or answer content
			const matches = questionText.includes(normalizedQuery) ||
			                panelText.includes(normalizedQuery);

			if (matches) {
				item.style.display = '';
				item.setAttribute('aria-hidden', 'false');
				visibleCount++;
			} else {
				item.style.display = 'none';
				item.setAttribute('aria-hidden', 'true');
			}
		});

		// Show/hide section headings and separators based on visible FAQs
		if (query) {
			hideEmptySections();
		} else {
			showAllSections();
		}

		updateResultsSummary(summary, query, visibleCount, totalCount);
	}

	/**
	 * Hide section headings and separators that have no visible FAQ items
	 */
	function hideEmptySections() {
		const headings = document.querySelectorAll('h2.wp-block-heading');
		const separators = document.querySelectorAll('.wp-block-uagb-separator');

		headings.forEach(heading => {
			let hasVisibleFAQs = false;
			let nextElement = heading.nextElementSibling;

			// Find the next accordion block (skip text nodes and empty elements)
			while (nextElement) {
				if (nextElement.classList && nextElement.classList.contains('wp-block-accordion')) {
					const faqItems = nextElement.querySelectorAll('.wp-block-accordion-item');
					hasVisibleFAQs = Array.from(faqItems).some(item =>
						item.style.display !== 'none' && item.getAttribute('aria-hidden') !== 'true'
					);
					break;
				}
				nextElement = nextElement.nextElementSibling;
			}

			if (hasVisibleFAQs) {
				heading.style.display = '';
			} else {
				heading.style.display = 'none';
			}
		});

		separators.forEach(separator => {
			let nextElement = separator.nextElementSibling;
			// Find the next H2 heading
			while (nextElement) {
				if (nextElement.tagName === 'H2') {
					separator.style.display = nextElement.style.display;
					break;
				}
				nextElement = nextElement.nextElementSibling;
			}
		});
	}

	/**
	 * Show all section headings and separators
	 */
	function showAllSections() {
		const headings = document.querySelectorAll('h2.wp-block-heading');
		const separators = document.querySelectorAll('.wp-block-uagb-separator');

		headings.forEach(heading => {
			heading.style.display = '';
		});

		separators.forEach(separator => {
			separator.style.display = '';
		});
	}

	/**
	 * Update results summary for screen readers
	 */
	function updateResultsSummary(summary, query, visibleCount, totalCount) {
		if (!summary) {
			return;
		}

		if (!query) {
			summary.textContent = '';
			return;
		}

		let message;

		if (visibleCount === 0) {
			message = `No results found for "${query}". Showing 0 of ${totalCount} FAQs.`;
		} else if (visibleCount === totalCount) {
			message = `Showing all ${totalCount} FAQs matching "${query}".`;
		} else {
			message = `Showing ${visibleCount} of ${totalCount} FAQs matching "${query}".`;
		}

		summary.textContent = message;
	}

	// Initialize when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
