const puppeteer = require('puppeteer');
const path = require('path');
const fs = require('fs');

function getErrorMessage(error) {
	return error instanceof Error ? error.message : String(error);
}

async function main() {
	const args = process.argv.slice(2);

	if (!args[0]) {
		throw new Error('Usage: screens.js <URL> [selector] [theme]');
	}

	const url = args[0];

	if (!url.includes('.test')) {
		throw new Error('URL should contain .test');
	}

	let parsedUrl;

	try {
		parsedUrl = new URL(url);
	} catch (error) {
		throw new Error(`Invalid URL: ${url}`);
	}

	const project = parsedUrl.hostname.replace(/\.test$/, '');
	const projectsFolder = 'Z:/';
	const themeName = args[2] || project;
	const projectPath = fs.readdirSync(projectsFolder, {withFileTypes: true})
		.filter(item => item.isDirectory())
		.map(item => path.join(projectsFolder, item.name, project))
		.find(candidate => fs.existsSync(candidate));

	if (!projectPath) {
		throw new Error(`Unable to find a project: ${project}`);
	}

	const themePath = path.join(projectPath, 'wp-content/themes', themeName);

	if (!fs.existsSync(themePath)) {
		throw new Error(`Unable to find a theme: ${themePath}`);
	}

	const previewFolder = path.join(themePath, 'assets/preview');
	fs.mkdirSync(previewFolder, {recursive: true});

	let browser;
	let page;

	try {
		browser = await puppeteer.launch();
		page = await browser.newPage();
		await page.setViewport({
			width: 1280,
			height: 1280
		});

		console.log(`URL: ${url.replace('?preview', '')}`);
		await page.goto(url, {waitUntil: 'networkidle2'});

		await page.evaluate(() => {
			for (const selector of ['.header_box', '.footer_box']) {
				document.querySelectorAll(selector).forEach(element => element.remove());
			}
		});

		const selector = args[1] || 'section[class*="_box"]';
		const elements = await page.$$(selector);

		if (elements.length === 0) {
			console.log('No Sections Found');
			return;
		}

		for (const element of elements) {
			const className = await element.evaluate(el => el.className || '');
			const layout = await element.evaluate(el => el.dataset.layout || '');
			const sectionName = layout || className.match(/(\w+)_box/)?.[1];

			if (!sectionName) {
				console.log(`Skipped Section: ${className}`);
				continue;
			}

			const fileName = `${sectionName}.webp`;
			await element.evaluate(el => el.classList.add('box_top', 'box_bottom'));

			const filePath = path.join(previewFolder, fileName);

			// Check if the file already exists, and skip it if so
			if (!args[1] && fs.existsSync(filePath)) {
				console.log(`Skipped: ${fileName}`);
				continue;
			}

			console.log(`Created: ${fileName}`);
			await element.evaluate(el => el.scrollIntoView({behavior: 'instant', block: 'start'}));
			await new Promise(resolve => setTimeout(resolve, 2000));

			const bounding = await element.boundingBox();
			const options = {
				path: filePath,
				type: 'webp',
				quality: 80
			};

			if (bounding && bounding.height > page.viewport().height) {
				options.clip = {
					x: 0,
					y: 0,
					width: page.viewport().width,
					height: page.viewport().height
				};
			}

			await element.screenshot(options);
		}
	} finally {
		if (browser) {
			await browser.close().catch(() => {});
		}
	}
}

main().catch(error => {
	console.error('Error:', getErrorMessage(error));
	process.exitCode = 1;
});
