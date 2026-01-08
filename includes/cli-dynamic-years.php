<?php
/**
 * WP-CLI commands for dynamic years migration.
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
 * Hardcoded replacements to make during migration.
 * Keys are the exact strings to find, values are the shortcode replacements.
 */
const REPLACEMENTS = [
	'30 years' => '[dev_years] years',
	'13 years' => '[wp_years] years',
];

/**
 * WP-CLI commands for dynamic years shortcode migration.
 */
class DynamicYearsCLI {

	/**
	 * Migrate hardcoded year values to dynamic shortcodes.
	 *
	 * Replaces "30 years" with "[dev_years] years" and "13 years" with "[wp_years] years"
	 * in post content.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Preview changes without saving.
	 *
	 * ## EXAMPLES
	 *
	 *     # Preview migration
	 *     wp 84em dynamic-years migrate --dry-run
	 *
	 *     # Execute migration
	 *     wp 84em dynamic-years migrate
	 *
	 * @subcommand migrate
	 * @when after_wp_load
	 *
	 * @param array $_args      Positional arguments (unused).
	 * @param array $assoc_args Associative arguments.
	 */
	public function migrate( $_args, $assoc_args ) {
		$dry_run            = isset( $assoc_args['dry-run'] );
		$total_replacements = 0;
		$total_items        = 0;

		if ( $dry_run ) {
			\WP_CLI::log( 'DRY RUN: No changes will be saved.' );
		}

		\WP_CLI::log( 'Migrating hardcoded years to dynamic shortcodes...' );

		// Find all items with hardcoded year references.
		$items = $this->find_items_with_year_references();

		if ( empty( $items ) ) {
			\WP_CLI::success( 'No hardcoded year values found. Nothing to migrate.' );
			return;
		}

		\WP_CLI::log( sprintf( 'Found %d items with potential year references.', count( $items ) ) );

		$current = 0;
		foreach ( $items as $item ) {
			$current++;
			$content           = $item['content'];
			$new_content       = $content;
			$item_replacements = 0;

			foreach ( REPLACEMENTS as $search => $replace ) {
				$count       = 0;
				$new_content = \str_ireplace( $search, $replace, $new_content, $count );
				$item_replacements += $count;
			}

			if ( $new_content !== $content && $item_replacements > 0 ) {
				$total_replacements += $item_replacements;
				$total_items++;

				$label = $item['type'] === 'template' ? 'template' : $item['type'];
				$title = $item['title'];

				if ( $dry_run ) {
					\WP_CLI::log( sprintf(
						'[%d/%d] Would migrate %s ID %s "%s"... %d replacement(s)',
						$current,
						count( $items ),
						$label,
						$item['id'],
						$title,
						$item_replacements
					) );
				} else {
					\wp_update_post(
						[
							'ID'           => $item['id'],
							'post_content' => $new_content,
						]
					);

					\WP_CLI::log( sprintf(
						'[%d/%d] Migrated %s ID %s "%s"... %d replacement(s)',
						$current,
						count( $items ),
						$label,
						$item['id'],
						$title,
						$item_replacements
					) );
				}
			}
		}

		if ( $dry_run ) {
			\WP_CLI::success( sprintf(
				'DRY RUN complete. Would make %d replacement(s) across %d item(s).',
				$total_replacements,
				$total_items
			) );
		} else {
			\WP_CLI::success( sprintf(
				'Migration complete. Made %d replacement(s) across %d item(s).',
				$total_replacements,
				$total_items
			) );
		}
	}

	/**
	 * Test that dynamic year shortcodes work correctly.
	 *
	 * ## EXAMPLES
	 *
	 *     wp 84em dynamic-years test
	 *
	 * @subcommand test
	 * @when after_wp_load
	 *
	 * @param array $_args      Positional arguments (unused).
	 * @param array $_assoc_args Associative arguments (unused).
	 */
	public function test( $_args, $_assoc_args ) {
		\WP_CLI::log( '=== Testing Dynamic Year Shortcodes ===' );

		$current_year = (int) \date( 'Y' );
		$tests_passed = 0;
		$tests_failed = 0;

		// Test [years_since year="2012"]
		$result   = \do_shortcode( '[years_since year="2012"]' );
		$expected = (string) ( $current_year - 2012 );
		if ( $result === $expected ) {
			\WP_CLI::log( "[PASS] [years_since year=\"2012\"] = {$result}" );
			$tests_passed++;
		} else {
			\WP_CLI::warning( "[FAIL] [years_since year=\"2012\"] expected {$expected}, got {$result}" );
			$tests_failed++;
		}

		// Test [wp_years]
		$result   = \do_shortcode( '[wp_years]' );
		$expected = (string) ( $current_year - \EightyFourEM\DynamicYears\WORDPRESS_START_YEAR );
		if ( $result === $expected ) {
			\WP_CLI::log( "[PASS] [wp_years] = {$result}" );
			$tests_passed++;
		} else {
			\WP_CLI::warning( "[FAIL] [wp_years] expected {$expected}, got {$result}" );
			$tests_failed++;
		}

		// Test [dev_years]
		$result   = \do_shortcode( '[dev_years]' );
		$expected = (string) ( $current_year - \EightyFourEM\DynamicYears\PROGRAMMING_START_YEAR );
		if ( $result === $expected ) {
			\WP_CLI::log( "[PASS] [dev_years] = {$result}" );
			$tests_passed++;
		} else {
			\WP_CLI::warning( "[FAIL] [dev_years] expected {$expected}, got {$result}" );
			$tests_failed++;
		}

		// Test [years_since] with no year (should return 0)
		$result = \do_shortcode( '[years_since]' );
		if ( $result === '0' ) {
			\WP_CLI::log( '[PASS] [years_since] (no year) = 0' );
			$tests_passed++;
		} else {
			\WP_CLI::warning( "[FAIL] [years_since] (no year) expected 0, got {$result}" );
			$tests_failed++;
		}

		\WP_CLI::log( '' );
		if ( $tests_failed === 0 ) {
			\WP_CLI::success( "All {$tests_passed} tests passed." );
		} else {
			\WP_CLI::error( "{$tests_failed} test(s) failed, {$tests_passed} passed." );
		}
	}

	/**
	 * Display migration statistics.
	 *
	 * Shows how many items contain hardcoded year values that could be migrated.
	 *
	 * ## EXAMPLES
	 *
	 *     wp 84em dynamic-years stats
	 *
	 * @subcommand stats
	 * @when after_wp_load
	 *
	 * @param array $_args      Positional arguments (unused).
	 * @param array $_assoc_args Associative arguments (unused).
	 */
	public function stats( $_args, $_assoc_args ) {
		\WP_CLI::log( '=== Dynamic Years Migration Statistics ===' );

		$current_year = (int) \date( 'Y' );

		\WP_CLI::log( '' );
		\WP_CLI::log( 'Shortcode values (calculated for ' . $current_year . '):' );
		\WP_CLI::log( sprintf( '  - [dev_years] (since %d): %d', \EightyFourEM\DynamicYears\PROGRAMMING_START_YEAR, $current_year - \EightyFourEM\DynamicYears\PROGRAMMING_START_YEAR ) );
		\WP_CLI::log( sprintf( '  - [wp_years] (since %d): %d', \EightyFourEM\DynamicYears\WORDPRESS_START_YEAR, $current_year - \EightyFourEM\DynamicYears\WORDPRESS_START_YEAR ) );
		\WP_CLI::log( '' );

		\WP_CLI::log( 'Search patterns:' );
		foreach ( REPLACEMENTS as $search => $replace ) {
			\WP_CLI::log( sprintf( '  - "%s" -> "%s"', $search, $replace ) );
		}
		\WP_CLI::log( '' );

		$items = $this->find_items_with_year_references();

		if ( empty( $items ) ) {
			\WP_CLI::success( 'No items found with hardcoded year values. Migration may already be complete.' );
			return;
		}

		// Count occurrences per pattern.
		$counts = [];
		foreach ( array_keys( REPLACEMENTS ) as $search ) {
			$counts[ $search ] = 0;
		}

		foreach ( $items as $item ) {
			foreach ( array_keys( REPLACEMENTS ) as $search ) {
				$counts[ $search ] += \substr_count( \strtolower( $item['content'] ), \strtolower( $search ) );
			}
		}

		\WP_CLI::log( 'Items with hardcoded years:' );
		\WP_CLI::log( sprintf( '  - Total items: %d', count( $items ) ) );
		foreach ( $counts as $search => $count ) {
			\WP_CLI::log( sprintf( '  - "%s" occurrences: %d', $search, $count ) );
		}
		\WP_CLI::log( '' );

		\WP_CLI::log( 'Affected items:' );
		foreach ( $items as $item ) {
			\WP_CLI::log( sprintf( '  - [%s] %s (ID: %d)', $item['type'], $item['title'], $item['id'] ) );
		}

		\WP_CLI::log( '' );
		\WP_CLI::log( 'Run "wp 84em dynamic-years migrate --dry-run" to preview migration.' );
	}

	/**
	 * Find all items containing hardcoded year references.
	 *
	 * @return array Array of items with id, title, content, and type.
	 */
	private function find_items_with_year_references(): array {
		global $wpdb;

		$items = [];

		// Build LIKE conditions for all search patterns.
		$like_conditions = [];
		foreach ( array_keys( REPLACEMENTS ) as $search ) {
			$like_conditions[] = $wpdb->prepare(
				'post_content LIKE %s',
				'%' . $wpdb->esc_like( $search ) . '%'
			);
		}
		$where_clause = '(' . implode( ' OR ', $like_conditions ) . ')';

		// Search in posts, pages, and reusable blocks.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$posts = $wpdb->get_results(
			"SELECT ID, post_title, post_content, post_type
			FROM {$wpdb->posts}
			WHERE post_status IN ('publish', 'draft', 'private')
			AND post_type IN ('post', 'page', 'wp_block')
			AND {$where_clause}"
		);

		foreach ( $posts as $post ) {
			$items[] = [
				'id'      => $post->ID,
				'title'   => $post->post_title,
				'content' => $post->post_content,
				'type'    => $post->post_type,
			];
		}

		// Search in wp_template and wp_template_part.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$templates = $wpdb->get_results(
			"SELECT ID, post_title, post_content, post_type, post_name
			FROM {$wpdb->posts}
			WHERE post_status IN ('publish', 'draft')
			AND post_type IN ('wp_template', 'wp_template_part')
			AND {$where_clause}"
		);

		foreach ( $templates as $template ) {
			$items[] = [
				'id'      => $template->ID,
				'title'   => $template->post_name,
				'content' => $template->post_content,
				'type'    => 'template',
			];
		}

		return $items;
	}
}

// Register the command.
\WP_CLI::add_command( '84em dynamic-years', DynamicYearsCLI::class );
