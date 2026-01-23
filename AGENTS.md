# Repository Guidelines

## Project Overview
The 84EM Block Theme is a custom WordPress FSE theme optimized for business websites. Recently streamlined (v2.4.0, v2.5.3) to remove unused patterns, fonts, templates, and style variations inherited from Twenty Twenty-Four base theme.

**Key Features:**
- **Case Study Filters** - Interactive filtering system with shareable URLs (`includes/case-study-filters.php`)
- **Sticky Header TOC** - Dynamic table of contents navigation in header (`assets/js/sticky-header.js`)
- **Google Reviews Block** - Custom Gutenberg block for displaying reviews
- **SEO Suite** - Meta tags, schema.org structured data, XML sitemap with batch processing
- **Custom 404 Handling** - Automatic redirects for legacy URLs (`includes/404.php`)
- **Modal Search** - Accessible modal search with keyboard navigation and ARIA support
- **FAQ Search** - On-page filtering for FAQ page with WCAG 2.1 accessibility support
- **Simple Analytics** - Privacy-focused analytics via external CDN (scripts.simpleanalyticscdn.com)
- **Columns Reverse on Mobile** - Block editor toggle to reverse column order when stacked on mobile (`assets/js/columns-reverse-mobile.js`)

## Project Structure & Module Organization
- `assets/css|js|fonts/` hold front-end sources; compiled files inherit the same path with `.min.(css|js)` suffixes. Keep Google Reviews block assets inside `assets/google-reviews-block/`.
- `includes/` contains PHP modules loaded by `functions.php`. Add new features by creating a focused include (e.g., `includes/case-study-filters.php`) and requiring it in `functions.php`.
- `patterns/` directory now contains **only 2 patterns** actively used: `posts-3-col.php` and `template-home-business.php`. All unused Twenty Twenty-Four patterns removed (v2.4.0, v2.5.3).
- `parts/`, `templates/` mirror core block theme conventions. Keep template-part slugs consistent to prevent Site Editor mismatches.
- `styles/` directory empty - all 7 style variations removed (v2.4.0) as site uses custom global styles.
- `theme.json` centralizes color, typography, and spacing tokens—extend global styles there before touching individual CSS files.

## Build, Test, and Development Commands
- `npm install` — install gulp-based tooling; rerun after updating `package.json`.
- `npm start` — default watcher; compiles CSS/JS with sourcemaps and reloads on change.
- `npm run build` — production build with minification and autoprefixing; **automated via GitHub Actions on push, do NOT run manually for releases**.
- `npm run clean` — remove generated `.min.*` artifacts to ensure a fresh pipeline.

## Documentation Guidelines
- **ALWAYS update this AGENTS.md file** after making code changes that affect:
  - Project structure (new directories, file organization)
  - Build processes or development workflows
  - Coding conventions or patterns
  - New features or architectural changes
  - Testing procedures
  - Deployment or release processes
- Keep documentation current so future AI agents and developers understand the codebase accurately

## Key Include Files
Files in `includes/` directory provide modular functionality:

**Core Features:**
- `case-study-filters.php` - Interactive filtering system with shortcode `[case_study_filters]`, keyword configuration, JS localization
- `google-reviews.php` - Custom Gutenberg block for displaying Google Business reviews
- `404.php` - Custom redirect handler (e.g., `/project/*` → `/case-studies/*`)
- `performance.php` - Font preloading, critical font-face inlining, resource hints to eliminate FOUT/FOIT

**SEO & Content:**
- `document-title.php` - Custom SEO title filter using `_84em_seo_title` meta key
- `meta-tags.php` - SEO description and robots meta tags using `_84em_seo_description` and `_84em_noindex` meta keys
- `schema.php` - Schema.org structured data generation for pages and posts; testimonials schema extracts reviews from reusable blocks
- `xml-sitemap.php` - XML sitemap with batch processing via Action Scheduler
- `html-sitemap.php` - Card-based HTML sitemap with collapsible sections
- `search.php` - Search result filtering, Challenge heading removal from excerpts
- `relevanssi.php` - Relevanssi search plugin integration with spell correction
- `open-graph-images.php` - Open Graph image management with fallback logic

**UI & Navigation:**
- `enqueue.php` - Script/style enqueuing for sticky header, modal search, case study filters, animations, columns reverse mobile
- `block-styles.php` - Custom block style registration
- `block-stylesheets.php` - Block-specific stylesheet loading
- `animations.css` - CSS animations for block editor (replaces blocks-animation plugin)
- `animations.js` - Intersection Observer for scroll-triggered animations
- `columns-reverse-mobile.js` - Extends Columns block with "Reverse on mobile" toggle in block editor

**Dynamic Content:**
- `dynamic-years.php` - Shortcodes for dynamic year calculations: `[dev_years]` (programming since 1995), `[wp_years]` (WordPress since 2012), `[years_since year="XXXX"]` (custom year)
- `cli-dynamic-years.php` - WP-CLI commands for migrating hardcoded year values to shortcodes

**Integrations:**
- `cli.php` - WP-CLI commands for schema regeneration
- `cli-dynamic-years.php` - WP-CLI commands for dynamic years migration
- `calendly-booking-details.php` - Calendly booking details block
- `shortcodes.php` - Shortcode registry (delegates to feature modules)

**Content Display:**
- `related-case-studies.php` - Displays related case studies on individual case study pages with weighted relevance scoring
- `author-pages.php` - Author page customizations

**Site Configuration:**
- `dequeue.php` - Script/style dequeuing for unused assets
- `disable-comments.php` - Disables comments site-wide
- `pattern-categories.php` - Block pattern category registration
- `permalinks.php` - Custom permalink handling
- `shortlinks.php` - Shortlink functionality

**Code Quality:**
- All functions include proper type hints (PHP 8.0+)
- Intentionally unused parameters marked with underscore prefix
- Safe $_POST access with wp_unslash() and sanitize_text_field()
- Default parameter values omitted from named parameters (priority: 10, deps: [], accepted_args: 1)

## Coding Style & Naming Conventions

### PHP Architecture
All files in `includes/` follow a **purpose-based approach** to function organization:

**Use Anonymous Functions (Default):**
- ✅ Single-purpose hook/filter callbacks
- ✅ Self-contained logic only called by WordPress
- ✅ Functions under 50 lines
- ✅ PHP 8.0+ named parameters (`hook_name:`, `callback:`, `priority:`)
- ✅ Short array syntax `[]` instead of `array()`
- ✅ No `function_exists()` checks needed

**Example:**
```php
\add_action(
	hook_name: 'wp_head',
	callback: function () {
		// Simple, focused logic
	},
	priority: 1
);
```

**Use Named Functions (When Needed):**
- ✅ Functions called from multiple places (helpers, utilities)
- ✅ Complex logic benefiting from descriptive names in stack traces
- ✅ Exposed via shortcodes, WP-CLI, or public API
- ✅ Functions you might unit test
- ✅ Functions over 50 lines

**Example:**
```php
namespace EightyFourEM\CaseStudyFilters;

function get_filters() {
	return [ /* ... */ ];
}

add_shortcode( 'case_study_filters', 'EightyFourEM\CaseStudyFilters\render_filters' );
```

### General Conventions
- PHP follows WordPress Coding Standards: tabs for indentation, early returns
- All custom code uses `namespace EightyFourEM\` or sub-namespaces
- SCSS/CSS files use 2-space indentation; prefer block-specific class prefixes (`.case-study-filter-btn`)
- JavaScript in `assets/js/` is plain ES2015; keep modules IIFE-scoped
- Block pattern slugs and filenames use lowercase with hyphens (`patterns/posts-3-col.php`)

## Testing Guidelines
- No automated suite yet; smoke-test changes by activating the theme in a local WordPress install and exercising modified templates/patterns.
- Validate responsive behavior in Chrome DevTools' device modes and confirm JavaScript features log no console errors.
- When editing data-driven templates, compare rendered markup against `theme.json` tokens to avoid color/spacing regressions.

### Feature-Specific Testing
- **Sticky Header TOC** (`assets/js/sticky-header.js`)
  - Scroll past 50px to confirm hamburger menu TOC appears
  - Verify TOC generates from H2 headings, excludes hero and first H2
  - Test dropdown menu opens/closes, smooth scroll works with header offset
  - Confirm menu auto-closes when clicking links or outside menu
  - Check hysteresis (40px-60px) prevents flickering at scroll threshold
  - Mobile: verify full-width menu (100vw), scrollbar styling, down arrow indicator

- **Case Study Filters** (`includes/case-study-filters.php`, `assets/js/case-study-filter.js`)
  - Test all filter buttons (All, Financial, API, AI, Affiliates, E-Commerce, Education, Security, Reporting, Automation)
  - Verify keyword matching works (check AI filter with multiple keywords)
  - Confirm result counter updates ("X of Y projects")
  - Test shareable URL hashes (e.g., `#filter=woocommerce`)
  - Check smooth fade/scale animations during filtering
  - Verify page loads with hash filter applied

- **Search/Excerpt Filtering** (`includes/search.php`)
  - Test search results exclude "Challenge" headings from excerpts
  - Verify case studies page (parent 4406) strips Challenge headings from excerpts
  - Confirm excerpts start with actual content, not section headings

- **Relevanssi Search Integration** (`includes/relevanssi.php`)
  - Test spell correction suggestions appear for misspelled searches
  - Verify fallback search with corrected query when no results found
  - Test "Did You Mean" functionality

- **Related Case Studies** (`includes/related-case-studies.php`, `assets/css/related-case-studies.css`)
  - Verify related case studies section appears at bottom of individual case study pages
  - Check that related studies share relevant categories (title matches weighted higher than body)
  - Test transient caching by viewing page twice (second load should be faster)
  - Clear cache via WP-CLI: `wp transient delete related_cs_{post_id}`
  - Verify cache clears automatically when any case study is saved

- **FAQ Search** (`assets/js/faq-search.js`, `assets/css/faq-search.css`)
  - Navigate to FAQ page (ID: 6908) to test search functionality
  - Type in search box and verify live filtering with 300ms debounce
  - Confirm section headings and separators hide when no matching FAQs in that section
  - Test keyboard navigation (Escape key clears search)
  - Verify result count announcements via screen reader
  - Test clear button functionality
  - Check reduced motion support in animations

- **Simple Analytics** (`includes/enqueue.php`)
  - Verify analytics script loads from external CDN (scripts.simpleanalyticscdn.com)
  - Check Network tab for simple-analytics requests on page load
  - Verify logged-in WordPress users are excluded from tracking
  - Confirm auto-events track outbound links, file downloads, and mailto links

- **Font Loading Performance** (`includes/performance.php`)
  - Check for FOUT (Flash of Unstyled Text) or FOIT (Flash of Invisible Text) on page load
  - Verify fonts preload in Network tab (should appear early in waterfall)
  - Confirm `<link rel="preload">` tags appear in `<head>` before other resources
  - Check critical font-face declarations are inlined in `<style id="critical-fonts">`
  - Verify both Instrument Sans and Jost fonts load correctly
  - Test on slow 3G connection to ensure fonts load without flash
  - Confirm `font-display: optional` prevents layout shifts

- **CSS Animations** (`assets/css/animations.css`, `assets/js/animations.js`)
  - Add animation classes via block editor's "Additional CSS class(es)" field
  - Use base class `animated` with animation name (e.g., `animated fadeIn`)
  - Available animations: `fadeIn`, `fadeInUp`, `fadeInDown`, `fadeInLeft`, `fadeInRight`, `bounceIn`, `zoomIn`, `slideInUp`, `slideInDown`, `pulse`
  - Elements above the fold animate immediately on page load
  - Elements below the fold animate when scrolled into view (Intersection Observer)
  - Test `prefers-reduced-motion` support (elements should appear immediately without animation delay)
  - Verify `.animate-visible` class is added when elements enter viewport
  - Confirm no console errors related to animation styles or scripts

- **Dynamic Years** (`includes/dynamic-years.php`, `includes/cli-dynamic-years.php`)
  - Test shortcodes via WP-CLI: `wp 84em dynamic-years test`
  - View migration statistics: `wp 84em dynamic-years stats`
  - Preview migration changes: `wp 84em dynamic-years migrate --dry-run`
  - Execute migration: `wp 84em dynamic-years migrate`
  - Shortcodes available:
    - `[dev_years]` - Years since 1995 (programming experience)
    - `[wp_years]` - Years since 2012 (WordPress experience)
    - `[years_since year="XXXX"]` - Years since custom year
  - After migration, verify pages display correct calculated years
  - Edit a migrated page in block editor to confirm shortcode appears as text
  - Clear caches after migration (FlyingPress, Cloudflare)

- **Columns Reverse on Mobile** (`assets/js/columns-reverse-mobile.js`, `assets/css/utilities.css`)
  - Select a Columns block in the block editor
  - In the Settings panel, find the "Reverse on mobile" toggle (below "Stack on mobile")
  - Enable the toggle and save the page
  - View the page on mobile (or use Chrome DevTools device mode at <782px width)
  - Verify columns appear in reverse order when stacked
  - Confirm the `is-reverse-on-mobile` class is added to the columns block
  - Test that the toggle only affects blocks with "Stack on mobile" enabled (blocks with `is-not-stacked-on-mobile` class are unaffected)
  - Verify the toggle state persists after saving and reopening the page

## Release Process
When preparing a release with version bump:
1. Update version numbers in:
   - `style.css` (Version header comment)
   - `package.json` (version field)
2. **ALWAYS update `CHANGELOG.md`** following [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) format
   - Add new version section with date
   - Categorize changes: Added, Changed, Deprecated, Removed, Fixed, Security
   - Be specific about files and functionality affected
3. Commit version files with format: `v{version} - Brief description of main changes`
4. Create git tag: `git tag v{version}`
5. Push branch and tags to GitHub for PR review
6. Follow [Semantic Versioning](https://semver.org/spec/v2.0.0.html):
   - MAJOR: Breaking changes
   - MINOR: New features (backwards compatible)
   - PATCH: Bug fixes (backwards compatible)

## Commit & Pull Request Guidelines
- **NEVER push directly to main.** All changes must go through a pull request, even small fixes.
- Use imperative, scope-first commit subjects under 72 chars (e.g., `Enqueue: add critical CSS preload`). Group related edits into a single commit.
- Reference Jira/GitHub issues in the body when available and describe testing performed (e.g., "Tested locally on WP 6.8.3").
- Pull requests should summarize the change, outline manual verification, and include before/after screenshots for visual updates. Request design review for pattern tweaks.
- GitHub Actions automatically runs `npm run build` on push to compile production assets.

## Security & Configuration Tips
- Never hardcode credentials or API keys; pull secrets from environment variables or WordPress options pages instead.
- Sanitize and escape all dynamic output using `esc_html`, `wp_kses`, or block supports, matching existing patterns in `includes/meta-tags.php`.
