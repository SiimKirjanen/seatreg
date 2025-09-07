(function($) {
	var canvasSupport = ($('html').hasClass('no-canvas') ? false : true);
	var bookingOrderInManager = null;
	var bookingManagerActiveAddBookingIdLookupIndex = 0;
	var validCurrencyCodes = [
		'AED', 'AFN', 'ALL', 'AMD', 'ANG', 'AOA', 'ARS', 'AUD', 'AWG', 'AZN',
		'BAM', 'BBD', 'BDT', 'BGN', 'BHD', 'BIF', 'BMD', 'BND', 'BOB', 'BOV',
		'BRL', 'BSD', 'BTN', 'BWP', 'BYN', 'BZD', 'CAD', 'CDF', 'CHE', 'CHF',
		'CHW', 'CLF', 'CLP', 'CNY', 'COP', 'COU', 'CRC', 'CUC', 'CUP', 'CVE',
		'CZK', 'DJF', 'DKK', 'DOP', 'DZD', 'EGP', 'ERN', 'ETB', 'EUR', 'FJD',
		'FKP', 'FOK', 'GBP', 'GEL', 'GGP', 'GHS', 'GIP', 'GMD', 'GNF', 'GTQ',
		'GYD', 'HKD', 'HNL', 'HRK', 'HTG', 'HUF', 'IDR', 'ILS', 'IMP', 'INR',
		'IQD', 'IRR', 'ISK', 'JMD', 'JOD', 'JPY', 'KES', 'KGS', 'KHR', 'KID',
		'KMF', 'KRW', 'KWD', 'KYD', 'KZT', 'LAK', 'LBP', 'LKR', 'LRD', 'LSL',
		'LYD', 'MAD', 'MDL', 'MGA', 'MKD', 'MMK', 'MNT', 'MOP', 'MRU', 'MUR',
		'MVR', 'MWK', 'MXN', 'MXV', 'MYR', 'MZN', 'NAD', 'NGN', 'NIO', 'NOK',
		'NPR', 'NZD', 'OMR', 'PAB', 'PEN', 'PGK', 'PHP', 'PKR', 'PLN', 'PYG',
		'QAR', 'RON', 'RSD', 'CNY', 'RUB', 'RWF', 'SAR', 'SBD', 'SCR', 'SDG',
		'SEK', 'SGD', 'SHP', 'SLL', 'SOS', 'SRD', 'SSP', 'STN', 'SYP', 'SZL',
		'THB', 'TJS', 'TMT', 'TND', 'TOP', 'TRY', 'TTD', 'TVD', 'TWD', 'TZS',
		'UAH', 'UGX', 'USD', 'UYI', 'UYU', 'UYW', 'UZS', 'VES', 'VND', 'VUV',
		'WST', 'XAF', 'XCD', 'XDR', 'XOF', 'XPF', 'YER', 'ZAR', 'ZMW', 'ZWL'
	];		
	//console.log('jQuery version: ' + $.fn.jquery);
	//console.log('jQUery UI version ' + $.ui.version);

	var translator = {
		translate: function(translationKey) {
			if(WP_Seatreg.translations && WP_Seatreg.translations.hasOwnProperty(translationKey)) {
				return WP_Seatreg.translations[translationKey];
			}
		}
	};

	window.seatreg = {
		builder: null,
		selectedRegistration: null,
		bookings: []
	};

	$('.time-stamp').each(function() {
		$(this).text(timeStampToDateString($(this).text()));
	});

	function seaterg_admin_ajax(action, code, data) {
		return $.ajax({
				url: ajaxurl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: action,
					security: WP_Seatreg.nonce,
					code: code,
					data: data
				}
			});
	}

	function seaterg_admin_ajax2(action, code, data) {
		return $.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: action,
					security: WP_Seatreg.nonce,
					code: code,
					data: data
				}
			});
	}

	function seatreg_edit_booking(action, code, editInfo) {
		return $.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: action,
					security: WP_Seatreg.nonce,
					code: code,
					fname: editInfo.firstName,
					lname: editInfo.lastName,
					room: editInfo.seatRoom,
					seatid: editInfo.seatId,
					bookingid: editInfo.bookingId,
					customfield: editInfo.customFieldData,
					id: editInfo.id,
					calendarDate: editInfo.calendarDate
				}
			});
	}

	function seatreg_add_booking_with_manager() {
		return $.ajax({
				url: ajaxurl,
				type: 'POST',
				data: $('#add-booking-modal-form').serialize() + '&security=' + WP_Seatreg.nonce	
			});
	}

	function seatreg_send_test_email(email) {
		return $.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'seatreg_send_test_email',
				security: WP_Seatreg.nonce,
				email: email
			}
		});
	}

	function seatreg_get_booking_logs($bookingId) {
		return $.ajax({
			url: ajaxurl,
			type: 'GET',
			data: {
				action: 'seatreg_get_booking_logs',
				security: WP_Seatreg.nonce,
				bookingId: $bookingId
			}
		});
	}

	function seatreg_get_registration_logs($registrationId) {
		return $.ajax({
			url: ajaxurl,
			type: 'GET',
			data: {
				action: 'seatreg_get_registration_logs',
				security: WP_Seatreg.nonce,
				registrationId: $registrationId
			}
		});
	}

	function seatreg_create_payment_log(bookingId, logStatus, logMessage) {
		return $.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'seatreg_create_payment_log',
				security: WP_Seatreg.nonce,
				logStatus: logStatus,
				bookingId: bookingId,
				logMessage: logMessage
			}
		});
	}

	function seatreg_upload_custom_payment_icon(regCode, file) {
		var formData = new FormData();

		formData.append('file', file);
		formData.append('code', regCode);
		formData.append('action', 'seatreg_custom_payment_icon_upload');
		formData.append('security', WP_Seatreg.nonce);

		return $.ajax({
			url: ajaxurl,
			type: 'POST',
			data: formData,
			contentType: false,
			processData: false,
		});
	}

	function seatreg_admin_ajax_error(jqXHR, textStatus, errorThrown) {
		console.log('error');
		console.log(textStatus);
		console.log(errorThrown);
		console.log(jqXHR);
	}

	function seatreg_clear_builder_data() {
		window.seatreg.builder.clearRegistrationData();
		window.seatreg.bookings = [];
		window.seatreg.selectedRegistration = null;
		window.seatreg.selectedRegistrationName = null;
	}

	function timeStampToDateString(timeStampText) {
		if(!isNaN(timeStampText)) {
			var date = new Date(parseInt(timeStampText));

			return date.format("d.M.Y H:i");
		}
		
		return timeStampText;
	}

	function generateUniqueString() {
		const timestamp = new Date().getTime().toString(36);
		const randomStr = Math.random().toString(36).substring(2, 7); // Adjusted indexes

		return timestamp + randomStr;
	}

	function validateCurrencyCode(currencyCode) {
		return validCurrencyCodes.includes(currencyCode.toUpperCase());
	}

	$('#create-registration-form').on('submit', function(e) {
		var newRegistrationName = $('#new-registration-name').val();

		if(newRegistrationName === '') {
			e.preventDefault();
			alertify.error(translator.translate('enterRegistrationName'));
		}else if(newRegistrationName.length > 255) {
			e.preventDefault();
			alertify.error(translator.translate('registrationNameLimit'));
		}
	});

	$('.seatreg-map-popup-btn').on('click', function() {
		seatreg_clear_builder_data();
		var code = $(this).data('map-code');
		var promise = seaterg_admin_ajax('get_seatreg_layout_and_bookings', code, null);

		promise.done(function(data) {
			if(data._response.type == 'ok') {
				let registrationName = data._response.data.registration[0].registration_name.replace(/\\/g, '');

				if(data._response.data.bookings.length > 0) {
					var arrLen = data._response.data.bookings.length;

					for(var i = 0; i < arrLen; i++) {
						window.seatreg.bookings.push( data._response.data.bookings[i] );
					}
				}

				if(data._response.data.uploadedImages.length > 0) {
					window.seatreg.uploadedImages = data._response.data.uploadedImages;
				}
				$('.reg-title-name').text(registrationName);
				
				if(data._response.data.registration[0].registration_layout == null) {
					window.seatreg.selectedRegistration = code;				
					$('.seatreg-builder-popup').css({'display': 'block'});
					window.seatreg.builder.syncData(null);
				}else {
					window.seatreg.selectedRegistration = code;
					window.seatreg.settings = {
						paypal_payments: data._response.data.registration[0].paypal_payments,
						stripe_payments: data._response.data.registration[0].stripe_payments,
						custom_payment: data._response.data.registration[0].custom_payment,
						using_seats: data._response.data.registration[0].using_seats
					};
				
					$('.seatreg-builder-popup').css({'display': 'block'});
					window.seatreg.builder.syncData( $.parseJSON(data._response.data.registration[0].registration_layout) );
				}
			}
		});
		promise.fail = seatreg_admin_ajax_error;
	});

$('.builder-popup-close').on('click', function() {
	if( window.seatreg.builder.needToSave == true) {
		alertify.set({ 
			labels: {
		    	ok     : translator.translate('yes'),
		    	cancel: translator.translate('no')
			},
			buttonFocus: "cancel"  
		});

		alertify.confirm(translator.translate('unsavedChanges'),function(e) {
			if (e) {
				$('.seatreg-builder-popup').css({'display':'none'});
				window.seatreg.builder.clearRegistrationData();
			} 
		});
	}else {
		$('.seatreg-builder-popup').css({'display':'none'});
		seatreg_clear_builder_data();
	}
});

$('#registration-start-timestamp').datepicker({
	altField: '#start-timestamp',
	altFormat: '@',
	dateFormat: 'dd.mm.yy'
}).on('keyup', function() {
	if($(this).val() == '') {
		$('#start-timestamp').val('');
	}
});

$('#registration-start-time').clockTimePicker();

$('#registration-end-timestamp').datepicker({
	altField: '#end-timestamp',
	altFormat: '@',
	dateFormat: 'dd.mm.yy'
}).on('keyup', function() {
	if($(this).val() == '') {
		$('#end-timestamp').val('');
	}
});

$('#registration-end-time').clockTimePicker();

function initOverviewCalendarDatePicker() {
	$('#overview-calendar-date').datepicker({
		altField: '#overview-calendar-date-value',
		altFormat: 'yy-mm-dd',
		dateFormat: 'yy-mm-dd',
		onSelect: function(dateText) {
			$('#overview-calendar-date').val( seatregFormatCalendarDateForDisplay(dateText, WP_Seatreg.SITE_LANGUAGE) );
			$('#existing-regs .room-list-item[data-active="true"]').trigger('click');
		}
	});

	var initialInternal = $('#overview-calendar-date-value').val();
    if (initialInternal) {
        $('#overview-calendar-date').val(
            seatregFormatCalendarDateForDisplay(initialInternal, WP_Seatreg.SITE_LANGUAGE)
        );
    }
}
initOverviewCalendarDatePicker();

function initBookingManagerCalendarDatePicer() {
	$('#booking-manager-calendar-date').datepicker({
		altField: '#bookings-calendar-date-value',
		altFormat: 'yy-mm-dd',
		dateFormat: 'yy-mm-dd',
		onSelect: function(dateText) {
			setCalendarDateUrlParam(dateText);
			$('#booking-manager-calendar-date').val( seatregFormatCalendarDateForDisplay(dateText, WP_Seatreg.SITE_LANGUAGE) );
			alertify.success(translator.translate('reloadingPage'));
			location.reload(); 
		}
	});
	var initialInternal = $('#booking-manager-calendar-date').val();
    if (initialInternal) {
        $('#booking-manager-calendar-date').val(
            seatregFormatCalendarDateForDisplay(initialInternal, WP_Seatreg.SITE_LANGUAGE)
        );
    }
}
initBookingManagerCalendarDatePicer();

function initEditBookingCalendarDatePicker() {
	$('#booking-edit-form #edit-date').datepicker({
		dateFormat: 'yy-mm-dd',
		onSelect: function(dateText) {
			
		}
	});
}
initEditBookingCalendarDatePicker();

$('#calendar-dates').multiDatesPicker({
	dateFormat: 'yy-mm-dd',
	separator: ','
});

$('.datepicker-altfield').each(function() {
	if( $(this).val() != '' ) {
		var date = new Date(parseInt( $(this).val() ));
		var formattedDate = date.format("d.m.Y");
		$(this).prev('.option-datepicker').val(formattedDate);
	}
});

$('#using-calendar').on('click', function() {
	if( $(this).is(":checked") ) {
		$('#calendar-dates').closest('.form-group').removeAttr("style");
	}else {
		$('#calendar-dates').closest('.form-group').css({
			display: 'none'
		});
	}
});


//add registration code to href in map builder
$('#registration-link').on('click', function() {
	var href = $(this).attr('href').split('?')[0];
	
	$(this).attr('href', href + '?seatreg=registration&c=' + seatreg.selectedRegistration + '&page_id=' + WP_Seatreg.seatreg_page_id);
});

$('.tab-container').easytabs({
	animate: false,
	animationSpeed: 0
}); 

$('#existing-regs-wrap').on('click', '.room-list-item', function() {
	var code = $('#seatreg-reg-code').val();
	var target = $(this).attr('data-stats-target');
	var calendarDate = $('#overview-calendar-date-value').val() || null;
	var overViewContainer = $(this).closest('.reg-overview');
	overViewContainer.append($('<img>').attr('src', WP_Seatreg.plugin_dir_url + 'img/ajax_loader.gif').addClass('ajax_loader'));

	var promise = seaterg_admin_ajax2('seatreg_get_room_stats', code, {
		target: target, 
		calendarDate: calendarDate
	});

	promise.done(function(data) {
		overViewContainer.replaceWith(data).promise().done(function() {
			$('#existing-regs-wrap .reg-overview').find('.time-stamp').each(function() {
				$(this).text(timeStampToDateString($(this).text()));
			});

			var donutWrapper = $('#existing-regs-wrap').find('.reg-overview-donuts');		
			var doughnutData = [
				{
					value: parseInt(donutWrapper.find('.seats-open-don').val()),
					color:"#61B329"
				},
				{
					value : parseInt(donutWrapper.find('.seats-bron-don').val()),
					color : "#FFFF00"
				},
				{
					value : parseInt(donutWrapper.find('.seats-taken-don').val()),
					color : "red"
				}
			
			];

			if( canvasSupport) {
				var ctx = donutWrapper.find('.stats-doughnut').get(0).getContext("2d");
				var myNewChart = new Chart(ctx).Doughnut(doughnutData,{animation: false});
			}

		});
		initOverviewCalendarDatePicker();
		
	});
	promise.fail = seatreg_admin_ajax_error;
});

$('.reg-overview-donuts').each(function() {
	var donutWrapper = $(this);
	var doughnutData = [
		{
			value: parseInt(donutWrapper.find('.seats-open-don').val()),
			color:"#61B329"
		},
		{
			value : parseInt(donutWrapper.find('.seats-bron-don').val()),
			color : "#FFFF00"
		},
		{
			value : parseInt(donutWrapper.find('.seats-taken-don').val()),
			color : "red"
		}
	
	];

	if(canvasSupport) {
		var ctx = donutWrapper.find('.stats-doughnut').get(0).getContext("2d");
		var myNewChart = new Chart(ctx).Doughnut(doughnutData,{animation: false});
	}			
});

/*
==================================================================================================================================================================================================================
Booking manager
==================================================================================================================================================================================================================
*/

(function() {
	var queryParams = new URLSearchParams(window.location.search);

	if( !queryParams.has('calendar-date') ) {
		var bookingManagerCalendarDate = $('#booking-manager-calendar-date').val();

		if( bookingManagerCalendarDate ) {
			//Calendar mode enabled. Lets update URL
			setCalendarDateUrlParam(bookingManagerCalendarDate);
		}
	} 
})();

function managerSearch() {
	var code = $('#seatreg-reg-code').val();
	var searchTerm = $('.manager-search').val();
	var wrapper = $('#seatreg-booking-manager .seatreg-tabs-content');
	var queryParams = new URLSearchParams(window.location.search); 
	wrapper.append($('<img>').attr('src', WP_Seatreg.plugin_dir_url + 'img/ajax_loader.gif').addClass('ajax_loader'));
	var promise = seaterg_admin_ajax2('seatreg_search_bookings', code, {
		searchTerm: searchTerm,
		orderby: bookingOrderInManager,
		calendarDate: queryParams.get('calendar-date')
	});

	promise.done(function(data) {
		wrapper.empty().html(data).promise().done(function() {
			wrapper.find('.tab-container').easytabs({
				animate: false,
				animationSpeed: 0
			});
		});
	});

	promise.fail = seatreg_admin_ajax_error;
}

$('#seatreg-booking-manager').on('click','.manager-box-link', function() {
	var code = $('#seatreg-reg-code').val();
	var searchTerm = $('.manager-search').val();
	var orderBy = $(this).attr('data-order');
	bookingOrderInManager = orderBy;
	var wrapper = $('#seatreg-booking-manager .seatreg-tabs-content');
	wrapper.append($('<img>').attr('src', WP_Seatreg.plugin_dir_url + 'img/ajax_loader.gif').addClass('ajax_loader'));

	var promise = seaterg_admin_ajax2('seatreg_get_booking_manager', code, {searchTerm: searchTerm, orderby: orderBy});

	promise.done(function(data) {
		wrapper.empty().html(data).promise().done(function() {
			wrapper.find('.tab-container').easytabs({
				animate: false,
				animationSpeed: 0
				//updateHash: false
			});
		});
	});
	
	promise.fail = seatreg_admin_ajax_error;
});

//remove input check from other bookings. Mark same bookings checked
$('#seatreg-booking-manager').on('click', '.bron-action', function() {
	var check = $(this);
	var bookingId = check.closest('.reg-seat-item').find('.booking-identification').val();

	check.closest('.action-select').find('.bron-action').not(check).prop('checked', false);

	var confirmCheck = check.closest('.reg-seat-item').find('.bron-action[data-action=confirm]').is(':checked');
	var unapproveCheck = check.closest('.reg-seat-item').find('.bron-action[data-action=unapprove]').is(':checked');
		
	$(this).closest('.tab_container').find('.bron-action').not(check).each(function() {
		if( $(this).closest('.reg-seat-item').find('.booking-identification').val() == bookingId ) {

			if(!check.is('[data-action=del]')) {
				$(this).closest('.reg-seat-item').find('.bron-action[data-action=del]').prop('checked', false);
			}
			
			$(this).closest('.reg-seat-item').find('.bron-action[data-action=confirm]').prop('checked', confirmCheck);
			$(this).closest('.reg-seat-item').find('.bron-action[data-action=unapprove]').prop('checked', unapproveCheck);
		}
	});
});

$('#seatreg-booking-manager').on('click', '.show-more-info', function() {
	$(this).parent().find('.more-info').slideToggle();
});

$(document).on('shown.bs.modal', '#booking-activity-modal', function () {
	var modalBody = $(this).find('.modal-body');
	var loading = modalBody.find('.activity-modal__loading');
	var logsWrap = modalBody.find('.activity-modal__logs');
	var bookingId = $(this).attr('data-booking-id');
    
	logsWrap.empty();
	loading.html(
		$('<img>').attr('src', WP_Seatreg.plugin_dir_url + 'img/ajax_loader.gif')
	);

	var promise = seatreg_get_booking_logs(bookingId);

	promise.done(function(data) {
		loading.empty();
		var logs = data._response.data;

		if(Array.isArray(logs) && logs.length > 0) {
			logs.forEach(function(log) {
				logsWrap.append('<div>'+ log.log_date +'</div>').append('<div>'+ log.log_message +'</div>');
			});
		}else {
			logsWrap.append(translator.translate('noActivityLogged'));
		}
	});
	
	promise.fail = seatreg_admin_ajax_error;
});

$('#registration-activity-modal').on('shown.bs.modal', function () {
	var modalBody = $(this).find('.modal-body');
	var loading = modalBody.find('.activity-modal__loading');
	var logsWrap = modalBody.find('.activity-modal__logs');
	var registrationId = $(this).attr('data-registration-id');

	logsWrap.empty();
	loading.html(
		$('<img>').attr('src', WP_Seatreg.plugin_dir_url + 'img/ajax_loader.gif')
	);

	var promise = seatreg_get_registration_logs(registrationId);

	promise.done(function(data) {
		loading.empty();
		var logs = data._response.data;

		if(Array.isArray(logs) && logs.length > 0) {
			logs.forEach(function(log) {
				logsWrap.append('<div>'+ log.log_date +'</div>').append('<div>'+ log.log_message +'</div>');
			});
		}else {
			logsWrap.append(translator.translate('noActivityLogged'));
		}
	});
	
	promise.fail = seatreg_admin_ajax_error;
});

$('.seatreg-registrations [data-action=view-more-modal]').on('click', function(e) {
	e.preventDefault();

	$(this).closest('[data-item="registration"]').find('.more-items-modal').modal('show');
});

$('.seatreg-registrations [data-action=open-copy-registration').on('click', function(e) {
	e.preventDefault();

	$(this).closest('[data-item="registration"]').find('.copy-registration-modal').modal('show');
});

$('.seatreg-registrations [data-action=view-shortcode').on('click', function(e) {
	e.preventDefault();

	$(this).closest('[data-item="registration"]').find('.shortcode-modal').modal('show');
});

$('#seatreg-booking-manager').on('click', 'button[data-action=view-booking-activity]', function() {
	$bookingId = $(this).data('booking-id');
	$('#booking-activity-modal').find('.activity-modal__logs').empty();
	$('#booking-activity-modal').attr('data-booking-id', $bookingId).modal('show');
});

$('.seatreg-registrations [data-action=view-registration-activity').on('click', function(e) {
	e.preventDefault();
	var registrationId = $(this).data('registration-id');
	$('#registration-activity-modal').find('.activity-modal__logs').empty();
	$('#registration-activity-modal').attr('data-registration-id', registrationId).modal('show');
});

$('#seatreg-booking-manager').on('click', 'button[data-action=change-payment-status]', function(e) {
	e.preventDefault();
	var $this = $(this);
	var bookingId = $this.closest('.reg-seat-item').data('booking-id');
	var selectedStatus = $this.siblings('select[name="payment-status"]').val();
	var selectedStatusText = $this.siblings('select[name="payment-status"]').find(":selected").text();

	$this.prop('disabled', true);

	var promise = seaterg_admin_ajax('seatreg_booking_payment_status_change', bookingId, {
		bookingStatus: selectedStatus
	});

	promise.done(function() {
		var $bookingItems = $('.reg-seat-item[data-booking-id="'+ bookingId +'"]');

		$bookingItems.each(function() {
			$(this).find('.payment-status-box').text( selectedStatusText );
			$(this).find('[data-place="payment-status"]').text(selectedStatusText);
		});

		alertify.success(translator.translate('paymentStatusUpdated'));
	});

	promise.always(function() {
		$this.removeAttr("disabled");
	});

	promise.fail = seatreg_admin_ajax_error;
});

$('#seatreg-booking-manager').on('click', 'button[data-action=add-payment-log]', function() {
	var $this = $(this);
	$this.prop('disabled', true);
	var bookingId = $this.data('booking-id');
	var logType = $this.closest('.add-payment-log-wrap').find('.payment-log-type').val();
	var logMessage = $this.closest('.add-payment-log-wrap').find('.payment-log-message').val();
	
	var promise = seatreg_create_payment_log(bookingId, logType, logMessage);

	promise.done(function() {
		$this.removeAttr("disabled");
		var $logsWrappers = $('#seatreg-booking-manager .reg-seat-item[data-booking-id="'+ bookingId +'"]').find('.payment-log-wrap');
		var logClass = '';

		if(logType === 'error') {
			logClass = 'error-log';
		}else if(logType === 'info') {
			logClass = 'info-log';
		}

		$logsWrappers.each(function() {
			$(this).append('<div class="'+ logClass +'">'+ logType +'</div><div class="'+ logClass +'">' + translator.translate('momentAgo') + '</div><div class="'+ logClass +'">'+ logMessage +'</div>');
		});
		$this.closest('.add-payment-log-wrap').find('.payment-log-message').val('');
	});
	
	promise.fail = seatreg_admin_ajax_error;
});

//when search bookings
$('#seatreg-booking-manager').on('click', '.search-button', function() {
	managerSearch();
});

$('#seatreg-booking-manager').on('keydown', '.manager-search', function(e) {
	if(e.key === "Enter") {
		managerSearch();
	}
});

$('#seatreg-booking-manager').on('click', '.action-control', function() {
	var button = $(this);
	var data = [];
	var code = $('#seatreg-reg-code').val();
	var searchTerm = $('.manager-search').val();
	var wrapper = $('#seatreg-booking-manager .seatreg-tabs-content');
	var queryParams = new URLSearchParams(window.location.search);
	var calendarDate = queryParams.get('calendar-date');

	wrapper.append($('<img>').attr('src', WP_Seatreg.plugin_dir_url + 'img/ajax_loader.gif').addClass('ajax_loader'));
	button.parent().find('.reg-seat-item').each(function() {
		$(this).find('.bron-action').each(function() {
			if($(this).prop('checked')) {
				if($(this).attr('data-action') == 'del') {
					data.push({
						booking_id: $(this).val(),
						action: 'del',
						room_name: $(this).closest('.reg-seat-item').find('.seat-room-box').text(),
						seat_nr: $(this).closest('.reg-seat-item').find('.seat-nr-box').text(),
						seat_id: $(this).closest('.reg-seat-item').find('.seat-id').val(),
					});
				}else if($(this).attr('data-action') == 'confirm') {
					data.push({
						booking_id: $(this).val(),
						action: 'conf',
						room_name: $(this).closest('.reg-seat-item').find('.seat-room-box').text(),
						seat_nr: $(this).closest('.reg-seat-item').find('.seat-nr-box').text()
					});
				}else if($(this).attr('data-action') == 'unapprove') {
					data.push({
						booking_id: $(this).val(),
						action: 'unapprove',
						room_name: $(this).closest('.reg-seat-item').find('.seat-room-box').text(),
						seat_nr: $(this).closest('.reg-seat-item').find('.seat-nr-box').text()
					});
				}
			}
		});
	});


	var promise = seaterg_admin_ajax2('seatreg_confirm_del_bookings', code, {
		searchTerm: searchTerm,
		orderby: bookingOrderInManager, 
		actionData: JSON.stringify(data),
		calendarDate: calendarDate
	});

	promise.done(function(data) {
		wrapper.empty().html(data).promise().done(function() {
			wrapper.find('.tab-container').easytabs({
				animate: false,
				animationSpeed: 0
			});
			if(calendarDate) {
				initBookingManagerCalendarDatePicer();
			}
			alertify.success(translator.translate('bookingStatusUpdated'));
		});
	});

	promise.fail = seatreg_admin_ajax_error;
});

$('#seatreg-booking-manager').on('click', '#add-modal-add-seat', function() {
	var bookingItemsWrap = $(this).closest('form').find('.modal-body-items');
	var bookingItems = bookingItemsWrap.find('.modal-body-item');
	var newItem = bookingItems.first().clone();

	newItem.find('input[name="seat-id[]"]').val('');
	newItem.find('.add-modal-input-wrap[data-type="price-selection"]').remove();
	
	bookingItemsWrap.append(newItem);
});

$('#seatreg-booking-manager').on('click', '#add-modal-remove-seat', function() {
	var bookingItemsWrap = $('#add-booking-modal-form .modal-body-item');

	if(bookingItemsWrap.length === 1) {
		return;
	}
	bookingItemsWrap.last().remove();
});

$('#seatreg-booking-manager').on('click', '.add-booking', function() {
	var customFields = $(this).data('custom-fields') || [];
	var registrationCode = $(this).data('registration-code');
	var modal = $('#add-booking-modal');
	var modalCutsom = modal.find('.modal-body-custom');

	modalCutsom.empty();
	modal.find('#add-booking-registration-id').val(registrationCode);
	customFields.forEach(function(customField) {
		var type = customField.type;
		var label = customField.label;

		if(type === "check") {
			modalCutsom.append('<div class="modal-custom" data-type="check"><label for="'+ label +'" class="modal-custom-l"><h5>'+ label +'</h5></label><br><input type="checkbox" id="'+ label +'" class="modal-custom-v" /></div>');
		}else if(type === "sel") {
			var selectOptions = customField.options;

			if(Array.isArray(selectOptions)) {
				modalCutsom.append('<div class="modal-custom"><label class="modal-custom-l" for="'+ label +'"><h5>'+ label+ '</h5></label><br><select id="'+ label +'" class="modal-custom-v">' +  selectOptions.map((option) => {
					return '<option>' + option + '</option>';
				})  + '</select>' + '</div>');
			}
		}else {
			modalCutsom.append('<div class="modal-custom"><label class="modal-custom-l" for="'+ label +'"><h5>'+ label +'</h5></label><br><input type="text" id="'+ label +'" class="modal-custom-v" /></div>');
		}
	});
	modal.modal('show');
});
$('#seatreg-booking-manager').on('click', '.import-bookings', function() {
	var modal = $('#import-bookings-modal');

	$('#import-bookings-modal .import-booking-modal-error').empty();
	$('#import-bookings-modal input[name="csv-file"]').val('');

	modal.modal('show');
});

$('#seatreg-booking-manager').on('change', 'input[name="csv-file"]', function() {
	var file = this.files[0];

	if(file) {
		var formData = new FormData();
		var $loader = $('#import-bookings-modal .import-booking-modal-loading');
		var $error = $('#import-bookings-modal .import-booking-modal-error')

		formData.append('csv-file', file);
		formData.append('seatreg-code', $('#import-bookings-modal input[name="seatreg-code"]').val());
		formData.append('security', WP_Seatreg.nonce);
		formData.append('action', 'seatreg_inspect_booking_csv');

		$.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false, // Prevent jQuery from automatically transforming the data into a query string
            contentType: false, // Prevent jQuery from setting the content type
            dataType: 'json', // Specify the type of data expected back from the server
			beforeSend: function() {
				$loader.show();
				$error.hide();
			},
            success: function(response) {
				$loader.hide();
				$('#import-bookings-modal').modal('hide');
				$('#import-bookings-finalization-modal').trigger('generate.markup', response);
				$('#import-bookings-finalization-modal').modal('show');
              				
            },
            error: function(jqXHR, textStatus, errorThrown) {
				$loader.hide();

				try {
					var responsePayload = JSON.parse(jqXHR.responseText);

					$error.text(responsePayload.data);
					$error.show();
				} catch (e) {
					$error.text('Error analyzing CSV file');
					$error.show();
				}
            }
        });
	}
});

$('#import-bookings-finalization-modal').on('generate.markup', function (event, response) {
    var $modal = $(this);
	var $bookingsWrap = $modal.find('[data-element="modal-bookings-wrap"]');

    if (response && response.data) {
		var csvData = response.data.sort(function(a, b) {
			return a.is_valid - b.is_valid;
		});
		var problematicRowsCount = csvData.reduce(function(count, row) {
            return count + (row.is_valid ? 0 : 1);
        }, 0);

        $modal.find('[data-element="modal-info"]').html(
			'<p>You are trying to import total of ' + csvData.length + ' bookings. <span class="warn-text">'  + problematicRowsCount + '</span> of those have conflicts and cant be imported</p>'
		);

		csvData.forEach(function(row) {
			var data = normalizeImportData(row.csv_row);

			$bookingsWrap.append(seatregGenerateImportBookingBox(data, {is_valid: row.is_valid, messages: row.messages, room_name: row.room_name}));
		});

		$('#import-bookings-finalization-modal [data-powertip]').powerTip({
			followMouse : false,
			placement: 'sw',
			popupClass: 'import-bookings-finalization-modal__popup'
		});

		if( csvData.length > problematicRowsCount )  {
			$modal.find('button[data-action="start-booking-import"]').prop('disabled', false); 
		}else {
			$modal.find('button[data-action="start-booking-import"]').prop('disabled', true);
		}
    }
    
});

function seatregGenerateImportBookingBox(bookingData, validationData) {
	var $bookingWrap = $('<div class="import-bookings-finalization-modal__booking"></div>'); 

	if( !validationData.is_valid ) {
		$bookingWrap.addClass('import-bookings-finalization-modal__booking--invalid');
	}else {
		$bookingWrap.attr('data-is-valid', 'true');
	}
	
	$bookingWrap.append('<div><b>Name: </b>' + bookingData.first_name + ',</div>');
	$bookingWrap.append('<div>' + bookingData.last_name + ',</div>');
	$bookingWrap.append('<div><b>Seat: </b>' + bookingData.seat_nr + '</div>');

	if(validationData.room_name) {
		$bookingWrap.append('<div><b>Room: </b>' + validationData.room_name + '</div>');
	}

	if( !validationData.is_valid ) {
		$bookingWrap.append('<i class="fa fa-exclamation-triangle import-bookings-finalization-modal__warning-icon" aria-hidden="true" data-powertip="' +  validationData.messages.join(', ')  + '"></i>');
	}else {
		$bookingWrap.append('<i class="fa fa-trash-o import-bookings-finalization-modal__trash-icon" data-action="remove-row" aria-hidden="true" data-powertip="Remove from the import"></i>');
	}

	var hiddenInputs = [
		'<input type="hidden" data-name="first_name" value="' + bookingData.first_name + '" />',
		'<input type="hidden" data-name="last_name" value="' + bookingData.last_name + '" />',
		'<input type="hidden" data-name="email" value="' + bookingData.email + '" />',
		'<input type="hidden" data-name="seat_id" value="' + bookingData.seat_id + '" />',
		'<input type="hidden" data-name="seat_nr" value="' + bookingData.seat_nr + '" />',
		'<input type="hidden" data-name="room_uuid" value="' + bookingData.room_uuid + '" />',
		'<input type="hidden" data-name="booking_date" value="' + bookingData.booking_date + '" />',
		'<input type="hidden" data-name="booking_confirm_date" value="' + bookingData.booking_confirm_date + '" />',
		'<input type="hidden" data-name="custom_field_data" value=\'' + bookingData.custom_field_data + '\' />',
		'<input type="hidden" data-name="status" value="' + bookingData.status + '" />',
		'<input type="hidden" data-name="booking_id" value="' + bookingData.booking_id + '" />',
		'<input type="hidden" data-name="booker_email" value="' + bookingData.booker_email + '" />',
		'<input type="hidden" data-name="multi_price_selection" value="' + bookingData.multi_price_selection + '" />',
		'<input type="hidden" data-name="logged_in_user_id" value="' + bookingData.logged_in_user_id + '" />'
	].join('');
	
	$bookingWrap.append(hiddenInputs);
        	
	return $bookingWrap;
}

function normalizeImportData(csvRow) {
	return {
		first_name: csvRow[WP_Seatreg.SEATREG_CSV_COL_FIRST_NAME],
		last_name: csvRow[WP_Seatreg.SEATREG_CSV_COL_LAST_NAME],
		email: csvRow[WP_Seatreg.SEATREG_CSV_COL_EMAIL], 
		seat_nr: csvRow[WP_Seatreg.SEATREG_CSV_COL_SEAT_NR],
		seat_id: csvRow[WP_Seatreg.SEATREG_CSV_COL_SEAT_ID],
		room_uuid: csvRow[WP_Seatreg.SEATREG_CSV_COL_ROOM_UUID],
		booking_date: csvRow[WP_Seatreg.SEATREG_CSV_COL_BOOKING_DATE],
		booking_confirm_date: csvRow[WP_Seatreg.SEATREG_CSV_COL_BOOKING_CONFIRM_DATE],
		custom_field_data: csvRow[WP_Seatreg.SEATREG_CSV_COL_CUSTOM_FIELD_DATA],
		status: csvRow[WP_Seatreg.SEATREG_CSV_COL_STATUS],
		booking_id: csvRow[WP_Seatreg.SEATREG_CSV_COL_BOOKING_ID],
		booker_email: csvRow[WP_Seatreg.SEATREG_CSV_COL_BOOKER_EMAIL] ,
		multi_price_selection: csvRow[WP_Seatreg.SEATREG_CSV_COL_MULTI_PRICE_SELECTION],
		logged_in_user_id: csvRow[WP_Seatreg.SEATREG_CSV_COL_LOGGED_IN_USER_ID]
	};
}

$('#import-bookings-finalization-modal').on('click', '[data-action="remove-row"]', function() {
	if (window.confirm("Are you sure you want to remove this import?")) {
		$(this).closest('.import-bookings-finalization-modal__booking').remove();
	}
});

$('#import-bookings-finalization-modal button[data-action="start-booking-import"]').on('click', function (event) {
	var bookingsImportData = [];
	var $importBtn = $('#import-bookings-finalization-modal button[data-action="start-booking-import"]');
	var importBtnText = $importBtn.text();

	$('#import-bookings-finalization-modal .import-bookings-finalization-modal__booking[data-is-valid="true"]').each(function() {
		var $booking = $(this);
		var bookingData = {
			first_name: $booking.find('[data-name="first_name"]').val(),
			last_name: $booking.find('[data-name="last_name"]').val(),
			email: $booking.find('[data-name="email"]').val(),
			seat_nr: $booking.find('[data-name="seat_nr"]').val(),
			seat_id: $booking.find('[data-name="seat_id"]').val(),
			room_uuid: $booking.find('[data-name="room_uuid"]').val(),
			booking_date: $booking.find('[data-name="booking_date"]').val(),
			booking_confirm_date: $booking.find('[data-name="booking_confirm_date"]').val(),
			custom_field_data: $booking.find('[data-name="custom_field_data"]').val(),
			status: $booking.find('[data-name="status"]').val(),
			booking_id: $booking.find('[data-name="booking_id"]').val(),
			booker_email: $booking.find('[data-name="booker_email"]').val(),
			multi_price_selection: $booking.find('[data-name="multi_price_selection"]').val(),
			logged_in_user_id: $booking.find('[data-name="logged_in_user_id"]').val()
		};
		bookingsImportData.push(bookingData);
	});

	$.ajax({
		url: ajaxurl,
		type: 'POST',
		dataType: 'json',
		data: {
			action: 'seatreg_import_bookings',
			security: WP_Seatreg.nonce,
			bookingsImport: JSON.stringify(bookingsImportData),
			code: $importBtn.data('code')
		},
		beforeSend: function() {
			$importBtn.prop('disabled', true).text('Importing...');
		},
		success: function(response) {
			$importBtn.prop('disabled', false).text(importBtnText);
			$('#import-bookings-finalization-modal .import-bookings-finalization-modal__bookings').empty();

			if(response.success) {
				$('.import-bookings-finalization-modal__info').html('<h6>Import of ' + response.successImports.length + ' bookings completed. Please refresh the page.</h6>');
				$importBtn.css('display', 'none');
			}else {
				$('.import-bookings-finalization-modal__info').html('<p>Failed to import ' + response.failedImports.length + ' bookings</p>');

				response.failedImports.forEach(function(failedImport) {
					$('#import-bookings-finalization-modal .import-bookings-finalization-modal__bookings').append(
						seatregGenerateImportBookingBox(failedImport.bookingData, {is_valid: false, messages: failedImport.messages})
					)
				}); 
			}			
		},
		error: function(jqXHR, textStatus, errorThrown) {
			$importBtn.prop('disabled', false).text(importBtnText);
		}
	});
});

//booking edit click. Show edit modal
$('#seatreg-booking-manager').on('click', '.edit-btn',function() {
	var info = $(this).parent();
	var modal = $('#edit-modal');
	var modalCutsom = modal.find('.modal-body-custom');
	modalCutsom.empty();

	modal.find('#edit-seat').val(info.find('.seat-id').val());
	modal.find('#edit-room').val(info.find('.seat-room-box').text());
	modal.find('#edit-fname').val(info.find('.f-name').val());
	modal.find('#edit-lname').val(info.find('.l-name').val());
	modal.find('#modal-code').val($(this).attr('data-code'));
	modal.find('#booking-id').val($(this).attr('data-booking'));
	modal.find('#r-id').val($(this).attr('data-id'));
	modal.find('#edit-booking-seat-nr').val(info.find('.seat-nr-box').text());
	info.find('.custom-field').each(function() {
		var type = $(this).data('type');

		if(type === "check") {
			var isChecked = $(this).find('.custom-field-value').data('checked') === true ? 'checked' : '';

			modalCutsom.append('<div class="modal-custom" data-type="check"><label for="'+ $(this).find('.custom-field-label').text() +'" class="modal-custom-l"><h5>'+ $(this).find('.custom-field-label').text() +'</h5></label><br><input type="checkbox" id="'+ $(this).find('.custom-field-label').text() +'" class="modal-custom-v" ' + isChecked +' /></div>');
		}else if(type === "sel") {
			var selectOptions = $(this).find('.custom-field-value').data('options');
			var selectedOption = $(this).find('.custom-field-value').text().trim();

			if(Array.isArray(selectOptions)) {
				modalCutsom.append('<div class="modal-custom"><label class="modal-custom-l" for="'+ $(this).find('.custom-field-label').text() +'"><h5>'+ $(this).find('.custom-field-label').text() + '</h5></label><br><select id="'+ $(this).find('.custom-field-label').text() +'" class="modal-custom-v">' +  selectOptions.map((option) => {
					if(option === selectedOption) {
						return '<option selected>' + option + '</option>';
					}
					return '<option>' + option + '</option>';
				})  + '</select>' + '</div>');
			}

		}else {
			modalCutsom.append('<div class="modal-custom"><label class="modal-custom-l" for="'+ $(this).find('.custom-field-label').text() +'"><h5>'+ $(this).find('.custom-field-label').text() +'</h5></label><br><input type="text" id="'+ $(this).find('.custom-field-label').text() +'" class="modal-custom-v" value="'+ $(this).find('.custom-field-value').text() +'" /></div>');
		}
	});

	$('#edit-room-error, #edit-seat-error').text('');
	modal.modal('show');
});

$('#bookings-file-modal .custom-filtering').on('click', function() {
	$('.custom-filtering-selection').toggle();
});

$("#bookings-file-modal .fa[data-action='remove']").on('click', function() {
	$customField = $(this).closest('.custom-field').children(':not(.fa)').clone();

	$('#bookings-file-form .form-fields').append($('<div class="mb-1">').append($customField));
});

$('#seatreg-booking-manager').on('click', '#generate-bookings-file', function() {
	var href = $(this).attr('data-link');
	var getParams = $('#bookings-file-form :input').filter(function(index, element) {
		return $(element).val() != '';
    }).serialize();
	var calendarDateparam = "";
	var queryParams = new URLSearchParams(window.location.search);

	if(queryParams.has('calendar-date')) {
		calendarDateparam += "&calendar-date=" + queryParams.get('calendar-date');
	}

	window.open(href + '&' + getParams +  calendarDateparam, '_blank');
});

$('#seatreg-booking-manager').on('click', '.seat-id-search', function() {
	bookingManagerActiveAddBookingIdLookupIndex = $(this).closest('.modal-body-item').index();
	$('#seat-id-modal').modal('show');
});

$('#seatreg-booking-manager .seat-id-grid [data-action="select-id"]').on('click', function() {
	const $modalBodyItem = $('#seatreg-booking-manager #add-booking-modal-form .modal-body-item').eq(bookingManagerActiveAddBookingIdLookupIndex);

	$modalBodyItem.find('[name="seat-id[]"]').val( $(this).data('seat-id') ).trigger('change');
	$('#seat-id-modal').modal('hide');
});

$('#seatreg-booking-manager').on('change keyup', '#add-booking-modal-form input[name="seat-id[]"]', function() {
    const seatPrice = $('#seat-id-modal .seat-id-grid').find('[data-seat-id="'+ $(this).val() +'"]').data('seat-price');
	const $modalBodyItem = $('#seatreg-booking-manager #add-booking-modal-form .modal-body-item').eq(bookingManagerActiveAddBookingIdLookupIndex);
	$modalBodyItem.find('.add-modal-input-wrap[data-type="price-selection"').remove();
	$modalBodyItem.find('input[name="seat-multi-price[]"]').remove();

	if(Array.isArray(seatPrice)) {
		$modalBodyItem.find('.add-modal-input-wrap').last().after(`
			<div class="add-modal-input-wrap" data-type="price-selection">
				<label>
					<h5>
						${translator.translate('price')}
					</h5>
					<select name="seat-multi-price[]">
						${seatPrice.map((price) => {
							return `<option value="${price.uuid}">${price.price} (${price.description})</option>`;
						}).join('')}
					</select>
					<div class="input-error"></div>
				</label>
			</div>
		`);
	}else {
		$modalBodyItem.find('.add-modal-input-wrap').last().after(`
			<input type="hidden" name="seat-multi-price[]" value="" />			
		`);
	}
});


$('#seatreg-booking-manager').on('click', '#add-booking-btn', function() {
	$(this).css('display','none').after('<img src="' + WP_Seatreg.plugin_dir_url + 'img/ajax_loader_small.gif' + '" alt="Loading..." class="ajax-load" />');
	var subBtn = $(this);
	var modal = $('#add-booking-modal');
	var allFieldsValid = true;
	var allBookingCustomFields = [];

	modal.find('.modal-body-item').each(function() {
		var booking = $(this);
		var currentBookingItemCustomFields = [];
		
		booking.find('.input-error').text('');

		if(booking.find('[name="seat-id[]"]').val() === '') {
			booking.find('[name="seat-id[]"]').closest('.add-modal-input-wrap').find('.input-error').text('No ID');
			subBtn.css('display','inline').next().css('display','none');
			allFieldsValid = false;
		}
		if(booking.find('[name="room[]"]').val() === ''){
			booking.find('[name="room[]"]').closest('.add-modal-input-wrap').find('.input-error').text('No room');
			subBtn.css('display','inline').next().css('display','none');
			allFieldsValid = false;
		}
		
		if (WP_Seatreg.require_name)
		{
			if (booking.find('[name="first-name[]"]').val() === '')
			{
				booking.find('[name="first-name[]"]').closest('.add-modal-input-wrap').find('.input-error').text('First name empty');
				subBtn.css('display', 'inline').next().css('display', 'none');
				allFieldsValid = false;
			}
			if (booking.find('[name="last-name[]"]').val() === '')
			{
				booking.find('[name="last-name[]"]').closest('.add-modal-input-wrap').find('.input-error').text('Last name empty');
				subBtn.css('display', 'inline').next().css('display', 'none');
				allFieldsValid = false;
			}
		}
		
		if(booking.find('[name="email[]"]').val() === ''){
			booking.find('[name="email[]"]').closest('.add-modal-input-wrap').find('.input-error').text('Email is empty');
			subBtn.css('display','inline').next().css('display','none');
			allFieldsValid = false;
		}

		booking.find('.modal-custom').each(function() {
			var custObj = {};
			var type = $(this).data('type');

			custObj['label'] = $(this).find('.modal-custom-l h5').text();
		
			if(type === 'check') {
				custObj['value'] = $(this).find('.modal-custom-v').is(':checked') ? '1' : '0';
			}else if(type === 'sel') {
				custObj['value'] = $(this).find('.modal-custom-v').find(":selected").text();
			}else {
				custObj['value'] = $(this).find('.modal-custom-v').val();
			}
		
			currentBookingItemCustomFields.push(custObj);
		});

		allBookingCustomFields.push(currentBookingItemCustomFields);
	});

	modal.find('[name="custom-fields"]').val(JSON.stringify(allBookingCustomFields));

	if(!allFieldsValid) {
		return;
	}

	var promise = seatreg_add_booking_with_manager();

	promise.done(function(resp) {
		subBtn.css('display','inline').next().css('display','none');
		
		if(resp.success === true) {
			alertify.success(translator.translate('newBookingWasAddedRefreshingThaPage'));

			setTimeout(function() {
				window.location.reload();
			}, 2000);

		}else {
			var data = resp.data;

			if(data.status === 'room-searching') {
				$('#add-booking-modal-form .modal-body-item').eq(data.index).find('[name="room[]"]').closest('.add-modal-input-wrap').find('.input-error').text(translator.translate('roomNotExist'));
				alertify.error(translator.translate('roomNotExist'));
			}
			if(data.status === 'seat-id-searching') {
				$('#add-booking-modal-form .modal-body-item').eq(data.index).find('[name="seat-id[]"]').closest('.add-modal-input-wrap').find('.input-error').text(translator.translate('seatIdNotExist'));
				alertify.error(translator.translate('seatIdNotExist'));
			}
			if(data.status === 'seat-booked') {
				$('#add-booking-modal-form .modal-body-item').eq(data.index).find('[name="seat-id[]"]').closest('.add-modal-input-wrap').find('.input-error').text(translator.translate('seatAlreadyBookedPending'));
				alertify.error(translator.translate('seatAlreadyBookedPending'));
			}
			if(data.status === 'create failed') {
				alert(translator.translate('errorBookingUpdate'));
			}
			if(data.status === 'custom field validation failed') {
				if(data.message === 'Max seats limit exceeded') {
					alert('Max seats limit exceeded');
				}else {
					alert('Custom field validation failed. ' + data.message);
				}
			}
			if(data.status === 'duplicate-seat') {
				alertify.error(translator.translate('duplicateSeatDetected'));
			}
			if(data.status === 'seat-price-not-found') {
				$('#add-booking-modal-form .modal-body-item').eq(data.index).find('[name="seat-multi-price[]"]').closest('.add-modal-input-wrap').find('.input-error').text(translator.translate('priceNotFound'));
			}
		}
	});
	promise.fail = seatreg_admin_ajax_error;
});

$('#seatreg-booking-manager').on('click', '#edit-update-btn', function() {
	$(this).css('display','none').after('<img src="' + WP_Seatreg.plugin_dir_url + 'img/ajax_loader_small.gif' + '" alt="Loading..." class="ajax-load" />');
	var subBtn = $(this);
	var modal = $('#edit-modal');
	var customFields = [];
	var code = $('#seatreg-reg-code').val();
	var seatId = modal.find('#edit-seat').val();
	var seat_room = modal.find('#edit-room').val(); 
	var first_name = modal.find('#edit-fname').val();
	var last_name = modal.find('#edit-lname').val();
	var calendarMode = $('#edit-date').length >= 1;

	$('#edit-room-error, #edit-seat-error, #edit-date-error').text('');
	if( seatId == '' ) {
		$('#edit-seat-error').text('No seat');
		subBtn.css('display','inline').next().css('display','none');

		return;
	}
	if(seat_room == ''){
		$('#edit-room-error').text('No room');
		subBtn.css('display','inline').next().css('display','none');

		return;
	}

	if (WP_Seatreg.require_name)
	{
		if (first_name == '')
		{
			$('#edit-fname-error').text('First name empty');
			subBtn.css('display', 'inline').next().css('display', 'none');
			
			return;
		}
		if (last_name == '')
		{
			$('#edit-lname-error').text('Last name empty');
			subBtn.css('display', 'inline').next().css('display', 'none');
			
			return;
		}
	}
	
	if(calendarMode && $('#edit-date').val() === '') {
		$('#edit-date-error').text('Date is empty');
		subBtn.css('display','inline').next().css('display','none');

		return;
	}

	modal.find('.modal-custom').each(function() {
		var custObj = {};

		if($(this).find('.modal-custom-v').val() != 'Not set' && $(this).find('.modal-custom-v').val() != '') {
			var type = $(this).data('type');

			custObj['label'] = $(this).find('.modal-custom-l h5').text();
			
			if(type === 'check') {
				custObj['value'] = $(this).find('.modal-custom-v').is(':checked') ? '1' : '0';
			}else if(type === 'sel') {
				custObj['value'] = $(this).find('.modal-custom-v').find(":selected").text();
			}else {
				custObj['value'] = $(this).find('.modal-custom-v').val();
			}
			
			customFields.push(custObj);
		}
	});

	editInfo = {
		'firstName': first_name,
		'lastName': last_name,
		'bookingId': $('#booking-id').val(),
		'seatId': seatId,
		'customFieldData': JSON.stringify(customFields),
		'seatRoom': seat_room,
		'id': $('#r-id').val(),
		'calendarDate': $('#edit-date').val()
	}

	var promise = seatreg_edit_booking('seatreg_edit_booking', code, editInfo);

	promise.done(function(data) {
		subBtn.css('display','inline').next().css('display','none');
		
		if(data.status == 'updated') {
			var bookingLoc = $('#r-id').val();
			var bookingInfo = $('#seatreg-booking-manager .edit-btn[data-id="'+ bookingLoc +'"]').parent();
			bookingInfo.find('.seat-nr-box').text(data.newSeatNr);
			bookingInfo.find('.seat-room-box').text(seat_room);
			bookingInfo.find('.seat-name-box').attr('title', first_name + ' ' + last_name).find('.full-name').text(first_name + ' ' + last_name);
			bookingInfo.find('.f-name').val(first_name);
			bookingInfo.find('.l-name').val(last_name);

			//correct custom fields
			var a = customFields.length;
			bookingInfo.find('.custom-field').each(function() {
				var found = false;

				for(var i = 0; i < a; i++) {
					if($(this).find('.custom-field-label').text() == customFields[i]['label']) {
						found = true;

						if( $(this).data('type') === 'check') {
							if(customFields[i]['value'] === '1') {
								$(this).find('.custom-field-value').replaceWith('<i class="fa fa-check custom-field-value" data-type="check" data-checked="true" aria-hidden="true"></i>');
							}else {
								$(this).find('.custom-field-value').replaceWith('<i class="fa fa-times custom-field-value" data-type="check" data-checked="false" aria-hidden="true"></i>');
							}
						}else {
							$(this).find('.custom-field-value').text(customFields[i]['value']);
						}
						
						break;
					}
				}

				if(!found) {
					$(this).find('.custom-field-value').text(translator.translate('notSet'));
				}
			});

			if( calendarMode && $('#edit-date').val() !== $('#booking-manager-calendar-date').val() ) {
				//Calendar date change.Remove booking from current view
				bookingInfo.remove();
			}
			alertify.success(translator.translate('bookingUpdated'));

		}else {
			if(data.status == 'room-searching') {
				$('#edit-room-error').text(translator.translate('roomNotExist'));
				alertify.error(translator.translate('roomNotExist'));
			}
			if(data.status == 'seat-id-searching') {
				$('#edit-seat-error').text(translator.translate('seatIdNotExist'));
				alertify.error(translator.translate('seatIdNotExist'));
			}
			if(data.status == 'seat-booked') {
				$('#edit-seat-error').text(translator.translate('seatAlreadyBookedPending'));
				alertify.error(translator.translate('seatAlreadyBookedPending'));
			}
			if(data.status == 'update failed') {
				alert(translator.translate('errorBookingUpdate'));
			}
			if(data.status == 'custom field validation failed') {
				alert('Custom field validation failed');
			}
			if(data.status === 'date not provided') {
				$('#edit-date-error').text(translator.translate('dateNotProvided'));
			}
			if(data.status === 'date not correct') {
				$('#edit-date-error').text(translator.translate('dateNotCorrect'));
			}	
		}
	});

	promise.fail = seatreg_admin_ajax_error;
});

$('#seatreg-booking-manager').on('click', '[data-action="save-approved-email-template-text"]', function(e) {
	e.preventDefault();
	var $this = $(this);
	var bookingId = $this.closest('.reg-seat-item').data('booking-id');
	var emailTemplateText = $this.siblings('[data-taget="custom-text-approved-email"]').val();

	$this.prop('disabled', true);

	var promise = seaterg_admin_ajax('seatreg_save_booking_approved_email_custom_text', bookingId, {
		emailTemplateText: emailTemplateText
	});

	promise.done(function(rep) {
		alertify.success(translator.translate('saved'));
	});

	promise.always(function() {
		$this.removeAttr("disabled");
	});

	promise.fail = seatreg_admin_ajax_error;
});


//text, xlsx and pdf 
$('.seatreg_page_seatreg-management').on('click', '.file-type-link', function(e) {
	e.preventDefault();
	$this = $(this);

	if( $this.attr('data-file-type') === 'xlsx' && $this.attr('data-zip-is-enabled') === 'false' ) {
		alertify.error(translator.translate('enableZipExtension'));

		return false;
	}

	$('#generate-bookings-file').attr('data-link', $this.attr('href'));
	$('#bookings-file-modal').modal('show');
});

/*Settings page custom fields functions*/
$('.seatreg_page_seatreg-options .apply-custom-field').on('click', function(e) {
		e.preventDefault();

		var labelElem = $(this).closest('.cust-field-create').find('.cust-input-label');
		var label = labelElem.val().trim();
		var selectedSelect = $(this).closest('.cust-field-create').find('.custom-field-select').find(':selected').attr('data-type');
		var existElems = $(this).closest('.user-custom-field-options').find('.existing-custom-fields');
		var labelRegExp = new RegExp("^[\\p{L}1234567890\+\\s]{1,100}$", "u");

		if(label === '') {
			alertify.error(translator.translate('pleaseEnterName'));
			labelElem.focus();

			return;
		}

		if(!labelRegExp.test(label)) {
			alertify.error(translator.translate('illegalCharactersDetec'));
			labelElem.focus();

			return;
		}

		if( existElems.find('[data-label="' + label + '"]').length ) {
			alertify.error(translator.translate('nameAlreadyUsed'));

			return;
		}

		if(selectedSelect != 'select') {
			seatreg_insert_custom_field(label, selectedSelect, [], existElems);
			$(this).parent().find('.cust-input-label').val('');			
		}else {
			var cusOptions = $(this).closest('.user-custom-field-options').find('.existing-options').find('.option-value');

			if(cusOptions.length == 0) {
				alertify.error(translator.translate('pleaseAddAtLeastOneOption'));
				$(this).prev().find('.option-name').focus();
				
				return;
			}

			var options = [];
			cusOptions.each(function() {
				options.push($(this).text());
			});

			seatreg_insert_custom_field(label, selectedSelect, options, existElems);
			$(this).parent().find('.cust-input-label, .option-name').val('');
			$(this).parent().find('.existing-options').empty();
		}
});

function seatreg_insert_custom_field(label,type,options, placeToPut) {
		var containerDiv = $('<div class="custom-container" data-label="'+ label +'"></div>');
		var move_up = $('<i class="custom-container-move custom-container-move-up">▲</i>');
		var move_down = $('<i class="custom-container-move custom-container-move-down">▼</i>');
		containerDiv.append(move_up);
		containerDiv.append(move_down);

		if(type == 'field') {
			var cusLabel = $('<label><span class="l-text">'+ label +'</span><input type="text"/></label> <div class="custom-container-controls"><span class="seatreg-ui-tooltip" title="Prevents booking when same input value provided">Unique</span> <input type="checkbox" class="unique-input" /> <i class="fa fa-times-circle remove-cust-item"></i></div>'); 
			containerDiv.attr('data-type','text').append(cusLabel);
		}else if(type == 'checkbox') {
			var cusLabel = $('<label><span class="l-text">'+ label +'</span><input type="checkbox"/></label><div class="custom-container-controls"><i class="fa fa-times-circle remove-cust-item"></i></div>'); 
			containerDiv.attr('data-type','check').append(cusLabel);

		}else if(type == 'select') {
			var lab = $('<label><span class="l-text">'+ label + '</span></label>'); 
			var sel = $('<select></select>');
			var arrlen = options.length;

			for(var i = 0; i < arrlen;i++) {
				sel.append('<option>' + options[i] + '</option>');
			}
			var remBtn = '<div class="custom-container-controls"><i class="fa fa-times-circle remove-cust-item"></i></div>';
			lab.append(sel,remBtn);
			containerDiv.attr('data-type','sel').append(lab);
		}	
		placeToPut.append(containerDiv);
		initTooltips();
}

$('.seatreg_page_seatreg-options .custom-field-select').on('change', function() {
	var createBox = $('.seatreg_page_seatreg-options .cust-field-create');

	if($(this).find(":selected").attr('data-type') == 'field') {
		createBox.find('.select-radio-create').css('display','none');
	}else if($(this).find(":selected").attr('data-type') == 'checkbox') {
		createBox.find('.select-radio-create').css('display','none');
	}else if($(this).find(":selected").attr('data-type') == 'select') {
		createBox.find('.select-radio-create').css('display','block');
	}
});

$('.seatreg_page_seatreg-options .add-select-option').on('click', function(e) {
		e.preventDefault();
        var selectOptionValue = $(this).prev().find('.option-name').val();

		if( selectOptionValue === '' ) {
			alertify.error(translator.translate('pleaseEnterOptionValue'));
			$(this).prev().focus();

			return;
		}

		$(this).prev().prev().append('<li class="select-option"><span class="option-value">'+ $(this).prev().find('.option-name').val() +'</span><i class="fa fa-times-circle remove-cust-item"></i></li>');
});

$('.seatreg_page_seatreg-options .existing-custom-fields').on('click','.remove-cust-item', function() {
	if(window.confirm(translator.translate('areYouSure'))) {
		$(this).closest('.custom-container').remove();
	}		
});

$('.seatreg_page_seatreg-options .cust-field-create').on('click','.remove-cust-item', function() {	
	if(window.confirm(translator.translate('areYouSure'))) {
		$(this).parent().remove();
	}
});

$('.seatreg_page_seatreg-options .existing-custom-fields').on('click','.custom-container-move-up', function() {
	var $item = $( this ).parent();
	var $prevItem = $item.prev();

	if ($prevItem.hasClass('custom-container')){
		$item.insertBefore($item.prev());
		highlight_moved_item($item);
	}
});

$('.seatreg_page_seatreg-options .existing-custom-fields').on('click','.custom-container-move-down', function() {
    var $item = $( this ).parent();

    if ( !$item.is(':last-child') )
        $item.insertAfter($item.next());
		highlight_moved_item($item);
});

$('.seatreg_page_seatreg-options .existing-custom-fields').on('click', '.edit-options', function() {
    var selectId = $(this).data('select-id');
    var selectElement = $('#' + selectId);
    var options = selectElement.find('option');

    // Create a dialog element
    var dialog = document.createElement('dialog');
    dialog.innerHTML = `
        <h2>Edit Options</h2>
        <ul id="options-list" class="mb-0"></ul>
		<p id="error-message" class="text-danger d-none mb-2">You must have at least one option.</p>
		<div class="d-flex">
			<input type="text" id="new-option" placeholder="New option">
			<button id="add-option" class="btn btn-primary ml-2 w-100">Add</button>
		</div>
        <button id="save-options" class="btn btn-success mt-2 w-100">Save Changes and Close</button>
    `;

    // Populate the dialog with existing options
    options.each(function() {
        var optionText = $(this).text();
        $('#options-list', dialog).append('<li class="d-flex"><input type="text" value="' + optionText + '"><button class="remove-option btn btn-danger ml-2 w-100">Remove</button></li>');
    });

    // Add event listeners for adding, removing, and saving options
    dialog.querySelector('#add-option').addEventListener('click', function() {
        var newOptionText = dialog.querySelector('#new-option').value;
        if (newOptionText !== '') {
            $('#options-list', dialog).append('<li class="d-flex"><input type="text" value="' + newOptionText + '"><button class="remove-option btn btn-danger ml-2 w-100">Remove</button></li>');
            dialog.querySelector('#new-option').value = ''; // Clear the input field
        }
    });

	dialog.addEventListener('click', function(e) {
		if (e.target.classList.contains('remove-option')) {
			var listItems = $('#options-list li', dialog);
			if (listItems.length > 1) {
				$(e.target).closest('li').remove();
			} else {
                $('#error-message', dialog).removeClass('d-none');
                setTimeout(function() {
                    $('#error-message', dialog).addClass('d-none');
                }, 5000);
			}
		}
	});

    dialog.querySelector('#save-options').addEventListener('click', function() {
        var newOptions = [];
        $('#options-list li', dialog).each(function() {
            newOptions.push($(this).find('input').val());
        });

        // Update the select element with new options
        selectElement.empty();
        $.each(newOptions, function(index, option) {
            selectElement.append('<option><span class="option-value">' + option + '</span></option>');
        });

        dialog.close();
		dialog.remove();
    });

    // Show the dialog
    document.body.appendChild(dialog);
    dialog.showModal();

    // Handle closing the dialog
    $(document).on('click', function(e) {
        if (e.target === dialog) {
            dialog.close();
			dialog.remove();
        }
    });
});

$('.seatreg_page_seatreg-options .existing-coupons').on('click', '[data-action="delete-coupon"]', function() {
    $(this).closest('.coupon-box').remove();
});

$('.seatreg_page_seatreg-options .coupon-create [data-action="add-coupon"]').on('click', function() {
    var newCouponCode = $('#new-coupon-code').val().trim();
	var newDiscountAmount = $('#new-coupon-discount').val().trim();

	if (newCouponCode === '') {
		alertify.error(translator.translate('enterCouponCode'));
		$('#new-coupon-code').focus();

		return;
	}
	if (newDiscountAmount === '') {
		alertify.error(translator.translate('enterCouponDiscount'));
		$('#new-coupon-discount').focus();

		return;
	}

	$('.seatreg_page_seatreg-options .existing-coupons').append(
		'<div class="coupon-box">' +
			'<div class="coupon-box__label">' + translator.translate('couponcode') + ':</div>' +
			'<div class="coupon-box__value" data-target="coupon-code">' + newCouponCode + '</div>' +
			'<div class="coupon-box__label">' + translator.translate('discount') + ':</div>' +
			'<div class="coupon-box__value" data-target="discount-value">' + newDiscountAmount + '</div>' +
			'<div class="coupon-box__actions">' +
				'<button class="btn btn-danger btn-sm" type="button" data-action="delete-coupon"> ' + translator.translate('delete') + ' </button>' +
			'</div>' +
		'</div>'
	);

	$('#new-coupon-code, #new-coupon-discount').val('');
});

function highlight_moved_item(moved_item){
	let css_class = 'custom-container-move-highlight';
	moved_item.addClass(css_class);
	setTimeout(function() {
		moved_item.removeClass(css_class)
	  }, 1500); // 1500ms = 1,5 seconds
	moved_item.focus();
}

function SeatregCustomField(label, type, options, unique = false, optional = false) {
		this.label = label.trim();
		this.type = type;
		this.options = options;
		this.unique = unique;
		this.optional = optional;
}

function SeatregCustomPayment(title, description, paymentId, paymentIcon) {
	this.title = title;
	this.description = description;
	this.paymentId = paymentId;
	this.paymentIcon = paymentIcon;
}

$('#seatreg-settings-form #create-custom-payment').on('click', function() {
	var customFieldTitle = $('#new-custom-payment [data-id="new-custom-payment-title"]').val();
	var customFieldDescription = $('#new-custom-payment [data-id="new-custom-payment-description"]').val();
	var registrationCode = $('#seatreg-settings-form input[name="registration_code"]').val();

	if( customFieldTitle === '' ) {
		alertify.error(translator.translate('enterCustomPaymentTitle'));
		return;
	}
	if( customFieldDescription === '' ) {
		alertify.error(translator.translate('enterCustomPaymentdescription'));
		return;
	}

	$('#custom-payments .existing-custom-payments').append(
		'<div class="custom-payment" data-payment-id="' + generateUniqueString() + '">' +
			'<p>' + translator.translate('title') + '</p>' +
			'<input value="'+ customFieldTitle +'" data-id="custom-payment-title" />' +
			'<p>' + translator.translate('description') + '</p>' +
			'<textarea data-id="custom-payment-description">'+ customFieldDescription +'</textarea>' +
			'<p>' + translator.translate('paymentIcon') + '</p>' +
			'<div>' +
				'<div class="current-custom-payment-icon">' +
				'</div>' +
				'<div class="custom-payment-icon-upload">' +
					'<div class="custom-payment-icon-upload__loading">' +
						'<img src="'+ WP_Seatreg.plugin_dir_url + 'img/ajax_loader_small.gif" alt="Loading...">' +
					'</div>' +
					'<input type="file" name="custom-payment-icon" data-action="custom-payment-icon-upload" data-code="'+ registrationCode +'" />' +
					'<p class="custom-payment-icon-upload__error"></p>' +
				'</div>' +
			'</div>' +
			'<div class="custom-payment__controls">' +
				'<button class="btn btn-danger btn-sm">'+ translator.translate('remove') +'</button>' + 
			'</div>' + 
		'</div>'
	);
});

$('#seatreg-settings-form #custom-payments').on('click', '[data-action="remove-custom-payment"]', function() {
	$(this).closest('.custom-payment').remove();
});

$('#seatreg-settings-form #custom-payments').on('click', '.current-custom-payment-icon__delete', function() {
	var $customPayment = $(this).closest('.custom-payment');
	var registrationCode = $('#seatreg-settings-form input[name="registration_code"]').val();
	var imageName = $(this).siblings('img[data-name]').data('name');

	$customPayment.find('.current-custom-payment-icon__delete').css('display', 'none');
	$customPayment.find('.current-custom-payment-icon__loading').css('display', 'block');
	var promise = seaterg_admin_ajax('seatreg_remove_custom_payment_img', registrationCode, imageName);

	promise.done(function() {
		$customPayment.find('.current-custom-payment-icon__loading').css('display', '');
		$customPayment.find('.current-custom-payment-icon__delete').css('display', 'block');
		$customPayment.find('.current-custom-payment-icon').empty();
		$customPayment.find('.custom-payment-icon-upload').css('display', 'flex');
	});
	
	promise.fail = seatreg_admin_ajax_error;
});

$('#seatreg-settings-form #custom-payments').on('change', '[data-action="custom-payment-icon-upload"]', function() {
	var $this = $(this);
	var $customPayment = $this.closest('.custom-payment');
	var regCode = $this.data('code');
	var file = $this[0].files[0];
	var $loading = $this.siblings('.custom-payment-icon-upload__loading');
	$loading.css('display', 'block');
	var promise = seatreg_upload_custom_payment_icon(regCode, file);
	
	promise.always(function() {
		$loading.css('display', '');
	});
	promise.done(function(data) {
		var resp = JSON.parse(data);
		$this.val(null);

		if(resp.type === 'ok') {
			var paymentLogoUrl = WP_Seatreg.uploads_url + '/custom_payment_icons/' + regCode + '/' + resp.data;
			$customPayment.find('.custom-payment-icon-upload').css('display', 'none');

			$customPayment.find('.current-custom-payment-icon').append(
				'<image class="current-custom-payment-icon__img" src="'+ paymentLogoUrl +'" data-name="'+ resp.data +'"/>' +
				'<i class="fa fa-times-circle current-custom-payment-icon__delete"></i>' +
				'<img class="current-custom-payment-icon__loading" src="'+ WP_Seatreg.plugin_dir_url + 'img/ajax_loader_small.gif" alt="Loading..." />'
				);
			alertify.success(translator.translate('paymentIconUploaded'));
		}else {
			alertify.error(resp.text);
		}
	});
	promise.fail = seatreg_admin_ajax_error;
});


$('#seatreg-settings-form #public-api-tokens').on('click', '.remove-token', function() {
	var tokenBox = $(this).closest('.token-box');
	var code = $('input[name="registration_code"]').val();
	var token = $(this).closest('.token-box').data('token');

	if( window.confirm(translator.translate('areYouSure')) ) {
		var promise = seaterg_admin_ajax2('seatreg_delete_api_token', code, {
			'api-token': token,
		});

		promise.done(function(data) {
			if(data.success === true) {
				tokenBox.remove();
				alertify.success(translator.translate('tokenRemoved'));
			}else {
				alertify.error(translator.translate('somethingWentWrong'));
			}
		});
		promise.fail = seatreg_admin_ajax_error;
	}
});

$('#seatreg-settings-form #public-api-tokens').on('click', '.toggle-token', function(e) {
	e.preventDefault();
	$tokenBox = $(this).closest('.token-box');
	$token = $tokenBox.find('.token');

	if( $token.text().includes('●') ) {
		$token.text($tokenBox.data('token'));
		$(this).text('Hide token');
	}else {
		$token.text($tokenBox.data('token-hidden'));
		$(this).text('Show token');
	}
});

$('#seatreg-settings-form #create-api-token').on('click', function(e) {
	e.preventDefault();
	var code = $('input[name="registration_code"]').val();
	var $this = $(this);
	$this.text(translator.translate('loading'));

	var promise = seaterg_admin_ajax2('seatreg_create_api_token', code);
	promise.done(function(data) {
		$this.text(translator.translate('createApiToken'));
		if(data.success === true) {
			var token = data.data.token;
			var hiddenToken = data.data.hiddenToken;

			$('#public-api-tokens').append(
				'<div class="token-box" data-token="'+ token +'" data-token-hidden="'+ hiddenToken +'">' +
					'<div class="token">'+ hiddenToken +'</div>' +
					'<button class="btn btn-default btn-sm toggle-token" type="button">Show token</button>' +
					'<div class="token-actions"><i class="fa fa-times-circle remove-token"></i></div>' +
				'</div>'
			);
			alertify.success(translator.translate('tokenCreated'));
		}else {
			alertify.error(translator.translate('somethingWentWrong'));
		}
	});
	promise.fail = seatreg_admin_ajax_error;
});

$('#seatreg-settings-submit').on('click', function(e) {
	var customFieldArray = [];
	var customPayments = [];
	var coupons = [];
	var currencyCode = $('#paypal-currency-code').val();

	if($('#stripe').is(":checked")) {
		if($('#stripe-api-key').val() === "") {
			e.preventDefault();
			alertify.error(translator.translate('pleaseEnterStripeApiKey'));

			return true;
		}
		if(currencyCode === "") {
			e.preventDefault();
			alertify.error(translator.translate('pleaseEnterPayPalCurrencyCode'));

			return true;
		}

		if(!validateCurrencyCode(currencyCode)) {
			e.preventDefault();
			alertify.error(translator.translate('currencyCodeNotCorrect'));

			return true;
		}
	}

	if( $('#stripe-api-key').val() !== "" && !$('#stripe-api-key').val().startsWith('sk') ) {
		e.preventDefault();
		alertify.error(translator.translate('pleaseProvideStripeApiSecretKey'));

		return true;
	}

	if( $('#email-from').val() !== '' && !/^\S+@\S+$/.test( $('#email-from').val() ) ) {
		e.preventDefault();
		alertify.error(translator.translate('emailFromNotCorrect'));

		return true;
	}

	if($('#paypal').is(":checked")) {
		if($('#paypal-business-email').val() === "") {
			e.preventDefault();
			alertify.error(translator.translate('pleaseEnterPayPalBusinessEmail'));

			return true;
		}
		if($('#paypal-button-id').val() === "") {
			e.preventDefault();
			alertify.error(translator.translate('pleaseEnterPayPalButtonId'));

			return true;
		}
		if(currencyCode === "") {
			e.preventDefault();
			alertify.error(translator.translate('pleaseEnterPayPalCurrencyCode'));

			return true;
		}
		if(!validateCurrencyCode(currencyCode)) {
			e.preventDefault();
			alertify.error(translator.translate('currencyCodeNotCorrect'));

			return true;
		}
	}

	if($('#approved-booking-email-template').val()) {
		if($('#approved-booking-email-template').val().indexOf('[status-link]') === -1) {
			e.preventDefault();
			alertify.error(translator.translate('emailTemplateNotCorrect'));

			return true;
		}
	}

	if($('#pendin-booking-email-template').val()) {
		if($('#pendin-booking-email-template').val().indexOf('[status-link]') === -1) {
			e.preventDefault();
			alertify.error(translator.translate('emailTemplateNotCorrect'));

			return true;
		}
	}

	if($('#email-verification-template').val()) {
		if($('#email-verification-template').val().indexOf('[verification-link]') === -1) {
			e.preventDefault();
			alertify.error(translator.translate('emailTemplateNotCorrect'));

			return true;
		}
	}

	$('#seatreg-settings-form .custom-container').each(function() {
 			if($(this).attr('data-type') != 'sel') {
				var isUnique = $(this).find('.unique-input').is(':checked');
				var isOptional = $(this).find('.optional-input').is(':checked');

 				customFieldArray.push(new SeatregCustomField($(this).find('.l-text').text(), $(this).attr('data-type'), [], isUnique, isOptional));
 			}else {
 				var optArr = [];

 				$(this).find('option').each(function() {
 					optArr.push($(this).text());
				 });
				 
 				customFieldArray.push(new SeatregCustomField($(this).find('.l-text').text(), $(this).attr('data-type'), optArr));
 			}	
 	}); 
 	$('#custom-fields').val(JSON.stringify( customFieldArray) );  //set #custom-fields hidden input value

	$('#seatreg-settings-form .existing-coupons .coupon-box').each(function() {
		let couponCode = $(this).find('[data-target="coupon-code"]').text().trim();
		let discountValue = $(this).find('[data-target="discount-value"]').text().trim();

 		coupons.push({
			couponCode: couponCode,
			discountValue: discountValue
		});
 	});

	$('#coupon-management input[name="coupons"]').val(JSON.stringify( coupons ));

	$('#seatreg-settings-form .existing-custom-payments .custom-payment').each(function() {
		var paymentIcon = $(this).find('.current-custom-payment-icon img').length ? $(this).find('.current-custom-payment-icon img').data('name') : null;

		customPayments.push(new SeatregCustomPayment( 
			$(this).find('[data-id="custom-payment-title"]').val(),
			$(this).find('[data-id="custom-payment-description"]').val(),
			$(this).data('payment-id'),
			paymentIcon
		));
	});
	$('#custom-payments input[name="custom-payments"]').val(JSON.stringify( customPayments ));
});

$('#seatreg-send-test-email').on('click', function(e) {
	e.preventDefault();
	var enteredEmail = $('#test-email-address').val();
	var emailReg = /^\S+@\S+$/;

	if(!emailReg.test(enteredEmail)) {
		alertify.error(translator.translate('emailNotCorrect'));
		$('#test-email-address').focus();

		return false;
	}else {
		var $sendTestEmailBtn = $('#seatreg-send-test-email');
		var btnText = $sendTestEmailBtn.val();	
		var enteredEmail = $('#test-email-address').val();
		var promise = seatreg_send_test_email(enteredEmail);
		$sendTestEmailBtn.val(translator.translate('pealseWait'));

		promise.done(function(data) {
			$sendTestEmailBtn.val(btnText);

			if(data._response.type === 'error') {
				alertify.error(translator.translate('emailSendingFailed'));
			}else {
				alertify.success(translator.translate('checkEmailAddress'));
			}
		});
	}
});

function initTooltips() {
	$('.seatreg-ui-tooltip').tooltip();
}
initTooltips();

})(jQuery);
