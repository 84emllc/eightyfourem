<?php

/**
 * Filters the query used to retrieve posts in WordPress.
 *
 * This filter modifies the query when performing a search request.
 * If the query is a search query, is not executed in the admin context,
 * and is the main query, it restricts the search results to include only
 * posts of type 'page' and excludes local pages (identified by _local_page_state meta key).
 *
 * Hooked to the 'pre_get_posts' hook.
 *
 * @param  \WP_Query  $query  The query object being filtered.
 *
 * @return \WP_Query The modified query object.
 */

namespace EightyFourEM;

defined( 'ABSPATH' ) || exit;

/**
 * Parent page IDs for type filtering.
 */
const SERVICES_PAGE_ID     = 2129;
const CASE_STUDIES_PAGE_ID = 4406;

/**
 * Get post type indicator label based on post parent.
 *
 * @param \WP_Post $post The post object.
 * @return string The post type label (Service, Case Study, or Page).
 */
function get_post_type_indicator( \WP_Post $post ): string {
	// Check if it's the Services page or a child of Services
	if ( $post->ID === SERVICES_PAGE_ID || $post->post_parent === SERVICES_PAGE_ID ) {
		return 'Service';
	}

	// Check if it's the Case Studies page or a child of Case Studies
	if ( $post->ID === CASE_STUDIES_PAGE_ID || $post->post_parent === CASE_STUDIES_PAGE_ID ) {
		return 'Case Study';
	}

	return 'Page';
}

/**
 * Get available search type filters.
 *
 * Returns an array mapping type labels to their configuration.
 *
 * @return array<string, array{parent_id: int|null, include_parent: bool}>
 */
function get_search_type_filters(): array {
	return [
		'service'    => [
			'parent_id'      => SERVICES_PAGE_ID,
			'include_parent' => true,
		],
		'case study' => [
			'parent_id'      => CASE_STUDIES_PAGE_ID,
			'include_parent' => true,
		],
		'page'       => [
			'parent_id'      => null,
			'include_parent' => false,
		],
	];
}

/**
 * Get post IDs matching a type filter.
 *
 * @param string $type The type filter (service, case study, or page).
 * @return array<int>|null Array of post IDs to include, or null if type is invalid.
 */
function get_posts_by_type_filter( string $type ): ?array {
	$filters = get_search_type_filters();
	$type    = strtolower( trim( $type ) );

	if ( ! isset( $filters[ $type ] ) ) {
		return null;
	}

	$config = $filters[ $type ];

	// Handle 'page' type - exclude service and case study pages
	if ( $config['parent_id'] === null ) {
		return null; // Will use post_parent__not_in instead
	}

	$parent_id = $config['parent_id'];

	// Get all children of the parent page
	$children = \get_children( [
		'post_parent' => $parent_id,
		'post_type'   => 'page',
		'post_status' => 'publish',
		'fields'      => 'ids',
		'numberposts' => -1,
	] );

	$post_ids = array_values( $children );

	// Include the parent page itself if configured
	if ( $config['include_parent'] ) {
		$post_ids[] = $parent_id;
	}

	return $post_ids;
}

\add_filter(
	hook_name: 'pre_get_posts',
	callback: function ( \WP_Query $query ): void {
		if ( ! $query->is_search || \is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$query->set( 'post_type', [ 'page' ] );

		// Base exclusions - always exclude these pages
		$excluded_posts = [ 2507, 4507 ];

		// Check for type filter parameter (supports array from checkboxes or single string)
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filter parameter
		$type_param = $_GET['type'] ?? null;

		// Normalize to array of sanitized values
		$type_values = [];
		if ( $type_param !== null ) {
			if ( \is_array( $type_param ) ) {
				// Handle array from type[] checkboxes
				$type_values = \array_map(
					fn( $val ) => strtolower( trim( \sanitize_text_field( \wp_unslash( $val ) ) ) ),
					$type_param
				);
			} else {
				// Handle single string value (backward compatibility)
				$sanitized   = strtolower( trim( \sanitize_text_field( \wp_unslash( $type_param ) ) ) );
				$type_values = $sanitized !== '' ? [ $sanitized ] : [];
			}
			$type_values = \array_filter( $type_values );
		}

		if ( ! empty( $type_values ) ) {
			$filters = get_search_type_filters();

			// Filter to only valid types
			$valid_types = array_filter( $type_values, fn( $t ) => isset( $filters[ $t ] ) );

			if ( ! empty( $valid_types ) ) {
				$all_post_ids      = [];
				$include_page_type = false;

				foreach ( $valid_types as $type_normalized ) {
					$config = $filters[ $type_normalized ];

					if ( $config['parent_id'] !== null ) {
						// Service or Case Study - get matching post IDs
						$post_ids = get_posts_by_type_filter( $type_normalized );
						if ( ! empty( $post_ids ) ) {
							$all_post_ids = array_merge( $all_post_ids, $post_ids );
						}
					} else {
						// 'page' type requested
						$include_page_type = true;
					}
				}

				// Remove duplicates
				$all_post_ids = array_unique( $all_post_ids );

				// If we have specific post IDs (Service/Case Study) but NOT page type
				if ( ! empty( $all_post_ids ) && ! $include_page_type ) {
					$query->set( 'post__in', $all_post_ids );
				} elseif ( $include_page_type && empty( $all_post_ids ) ) {
					// Only 'page' type selected - exclude Services and Case Studies
					$query->set( 'post_parent__not_in', [
						SERVICES_PAGE_ID,
						CASE_STUDIES_PAGE_ID,
					] );
					$excluded_posts[] = SERVICES_PAGE_ID;
					$excluded_posts[] = CASE_STUDIES_PAGE_ID;
				}
				// If both page type AND specific types are selected, don't filter by post__in
				// (allows all valid results through)
			}
		}

		$query->set( 'post__not_in', $excluded_posts );

		$query->set( 'meta_query', [
			'relation' => 'AND',
			[
				'key'     => '_local_page_state',
				'compare' => 'NOT EXISTS',
			],
			[
				'key'     => '_local_page_city',
				'compare' => 'NOT EXISTS',
			],
			[
				'key'     => '_84em_noindex',
				'compare' => '!=',
				'value'   => '1',
			],
		] );
	}
);

\add_filter(
	hook_name: 'render_block',
	callback: function ( string $block_content, array $parsed_block, $block ) {
		// Only apply to post title blocks in Query Loop
		if ( $parsed_block['blockName'] !== 'core/post-title' ) {
			return $block_content;
		}

		// Only on search results
		if ( ! \is_search() ) {
			return $block_content;
		}

		// Get post ID from WP_Block context (used in Query Loop)
		if ( ! isset( $block->context['postId'] ) ) {
			return $block_content;
		}

		$post_id = $block->context['postId'];
		$post = \get_post( $post_id );

		if ( ! $post || $post->post_type !== 'page' ) {
			return $block_content;
		}

		$indicator = get_post_type_indicator( $post );
		$badge_class = 'post-type-badge post-type-' . \sanitize_html_class( strtolower( str_replace( ' ', '-', $indicator ) ) );

		$badge = sprintf(
			'<span class="%s">%s</span> ',
			\esc_attr( $badge_class ),
			\esc_html( $indicator )
		);

		// Insert badge after the opening tag (after <h1>, <h2>, etc.)
		if ( preg_match( '/^(<h[1-6][^>]*>)(.*)$/s', $block_content, $matches ) ) {
			$block_content = $matches[1] . $badge . $matches[2];
		}

		return $block_content;
	},
	accepted_args: 3
);

\add_filter(
	hook_name: 'get_the_excerpt',
	callback: function ( string $excerpt, \WP_Post $post ): string {
		// Only apply on search pages or case studies
		if ( ! \is_search() && $post->post_parent !== CASE_STUDIES_PAGE_ID ) {
			return $excerpt;
		}

		$blocks = \parse_blocks( $post->post_content );

		if ( empty( $blocks ) ) {
			return $excerpt;
		}

		// Find and remove any Challenge heading block (and everything before it)
		$challenge_index = -1;

		foreach ( $blocks as $index => $block ) {
			if ( isset( $block['blockName'] ) &&
				 $block['blockName'] === 'core/heading' ) {

				$content = $block['innerHTML'] ?? '';
				$text    = \wp_strip_all_tags( $content );

				if ( stripos( $text, 'Challenge' ) !== false ) {
					$challenge_index = $index;
					break;
				}
			}
		}

		// If we found a Challenge heading, remove everything up to and including it
		if ( $challenge_index !== -1 ) {
			// Rebuild content without blocks up to and including the Challenge heading
			$filtered_blocks  = \array_slice( $blocks, $challenge_index + 1 );
			$filtered_content = '';

			foreach ( $filtered_blocks as $block ) {
				$filtered_content .= \render_block( $block );
			}

			// Generate excerpt from filtered content
			return \wp_trim_words( \wp_strip_all_tags( $filtered_content ), 55, '...' );
		}

		return $excerpt;
	},
	accepted_args: 2
);
