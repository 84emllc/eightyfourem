<?php
/**
 * Accessibility fixes for the 84EM theme.
 *
 * @package EightyFourEM
 */

namespace EightyFourEM\Accessibility;

defined( 'ABSPATH' ) or die;

/**
 * Filter the site logo link to have proper accessible name.
 *
 * The logo link needs an accessible name for screen readers. Using aria-label="Home"
 * on the link and keeping the image decorative (alt="") avoids duplication with
 * the adjacent "84EM" site title text.
 *
 * @param string $html The site logo HTML.
 * @return string Modified HTML with aria-label and empty alt.
 */
\add_filter(
	hook_name: 'get_custom_logo',
	callback: function ( string $html ): string {
		// Add aria-label to the link
		$html = \str_replace( 'class="custom-logo-link"', 'class="custom-logo-link" aria-label="Home"', $html );
		// Set image alt to empty (decorative) since link has aria-label
		$html = \preg_replace( '/alt="[^"]*"/', 'alt=""', $html );
		return $html;
	}
);

/**
 * Add aria-label to search icon link for screen reader accessibility.
 *
 * The search icon link in the header contains only a decorative image with
 * empty alt text. Without an aria-label, screen readers cannot convey the
 * purpose of the link to users.
 *
 * @param string $block_content The block content.
 * @param array  $block         The block data.
 * @return string Modified block content with aria-label.
 */
\add_filter(
	hook_name: 'render_block_core/image',
	callback: function ( string $block_content, array $block ): string {
		// Check if this is the search icon by looking for the search-icon class
		if ( ! isset( $block['attrs']['className'] ) || strpos( $block['attrs']['className'], 'search-icon' ) === false ) {
			return $block_content;
		}

		// Add aria-label to the link within the search icon figure
		return \str_replace( '<a href="#"', '<a href="#" aria-label="Search"', $block_content );
	},
	priority: 10,
	accepted_args: 2
);

/**
 * Remove empty title attributes from navigation links.
 *
 * Empty title="" attributes on links are accessibility anti-patterns.
 * Screen readers may announce "title: blank" which confuses users.
 * This filter removes them from core navigation-link blocks.
 *
 * @param string $block_content The block content.
 * @return string Modified block content without empty title attributes.
 */
\add_filter(
	hook_name: 'render_block_core/navigation-link',
	callback: function ( string $block_content ): string {
		return \str_replace( ' title=""', '', $block_content );
	}
);
