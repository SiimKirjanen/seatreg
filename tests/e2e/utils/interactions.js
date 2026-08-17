const { expect } = require('@playwright/test');
const { TIMEOUTS } = require('./timeouts');

/**
 * Interaction helpers for the SeatReg admin screens.
 */

/**
 * Click until the DOM reacts.
 *
 * The plugin binds its handlers to the elements that exist when the script runs
 * instead of delegating, so a click landing before that is silently ignored.
 * Retried only while the target is still missing - once it is open it would be
 * in the way of the next click.
 *
 * @param {number} options.reaction How long one click gets to show its effect
 * @param {number} options.timeout  Budget for all the attempts together
 */
async function clickUntil(
	trigger,
	target,
	{ reaction = TIMEOUTS.REACTION, timeout = TIMEOUTS.NAVIGATION } = {}
) {
	await expect(async () => {
		if (!(await target.isVisible())) {
			await trigger.click();
		}

		await expect(target).toBeVisible({ timeout: reaction });
	}).toPass({ timeout, intervals: [200, 500, 1000] });
}

/**
 * Wait for a Bootstrap modal to be fully faded in.
 *
 * Acting on one mid transition is ignored, which leaves it stuck on screen.
 */
async function expectModalShown(modal) {
	await expect(modal).toBeVisible();
	await expect(modal).toHaveCSS('opacity', '1');
}

module.exports = { clickUntil, expectModalShown };
