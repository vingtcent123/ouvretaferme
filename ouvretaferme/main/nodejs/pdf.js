'use strict';

const argv = require('minimist')(process.argv.slice(2));
const puppeteer = require('puppeteer-core');
const fs = require('fs');
const { PDFDocument } = require("pdf-lib");

// CLI Args
const url = argv.url;
const destination = argv.destination;

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
		format: 'A4'
	});

	const pageTitle = await page.title();

	const buffer = fs.readFileSync(destination);
	const pdfDoc = await PDFDocument.load(buffer)
	console.log(pageTitle);
	pdfDoc.setTitle(pageTitle)
	const pdfBytes = await pdfDoc.save()

	await fs.writeFileSync(destination, pdfBytes);

	await browser.close();


})();
