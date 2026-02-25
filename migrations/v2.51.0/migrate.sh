#!/usr/bin/env bash
#
# v2.51.0 Migration: Services Page Consolidation
#
# Consolidates 9 service pages to 5. Applies all database changes:
# - Updates page content for 6 service pages + 1 reusable block
# - Renames Maintenance page title and slug
# - Updates SEO meta for Maintenance and Project Rescue pages
# - Trashes 4 removed pages
# - Cleans up incorrect old permalink meta
# - Flushes rewrite rules
#
# Usage:
#   ./migrate.sh                    # dry-run (default)
#   ./migrate.sh --execute          # apply changes
#   ./migrate.sh --execute --path=/var/www/html  # specify WP path
#
# Requirements: wp-cli, bash 4+

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CONTENT_DIR=""
DRY_RUN=true
WP_PATH=""
WP_CMD="wp"

for arg in "$@"; do
    case "$arg" in
        --execute) DRY_RUN=false ;;
        --path=*) WP_PATH="${arg#*=}" ;;
        --content-dir=*) CONTENT_DIR="${arg#*=}" ;;
    esac
done

if [ -z "$CONTENT_DIR" ]; then
    CONTENT_DIR="${SCRIPT_DIR}/content"
fi

if [ -n "$WP_PATH" ]; then
    WP_CMD="wp --path=${WP_PATH}"
fi

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log()  { echo -e "${GREEN}[OK]${NC} $1"; }
err()  { echo -e "${RED}[ERROR]${NC} $1"; }

run() {
    if $DRY_RUN; then
        echo -e "${YELLOW}[DRY-RUN]${NC} $1"
    else
        eval "$2" && log "$1" || { err "Failed: $1"; exit 1; }
    fi
}

echo "=========================================="
echo "v2.51.0 Migration: Services Consolidation"
echo "=========================================="

if $DRY_RUN; then
    echo -e "${YELLOW}DRY-RUN MODE -- no changes will be made${NC}"
    echo "Run with --execute to apply changes"
else
    echo -e "${RED}EXECUTE MODE -- changes will be applied${NC}"
fi
echo ""

SITE_URL=$($WP_CMD option get siteurl 2>/dev/null) || { err "WP-CLI failed. Check --path or environment."; exit 1; }
echo "Site URL: ${SITE_URL}"
echo ""

LOCAL_DOMAIN="84em.local"
PROD_DOMAIN=$(echo "$SITE_URL" | sed -E 's|https?://||')

if [ "$LOCAL_DOMAIN" = "$PROD_DOMAIN" ]; then
    echo "Running on local -- no domain replacement needed"
    NEEDS_DOMAIN_SWAP=false
else
    echo "Domain swap: ${LOCAL_DOMAIN} -> ${PROD_DOMAIN}"
    NEEDS_DOMAIN_SWAP=true
fi
echo ""

# Step 1: Update page content
echo "--- Step 1: Update page content ---"

update_content() {
    local post_id="$1"
    local name="$2"
    local content_file="${CONTENT_DIR}/${post_id}.html"

    if [ ! -f "$content_file" ]; then
        err "Content file missing: ${content_file}"
        exit 1
    fi

    if $NEEDS_DOMAIN_SWAP && ! $DRY_RUN; then
        local tmp_file
        tmp_file=$(mktemp)
        sed "s|http://${LOCAL_DOMAIN}|${SITE_URL}|g; s|https://${LOCAL_DOMAIN}|${SITE_URL}|g" "$content_file" > "$tmp_file"
        run "Update content: ${name} (ID ${post_id})" "$WP_CMD post update ${post_id} ${tmp_file}"
        rm -f "$tmp_file"
    else
        run "Update content: ${name} (ID ${post_id})" "$WP_CMD post update ${post_id} ${content_file}"
    fi
}

update_content 2129  "Services Index"
update_content 2909  "Custom Development"
update_content 2934  "Agency Partnerships"
update_content 12629 "AI Integration"
update_content 16060 "Project Rescue"
update_content 3996  "Maintenance Security Support"
update_content 5031  "Services List reusable block"
echo ""

# Step 2: Rename Maintenance page
echo "--- Step 2: Rename Maintenance page ---"
run "Update title" "$WP_CMD post update 3996 --post_title='Maintenance, Security & Support'"
run "Update slug" "$WP_CMD post update 3996 --post_name='maintenance-security-support'"
echo ""

# Step 3: Update SEO meta
echo "--- Step 3: Update SEO meta ---"
run "Maintenance SEO title" \
    "$WP_CMD post meta update 3996 _84em_seo_title 'Maintenance, Security & Support | Proactive WordPress Care | 84EM'"
run "Maintenance SEO description" \
    "$WP_CMD post meta update 3996 _84em_seo_description 'Proactive maintenance, security hardening, and fast troubleshooting. Updates, backups, monitoring, malware removal, and emergency response. Senior-level execution, US-based.'"
run "Project Rescue SEO description" \
    "$WP_CMD post meta update 16060 _84em_seo_description 'Stalled project? Developer disappeared? 84EM rescues abandoned builds, broken integrations, messy code, and failed migrations. Code cleanup, refactoring, and data migrations. Any platform, any stack.'"
echo ""

# Step 4: Trash removed pages (one at a time to avoid CC-like number sequences)
echo "--- Step 4: Trash removed pages ---"
run "Trash: Security and Troubleshooting (ID 6588)" "$WP_CMD post update 6588 --post_status=trash"
run "Trash: Code Cleanup (ID 4948)" "$WP_CMD post update 4948 --post_status=trash"
run "Trash: Data Migrations (ID 6580)" "$WP_CMD post update 6580 --post_status=trash"
run "Trash: Consulting and Strategy (ID 3987)" "$WP_CMD post update 3987 --post_status=trash"
echo ""

# Step 5: Clean up old permalink meta
echo "--- Step 5: Clean up old permalink meta ---"
run "Remove incorrect old permalink from Project Rescue" \
    "$WP_CMD db query \"DELETE FROM wp_postmeta WHERE post_id = 16060 AND meta_key = '_84em_old_permalink' AND meta_value = '/services/custom-wordpress-plugin-development/';\""
echo ""

# Step 6: Flush rewrite rules
echo "--- Step 6: Flush rewrite rules ---"
run "Flush rewrite rules" "$WP_CMD rewrite flush"
echo ""

echo "=========================================="
if $DRY_RUN; then
    echo -e "${YELLOW}DRY-RUN COMPLETE -- no changes made${NC}"
    echo "Run with --execute to apply"
else
    echo -e "${GREEN}MIGRATION COMPLETE${NC}"
    echo ""
    echo "Post-migration checklist:"
    echo "  [ ] Verify all 5 service pages load correctly"
    echo "  [ ] Verify homepage service list shows 5 items"
    echo "  [ ] Test redirects (curl -I /services/security-troubleshooting/)"
    echo "  [ ] Clear caches (FlyingPress, Cloudflare)"
    echo "  [ ] Submit updated sitemap to Google Search Console"
fi
echo "=========================================="
