<?php
/**
 * Google Reviews Block Template
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 */

namespace EightyFourEM\GoogleReviews;

// Get reviews data
$sort_by = $attributes['reviewsSort'] ?? 'most_relevant';
$reviews = \EightyFourEM\GoogleReviews\get_google_reviews( $sort_by );

if ( ! $reviews ) {
	echo '<div class="google-reviews-block error">Unable to load reviews at this time.</div>';
	return;
}

// Extract attributes
$show_link           = ! isset( $attributes['showLink'] ) || $attributes['showLink'];
$show_review_content = $attributes['showReviewContent'] ?? false;
$max_reviews         = ! isset( $attributes['maxReviews'] ) ? 5 : min( 5, \intval( $attributes['maxReviews'] ) );
$bg_color            = $attributes['backgroundColor'] ?? '#f9f9f9';
$text_color          = $attributes['textColor'] ?? '#333333';
$override_url        = $attributes['overrideUrl'] ?? '';
$override_title      = $attributes['overrideTitle'] ?? '';
$custom_rating_text  = $attributes['customRatingText'] ?? '';
$show_title          = $attributes['showTitle'] ?? true;
$show_rating_text    = $attributes['showRatingText'] ?? true;
$rating_text_below   = $attributes['ratingTextBelow'] ?? false;

// Individual color attributes
$title_text_color          = $attributes['titleTextColor'] ?? '';
$title_background_color    = $attributes['titleBackgroundColor'] ?? '';
$rating_text_color         = $attributes['ratingTextColor'] ?? '';
$rating_background_color   = $attributes['ratingBackgroundColor'] ?? '';
$reviews_text_color        = $attributes['reviewsTextColor'] ?? '';
$reviews_background_color  = $attributes['reviewsBackgroundColor'] ?? '';

// Typography attributes
$title_font_size              = $attributes['titleFontSize'] ?? '';
$title_font_size_custom       = $attributes['titleFontSizeCustom'] ?? null;
$rating_text_font_size        = $attributes['ratingTextFontSize'] ?? '';
$rating_text_font_size_custom = $attributes['ratingTextFontSizeCustom'] ?? null;
$reviews_font_size            = $attributes['reviewsFontSize'] ?? '';
$reviews_font_size_custom     = $attributes['reviewsFontSizeCustom'] ?? null;
$review_time_font_size        = $attributes['reviewTimeFontSize'] ?? '';
$review_time_font_size_custom = $attributes['reviewTimeFontSizeCustom'] ?? null;
$review_time_text_color       = $attributes['reviewTimeTextColor'] ?? '';
$review_time_background_color = $attributes['reviewTimeBackgroundColor'] ?? '';
$stars_font_size              = $attributes['starsFontSize'] ?? '';
$stars_font_size_custom       = $attributes['starsFontSizeCustom'] ?? null;
$stars_text_color             = $attributes['starsTextColor'] ?? '';
$stars_background_color       = $attributes['starsBackgroundColor'] ?? '';

// Build styles
$style = sprintf(
	'background-color: %s; color: %s;',
	esc_attr( $bg_color ),
	esc_attr( $text_color )
);

// Typography classes and styles for title
$title_class = '';
$title_style = '';
if ( ! empty( $title_font_size ) ) {
	$title_class .= ' has-' . $title_font_size . '-font-size';
	$preset_sizes = [
		'small'    => '0.77rem',
		'medium'   => '0.9rem',
		'large'    => '1.57rem',
		'x-large'  => '2.1rem',
		'xx-large' => '2.8rem',
	];
	if ( isset( $preset_sizes[ $title_font_size ] ) ) {
		$title_style .= 'font-size: ' . esc_attr( $preset_sizes[ $title_font_size ] ) . '; ';
	}
} elseif ( $title_font_size_custom ) {
	$title_style .= 'font-size: ' . esc_attr( $title_font_size_custom ) . 'px; ';
}

if ( ! empty( $title_text_color ) ) {
	$title_style .= 'color: ' . esc_attr( $title_text_color ) . '; ';
}
if ( ! empty( $title_background_color ) ) {
	$title_style .= 'background-color: ' . esc_attr( $title_background_color ) . '; ';
}

// Typography classes and styles for rating text
$rating_text_class = '';
$rating_text_style = '';
if ( ! empty( $rating_text_font_size ) ) {
	$rating_text_class .= ' has-' . $rating_text_font_size . '-font-size';
	$preset_sizes = [
		'small'    => '0.77rem',
		'medium'   => '0.9rem',
		'large'    => '1.57rem',
		'x-large'  => '2.1rem',
		'xx-large' => '2.8rem',
	];
	if ( isset( $preset_sizes[ $rating_text_font_size ] ) ) {
		$rating_text_style .= 'font-size: ' . esc_attr( $preset_sizes[ $rating_text_font_size ] ) . '; ';
	}
} elseif ( $rating_text_font_size_custom ) {
	$rating_text_style .= 'font-size: ' . esc_attr( $rating_text_font_size_custom ) . 'px; ';
}

if ( ! empty( $rating_text_color ) ) {
	$rating_text_style .= 'color: ' . esc_attr( $rating_text_color ) . '; ';
}
if ( ! empty( $rating_background_color ) ) {
	$rating_text_style .= 'background-color: ' . esc_attr( $rating_background_color ) . '; ';
}

// Typography classes and styles for reviews
$reviews_class = '';
$reviews_style = '';
if ( ! empty( $reviews_font_size ) ) {
	$reviews_class .= ' has-' . $reviews_font_size . '-font-size';
	$preset_sizes = [
		'small'    => '0.77rem',
		'medium'   => '0.9rem',
		'large'    => '1.57rem',
		'x-large'  => '2.1rem',
		'xx-large' => '2.8rem',
	];
	if ( isset( $preset_sizes[ $reviews_font_size ] ) ) {
		$reviews_style .= 'font-size: ' . esc_attr( $preset_sizes[ $reviews_font_size ] ) . '; ';
	}
} elseif ( $reviews_font_size_custom ) {
	$reviews_style .= 'font-size: ' . esc_attr( $reviews_font_size_custom ) . 'px; ';
}

if ( ! empty( $reviews_text_color ) ) {
	$reviews_style .= 'color: ' . esc_attr( $reviews_text_color ) . '; ';
}
if ( ! empty( $reviews_background_color ) ) {
	$reviews_style .= 'background-color: ' . esc_attr( $reviews_background_color ) . '; ';
}

// Typography classes and styles for review time
$review_time_class = '';
$review_time_style = '';
if ( ! empty( $review_time_font_size ) ) {
	$review_time_class .= ' has-' . $review_time_font_size . '-font-size';
	$preset_sizes = [
		'small'    => '0.77rem',
		'medium'   => '0.9rem',
		'large'    => '1.57rem',
		'x-large'  => '2.1rem',
		'xx-large' => '2.8rem',
	];
	if ( isset( $preset_sizes[ $review_time_font_size ] ) ) {
		$review_time_style .= 'font-size: ' . esc_attr( $preset_sizes[ $review_time_font_size ] ) . '; ';
	}
} elseif ( $review_time_font_size_custom ) {
	$review_time_style .= 'font-size: ' . esc_attr( $review_time_font_size_custom ) . 'px; ';
}

if ( ! empty( $review_time_text_color ) ) {
	$review_time_style .= 'color: ' . esc_attr( $review_time_text_color ) . '; ';
}
if ( ! empty( $review_time_background_color ) ) {
	$review_time_style .= 'background-color: ' . esc_attr( $review_time_background_color ) . '; ';
}

// Typography classes and styles for stars
$stars_class = '';
$stars_style = '';
if ( ! empty( $stars_font_size ) ) {
	$stars_class .= ' has-' . $stars_font_size . '-font-size';
	$preset_sizes = [
		'small'    => '0.77rem',
		'medium'   => '0.9rem',
		'large'    => '1.57rem',
		'x-large'  => '2.1rem',
		'xx-large' => '2.8rem',
	];
	if ( isset( $preset_sizes[ $stars_font_size ] ) ) {
		$stars_style .= 'font-size: ' . esc_attr( $preset_sizes[ $stars_font_size ] ) . '; ';
	}
} elseif ( $stars_font_size_custom ) {
	$stars_style .= 'font-size: ' . esc_attr( $stars_font_size_custom ) . 'px; ';
}

if ( ! empty( $stars_text_color ) ) {
	$stars_style .= 'color: ' . esc_attr( $stars_text_color ) . '; ';
}
if ( ! empty( $stars_background_color ) ) {
	$stars_style .= 'background-color: ' . esc_attr( $stars_background_color ) . '; ';
}

// Create a style for the rating section
$rating_section_style = '';
if ( ! empty( $rating_background_color ) ) {
	$rating_section_style .= 'background-color: ' . esc_attr( $rating_background_color ) . '; ';
}

// Get block wrapper attributes
$wrapper_attributes = \get_block_wrapper_attributes( [
	'class' => 'google-reviews-block',
	'style' => $style,
] );

// Allowed HTML for title and custom rating text
$allowed_html = [
	'p'      => [],
	'a'      => [
		'href'   => true,
		'title'  => true,
		'target' => true,
		'rel'    => true,
	],
	'br'     => [],
	'strong' => [],
	'em'     => [],
];

?>
<div <?php echo $wrapper_attributes; ?>>
	<?php if ( $show_title ) : ?>
		<div class="review-header">
			<h3 class="<?php echo esc_attr( trim( $title_class ) ); ?>" style="<?php echo esc_attr( $title_style ); ?>">
				<?php
				$title_text = ! empty( $override_title ) ? $override_title : $reviews['name'];
				echo wp_kses( $title_text, $allowed_html );
				?>
			</h3>
		</div>
	<?php endif; ?>

	<div class="review-rating" style="<?php echo esc_attr( $rating_section_style ); ?>">
		<span class="rating-number"><?php echo number_format( $reviews['rating'], 1 ); ?></span>
		<span class="stars"><?php echo \EightyFourEM\GoogleReviews\render_stars( $reviews['rating'], $stars_class, $stars_style ); ?></span>

		<?php if ( $show_rating_text && ! $rating_text_below ) : ?>
			<span class="rating-count<?php echo esc_attr( $rating_text_class ); ?>" style="<?php echo esc_attr( $rating_text_style ); ?>">
				<?php
				if ( ! empty( $custom_rating_text ) ) {
					$rating_text = str_replace( '$review_count', $reviews['total_ratings'], $custom_rating_text );
					echo wp_kses( $rating_text, $allowed_html );
				} else {
					echo '(' . esc_html( $reviews['total_ratings'] ) . ' reviews)';
				}
				?>
			</span>
		<?php endif; ?>
	</div>

	<?php if ( $show_rating_text && $rating_text_below ) : ?>
		<div class="rating-count-below<?php echo esc_attr( $rating_text_class ); ?>" style="<?php echo esc_attr( $rating_text_style ); ?>">
			<?php
			if ( ! empty( $custom_rating_text ) ) {
				$rating_text = str_replace( '$review_count', $reviews['total_ratings'], $custom_rating_text );
				echo wp_kses( $rating_text, $allowed_html );
			} else {
				echo '(' . esc_html( $reviews['total_ratings'] ) . ' reviews)';
			}
			?>
		</div>
	<?php endif; ?>

	<?php if ( $show_review_content && ! empty( $reviews['reviews'] ) ) : ?>
		<div class="individual-reviews<?php echo esc_attr( $reviews_class ); ?>" style="<?php echo esc_attr( $reviews_style ); ?>">
			<?php
			$individual_reviews = \array_slice( $reviews['reviews'], 0, $max_reviews );
			foreach ( $individual_reviews as $review ) :
				?>
				<div class="review-item">
					<div class="review-header">
						<div class="reviewer-info">
							<?php if ( isset( $review['profile_photo_url'] ) ) : ?>
								<img src="<?php echo esc_url( $review['profile_photo_url'] ); ?>" alt="<?php echo esc_attr( $review['author_name'] ); ?>" class="reviewer-photo"/>
							<?php endif; ?>
							<div class="reviewer-details">
								<span class="reviewer-name"><?php echo esc_html( $review['author_name'] ); ?></span>
								<div class="review-rating-individual">
									<?php echo \EightyFourEM\GoogleReviews\render_stars( $review['rating'], $stars_class, $stars_style ); ?>
									<span class="review-time<?php echo esc_attr( $review_time_class ); ?>" style="<?php echo esc_attr( $review_time_style ); ?>">
										<?php echo esc_html( \EightyFourEM\GoogleReviews\format_review_time( $review['time'] ) ); ?>
									</span>
								</div>
							</div>
						</div>
					</div>
					<?php if ( ! empty( $review['text'] ) ) : ?>
						<div class="review-text">
							<?php echo nl2br( esc_html( $review['text'] ) ); ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php if ( $show_link && ( ! empty( $override_url ) || ! empty( $reviews['url'] ) ) ) : ?>
		<div class="review-link">
			<?php
			$link_url = ! empty( $override_url ) ? $override_url : $reviews['url'];
			if ( filter_var( $link_url, FILTER_VALIDATE_URL ) !== false ) :
				?>
				<a href="<?php echo esc_url( $link_url ); ?>" target="_blank" rel="noopener">See All Reviews on Google</a>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
