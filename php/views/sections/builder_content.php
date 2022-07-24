<?php
	if ( ! defined( 'ABSPATH' ) ) {
		exit(); 
	}	
?>

<div id="construction-wrapper">
	<div class="build-head">
		<h1 class="reg-title"><?php esc_html_e('Registration name', 'seatreg'); ?>: <span class="reg-title-name"></span></h1>
		<h2 class="room-title"><?php esc_html_e('Room', 'seatreg');?>: <span class="room-title-name"></span><span class="change-room-name"><?php esc_html_e('Change name', 'seatreg');?></span></h2>
		<div id="room-selection-wrapper"></div>
		<div class="cre-del-room-wrapper">
			<span id="new-room-create" class="room-action"><i class="fa fa-plus" aria-hidden="true"></i> <?php esc_html_e('Add room', 'seatreg');?> </span>
			<span id="current-room-delete" class="room-action"><i class="fa fa-minus" aria-hidden="true"></i> <?php esc_html_e('Delete room', 'seatreg');?></span>
		</div>
	</div> <!-- end of build head-->
	
	<div class="build-section" id="build-section">
		<div class="mouse-actions-and-help">
			<div class="help-icon" data-toggle="modal" data-target="#help-dialog"></div>

			<div class="mouse-action-boxes">
				<div data-action="1" class="mouse-option action1" id="mouse-option-active" title="Select, move and resize tool"></div>
				<div data-action="4" class="mouse-option action4" title="Lasso select tool"></div>
				<div data-action="2" class="mouse-option action2" title="Seat creation tool"></div>
				<div data-action="5" class="mouse-option action5" title="Custom box tool"></div>
				<div data-action="9" class="mouse-option action9" title="Text tool"></div>
				<div data-action="3" class="mouse-option action3" title="Eraser tool"></div>
			</div>

			<div id="build-section-click-controls">
				<div class="click-control-left">
					<label for="click-control-move-nr"><?php esc_html_e('Move selected boxes', 'seatreg');?></label> <input type="number" id="click-control-move-nr" value="15" style="width: 50px"> <label for="click-control-move-nr"><?php esc_html_e('pixels to', 'seatreg');?></label> 
				</div>

				<div class="click-control-right">
					<div>
						<i class="fa fa-arrow-circle-up" aria-hidden="true" data-destination="up"></i>
						<i class="fa fa-arrow-circle-down" aria-hidden="true" data-destination="down"></i>
						<i class="fa fa-arrow-circle-left" aria-hidden="true" data-destination="left"></i>
						<i class="fa fa-arrow-circle-right" aria-hidden="true" data-destination="right"></i>
					</div>
				</div>
			</div>
		</div>

		<div class="build-area-side">
			<div class="side-option delete-box" data-action="1" title="Delete selected"></div>
			<div class="side-option legend-option" data-action="2" title="Legends" data-toggle="modal" data-target="#legends-dialog"></div>
			<div class="side-option bubble-text" data-action="4" title="Hover text"></div>
			<div class="side-option palette-call" data-action="5" title="Color"></div>
			<div class="side-option background-image" data-action="7" title="Background image" data-toggle="modal" data-target="#background-image-modal"></div>
			<div class="side-option grid-stats" data-action="6" title="Grid settings" data-toggle="modal" data-target="#skeleton-dialog"></div>
			<div class="side-option price-option" data-action="8" title="Seat price" data-toggle="modal" data-target="#price-dialog"></div>
			<div class="side-option lock-option" data-action="10" title="Lock seats" data-toggle="modal" data-target="#lock-seat-dialog"></div>
			<div class="side-option numbering-option" data-action="11" title="Change numbering" data-toggle="modal" data-target="#seat-numbering-dialog"></div>
		</div>
	
		<div class="build-area-wrapper" data-cursor="1">
			<div id="build-area-loading-wrap">
				<img src="<?php echo SEATREG_PLUGIN_FOLDER_URL . 'img/loading.png'; ?>" id="loading-img" alt="Loading...">
				<span class='loading-text'><?php esc_html_e('Loading...', 'seatreg');?></span>
			</div>
			<div class="build-area dragger" draggable="false">
			</div><!-- end of build-area -->
		</div><!-- end of build-area-wrapper -->
	</div>

	<div class="build-controls">
		<div class="legends"></div>
		<div class="update-wrapper">
			<div id="build-section-message-wrap" data-toggle="modal" data-target="#limit-dialog">
				<span class="message-text"><span style="vertical-align:middle"><?php esc_html_e('Pending and booked seats can\'t be deleted', 'seatreg');?></span><span class="more-message-btn"></span></span>
			</div>

			<div id="build-head-stats-2">
				<div class="build-head-stats-2-icon"></div>
				<div class="build-head-stats-2-text"></div>
			</div>

			<button id="update-data">
				<i class="fa fa-floppy-o"></i>
				<span style="margin-left:4px" class="save-text"><?php esc_html_e('Save', 'seatreg');?></span>
			</button>
			<a id="registration-link" class="save-check view-btn" target="_blank" href="<?php echo get_site_url(); ?>">
				<span style="margin-left:4px" class="link-text"><?php esc_html_e('View registration', 'seatreg');?></span>
			</a>
			<div id="server-response"></div>
		</div>
		
	</div><!-- end of build-controls -->

	<div class="build-footer"></div>

	<div class="modal fade" id="color-dialog" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="myModalLabel"><?php esc_html_e('Color', 'seatreg');?></h4>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body">
				<div class="color-dialog-info"></div>
				<div id="picker" class="color-picker-wrap"></div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"><?php esc_html_e('Close', 'seatreg');?></button>			
			</div>
		</div>
		</div>
	</div>

	<div class="modal fade" id="hover-dialog" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="myModalLabel"><?php esc_html_e('Adding hover text', 'seatreg');?></h4>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body">
				<div class="hover-dialog-info"></div>
				<label for="box-hover-text"><?php esc_html_e('Enter text', 'seatreg');?></label><br>
				<textarea id="box-hover-text" rows="4" cols="26"></textarea>
				<div><?php esc_html_e('Leave field empty to remove existing hover text', 'seatreg');?></div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"><?php esc_html_e('Close', 'seatreg');?></button>
				<button type="button" class="btn btn-primary" id="box-hover-submit"><?php esc_html_e('OK', 'seatreg');?></button>
			</div>
		</div>
		</div>
	</div>

	<div class="modal vert-modal fade skeleton-dialog" id="skeleton-dialog" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
		<div class="modal-dialog vert-modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="myModalLabel"><?php esc_html_e('Building grid helps you to create boxes', 'seatreg');?></h4>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body">
				<div>
					<h5><?php esc_html_e('Box size', 'seatreg');?></h5>
					<label>x<input type="number" class="skeleton-input" id="size-x" value="36"/></label> <br>
					<label>y<input type="number" class="skeleton-input" id="size-y" value="36"/></label>
					<br><br>
				</div>

				<div>
					<h5><?php esc_html_e('Box count', 'seatreg');?></h5>
					<label><?php esc_html_e('x-axis', 'seatreg');?><input type="number" class="skeleton-input" id="count-x" value="22"/></label> <br>
					<label><?php esc_html_e('y-axis', 'seatreg');?><input type="number" class="skeleton-input" id="count-y" value="20"/></label> 
					<br><br>
				</div>

				<div>
					<h5><?php esc_html_e('Distance between boxes', 'seatreg');?></h5>
					<label>x<input type="number" class="skeleton-input" id="margin-x" value="10"/></label> <br>
					<label>y<input type="number" class="skeleton-input" id="margin-y" value="10"/></label> 
					<br><br>					
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"><?php esc_html_e('Close', 'seatreg');?></button>
				<button type="button" class="btn btn-primary build-skeleton"><?php esc_html_e('Update grid', 'seatreg');?></button>
			</div>
		</div>
		</div>
	</div>

	<div class="modal vert-modal fade" id="price-dialog" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
		<div class="modal-dialog vert-modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="myModalLabel"><?php esc_html_e('Seat pricing', 'seatreg');?></h4>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body">
				<div class="alert alert-primary" role="alert" id="enable-paypal-alert">
					<?php esc_html_e('You need to enable PayPal in settings to turn on pricing functionality.', 'seatreg'); ?>
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

	<div class="modal vert-modal fade" id="lock-seat-dialog" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
		<div class="modal-dialog vert-modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="myModalLabel"><?php esc_html_e('Lock seats', 'seatreg');?></h4>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body">
				<div class="set-password-wrap">
					<div><label for="password-for-all-selected"><?php esc_html_e('Fill password to all selected seats', 'seatreg'); ?></label></div>
					<input type="text" id="password-for-all-selected" />
					<button type="button" class="btn btn-success btn-sm" id="fill-password-for-all-selected"><?php esc_html_e('Fill password', 'seatreg'); ?></button>
				</div>
				<div id="selected-seats-for-locking"></div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"><?php esc_html_e('Close', 'seatreg');?></button>
				<button type="button" class="btn btn-primary" id="set-seat-locks"><?php esc_html_e('Apply', 'seatreg');?></button>
			</div>
		</div>
		</div>
	</div>

	<div class="modal vert-modal fade" id="seat-numbering-dialog" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
		<div class="modal-dialog vert-modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="myModalLabel"><?php esc_html_e('Seat numbering', 'seatreg');?></h4>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body">
				<div class="alert alert-primary d-none" id="seat-nr-change-no-selection"><?php esc_html_e('No seats selected', 'seatreg'); ?></div>
				<div class="alert alert-primary d-none" id="seat-nr-change-warning"><?php esc_html_e('Pending or booked seat numbers can\'t be changed', 'seatreg'); ?></div>
				<div id="seat-numbering-wrap">
					<div>
						<div><label for="seat-prefix"><?php esc_html_e('Seat prefix for selected seats', 'seatreg'); ?></label></div>
						<input type="text" id="seat-prefix" style="width:60px" />
						<button type="button" class="btn btn-success btn-sm" id="set-seat-prefix"><?php esc_html_e('Set prefix', 'seatreg'); ?></button>
					</div><br>
					<div>
						<div><label for="seat-reorder"><?php esc_html_e('Reorder selected seats starting from', 'seatreg'); ?></label></div>
						<input type="number" id="seat-reorder" size="3" style="width:60px" />
						<button type="button" class="btn btn-success btn-sm" id="reorder-seats"><?php esc_html_e('Reorder seats', 'seatreg'); ?></button>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"><?php esc_html_e('Close', 'seatreg');?></button>
			</div>
		</div>
		</div>
	</div>

	<!-- Legend dialog-->
	<div id="legend-dialog" title="Legends" class="display-none">
		<div class="legend-dialog-upper">
			<ul class="legend-dialog-info">
			</ul>

			<div class="legend-dialog-commands">
				<div class="legend-dialog-div">
					<label for="use-select" class="legend-dialog-label"><?php esc_html_e('Existing legends:', 'seatreg');?></label>
					<select class="legend-select" id="use-select"></select> <br>
					<button type="button" id="apply-legend" class="btn btn-secondary d-block btn-sm"><?php esc_html_e('Apply legend', 'seatreg');?></button> 
				</div>

				<div class="legend-dialog-div">
					<label for="delete-select" class="legend-dialog-label"><?php esc_html_e('Remove legend from registration:', 'seatreg');?></label>
					<select class="legend-select" id="delete-select"></select>
					<button type="button" id="delete-legend" class="btn btn-secondary d-block btn-sm"><?php esc_html_e('Delete', 'seatreg');?></button> 
				</div>

				<div class="legend-dialog-div">
					<label for="legend-delete-select-room" class="legend-dialog-label"><?php esc_html_e('Remove legend from this room:', 'seatreg');?></label>
					<select class="legend-select-room" id="legend-delete-select-room"></select> 
					<button type="button" id="delete-legend-from-room" class="btn btn-secondary d-block btn-sm"><?php esc_html_e('Remove', 'seatreg');?></button>
				</div>

				<div class="legend-dialog-div">
					<label for="legend-change-select" class="legend-dialog-label"><?php esc_html_e('Change legend', 'seatreg');?>:</label>
					<select id="legend-change-select" class="legend-select"></select> 
					<button type="button" id="change-legend" class="btn btn-secondary d-block btn-sm"><?php esc_html_e('Change', 'seatreg');?></button>
				</div>
			</div>
		</div>

		<div id="legend-creator">
			<div class="legend-dialog-slide">
				<div>
					<label for="new-legend-text" class="legend-dialog-label-step"> 
						<span class="legend-step"><?php esc_html_e('Step 1.', 'seatreg');?> </span> <span><?php _e('Enter legend name', 'seatreg');?>:</span>
					</label>
				</div>
				<input type="text" id="new-legend-text" class="form-control"><br>
				<span id="new-legend-text-rem"></span><br>
				<div class="next-step step-btn" data-slide="1" data-slide-open="2"><?php esc_html_e('Step 2.', 'seatreg');?>  <span class="glyphicon glyphicon-arrow-right"></span></div>
			</div>

			<div class="legend-dialog-slide">
				<div>
					<label class="legend-dialog-label-step">
						<span class="legend-step"><?php esc_html_e('Step 2.', 'seatreg');?> </span><span><?php esc_html_e('Choose legend color', 'seatreg');?>:</span>
					</label>
				</div>

				<div id="picker2" class="legends-color-picker"></div>
				<div class="prev-step step-btn" data-slide="2" data-slide-open="1"><?php esc_html_e('Step 1.', 'seatreg');?> <span class="glyphicon glyphicon-arrow-left"></span></div>
				<div class="next-step step-btn" data-slide="2" data-slide-open="3"><?php esc_html_e('Step 3.', 'seatreg');?> <span class="glyphicon glyphicon-arrow-right"></span></div>
			</div>
			<div class="legend-dialog-slide">
				<div>
					<label class="legend-dialog-label-step">
						<span class="legend-step"><?php esc_html_e('Step 3.', 'seatreg');?> </span><span><?php esc_html_e('Review new legend', 'seatreg');?></span>
					</label>
				</div>

				<div id="dummy-legend">
					<div class="legend-box" style="background-color: #61B329"></div>
					<span class="dialog-legend-text"></span>
					<input type="hidden" id="hiddenColor" value="#61B329">
				</div>

				<div id="create-new-legend" class="green-toggle"><?php esc_html_e('Create new legend', 'seatreg');?></div>

				<div class="prev-step step-btn" data-slide="3" data-slide-open="2"><?php esc_html_e('Step 2.', 'seatreg');?> <span class="glyphicon glyphicon-arrow-left"></span></div>
			</div>	
		</div>

		<div class="toggle-lcreator-wrap">
			<div id="toggle-lcreator" class="green-toggle"><?php esc_html_e('Create new legend', 'seatreg');?></div>
		</div>

		<div id="legend-change-wrap">
			<div id="legend-change-wrap-inner">
				<div class="legend-change-name change-wrap-section">
					<label for="old-legend-name"><?php esc_html_e('Enter new name', 'seatreg');?></label>
					<input type="text" id="new-legend-name" class="form-control" maxlength="20">
					<input type="hidden" id="old-legend-name">
					<div id="new-legend-name-info"></div>
					<div class="d-flex justify-content-between align-items-center pr-3">
						<div id="apply-new-legend-name" class="legend-dialog-btn green-btn"><?php esc_html_e('Change name', 'seatreg');?></div>
						<span class="change-btn" data-slide="1" data-slide-open="2"><?php esc_html_e('Back', 'seatreg');?><span class="glyphicon glyphicon-arrow-right"></span></span>
					</div>
				</div>

				<div class="legend-change-preview change-wrap-section">	
					<div><?php _e('Change legend', 'seatreg');?></div>
					<div class="change-dummy">
						<div class="legend-box-2"></div>
						<span class="dialog-legend-text-2"></span>
					</div>
					<div class="d-flex justify-content-between pr-3">
						<span class="change-btn" data-slide="2" data-slide-open="1"><span class="glyphicon glyphicon-arrow-left"></span><?php esc_html_e('Change name', 'seatreg');?> </span>
						<span class="change-btn" data-slide="2" data-slide-open="3"><?php esc_html_e('Change color', 'seatreg');?> <span class="glyphicon glyphicon-arrow-right"></span></span>
					</div>
				</div>

				<div class="legend-change-color change-wrap-section" style="text-align:center">
					<div id="legend-change-color-pic" class="legend-change-color-pic"></div>
					<input type="hidden" id="change-chosen-color">
					<div id="new-legend-color-info"></div>
					<span class="change-btn btn-left" data-slide="3" data-slide-open="2"><span class="glyphicon glyphicon-arrow-left"></span> <?php esc_html_e('Back', 'seatreg');?></span>
					<div id="apply-new-legend-color" class="legend-dialog-btn green-btn"><?php esc_html_e('Change color', 'seatreg');?></div>
				</div>
			</div>

			<div class="close-wrap">
				<div id="close-legend-change"><?php esc_html_e('Close legend change', 'seatreg');?></div>
			</div>
		</div>		
	</div> 

	<!-- end of #legend-dialog -->
	
	<div class="modal vert-modal fade" id="room-name-dialog" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog vert-modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title" id="myModalLabel"><?php esc_html_e('Room name', 'seatreg');?></h4>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>
				<div class="modal-body">
					<label for="room-name-dialog-input"><?php esc_html_e('Enter room name:', 'seatreg');?> </label>
					<input type="text" id="room-name-dialog-input">
					<div class="room-name-error"></div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal"><?php esc_html_e('Close', 'seatreg');?></button>
					<button type="button" class="btn btn-primary" id="room-dialog-ok"><?php esc_html_e('OK', 'seatreg');?></button>
				</div>
			</div>
		</div>
	</div>

	<div class="modal fade" id="help-dialog" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title" id="myModalLabel"><?php esc_html_e('Manual', 'seatreg');?></h4>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>
				<div class="modal-body">
				
					<div class="help-dialog-row">
						<div class="guide-item2 guide-item-mouse"></div>
						<p class="help-dialog-text">
							<?php esc_html_e('With select tool you can select individual objects in your seat map. This tool is also used to resize and change object location. To change seat/custom object location you need to drag it with mouse cursor. Resizing is done by placing mouse cursor at border of a object and dragging it to desired width or height.', 'seatreg');?>
						</p>
					</div>

					<div class="help-dialog-row">
						<div class="guide-item2 guide-item-lasso"></div>
						<p class="help-dialog-text">	
							<?php esc_html_e('Lasso tool is helpful if you wish to work with multiple objects at the same time (changing color, deleting, adding hover text and legends). Simple hold down left mouse button and move your cursor to select multiple seats/boxes.', 'seatreg');?>						
						</p>
					</div>

					<div class="help-dialog-row">
						<div class="guide-item2 guide-item-add"></div>
						<p class="help-dialog-text">
							<?php esc_html_e('With this tool you can create seats. Simply click on gray dotted box and new seat will be created. You can create multiple seats at once by dragging cursor over gray boxes.', 'seatreg');?>
						</p>
					</div>

					<div class="help-dialog-row">
						<div class="guide-item2 guide-item-add2"></div>
						<p class="help-dialog-text">
							<?php esc_html_e('Custom boxes are used to show special objects in your seat map (exit, trash can, stage and so on). Simply click on gray dotted box and new custom box will be created. You can create multiple boxes at once by dragging cursor over gray boxes.', 'seatreg');?>
						</p>
					</div>

					<div class="help-dialog-row">
						<div class="guide-item2 guide-item-text"></div>
						<p class="help-dialog-text">
							<?php esc_html_e('With text tool you can add text to your seat map. You can also change the color of the text with the color tool.', 'seatreg');?>
						</p>
					</div>

					<div class="help-dialog-row">
						<div class="guide-item2 guide-item-eraser"></div>
						<p class="help-dialog-text">
							<?php esc_html_e('With eraser tool you can remove seats and custom boxes. Simply click on object and it will be removed. You can remove multiple objects at once by holding down left mouse and moving cursor over them.', 'seatreg');?>
						</p>
					</div>
					
					<div class="help-dialog-row">
						<div class="guide-item2 guide-item-del"></div>
						<p class="help-dialog-text">
							<?php esc_html_e('Once you have selected seats/boxes with either select or lasso tool you can delete them with clicking on garbage can button. ', 'seatreg');?>
						</p>
					</div>

					<div class="help-dialog-row">
						<div class="guide-item2 guide-item-legend "></div>
						<p class="help-dialog-text">
							<?php esc_html_e('Legends are useful if you want to add labels to seats/custom boxes with special meaning. Example: Lets say you want to show where boys and where girls should sit. In legends dialog you can create, delete, change and apply legends to seats nad custom boxes.', 'seatreg');?>
						</p>
					</div>

					<div class="help-dialog-row">
						<div class="guide-item2 guide-item-hover"></div>
						<p class="help-dialog-text">
							<?php esc_html_e('Hover text is shown when user clicks or moves mouse cursor over a seat/custom box. Simply select seats or custom boxes and add text via hover text dialog.', 'seatreg');?>
						</p>
					</div>

					<div class="help-dialog-row">
						<div class="guide-item2 guide-item-pallette"></div>
						<p class="help-dialog-text">
							<?php esc_html_e('Opens up color dialog where you can apply colors to selected seats/custom boxes.', 'seatreg');?>
						</p>
					</div>

					<div class="help-dialog-row">
						<div class="guide-item2 guide-item-grid"></div>
						<p class="help-dialog-text">
							<?php esc_html_e('In seat map builder you can see lot of gray dotted boxes. These boxes are used to creat seats and custom boxes. In grid settings you can change size, distance and count of those grid boxes. That way you can change how new seats and boxes are created.', 'seatreg');?>
						</p>
					</div>

					<div class="help-dialog-row">
						<div class="guide-item2 guide-item-price"></div>
						<p class="help-dialog-text">
							<?php esc_html_e('Lets you add prices to seats. You also need to configure paypal in settings to enable payments.', 'seatreg');?>
						</p>
					</div>

					<div class="help-dialog-row">
						<div class="guide-item2 guide-item-lock"></div>
						<p class="help-dialog-text">
							<?php esc_html_e('Lets you lock or set password to seats. When seat is locked then only admin can book a seat using booking-manager. If password is added to the seat then it is required before booking can be made.', 'seatreg');?>
						</p>
					</div>

					<div class="help-dialog-row">
						<div class="guide-item2 guide-item-seat-nr"></div>
						<p class="help-dialog-text">
							<?php esc_html_e('Lets you change seat numbers. Pending and booked seat numbers can\'t be changed.', 'seatreg');?>
						</p>
					</div>

				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal"><?php esc_html_e('Close', 'seatreg');?></button>
				</div>
			</div>
		</div>
	</div>

	<div class="modal vert-modal fade" id="limit-dialog" role="dialog" aria-hidden="true">
		<div class="modal-dialog vert-modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title"><?php esc_html_e('Limited deletion', 'seatreg');?></h4>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>
				<div class="modal-body">
					<div>
						<h5><?php esc_html_e('Deleting seats with pending or booked status is not enabled', 'seatreg');?></h5>
						<p><?php esc_html_e('If you really want to delete it then you first need to relocate booking to other seat with booking-manager.', 'seatreg'); ?></p>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal"><?php esc_html_e('Close', 'seatreg');?></button>				        
				</div>
			</div><!-- /.modal-content -->
		</div><!-- /.modal-dialog -->
	</div><!-- /.modal -->

	<div class="modal vert-modal fade" id="over-limit-dialog" role="dialog" aria-hidden="true">
		<div class="modal-dialog vert-modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title"><?php esc_html_e('Saving is not allowed', 'seatreg');?></h4>
				</div>
				<div class="modal-body">
					<div id="what-to-change"></div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal"><?php esc_html_e('Close', 'seatreg');?></button>				        
				</div>
			</div><!-- /.modal-content -->
		</div><!-- /.modal-dialog -->
	</div><!-- /.modal -->

	<div class="modal fade background-image-modal" id="background-image-modal" role="dialog" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title"><?php esc_html_e('Room background image', 'seatreg');?></h4>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>
				<div class="modal-body">
					<h4><?php esc_html_e('Current room image', 'seatreg');?></h4>
					<div id="activ-room-img-wrap"></div>
					<br>

					<h4><?php esc_html_e('Upload image', 'seatreg');?> (2MB)</h4>
					<form action="<?php echo admin_url( 'admin-ajax.php' ); ?>" method="post" enctype="multipart/form-data" id="room-image-submit">
						<input type="file" name="fileToUpload" id="img-upload" class="file-select"><br>
						<input type="hidden" name="code" id="urlCode"  value="">
						<input type="hidden" name="action" value="seatreg_upload_image">
						<input type="submit" class="btn btn-success" name="submit" id="file-sub" value="<?php esc_html_e('Upload'); ?>">
						<input type="reset" class="btn btn-danger" value="Clear" id="reset-btn">
					</form>
					<br>
					<div class="progress">
						<div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
							<span class="sr-only">0% Complete</span>
						</div>
					</div>
					<br>
					<div id="img-upload-resp"></div>
					<br><br>
				
					<h4><?php esc_html_e('Previously uploaded images', 'seatreg');?></h4>
					<div id="uploaded-images"></div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal"><?php esc_html_e('Close', 'seatreg');?></button>				        
				</div>
			</div><!-- /.modal-content -->
		</div><!-- /.modal-dialog -->
	</div><!-- /.modal -->
</div> <!-- end of construction-wrapper-->