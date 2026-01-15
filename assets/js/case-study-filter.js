/**
 * Case Study Filter
 * Client-side filtering for case study pages
 *
 * @package EightyFourEM
 */

(function () {
	'use strict';

	// Get filter keywords from localized data (set in PHP)
	const filters = window.caseStudyFilters || {};

	document.addEventListener('DOMContentLoaded', function () {
		const filterButtons = document.querySelectorAll('[data-filter]');
		const caseStudyItems = document.querySelectorAll('.wp-block-post');
		const resultCounter = document.querySelector('.case-study-result-count');
		const totalItems = caseStudyItems.length;

		// Track if animations have been initialized (only init after first filter click)
		let animationsInitialized = false;

		if (!filterButtons.length || !caseStudyItems.length) return;

		// Function to update result counter
		function updateCounter(visibleCount) {
			if (resultCounter) {
				if (visibleCount === totalItems) {
					resultCounter.textContent = 'Showing all ' + totalItems + ' case studies';
				} else {
					resultCounter.textContent = 'Showing ' + visibleCount + ' of ' + totalItems + ' case studies';
				}
				resultCounter.classList.add('is-visible');
			}
		}

		// Function to apply filter
		function applyFilter(filter, shouldScroll) {
			let visibleCount = 0;

			// Default to scrolling if not specified
			if (shouldScroll === undefined) {
				shouldScroll = true;
			}

			// Update active button state
			filterButtons.forEach(function (btn) {
				if (btn.dataset.filter === filter) {
					btn.classList.add('is-active');
				} else {
					btn.classList.remove('is-active');
				}
			});

			// Add brief loading state
			if (resultCounter) {
				resultCounter.classList.add('is-filtering');
			}

			// Small delay to show filtering state
			setTimeout(function () {
				// Filter case study items
				caseStudyItems.forEach(function (item) {
					const title =
						item.querySelector('.wp-block-post-title')?.textContent.toLowerCase() || '';
					const excerpt =
						item.querySelector('.wp-block-post-excerpt')?.textContent.toLowerCase() || '';
					const searchText = title + ' ' + excerpt;

					if (filter === 'all') {
						// Show all items
						item.style.display = '';
						item.classList.remove('filtered-out');
						visibleCount++;
					} else {
						// Check if any keyword matches
						const keywords = filters[filter] || [];
						const matches = keywords.some(function (keyword) {
							return searchText.includes(keyword);
						});

						if (matches) {
							item.style.display = '';
							item.classList.remove('filtered-out');
							visibleCount++;
						} else {
							item.style.display = 'none';
							item.classList.add('filtered-out');
						}
					}
				});

				// Update counter
				updateCounter(visibleCount);

				// Remove filtering state
				if (resultCounter) {
					resultCounter.classList.remove('is-filtering');
				}

				// Scroll to first visible case study after filtering completes (only if shouldScroll is true)
				if (shouldScroll) {
					const firstVisibleCaseStudy = Array.from(caseStudyItems).find(function(item) {
						return item.style.display !== 'none';
					});

					if (firstVisibleCaseStudy) {
						// Wait for DOM to update, then scroll
						setTimeout(function() {
							const header = document.querySelector('header');
							const headerHeight = header ? header.offsetHeight : 0;
							const filtersContainer = document.querySelector('.case-study-filters');
							const filtersHeight = filtersContainer ? filtersContainer.offsetHeight : 0;
							const offset = headerHeight + filtersHeight + 20; // 20px padding

							const targetPosition = firstVisibleCaseStudy.getBoundingClientRect().top + window.pageYOffset - offset;

							window.scrollTo({
								top: targetPosition,
								behavior: 'smooth'
							});
						}, 100);
					}
				}
			}, 50);
		}

		// Add click handlers to filter buttons
		filterButtons.forEach(function (button) {
			button.addEventListener('click', function (e) {
				e.preventDefault();

				// Initialize animations on first filter click (not on page load)
				if (!animationsInitialized) {
					initLazyLoadAnimations(caseStudyItems);
					animationsInitialized = true;
				}

				const filter = this.dataset.filter;

				// Update URL hash for shareability
				if (filter === 'all') {
					// Remove hash for "all"
					history.replaceState(null, null, window.location.pathname);
				} else {
					history.replaceState(null, null, '#filter=' + filter);
				}

				// Apply the filter
				applyFilter(filter);
			});
		});

		// Check URL hash on page load
		function checkUrlHash() {
			const hash = window.location.hash;
			if (hash && hash.startsWith('#filter=')) {
				const filter = hash.replace('#filter=', '');
				// Verify the filter exists
				const filterExists = Array.from(filterButtons).some(function (btn) {
					return btn.dataset.filter === filter;
				});
				if (filterExists) {
					applyFilter(filter, false); // Don't scroll on page load
					return;
				}
			}
			// Default: show all
			applyFilter('all', false); // Don't scroll on page load
		}

		// Initialize
		checkUrlHash();
	});

	/**
	 * Initialize lazy load fade-in animations for case study grid items
	 * Uses Intersection Observer to trigger animations when items enter viewport
	 */
	function initLazyLoadAnimations(items) {
		// Check for reduced motion preference
		var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

		// If user prefers reduced motion, show all items immediately
		if (prefersReducedMotion) {
			items.forEach(function (item) {
				item.classList.add('lazy-visible');
			});
			return;
		}

		// Check if Intersection Observer is supported
		if (!('IntersectionObserver' in window)) {
			// Fallback: show all items immediately
			items.forEach(function (item) {
				item.classList.add('lazy-visible');
			});
			return;
		}

		// Add lazy-animate class to all items
		items.forEach(function (item) {
			item.classList.add('lazy-animate');
		});

		// Create observer with 15% threshold
		var observer = new IntersectionObserver(
			function (entries) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting) {
						entry.target.classList.add('lazy-visible');
						observer.unobserve(entry.target);
					}
				});
			},
			{
				threshold: 0.15,
				rootMargin: '0px 0px -30px 0px',
			}
		);

		// Observe each item
		items.forEach(function (item) {
			var rect = item.getBoundingClientRect();
			var windowHeight = window.innerHeight || document.documentElement.clientHeight;

			if (rect.top < windowHeight && rect.bottom > 0) {
				// Element is already in viewport, animate immediately
				item.classList.add('lazy-visible');
			} else {
				// Element is below viewport, observe it
				observer.observe(item);
			}
		});
	}
})();
