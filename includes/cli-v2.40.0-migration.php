<?php
/**
 * WP-CLI migration commands for v2.40.0 database changes.
 *
 * @package EightyFourEM
 */

namespace EightyFourEM\CLI\Migration;

defined( 'ABSPATH' ) or die;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Migration commands for v2.40.0 release.
 *
 * ## EXAMPLES
 *
 *     # Run all v2.40.0 migrations
 *     wp 84em migrate v2-40-0
 *
 *     # Run with dry-run to preview changes
 *     wp 84em migrate v2-40-0 --dry-run
 *
 * @subcommand v2-40-0
 */
\WP_CLI::add_command(
	'84em migrate v2-40-0',
	function ( $args, $assoc_args ) {
		$dry_run = isset( $assoc_args['dry-run'] );

		if ( $dry_run ) {
			\WP_CLI::log( 'Dry run mode - no changes will be made.' );
		}

		// Get site URL for content replacement
		$site_url = \get_site_url();

		// Migration 1: Update Footer Secondary navigation (ID 3102) - remove title attributes
		\WP_CLI::log( '' );
		\WP_CLI::log( '=== Migration 1: Footer Secondary Navigation (ID 3102) ===' );
		\WP_CLI::log( 'Removing title attributes from LinkedIn, Github, and Legal links.' );

		$nav_content = '<!-- wp:navigation-link {"label":"LinkedIn","type":"page","description":"","opensInNewTab":true,"url":"https://www.linkedin.com/in/andrew84em/","title":"","kind":"post-type","attributesForBlocks":{"target":"_blank"},"className":"menu-item menu-item-type-post_type menu-item-object-page is-style-arrow-link","fontSize":"small"} /-->

<!-- wp:navigation-link {"label":"Github","type":"page","description":"","opensInNewTab":true,"url":"https://github.com/84emllc","title":"","kind":"post-type","attributesForBlocks":{"target":"_blank"},"className":"menu-item menu-item-type-post_type menu-item-object-page is-style-arrow-link","fontSize":"small"} /-->

<!-- wp:navigation-link {"label":"Legal","type":"page","description":"","url":"' . $site_url . '/legal/","title":"","kind":"post-type","className":"menu-item menu-item-type-post_type menu-item-object-page is-style-default","fontSize":"small"} /-->

<!-- wp:navigation-link {"label":"Sitemap","type":"page","id":6964,"url":"' . $site_url . '/sitemap/","kind":"post-type"} /-->';

		if ( ! $dry_run ) {
			$result = \wp_update_post(
				[
					'ID'           => 3102,
					'post_content' => $nav_content,
				]
			);
			if ( \is_wp_error( $result ) ) {
				\WP_CLI::error( 'Failed to update navigation 3102: ' . $result->get_error_message() );
			}
			\WP_CLI::success( 'Updated navigation block 3102.' );
		} else {
			\WP_CLI::log( 'Would update navigation block 3102.' );
		}

		// Migration 2: Update Front Page (ID 2) - new hero with background image
		\WP_CLI::log( '' );
		\WP_CLI::log( '=== Migration 2: Front Page Hero (ID 2) ===' );
		\WP_CLI::log( 'Updating hero to use laptop-desk background image (ID 639).' );

		$hero_content = '<!-- wp:group {"metadata":{"name":"hero"},"align":"full","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"},"margin":{"top":"0","bottom":"0"}}},"backgroundColor":"contrast","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull has-contrast-background-color has-background" style="margin-top:0;margin-bottom:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0"><!-- wp:group {"style":{"background":{"backgroundImage":{"url":"' . $site_url . '/wp-content/uploads/2020/02/378267091-huge.jpg","id":639,"source":"file","title":"378267091-huge"},"backgroundSize":"cover","backgroundPosition":"50% 50%"},"dimensions":{"minHeight":"500px"},"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="min-height:500px;padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--30)"><!-- wp:group {"align":"wide","style":{"color":{"background":"#1e1e1e8c"},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"1280px","justifyContent":"left"}} -->
<div class="wp-block-group alignwide has-background" style="background-color:#1e1e1e8c;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)"><!-- wp:post-title {"level":1,"className":"is-style-default is-glow","style":{"elements":{"link":{"color":{"text":"#ffffff"}}},"typography":{"fontStyle":"normal","fontWeight":"600"},"color":{"text":"#ffffff"},"spacing":{"padding":{"top":"0","bottom":"0"}}},"fontSize":"xx-large","fontFamily":"heading"} /-->

<!-- wp:heading {"className":"is-glow is-style-default","style":{"elements":{"link":{"color":{"text":"#ffffff"}}},"typography":{"fontStyle":"normal","fontWeight":"600","lineHeight":"1.3"},"color":{"text":"#ffffff"},"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|10"}}},"fontSize":"large","fontFamily":"heading"} -->
<h2 class="wp-block-heading is-glow is-style-default has-text-color has-link-color has-heading-font-family has-large-font-size" style="color:#ffffff;padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--10);font-style:normal;font-weight:600;line-height:1.3">Custom Plugins, Agency Partnerships, Security, Consulting, and Maintenance</h2>
<!-- /wp:heading -->

<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}},"fontSize":"medium","layout":{"type":"flex","justifyContent":"left"}} -->
<div class="wp-block-buttons has-custom-font-size has-medium-font-size" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--20)"><!-- wp:button {"style":{"border":{"radius":{"topLeft":"0px","topRight":"30px","bottomLeft":"30px","bottomRight":"0px"}},"shadow":"var:preset|shadow|crisp"},"fontSize":"large"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-large-font-size has-custom-font-size wp-element-button" href="' . $site_url . '/contact/" style="border-top-left-radius:0px;border-top-right-radius:30px;border-bottom-left-radius:30px;border-bottom-right-radius:0px;box-shadow:var(--wp--preset--shadow--crisp)">Free Consult</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"metadata":{"name":"plugins"},"align":"full","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}},"backgroundColor":"base","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-base-background-color has-background" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)"><!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"0","left":"var:preset|spacing|60"}}}} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"verticalAlignment":"center","width":"50%","style":{"spacing":{"blockGap":"var:preset|spacing|10","padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}}} -->
<div class="wp-block-column is-vertically-aligned-center" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);flex-basis:50%"><!-- wp:heading {"textAlign":"left","className":"is-style-default","style":{"typography":{"fontStyle":"normal","fontWeight":"600"}},"fontSize":"x-large","fontFamily":"heading"} -->
<h2 class="wp-block-heading has-text-align-left is-style-default has-heading-font-family has-x-large-font-size" style="font-style:normal;font-weight:600"><a href="' . $site_url . '/services/custom-wordpress-plugin-development/" data-type="page" data-id="2909">Custom Development<strong><span aria-hidden="true">→</span></strong></a></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"left","fontSize":"large"} -->
<p class="has-text-align-left has-large-font-size">Built for businesses and enterprises that need reliable, scalable, custom solutions to solve business challenges</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"50%","style":{"spacing":{"blockGap":"var:preset|spacing|10","padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}}} -->
<div class="wp-block-column is-vertically-aligned-center" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);flex-basis:50%"><!-- wp:block {"ref":9332} /-->

<!-- wp:paragraph {"align":"center","fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size"><a href="' . $site_url . '/testimonials/" data-type="page" data-id="986"><em>What Our Clients Say<span aria-hidden="true">→</span></em></a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:block {"ref":3024} /-->

<!-- wp:group {"metadata":{"name":"white label"},"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30","right":"var:preset|spacing|30"},"margin":{"top":"0","bottom":"0"}},"color":{"background":"#ffffff"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-background" style="background-color:#ffffff;margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)"><!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"0","left":"var:preset|spacing|60"}}}} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"verticalAlignment":"center","width":"50%","className":"animated fadeIn","style":{"spacing":{"blockGap":"var:preset|spacing|10","padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}}} -->
<div class="wp-block-column is-vertically-aligned-center animated fadeIn" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);flex-basis:50%"><!-- wp:heading {"textAlign":"left","className":"is-style-default","style":{"typography":{"fontStyle":"normal","fontWeight":"600"},"elements":{"link":{"color":{"text":"var:preset|color|custom-color-1"}}}},"textColor":"contrast","fontSize":"x-large","fontFamily":"heading"} -->
<h2 class="wp-block-heading has-text-align-left is-style-default has-contrast-color has-text-color has-link-color has-heading-font-family has-x-large-font-size" style="font-style:normal;font-weight:600"><a href="' . $site_url . '/services/wordpress-development-for-agencies/">Agency Partnerships<span aria-hidden="true">→</span></a></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"left","fontSize":"large"} -->
<p class="has-text-align-left has-large-font-size">Your outsourced development partner for overflow projects and specialized expertise. White-label, client-facing, whatever works for you. Scale without hiring.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"50%","className":"animated fadeIn","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}}} -->
<div class="wp-block-column is-vertically-aligned-center animated fadeIn" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);flex-basis:50%"><!-- wp:block {"ref":9333} /--></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:block {"ref":3021} /-->

<!-- wp:group {"metadata":{"name":"experience"},"align":"full","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}},"color":{"background":"#ffffff"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-background" style="background-color:#ffffff;margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)"><!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"0","left":"var:preset|spacing|30"}}}} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"verticalAlignment":"center","width":"70%","className":"animated fadeIn","style":{"spacing":{"blockGap":"var:preset|spacing|10","padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}}} -->
<div class="wp-block-column is-vertically-aligned-center animated fadeIn" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);flex-basis:70%"><!-- wp:heading {"textAlign":"left","className":"is-style-default","style":{"typography":{"fontStyle":"normal","fontWeight":"600"},"elements":{"link":{"color":{"text":"var:preset|color|custom-color-1"}}}},"textColor":"contrast","fontSize":"x-large","fontFamily":"heading"} -->
<h2 class="wp-block-heading has-text-align-left is-style-default has-contrast-color has-text-color has-link-color has-heading-font-family has-x-large-font-size" style="font-style:normal;font-weight:600"><a href="' . $site_url . '/case-studies/" data-type="link" data-id="' . $site_url . '/case-studies/">[dev_years] years of Experience<span aria-hidden="true">→</span></a></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"left","textColor":"contrast","fontSize":"large"} -->
<p class="has-text-align-left has-contrast-color has-text-color has-large-font-size">Building for the web since 1995. WordPress expertise since 2012. Partnering with digital agencies, banking, non-profits, education, fintech, wellness, advertising, financial services, healthcare, cryptocurrency clients, and more. </p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"top","width":"30%","className":"animated fadeIn","style":{"spacing":{"blockGap":"var:preset|spacing|10","padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}}} -->
<div class="wp-block-column is-vertically-aligned-top animated fadeIn" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);flex-basis:30%"><!-- wp:image {"id":882,"sizeSlug":"medium","linkDestination":"custom","attributesForBlocks":{"style":"max-width:300px"},"className":"is-style-rounded","style":{"border":{"radius":"25px"},"color":{"duotone":"unset"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
<figure class="wp-block-image size-medium has-custom-border is-style-rounded" style="margin-top:0;margin-bottom:0;max-width:300px"><img src="' . $site_url . '/wp-content/uploads/2021/07/beard-headshot-300x300.jpg" alt="" class="wp-image-882" style="border-radius:25px"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:block {"ref":3019} /-->

<!-- wp:group {"metadata":{"name":"services"},"align":"full","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}},"color":{"background":"#ffffff"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-background" style="background-color:#ffffff;margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)"><!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"0","left":"var:preset|spacing|30"}}}} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"verticalAlignment":"center","width":"50%","className":"animated fadeIn","style":{"spacing":{"blockGap":"var:preset|spacing|10","padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}}} -->
<div class="wp-block-column is-vertically-aligned-center animated fadeIn" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);flex-basis:50%"><!-- wp:heading {"textAlign":"left","className":"is-style-default","style":{"typography":{"fontStyle":"normal","fontWeight":"600"},"elements":{"link":{"color":{"text":"var:preset|color|custom-color-1"}}},"spacing":{"margin":{"top":"var:preset|spacing|10"}}},"textColor":"contrast","fontSize":"x-large","fontFamily":"heading"} -->
<h2 class="wp-block-heading has-text-align-left is-style-default has-contrast-color has-text-color has-link-color has-heading-font-family has-x-large-font-size" style="margin-top:var(--wp--preset--spacing--10);font-style:normal;font-weight:600"><a href="' . $site_url . '/services/">Explore All Services<span aria-hidden="true">→</span></a></h2>
<!-- /wp:heading -->

<!-- wp:image {"id":6620,"scale":"cover","sizeSlug":"medium","linkDestination":"none","className":"uag-hide-tab uag-hide-mob"} -->
<figure class="wp-block-image size-medium uag-hide-tab uag-hide-mob"><img src="' . $site_url . '/wp-content/uploads/2025/11/84m-services-migrations-how-300x300.png" alt="" class="wp-image-6620" style="object-fit:cover"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"50%","className":"animated fadeIn","style":{"spacing":{"blockGap":"var:preset|spacing|10","padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}}} -->
<div class="wp-block-column is-vertically-aligned-center animated fadeIn" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);flex-basis:50%"><!-- wp:block {"ref":5031} /--></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:paragraph -->
<p></p>
<!-- /wp:paragraph -->';

		if ( ! $dry_run ) {
			$result = \wp_update_post(
				[
					'ID'           => 2,
					'post_content' => $hero_content,
				]
			);
			if ( \is_wp_error( $result ) ) {
				\WP_CLI::error( 'Failed to update front page: ' . $result->get_error_message() );
			}
			\WP_CLI::success( 'Updated front page (ID 2) with new hero.' );
		} else {
			\WP_CLI::log( 'Would update front page (ID 2) with new hero.' );
		}

		// Migration 3: Update Reusable Hero Block (ID 2918) - new hero with background image
		\WP_CLI::log( '' );
		\WP_CLI::log( '=== Migration 3: Reusable Hero Block (ID 2918) ===' );
		\WP_CLI::log( 'Updating 84EM Hero reusable block with laptop-desk background image (ID 639).' );

		$hero_block_content = '<!-- wp:group {"metadata":{"name":"84EM Hero"},"align":"full","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"},"margin":{"top":"0","bottom":"0"}}},"backgroundColor":"contrast","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull has-contrast-background-color has-background" style="margin-top:0;margin-bottom:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0"><!-- wp:group {"style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"}},"background":{"backgroundImage":{"url":"' . $site_url . '/wp-content/uploads/2020/02/378267091-huge.jpg","id":639,"source":"file","title":"laptop-desk"},"backgroundSize":"cover","backgroundPosition":"50% 50%"},"dimensions":{"minHeight":"500px"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="min-height:500px;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0"><!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"color":{"background":"#1e1e1e8c"},"layout":{"selfStretch":"fit","flexSize":null}},"layout":{"type":"constrained","contentSize":"600px","justifyContent":"left"}} -->
<div class="wp-block-group has-background" style="background-color:#1e1e1e8c;padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--40)"><!-- wp:post-title {"level":1,"className":"is-style-default","style":{"elements":{"link":{"color":{"text":"#ffffff"}}},"typography":{"fontStyle":"normal","fontWeight":"600"},"color":{"text":"#ffffff"}},"fontSize":"xx-large","fontFamily":"heading"} /-->

<!-- wp:heading {"level":2,"style":{"elements":{"link":{"color":{"text":"#ffffffcc"}}},"color":{"text":"#ffffffcc"},"typography":{"fontStyle":"normal","fontWeight":"400"}},"fontSize":"medium"} -->
<h2 class="wp-block-heading has-text-color has-link-color has-medium-font-size" style="color:#ffffffcc;font-style:normal;font-weight:400">Custom Plugins, Agency Partnerships, Security, Consulting, and Maintenance</h2>
<!-- /wp:heading -->

<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--40)"><!-- wp:button {"style":{"border":{"radius":{"topLeft":"0px","topRight":"30px","bottomLeft":"30px","bottomRight":"0px"}},"shadow":"var:preset|shadow|crisp"},"fontSize":"large"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-large-font-size wp-element-button" href="/contact/" style="border-top-left-radius:0px;border-top-right-radius:30px;border-bottom-left-radius:30px;border-bottom-right-radius:0px;box-shadow:var(--wp--preset--shadow--crisp)">Free Consult</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->';

		if ( ! $dry_run ) {
			$result = \wp_update_post(
				[
					'ID'           => 2918,
					'post_content' => $hero_block_content,
				]
			);
			if ( \is_wp_error( $result ) ) {
				\WP_CLI::error( 'Failed to update hero block 2918: ' . $result->get_error_message() );
			}
			\WP_CLI::success( 'Updated reusable hero block 2918.' );
		} else {
			\WP_CLI::log( 'Would update reusable hero block 2918.' );
		}

		\WP_CLI::log( '' );
		\WP_CLI::success( 'v2.40.0 migration complete!' );
	}
);
