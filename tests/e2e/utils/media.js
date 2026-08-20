const fs = require('fs');
const path = require('path');
const { expect } = require('@playwright/test');
const { TIMEOUTS } = require('./timeouts');
const { clickUntil } = require('./interactions');

/* The plugin's own forms have no file input for an attachment setting: they open
   the WordPress media modal and take back an id. */

/** One of the plugin's own images, so the suite carries no binary of its own. */
const IMAGE_FILE = path.join(__dirname, '../../../img/chairs_med.jpg');

let counter = 0;

/** The media library is global and tests do not clean up, so names must not collide. */
function uniqueFileName(prefix = 'seatreg-e2e') {
	counter += 1;

	const run = Date.now().toString(36);
	const worker = process.env.TEST_WORKER_INDEX ?? '0';

	return `${prefix}-${run}-${worker}-${counter}.jpg`;
}

/**
 * Upload an image through an already open media modal and choose it.
 *
 * @param {string} confirmLabel The caption the screen gave the modal's button
 * @return {Promise<string>} The attachment's title
 */
async function uploadThroughMediaModal(page, confirmLabel) {
	const modal = page.locator('.media-modal');
	await expect(modal).toBeVisible({ timeout: TIMEOUTS.NAVIGATION });

	const fileName = uniqueFileName();
	const title = path.parse(fileName).name;

	/* Scoped to the modal: there is a second, page wide file input for dropping
	   files anywhere. */
	await modal.locator('#menu-item-upload').click();
	await modal.locator('input[type="file"]').first().setInputFiles({
		name: fileName,
		mimeType: 'image/jpeg',
		buffer: fs.readFileSync(IMAGE_FILE),
	});

	/* The upload has no completion to wait for. It is done when the library lists
	   the attachment, which is also where it has to be chosen. */
	const attachment = modal.locator(`.attachments .attachment[aria-label="${title}"]`);
	await clickUntil(modal.locator('#menu-item-browse'), attachment);

	/* An upload arrives already chosen, and clicking it would take it off again. */
	if ((await attachment.getAttribute('aria-checked')) !== 'true') {
		await attachment.click();
	}

	await expect(attachment).toHaveAttribute('aria-checked', 'true');

	await modal.getByRole('button', { name: confirmLabel }).click();
	await expect(modal).toBeHidden();

	return title;
}

module.exports = { uniqueFileName, uploadThroughMediaModal };
