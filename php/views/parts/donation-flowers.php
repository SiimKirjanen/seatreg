<?php
if(!defined('ABSPATH')) exit;

$seatreg_flowers = array(
	array( 'size' => '12px', 'delay' => '0s', 'position' => 'top: 24%; left: 24px;' ),
	array( 'size' => '18px', 'delay' => '1s', 'position' => 'top: 12px; right: 64px;' ),
	array( 'size' => '14px', 'delay' => '2s', 'position' => 'bottom: 26%; right: 48px;' ),
	array( 'size' => '10px', 'delay' => '4s', 'position' => 'bottom: 0; left: 16px;' ),
);
?>

<div class="seatreg-flower-layer" aria-hidden="true">
	<?php foreach($seatreg_flowers as $seatreg_flower) : ?>
		<div class="seatreg-flower-container" style="--flower-size: <?php echo esc_attr($seatreg_flower['size']); ?>; --flower-delay: <?php echo esc_attr($seatreg_flower['delay']); ?>; --flower-opacity: 0.1; <?php echo esc_attr($seatreg_flower['position']); ?>">
			<?php for($seatreg_droplet = 1; $seatreg_droplet <= 10; $seatreg_droplet++) : ?>
				<div class="seatreg-flower-droplet seatreg-flower-droplet-<?php echo esc_attr($seatreg_droplet); ?>"></div>
			<?php endfor; ?>
		</div>
	<?php endforeach; ?>
</div>
