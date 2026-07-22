<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

function seatreg_generate_registration_strings() {
	$translations = new stdClass();
	$translations->illegalCharactersDetec = esc_html__('Illegal characters detected', 'seatreg');
	$translations->emailNotCorrect = esc_html__('Email address is not correct', 'seatreg');
	$translations->somethingWentWrong = esc_html__('Something went wrong. Please try again', 'seatreg');
	$translations->selectionIsEmpty = esc_html__('Seat selection is empty', 'seatreg');
    $translations->selectionIsEmptyPlace = esc_html__('Place selection is empty', 'seatreg');
    $translations->youCanAdd_ = esc_html__('You can add ', 'seatreg');
    $translations->_toCartClickTab = esc_html__(' to selection by selecting boxes', 'seatreg');
	$translations->toCartClickTab = esc_html__(' to selection by clicking/tabbing them', 'seatreg');
	$translations->regClosedAtMoment = esc_html__('Registration is closed at the moment', 'seatreg');
	$translations->confWillBeSentTo = esc_html__('Confirmation will be sent to:', 'seatreg');
	$translations->confWillBeSentTogmail = esc_html__('Confirmation will be sent to (Gmail):', 'seatreg');
	$translations->gmailReq = esc_html__('Email (Gmail required)', 'seatreg');
	$translations->_fromRoom_ = esc_html__(' from room ', 'seatreg');
	$translations->_toSelection = esc_html__(' to booking?', 'seatreg');
	$translations->_isOccupied = esc_html__(' is occupied', 'seatreg');
	$translations->_isPendingState = esc_html__(' is in pending state', 'seatreg');
	$translations->regOwnerNotConfirmed = esc_html__('(registration admin has not confirmed it)', 'seatreg');
	$translations->selectionIsFull = esc_html__('Booking is full', 'seatreg');
    $translations->_isAlreadySelected = esc_html__(' is already selected!', 'seatreg');
	$translations->_regUnderConstruction = esc_html__('Under construction', 'seatreg');
	$translations->emptyField = esc_html__('Empty field', 'seatreg');
	$translations->remove = esc_html__('Remove', 'seatreg');
	$translations->add_ = esc_html__('Add ', 'seatreg');
	$translations->addToBooking = esc_html__('Add to Booking', 'seatreg');
	$translations->openSeatsInRoom_ = esc_html__('Open seats in the room: ', 'seatreg');
    $translations->openPlacesInRoom_ = esc_html__('Open places in the room: ', 'seatreg');
	$translations->pendingSeatInRoom_ = esc_html__('Pending bookings in the room: ', 'seatreg');
	$translations->confirmedSeatInRoom_ = esc_html__('Approved bookings in the room: ', 'seatreg');
	$translations->seat = esc_html__('seat', 'seatreg');
    $translations->place = esc_html__('place', 'seatreg');
	$translations->firstName = esc_html__('Firstname', 'seatreg');
	$translations->lastName = esc_html__('Lastname', 'seatreg');
	$translations->eMail = esc_html__('Email', 'seatreg');
	$translations->this_ = esc_html__('This ', 'seatreg');
    $translations->_selected = esc_html__(' selected', 'seatreg');
    $translations->_seatSelected = esc_html__(' seat selected', 'seatreg');
    $translations->_seatsSelected = esc_html__(' seats selected', 'seatreg');
    $translations->_placeSelected = esc_html__(' place selected', 'seatreg');
    $translations->_placesSelected = esc_html__(' places selected', 'seatreg');
    $translations->bookingsConfirmed = esc_html__('Your booking is approved', 'seatreg');
    $translations->bookingsConfirmedPending = esc_html__('Your booking is now in pending state. Registration admin needs to approve it', 'seatreg');
    $translations->selectingGuide = esc_html__('Select a seat you want to add to booking', 'seatreg');
    $translations->selectingGuidePlace = esc_html__('Select a place you want to add to booking', 'seatreg');
    $translations->Booked = esc_html__('Booked', 'seatreg');
    $translations->Pending = esc_html__('Pending', 'seatreg');
    $translations->maxSeatsToAdd = esc_html__('Total seats you can add to booking is ', 'seatreg');
    $translations->maxPlacesToAdd = esc_html__('Total places you can add to booking is ', 'seatreg');
    $translations->seatCosts_ = esc_html__('Booking this seat costs ', 'seatreg');
    $translations->placeCosts_ = esc_html__('Booking this place costs ', 'seatreg');
    $translations->bookingTotalCostIs_ = esc_html__('Booking total cost is ', 'seatreg');
    $translations->receiptSent = esc_html__('Booking receipt has been sent to your email', 'seatreg');
    $translations->payForBookingLink = esc_html__('Click the following link to pay for the booking', 'seatreg');
    $translations->yes = esc_html__('Yes', 'seatreg');
    $translations->no = esc_html__('No', 'seatreg');
    $translations->seatIsLocked = esc_html__('Seat is locked', 'seatreg');
    $translations->placeIsLocked = esc_html__('Place is locked', 'seatreg');
    $translations->pleaseEnterPassword = esc_html__('Please enter password', 'seatreg');
    $translations->passwordNotCorrect = esc_html__('Password is not correct', 'seatreg');
    $translations->closedPleaseChooseNewDate = esc_html__('Registration not open for today. Please choose another date', 'seatreg');
    $translations->controlledZoom = esc_html__('Hold Z key also to zoom', 'seatreg');
    $translations->wpLoginRequired = esc_html__('Please log in to make a booking', 'seatreg');
    $translations->enterCouponCode = esc_html__('Please enter coupon code', 'seatreg');
    $translations->failedToApplyCoupon = esc_html__('Failed to apply coupon', 'seatreg');
    $translations->couponNotFound = esc_html__('Coupon not found', 'seatreg');
    /* translators: %s: Coupon code */
    $translations->couponApplied = esc_html__('Coupon %s applied', 'seatreg');
    /* translators: %1$s: Coupon code, %2$s: Discount amount */
    $translations->couponAppliedWithDiscount = esc_html__('Coupon %1$s applied with -%2$s discount', 'seatreg');

	return $translations;
}

function seatreg_generate_admin_strings() {
    $translations = new stdClass();
    $translations->hoverDeleteSuccess = esc_html__('Hover text deleted', 'seatreg');
    $translations->hoverTextAdded = esc_html__('Hover text added', 'seatreg');
    $translations->legendNameChanged = esc_html__('Legend name changed', 'seatreg');
    $translations->legendColorChanged = esc_html__('Legend color changed', 'seatreg');
    $translations->buildingGridUpdated = esc_html__('Building grid updated', 'seatreg');
    $translations->roomNameChanged = esc_html__('Room name changed', 'seatreg');
    $translations->roomNameSet = esc_html__('New room added', 'seatreg');
    $translations->roomNotExist = esc_html__('Room does not exist', 'seatreg');
    $translations->seatNotExist = esc_html__('Seat does not exist', 'seatreg');
    $translations->seatIdNotExist = esc_html__('Seat id dose not exist', 'seatreg');
    $translations->seatAlreadyBookedPending = esc_html__('Seat is already booked/pending', 'seatreg');
    $translations->errorBookingUpdate = esc_html__('Error updating booking', 'seatreg');
    $translations->hoverError = esc_html__('Error while creating hover', 'seatreg');
    $translations->legendChangeError = esc_html__('Error while changing legend', 'seatreg');
    $translations->legendNameTaken = esc_html__('Legend name is taken', 'seatreg');
    $translations->lagendNameMissing = esc_html__('Legend name missing!', 'seatreg');
    $translations->legendColorTaken = esc_html__('Legend color is taken. Choose another', 'seatreg');
    $translations->legendAddedTo = esc_html__('Legend added to', 'seatreg');
    $translations->oneRoomNeeded = esc_html__('You must have at least one room', 'seatreg');
    $translations->alreadyInRoom = esc_html__('Already in this room', 'seatreg');
    $translations->allRoomsNeedName = esc_html__('All rooms must have name', 'seatreg');
    $translations->illegalCharactersDetec = esc_html__('Illegal characters detected', 'seatreg');
    $translations->illegalCharactersDetecCouponCode = esc_html__('Illegal characters detected in coupon code', 'seatreg');
    $translations->couponCodeLengthLimitExceeded = esc_html__('Coupon code cant be longer than 20 characters', 'seatreg');
    $translations->illegalCharactersDetecDiscount = esc_html__('Illegal characters detected in discount value', 'seatreg');
    $translations->missingName = esc_html__('Name missing', 'seatreg');
    $translations->cantDelRoom_ = esc_html__('You can\'t delete room ', 'seatreg');
    $translations->_cantDelRoomBecause = esc_html__(' because it contains pending or confirmed seats. You must remove them with manager first.', 'seatreg');
    $translations->roomNameMissing = esc_html__('Room name is missing', 'seatreg');
    $translations->roomNameExists = esc_html__('Room name already exists. You must choose another', 'seatreg');
    $translations->youHaveSelected = esc_html__('You have selected', 'seatreg');
    $translations->_boxesSpanLi = esc_html__(' box/boxes</span></li>', 'seatreg');
    $translations->toSelectOneBox_ = esc_html__('To select one box use ', 'seatreg');
    $translations->toSelectMultiBox_ = esc_html__('To select multiple boxes use ', 'seatreg');
    $translations->selectBoxesToAddHover = esc_html__('Select box/boxes to add hover text', 'seatreg');
    $translations->selectBoxesToAddColor = esc_html__('Select box/boxes to add color', 'seatreg');
    $translations->loading = esc_html__('Loading...', 'seatreg');
    $translations->selectBoxesToDelete = esc_html__('Select box/boxes you want to delete', 'seatreg');
    $translations->colorApplied = esc_html__('Color applied', 'seatreg');
    $translations->noLegendsCreated = esc_html__('You have not made and legends yet', 'seatreg');
    $translations->_noSelectBoxToAddLegend = esc_html__(' You have not selected any box/boxes to add legends', 'seatreg');
    $translations->_charRemaining = esc_html__(' characters remaining', 'seatreg');
    $translations->deleteRoom_ = esc_html__('Are you sure you want to delete room ', 'seatreg');
    $translations->unsavedChanges = esc_html__('Unsaved changes. You sure you want to leave?', 'seatreg');
    $translations->createLegend = esc_html__('Create new legend', 'seatreg');
    $translations->cancelLegendCreation = esc_html__('Cancel legend creation', 'seatreg');
    $translations->chooseLegend = esc_html__('Choose legend', 'seatreg');
    $translations->enterLegendName = esc_html__('Enter legend name', 'seatreg');
    $translations->ok = esc_html__('Ok', 'seatreg');
    $translations->cancel = esc_html__('Cancel', 'seatreg');
    $translations->open = esc_html__('Open', 'seatreg');
    $translations->boxes = esc_html__('boxes', 'seatreg');
    $translations->box = esc_html__('box', 'seatreg');
    $translations->noBoxesSelected = esc_html__('No boxes selected', 'seatreg');
    $translations->pendingSeat = esc_html__('Pending seat', 'seatreg');
    $translations->pendingPlace = esc_html__('Pending place', 'seatreg');
    $translations->confirmedSeat = esc_html__('Approved seat', 'seatreg');
    $translations->confirmedPlace= esc_html__('Approved place', 'seatreg');
    $translations->save = esc_html__('Save', 'seatreg');
    $translations->saving = esc_html__('Saving...', 'seatreg');
    $translations->saved = esc_html__('Saved', 'seatreg');
    $translations->room = esc_html__('room', 'seatreg');
    $translations->bookingUpdated = esc_html__('Booking updated', 'seatreg');
    $translations->notSet = esc_html__('Not set', 'seatreg');
    $translations->enterRegistrationName = esc_html__('Please enter registration name', 'seatreg');
    $translations->registrationNameLimit = esc_html__('Name must be between 1-255 characters', 'seatreg');
    $translations->pleaseEnterName = esc_html__('Please enter name', 'seatreg');
    $translations->pleaseEnterOptionValue = esc_html__('Please enter option value', 'seatreg');
    $translations->areYouSure = esc_html__('Are you sure?', 'seatreg');
    $translations->pleaseAddAtLeastOneOption = esc_html__('Please add at least one option', 'seatreg');
    $translations->nameAlreadyUsed = esc_html__('Name already used', 'seatreg');
    $translations->noBgImageInRoom = esc_html__('Current room does not have background image', 'seatreg');
    $translations->removeFromRoom = esc_html__('Remove from room', 'seatreg');
    $translations->choosePictureToUpload = esc_html__('Choose a picture to upload', 'seatreg');
    $translations->imageNameIllegalChar = esc_html__('Image name contains illegal characters', 'seatreg');
    $translations->addToRoomBackground = esc_html__('Add to room background', 'seatreg');
    $translations->remove = esc_html__('Remove', 'seatreg');
    $translations->showPendingBookings = esc_html__('Show pending bookings', 'seatreg');
    $translations->showApprovedBookings = esc_html__('Show approved bookings', 'seatreg');
    $translations->separateFirstandLastName = esc_html__('Separate First name and Last name', 'seatreg');
    $translations->pleaseEnterPayPalBusinessEmail = esc_html__('Please enter PayPal business email', 'seatreg');
    $translations->pleaseEnterPayPalButtonId = esc_html__('Please enter PayPal button id', 'seatreg');
    $translations->pleaseEnterPayPalCurrencyCode = esc_html__('Please enter currency code', 'seatreg');
    $translations->pleaseEnterStripeApiKey = esc_html__('Please enter Stripe API key', 'seatreg');
    $translations->pleaseProvideStripeApiSecretKey = esc_html__('Please provide Stripe API secret key', 'seatreg');
    $translations->pricesAdded = esc_html__('Prices added', 'seatreg');
    $translations->noSeatsSelected = esc_html__('No seats/places selected!', 'seatreg');
    $translations->emailNotCorrect = esc_html__('Email address is not correct', 'seatreg');
    $translations->emailFromNotCorrect = esc_html__('Email FROM address is not correct', 'seatreg');
    $translations->checkEmailAddress = esc_html__('Check your email address', 'seatreg');
    $translations->emailSendingFailed= esc_html__('Email sending failed', 'seatreg');
    $translations->pealseWait= esc_html__('Please wait', 'seatreg');
    $translations->yes = esc_html__('Yes', 'seatreg');
    $translations->no = esc_html__('No', 'seatreg');
    $translations->noActivityLogged = esc_html__('No activity logged', 'seatreg');
    $translations->bookingStatusUpdated = esc_html__('Booking status updated', 'seatreg');
    $translations->permanentlyDeleteBookingConfirm = esc_html__('This will permanently delete the selected bookings. This cannot be undone. Are you sure?', 'seatreg');
    $translations->bookingPermanentlyDeleted = esc_html__('Booking permanently deleted', 'seatreg');
    $translations->newBookingWasAddedRefreshingThaPage = esc_html__('Booking was added. Page will refresh in a second', 'seatreg');
    $translations->duplicateSeatDetected = esc_html__('Duplicate seat detected!', 'seatreg');
    $translations->emailTemplateNotCorrect = esc_html__('Email template is missing required keywords', 'seatreg');
    $translations->approvedBookingEmailTemplateIllegalCharacter = esc_html__('The approved booking email template contains disallowed characters.', 'seatreg');
    $translations->pendingBookingEmailTemplateIllegalCharacter = esc_html__('The pending booking email template contains disallowed characters.', 'seatreg');
    $translations->emailVerificationEmailTemplateIllegalCharacter = esc_html__('The email verification template contains disallowed characters.', 'seatreg');
    $translations->lockSeat = esc_html__('Lock seat', 'seatreg');
    $translations->setPassword = esc_html__('Set password', 'seatreg');
    $translations->changesApplied = esc_html__('Changes applied', 'seatreg');
    $translations->addPriceDescription = esc_html__('Please add price description', 'seatreg');
    $translations->enableZipExtension = esc_html__('PHP Zip extension is needed for XLSX file generation', 'seatreg');
    $translations->reloadingPage = esc_html__('Reloading page. Please wait.', 'seatreg');
    $translations->dateNotProvided = esc_html__('Date not provided', 'seatreg');
    $translations->dateNotCorrect = esc_html__('Date not correct', 'seatreg');
    $translations->momentAgo = esc_html__('Moment ago', 'seatreg');
    $translations->somethingWentWrong = esc_html__('Something went wrong. Please try again', 'seatreg');
    $translations->tokenRemoved = esc_html__('API token removed', 'seatreg');
    $translations->tokenCreated = esc_html__('API token created', 'seatreg');
    $translations->createApiToken = esc_html__('Create API token', 'seatreg');
    $translations->enterCustomPaymentTitle = esc_html__('Please enter custom payment title', 'seatreg');
    $translations->enterCustomPaymentdescription = esc_html__('Please enter custom payment description', 'seatreg');
    $translations->title = esc_html__('Title', 'seatreg');
    $translations->description = esc_html__('Description', 'seatreg');
    $translations->paymentIconUploaded = esc_html__('Custom payment icon uploaded', 'seatreg');
    $translations->paymentIconUploadedFail = esc_html__('Custom payment icon upload failed', 'seatreg');
    $translations->paymentIcon = esc_html__('Payment icon', 'seatreg');
    $translations->paymentStatusUpdated = esc_html__('Payment status updated', 'seatreg');
    $translations->currencyCodeNotCorrect = esc_html__('Currency code in not valid', 'seatreg');
    $translations->textSaved = esc_html__('Text saved', 'seatreg');
    $translations->errorSavingText = esc_html__('Error at saving text', 'seatreg');
    $translations->price = esc_html__('Price', 'seatreg');
    $translations->priceNotFound = esc_html__('Price not found', 'seatreg');
    $translations->enterCouponCode = esc_html__('Please enter coupon code', 'seatreg');
    $translations->enterCouponDiscount = esc_html__('Please enter coupon discount', 'seatreg');
    $translations->couponcode = esc_html__('Coupon code', 'seatreg');
    $translations->discount = esc_html__('Discount', 'seatreg');
    $translations->delete = esc_html__('Delete', 'seatreg');
    $translations->email = esc_html__('Email', 'seatreg');
    $translations->bookingMainEmail = esc_html__('Booking main email', 'seatreg');
    $translations->editEmailNotValid = esc_html__('Provided email address is not valid', 'seatreg');
    $translations->multiBookingMailEmailEditDesc = esc_html__('Primary contact email if more than one seat is booked', 'seatreg');
    $translations->roomDescriptionSet = esc_html__('Room description changed', 'seatreg');
    $translations->primaryEmailValidationFailed = esc_html__('Booking primary email validation failed', 'seatreg');
    $translations->pleaseEnterBookingPrimaryEmail = esc_html__('Please enter booking primary email', 'seatreg');
    $translations->preventsBookingWhenSameInputValueProvided = esc_html__('Prevents booking when same input value provided', 'seatreg');
    $translations->unique = esc_html__('Unique', 'seatreg');
    $translations->makeFieldOptional = esc_html__('Make field optional', 'seatreg');
    $translations->optional = esc_html__('Optional', 'seatreg');

    /* Booking flow summary (settings page) */
    $translations->flowGroupBefore = esc_html__('Before booking', 'seatreg');
    $translations->flowGroupBooking = esc_html__('Making a booking', 'seatreg');
    $translations->flowGroupAfter = esc_html__('After submitting', 'seatreg');
    $translations->flowJumpToSetting = esc_html__('Go to this setting', 'seatreg');
    $translations->flowSeatSingular = esc_html__('seat', 'seatreg');
    $translations->flowSeatPlural = esc_html__('seats', 'seatreg');
    $translations->flowPlaceSingular = esc_html__('place', 'seatreg');
    $translations->flowPlacePlural = esc_html__('places', 'seatreg');
    $translations->flowClosed = esc_html__('Your registration is currently closed, so visitors cannot make a booking.', 'seatreg');
    /* translators: %s: the close reason text entered by the admin */
    $translations->flowClosedReason = esc_html__('Visitors are shown the following reason: %s', 'seatreg');
    $translations->flowPassword = esc_html__('Visitors must enter the access password to view it.', 'seatreg');
    $translations->flowRequireLogin = esc_html__('Only visitors logged in to your site can book.', 'seatreg');
    /* translators: %1$d: number of bookings, %2$s: seats or places */
    $translations->flowWpBookingLimit = esc_html__('Each logged-in user can make at most %1$d separate bookings (a single booking can include several %2$s).', 'seatreg');
    /* translators: %1$d: number of seats, %2$s: seats or places */
    $translations->flowWpSeatLimit = esc_html__('Across all their bookings, each logged-in user can book at most %1$d %2$s.', 'seatreg');
    $translations->flowDateWindowBoth = esc_html__('Bookings can only be made within the registration\'s scheduled start and end dates.', 'seatreg');
    $translations->flowDateWindowStart = esc_html__('Bookings can only be made once the registration\'s scheduled start date is reached.', 'seatreg');
    $translations->flowDateWindowEnd = esc_html__('Bookings can only be made until the registration\'s scheduled end date.', 'seatreg');
    $translations->flowTimeWindowBoth = esc_html__('Each day, bookings can only be made during the registration\'s scheduled hours.', 'seatreg');
    $translations->flowTimeWindowStart = esc_html__('Each day, bookings can only be made after the registration\'s scheduled start time.', 'seatreg');
    $translations->flowTimeWindowEnd = esc_html__('Each day, bookings can only be made before the registration\'s scheduled end time.', 'seatreg');
    $translations->flowCalendar = esc_html__('The registration runs on a calendar, so each booking is made for a specific day.', 'seatreg');
    $translations->flowCalendarDates = esc_html__('Bookings are only possible on the dates you have opened.', 'seatreg');
    $translations->calendarSelectedDates = esc_html__('Selected dates', 'seatreg');
    $translations->calendarNoDatesSelected = esc_html__('No dates selected yet', 'seatreg');
    $translations->calendarRemoveDate = esc_html__('Remove date', 'seatreg');
    /* translators: %s: seats or places */
    $translations->flowSelect = esc_html__('Visitors select %s on the map.', 'seatreg');
    /* translators: %1$s: seats or places, %2$d: maximum number per booking */
    $translations->flowSelectMax = esc_html__('Visitors select %1$s on the map, up to %2$d per booking.', 'seatreg');
    /* translators: %1$s: seats or places, %2$s: comma-separated list of shown details */
    $translations->flowShowBookingData = esc_html__('Already-booked %1$s publicly display the booking details you have chosen to show (%2$s).', 'seatreg');
    $translations->flowShowBookingDataFullName = esc_html__('full name', 'seatreg');
    /* translators: %s: seat or place */
    $translations->flowAutoDialog = esc_html__('The booking form opens automatically as soon as a %s is selected.', 'seatreg');
    /* translators: %s: seats or places */
    $translations->flowManualDialog = esc_html__('After choosing %s, visitors open the selection menu to complete their booking.', 'seatreg');
    /* translators: %s: seat or place */
    $translations->flowOnePersonCheckout = esc_html__('Booking details are entered once and applied to every %s.', 'seatreg');
    /* translators: %s: seat or place */
    $translations->flowPerSeatCheckout = esc_html__('Booking details are entered for each %s.', 'seatreg');
    $translations->flowRequireName = esc_html__('A full name (first and last) is required.', 'seatreg');
    $translations->flowGmailRequired = esc_html__('A Gmail address is required.', 'seatreg');
    /* translators: %1$d: maximum number of bookings per email, %2$s: seats or places */
    $translations->flowEmailLimit = esc_html__('A booking\'s main contact email can be used for at most %1$d bookings (a single booking can include several %2$s).', 'seatreg');
    $translations->flowCustomFields = esc_html__('Bookers also fill in the custom fields you have created, which are required by default.', 'seatreg');
    $translations->flowCustomFieldsOptional = esc_html__('Some custom fields are optional and can be left blank.', 'seatreg');
    $translations->flowCustomFieldsUnique = esc_html__('Some custom fields must contain a value that no other booking has used.', 'seatreg');
    $translations->flowEmailVerify = esc_html__('Bookings must be verified through an email link before they are submitted.', 'seatreg');
    $translations->flowStatusPage = esc_html__('Every booking gets its own status page where the booker can view the booking details and its current status.', 'seatreg');
    $translations->flowBookingPdfPending = esc_html__('While a booking is pending, the status page offers a downloadable booking PDF with a scannable QR code.', 'seatreg');
    $translations->flowBookingPdfApproved = esc_html__('Once a booking is approved, the status page offers a downloadable booking PDF with a scannable QR code.', 'seatreg');
    $translations->flowBookingPdfBoth = esc_html__('The status page offers a downloadable booking PDF with a scannable QR code, both while the booking is pending and after it is approved.', 'seatreg');
    $translations->flowApprovedEmail = esc_html__('When a booking is approved, the booker receives a receipt email.', 'seatreg');
    $translations->flowApprovedEmailQr = esc_html__('That receipt email also includes a scannable QR code.', 'seatreg');
    $translations->flowPending = esc_html__('Each booking stays pending until an admin approves it.', 'seatreg');
    $translations->flowPaidAutoApprove = esc_html__('A pending booking is approved automatically once its payment is completed.', 'seatreg');
    $translations->flowBookerPendingNotification = esc_html__('The booker is emailed when their booking becomes pending.', 'seatreg');
    /* translators: %d: number of minutes */
    $translations->flowPendingExpiration = esc_html__('If there is no payment activity, a pending booking is automatically removed after %d minutes.', 'seatreg');
    /* translators: %s: comma-separated list of payment statuses */
    $translations->flowPendingExpirationStatuses = esc_html__('Expired pending bookings are also removed even if they have one of these payment statuses: %s.', 'seatreg');
    $translations->flowAutoApproved = esc_html__('Bookings are approved automatically.', 'seatreg');
    $translations->flowPayment = esc_html__('Payment is requested on the booking status page after the booking is made.', 'seatreg');
    $translations->flowCoupons = esc_html__('In the cart, bookers can apply a coupon code to receive a discount.', 'seatreg');
    $translations->flowRedirectStatus = esc_html__('Afterward, visitors are redirected to their booking status page.', 'seatreg');

    return $translations;
}

function seatreg_generate_companion_app_strings() {
    return [
        'noConnections' => esc_html__('No connections', 'seatreg'),
        'initializing' => esc_html__('Initializing', 'seatreg'),
        'bookings' => esc_html__('bookings', 'seatreg'),
        'loadingBookings' => esc_html__('Loading bookings', 'seatreg'),
        'enterCorrectHttpsUrl' => esc_html__('Please enter correct HTTPS site URL', 'seatreg'),
        'enterWPSiteUrl' => esc_html__('Please enter the root URL of your WordPress site where SeatReg plugin is activated', 'seatreg'),
        'enterHere' => esc_html__('Enter here', 'seatreg'),
        'next' => esc_html__('Next', 'seatreg'),
        /* translators: %s: Error message */
        'errorMessage' => esc_html__('An error occurred: %s. Please try again', 'seatreg'),
        'save' => esc_html__('Save', 'seatreg'),
        'back' => esc_html__('Back', 'seatreg'),
        'enterApiTokenPlaceholder' => esc_html__('Enter API token', 'seatreg'),
        'enterSeatRegApiToken' => esc_html__('Enter SeatReg public API token', 'seatreg'),
        'tokenValidationFailed' => esc_html__('Validating token request failed', 'seatreg'),
        'connectionAdded' => esc_html__('Connection added', 'seatreg'),
        /* translators: %s: API token */
        'tokenAlreadyAdded' => esc_html__('Token %s is already added', 'seatreg'),
        'requestFailed' => esc_html__('Request failed', 'seatreg'),
        'unableToEstablishConnection' => esc_html__('Unable to Establish Connection. Check your internet connection and verify the website availability', 'seatreg'),
        'home' => esc_html__('Home', 'seatreg'),
        'addToken' => esc_html__('Add Connection', 'seatreg'),
        'pushPermissionFailed' => esc_html__('Failed to get permissions for push notification!', 'seatreg'),
        'pushPhysicalDeviceRequired' => esc_html__('Must use physical device for Push Notifications', 'seatreg'),
        'notificationsNotSupported' => esc_html__('This browser does not support notifications', 'seatreg'),
        'webNotificationPermissionFailed' => esc_html__('Failed to get permissions for web notifications', 'seatreg'),
        /* translators: %s: Booker name */
        'bookingName' => esc_html__('Name: %s', 'seatreg'),
        /* translators: %s: Room name */
        'bookingRoom' => esc_html__('Room: %s', 'seatreg'),
        /* translators: %s: Seat number */
        'bookingSeat' => esc_html__('Seat: %s', 'seatreg'),
        /* translators: %s: Email address */
        'bookingEmail' => esc_html__('Email: %s', 'seatreg'),
        /* translators: %s: Booker email */
        'bookingBookerEmail' => esc_html__('Booker email: %s', 'seatreg'),
        /* translators: %s: Calendar date */
        'bookingCalendarDate' => esc_html__('Calendar date: %s', 'seatreg'),
        /* translators: %s: Booking date */
        'bookingDate' => esc_html__('Booking date: %s', 'seatreg'),
        /* translators: %s: Booking approved date */
        'bookingApprovedDate' => esc_html__('Booking approved date: %s', 'seatreg'),
        /* translators: %s: Registration name */
        'newBookingsSingle' => esc_html__('%s got a new booking', 'seatreg'),
        /* translators: %1$s: Registration name, %2$s: Number of new bookings */
        'newBookingsMultiple' => esc_html__('%1$s got %2$s new bookings', 'seatreg'),
        /* translators: %1$s: Registration name, %2$s: Number of continuous request failures */
        'bookingNotificationsDisabledDueToFailures' => esc_html__('%1$s had %2$s continuous request failures. Turning off booking notifications.', 'seatreg'),
        'cameraNotFound' => esc_html__(
            'No camera was found. Please connect a camera or use a different device.',
            'seatreg'
        ),
        'cameraAccessDenied' => esc_html__(
            'Camera access was denied. Please allow camera permissions.',
            'seatreg'
        ),
        'cameraRequiresHttps' => esc_html__(
            'Camera access requires HTTPS.',
            'seatreg'
        ),
        'cameraStartFailed' => esc_html__(
            'Unable to start the camera. Please try again.',
            'seatreg'
        ),
        'searchCleared' => esc_html__(
            'Search cleared',
            'seatreg'
        ),
        'bookingSearch' => esc_html__('Booking search', 'seatreg'),
        'searchPlaceholder' => esc_html__('Search', 'seatreg'),
        'closeQRScanner' => esc_html__('Close QR scanner', 'seatreg'),
        'scanQR' => esc_html__('Scan QR', 'seatreg'),
        'closeButton' => esc_html__('Close', 'seatreg'),
        'clearButton' => esc_html__('Clear', 'seatreg'),
        'applyButton' => esc_html__('Apply', 'seatreg'),
        'changeDateButton' => esc_html__('Change date', 'seatreg'),
        'optionsUpdated' => esc_html__('Options updated', 'seatreg'),
        'optionsDialogTitle' => esc_html__('Options', 'seatreg'),
        'connectionRemoved' => esc_html__('Connection removed', 'seatreg'),
        'optionsButton' => esc_html__('Options', 'seatreg'),
        'bookingsButton' => esc_html__('Bookings', 'seatreg'),
        'removeButton' => esc_html__('Remove', 'seatreg'),
        'deleteConnectionDialogTitle' => esc_html__('Delete connection', 'seatreg'),
        'deleteConnectionDialogMessage' => esc_html__('Are you sure?', 'seatreg'),
        'bookingStatusPending' => esc_html__('Pending', 'seatreg'),
        'bookingStatusApproved' => esc_html__('Approved', 'seatreg'),
        /* translators: %s: Booking status */
        'bookingStatusLabel' => esc_html__('Status: %s', 'seatreg'),
        'bookingFilteringActive' => esc_html__('Booking filtering is active', 'seatreg')
    ];
}
