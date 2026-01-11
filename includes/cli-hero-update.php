<?php
/**
 * WP-CLI commands for hero block styling updates.
 *
 * Updates hero blocks across pages to match the reference styling from pattern 12604.
 * Preserves page-specific content (H2 text, button text/link) while standardizing structure.
 *
 * @package suspended-developer/eightyfourem
 */

namespace EightyFourEM\CLI;

defined( 'ABSPATH' ) || exit;

// Only load if WP-CLI is available.
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * WP-CLI commands for hero block styling updates.
 */
class HeroUpdateCLI {

	/**
	 * Homepage page ID to exclude from updates.
	 *
	 * @var int
	 */
	private int $homepage_id;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->homepage_id = (int) \get_option( 'page_on_front', 0 );
	}

	/**
	 * Scan pages for hero blocks and show current styling vs target.
	 *
	 * ## OPTIONS
	 *
	 * [--post-id=<id>]
	 * : Scan a specific page by ID.
	 *
	 * ## EXAMPLES
	 *
	 *     # Scan all pages with hero blocks
	 *     wp 84em hero-update scan
	 *
	 *     # Scan a specific page
	 *     wp 84em hero-update scan --post-id=4041
	 *
	 * @subcommand scan
	 * @when after_wp_load
	 *
	 * @param array $_args      Positional arguments (unused).
	 * @param array $assoc_args Associative arguments.
	 */
	public function scan( $_args, $assoc_args ): void {
		$post_id = isset( $assoc_args['post-id'] ) ? (int) $assoc_args['post-id'] : null;

		\WP_CLI::log( '=== Hero Block Scan ===' );
		\WP_CLI::log( '' );

		if ( $post_id ) {
			$pages = [ \get_post( $post_id ) ];
			if ( ! $pages[0] ) {
				\WP_CLI::error( "Page not found: {$post_id}" );
				return;
			}
		} else {
			$pages = $this->find_pages_with_hero_blocks();
		}

		if ( empty( $pages ) ) {
			\WP_CLI::success( 'No pages with hero blocks found.' );
			return;
		}

		\WP_CLI::log( sprintf( 'Found %d page(s) with hero blocks.', count( $pages ) ) );
		\WP_CLI::log( sprintf( 'Homepage ID: %d (will be excluded from updates)', $this->homepage_id ) );
		\WP_CLI::log( '' );

		foreach ( $pages as $page ) {
			$is_homepage = (int) $page->ID === $this->homepage_id;
			$status      = $is_homepage ? ' [HOMEPAGE - SKIP]' : '';

			\WP_CLI::log( sprintf( '--- Page ID %d: %s%s ---', $page->ID, $page->post_title, $status ) );

			$hero_info = $this->analyze_hero_block( $page->post_content );

			if ( ! $hero_info ) {
				\WP_CLI::log( '  No hero block found.' );
				continue;
			}

			\WP_CLI::log( sprintf( '  Hero Type: %s', $hero_info['type'] ) );
			\WP_CLI::log( sprintf( '  Background: %s', $hero_info['background'] ) );
			\WP_CLI::log( sprintf( '  Has H2: %s', $hero_info['has_h2'] ? 'Yes' : 'No' ) );
			\WP_CLI::log( sprintf( '  Has Button: %s', $hero_info['has_button'] ? 'Yes' : 'No' ) );

			if ( $hero_info['h2_text'] ) {
				\WP_CLI::log( sprintf( '  H2 Text: %s', mb_substr( $hero_info['h2_text'], 0, 60 ) . '...' ) );
			}

			if ( $hero_info['button_text'] ) {
				\WP_CLI::log( sprintf( '  Button: %s -> %s', $hero_info['button_text'], $hero_info['button_href'] ) );
			}

			$needs_update = $this->needs_styling_update( $hero_info );
			\WP_CLI::log( sprintf( '  Needs Update: %s', $needs_update ? 'YES' : 'No (already matches target)' ) );
			\WP_CLI::log( '' );
		}

		\WP_CLI::success( 'Scan complete.' );
	}

	/**
	 * Run hero block styling updates.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Preview changes without saving.
	 *
	 * [--post-id=<id>]
	 * : Update a specific page by ID.
	 *
	 * [--include-homepage]
	 * : Include the homepage in updates (normally excluded).
	 *
	 * ## EXAMPLES
	 *
	 *     # Preview all changes
	 *     wp 84em hero-update run --dry-run
	 *
	 *     # Execute updates on all pages
	 *     wp 84em hero-update run
	 *
	 *     # Update a specific page
	 *     wp 84em hero-update run --post-id=4041
	 *
	 *     # Update all pages including homepage
	 *     wp 84em hero-update run --include-homepage
	 *
	 * @subcommand run
	 * @when after_wp_load
	 *
	 * @param array $_args      Positional arguments (unused).
	 * @param array $assoc_args Associative arguments.
	 */
	public function run( $_args, $assoc_args ): void {
		$dry_run          = isset( $assoc_args['dry-run'] );
		$post_id          = isset( $assoc_args['post-id'] ) ? (int) $assoc_args['post-id'] : null;
		$include_homepage = isset( $assoc_args['include-homepage'] );

		if ( $dry_run ) {
			\WP_CLI::log( 'DRY RUN: No changes will be saved.' );
			\WP_CLI::log( '' );
		}

		\WP_CLI::log( '=== Hero Block Update ===' );
		if ( $include_homepage ) {
			\WP_CLI::log( 'Homepage INCLUDED in updates.' );
		}
		\WP_CLI::log( '' );

		if ( $post_id ) {
			if ( $post_id === $this->homepage_id && ! $include_homepage ) {
				\WP_CLI::error( 'Cannot update homepage hero block. Use --include-homepage flag to include it.' );
				return;
			}
			$pages = [ \get_post( $post_id ) ];
			if ( ! $pages[0] ) {
				\WP_CLI::error( "Page not found: {$post_id}" );
				return;
			}
		} else {
			$pages = $this->find_pages_with_hero_blocks();
		}

		if ( empty( $pages ) ) {
			\WP_CLI::success( 'No pages with hero blocks found.' );
			return;
		}

		$updated_count = 0;
		$skipped_count = 0;
		$error_count   = 0;

		foreach ( $pages as $page ) {
			// Skip homepage unless --include-homepage flag is set.
			if ( (int) $page->ID === $this->homepage_id && ! $include_homepage ) {
				\WP_CLI::log( sprintf( '[SKIP] Page ID %d: %s (homepage)', $page->ID, $page->post_title ) );
				$skipped_count++;
				continue;
			}

			$hero_info = $this->analyze_hero_block( $page->post_content );

			if ( ! $hero_info ) {
				\WP_CLI::log( sprintf( '[SKIP] Page ID %d: %s (no hero block)', $page->ID, $page->post_title ) );
				$skipped_count++;
				continue;
			}

			if ( ! $this->needs_styling_update( $hero_info ) ) {
				\WP_CLI::log( sprintf( '[SKIP] Page ID %d: %s (already matches target)', $page->ID, $page->post_title ) );
				$skipped_count++;
				continue;
			}

			$new_content = $this->update_hero_block( $page->post_content, $hero_info );

			if ( $new_content === $page->post_content ) {
				\WP_CLI::warning( sprintf( '[ERROR] Page ID %d: %s (no changes produced)', $page->ID, $page->post_title ) );
				$error_count++;
				continue;
			}

			if ( $dry_run ) {
				\WP_CLI::log( sprintf( '[WOULD UPDATE] Page ID %d: %s', $page->ID, $page->post_title ) );
				$this->log_changes( $hero_info );
			} else {
				$result = \wp_update_post(
					[
						'ID'           => $page->ID,
						'post_content' => $new_content,
					],
					true
				);

				if ( \is_wp_error( $result ) ) {
					\WP_CLI::warning( sprintf( '[ERROR] Page ID %d: %s - %s', $page->ID, $page->post_title, $result->get_error_message() ) );
					$error_count++;
					continue;
				}

				\WP_CLI::log( sprintf( '[UPDATED] Page ID %d: %s', $page->ID, $page->post_title ) );
				$this->log_changes( $hero_info );
			}

			$updated_count++;
		}

		\WP_CLI::log( '' );
		if ( $dry_run ) {
			\WP_CLI::success( sprintf(
				'DRY RUN complete. Would update %d page(s), skip %d, errors %d.',
				$updated_count,
				$skipped_count,
				$error_count
			) );
		} else {
			\WP_CLI::success( sprintf(
				'Update complete. Updated %d page(s), skipped %d, errors %d.',
				$updated_count,
				$skipped_count,
				$error_count
			) );
		}
	}

	/**
	 * Find all pages with hero blocks.
	 *
	 * @return array Array of WP_Post objects.
	 */
	private function find_pages_with_hero_blocks(): array {
		global $wpdb;

		// Find pages with hero blocks by metadata name or structure.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			"SELECT ID, post_title, post_content
			FROM {$wpdb->posts}
			WHERE post_status = 'publish'
			AND post_type = 'page'
			AND (
				post_content LIKE '%\"name\":\"84EM Hero\"%'
				OR post_content LIKE '%\"name\":\"hero\"%'
				OR post_content LIKE '%\"name\":\"Hero%'
			)
			ORDER BY post_title"
		);

		return $results ?: [];
	}

	/**
	 * Analyze hero block structure and extract content.
	 *
	 * @param string $content Post content.
	 *
	 * @return array|null Hero info array or null if not found.
	 */
	private function analyze_hero_block( string $content ): ?array {
		// Parse the blocks.
		$blocks = \parse_blocks( $content );

		foreach ( $blocks as $block ) {
			$hero_info = $this->find_hero_in_block( $block );
			if ( $hero_info ) {
				return $hero_info;
			}
		}

		return null;
	}

	/**
	 * Recursively find hero block in parsed block tree.
	 *
	 * @param array $block Parsed block.
	 *
	 * @return array|null Hero info or null.
	 */
	private function find_hero_in_block( array $block ): ?array {
		// Check if this is a hero block by metadata name.
		$is_hero = false;
		$attrs   = $block['attrs'] ?? [];

		if ( isset( $attrs['metadata']['name'] ) ) {
			$name    = strtolower( $attrs['metadata']['name'] );
			$is_hero = strpos( $name, 'hero' ) !== false;
		}

		if ( $is_hero && $block['blockName'] === 'core/group' ) {
			return $this->extract_hero_info( $block );
		}

		// Check inner blocks.
		if ( ! empty( $block['innerBlocks'] ) ) {
			foreach ( $block['innerBlocks'] as $inner ) {
				$result = $this->find_hero_in_block( $inner );
				if ( $result ) {
					return $result;
				}
			}
		}

		return null;
	}

	/**
	 * Extract hero block information.
	 *
	 * @param array $block Hero block.
	 *
	 * @return array Hero info.
	 */
	private function extract_hero_info( array $block ): array {
		$attrs = $block['attrs'] ?? [];

		// Determine hero type.
		$type = 'unknown';
		if ( isset( $attrs['style']['background']['backgroundImage'] ) ) {
			$type = 'background-image';
		} elseif ( isset( $attrs['style']['color']['gradient'] ) ) {
			$type = 'gradient';
		} elseif ( isset( $attrs['backgroundColor'] ) ) {
			$type = 'solid-color';
		}

		// Get background info.
		$background = 'none';
		if ( $type === 'background-image' ) {
			$bg_url     = $attrs['style']['background']['backgroundImage']['url'] ?? '';
			$background = $bg_url ? basename( $bg_url ) : 'image';
		} elseif ( $type === 'gradient' ) {
			$background = $attrs['style']['color']['gradient'] ?? 'gradient';
		} elseif ( $type === 'solid-color' ) {
			$background = $attrs['backgroundColor'] ?? 'color';
		}

		// Extract H2 and button.
		$h2_text     = '';
		$button_text = '';
		$button_href = '';
		$has_h2      = false;
		$has_button  = false;

		$this->find_content_in_blocks( $block['innerBlocks'] ?? [], $h2_text, $button_text, $button_href );

		$has_h2     = ! empty( $h2_text );
		$has_button = ! empty( $button_text );

		return [
			'type'        => $type,
			'background'  => $background,
			'has_h2'      => $has_h2,
			'has_button'  => $has_button,
			'h2_text'     => $h2_text,
			'button_text' => $button_text,
			'button_href' => $button_href,
			'raw_block'   => $block,
		];
	}

	/**
	 * Recursively find H2 and button content in blocks.
	 *
	 * @param array  $blocks      Inner blocks.
	 * @param string $h2_text     Reference to H2 text.
	 * @param string $button_text Reference to button text.
	 * @param string $button_href Reference to button href.
	 */
	private function find_content_in_blocks( array $blocks, string &$h2_text, string &$button_text, string &$button_href ): void {
		foreach ( $blocks as $block ) {
			if ( $block['blockName'] === 'core/heading' ) {
				$level = $block['attrs']['level'] ?? 2;
				if ( $level === 2 && empty( $h2_text ) ) {
					$h2_text = $this->extract_text_from_html( $block['innerHTML'] ?? '' );
				}
			}

			if ( $block['blockName'] === 'core/button' && empty( $button_text ) ) {
				$button_text = $this->extract_text_from_html( $block['innerHTML'] ?? '' );
				// Extract href from the inner anchor.
				if ( preg_match( '/href="([^"]+)"/', $block['innerHTML'] ?? '', $matches ) ) {
					$button_href = $matches[1];
				}
			}

			// Recurse into inner blocks.
			if ( ! empty( $block['innerBlocks'] ) ) {
				$this->find_content_in_blocks( $block['innerBlocks'], $h2_text, $button_text, $button_href );
			}
		}
	}

	/**
	 * Extract text content from HTML.
	 *
	 * @param string $html HTML string.
	 *
	 * @return string Text content.
	 */
	private function extract_text_from_html( string $html ): string {
		return trim( \wp_strip_all_tags( $html ) );
	}

	/**
	 * Check if hero needs styling update.
	 *
	 * @param array $hero_info Hero info array.
	 *
	 * @return bool True if needs update.
	 */
	private function needs_styling_update( array $hero_info ): bool {
		$raw_block = $hero_info['raw_block'];

		// Check for the target 3-level nested structure.
		// Outer: contrast background, layout default.
		// Middle: gradient or background-image, minHeight, constrained layout.
		// Inner: overlay color #1e1e1e8c, contentSize 1280px, left justified.
		return ! $this->has_target_structure( $raw_block );
	}

	/**
	 * Check if hero block has the target structure with CSS variables.
	 *
	 * Validates that all hero styling uses CSS variables for maintainability:
	 * --hero-min-height, --hero-overlay-bg, --hero-content-width, --hero-bg-position, --hero-text-color
	 *
	 * @param array $block Hero block.
	 *
	 * @return bool True if has target structure with CSS variables.
	 */
	private function has_target_structure( array $block ): bool {
		$attrs = $block['attrs'] ?? [];

		// Outer group must have contrast backgroundColor and default layout.
		if ( ( $attrs['backgroundColor'] ?? '' ) !== 'contrast' ) {
			return false;
		}

		if ( ( $attrs['layout']['type'] ?? '' ) !== 'default' ) {
			return false;
		}

		// Check for at least one inner block (middle group).
		$inner_blocks = $block['innerBlocks'] ?? [];
		if ( count( $inner_blocks ) < 1 ) {
			return false;
		}

		$middle_group = $inner_blocks[0] ?? [];
		$middle_attrs = $middle_group['attrs'] ?? [];

		// Middle group must have background-image (not gradient).
		$has_bg_image = isset( $middle_attrs['style']['background']['backgroundImage'] );
		$min_height   = $middle_attrs['style']['dimensions']['minHeight'] ?? '';
		$bg_position  = $middle_attrs['style']['background']['backgroundPosition'] ?? '';

		if ( ! $has_bg_image ) {
			return false;
		}

		// Must use CSS variables for min-height and background-position.
		if ( strpos( $min_height, '--hero-min-height' ) === false ) {
			return false;
		}

		if ( strpos( $bg_position, '--hero-bg-position' ) === false ) {
			return false;
		}

		// Check for inner overlay group.
		$overlay_blocks = $middle_group['innerBlocks'] ?? [];
		if ( count( $overlay_blocks ) < 1 ) {
			return false;
		}

		$overlay_group = $overlay_blocks[0] ?? [];
		$overlay_attrs = $overlay_group['attrs'] ?? [];

		// Overlay group must use CSS variable for background color.
		$overlay_color = $overlay_attrs['style']['color']['background'] ?? '';
		if ( strpos( $overlay_color, '--hero-overlay-bg' ) === false ) {
			return false;
		}

		// Overlay group must use CSS variable for contentSize.
		$content_size = $overlay_attrs['layout']['contentSize'] ?? '';
		if ( strpos( $content_size, '--hero-content-width' ) === false ) {
			return false;
		}

		return true;
	}

	/**
	 * Update hero block to target styling.
	 *
	 * For gradient heroes, convert to the new structure.
	 * Preserves H2 content and button if present.
	 *
	 * @param string $content   Post content.
	 * @param array  $hero_info Hero info.
	 *
	 * @return string Updated content.
	 */
	private function update_hero_block( string $content, array $hero_info ): string {
		// Build the new hero block.
		$new_hero = $this->build_updated_hero( $hero_info );

		// Replace the old hero with the new one.
		// Find the hero block boundaries in content.
		$pattern = $this->build_hero_regex_pattern();

		$new_content = preg_replace( $pattern, $new_hero, $content, 1 );

		return $new_content ?: $content;
	}

	/**
	 * Build regex pattern to match hero block.
	 *
	 * @return string Regex pattern.
	 */
	private function build_hero_regex_pattern(): string {
		// Match hero blocks by their metadata name.
		// This is a simplified pattern - may need adjustment for edge cases.
		return '/<!-- wp:group \{[^}]*"name":"(?:84EM Hero|hero|Hero[^"]*)"[^}]*\}[^>]*-->.*?<!-- \/wp:group -->\s*(?=<!-- wp:(?:block|group))/s';
	}

	/**
	 * Build updated hero block markup.
	 *
	 * Uses CSS variables defined in utilities.css for maintainability:
	 * --hero-min-height, --hero-overlay-bg, --hero-content-width,
	 * --hero-bg-position, --hero-text-color, --hero-btn-radius-small, --hero-btn-radius-large
	 *
	 * @param array $hero_info Hero info.
	 *
	 * @return string New hero block markup.
	 */
	private function build_updated_hero( array $hero_info ): string {
		$h2_text     = $hero_info['h2_text'];
		$button_text = $hero_info['button_text'];
		$button_href = $hero_info['button_href'];

		// Escape content for safe output.
		$h2_escaped = \esc_html( $h2_text );

		// Build inner content (H2 and optional button).
		$h2_block = '';
		if ( $h2_text ) {
			$h2_block = <<<BLOCK

<!-- wp:heading {"className":"is-glow is-style-default","style":{"elements":{"link":{"color":{"text":"var(--hero-text-color, #ffffff)"}}},"typography":{"fontStyle":"normal","fontWeight":"600","lineHeight":"1.3"},"color":{"text":"var(--hero-text-color, #ffffff)"},"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|10"}}},"fontSize":"large","fontFamily":"heading"} -->
<h2 class="wp-block-heading is-glow is-style-default has-text-color has-link-color has-heading-font-family has-large-font-size" style="color:var(--hero-text-color, #ffffff);padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--10);font-style:normal;font-weight:600;line-height:1.3">{$h2_escaped}</h2>
<!-- /wp:heading -->
BLOCK;
		}

		$button_block = '';
		if ( $button_text && $button_href ) {
			$btn_text_escaped = \esc_html( $button_text );
			$btn_href_escaped = \esc_url( $button_href );
			$button_block     = <<<BLOCK

<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}},"fontSize":"medium","layout":{"type":"flex","justifyContent":"left"}} -->
<div class="wp-block-buttons has-custom-font-size has-medium-font-size" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--20)"><!-- wp:button {"style":{"border":{"radius":{"topLeft":"var(--hero-btn-radius-small, 0px)","topRight":"var(--hero-btn-radius-large, 30px)","bottomLeft":"var(--hero-btn-radius-large, 30px)","bottomRight":"var(--hero-btn-radius-small, 0px)"}},"shadow":"var:preset|shadow|crisp"},"fontSize":"large"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-large-font-size has-custom-font-size wp-element-button" href="{$btn_href_escaped}" style="border-top-left-radius:var(--hero-btn-radius-small, 0px);border-top-right-radius:var(--hero-btn-radius-large, 30px);border-bottom-left-radius:var(--hero-btn-radius-large, 30px);border-bottom-right-radius:var(--hero-btn-radius-small, 0px);box-shadow:var(--wp--preset--shadow--crisp)">{$btn_text_escaped}</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
BLOCK;
		}

		// Get the homepage background image URL.
		$bg_image_url = \content_url( '/uploads/2026/01/84em-home-hero-background-scaled.jpg' );

		// Build complete hero block with new structure.
		// Note: JSON attributes use fallback values for WordPress editor compatibility.
		// Inline styles use CSS variables for runtime flexibility.
		$hero = <<<HERO
<!-- wp:group {"metadata":{"name":"hero"},"align":"full","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"},"margin":{"top":"0","bottom":"0"}}},"backgroundColor":"contrast","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull has-contrast-background-color has-background" style="margin-top:0;margin-bottom:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0"><!-- wp:group {"style":{"background":{"backgroundImage":{"url":"{$bg_image_url}","id":12601,"source":"file","title":"84em-home-hero-background"},"backgroundSize":"cover","backgroundPosition":"var(--hero-bg-position, 60% 45%)"},"dimensions":{"minHeight":"var(--hero-min-height, 250px)"},"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="min-height:var(--hero-min-height, 250px);padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--30);background-position:var(--hero-bg-position, 60% 45%)"><!-- wp:group {"align":"wide","style":{"color":{"background":"var(--hero-overlay-bg, #1e1e1e8c)"},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"var(--hero-content-width, 1280px)","justifyContent":"left"}} -->
<div class="wp-block-group alignwide has-background" style="background-color:var(--hero-overlay-bg, #1e1e1e8c);padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)"><!-- wp:post-title {"level":1,"className":"is-style-default is-glow","style":{"elements":{"link":{"color":{"text":"var(--hero-text-color, #ffffff)"}}},"typography":{"fontStyle":"normal","fontWeight":"600"},"color":{"text":"var(--hero-text-color, #ffffff)"},"spacing":{"padding":{"top":"0","bottom":"0"}}},"fontSize":"xx-large","fontFamily":"heading"} /-->{$h2_block}{$button_block}</div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

HERO;

		return $hero;
	}

	/**
	 * Log changes made to hero block.
	 *
	 * @param array $hero_info Hero info.
	 */
	private function log_changes( array $hero_info ): void {
		\WP_CLI::log( sprintf( '  - Changed from: %s (%s)', $hero_info['type'], $hero_info['background'] ) );
		\WP_CLI::log( '  - Changed to: background image with overlay structure' );
		if ( $hero_info['h2_text'] ) {
			\WP_CLI::log( sprintf( '  - Preserved H2: %s', mb_substr( $hero_info['h2_text'], 0, 50 ) . '...' ) );
		}
		if ( $hero_info['button_text'] ) {
			\WP_CLI::log( sprintf( '  - Preserved button: %s', $hero_info['button_text'] ) );
		}
	}
}

// Register the command.
\WP_CLI::add_command( '84em hero-update', HeroUpdateCLI::class );
