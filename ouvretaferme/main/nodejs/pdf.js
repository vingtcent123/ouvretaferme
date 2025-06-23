'use strict';

const argv = require('minimist')(process.argv.slice(2));
const puppeteer = require('puppeteer-core');
const fs = require('fs');
const { PDFDocument } = require('pdf-lib');

// CLI Args
const url = argv.url;
const destination = argv.destination;
const headerTemplate = argv.header || null;
const title = argv.title || null;
const footerTemplate = argv.footer || null;
const headerFooterArgs = {
	...headerTemplate ? {headerTemplate: decodeURIComponent((headerTemplate + '').replace(/\+/g, '%20'))} : {},
	...footerTemplate ? {footerTemplate: decodeURIComponent((footerTemplate + '').replace(/\+/g, '%20'))} : {},
	...(headerTemplate || footerTemplate) ? {displayHeaderFooter : true, margin: {top: '150px'}} : {},
};

(async() => {

	const browser = await puppeteer.launch({
		executablePath: '/usr/bin/google-chrome',
		args: ['--no-sandbox']
	});

	const page = await browser.newPage();

	await page.goto(url, {
		waitUntil: 'networkidle0'
	});

	await page.pdf({
		path: destination,
		printBackground: true,
		format: 'A4',
		...headerFooterArgs,
	});

	const buffer = fs.readFileSync(destination);
	const pdfDoc = await PDFDocument.load(buffer)
	pdfDoc.setTitle(decodeURIComponent((title + '').replace(/\+/g, '%20')))
	const pdfBytes = await pdfDoc.save()

	await fs.writeFileSync(destination, pdfBytes);

	await browser.close();


})();
