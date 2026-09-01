<?php if(!defined('ABSPATH')) exit; ?>

<div class="donate-wrap">
	<img src="<?php echo esc_url(SEATREG_PLUGIN_FOLDER_URL . 'img/donate.svg'); ?>" alt="Donate a little" width="160" />
	<form action="https://www.paypal.com/donate" method="post" target="_blank">
		<input type="hidden" name="hosted_button_id" value="9QSGHYKHL6NMU" />
		<input type="image" class="donate-img" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" title="PayPal - The safer, easier way to pay online!" alt="Donate with PayPal button" />
	</form>
	<p>
		Bitcoin address: <code>bc1q2vam0ree3zw4j3f92vkfjd9vvxlwpxxsrpmkhu</code>
	</p>
	<p style="text-align:center">
		Don't forget to leave a
		<a href="https://wordpress.org/support/plugin/seatreg/reviews/" target="_blank">
			review
		</a>
	</p>
	<p>
		Thank you if you have already donated and left a review. It means a lot, as it confirms that what I do makes a difference and motivates me to maintain and add new features.
	</p>
	<p style="text-align:center">
		Found a problem or have some ideas how to improve? Don't hesitate to write to
		<a href="https://wordpress.org/support/plugin/seatreg/" target="_blank">
			support forum
		</a>
	</p>
	<p>Source code is located at <a href="https://github.com/SiimKirjanen/seatreg" target="_blank">GitHub</a></p>
	<p style="text-align:center">
		I also created an Android companion application for this plugin.<br>
		<a href="https://play.google.com/store/apps/details?id=com.seatreg" target="_blank">
			<i class="fa fa-android" aria-hidden="true" style="color: #A4C639"></i>
			SeatReg Android application
		</a>
	</p>
	<p>
		Also take a look at my other plugin QuickTasker. It is a task management plugin. <br />
		<img src="<?php echo esc_url(SEATREG_PLUGIN_FOLDER_URL . "img/quicktasker-icon.png"); ?>" />
		<a href="https://wordpress.org/plugins/quicktasker/" target="_blank">QuickTasker</a>
	</p>
	<?php include(SEATREG_PLUGIN_FOLDER_DIR . 'php/views/parts/donation-flowers.php'); ?>
</div>
