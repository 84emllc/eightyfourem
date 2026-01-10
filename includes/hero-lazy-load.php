<?php
/**
 * Hero Lazy Load
 * Defers hero background image loading until user interaction
 *
 * @package EightyFourEM
 */

namespace EightyFourEM\HeroLazyLoad;

defined( 'ABSPATH' ) || exit;

/**
 * Output critical inline CSS and JS in head to immediately replace hero background
 * This runs before the hero image can start loading, preventing the initial request
 * Only applies to front page
 */
\add_action(
	hook_name: 'wp_head',
	callback: function (): void {
		// Only process on front page
		if ( ! \is_front_page() ) {
			return;
		}

		$hero_image   = '378267091-huge.jpg';
		$gradient     = 'linear-gradient(185deg, rgb(17, 17, 17) 0%, rgb(51, 51, 51) 100%)';
		?>
		<style id="hero-lazy-load-critical">
			/* Hide hero background image initially, show gradient */
			.wp-block-group[style*="<?php echo \esc_attr( $hero_image ); ?>"] {
				background-image: <?php echo $gradient; ?> !important;
			}
			.hero-bg-loaded {
				transition: opacity 0.5s ease-in-out;
			}
		</style>
		<script id="hero-lazy-load-critical-js">
		(function() {
			var heroImage = '<?php echo \esc_js( $hero_image ); ?>';
			var gradient = '<?php echo \esc_js( $gradient ); ?>';

			// Store original URL and apply gradient immediately when DOM is ready
			document.addEventListener('DOMContentLoaded', function() {
				var hero = document.querySelector('[style*="' + heroImage + '"]');
				if (!hero) return;

				// Extract and store original background image URL
				var style = hero.getAttribute('style');
				var match = style.match(/background-image\s*:\s*url\(['"]?([^'")]+)['"]?\)/i);
				if (match) {
					hero.setAttribute('data-hero-bg', match[1]);
					hero.classList.add('hero-lazy-load');
					// Apply gradient (CSS should already be handling this via !important)
				}
			});
		})();
		</script>
		<?php
	},
	priority: 1
);
