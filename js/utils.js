function setCalendarDateUrlParam(calendarDate) {
	var queryParams = new URLSearchParams(window.location.search);

	queryParams.set('calendar-date', calendarDate);
	window.history.replaceState(null, null, '?' + queryParams.toString());
}

function seatregFormatCalendarDateForDisplay(isoDate, siteLang) {
	if (!isoDate || !/^\d{4}-\d{2}-\d{2}$/.test(isoDate)) {
		return isoDate;
	}
	var parts = isoDate.split('-'); // avoid timezone shift
	var d = new Date(parts[0], parts[1] - 1, parts[2]);
	var locale = (siteLang || 'en').replace('_', '-');
	
	try {
		return new Intl.DateTimeFormat(locale, { year: 'numeric', month: 'long', day: 'numeric' }).format(d);
	} catch (e) {
		return isoDate;
	}
}