<?php

/**
 * Filters the document title to integrate a custom title set in the post meta (_84em_seo_title).
 *
 * This function retrieves the `_84em_seo_title` meta field for the current post and applies it
 * as the document title if it exists and is not empty. The meta value is sanitized using
 * `wp_strip_all_tags` before replacing the default title.
 *
 * @hook document_title
 *
 * @param  string  $title  The current document title.
 *
 * @return string The filtered document title.
 */

namespace EightyFourEM;

defined( 'ABSPATH' ) || exit;

\add_filter(
    hook_name: 'document_title',
    callback: function ( $title ) {
        // Only apply custom title on singular posts/pages, not on archives or search results
        if ( ! \is_singular() ) {
            return $title;
        }

        $_84em_seo_title = \get_post_meta( \get_the_ID(), '_84em_seo_title', true );
        if ( ! empty( $_84em_seo_title ) ) {
            $title = \wp_strip_all_tags( \do_shortcode( $_84em_seo_title ) );
        }

        return $title;
    },
    priority: 100 );
