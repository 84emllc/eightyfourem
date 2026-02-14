<?php

/**
 * Automatically generate and save schema.org structured data for posts and pages.
 *
 * The `save_post` action is used to hook into post save/update events, ensuring that structured data
 * is generated and saved only for a given set of post types. The schema data follows the guidelines
 * of schema.org
 *
 * The following high-level operations are performed:
 * - Preventing execution during autosave or post revision updates.
 * - Verifying user permissions to edit the current post.
 * - Checking the post type to determine if schema generation applies (post, page).
 * - Generating structured data for supported post types based on schema.org guidelines with specific attributes.
 * - Adding breadcrumb trail information for enhanced schema context.
 * - Providing additional attributes or keywords relevant to the content type, such as author, categories, and tags.
 *
 * For `post` post types:
 * - Adds author and publisher information.
 * - Includes associated categories and tags as keywords.
 *
 * For `page` post types:
 * - Custom handling for specific page slugs like "contact" or "services" to provide more contextual schema.
 *
 * @hook save_post
 *
 * @param  int  $post_id  The ID of the post being saved.
 *
 * @return void This function does not return a value. It halts execution if certain criteria (e.g., autosave, permissions)
 *              are not met, or it proceeds to generate schema and update database as needed.
 */

namespace EightyFourEM;

defined( 'ABSPATH' ) || exit;

/**
 * Extract FAQ items from WordPress core accordion blocks.
 *
 * Parses page content to find accordion blocks and extracts question/answer pairs.
 *
 * @param int $page_id The page ID containing accordion blocks.
 *
 * @return array Array of FAQ data with keys: question, answer.
 */
function extract_faqs_from_accordion( int $page_id ): array {
	$page = \get_post( $page_id );
	if ( ! $page || empty( $page->post_content ) ) {
		return [];
	}

	$faqs    = [];
	$content = $page->post_content;

	// Match each accordion-item block.
	$item_pattern = '/<!-- wp:accordion-item -->(.*?)<!-- \/wp:accordion-item -->/s';
	if ( ! \preg_match_all( $item_pattern, $content, $items ) ) {
		return [];
	}

	foreach ( $items[1] as $item ) {
		// Extract question from accordion-heading toggle-title span.
		$question = '';
		if ( \preg_match( '/wp-block-accordion-heading__toggle-title["\']?>([^<]+)</s', $item, $q_match ) ) {
			$question = \trim( $q_match[1] );
		}

		// Extract answer from accordion-panel content.
		$answer = '';
		if ( \preg_match( '/<!-- wp:accordion-panel -->(.*?)<!-- \/wp:accordion-panel -->/s', $item, $panel_match ) ) {
			// Get the inner HTML content, strip block comments but keep HTML structure.
			$panel_content = $panel_match[1];
			// Remove block comments.
			$panel_content = \preg_replace( '/<!-- \/?wp:[^>]+ -->/s', '', $panel_content );
			// Remove the outer panel div wrapper.
			$panel_content = \preg_replace( '/<div[^>]*class="[^"]*wp-block-accordion-panel[^"]*"[^>]*>(.*)<\/div>/s', '$1', $panel_content );
			// Normalize whitespace: collapse multiple newlines/spaces to single space between tags.
			$panel_content = \preg_replace( '/>\s+</', '><', $panel_content );
			// Clean up but preserve allowed HTML tags per Google guidelines.
			$answer = \trim( $panel_content );
		}

		if ( ! empty( $question ) && ! empty( $answer ) ) {
			// Strip all HTML attributes to avoid quote escaping issues in JSON.
			// Keep only the tag names per Google's allowed tags.
			$answer = \preg_replace( '/<(\w+)[^>]*>/', '<$1>', $answer );
			// Convert special characters to HTML entities for safe JSON encoding.
			$answer = \str_replace(
				[ \html_entity_decode( '&ldquo;' ), \html_entity_decode( '&rdquo;' ), \html_entity_decode( '&lsquo;' ), \html_entity_decode( '&rsquo;' ), \html_entity_decode( '&mdash;' ), \html_entity_decode( '&ndash;' ), \html_entity_decode( '&nbsp;' ) ],
				[ '&quot;', '&quot;', "'", "'", '-', '-', ' ' ],
				$answer
			);
			// Convert remaining double quotes to HTML entities.
			$answer = \str_replace( '"', '&quot;', $answer );
			$faqs[] = [
				'question' => $question,
				'answer'   => $answer,
			];
		}
	}

	return $faqs;
}

/**
 * Extract testimonials from a page's Easy Testimonial Blocks (ETB) or reusable blocks.
 *
 * Parses page content to find ETB grid-item blocks or reusable block references,
 * and extracts quote/attribution data from testimonial blocks.
 *
 * @param int $page_id The page ID containing testimonial blocks.
 *
 * @return array Array of testimonial data with keys: quote, attribution, date, rating.
 */
function extract_testimonials_from_page( int $page_id ): array {
	$page = \get_post( $page_id );
	if ( ! $page || empty( $page->post_content ) ) {
		return [];
	}

	$testimonials = [];
	$content      = $page->post_content;

	// Try ETB (Easy Testimonial Blocks) grid-item blocks first.
	$etb_pattern = '/<!-- wp:etb\/grid-item \{([^}]+)}/';
	if ( \preg_match_all( $etb_pattern, $content, $etb_matches ) ) {
		foreach ( $etb_matches[1] as $json_attrs ) {
			// Parse the JSON attributes from the block comment.
			$attrs = \json_decode( '{' . $json_attrs . '}', true );
			if ( ! $attrs ) {
				continue;
			}

			$quote = $attrs['testimonial'] ?? '';
			// Clean HTML tags from testimonial text (e.g., <br> tags).
			$quote = \wp_strip_all_tags( $quote );

			// Build attribution from available fields.
			$attribution_parts = [];
			if ( ! empty( $attrs['reviewerName'] ) ) {
				$attribution_parts[] = $attrs['reviewerName'];
			}
			if ( ! empty( $attrs['reviewerTitle'] ) ) {
				$attribution_parts[] = $attrs['reviewerTitle'];
			}
			if ( ! empty( $attrs['reviewerCompany'] ) ) {
				$attribution_parts[] = $attrs['reviewerCompany'];
			}
			$attribution = \implode( ', ', $attribution_parts );

			if ( ! empty( $quote ) && ! empty( $attribution ) ) {
				$testimonials[] = [
					'quote'       => $quote,
					'attribution' => $attribution,
					'date'        => \get_the_date( 'c', $page ),
					'rating'      => 5,
				];
			}
		}
	}

	return $testimonials;
}

\add_action(
    hook_name: 'wp_after_insert_post',
    callback: function ( $post_id, $post, $update ) {
        // Prevent autosave and revision updates
        if ( \wp_is_post_autosave( $post_id ) || \wp_is_post_revision( $post_id ) ) {
            return;
        }

        // Check user permissions (skip for WP-CLI)
        if ( ! \defined( 'WP_CLI' ) && ! \current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Get post data
        $post      = \get_post( $post_id );
        $post_type = \get_post_type( $post_id );
        $post_url  = \get_permalink( $post_id );
        $site_url  = \get_site_url();

        // Only generate schema for posts, pages
        if ( ! \in_array( $post_type, [ 'post', 'page' ] ) ) {
            return;
        }

        // Base schema structure
        $schema = [
            '@context'      => 'https://schema.org',
            '@type'         => 'WebPage',
            '@id'           => $post_url . '#webpage',
            'url'           => $post_url,
            'name'          => \get_the_title( $post_id ),
            'description'   => \get_post_meta( $post_id, '_84em_seo_description', true ) ?: \wp_trim_words( \strip_tags( $post->post_content ), 25 ),
            'inLanguage'    => 'en-US',
            'datePublished' => \get_the_date( 'c', $post_id ),
            'dateModified'  => \get_the_modified_date( 'c', $post_id ),
            'isPartOf'      => [
                '@type' => 'WebSite',
                '@id'   => $site_url . '/#website',
                'url'   => $site_url,
                'name'  => '84EM',
            ],
            'breadcrumb'    => [
                '@type'           => 'BreadcrumbList',
                'itemListElement' => [],
            ],
        ];

        // Generate breadcrumbs
        $breadcrumbs = [
            [
                '@type'    => 'ListItem',
                'position' => 1,
                'name'     => 'Home',
                'item'     => $site_url,
            ],
        ];

        // Add post type specific breadcrumb
        if ( $post_type === 'post' ) {
            $breadcrumbs[] = [
                '@type'    => 'ListItem',
                'position' => 2,
                'name'     => 'Blog',
                'item'     => \get_permalink( \get_option( 'page_for_posts' ) ),
            ];
            $breadcrumbs[] = [
                '@type'    => 'ListItem',
                'position' => 3,
                'name'     => \get_the_title( $post_id ),
                'item'     => $post_url,
            ];
        } else {
            $breadcrumbs[] = [
                '@type'    => 'ListItem',
                'position' => 2,
                'name'     => \get_the_title( $post_id ),
                'item'     => $post_url,
            ];
        }

        $schema['breadcrumb']['itemListElement'] = $breadcrumbs;

        // Post type specific schema enhancements
        switch ( $post_type ) {
            case 'post':
                $schema['@type']     = 'BlogPosting';
                $schema['author']    = [
                    '@type' => 'Person',
                    'name'  => \get_the_author_meta( 'display_name', $post->post_author ),
                    'url'   => \get_author_posts_url( $post->post_author ),
                ];
                $schema['publisher'] = [
                    '@type' => 'Organization',
                    '@id'   => $site_url . '/#organization',
                    'name'  => '84EM',
                ];

                // Add categories and tags
                $categories = \get_the_category( $post_id );
                if ( ! empty( $categories ) ) {
                    $schema['articleSection'] = $categories[0]->name;
                }

                $tags = \get_the_tags( $post_id );
                if ( ! empty( $tags ) ) {
                    $schema['keywords'] = \implode( ', ', \wp_list_pluck( $tags, 'name' ) );
                }

                break;

            case 'page':
                // Handle specific pages
                $page_slug = $post->post_name;

                switch ( $page_slug ) {
                    case 'contact':
                        $schema['@type']      = 'ContactPage';
                        $schema['mainEntity'] = [
                            '@type'        => 'Organization',
                            '@id'          => $site_url . '/#organization',
                            'name'         => '84EM',
                            'contactPoint' => [
                                '@type'             => 'ContactPoint',
                                'contactType'       => 'customer service',
                                'areaServed'        => 'Worldwide',
                                'availableLanguage' => 'English',
                            ],
                        ];
                        break;

                    case 'services':
                        $schema['mainEntity'] = [
                            '@type'           => 'Organization',
                            '@id'             => $site_url . '/#organization',
                            'hasOfferCatalog' => [
                                '@type'           => 'OfferCatalog',
                                'name'            => 'Web Engineering & Development Services',
                                'itemListElement' => [
                                    [
                                        '@type'       => 'Offer',
                                        'itemOffered' => [
                                            '@type'    => 'Service',
                                            'name'     => 'Custom WordPress Plugin Development',
                                            'url'      => $site_url . '/services/custom-wordpress-plugin-development/',
                                            'provider' => [
                                                '@type' => 'Organization',
                                                '@id'   => $site_url . '/#organization',
                                            ],
                                        ],
                                    ],
                                    [
                                        '@type'       => 'Offer',
                                        'itemOffered' => [
                                            '@type'    => 'Service',
                                            'name'     => 'White Label WordPress Development for Agencies',
                                            'url'      => $site_url . '/services/white-label-wordpress-development-for-agencies/',
                                            'provider' => [
                                                '@type' => 'Organization',
                                                '@id'   => $site_url . '/#organization',
                                            ],
                                        ],
                                    ],
                                    [
                                        '@type'       => 'Offer',
                                        'itemOffered' => [
                                            '@type'    => 'Service',
                                            'name'     => 'WordPress Consulting & Strategy',
                                            'url'      => $site_url . '/services/wordpress-consulting-strategy/',
                                            'provider' => [
                                                '@type' => 'Organization',
                                                '@id'   => $site_url . '/#organization',
                                            ],
                                        ],
                                    ],
                                    [
                                        '@type'       => 'Offer',
                                        'itemOffered' => [
                                            '@type'    => 'Service',
                                            'name'     => 'WordPress Maintenance & Support',
                                            'url'      => $site_url . '/services/wordpress-maintenance-support/',
                                            'provider' => [
                                                '@type' => 'Organization',
                                                '@id'   => $site_url . '/#organization',
                                            ],
                                        ],
                                    ],
                                    [
                                        '@type'       => 'Offer',
                                        'itemOffered' => [
                                            '@type'    => 'Service',
                                            'name'     => 'AI-Enhanced WordPress Development',
                                            'url'      => $site_url . '/services/ai-enhanced-wordpress-development/',
                                            'provider' => [
                                                '@type' => 'Organization',
                                                '@id'   => $site_url . '/#organization',
                                            ],
                                        ],
                                    ],
                                    [
                                        '@type'       => 'Offer',
                                        'itemOffered' => [
                                            '@type'    => 'Service',
                                            'name'     => 'AI Integration & Development',
                                            'url'      => $site_url . '/services/ai-integration-development/',
                                            'provider' => [
                                                '@type' => 'Organization',
                                                '@id'   => $site_url . '/#organization',
                                            ],
                                        ],
                                    ],
                                    [
                                        '@type'       => 'Offer',
                                        'itemOffered' => [
                                            '@type'    => 'Service',
                                            'name'     => 'Data Migrations & Platform Transfers',
                                            'url'      => $site_url . '/services/',
                                            'provider' => [
                                                '@type' => 'Organization',
                                                '@id'   => $site_url . '/#organization',
                                            ],
                                        ],
                                    ],
                                    [
                                        '@type'       => 'Offer',
                                        'itemOffered' => [
                                            '@type'    => 'Service',
                                            'name'     => 'Security & Troubleshooting',
                                            'url'      => $site_url . '/services/',
                                            'provider' => [
                                                '@type' => 'Organization',
                                                '@id'   => $site_url . '/#organization',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ];
                        break;

                    case 'about':
                        $schema['mainEntity'] = [
                            '@type'      => 'Person',
                            'name'       => 'Andrew Miller',
                            'jobTitle'   => 'Principal Engineer & Consultant',
                            'worksFor'   => [
                                '@type' => 'Organization',
                                '@id'   => $site_url . '/#organization',
                            ],
                            'knowsAbout' => [
                                'WordPress Plugin Development',
                                'Custom WordPress Development',
                                'PHP Programming',
                                'React Development',
                                'Python Development',
                                'API Integration',
                                'AI Integration',
                                'Headless CMS Architecture',
                                'Hugo Static Sites',
                                'CI/CD Pipelines',
                                'GitHub Actions',
                                'WordPress Security',
                                'Security Audits',
                                'Data Migration',
                                'Platform Transfers',
                                'Meilisearch',
                                'Server Provisioning',
                                'DevOps',
                                'White Label Development',
                                'WordPress Maintenance',
                                'WordPress Consulting',
                            ],
                        ];
                        break;

                    case 'pricing':
                        // Add Service schema with comprehensive pricing information
                        $schema['mainEntity'] = [
                            '@type'            => 'Service',
                            '@id'              => $post_url . '#service',
                            'serviceType'      => 'Web Engineering & Development',
                            'name'             => 'Web Engineering & Development Services',
                            'description'      => 'Professional web engineering including custom development, plugins, AI integrations, performance optimization, and maintenance',
                            'provider'         => [
                                '@type' => 'Organization',
                                '@id'   => $site_url . '/#organization',
                                'name'  => '84EM',
                                'url'   => $site_url,
                            ],
                            'areaServed'       => [
                                '@type' => 'Country',
                                'name'  => 'United States',
                            ],
                            'hasOfferCatalog'  => [
                                '@type'           => 'OfferCatalog',
                                'name'            => 'Web Engineering & Development Services',
                                'itemListElement' => [
                                    [
                                        '@type'              => 'Offer',
                                        'itemOffered'        => [
                                            '@type'       => 'Service',
                                            'name'        => 'Custom WordPress Plugin Development',
                                            'description' => 'Custom WordPress plugin development tailored to your specific needs',
                                            'url'         => $site_url . '/services/custom-wordpress-plugin-development/',
                                            'provider'    => [
                                                '@type' => 'Organization',
                                                '@id'   => $site_url . '/#organization',
                                            ],
                                        ],
                                        'priceSpecification' => [
                                            '@type'        => 'UnitPriceSpecification',
                                            'price'        => '150',
                                            'priceCurrency' => 'USD',
                                            'unitText'     => 'HOUR',
                                        ],
                                        'availability'       => 'https://schema.org/InStock',
                                    ],
                                    [
                                        '@type'              => 'Offer',
                                        'itemOffered'        => [
                                            '@type'       => 'Service',
                                            'name'        => 'White Label WordPress Development for Agencies',
                                            'description' => 'White label WordPress development services for agencies and resellers',
                                            'url'         => $site_url . '/services/white-label-wordpress-development-for-agencies/',
                                            'provider'    => [
                                                '@type' => 'Organization',
                                                '@id'   => $site_url . '/#organization',
                                            ],
                                        ],
                                        'priceSpecification' => [
                                            '@type'        => 'UnitPriceSpecification',
                                            'price'        => '150',
                                            'priceCurrency' => 'USD',
                                            'unitText'     => 'HOUR',
                                        ],
                                        'availability'       => 'https://schema.org/InStock',
                                    ],
                                    [
                                        '@type'              => 'Offer',
                                        'itemOffered'        => [
                                            '@type'       => 'Service',
                                            'name'        => 'WordPress Consulting & Strategy',
                                            'description' => 'Expert WordPress consulting and strategic planning services',
                                            'url'         => $site_url . '/services/wordpress-consulting-strategy/',
                                            'provider'    => [
                                                '@type' => 'Organization',
                                                '@id'   => $site_url . '/#organization',
                                            ],
                                        ],
                                        'priceSpecification' => [
                                            '@type'        => 'UnitPriceSpecification',
                                            'price'        => '150',
                                            'priceCurrency' => 'USD',
                                            'unitText'     => 'HOUR',
                                        ],
                                        'availability'       => 'https://schema.org/InStock',
                                    ],
                                    [
                                        '@type'              => 'Offer',
                                        'itemOffered'        => [
                                            '@type'       => 'Service',
                                            'name'        => 'WordPress Maintenance & Support',
                                            'description' => 'Ongoing WordPress maintenance, updates, and technical support',
                                            'url'         => $site_url . '/services/wordpress-maintenance-support/',
                                            'provider'    => [
                                                '@type' => 'Organization',
                                                '@id'   => $site_url . '/#organization',
                                            ],
                                        ],
                                        'priceSpecification' => [
                                            '@type'        => 'UnitPriceSpecification',
                                            'price'        => '150',
                                            'priceCurrency' => 'USD',
                                            'unitText'     => 'HOUR',
                                        ],
                                        'availability'       => 'https://schema.org/InStock',
                                    ],
                                    [
                                        '@type'              => 'Offer',
                                        'itemOffered'        => [
                                            '@type'       => 'Service',
                                            'name'        => 'AI-Enhanced WordPress Development',
                                            'description' => 'WordPress development enhanced with AI tools and automation',
                                            'url'         => $site_url . '/services/ai-enhanced-wordpress-development/',
                                            'provider'    => [
                                                '@type' => 'Organization',
                                                '@id'   => $site_url . '/#organization',
                                            ],
                                        ],
                                        'priceSpecification' => [
                                            '@type'        => 'UnitPriceSpecification',
                                            'price'        => '150',
                                            'priceCurrency' => 'USD',
                                            'unitText'     => 'HOUR',
                                        ],
                                        'availability'       => 'https://schema.org/InStock',
                                    ],
                                    [
                                        '@type'              => 'Offer',
                                        'itemOffered'        => [
                                            '@type'       => 'Service',
                                            'name'        => 'AI Integration & Development',
                                            'description' => 'Custom AI integrations, automations, and tools that solve real business problems',
                                            'url'         => $site_url . '/services/ai-integration-development/',
                                            'provider'    => [
                                                '@type' => 'Organization',
                                                '@id'   => $site_url . '/#organization',
                                            ],
                                        ],
                                        'priceSpecification' => [
                                            '@type'        => 'UnitPriceSpecification',
                                            'price'        => '150',
                                            'priceCurrency' => 'USD',
                                            'unitText'     => 'HOUR',
                                        ],
                                        'availability'       => 'https://schema.org/InStock',
                                    ],
                                    [
                                        '@type'              => 'Offer',
                                        'itemOffered'        => [
                                            '@type'       => 'Service',
                                            'name'        => 'After-Hours Development',
                                            'description' => 'After-hours and emergency development services',
                                            'provider'    => [
                                                '@type' => 'Organization',
                                                '@id'   => $site_url . '/#organization',
                                            ],
                                        ],
                                        'priceSpecification' => [
                                            '@type'        => 'UnitPriceSpecification',
                                            'price'        => '225',
                                            'priceCurrency' => 'USD',
                                            'unitText'     => 'HOUR',
                                        ],
                                        'availability'       => 'https://schema.org/InStock',
                                    ],
                                ],
                            ],
                        ];

                        // Add 'about' property to WebPage schema to reference the Service
                        $schema['about'] = [
                            '@id' => $post_url . '#service',
                        ];
                        break;

                    case 'custom-wordpress-plugin-development':
                        $schema['mainEntity'] = [
                            '@type'              => 'Service',
                            '@id'                => $post_url . '#service',
                            'serviceType'        => 'Custom WordPress Plugin Development',
                            'name'               => 'Custom WordPress Plugin Development',
                            'description'        => 'Custom WordPress plugin development tailored to your specific business requirements, from simple integrations to complex multi-system architectures',
                            'provider'           => [
                                '@type' => 'Organization',
                                '@id'   => $site_url . '/#organization',
                                'name'  => '84EM',
                                'url'   => $site_url,
                            ],
                            'areaServed'         => [
                                '@type' => 'Country',
                                'name'  => 'United States',
                            ],
                            'offers'             => [
                                [
                                    '@type'              => 'Offer',
                                    'name'               => 'Standard Rate',
                                    'priceSpecification' => [
                                        '@type'         => 'UnitPriceSpecification',
                                        'price'         => '150',
                                        'priceCurrency' => 'USD',
                                        'unitText'      => 'HOUR',
                                    ],
                                    'availability'       => 'https://schema.org/InStock',
                                ],
                                [
                                    '@type'              => 'Offer',
                                    'name'               => 'After-Hours Rate',
                                    'description'        => 'After-hours and emergency development services',
                                    'priceSpecification' => [
                                        '@type'         => 'UnitPriceSpecification',
                                        'price'         => '225',
                                        'priceCurrency' => 'USD',
                                        'unitText'      => 'HOUR',
                                    ],
                                    'availability'       => 'https://schema.org/InStock',
                                ],
                            ],
                        ];
                        $schema['about'] = [
                            '@id' => $post_url . '#service',
                        ];
                        break;

                    case 'white-label-wordpress-development-for-agencies':
                        $schema['mainEntity'] = [
                            '@type'              => 'Service',
                            '@id'                => $post_url . '#service',
                            'serviceType'        => 'White Label WordPress Development',
                            'name'               => 'White Label WordPress Development for Agencies',
                            'description'        => 'White label WordPress development services for digital agencies and resellers, providing project-based and ongoing partnership solutions',
                            'provider'           => [
                                '@type' => 'Organization',
                                '@id'   => $site_url . '/#organization',
                                'name'  => '84EM',
                                'url'   => $site_url,
                            ],
                            'areaServed'         => [
                                '@type' => 'Country',
                                'name'  => 'United States',
                            ],
                            'offers'             => [
                                [
                                    '@type'              => 'Offer',
                                    'name'               => 'Standard Rate',
                                    'priceSpecification' => [
                                        '@type'         => 'UnitPriceSpecification',
                                        'price'         => '150',
                                        'priceCurrency' => 'USD',
                                        'unitText'      => 'HOUR',
                                    ],
                                    'availability'       => 'https://schema.org/InStock',
                                ],
                                [
                                    '@type'              => 'Offer',
                                    'name'               => 'After-Hours Rate',
                                    'description'        => 'After-hours and emergency development services',
                                    'priceSpecification' => [
                                        '@type'         => 'UnitPriceSpecification',
                                        'price'         => '225',
                                        'priceCurrency' => 'USD',
                                        'unitText'      => 'HOUR',
                                    ],
                                    'availability'       => 'https://schema.org/InStock',
                                ],
                            ],
                        ];
                        $schema['about'] = [
                            '@id' => $post_url . '#service',
                        ];
                        break;

                    case 'ai-enhanced-wordpress-development':
                        $schema['mainEntity'] = [
                            '@type'              => 'Service',
                            '@id'                => $post_url . '#service',
                            'serviceType'        => 'AI-Enhanced WordPress Development',
                            'name'               => 'AI-Enhanced WordPress Development',
                            'description'        => 'WordPress development enhanced with artificial intelligence tools to deliver solutions faster with better quality at reduced costs',
                            'provider'           => [
                                '@type' => 'Organization',
                                '@id'   => $site_url . '/#organization',
                                'name'  => '84EM',
                                'url'   => $site_url,
                            ],
                            'areaServed'         => [
                                '@type' => 'Country',
                                'name'  => 'United States',
                            ],
                            'offers'             => [
                                [
                                    '@type'              => 'Offer',
                                    'name'               => 'Standard Rate',
                                    'priceSpecification' => [
                                        '@type'         => 'UnitPriceSpecification',
                                        'price'         => '150',
                                        'priceCurrency' => 'USD',
                                        'unitText'      => 'HOUR',
                                    ],
                                    'availability'       => 'https://schema.org/InStock',
                                ],
                                [
                                    '@type'              => 'Offer',
                                    'name'               => 'After-Hours Rate',
                                    'description'        => 'After-hours and emergency development services',
                                    'priceSpecification' => [
                                        '@type'         => 'UnitPriceSpecification',
                                        'price'         => '225',
                                        'priceCurrency' => 'USD',
                                        'unitText'      => 'HOUR',
                                    ],
                                    'availability'       => 'https://schema.org/InStock',
                                ],
                            ],
                        ];
                        $schema['about'] = [
                            '@id' => $post_url . '#service',
                        ];
                        break;

                    case 'ai-integration-development':
                        $schema['mainEntity'] = [
                            '@type'              => 'Service',
                            '@id'                => $post_url . '#service',
                            'serviceType'        => 'AI Integration & Development',
                            'name'               => 'AI Integration & Development',
                            'description'        => 'Custom AI integrations, automations, and tools that solve real business problems',
                            'provider'           => [
                                '@type' => 'Organization',
                                '@id'   => $site_url . '/#organization',
                                'name'  => '84EM',
                                'url'   => $site_url,
                            ],
                            'areaServed'         => [
                                '@type' => 'Country',
                                'name'  => 'United States',
                            ],
                            'offers'             => [
                                [
                                    '@type'              => 'Offer',
                                    'name'               => 'Standard Rate',
                                    'priceSpecification' => [
                                        '@type'         => 'UnitPriceSpecification',
                                        'price'         => '150',
                                        'priceCurrency' => 'USD',
                                        'unitText'      => 'HOUR',
                                    ],
                                    'availability'       => 'https://schema.org/InStock',
                                ],
                                [
                                    '@type'              => 'Offer',
                                    'name'               => 'After-Hours Rate',
                                    'priceSpecification' => [
                                        '@type'         => 'UnitPriceSpecification',
                                        'price'         => '225',
                                        'priceCurrency' => 'USD',
                                        'unitText'      => 'HOUR',
                                    ],
                                    'availability'       => 'https://schema.org/InStock',
                                ],
                            ],
                        ];
                        $schema['about'] = [
                            '@id' => $post_url . '#service',
                        ];
                        break;

                    case 'wordpress-consulting-strategy':
                        $schema['mainEntity'] = [
                            '@type'              => 'Service',
                            '@id'                => $post_url . '#service',
                            'serviceType'        => 'Technical Consulting',
                            'name'               => 'Technical Consulting & Strategy',
                            'description'        => 'Expert technical consulting and strategic planning services including audits and architecture planning',
                            'provider'           => [
                                '@type' => 'Organization',
                                '@id'   => $site_url . '/#organization',
                                'name'  => '84EM',
                                'url'   => $site_url,
                            ],
                            'areaServed'         => [
                                '@type' => 'Country',
                                'name'  => 'United States',
                            ],
                            'offers'             => [
                                [
                                    '@type'              => 'Offer',
                                    'name'               => 'Standard Rate',
                                    'priceSpecification' => [
                                        '@type'         => 'UnitPriceSpecification',
                                        'price'         => '150',
                                        'priceCurrency' => 'USD',
                                        'unitText'      => 'HOUR',
                                    ],
                                    'availability'       => 'https://schema.org/InStock',
                                ],
                                [
                                    '@type'              => 'Offer',
                                    'name'               => 'After-Hours Rate',
                                    'description'        => 'After-hours and emergency development services',
                                    'priceSpecification' => [
                                        '@type'         => 'UnitPriceSpecification',
                                        'price'         => '225',
                                        'priceCurrency' => 'USD',
                                        'unitText'      => 'HOUR',
                                    ],
                                    'availability'       => 'https://schema.org/InStock',
                                ],
                            ],
                        ];
                        $schema['about'] = [
                            '@id' => $post_url . '#service',
                        ];
                        break;

                    case 'wordpress-maintenance-support':
                        $schema['mainEntity'] = [
                            '@type'              => 'Service',
                            '@id'                => $post_url . '#service',
                            'serviceType'        => 'WordPress Maintenance',
                            'name'               => 'WordPress Maintenance & Support',
                            'description'        => 'Ongoing WordPress maintenance, updates, security monitoring, backups, and troubleshooting to keep your site running reliably',
                            'provider'           => [
                                '@type' => 'Organization',
                                '@id'   => $site_url . '/#organization',
                                'name'  => '84EM',
                                'url'   => $site_url,
                            ],
                            'areaServed'         => [
                                '@type' => 'Country',
                                'name'  => 'United States',
                            ],
                            'offers'             => [
                                [
                                    '@type'              => 'Offer',
                                    'name'               => 'Standard Rate',
                                    'priceSpecification' => [
                                        '@type'         => 'UnitPriceSpecification',
                                        'price'         => '150',
                                        'priceCurrency' => 'USD',
                                        'unitText'      => 'HOUR',
                                    ],
                                    'availability'       => 'https://schema.org/InStock',
                                ],
                                [
                                    '@type'              => 'Offer',
                                    'name'               => 'After-Hours Rate',
                                    'description'        => 'After-hours and emergency development services',
                                    'priceSpecification' => [
                                        '@type'         => 'UnitPriceSpecification',
                                        'price'         => '225',
                                        'priceCurrency' => 'USD',
                                        'unitText'      => 'HOUR',
                                    ],
                                    'availability'       => 'https://schema.org/InStock',
                                ],
                            ],
                        ];
                        $schema['about'] = [
                            '@id' => $post_url . '#service',
                        ];
                        break;

                    case 'privacy-policy':
                        $schema['mainEntity'] = [
                            '@type'       => 'DigitalDocument',
                            'name'        => '84EM Privacy Policy',
                            'description' => 'Privacy policy governing web engineering services and data protection practices',
                            'author'      => [
                                '@type' => 'Organization',
                                '@id'   => $site_url . '/#organization',
                            ],
                        ];
                        break;

                    case 'faq':
                        // Extract FAQs from accordion blocks.
                        $faqs = extract_faqs_from_accordion( $post_id );

                        if ( ! empty( $faqs ) ) {
                            // Build Question entities for FAQPage schema.
                            $questions = [];
                            foreach ( $faqs as $faq ) {
                                $questions[] = [
                                    '@type'          => 'Question',
                                    'name'           => $faq['question'],
                                    'acceptedAnswer' => [
                                        '@type' => 'Answer',
                                        'text'  => $faq['answer'],
                                    ],
                                ];
                            }

                            // Change schema type to FAQPage per Google guidelines.
                            $schema['@type']      = 'FAQPage';
                            $schema['mainEntity'] = $questions;
                        }
                        break;

                    case 'testimonials':
                        // Extract testimonials from reusable blocks on this page.
                        $testimonials = extract_testimonials_from_page( $post_id );

                        // Build reviews array from extracted testimonials.
                        $reviews = [];
                        foreach ( $testimonials as $testimonial ) {
                            $reviews[] = [
                                '@type'        => 'Review',
                                'itemReviewed' => [
                                    '@type' => 'Organization',
                                    '@id'   => $site_url . '/#organization',
                                ],
                                'reviewRating' => [
                                    '@type'       => 'Rating',
                                    'ratingValue' => $testimonial['rating'],
                                    'bestRating'  => 5,
                                    'worstRating' => 1,
                                ],
                                'author'       => [
                                    '@type' => 'Person',
                                    'name'  => $testimonial['attribution'],
                                ],
                                'reviewBody'   => $testimonial['quote'],
                                'datePublished' => $testimonial['date'],
                            ];
                        }

                        // Calculate aggregate rating.
                        $review_count   = \count( $testimonials );
                        $average_rating = $review_count > 0 ? 5.0 : 0;

                        $schema['mainEntity'] = [
                            '@type'           => 'Organization',
                            '@id'             => $site_url . '/#organization',
                            'name'            => '84EM',
                            'description'     => 'Expert Web Engineering Services',
                            'url'             => $site_url,
                            'aggregateRating' => [
                                '@type'       => 'AggregateRating',
                                'ratingValue' => $average_rating,
                                'reviewCount' => $review_count,
                                'bestRating'  => 5,
                                'worstRating' => 1,
                            ],
                            'review'          => $reviews,
                        ];
                        break;
                }
                break;
        }

        // Add featured image if available
        $featured_image_id = \get_post_thumbnail_id( $post_id );
        if ( $featured_image_id ) {
            $image_url  = \wp_get_attachment_image_url( $featured_image_id, 'full' );
            $image_meta = \wp_get_attachment_metadata( $featured_image_id );

            $schema['image'] = [
                '@type'  => 'ImageObject',
                'url'    => $image_url,
                'width'  => $image_meta['width'] ?? 1200,
                'height' => $image_meta['height'] ?? 630,
            ];
        }

        // Convert to JSON and save to post meta
        $schema_json = \wp_json_encode( $schema, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE );
        \update_post_meta( $post_id, 'schema', $schema_json );
    },
    priority: 99,
    accepted_args: 3 );

// Function to output schema in head
\add_action(
    hook_name: 'wp_head',
    callback: function () {
        if ( \is_singular() ) {
            $schema_json = \get_post_meta( \get_the_ID(), 'schema', true );
            if ( ! empty( $schema_json ) ) {
                // Escape potential </script> tags in JSON to prevent script injection
                echo sprintf(
                    '<script type="application/ld+json">%s</script>',
                    str_replace( '</script>', '<\/script>', $schema_json )
                ) . "\n";
            }
        }
    } );
