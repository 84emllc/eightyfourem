/**
 * Scroll-Triggered Animations for 84EM Theme
 *
 * Uses Intersection Observer to trigger CSS animations when elements
 * scroll into the viewport. Supports prefers-reduced-motion.
 *
 * @package EightyFourEM
 */
(function() {
	'use strict';

	document.addEventListener('DOMContentLoaded', function() {
		var animatedElements = document.querySelectorAll('.animated');

		if (!animatedElements.length) {
			return;
		}

		// Check for reduced motion preference
		var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

		// If user prefers reduced motion, make all elements visible immediately
		if (prefersReducedMotion) {
			animatedElements.forEach(function(element) {
				element.classList.add('animate-visible');
			});
			return;
		}

		// Check if Intersection Observer is supported
		if (!('IntersectionObserver' in window)) {
			// Fallback: show all elements immediately
			animatedElements.forEach(function(element) {
				element.classList.add('animate-visible');
			});
			return;
		}

		// Create observer with 10% threshold (trigger when 10% visible)
		var observer = new IntersectionObserver(function(entries) {
			entries.forEach(function(entry) {
				if (entry.isIntersecting) {
					entry.target.classList.add('animate-visible');
					// Disconnect observer for this element (animation only runs once)
					observer.unobserve(entry.target);
				}
			});
		}, {
			threshold: 0.1,
			rootMargin: '0px 0px -50px 0px'
		});

		// Observe each animated element
		animatedElements.forEach(function(element) {
			// Check if element is already in viewport (above the fold)
			var rect = element.getBoundingClientRect();
			var windowHeight = window.innerHeight || document.documentElement.clientHeight;

			if (rect.top < windowHeight && rect.bottom > 0) {
				// Element is already visible, animate immediately
				element.classList.add('animate-visible');
			} else {
				// Element is below viewport, observe it
				observer.observe(element);
			}
		});
	});
})();
