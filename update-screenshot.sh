#!/bin/bash
# Update theme screenshot.png from 84em.com
# Triggers hero lazy load via mouse event, then crops to WordPress standard 1200x900

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SCREENSHOT_PATH="$SCRIPT_DIR/screenshot.png"

echo "Taking screenshot of https://84em.com..."
export LD_LIBRARY_PATH=/usr/lib/x86_64-linux-gnu:$LD_LIBRARY_PATH

node -e "
const puppeteer = require('puppeteer');

(async () => {
    const browser = await puppeteer.launch({ headless: 'new' });
    const page = await browser.newPage();
    await page.setViewport({ width: 1200, height: 900 });
    await page.goto('https://84em.com', { waitUntil: 'networkidle0' });

    // Trigger hero lazy load by dispatching mousemove event
    await page.evaluate(() => {
        window.dispatchEvent(new MouseEvent('mousemove', { bubbles: true }));
    });

    // Wait for hero image to load
    await page.waitForFunction(() => {
        const hero = document.querySelector('[data-lazy-hero]');
        return !hero; // data attribute removed after load
    }, { timeout: 5000 }).catch(() => {});

    // Additional wait for image render
    await new Promise(r => setTimeout(r, 500));

    await page.screenshot({ path: '$SCREENSHOT_PATH' });
    await browser.close();
})();
"

echo "Cropping to 1200x900..."
python3 -c "
from PIL import Image
img = Image.open('$SCREENSHOT_PATH')
cropped = img.crop((0, 0, 1200, 900))
cropped.save('$SCREENSHOT_PATH')
"

echo "Done. Screenshot saved to $SCREENSHOT_PATH"
