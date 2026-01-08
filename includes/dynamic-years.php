<?php
/**
 * Dynamic year shortcodes for experience calculations.
 *
 * @package suspended-developer/eightyfourem
 */

namespace EightyFourEM\DynamicYears;

defined( 'ABSPATH' ) || exit;

const WORDPRESS_START_YEAR   = 2012;
const PROGRAMMING_START_YEAR = 1995;

/**
 * Calculate years since a given start year.
 *
 * @param int $start_year The starting year.
 * @return int Years elapsed.
 */
function calculate_years_since( int $start_year ): int {
	return (int) \date( 'Y' ) - $start_year;
}

// Main shortcode: [years_since year="2012"]
\add_shortcode(
	tag: 'years_since',
	callback: function ( array $atts ): string {
		$defaults = [ 'year' => (int) \date( 'Y' ) ];
		$atts     = \shortcode_atts( $defaults, $atts, 'years_since' );

		return (string) calculate_years_since( (int) $atts['year'] );
	}
);

// Alias: [wp_years] - WordPress experience since 2012
\add_shortcode(
	tag: 'wp_years',
	callback: function (): string {
		return (string) calculate_years_since( WORDPRESS_START_YEAR );
	}
);

// Alias: [dev_years] - Programming experience since 1995
\add_shortcode(
	tag: 'dev_years',
	callback: function (): string {
		return (string) calculate_years_since( PROGRAMMING_START_YEAR );
	}
);
