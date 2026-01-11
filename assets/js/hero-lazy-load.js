/**
 * Hero Lazy Load for 84EM Theme
 *
 * Defers hero background image loading until user interaction.
 * Works with critical CSS that hides the hero background initially.
 * Loads the actual image on scroll, click, mousemove, touchstart, or keydown.
 * Supports multiple heroes per page via data attributes.
 *
 * @package EightyFourEM
 */
(function() {
	'use strict';

	var loaded = false;
	var heroes = [];

	/**
	 * Initialize - find all hero elements with data attributes
	 */
	function init() {
		var elements = document.querySelectorAll('[data-lazy-hero="true"][data-hero-bg]');
		if (!elements.length) {
			return false;
		}

		elements.forEach(function(hero) {
			var bgUrl = hero.getAttribute('data-hero-bg');
			if (bgUrl) {
				heroes.push({
					element: hero,
					url: bgUrl
				});
			}
		});

		return heroes.length > 0;
	}

	/**
	 * Find the element with the actual background-image style
	 * Could be the hero itself or a nested child
	 */
	function findBgElement(hero, url) {
		// Check if hero itself has the background
		var style = hero.getAttribute('style') || '';
		if (style.indexOf('background-image') !== -1) {
			return hero;
		}

		// Check nested groups for background
		var nested = hero.querySelectorAll('.wp-block-group[style*="background-image"]');
		if (nested.length) {
			return nested[0];
		}

		// Fallback to hero itself
		return hero;
	}

	/**
	 * Preload and apply the background image with fade transition
	 */
	function loadHeroImages() {
		if (loaded) {
			return;
		}
		loaded = true;

		// Remove event listeners immediately
		removeListeners();

		heroes.forEach(function(heroData) {
			var hero = heroData.element;
			var imageUrl = heroData.url;
			var bgElement = findBgElement(hero, imageUrl);

			// Preload the image
			var img = new Image();
			img.onload = function() {
				// Add loaded class for transition
				bgElement.classList.add('hero-bg-loaded');

				// Apply the background image with !important to override critical CSS
				bgElement.style.setProperty('background-image', 'url(' + imageUrl + ')', 'important');

				// Clean up data attributes
				hero.removeAttribute('data-hero-bg');
				hero.removeAttribute('data-lazy-hero');
			};
			img.src = imageUrl;
		});
	}

	/**
	 * Remove all interaction listeners
	 */
	function removeListeners() {
		var events = ['scroll', 'click', 'mousemove', 'touchstart', 'keydown'];
		events.forEach(function(event) {
			window.removeEventListener(event, loadHeroImages, { passive: true });
		});
	}

	/**
	 * Add interaction listeners
	 */
	function addListeners() {
		var events = ['scroll', 'click', 'mousemove', 'touchstart', 'keydown'];
		events.forEach(function(event) {
			window.addEventListener(event, loadHeroImages, { passive: true });
		});
	}

	// Initialize on DOMContentLoaded
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function() {
			if (init()) {
				addListeners();
			}
		});
	} else {
		if (init()) {
			addListeners();
		}
	}
})();
