<?php if(!defined('ABSPATH')) exit; ?>

<?php $seatreg_new_version = seatreg_get_available_plugin_update(); ?>

<?php if( $seatreg_new_version ) : ?>
	<a class="seatreg-update-notice" href="<?php echo esc_url(self_admin_url('plugins.php?plugin_status=upgrade')); ?>">
		<?php
			printf(
				/* translators: %s: new plugin version number */
				esc_html__('Version %s is available', 'seatreg'),
				esc_html($seatreg_new_version)
			);
		?>
	</a>
<?php endif; ?>
