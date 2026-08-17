const TIMEOUTS = {
	/** Default Playwright expect timeout (5s) */
	DEFAULT: 5000,
	/** Page navigations and app load */
	NAVIGATION: 15000,
	/** Tests with multiple navigations and async side effects */
	LONG_TEST: 60000,
};

module.exports = { TIMEOUTS };
