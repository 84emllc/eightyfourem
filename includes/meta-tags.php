<?php

/**
 * SEO Meta Tags
 *
 * Integrates legacy Genesis theme meta tags for SEO:
 * - Custom meta descriptions from _84em_seo_description field
 * - Custom robots meta tags from _84em_noindex field
 * - Enhanced robots directives for better search engine indexing
 *
 * @package EightyFourEM
 */

namespace EightyFourEM;

defined( 'ABSPATH' ) || exit;
\add_action(
    hook_name: 'wp_head',
    callback: function () {
        $_84em_seo_description = get_post_meta( get_the_ID(), '_84em_seo_description', true );
        if ( ! empty( $_84em_seo_description ) ) {
            $_84em_seo_description = do_shortcode( $_84em_seo_description );
            echo sprintf( '<meta name="description" content="%s"/>', esc_attr( wp_strip_all_tags( $_84em_seo_description ) ) ) . PHP_EOL;
        }

        $_84em_noindex = get_post_meta( get_the_ID(), '_84em_noindex', true );
        if ( 1 === (int) $_84em_noindex ) {
            echo '<meta name="robots" content="noindex,nofollow"/>' . PHP_EOL;
        } else {
            echo '<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />' . PHP_EOL;
        }
    },
    priority: 1 );

// removes the default robots meta tag
\remove_action(
    hook_name: 'wp_head',
    callback: 'wp_robots',
    priority: 1 );
