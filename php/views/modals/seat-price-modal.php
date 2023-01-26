<div class="modal vert-modal fade" id="price-dialog" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog vert-modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h4 class="modal-title" id="myModalLabel"><?php esc_html_e('Pricing', 'seatreg');?></h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
            <div class="alert alert-primary" role="alert" id="enable-paypal-alert">
                <?php esc_html_e('You need to enable PayPal or Stripe payments in settings to turn on pricing functionality.', 'seatreg'); ?>
            </div>
            <div class="set-price-wrap">
                <div><label for="price-for-all-selected"><?php esc_html_e('Fill price to all selected seats', 'seatreg'); ?></label></div>
                <input type="number" min="0" oninput="this.value = Math.abs(this.value)" id="price-for-all-selected" value="0" />
                <button type="button" class="btn btn-success btn-sm" id="fill-price-for-all-selected"><?php esc_html_e('Fill prices', 'seatreg'); ?></button>
            </div>
            <div id="selected-seats-for-pricing"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php esc_html_e('Close', 'seatreg');?></button>
            <button type="button" class="btn btn-primary" id="set-prices"><?php esc_html_e('Set price', 'seatreg');?></button>
        </div>
    </div>
    </div>
</div>