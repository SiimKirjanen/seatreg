<div id="construction-wrapper">

	<div class="build-head">
		<h1 class="reg-title"><?php _e('Registration name:', 'seatreg'); ?> <span class="reg-title-name"></span></h1>
		<h2 class="room-title"><?php _e('Room:', 'seatreg');?> <span class="room-title-name"></span><span class="change-room-name"><?php _e('Change name', 'seatreg');?></span></h2>
		<div id="room-selection-wrapper"></div>
		<div class="cre-del-room-wrapper">
			<span id="new-room-create" class="room-action"><span class="glyphicon glyphicon-plus"></span><?php _e('Add room', 'seatreg');?> </span>
			<span id="current-room-delete" class="room-action"><span class="glyphicon glyphicon-minus"></span><?php _e('Delete room', 'seatreg');?></span>
		</div>
	</div> <!-- end of build head-->
	
	<div class="build-section" id="build-section">
		<div class="mouse-actions-and-help">
			<div class="help-icon" data-toggle="modal" data-target="#help-dialog"></div>

			<div class="mouse-action-boxes">
				<div data-action="1" class="mouse-option action1" id="mouse-option-active" title="Select, move and resize tool"></div>
				<div data-action="2" class="mouse-option action2" title="Seat creation tool"></div>
				<div data-action="5" class="mouse-option action5" title="Custom box tool"></div>
				<div data-action="3" class="mouse-option action3" title="Eraser tool"></div>
				<div data-action="4" class="mouse-option action4" title="Lasso select tool"></div>
			</div>

			<div id="build-section-click-controls">
				<div class="click-control-left">
					<label for="click-control-move-nr"><?php _e('Move selected boxes', 'seatreg');?></label> <input type="number" id="click-control-move-nr" value="15" style="width: 50px"> <label for="click-control-move-nr"><?php _e('pixels to', 'seatreg');?></label> 
				</div>

				<div class="click-control-right">
					<div>
						<span class="glyphicon glyphicon-circle-arrow-up" aria-hidden="true" data-destination="up"></span>
						<span class="glyphicon glyphicon-circle-arrow-down" aria-hidden="true" data-destination="down"></span>

						<span class="glyphicon glyphicon-circle-arrow-left" aria-hidden="true" data-destination="left"></span>
						<span class="glyphicon glyphicon-circle-arrow-right" aria-hidden="true" data-destination="right"></span>
					</div>
				</div>
			</div>
		</div>

		<div class="build-area-side">
			<div class="side-option hey delete-box" data-action="1" title="Delete selected"></div>
			<div class="side-option hey legend-option" data-action="2" title="Legends" data-toggle="modal" data-target="#legends-dialog"></div>
			<div class="side-option hey bubble-text" data-action="4" title="Hover text"></div>
			<div class="side-option hey palette-call" data-action="5" title="Color"></div>
			<div class="side-option hey background-image" data-action="7" title="Background image" data-toggle="modal" data-target="#background-image-modal"></div>
			<div class="side-option hey grid-stats" data-action="6" title="Grid settings" data-toggle="modal" data-target="#skeleton-dialog"></div>
		</div>
	
		<div class="build-area-wrapper" data-cursor="1">
			<div id="build-area-loading-wrap">
				<img src="<?php echo  plugins_url( 'css/loading.png', dirname(__FILE__) ) ?>" id="loading-img" alt="Loading...">
				<span class='loading-text'><?php _e('Loading...', 'seatreg');?></span>
			</div>
			<div class="build-area dragger" draggable="false">
			</div><!-- end of build-area -->
		</div><!-- end of build-area-wrapper -->
	</div>

	<div class="build-controls">
		<div class="legends"></div>
		<div class="update-wrapper">
			<div id="build-section-message-wrap" data-toggle="modal" data-target="#limit-dialog">
				<span class="message-text"><span style="vertical-align:middle"><?php _e('Seat deletion is limited!', 'seatreg');?></span><span class="more-message-btn"></span></span>
			</div>

			<div id="build-head-stats-2">
				<div class="build-head-stats-2-icon"></div>
				<div class="build-head-stats-2-text"></div>
			</div>

			<button id="update-data">
				<span class="glyphicon glyphicon-save"></span>
				<i class="fa fa-coffee" style="display:none"></i>
				<span style="margin-left:4px" class="save-text"><?php _e('Save', 'seatreg');?></span>
			</button>
			<a id="registration-link" class="save-check view-btn" target="_blank" href="<?php echo plugins_url('reg/registration.php', dirname(__FILE__) ); ?>">
				<span class="glyphicon glyphicon-eye-open"></span><span style="margin-left:4px" class="link-text"><?php _e('View registration', 'seatreg');?></span>
			</a>
			<div id="server-response"></div>
		</div>
		
	</div><!-- end of build-controls -->

	<div class="build-footer"></div>

	<div class="modal fade" id="color-dialog" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" id="myModalLabel"><?php _e('Color', 'seatreg');?></h4>
			</div>
			<div class="modal-body">
			<div class="color-dialog-info"></div>
			<div id="picker"></div>
			</div>
			<div class="modal-footer">
			<button type="button" class="btn btn-default" data-dismiss="modal"><?php _e('Close', 'seatreg');?></button>			
			</div>
		</div>
		</div>
	</div>

	<div class="modal fade" id="hover-dialog" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" id="myModalLabel"><?php _e('Adding hover text', 'seatreg');?></h4>
			</div>
			<div class="modal-body">
			<div class="hover-dialog-info"></div>
			<label for="box-hover-text"><?php _e('Enter text', 'seatreg');?></label><br>
			<textarea id="box-hover-text" rows="4" cols="26" maxlength="150"></textarea>
			<div class="box-hover-char-rem"></div>
			<div><?php _e('Leave field empty to remove existing hover text', 'seatreg');?></div>
			</div>
			<div class="modal-footer">
			<button type="button" class="btn btn-default" data-dismiss="modal"><?php _e('Close', 'seatreg');?></button>
			<button type="button" class="btn btn-primary" id="box-hover-submit"><?php _e('OK', 'seatreg');?></button>
			</div>
		</div>
		</div>
	</div>

	<div class="modal vert-modal fade" id="skeleton-dialog" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
		<div class="modal-dialog vert-modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" id="myModalLabel"><?php _e('Building grid helps you to create boxes', 'seatreg');?></h4>
			</div>
			<div class="modal-body">
			<div>

				<?php _e('Box size', 'seatreg');?><br>
				<label>x<input type="text" class="skeleton-input" id="size-x" value="36"/></label> <br>
				<label>y<input type="text" class="skeleton-input" id="size-y" value="36"/></label> <br>

			</div>

			<div>
				<?php _e('Box count', 'seatreg');?><br>
				<label><?php _e('x-axis', 'seatreg');?><input type="text" class="skeleton-input" id="count-x" value="22"/></label> <br>
				<label><?php _e('y-axis', 'seatreg');?><input type="text" class="skeleton-input" id="count-y" value="20"/></label> 
				<br><br>
			</div>

			<div>
				<?php _e('Distance between boxes', 'seatreg');?> <br>
				<label>x<input type="text" class="skeleton-input" id="margin-x" value="10"/></label> <br>
				<label>y<input type="text" class="skeleton-input" id="margin-y" value="10"/></label> <br><br>					
			</div>

			</div>
			<div class="modal-footer">
			<button type="button" class="btn btn-default" data-dismiss="modal"><?php _e('Close', 'seatreg');?></button>
			<button type="button" class="btn btn-primary build-skeleton"><?php _e('Update grid', 'seatreg');?></button>
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
					<label for="use-select" class="legend-dialog-label"><?php _e('Existing legends:', 'seatreg');?></label>
					<select class="legend-select" id="use-select"></select> <br>
					<div id="apply-legend" class="commands-btn"><?php _e('Apply legend:', 'seatreg');?></div>
				</div>

				<div class="legend-dialog-div">
					<label for="delete-select" class="legend-dialog-label"><?php _e('Remove legend from registration:', 'seatreg');?></label>
					<select class="legend-select" id="delete-select"></select> 
					<div id="delete-legend" class="commands-btn"><?php _e('Delete:', 'seatreg');?></div>
				</div>

				<div class="legend-dialog-div">
					<label for="legend-delete-select-room" class="legend-dialog-label"><?php _e('Remove legend from this room:', 'seatreg');?></label>
					<select class="legend-select-room" id="legend-delete-select-room"></select> 
					<div id="delete-legend-from-room" class="commands-btn"><?php _e('Remove:', 'seatreg');?></div>
				</div>

				<div class="legend-dialog-div">
					<label for="legend-change-select" class="legend-dialog-label"><?php _e('Change legend:', 'seatreg');?></label>
					<select id="legend-change-select" class="legend-select"></select> 
					<div id="change-legend" class="commands-btn"><?php _e('Change:', 'seatreg');?></div>
				</div>

			</div>
		</div>

		<div id="legend-creator">
			<div class="legend-dialog-slide">
				<div>
					<label for="new-legend-text" class="legend-dialog-label-step"> 
						<span class="legend-step"><?php _e('Step 1.', 'seatreg');?> </span> <span><?php _e('Enter legend name:', 'seatreg');?></span>
					</label>

				</div>
				<input type="text" id="new-legend-text" maxlength="20" class="form-control"><br>
				<span id="new-legend-text-rem"></span><br>
				<div class="next-step step-btn" data-slide="1" data-slide-open="2"><?php _e('Step 2.', 'seatreg');?>  <span class="glyphicon glyphicon-arrow-right"></span></div>
			</div>

			<div class="legend-dialog-slide">
				<div>
					<label class="legend-dialog-label-step">
						<span class="legend-step"><?php _e('Step 2.', 'seatreg');?> </span><span><?php _e('Choose legend color:', 'seatreg');?></span>
					</label>
				</div>

				<div id="picker2"></div>
				<div class="prev-step step-btn" data-slide="2" data-slide-open="1"><?php _e('Step 1.', 'seatreg');?> <span class="glyphicon glyphicon-arrow-left"></span></div>
				<div class="next-step step-btn" data-slide="2" data-slide-open="3"><?php _e('Step 3.', 'seatreg');?> <span class="glyphicon glyphicon-arrow-right"></span></div>
			</div>
			<div class="legend-dialog-slide">
				<div>
					<label class="legend-dialog-label-step">
						<span class="legend-step"><?php _e('Step 3.', 'seatreg');?> </span><span><?php _e('Review new legend', 'seatreg');?></span>
					</label>
				</div>

				<div id="dummy-legend">
					<div class="legend-box" style="background-color: #61B329"></div>
					<span class="dialog-legend-text"></span>
					<input type="hidden" id="hiddenColor" value="#61B329">
				</div>

				<div id="create-new-legend" class="green-toggle"><?php _e('Create new legend', 'seatreg');?></div>

				<div class="prev-step step-btn" data-slide="3" data-slide-open="2"><?php _e('Step 2.', 'seatreg');?> <span class="glyphicon glyphicon-arrow-left"></span></div>

			</div>	
		</div>

		<div class="toggle-lcreator-wrap">
			<div id="toggle-lcreator" class="green-toggle"><?php _e('Create new legend', 'seatreg');?></div>
		</div>

		<div id="legend-change-wrap">
			<div id="legend-change-wrap-inner">
				<div class="legend-change-name change-wrap-section">

					<label for="old-legend-name"><?php _e('Enter new name', 'seatreg');?></label>
					<input type="text" id="new-legend-name" class="form-control" maxlength="20">
					<input type="hidden" id="old-legend-name">
					<div id="new-legend-name-info"></div>
					<div id="apply-new-legend-name" class="legend-dialog-btn green-btn"><?php _e('Change name', 'seatreg');?></div>
					<span class="change-btn btn-right" data-slide="1" data-slide-open="2"><?php _e('Back', 'seatreg');?><span class="glyphicon glyphicon-arrow-right"></span></span>
				</div>

				<div class="legend-change-preview change-wrap-section">	
					<div><?php _e('Change legend', 'seatreg');?></div>
					<div class="change-dummy">
						<div class="legend-box-2"></div>
						<span class="dialog-legend-text-2"></span>
					</div>
					<span class="change-btn btn-left" data-slide="2" data-slide-open="1"><span class="glyphicon glyphicon-arrow-left"></span><?php _e('Change name', 'seatreg');?> </span>
					<span class="change-btn btn-right" data-slide="2" data-slide-open="3"><?php _e('Change color', 'seatreg');?> <span class="glyphicon glyphicon-arrow-right"></span></span>
				</div>

				<div class="legend-change-color change-wrap-section">
					<div id="legend-change-color-pic"></div>
					<input type="hidden" id="change-chosen-color">
					<div id="new-legend-color-info"></div>
					<span class="change-btn btn-left" data-slide="3" data-slide-open="2"><span class="glyphicon glyphicon-arrow-left"></span> <?php _e('Back', 'seatreg');?></span>
					<div id="apply-new-legend-color" class="legend-dialog-btn green-btn"><?php _e('Change color', 'seatreg');?></div>
				</div>
			</div>

			<div class="close-wrap">
				<div id="close-legend-change"><?php _e('Close legend change', 'seatreg');?></div>
			</div>

		</div>
				
	</div> 

	<!-- end of #legend-dialog -->
	
	<div class="modal vert-modal fade" id="room-name-dialog" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog vert-modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" id="myModalLabel"><?php _e('Room name', 'seatreg');?></h4>
			</div>
			<div class="modal-body">

			<label for="room-name-dialog-input"><?php _e('Enter room name:', 'seatreg');?> </label>
			<input type="text" id="room-name-dialog-input">
			<div class="room-name-char-rem"></div>
			<div class="room-name-error"></div>
		
		</div>
		<div class="modal-footer">
			<button type="button" class="btn btn-default" data-dismiss="modal"><?php _e('Close', 'seatreg');?></button>
			<button type="button" class="btn btn-primary" id="room-dialog-ok"><?php _e('OK', 'seatreg');?></button>
		</div>
		</div>
		</div>
	</div>

	<div class="modal fade" id="help-dialog" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" id="myModalLabel"><?php _e('Manual', 'seatreg');?></h4>
			</div>
			<div class="modal-body">
			
				<div class="help-dialog-row">
					<div class="guide-item2 guide-item-mouse"></div>
					<p class="help-dialog-text">
						<?php _e('With select tool you can select individual objects in your seat map. This tool is also used to resize and change object location.
						To change seat/custom object location you need to drag it with mouse cursor.
						Resizing is done by placing mouse cursor at border of a object and dragging it to desired width or height.', 'seatreg');?>
					</p>
				</div>

				<div class="help-dialog-row">
					<div class="guide-item2 guide-item-add"></div>
					<p class="help-dialog-text">
						<?php _e('With this tool you can create seats.
						Simply click on gray dotted box and new seat will be created.
						You can create multiple seats at once by dragging cursor over gray boxes.', 'seatreg');?>
					</p>
				</div>

				<div class="help-dialog-row">
					<div class="guide-item2 guide-item-add2"></div>
					<p class="help-dialog-text">
						<?php _e('Custom boxes are used to show special objects in your seat map (exit, trash can, stage and so on).
						Simply click on gray dotted box and new custom box will be created.
						You can create multiple boxes at once by dragging cursor over gray boxes.', 'seatreg');?>
					</p>
				</div>

				<div class="help-dialog-row">
					<div class="guide-item2 guide-item-eraser"></div>
					<p class="help-dialog-text">
						<?php _e('With eraser tool you can remove seats and custom boxes.
						Simply click on object and it will be removed.
						You can remove multiple objects at once by holding down left mouse and moving cursor over them.', 'seatreg');?>
					</p>
				</div>
				
				<div class="help-dialog-row">
					<div class="guide-item2 guide-item-lasso"></div>
					<p class="help-dialog-text">	
						<?php _e('Lasso tool is helpful if you wish to work with multiple objects at the same time (changing color, deleting, adding hover text and legends).
						Simple hold down left mouse button and move your cursor to select multiple seats/boxes.', 'seatreg');?>						
					</p>
				</div>

				<div class="help-dialog-row">
					<div class="guide-item2 guide-item-del"></div>
					<p class="help-dialog-text">
						<?php _e('Once you have selected seats/boxes with either select or lasso tool you can delete them with clicking on garbage can button. ', 'seatreg');?>
					</p>
				</div>

				<div class="help-dialog-row">
					<div class="guide-item2 guide-item-legend "></div>
					<p class="help-dialog-text">
						<?php _e('Legends are useful if you want to add labels to seats/custom boxes with special meaning. 
						Example: Lets say you want to show where boys and where girls should sit. In legends dialog you can create, delete, change and apply legends to seats nad custom boxes.', 'seatreg');?>
					</p>
				</div>

				<div class="help-dialog-row">
					<div class="guide-item2 guide-item-hover"></div>
					<p class="help-dialog-text">
						<?php _e('Hover text is shown when user clicks or moves mouse cursor over a seat/custom box. Simply select seats or custom boxes and add text via hover text dialog.', 'seatreg');?>
						
					</p>
				</div>

				<div class="help-dialog-row">
					<div class="guide-item2 guide-item-pallette"></div>
					<p class="help-dialog-text">
						<?php _e('Opens up color dialog where you can apply colors to selected seats/custom boxes.', 'seatreg');?>
					</p>
				</div>

				<div class="help-dialog-row">
					<div class="guide-item2 guide-item-grid"></div>
					<p class="help-dialog-text">
						<?php _e('In seat map builder you can see lot of gray dotted boxes. These boxes are used to creat seats and custom boxes. In grid settings you can change size, distance and count of those grid boxes. That way you can change how new seats and boxes are created.', 'seatreg');?>
					</p>
				</div>

			</div>
			<div class="modal-footer">
			<button type="button" class="btn btn-default" data-dismiss="modal"><?php _e('Close', 'seatreg');?></button>
			
			</div>
		</div>
		</div>
	</div>

	<div class="modal vert-modal fade" id="limit-dialog" role="dialog" aria-hidden="true">
		<div class="modal-dialog vert-modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title"><?php _e('Limited deletion', 'seatreg');?></h4>
			</div>
			<div class="modal-body">

			<div>
				<h4><?php _e('If room contains pending or confirmed seats then seat deletion is limited.', 'seatreg');?></h4>
				<p><?php _e('Example:', 'seatreg');?> <br><?php _e('Lets say you have seats with numbers 1,2,3 and 4.', 'seatreg');?> <br><?php _e('If someone occupies seat number 2 you won\'t be able to delete seats 1 and 2.', 'seatreg');?> 
					</p>
			</div>

			</div>
			<div class="modal-footer">
			<button type="button" class="btn btn-default" data-dismiss="modal"><?php _e('Close', 'seatreg');?></button>				        
			</div>
		</div><!-- /.modal-content -->
		</div><!-- /.modal-dialog -->
	</div><!-- /.modal -->

	<div class="modal vert-modal fade" id="over-limit-dialog" role="dialog" aria-hidden="true">
		<div class="modal-dialog vert-modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title"><?php _e('Saving is not allowed', 'seatreg');?></h4>
			</div>
			<div class="modal-body">
			<div id="what-to-change"></div>
		</div>
			<div class="modal-footer">
			<button type="button" class="btn btn-default" data-dismiss="modal"><?php _e('Close', 'seatreg');?></button>				        
			</div>
		</div><!-- /.modal-content -->
		</div><!-- /.modal-dialog -->
	</div><!-- /.modal -->


	<div class="modal fade" id="background-image-modal" role="dialog" aria-hidden="true">
		<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title"><?php _e('Room background image', 'seatreg');?></h4>
			</div>
			<div class="modal-body">

			<h4><?php _e('Current room image', 'seatreg');?></h4>

			<div id="activ-room-img-wrap"></div>
			<br>

			<h4><?php _e('Upload image (2MB)', 'seatreg');?></h4>
			<form action="<?php echo '/wp-admin/admin-ajax.php'; ?>" method="post" enctype="multipart/form-data" id="room-image-submit">
				<input type="file" name="fileToUpload" id="img-upload">
				<br>
				<input type="hidden" name="code" id="urlCode"  value="">
				<input type="hidden" name="action" value="seatreg_upload_image">
				<input type="submit" class="btn btn-success" name="submit" id="file-sub" value="Upload">
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
			
			<h4><?php _e('Previously uploaded images', 'seatreg');?></h4>
			<div id="uploaded-images"></div>

			</div>
			<div class="modal-footer">
			<button type="button" class="btn btn-default" data-dismiss="modal"><?php _e('Close', 'seatreg');?></button>				        
			</div>
		</div><!-- /.modal-content -->
		</div><!-- /.modal-dialog -->
	</div><!-- /.modal -->
</div> <!-- end of construction-wrapper-->