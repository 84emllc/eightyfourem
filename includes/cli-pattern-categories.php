<?php
/**
 * WP-CLI commands for synced pattern categorization.
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
 * Category definitions for synced patterns.
 * Keys are category slugs, values contain name and matching rules.
 */
const PATTERN_CATEGORIES = [
	'heroes'       => [
		'name'  => 'Heroes',
		'rules' => [
			'starts_with' => [ '84EM Hero' ],
		],
	],
	'testimonials' => [
		'name'  => 'Testimonials',
		'rules' => [
			'starts_with' => [ 'Testimonial:' ],
		],
	],
	'ctas'         => [
		'name'  => 'CTAs',
		'rules' => [
			'contains' => [ 'CTA', 'Button', 'Start Your' ],
		],
	],
	'logos'        => [
		'name'  => 'Logos',
		'rules' => [
			'contains' => [ 'Logos' ],
		],
	],
	'utility'      => [
		'name'  => 'Utility',
		'rules' => [
			'exact' => [ 'Breadcrumbs', 'code separator', 'Security Metrics', 'services checklist' ],
		],
	],
	'deprecated'   => [
		'name'  => 'Deprecated',
		'rules' => [
			'contains' => [ 'old' ],
		],
	],
];

/**
 * WP-CLI commands for synced pattern categorization.
 */
class PatternCategoriesCLI {

	/**
	 * Sync categories for all synced patterns based on naming rules.
	 *
	 * Assigns categories to wp_block posts based on their titles.
	 * Creates categories if they don't exist.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Preview changes without saving.
	 *
	 * ## EXAMPLES
	 *
	 *     # Preview categorization
	 *     wp 84em pattern-categories sync --dry-run
	 *
	 *     # Execute categorization
	 *     wp 84em pattern-categories sync
	 *
	 * @subcommand sync
	 * @when after_wp_load
	 *
	 * @param array $_args      Positional arguments (unused).
	 * @param array $assoc_args Associative arguments.
	 */
	public function sync( $_args, $assoc_args ): void {
		$dry_run = isset( $assoc_args['dry-run'] );

		if ( $dry_run ) {
			\WP_CLI::log( 'DRY RUN: No changes will be saved.' );
			\WP_CLI::log( '' );
		}

		// Ensure categories exist.
		\WP_CLI::log( 'Ensuring pattern categories exist...' );
		$category_ids = $this->ensure_categories_exist( $dry_run );

		if ( empty( $category_ids ) && ! $dry_run ) {
			\WP_CLI::error( 'Failed to create or find pattern categories.' );
			return;
		}

		\WP_CLI::log( '' );

		// Get all synced patterns.
		$patterns = \get_posts(
			[
				'post_type'      => 'wp_block',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			]
		);

		if ( empty( $patterns ) ) {
			\WP_CLI::warning( 'No synced patterns found.' );
			return;
		}

		\WP_CLI::log( sprintf( 'Processing %d synced patterns...', count( $patterns ) ) );
		\WP_CLI::log( '' );

		$categorized   = 0;
		$uncategorized = [];

		foreach ( $patterns as $pattern ) {
			$title          = $pattern->post_title;
			$matched_category = $this->match_category( $title );

			if ( $matched_category ) {
				$category_name = PATTERN_CATEGORIES[ $matched_category ]['name'];

				if ( $dry_run ) {
					\WP_CLI::log( sprintf(
						'  [%s] %s',
						$category_name,
						$title
					) );
				} else {
					$result = \wp_set_object_terms(
						$pattern->ID,
						$matched_category,
						'wp_pattern_category'
					);

					if ( \is_wp_error( $result ) ) {
						\WP_CLI::warning( sprintf(
							'Failed to categorize "%s": %s',
							$title,
							$result->get_error_message()
						) );
						continue;
					}

					\WP_CLI::log( sprintf(
						'  [%s] %s',
						$category_name,
						$title
					) );
				}

				$categorized++;
			} else {
				$uncategorized[] = $title;
			}
		}

		\WP_CLI::log( '' );

		// Report uncategorized patterns.
		if ( ! empty( $uncategorized ) ) {
			\WP_CLI::log( 'Uncategorized patterns:' );
			foreach ( $uncategorized as $title ) {
				\WP_CLI::log( sprintf( '  - %s', $title ) );
			}
			\WP_CLI::log( '' );
		}

		// Summary.
		if ( $dry_run ) {
			\WP_CLI::success( sprintf(
				'DRY RUN complete. Would categorize %d of %d patterns.',
				$categorized,
				count( $patterns )
			) );
		} else {
			\WP_CLI::success( sprintf(
				'Categorization complete. %d of %d patterns categorized.',
				$categorized,
				count( $patterns )
			) );
		}
	}

	/**
	 * List all synced patterns with their current categories.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format. Options: table, csv, json. Default: table.
	 *
	 * ## EXAMPLES
	 *
	 *     # List patterns in table format
	 *     wp 84em pattern-categories list
	 *
	 *     # List patterns in JSON format
	 *     wp 84em pattern-categories list --format=json
	 *
	 * @subcommand list
	 * @when after_wp_load
	 *
	 * @param array $_args      Positional arguments (unused).
	 * @param array $assoc_args Associative arguments.
	 */
	public function list_patterns( $_args, $assoc_args ): void {
		$format = $assoc_args['format'] ?? 'table';

		$patterns = \get_posts(
			[
				'post_type'      => 'wp_block',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			]
		);

		if ( empty( $patterns ) ) {
			\WP_CLI::warning( 'No synced patterns found.' );
			return;
		}

		$data = [];

		foreach ( $patterns as $pattern ) {
			$categories = \wp_get_object_terms( $pattern->ID, 'wp_pattern_category', [ 'fields' => 'names' ] );

			if ( \is_wp_error( $categories ) ) {
				$category_list = 'Error';
			} elseif ( empty( $categories ) ) {
				$category_list = '-';
			} else {
				$category_list = implode( ', ', $categories );
			}

			$data[] = [
				'ID'       => $pattern->ID,
				'Title'    => $pattern->post_title,
				'Category' => $category_list,
			];
		}

		\WP_CLI\Utils\format_items( $format, $data, [ 'ID', 'Title', 'Category' ] );
	}

	/**
	 * Ensure all required pattern categories exist.
	 *
	 * @param bool $dry_run Whether this is a dry run.
	 *
	 * @return array<string, int> Map of category slug to term ID.
	 */
	private function ensure_categories_exist( bool $dry_run ): array {
		$category_ids = [];

		foreach ( PATTERN_CATEGORIES as $slug => $config ) {
			$term = \get_term_by( 'slug', $slug, 'wp_pattern_category' );

			if ( $term ) {
				\WP_CLI::log( sprintf( '  Category exists: %s', $config['name'] ) );
				$category_ids[ $slug ] = $term->term_id;
			} else {
				if ( $dry_run ) {
					\WP_CLI::log( sprintf( '  Would create category: %s', $config['name'] ) );
					$category_ids[ $slug ] = 0;
				} else {
					$result = \wp_insert_term(
						$config['name'],
						'wp_pattern_category',
						[ 'slug' => $slug ]
					);

					if ( \is_wp_error( $result ) ) {
						\WP_CLI::warning( sprintf(
							'Failed to create category "%s": %s',
							$config['name'],
							$result->get_error_message()
						) );
					} else {
						\WP_CLI::log( sprintf( '  Created category: %s', $config['name'] ) );
						$category_ids[ $slug ] = $result['term_id'];
					}
				}
			}
		}

		return $category_ids;
	}

	/**
	 * Match a pattern title to a category based on rules.
	 *
	 * @param string $title The pattern title.
	 *
	 * @return string|null The matching category slug or null if no match.
	 */
	private function match_category( string $title ): ?string {
		foreach ( PATTERN_CATEGORIES as $slug => $config ) {
			$rules = $config['rules'];

			// Check exact match rules.
			if ( isset( $rules['exact'] ) ) {
				foreach ( $rules['exact'] as $exact ) {
					if ( \strcasecmp( $title, $exact ) === 0 ) {
						return $slug;
					}
				}
			}

			// Check starts_with rules.
			if ( isset( $rules['starts_with'] ) ) {
				foreach ( $rules['starts_with'] as $prefix ) {
					if ( \stripos( $title, $prefix ) === 0 ) {
						return $slug;
					}
				}
			}

			// Check contains rules.
			if ( isset( $rules['contains'] ) ) {
				foreach ( $rules['contains'] as $substring ) {
					if ( \stripos( $title, $substring ) !== false ) {
						return $slug;
					}
				}
			}
		}

		return null;
	}
}

// Register the command.
\WP_CLI::add_command( '84em pattern-categories', PatternCategoriesCLI::class );
