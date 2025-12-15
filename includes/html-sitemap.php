<?php

/**
 * HTML Sitemap Generation
 *
 * Generates a visual HTML sitemap with card-based layout organized into sections:
 * - Pages: Regular site pages with collapsible child pages
 * - Case Studies: Portfolio case studies
 * - Service Areas: Local SEO pages (states and cities)
 *
 * @package EightyFourEM
 */

namespace EightyFourEM\HtmlSitemap;

defined( 'ABSPATH' ) || exit;

/**
 * Converts a permalink to a readable title.
 *
 * @param int $post_id The post ID.
 * @return string The readable title (e.g., /some-url/ becomes "Some Url").
 */
function permalink_to_title( int $post_id ): string {
	$permalink = \get_permalink( $post_id );
	$path      = \wp_parse_url( $permalink, PHP_URL_PATH );

	if ( empty( $path ) || '/' === $path ) {
		return 'Home';
	}

	// Remove leading/trailing slashes and convert to title case.
	$slug = trim( $path, '/' );
	$slug = str_replace( [ '-', '_' ], ' ', $slug );

	return ucwords( $slug );
}

/**
 * Sanitizes local page titles by stripping verbose prefixes.
 *
 * @param string $title The original page title.
 * @param bool   $strip_state Whether to strip ", State" suffix from city titles.
 * @return string The sanitized title.
 */
function sanitize_local_title( string $title, bool $strip_state = false ): string {
	$prefixes = [
		'Custom WordPress Plugin Development, Consulting, and White-Label services in the ',
		'Custom WordPress Plugin Development, Consulting, and White-Label services in ',
		'WordPress Development, Plugins, Consulting, White-Label in ',
		'AI-Enhanced WordPress Development, White-Label Services, Plugins, Consulting in ',
		'WordPress Development, Plugins, Consulting, Agency Services in ',
		'WordPress Development, Plugins, Consulting, White-Label services in the ',
		' | 84EM',
	];

	foreach ( $prefixes as $prefix ) {
		$title = str_replace( $prefix, '', $title );
	}

	$title = trim( $title );

	// Strip ", State" suffix from city titles.
	if ( $strip_state ) {
		$comma_pos = strrpos( $title, ',' );
		if ( false !== $comma_pos ) {
			$title = substr( $title, 0, $comma_pos );
		}
	}

	return $title;
}

/**
 * Renders the HTML sitemap.
 *
 * @return string The HTML sitemap markup.
 */
function render(): string {
	global $wpdb;

	// Get noindex page IDs to exclude.
	$noindex_ids = $wpdb->get_col(
		"SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_84em_noindex' AND meta_value != '0'"
	);

	// Get local page IDs to exclude from regular pages section.
	$local_page_ids = $wpdb->get_col(
		"SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key IN('_local_page_state', '_local_page_city')"
	);

	// Combine exclusions: noindex + local pages + sitemap page + USA services parent + case studies parent.
	$exclude_ids = array_merge( $noindex_ids, $local_page_ids, [ 6964, 2507, 4406 ] );

	// Get case study IDs to exclude from regular pages section.
	$case_study_ids = \get_pages( [
		'child_of' => 4406,
		'fields'   => 'ids',
	] );
	$exclude_ids = array_merge( $exclude_ids, $case_study_ids ? array_map( fn( $p ) => $p->ID, $case_study_ids ) : [] );

	// Build output.
	$output = '<div class="sitemap-container">';

	// Section 1: Regular Pages.
	$output .= '<section class="sitemap-section sitemap-pages">';
	$output .= '<h2 class="sitemap-section-title">' . \esc_html__( 'Pages', 'eightyfourem' ) . '</h2>';
	$output .= '<div class="sitemap-grid">';

	// Get all top-level pages (parent = 0).
	$top_level_pages = \get_pages( [
		'parent'  => 0,
		'exclude' => $exclude_ids,
	] );

	// Sort by permalink-based title.
	if ( $top_level_pages ) {
		usort( $top_level_pages, fn( $a, $b ) => strcasecmp( permalink_to_title( $a->ID ), permalink_to_title( $b->ID ) ) );
	}

	if ( $top_level_pages ) {
		foreach ( $top_level_pages as $page ) {
			$output .= '<div class="sitemap-card">';
			$output .= '<h3 class="sitemap-card-title">';
			$output .= '<a href="' . \esc_url( \get_permalink( $page->ID ) ) . '">';
			$output .= \esc_html( permalink_to_title( $page->ID ) );
			$output .= '</a></h3>';

			// Get children of this page.
			$children = \get_pages( [
				'parent'      => $page->ID,
				'exclude'     => $exclude_ids,
				'sort_column' => 'post_title',
				'sort_order'  => 'ASC',
			] );

			if ( $children ) {
				$child_count = count( $children );
				$output     .= '<details class="sitemap-card-details">';
				$output     .= '<summary class="sitemap-card-summary">' . $child_count . ' ' . \_n( 'page', 'pages', $child_count, 'eightyfourem' ) . '</summary>';
				$output     .= '<ul class="sitemap-card-list">';
				foreach ( $children as $child ) {
					$output .= '<li>';
					$output .= '&middot; <a href="' . \esc_url( \get_permalink( $child->ID ) ) . '">';
					$output .= \esc_html( $child->post_title );
					$output .= '</a></li>';
				}
				$output .= '</ul>';
				$output .= '</details>';
			}

			$output .= '</div>';
		}
	}

	$output .= '</div></section>';

	// Section 2: Case Studies.
	$output .= '<section class="sitemap-section sitemap-case-studies">';
	$output .= '<h2 class="sitemap-section-title">' . \esc_html__( 'Case Studies', 'eightyfourem' ) . '</h2>';
	$output .= '<div class="sitemap-grid">';

	// Case Studies parent page as a card.
	$case_studies_parent = \get_post( 4406 );
	if ( $case_studies_parent ) {
		$output .= '<div class="sitemap-card sitemap-card-case-study">';
		$output .= '<h3 class="sitemap-card-title">';
		$output .= '<a href="' . \esc_url( \get_permalink( 4406 ) ) . '">';
		$output .= \esc_html( $case_studies_parent->post_title );
		$output .= '</a></h3>';

		// Get all case study children.
		$case_studies = \get_pages( [
			'parent'      => 4406,
			'exclude'     => $noindex_ids,
			'sort_column' => 'post_title',
			'sort_order'  => 'ASC',
		] );

		if ( $case_studies ) {
			$output .= '<ul class="sitemap-card-list">';
			foreach ( $case_studies as $case_study ) {
				$output .= '<li>';
				$output .= '&middot; <a href="' . \esc_url( \get_permalink( $case_study->ID ) ) . '">';
				$output .= \esc_html( $case_study->post_title );
				$output .= '</a></li>';
			}
			$output .= '</ul>';
		}

		$output .= '</div>';
	}

	$output .= '</div></section>';

	// Section 3: Service Areas (Local Pages).
	$output .= '<section class="sitemap-section sitemap-local">';
	$output .= '<h2 class="sitemap-section-title">' . \esc_html__( 'Service Areas', 'eightyfourem' ) . '</h2>';
	$output .= '<div class="sitemap-grid">';

	// Get states (direct children of USA parent page 2507).
	$states = \get_pages( [
		'parent'      => 2507,
		'exclude'     => $noindex_ids,
		'sort_column' => 'post_title',
		'sort_order'  => 'ASC',
	] );

	if ( $states ) {
		foreach ( $states as $state ) {
			$state_title = sanitize_local_title( $state->post_title );

			$output .= '<div class="sitemap-card sitemap-card-local">';
			$output .= '<h3 class="sitemap-card-title">';
			$output .= '<a href="' . \esc_url( \get_permalink( $state->ID ) ) . '">';
			$output .= \esc_html( $state_title );
			$output .= '</a></h3>';

			// Get cities (grandchildren - children of this state).
			$cities = \get_pages( [
				'parent'      => $state->ID,
				'exclude'     => $noindex_ids,
				'sort_column' => 'post_title',
				'sort_order'  => 'ASC',
			] );

			if ( $cities ) {
				$output .= '<ul class="sitemap-card-list">';
				foreach ( $cities as $city ) {
					$city_title = sanitize_local_title( $city->post_title, strip_state: true );
					$output    .= '<li>';
					$output    .= '&middot; <a href="' . \esc_url( \get_permalink( $city->ID ) ) . '">';
					$output    .= \esc_html( $city_title );
					$output    .= '</a></li>';
				}
				$output .= '</ul>';
			}

			$output .= '</div>';
		}
	}

	$output .= '</div></section></div>';

	return $output;
}
