(function($) {
    $('#send-receipt').on('click', function() {
        $this = $(this);
        $this.attr("disabled", true);

        $.ajax({
            type: 'POST',
		    url: WP_Seatreg.ajaxUrl,
            data: {
                bookingId: $this.data('booking-id'),
                registrationCode: $this.data('registration-id'),
                action: 'seatreg_resend_receipt',
            },
            success: function(data) {
                $this.removeAttr("disabled");
                var resp = $.parseJSON(data);

                if(resp.type === 'ok') {
                    alertify.success(WP_Seatreg.successMessage);	
                }else {
                    alertify.error(WP_Seatreg.errorMessage);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                $this.removeAttr("disabled");
                alertify.error(WP_Seatreg.errorMessage);
            }
        })
    });

})(jQuery);