<?php
/**
 * Reading time functionality for case studies.
 *
 * Calculates reading time based on post content and appends it to
 * the "Read more" link in post excerpts on the case studies page.
 *
 * @package EightyFourEM
 */

namespace EightyFourEM\ReadingTime;

defined( 'ABSPATH' ) or die;

/**
 * Average reading speed in words per minute.
 */
const WORDS_PER_MINUTE = 150;

/**
 * Case studies parent page ID.
 */
const CASE_STUDIES_PAGE_ID = 4406;

/**
 * Calculate reading time for a post.
 *
 * @param int $post_id The post ID.
 * @return int Reading time in minutes (minimum 1).
 */
function get_reading_time( int $post_id ): int {
	$post = \get_post( $post_id );

	if ( ! $post ) {
		return 1;
	}

	$content    = $post->post_content;
	$word_count = \str_word_count( \wp_strip_all_tags( $content ) );
	$minutes    = (int) ceil( $word_count / WORDS_PER_MINUTE );

	return max( 1, $minutes );
}

/**
 * Modify post excerpt block to include reading time in the "Read more" link.
 *
 * Only applies on the case studies page (page ID 4406) and its query blocks.
 */
\add_filter(
	hook_name: 'render_block_core/post-excerpt',
	callback: function ( string $block_content, array $parsed_block, $block ): string {
		// Only on case studies page
		if ( ! \is_page( CASE_STUDIES_PAGE_ID ) ) {
			return $block_content;
		}

		// Get post ID from block context
		if ( ! isset( $block->context['postId'] ) ) {
			return $block_content;
		}

		$post_id      = $block->context['postId'];
		$reading_time = get_reading_time( $post_id );

		// Find and modify the "Read more" link
		// Pattern matches: <a class="wp-block-post-excerpt__more-link" href="...">TEXT</a>
		$pattern = '/(<a\s+class="wp-block-post-excerpt__more-link"[^>]*>)([^<]+)(<\/a>)/';

		$block_content = preg_replace_callback(
			$pattern,
			function ( $matches ) use ( $reading_time ) {
				$open_tag  = $matches[1];
				$text      = $matches[2];
				$close_tag = $matches[3];

				// Insert reading time before the arrow
				// Original: "Read more→"
				// New: "Read more (5 min)→"
				if ( preg_match( '/^(.+?)(→|->|&rarr;)$/', $text, $text_parts ) ) {
					$base_text = trim( $text_parts[1] );
					$arrow     = $text_parts[2];
					$new_text  = sprintf( '%d min read %s', $reading_time, $arrow );
				} else {
					// Fallback if no arrow found
					$new_text = sprintf( '%d min read', $reading_time );
				}

				return $open_tag . $new_text . $close_tag;
			},
			$block_content
		);

		return $block_content;
	},
	accepted_args: 3
);
