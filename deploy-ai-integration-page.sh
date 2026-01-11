#!/bin/bash
# Deploy AI Integration & Development service page to production
# Run from the WordPress root directory on production server
#
# Usage: ./wp-content/themes/eightyfourem/deploy-ai-integration-page.sh
#
# This script:
# 1. Creates the page with block content
# 2. Sets SEO meta fields
# 3. Outputs the new page ID
#
# Prerequisites:
# - WP-CLI installed and configured
# - Run from WordPress root directory

set -e

# Get script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CONTENT_FILE="$SCRIPT_DIR/deploy-ai-integration-content.html"

# Check if wp-cli is available
if ! command -v wp &> /dev/null; then
    echo "Error: WP-CLI is not installed or not in PATH"
    exit 1
fi

# Check if content file exists
if [ ! -f "$CONTENT_FILE" ]; then
    echo "Error: Content file not found at $CONTENT_FILE"
    exit 1
fi

# Get Services page ID (parent)
SERVICES_ID=$(wp post list --post_type=page --name=services --field=ID --format=csv 2>/dev/null | tail -1)

if [ -z "$SERVICES_ID" ]; then
    echo "Error: Could not find Services page"
    exit 1
fi

echo "Found Services page ID: $SERVICES_ID"

# Check if page already exists
EXISTING_ID=$(wp post list --post_type=page --name=ai-integration-development --field=ID --format=csv 2>/dev/null | tail -1)

if [ -n "$EXISTING_ID" ]; then
    echo "Warning: Page already exists with ID: $EXISTING_ID"
    echo "URL: $(wp option get siteurl)/services/ai-integration-development/"
    exit 0
fi

echo "Creating AI Integration & Development page..."

# Create the page
PAGE_ID=$(wp post create \
    --post_type=page \
    --post_title="AI Integration & Development" \
    --post_name="ai-integration-development" \
    --post_status=publish \
    --post_parent="$SERVICES_ID" \
    --post_content="$(cat "$CONTENT_FILE")" \
    --porcelain)

echo "Created page with ID: $PAGE_ID"

# Set SEO meta fields
echo "Setting SEO meta fields..."
wp post meta update "$PAGE_ID" _84em_seo_title "AI Integration & Development Services | Custom AI Solutions | 84EM"
wp post meta update "$PAGE_ID" _84em_seo_description "Add AI capabilities to your business. Custom integrations with OpenAI, Claude, and more. WordPress expertise plus standalone tools. \$150/hour."

echo ""
echo "Success! Page created:"
echo "  ID: $PAGE_ID"
echo "  URL: $(wp option get siteurl)/services/ai-integration-development/"
echo ""
echo "Note: schema.php changes must be deployed separately via git."
