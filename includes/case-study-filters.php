<?php
/**
 * Case Study Filters Configuration
 * Manages filter buttons and keywords for the case studies page
 *
 * @package EightyFourEM
 */

namespace EightyFourEM\CaseStudyFilters;

defined( 'ABSPATH' ) || exit;

/**
 * Get all filter configurations
 *
 * @return array Filter configuration with labels and keywords
 */
function get_filters(): array {
	return [
            'all'         => [
                    'label'    => 'All',
                    'keywords' => [],
            ],
            'financial'   => [
                    'label'    => 'Financial Services',
                    'keywords' => [ 'financial', 'fintech', 'banking', 'crypto', 'bank', 'lending', 'investment', 'cryptocurrency', 'ibor', 'asset management', 'asset manager', 'fund management', 'conversions api' ],
            ],
            'ecommerce'  => [
                    'label'    => 'E-Commerce',
                    'keywords' => [ 'woocommerce', 'ecommerce', 'e-commerce', 'shipstation', 'subscription', 'order', 'store credit', 'loyalty', 'wellness brand' ],
            ],
            'healthcare' => [
                    'label'    => 'Healthcare',
                    'keywords' => [ 'healthcare', 'medical', 'health', 'senior living', 'wellness', 'assessment' ],
            ],
            'marketing'  => [
                    'label'    => 'Marketing & CRM',
                    'keywords' => [ 'marketing', 'crm', 'lead', 'leads', 'email', 'klaviyo', 'conversions', 'reporting', 'analytics', 'gravity forms', 'twilio', 'sms', 'advertising', 'ads', 'hubspot' ],
            ],
            'realestate' => [
                    'label'    => 'Real Estate',
                    'keywords' => [ 'real estate', 'mls', 'commercial', 'property' ],
            ],
            'security'   => [
                    'label'    => 'Security & Identity',
                    'keywords' => [ 'security', 'authentication', 'identity', 'verification', 'saml', 'two factor', '2fa', 'two-factor', 'headers', 'persona', 'idology', 'integrity checker', 'single sign-on', 'oidc', 'sso', 'identity provider' ],
            ],
            'integrations' => [
                    'label'    => 'Data & Integrations',
                    'keywords' => [ 'api', 'integration', 'zapier', 'calendly', 'webhook', 'data sync', 'pdf import', 'youtube', 'spektrix', 'fasttrack', 's3', 'zoom', 'typeform' ],
            ],
            'education'  => [
                    'label'    => 'Education',
                    'keywords' => [ 'education', 'learndash', 'lms', 'learning', 'course', 'training', 'e-learning', 'student' ],
            ],
            'ai'         => [
                    'label'    => 'AI & Automation',
                    'keywords' => [ 'ai', 'ai-powered', 'ai analysis', 'claude', 'openai', 'chatgpt', 'machine learning', 'artificial intelligence', 'automation', 'automated', 'automatic', 'automatically', 'intelligent' ],
            ],
	];
}

/**
 * Get filter categories for a case study based on keyword matching
 *
 * @param int $post_id Post ID
 * @return array Filter category keys that match this case study
 */
function get_case_study_categories( int $post_id ): array {
	$post = \get_post( $post_id );
	if ( ! $post ) {
		return [];
	}

	$search_text        = \strtolower( $post->post_title . ' ' . $post->post_content );
	$filters            = get_filters();
	$matched_categories = [];

	foreach ( $filters as $key => $filter ) {
		if ( 'all' === $key || empty( $filter['keywords'] ) ) {
			continue;
		}

		foreach ( $filter['keywords'] as $keyword ) {
			if ( false !== \strpos( $search_text, \strtolower( $keyword ) ) ) {
				$matched_categories[] = $key;
				break;
			}
		}
	}

	return $matched_categories;
}

/**
 * Render filter buttons HTML
 *
 * @return string HTML output
 */
function render_filters(): string {
    $filters = get_filters();

    \ob_start();
    ?>
    <div class="case-study-filters">
        <div class="case-study-filter-buttons">
            <?php foreach ( $filters as $key => $filter ) : ?>
                <button class="case-study-filter-btn <?php echo $key === 'all' ? 'is-active' : ''; ?>" data-filter="<?php echo \esc_attr( $key ); ?>">
                    <?php echo \esc_html( $filter['label'] ); ?>
                </button>
            <?php endforeach; ?>
        </div>
        <div class="case-study-result-count"></div>
    </div>
    <?php
    return \ob_get_clean();
}

// Register shortcode
\add_shortcode( 'case_study_filters', 'EightyFourEM\CaseStudyFilters\render_filters' );

/**
 * Localize filter keywords to JavaScript
 */
\add_action( 'wp_enqueue_scripts', function () {
    if ( \is_page( 4406 ) ) {
        $filters  = get_filters();
        $keywords = [];

        foreach ( $filters as $key => $filter ) {
            if ( ! empty( $filter['keywords'] ) ) {
                $keywords[ $key ] = $filter['keywords'];
            }
        }

        \wp_localize_script(
                'eightyfourem-case-study-filter',
                'caseStudyFilters',
                $keywords
        );
    }
}, 20 ); // Priority 20 to run after script is enqueued
