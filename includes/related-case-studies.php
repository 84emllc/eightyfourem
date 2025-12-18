<?php
/**
 * Related Case Studies
 * Automatically displays related case studies on individual case study pages
 *
 * @package EightyFourEM
 */

namespace EightyFourEM\RelatedCaseStudies;

defined( 'ABSPATH' ) || exit;

/**
 * Get weighted category matches for a case study
 *
 * Determines which categories match from title vs body content,
 * returning separate arrays for weighted scoring.
 *
 * @param int $post_id Post ID
 * @return array{title: array, body: array} Categories matched in title and body-only
 */
function get_weighted_categories( int $post_id ): array {
	$post = \get_post( $post_id );
	if ( ! $post ) {
		return [ 'title' => [], 'body' => [] ];
	}

	$title_text   = \strtolower( $post->post_title );
	$content_text = \strtolower( $post->post_content );
	$filters      = \EightyFourEM\CaseStudyFilters\get_filters();

	$title_categories = [];
	$body_categories  = [];

	foreach ( $filters as $key => $filter ) {
		if ( 'all' === $key || empty( $filter['keywords'] ) ) {
			continue;
		}

		$found_in_title = false;
		$found_in_body  = false;

		foreach ( $filter['keywords'] as $keyword ) {
			$keyword_lower = \strtolower( $keyword );

			if ( false !== \strpos( $title_text, $keyword_lower ) ) {
				$found_in_title = true;
				break;
			}

			if ( false !== \strpos( $content_text, $keyword_lower ) ) {
				$found_in_body = true;
			}
		}

		if ( $found_in_title ) {
			$title_categories[] = $key;
		} elseif ( $found_in_body ) {
			$body_categories[] = $key;
		}
	}

	return [
		'title' => $title_categories,
		'body'  => $body_categories,
	];
}

/**
 * Get related case studies for a given post
 *
 * @param int $post_id Current case study post ID
 * @param int $limit Maximum number of related studies to return
 * @return array Array of WP_Post objects
 */
function get_related_case_studies( int $post_id, int $limit = 6 ): array {
	$cache_key = 'related_cs_' . $post_id;
	$cached    = \get_transient( $cache_key );

	if ( false !== $cached ) {
		return $cached;
	}

	$current_weighted = get_weighted_categories( $post_id );
	$current_all      = \array_merge( $current_weighted['title'], $current_weighted['body'] );

	if ( empty( $current_all ) ) {
		return [];
	}

	$all_case_studies = \get_posts(
		args: [
			'post_type'    => 'page',
			'post_parent'  => 4406,
			'post__not_in' => [ $post_id ],
			'numberposts'  => -1,
			'orderby'      => 'date',
			'order'        => 'DESC',
		]
	);

	$scored_studies = [];

	// Scoring weights:
	// - Current title category matched in candidate title: 9 points (most relevant)
	// - Current title category matched in candidate body: 3 points
	// - Current body category matched in candidate title: 2 points
	// - Current body category matched in candidate body: 1 point (least relevant)

	foreach ( $all_case_studies as $study ) {
		$study_weighted    = get_weighted_categories( $study->ID );
		$study_all         = \array_merge( $study_weighted['title'], $study_weighted['body'] );
		$shared_categories = \array_intersect( $current_all, $study_all );

		if ( empty( $shared_categories ) ) {
			continue;
		}

		// Calculate weighted score based on where matches occur in BOTH studies
		$score = 0;
		foreach ( $shared_categories as $category ) {
			$in_current_title = \in_array( $category, $current_weighted['title'], true );
			$in_study_title   = \in_array( $category, $study_weighted['title'], true );

			if ( $in_current_title && $in_study_title ) {
				$score += 9; // Both have this category in title - highly relevant
			} elseif ( $in_current_title ) {
				$score += 3; // Current's title category found in candidate's body
			} elseif ( $in_study_title ) {
				$score += 2; // Current's body category found in candidate's title
			} else {
				$score += 1; // Both have category in body only
			}
		}

		$scored_studies[] = [
			'post'       => $study,
			'score'      => $score,
			'categories' => $study_all,
		];
	}

	\usort(
		array: $scored_studies,
		callback: function ( array $a, array $b ): int {
			if ( $a['score'] !== $b['score'] ) {
				return $b['score'] - $a['score'];
			}
			return \strtotime( $b['post']->post_date ) - \strtotime( $a['post']->post_date );
		}
	);

	$related = \array_slice( \array_column( $scored_studies, 'post' ), 0, $limit );

	\set_transient(
		transient: $cache_key,
		value: $related,
		expiration: MONTH_IN_SECONDS
	);

	return $related;
}

/**
 * Render related case studies HTML
 *
 * @param int $post_id Current post ID
 * @return string HTML output
 */
function render_related_case_studies( int $post_id ): string {
	$related = get_related_case_studies(
		post_id: $post_id,
		limit: 3
	);

	if ( empty( $related ) ) {
		return '';
	}

	$filters = \EightyFourEM\CaseStudyFilters\get_filters();

	\ob_start();
	?>
	<section class="related-case-studies">
		<div class="related-case-studies-container">
			<h2 class="related-case-studies-heading">Related Case Studies</h2>

			<div class="related-case-studies-grid">
				<?php foreach ( $related as $study ) :
					$study_categories = \EightyFourEM\CaseStudyFilters\get_case_study_categories( $study->ID );
					$permalink        = \get_permalink( $study->ID );
					$thumbnail        = \get_the_post_thumbnail_url(
						post: $study->ID,
						size: 'medium'
					);

					// Generate excerpt, removing Challenge heading if present
					$content = \wp_strip_all_tags( $study->post_content );
					// Remove "Challenge" from the start if it appears
					$content = \preg_replace( '/^\s*Challenge\s*/i', '', $content );
					$excerpt = \wp_trim_words(
						text: $content,
						num_words: 20
					);
				?>
					<article class="related-case-study-card">
						<a href="<?php echo \esc_url( $permalink ); ?>" class="related-case-study-link">
							<?php if ( $thumbnail ) : ?>
								<div class="related-case-study-image">
									<img src="<?php echo \esc_url( $thumbnail ); ?>"
									     alt="<?php echo \esc_attr( $study->post_title ); ?>"
									     loading="lazy">
								</div>
							<?php endif; ?>

							<div class="related-case-study-content">
								<h3 class="related-case-study-title">
									<?php echo \esc_html( $study->post_title ); ?>
								</h3>

								<p class="related-case-study-excerpt">
									<?php echo \esc_html( $excerpt ); ?>
								</p>

								<?php if ( ! empty( $study_categories ) ) : ?>
									<div class="related-case-study-categories">
										<?php foreach ( $study_categories as $cat_key ) :
											if ( isset( $filters[ $cat_key ] ) ) :
										?>
											<span class="related-case-study-badge">
												<?php echo \esc_html( $filters[ $cat_key ]['label'] ); ?>
											</span>
										<?php
											endif;
										endforeach;
										?>
									</div>
								<?php endif; ?>
							</div>
						</a>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php
	return \ob_get_clean();
}

/**
 * Inject related case studies into post content
 */
\add_filter(
	hook_name: 'the_content',
	callback: function ( string $content ): string {
		if ( ! \is_page() || ! \is_singular() ) {
			return $content;
		}

		$post = \get_post();
		if ( ! $post || 4406 !== $post->post_parent ) {
			return $content;
		}

		$related_html = render_related_case_studies( $post->ID );

		if ( empty( $related_html ) ) {
			return $content;
		}

		return $content . $related_html;
	},
	priority: 20
);

/**
 * Clear related case studies cache when a case study is updated
 */
\add_action(
	hook_name: 'save_post_page',
	callback: function ( int $post_id ): void {
		$post = \get_post( $post_id );

		if ( ! $post || 4406 !== $post->post_parent ) {
			return;
		}

		\delete_transient( 'related_cs_' . $post_id );

		$all_case_studies = \get_posts(
			args: [
				'post_type'   => 'page',
				'post_parent' => 4406,
				'numberposts' => -1,
				'fields'      => 'ids',
			]
		);

		foreach ( $all_case_studies as $study_id ) {
			\delete_transient( 'related_cs_' . $study_id );
		}
	}
);
