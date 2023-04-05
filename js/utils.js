function setCalendarDateUrlParam(calendarDate) {
	var queryParams = new URLSearchParams(window.location.search);

	queryParams.set('calendar-date', calendarDate);
	window.history.replaceState(null, null, '?' + queryParams.toString());
}