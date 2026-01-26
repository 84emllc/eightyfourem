<?php
/**
 * Hero Lazy Load
 * Defers hero background image loading until user interaction
 * Applies site-wide to any block with metadata name containing "hero"
 *
 * @package EightyFourEM
 */

namespace EightyFourEM\HeroLazyLoad;

defined( 'ABSPATH' ) || exit;

/**
 * Extract background image URL from block style attribute
 *
 * @param string $html Block HTML content.
 * @return string|null Background image URL or null if not found.
 */
function extract_background_url( string $html ): ?string {
	// Match background-image: url(...) in style attribute
	// Captures content inside url(), then strips any surrounding quotes
	if ( preg_match( '/background-image\s*:\s*url\(([^)]+)\)/i', $html, $matches ) ) {
		$url = $matches[1];
		// Decode HTML entities first (WordPress may encode quotes as &apos; or &#039;)
		$url = html_entity_decode( $url, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		// Strip surrounding quotes
		return trim( $url, "\"' \t\n\r" );
	}
	return null;
}

/**
 * Check if block is a hero block based on metadata name
 *
 * @param array $block Block data.
 * @return bool True if block is a hero.
 */
function is_hero_block( array $block ): bool {
	if ( ! isset( $block['attrs']['metadata']['name'] ) ) {
		return false;
	}

	$name = strtolower( $block['attrs']['metadata']['name'] );

	// Match any block name containing "hero" (case-insensitive)
	return str_contains( $name, 'hero' );
}

/**
 * Filter group block rendering to add lazy load data attributes to heroes
 */
\add_filter(
	hook_name: 'render_block_core/group',
	callback: function ( string $block_content, array $block ): string {
		// Check if this is a hero block
		if ( ! is_hero_block( $block ) ) {
			return $block_content;
		}

		// Use centralized hero background URL for all hero blocks site-wide
		$bg_url = \apply_filters(
			'eightyfourem_hero_background_url',
			'https://84em.com/wp-content/uploads/2025/10/84em-desktop-background-scaled.jpg'
		);

		// Add data attributes to the first wp-block-group element
		$block_content = preg_replace(
			'/(<div[^>]*class="[^"]*wp-block-group[^"]*"[^>]*)>/i',
			'$1 data-lazy-hero="true" data-hero-bg="' . \esc_attr( $bg_url ) . '">',
			$block_content,
			1
		);

		return $block_content;
	},
	priority: 10,
	accepted_args: 2
);

/**
 * Output critical inline CSS in head to immediately replace hero backgrounds
 * This runs before hero images can start loading, preventing the initial request
 */
\add_action(
	hook_name: 'wp_head',
	callback: function (): void {
		$gradient = 'linear-gradient(185deg, rgb(17, 17, 17) 0%, rgb(51, 51, 51) 100%)';
		?>
		<style id="hero-lazy-load-critical">
			/* Hide hero background image initially, show gradient */
			[data-lazy-hero="true"] {
				background-image: <?php echo $gradient; ?> !important;
			}
			[data-lazy-hero="true"] .wp-block-group[style*="background-image"] {
				background-image: <?php echo $gradient; ?> !important;
			}
			.hero-bg-loaded {
				transition: opacity 0.5s ease-in-out;
			}
		</style>
		<?php
	},
	priority: 1
);
