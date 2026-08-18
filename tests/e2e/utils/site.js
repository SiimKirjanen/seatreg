const { expect } = require('@playwright/test');
const { TIMEOUTS } = require('./timeouts');

/**
 * Facts about the WordPress install the tests run against.
 */

/**
 * The site's own clock, as hours and minutes.
 *
 * Settings that work in times of day are judged by the site's timezone, which
 * does not have to be the one the tests are running in. A test that needs to be
 * on one side of a time of day has to pick it from this rather than from its own
 * clock.
 *
 * WordPress only prints the local time when the site has been given a timezone.
 * Without one it runs on UTC, and the universal time it always prints is the
 * same thing.
 *
 * @return {Promise<{hours: number, minutes: number}>}
 */
async function siteLocalTime(page) {
	await page.goto('/wp-admin/options-general.php');
	await expect(page.locator('#utc-time')).toBeVisible({ timeout: TIMEOUTS.NAVIGATION });

	const localTime = page.locator('#local-time code');
	const clock = (await localTime.count()) > 0 ? localTime : page.locator('#utc-time code');

	/* Printed in the 'Y-m-d H:i:s' format WordPress uses for both of them. */
	const [, time] = (await clock.innerText()).trim().split(' ');
	const [hours, minutes] = time.split(':').map(Number);

	return { hours, minutes };
}

module.exports = { siteLocalTime };
