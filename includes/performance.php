<?php
/**
 * Performance Optimizations
 * Handles critical resource preloading and performance enhancements
 *
 * @package EightyFourEM
 */

namespace EightyFourEM\Performance;

defined( 'ABSPATH' ) || exit;

/**
 * Preload critical fonts
 * Loads fonts as early as possible to prevent FOUT/FOIT
 * Uses highest priority to execute before other wp_head actions
 * fetchpriority="high" ensures fonts load before other resources
 */
\add_action(
	hook_name: 'wp_head',
	callback: function () {
		$theme_uri = \get_template_directory_uri();

		// Preload Instrument Sans (body font) - regular weight
		echo sprintf(
			'<link rel="preload" href="%s/assets/fonts/instrument-sans/InstrumentSans-VariableFont_wdth,wght.woff2" as="font" type="font/woff2" crossorigin="anonymous" fetchpriority="high">',
			\esc_url( $theme_uri )
		) . PHP_EOL;

		// Preload Instrument Sans (body font) - italic weight
		echo sprintf(
			'<link rel="preload" href="%s/assets/fonts/instrument-sans/InstrumentSans-Italic-VariableFont_wdth,wght.woff2" as="font" type="font/woff2" crossorigin="anonymous" fetchpriority="high">',
			\esc_url( $theme_uri )
		) . PHP_EOL;

		// Preload Jost (heading font) - regular weight
		echo sprintf(
			'<link rel="preload" href="%s/assets/fonts/jost/Jost-VariableFont_wght.woff2" as="font" type="font/woff2" crossorigin="anonymous" fetchpriority="high">',
			\esc_url( $theme_uri )
		) . PHP_EOL;
	},
	priority: 1
);

/**
 * Add DNS prefetch for external resources
 * Helps browser resolve DNS earlier for third-party domains
 * (Hook registration will be added here if/when external resources are used.)
 */

/**
 * Add resource hints for font preconnect
 * Establishes early connection to font origins
 */
/**
 * Inline critical font-face declarations
 * Embeds font declarations directly in HTML to avoid render-blocking CSS
 * This ensures fonts start loading immediately without waiting for CSS parsing
 */
\add_action(
	hook_name: 'wp_head',
	callback: function () {
		$theme_uri = \get_template_directory_uri();
		?>
		<style id="critical-fonts">
			/* Critical font-face declarations for immediate loading */
			@font-face {
				font-family: 'Instrument Sans';
				font-style: normal;
				font-weight: 400 700;
				font-display: swap;
				src: url('<?php echo \esc_url( $theme_uri ); ?>/assets/fonts/instrument-sans/InstrumentSans-VariableFont_wdth,wght.woff2') format('woff2');
			}

			@font-face {
				font-family: 'Instrument Sans';
				font-style: italic;
				font-weight: 400 700;
				font-display: swap;
				src: url('<?php echo \esc_url( $theme_uri ); ?>/assets/fonts/instrument-sans/InstrumentSans-Italic-VariableFont_wdth,wght.woff2') format('woff2');
			}

			@font-face {
				font-family: 'Jost';
				font-style: normal;
				font-weight: 100 900;
				font-display: swap;
				src: url('<?php echo \esc_url( $theme_uri ); ?>/assets/fonts/jost/Jost-VariableFont_wght.woff2') format('woff2');
			}
		</style>
		<?php
	},
	priority: 2
);

/**
 * Inline critical client logo gallery styles
 * Prevents FOUC where logos appear in color before grayscale filter is applied
 * Must load before images start rendering to avoid visual flash
 */
\add_action(
	hook_name: 'wp_head',
	callback: function () {
		?>
		<style id="critical-client-logos">
			/* Critical client logo styles to prevent FOUC */
			.client-logo-gallery .wp-block-image {
				display: flex;
				align-items: center;
				justify-content: center;
				aspect-ratio: 1 / 1;
				padding: 0.75rem;
			}
			.client-logo-gallery .wp-block-image img {
				max-width: 100%;
				max-height: 100%;
				width: auto;
				height: auto;
				object-fit: contain;
				filter: grayscale(100%) brightness(.88) contrast(1.5);
			}
		</style>
		<?php
	},
	priority: 3
);

/**
 * Exclude utilities.css from FlyingPress unused CSS deferral
 * FlyingPress converts href to data-href and loads CSS on user interaction
 * This causes FOUC for client logos (grayscale filter applied on mouse move)
 * Restore href immediately to load utilities.css synchronously
 */
\add_filter(
	hook_name: 'flying_press_optimization:after',
	callback: function ( string $html ): string {
		// Match utilities CSS link tag with data-href (deferred by FlyingPress)
		$pattern = '/<link[^>]*id=[\'"]eightyfourem-utilities-css[\'"][^>]*data-href=[\'"]([^\'"]+)[\'"][^>]*>/i';

		if ( preg_match( $pattern, $html, $matches ) ) {
			$deferred_tag = $matches[0];
			$href_value   = $matches[1];

			// Restore href and remove data-href to load synchronously
			$restored_tag = preg_replace( '/\sdata-href=/', ' href=', $deferred_tag );
			$html         = str_replace( $deferred_tag, $restored_tag, $html );
		}

		return $html;
	}
);

/**
 * Convert async-loaded stylesheets from print to all media
 * Stylesheets loaded with media="print" are non-blocking but need to be
 * switched to media="all" after page load to apply to screen
 * Handles: eightyfourem-modal-search, eightyfourem-highlighter
 */
\add_action(
	hook_name: 'wp_footer',
	callback: function () {
		?>
		<script id="async-css-loader">
		(function() {
			var asyncStyles = [
				'eightyfourem-modal-search-css',
				'eightyfourem-highlighter-css'
			];
			function loadAsyncCSS() {
				asyncStyles.forEach(function(id) {
					var link = document.getElementById(id);
					if (link && link.media === 'print') {
						link.media = 'all';
					}
				});
			}
			if (document.readyState === 'complete') {
				loadAsyncCSS();
			} else {
				window.addEventListener('load', loadAsyncCSS);
			}
		})();
		</script>
		<?php
	},
	priority: 99
);
