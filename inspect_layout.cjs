const puppeteer = require('puppeteer');
const path = require('path');
const fs = require('fs');

(async () => {
    const browser = await puppeteer.launch({
        executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu']
    });

    const page = await browser.newPage();
    const filePath = path.resolve(__dirname, 'workflow_flowchart.html');
    await page.goto('file://' + filePath, { waitUntil: 'load', timeout: 60000 });

    try {
        await page.waitForFunction(() => {
            const mermaidElements = document.querySelectorAll('.mermaid');
            for (const el of mermaidElements) {
                if (!el.querySelector('svg')) return false;
            }
            return mermaidElements.length > 0;
        }, { timeout: 15000 });
    } catch (e) {}

    await new Promise(r => setTimeout(r, 2000));

    await page.evaluate(() => {
        const btn = document.querySelector('.print-btn');
        if (btn) btn.style.display = 'none';
    });

    // Set viewport to A4 aspect ratio
    await page.setViewport({ width: 1200, height: 1600, deviceScaleFactor: 1 });

    // Measure total document height
    const bodyHeight = await page.evaluate(() => document.body.scrollHeight);
    console.log('Body scrollHeight:', bodyHeight);

    // Take screenshot of full page
    await page.screenshot({ path: path.resolve(__dirname, 'pdf_preview_full.png'), fullPage: true });
    console.log('Full screenshot saved: pdf_preview_full.png');

    await browser.close();
})();
