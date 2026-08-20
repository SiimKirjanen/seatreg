const { expect } = require('@playwright/test');
const { TIMEOUTS } = require('./timeouts');

/**
 * The site's own clock, which need not be the one the tests are running in.
 *
 * WordPress only prints a local time when the site has been given a timezone;
 * without one it runs on UTC and the universal time it always prints is the same.
 *
 * @return {Promise<{hours: number, minutes: number}>}
 */
async function siteLocalTime(page) {
	await page.goto('/wp-admin/options-general.php');
	await expect(page.locator('#utc-time')).toBeVisible({ timeout: TIMEOUTS.NAVIGATION });

	const localTime = page.locator('#local-time code');
	const clock = (await localTime.count()) > 0 ? localTime : page.locator('#utc-time code');

	const [, time] = (await clock.innerText()).trim().split(' ');
	const [hours, minutes] = time.split(':').map(Number);

	return { hours, minutes };
}

module.exports = { siteLocalTime };
