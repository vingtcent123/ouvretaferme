'use strict';

const argv = require('minimist')(process.argv.slice(2));
const puppeteer = require('puppeteer-core');

// CLI Args
const url = argv.url;
const destination = argv.destination;

(async() => {

	const browser = await puppeteer.launch({
		executablePath: '/var/www/chrome-linux/chrome',
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

	await browser.close();


})();
