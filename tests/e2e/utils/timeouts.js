const TIMEOUTS = {
	/** Default Playwright expect timeout (5s) */
	DEFAULT: 5000,
	/** Page navigations and app load */
	NAVIGATION: 15000,
	/** How long a bound handler gets to visibly react, one attempt of clickUntil() */
	REACTION: 1000,
};

module.exports = { TIMEOUTS };
