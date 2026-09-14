const puppeteer = require('puppeteer');
const path = require('path');

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
    } catch (e) {
        console.log('Mermaid wait warning:', e.message);
    }

    await new Promise(r => setTimeout(r, 2000));

    await page.evaluate(() => {
        const btn = document.querySelector('.print-btn');
        if (btn) btn.style.display = 'none';
    });

    const outputPath = path.resolve(__dirname, 'Workflow_Flowchart_simonKeu.pdf');
    await page.pdf({
        path: outputPath,
        format: 'A4',
        printBackground: true,
        margin: { top: '0mm', bottom: '0mm', left: '0mm', right: '0mm' },
        preferCSSPageSize: false
    });

    console.log('PDF 2 Halaman Sempurna berhasil dibuat: ' + outputPath);
    await browser.close();
})();
