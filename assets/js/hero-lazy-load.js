/**
 * Hero Lazy Load for 84EM Theme
 *
 * Defers hero background image loading until user interaction.
 * Works with critical CSS that hides the hero background initially.
 * Loads the actual image on scroll, click, mousemove, touchstart, or keydown.
 *
 * @package EightyFourEM
 */
(function() {
	'use strict';

	var loaded = false;
	var hero = null;
	var imageUrl = null;

	/**
	 * Initialize - find hero element and get stored image URL
	 */
	function init() {
		hero = document.querySelector('.hero-lazy-load[data-hero-bg]');
		if (!hero) {
			// Fallback: try to find by style attribute
			hero = document.querySelector('[style*="378267091-huge.jpg"]');
			if (hero) {
				var style = hero.getAttribute('style');
				var match = style.match(/background-image\s*:\s*url\(['"]?([^'")]+)['"]?\)/i);
				if (match) {
					imageUrl = match[1];
					hero.setAttribute('data-hero-bg', imageUrl);
					hero.classList.add('hero-lazy-load');
				}
			}
		} else {
			imageUrl = hero.getAttribute('data-hero-bg');
		}

		if (!hero || !imageUrl) {
			return false;
		}
		return true;
	}

	/**
	 * Preload and apply the background image with fade transition
	 */
	function loadHeroImage() {
		if (loaded) {
			return;
		}
		loaded = true;

		// Remove event listeners immediately
		removeListeners();

		if (!hero || !imageUrl) {
			if (!init()) {
				return;
			}
		}

		// Preload the image
		var img = new Image();
		img.onload = function() {
			// Add loaded class for transition
			hero.classList.add('hero-bg-loaded');

			// Apply the background image with !important to override critical CSS
			hero.style.setProperty('background-image', 'url(' + imageUrl + ')', 'important');

			// Clean up data attribute
			hero.removeAttribute('data-hero-bg');
		};
		img.src = imageUrl;
	}

	/**
	 * Remove all interaction listeners
	 */
	function removeListeners() {
		var events = ['scroll', 'click', 'mousemove', 'touchstart', 'keydown'];
		events.forEach(function(event) {
			window.removeEventListener(event, loadHeroImage, { passive: true });
		});
	}

	// Initialize on DOMContentLoaded
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function() {
			if (init()) {
				// Add event listeners for user interaction
				var events = ['scroll', 'click', 'mousemove', 'touchstart', 'keydown'];
				events.forEach(function(event) {
					window.addEventListener(event, loadHeroImage, { passive: true });
				});
			}
		});
	} else {
		if (init()) {
			// Add event listeners for user interaction
			var events = ['scroll', 'click', 'mousemove', 'touchstart', 'keydown'];
			events.forEach(function(event) {
				window.addEventListener(event, loadHeroImage, { passive: true });
			});
		}
	}
})();
