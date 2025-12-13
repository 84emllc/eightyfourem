<?php
/**
 * Enqueue Scripts and Styles
 * Handles theme asset enqueuing
 *
 * @package EightyFourEM
 */

namespace EightyFourEM;

defined( 'ABSPATH' ) || exit;

/**
 * Enqueue main theme scripts and styles
 */
\add_action(
	hook_name: 'wp_enqueue_scripts',
	callback: function () {
		$suffix  = ( ! \defined( 'WP_DEBUG' ) || ! WP_DEBUG ) ? '.min' : '';
		$version = \wp_get_theme()->get( 'Version' );

		// Enqueue modular CSS files (replaces old customizer.css)
		\wp_enqueue_style(
            handle: 'eightyfourem-navigation',
            src: \get_theme_file_uri( "assets/css/navigation{$suffix}.css" ),
            ver: $version
		);

		\wp_enqueue_style(
            handle: 'eightyfourem-page-specific',
            src: \get_theme_file_uri( "assets/css/page-specific{$suffix}.css" ),
            ver: $version
		);

		\wp_enqueue_style(
            handle: 'eightyfourem-utilities',
            src: \get_theme_file_uri( "assets/css/utilities{$suffix}.css" ),
            ver: $version
		);

        \wp_enqueue_style(
            handle: 'eightyfourem-testimonials',
            src: \get_theme_file_uri( "assets/css/testimonials{$suffix}.css" ),
            ver: $version
        );

		\wp_enqueue_style(
			handle: 'eightyfourem-animations',
			src: \get_theme_file_uri( "assets/css/animations{$suffix}.css" ),
			ver: $version
		);

		// Enqueue animations JavaScript (defer for better performance)
		\wp_enqueue_script(
			handle: 'eightyfourem-animations',
			src: \get_theme_file_uri( "assets/js/animations{$suffix}.js" ),
			ver: $version,
			args: [
				'strategy'  => 'defer',
				'in_footer' => true,
			]
		);

		// Enqueue sticky header CSS
		\wp_enqueue_style(
            handle: 'eightyfourem-sticky-header',
            src: \get_theme_file_uri( "assets/css/sticky-header{$suffix}.css" ),
            ver: $version
		);

		// Enqueue sticky header JavaScript (defer for better performance)
        \wp_enqueue_script(
            handle: 'eightyfourem-sticky-header',
            src: \get_theme_file_uri( "assets/js/sticky-header{$suffix}.js" ),
            ver: $version,
            args: [
                'strategy' => 'defer',
                'in_footer' => true,
            ]
		);

        // Enqueue highlight script (defer for better performance)
        \wp_enqueue_script(
            handle: 'eightyfourem-highlight',
            src: \get_theme_file_uri( "assets/js/highlight{$suffix}.js" ),
            args: [
                'strategy' => 'defer',
                'in_footer' => true,
            ]
        );

        // Enqueue highlight CSS (non-critical, load async via media print)
        \wp_enqueue_style(
            handle: 'eightyfourem-highlighter',
            src: \get_theme_file_uri( "assets/css/highlight{$suffix}.css" ),
            media: 'print'
        );

		// Enqueue modal search CSS (non-critical, load async via media print)
		\wp_enqueue_style(
			handle: 'eightyfourem-modal-search',
			src: \get_theme_file_uri( "assets/css/modal-search{$suffix}.css" ),
			ver: $version,
			media: 'print'
		);

		// Enqueue modal search JavaScript (defer for better performance)
		\wp_enqueue_script(
			handle: 'eightyfourem-modal-search',
			src: \get_theme_file_uri( "assets/js/modal-search{$suffix}.js" ),
			ver: $version,
			args: [
				'strategy' => 'defer',
				'in_footer' => true,
			]
		);

		// Simple Analytics script
		\wp_enqueue_script(
			handle: 'eightyfourem-simple-analytics',
			src: 'https://scripts.simpleanalyticscdn.com/latest.js',
			ver: null,
			args: [
				'strategy'  => 'defer',
				'in_footer' => true,
			]
		);

		// Simple Analytics auto-events script
		\wp_enqueue_script(
			handle: 'eightyfourem-simple-analytics-events',
			src: 'https://scripts.simpleanalyticscdn.com/auto-events.js',
			ver: null,
			args: [
				'strategy'  => 'defer',
				'in_footer' => true,
			]
		);
	}
);

/**
 * Enqueue search results styles
 * Only loads on search results pages
 */
\add_action(
	hook_name: 'wp_enqueue_scripts',
	callback: function () {
		if ( ! \is_search() ) {
			return;
		}

		$suffix  = ( ! \defined( 'WP_DEBUG' ) || ! WP_DEBUG ) ? '.min' : '';
		$version = \wp_get_theme()->get( 'Version' );

		\wp_enqueue_style(
			'eightyfourem-search',
            \get_theme_file_uri( "assets/css/search{$suffix}.css" ),
			$version
		);
	}
);

/**
 * Enqueue case study filter assets
 * Only loads on case studies page (ID: 4406)
 */
\add_action(
	hook_name: 'wp_enqueue_scripts',
	callback: function () {
		if ( ! \is_page( 4406 ) ) {
			return;
		}

		$suffix  = ( ! \defined( 'WP_DEBUG' ) || ! WP_DEBUG ) ? '.min' : '';
		$version = \wp_get_theme()->get( 'Version' );

		\wp_enqueue_style(
			'eightyfourem-case-study-filter',
            \get_theme_file_uri( "assets/css/case-study-filter{$suffix}.css" ),
			$version
		);

        \wp_enqueue_script(
			handle: 'eightyfourem-case-study-filter',
            src: \get_theme_file_uri( "assets/js/case-study-filter{$suffix}.js" ),
			ver: $version,
			args: [
				'strategy' => 'defer',
				'in_footer' => true,
			]
		);
	}
);

/**
 * Enqueue FAQ search assets
 * Only loads on FAQ page (ID: 6908)
 */
\add_action(
	hook_name: 'wp_enqueue_scripts',
	callback: function () {
		if ( ! \is_page( 6908 ) ) {
			return;
		}

		$suffix  = ( ! \defined( 'WP_DEBUG' ) || ! WP_DEBUG ) ? '.min' : '';
		$version = \wp_get_theme()->get( 'Version' );

		\wp_enqueue_style(
			handle: 'eightyfourem-faq-search',
			src: \get_theme_file_uri( "assets/css/faq-search{$suffix}.css" ),
			ver: $version
		);

		\wp_enqueue_script(
			handle: 'eightyfourem-faq-search',
			src: \get_theme_file_uri( "assets/js/faq-search{$suffix}.js" ),
			ver: $version,
			args: [
				'strategy' => 'defer',
				'in_footer' => true,
			]
		);
	}
);

/**
 * Enqueue related case studies styles
 * Only loads on single case study pages
 */
\add_action(
	hook_name: 'wp_enqueue_scripts',
	callback: function () {
		if ( ! \is_page() || ! \is_singular() ) {
			return;
		}

		$post = \get_post();
		if ( ! $post || 4406 !== $post->post_parent ) {
			return;
		}

		$suffix  = ( ! \defined( 'WP_DEBUG' ) || ! WP_DEBUG ) ? '.min' : '';
		$version = \wp_get_theme()->get( 'Version' );

		\wp_enqueue_style(
			handle: 'eightyfourem-related-case-studies',
			src: \get_theme_file_uri( "assets/css/related-case-studies{$suffix}.css" ),
			ver: $version
		);
	}
);

/**
 * Add body class to disable sticky TOC on specific pages
 * Allows pages with many headings to opt out of jump navigation
 */
\add_filter(
	hook_name: 'body_class',
	callback: function ( array $classes ): array {
		// Pages where sticky TOC should be disabled
		$disabled_pages = [
			4406, // Case Studies page
		];

		if ( \is_page( $disabled_pages ) ) {
			$classes[] = 'disable-sticky-toc';
		}

		return $classes;
	}
);

/**
 * Enqueue sitemap styles
 * Only loads on sitemap page (ID: 6964)
 */
\add_action(
	hook_name: 'wp_enqueue_scripts',
	callback: function (): void {
		if ( ! \is_page( 6964 ) ) {
			return;
		}

		$suffix  = ( ! \defined( 'WP_DEBUG' ) || ! WP_DEBUG ) ? '.min' : '';
		$version = \wp_get_theme()->get( 'Version' );

		\wp_enqueue_style(
			handle: 'eightyfourem-sitemap',
			src: \get_theme_file_uri( "assets/css/sitemap{$suffix}.css" ),
			ver: $version
		);
	}
);
