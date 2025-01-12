const puppeteer = require('puppeteer');
const path = require('path');
const fs = require('fs');

(async () => {

	try {

		const browser = await puppeteer.launch();
		const page = await browser.newPage();

		// Set the viewport size
		await page.setViewport({
			width: 1280,
			height: 1280
		});

		const args = process.argv.slice(2);

		if (args.length === 0) {
			console.error('Usage: screens.bat <URL>');
			process.exit(0);
		}

		const url = args[0];

		if (url.indexOf('.test') === -1) {
			console.error('URL should contain .test');
			process.exit(0);
		}

		let project = (new URL(url)).hostname.replace('.test', ''),
			projectsFolder = 'Z:/',
			projectPath = '',
			themeName = args[2] ? args[2] : project;

		let subfolders = fs.readdirSync(projectsFolder, {withFileTypes: true}).filter(item => item.isDirectory()).map(item => path.join(projectsFolder, item.name));

		for (const subfolder of subfolders) {

			let subfolderContents = fs.readdirSync(subfolder, {withFileTypes: true}).filter(item => item.isDirectory() && item.name === project).map(item => path.join(subfolder, item.name));

			if (subfolderContents.length > 0) {
				projectPath = subfolderContents[0];
			}

		}

		if (projectPath === '') {
			console.error('Unable to find a project: ' + project);
			process.exit(0);
		}

		const themePath = path.join(projectPath, 'wp-content/themes/' + themeName);

		if (!fs.existsSync(themePath)) {
			console.error('Unable to find a theme: ' + themePath);
			process.exit(0);
		}

		// Define the screenshots folder
		const previewFolder = path.join(themePath, 'assets/preview');

		if (!fs.existsSync(previewFolder)) {
			fs.mkdirSync(previewFolder, {recursive: true}); // Create folder if it doesn't exist
		}

		// Load the page
		console.log(`URL: ${url.replace('?preview', '')}`);

		await page.goto(url, {waitUntil: 'networkidle2'});

		// Remove or hide specific elements (e.g., sticky header, footer, ads)
		await page.evaluate(() => {
			const selectorsToRemove = ['.header_box', '.footer_box'];
			selectorsToRemove.forEach(selector => {
				document.querySelectorAll(selector).forEach(el => el.remove());
			});
		});

		const selector = args[1] ? args[1] : 'section[class*="_box"]';
		const elements = await page.$$(selector);

		if (elements.length === 0) {
			console.log('No Sections Found');
			await browser.close();
			process.exit(0);
		}

		/**
		 * Process selected sections
		 */
		for (let i = 0; i < elements.length; i++) {

			const className = await elements[i].evaluate(el => el.className);

			let fileName = '',
				currentLayout = await elements[i].evaluate(el => el.dataset.layout);

			if (currentLayout) {
				fileName = currentLayout;
			} else {
				const match = className.match(/(\w+)_box/);
				if (match[1]) {
					fileName = match[1];
				}
			}

			if (!fileName) {
				console.log(`Skipped Section: ${className}`);
				continue;
			}

			fileName = currentLayout + '.webp';

			await elements[i].evaluate(el => el.classList.add('box_top', 'box_bottom'));

			const filePath = path.join(previewFolder, fileName);

			// Check if the file already exists, and skip it if so
			if (!args[1] && fs.existsSync(filePath)) {
				console.log(`Skipped: ${fileName}`);
				continue;
			} else {
				console.log(`Created: ${fileName}`);
			}

			const bounding = await elements[i].boundingBox();
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
					height: page.viewport().height,
				}
			}

			await elements[i].screenshot(options);

		}

		await browser.close();

	} catch (error) {

		console.error('Error:', error);
		process.exit(0);

	}

})();