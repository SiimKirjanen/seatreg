const fs = require('fs');
const path = require('path');
const { expect } = require('@playwright/test');
const { TIMEOUTS } = require('./timeouts');
const { clickUntil } = require('./interactions');

/**
 * The WordPress media library, for the settings that hold an attachment id.
 *
 * This is about WordPress rather than about a SeatReg screen: the plugin's own
 * forms have no file input for these, they open the media modal and take back an
 * id, so every screen that has such a setting goes through the same core markup.
 */

/**
 * One of the plugin's own images, so the suite carries no binary of its own. It
 * is re-sent under a new name rather than uploaded from here, so the library
 * never has to be searched by anything a parallel worker could also be using.
 */
const IMAGE_FILE = path.join(__dirname, '../../../img/chairs_med.jpg');

let counter = 0;

/**
 * A file name that cannot collide with another test, another worker, or a
 * previous run. The media library is global and tests do not clean up after
 * themselves, the same way registrations do not.
 *
 * @param {string} prefix Human readable prefix so leftovers are identifiable
 * @return {string} e.g. `seatreg-e2e-mgk3x2ab-0-1.jpg`
 */
function uniqueFileName(prefix = 'seatreg-e2e') {
	counter += 1;

	const run = Date.now().toString(36);
	const worker = process.env.TEST_WORKER_INDEX ?? '0';

	return `${prefix}-${run}-${worker}-${counter}.jpg`;
}

/**
 * Upload an image through an already open media modal and choose it.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} confirmLabel The caption the screen gave the modal's button
 * @return {Promise<string>} The attachment's title, which is the name it was
 *                           uploaded under without its extension
 */
async function uploadThroughMediaModal(page, confirmLabel) {
	const modal = page.locator('.media-modal');
	await expect(modal).toBeVisible({ timeout: TIMEOUTS.NAVIGATION });

	const fileName = uniqueFileName();
	const title = path.parse(fileName).name;

	/* The upload tab is what renders the file input; the modal opens on the
	   library. There is a second, page wide input outside the modal for dropping
	   files anywhere, hence the scope. */
	await modal.locator('#menu-item-upload').click();
	await modal.locator('input[type="file"]').first().setInputFiles({
		name: fileName,
		mimeType: 'image/jpeg',
		buffer: fs.readFileSync(IMAGE_FILE),
	});

	/* The upload has no completion of its own to wait for. It is done when the
	   library lists the attachment, which is also where it has to be chosen, so
	   going back to that tab until it shows up covers both. */
	const attachment = modal.locator(`.attachments .attachment[aria-label="${title}"]`);
	await clickUntil(modal.locator('#menu-item-browse'), attachment);

	/* An upload arrives already chosen, and clicking it would take it off again,
	   so it is only clicked when it is not. */
	if ((await attachment.getAttribute('aria-checked')) !== 'true') {
		await attachment.click();
	}

	await expect(attachment).toHaveAttribute('aria-checked', 'true');

	await modal.getByRole('button', { name: confirmLabel }).click();
	await expect(modal).toBeHidden();

	return title;
}

module.exports = { uniqueFileName, uploadThroughMediaModal };
