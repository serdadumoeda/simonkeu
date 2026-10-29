const puppeteer = require('puppeteer');
const path = require('path');

(async () => {
    const browser = await puppeteer.launch({
        executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu']
    });

    const page = await browser.newPage();
    const filePath = path.resolve(__dirname, 'workflow_banprog.html');
    await page.goto('file://' + filePath, { waitUntil: 'load', timeout: 60000 });

    // Tunggu semua diagram Mermaid selesai render
    try {
        await page.waitForFunction(() => {
            const mermaidElements = document.querySelectorAll('.mermaid');
            for (const el of mermaidElements) {
                if (!el.querySelector('svg')) return false;
            }
            return mermaidElements.length > 0;
        }, { timeout: 20000 });
        console.log('✅ Semua diagram Mermaid berhasil di-render.');
    } catch (e) {
        console.log('⚠️  Mermaid wait warning (lanjut generate):', e.message);
    }

    // Tambahan buffer agar animasi/font selesai load
    await new Promise(r => setTimeout(r, 3000));

    // Sembunyikan tombol print sebelum export
    await page.evaluate(() => {
        const btn = document.querySelector('.print-btn');
        if (btn) btn.style.display = 'none';
    });

    const outputPath = path.resolve(__dirname, 'Workflow_Banprog_simonKeu.pdf');

    await page.pdf({
        path: outputPath,
        format: 'A4',
        landscape: true,
        printBackground: true,
        margin: { top: '6mm', bottom: '6mm', left: '8mm', right: '8mm' },
        preferCSSPageSize: false
    });

    console.log('✅ PDF Landscape Banprog berhasil dibuat: ' + outputPath);
    await browser.close();
})();
