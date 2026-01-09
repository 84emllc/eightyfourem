<?php
/**
 * Pattern Categories
 * Register custom pattern categories for the theme
 *
 * @package EightyFourEM
 */

namespace EightyFourEM;

defined( 'ABSPATH' ) || exit;

/**
 * Register custom pattern categories
 */
\add_action(
	hook_name: 'init',
	callback: function () {
		\register_block_pattern_category(
			'page',
			[
				'label'       => \_x( 'Pages', 'Block pattern category' ),
				'description' => \__( 'A collection of full page layouts.' ),
			]
		);
	}
);

/**
 * Unregister unused core pattern categories
 *
 * Theme only uses 'query' and 'page' categories.
 * All other core categories clutter the Block Editor pattern picker.
 */
\add_action(
	hook_name: 'init',
	callback: function () {
		$unused_categories = [
			'banner',
			'buttons',
			'columns',
			'text',
			'featured',
			'call-to-action',
			'team',
			'testimonials',
			'services',
			'contact',
			'about',
			'portfolio',
			'gallery',
			'media',
			'videos',
			'audio',
			'posts',
			'footer',
			'header',
		];

		foreach ( $unused_categories as $category ) {
			\unregister_block_pattern_category( $category );
		}
	},
	priority: 20
);

/**
 * Disable remote block patterns from WordPress.org
 *
 * Prevents patterns from wordpress.org/patterns from appearing in the Block Editor.
 * These remote patterns clutter the inserter and are not used in this theme.
 */
\add_filter(
	hook_name: 'should_load_remote_block_patterns',
	callback: fn() => false
);
