const { test: teardown } = require('@playwright/test');
const { setMailCapture } = require('./utils/mail');

/* Left on, the site sends no mail at all. A killed run cannot get here, which is
   what the admin notice in the mu-plugin is for. */

teardown('stop capturing mail', async ({ page }) => {
	await setMailCapture(page, false);
});
