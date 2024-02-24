=== SeatReg ===
Donate link: https://www.paypal.com/donate?hosted_button_id=9QSGHYKHL6NMU&source=url
Tags: seat registration, booking seats, booking events, seat map, booking management
Requires at least: 5.3
Requires PHP: 7.2.28
Tested up to: 6.3.1
Stable tag: 1.53.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
 
Create and manage online registrations. Design your own registration scheme and manage bookings.
 

== Description ==

Create and manage online registrations. Design your own registration scheme and manage bookings. 

SeatReg is a plugin that offers the following and more to build and manage online registrations.
 
* Map builder helps you design your registration scheme. Create, delete, resize, move around, add price, add custom legends, add custom seat numbers, add hover text and change color of your seats. Add custom text to seat map.
* Each registration can have as many seats and rooms you wish.
* With manager you keep an eye on your bookings. You can view, search, remove, confirm, change, add and download (PDF, XLSX, text) them. 
* Get an overview of your registrations. See how many open, approved or pending bookings you have.
* Many settings to control the booking flow. For example you can create custom fields that allow customers to enter extra data.
* Email templates.
* Calendar mode
* Scrollable and resizable registration view that can be provided to people via direct link or by inserting it to your website pages via shortcode (example of shortcode: [seatreg code=d0ca254995]).
* Paypal and Stripe payments support.
  
== Installation ==
 
1. Install SeatReg either via the WordPress.org plugin directory, or by uploading the files to your server.
2. Activate the plugin through the ‘Plugins’ menu in WordPress.

== Screenshots ==

1. Map builder
2. Registration view
3. Booking manager
4. Custom fields
5. Overview
6. Legends and background image
7. Seat custom numbering

== Changelog ==

= 1.53.2 =
* Fix conflict between Elementor plugin.

= 1.53.1 =
* Fixed Stripe payment issue when charge.failed event happens. 

= 1.53.0 =
* Added option to limit how many total seats can user make across all their bookings.
* Fix user limit check. 

= 1.52.0 =
* Added option that requires users to be logged in to make a booking.
* Added option to limit how many bookings can one logged in user make.

= 1.51.1 =
* Added auto page refresh to booking status page when page regains focus.

= 1.51.0 =
* Added booking seat price to booking manager and also to PDF, XLSX and text files.
* Open payment gateways in separate tab in booking status page. Prevents issues when using shortcode.

= 1.50.1 =
* Increased custom field max length to 100 characters.
* Allow comma character in custom fields.

= 1.50.0 =
* Added option to automatically redirect booker to booking status page.
* Support a greater number of characters in the custom payment description.

= 1.49.3 =
* Fixed registration view issue with mobile touch event and multi price selection.
* Added deposit payed option to payment status.

= 1.49.2 =
* Minor style changes on registration page.

= 1.49.1 =
* Stripe API secret key update improvement.

= 1.49.0 =
* Added option to manually change payment status.
* Added payment status column for booking manager.

= 1.48.4 =
* Trim booking email field to prevent whitespace.

= 1.48.3 =
* Copy more settings when copying registration.
* Fixed issue where seat price was not showing in booking confirmation modal when using only custom payments.

= 1.48.2 =
* ID lookup for add booking (booking manager) now displays only open seats.

= 1.48.1 =
* Fixed issue with booking manager actions with calendar mode

= 1.48.0 =
* Added option to configure booking notification email.
* CSS classes for styling legends and selected seats in registration page.
* Using selected WordPress timezone to display dates.

= 1.47.0 =
* Added booking PDF to booking status page. In settings you can control when the PDF is displayed.

= 1.46.0 =
* Registration start and end date now use WordPress timezone
* Minor improvements

= 1.45.0 =
* Added option to add custom styles for booking status and booking confirm page.
* Trigger 'seatreg_action_booking_approved' event also with payments.
* Added option to separate first and last names when generating text, xlsx and pdf files. 
* Added time restrictions to settings.

= 1.44.1 =
* Fixed missue with missing approved emails.

= 1.44.0 =
* Added bookings and events capability. Allows to control who has access to bookings and events in admin area. Administrators have both capabilities by default.

= 1.43.0 =
* Custom payment icons added.

= 1.42.0 =
* Seat ID lookup can now fill seat ID.
* Room name selection for add booking in booking manager.
* Changed registration background image locations. Will fix issue where plugin update deleted existing background images.

= 1.41.0 =
* Added option to add multiple custom payments.
* Added option to control if pending booking notification is sent out to the booker.
* Smaller fixes/improvements.

= 1.40.0 =
* Added option to customize button text that opens seat selection in registration view.
* Registration view now displays currency symbols instead of currency code.

= 1.39.4 =
* Fix for Stripe zero decimal currencies.

= 1.39.3 =
* PHP warning fixes
* Updated PDF and XLSX generation libs.

= 1.39.2 =
* Fixed bug in overview page.

= 1.39.1 =
* Local notification support for Android app.

= 1.39.0 =
* Added option to add custom markup to registration page. Will be displayed above the confirm booking button.

= 1.38.1 =
* Booking manager add booking feature fix.

= 1.38.0 =
* Added public API.

= 1.37.0 =
* Added possibility to change custom fields order.
* Added options to add custom email subjects.

= 1.36.0 =
* Added option to add custom styles to registration page.
* Added plugin related actions for developers to hook custom code into.

= 1.35.2 =
* Display custom payment booking price in registration view.

= 1.35.1 =
* Enabled custom payments in registration view.

= 1.35.0 =
* Added custom payments option.
* Added seat prefix to seat ID lookup modal.

= 1.34.0 =
* Added seat ID lookup modal for booking manager.
* Added controlled scroll feature to registration view.
* Changed seat number field size in DB.

= 1.33.0 =
* Added calendar mode.

= 1.32.3 =
* Made booking related email subjects translatable.
* Allow more characters with custom fields.
* Added Stripe API key validation.

= 1.32.2 =
* Fixed bug that only allowed to open PDF file generation.

= 1.32.1 =
* Bug fix on multi price validation.
* PHP warning fix.
* Minor improvements.

= 1.32.0 =
* Added setting to add limit how many bookings can be made with the same email.
* Added option to make text custom field unique.

= 1.31.1 =
* Fixed issue where locked seat text was not showing.

= 1.31.0 =
* Added option to set FROM email address for SeatReg system related emails.

= 1.30.1 =
* Fixed DB table issue caused by multi price selection.

= 1.30.0 =
* Added possibility to add multiple prices to seat.

= 1.29.4 =
* Fixed issue that was caused when creating a seat outside of builder grid.

= 1.29.3 =
* Allow space character usage in bookings.
* Minor improvements (PHP warnings removed, redirect to settings page when settings saved).

= 1.29.2 =
* Fixed issue with older PHP version (trailing comma).

= 1.29.1 =
* Fixed issue with custom seat numbers in scheme editor.

= 1.29.0 =
* Improved PDF, XLSX and Text file generation filtering.
* Added powered by Seatreg to registration view.

= 1.28.0 =
* Added options to use generic place instead of a seat. Useful if your registration is not dealing with seats.

= 1.27.1 =
* Open Redirect vulnerability fix.

= 1.27.0 =
* Booking manager can now delete individual seats from bookings.

= 1.26.3 =
* Fixed map eidtor seat/boxes moving issue with larger grids.
* Changed Stripe API version to 2020-08-27 when dealing with Stripe.
* Added donation text.

= 1.26.2 =
* Changed Stripe API version.

= 1.26.1 =
* Using spesific Stripe API version to prevent problems.
* Fixed bug where Stripe confirmed payment booking were not set approved when configured to do so.

= 1.26.0 =
* Added zooming and moving controls to mobile view.

= 1.25.1 =
* Fixed issue with map editor seat numbering.

= 1.25.0 =
* Added payment table to booking check page.
* Added payment table to approved booking email and email template.

= 1.24.0 =
* Stripe payment support added.

= 1.23.1 =
* Fix booking submit when special characters are used in seat nr.

= 1.23.0 =
* Color picker update on map-eidtor page. Allows to set transparent background.

= 1.22.0 =
* Checkout field values copy when multiple seats selected
* Minor style fixes 

= 1.21.0 =
* Registration mobile view changes
* Shortcode height attribute support. Lets you control the height of shortcode.

= 1.20.1 =
* Fixed issue where start and end date where not displayed correctly in registration view

= 1.20.0 =
* Seat number change functionality added to map-editor.

= 1.19.2 =
* PHP warning fix

= 1.19.1 =
* Bug fix

= 1.19.0 =
* Seat lock and seat password feature added.

= 1.18.0 =
* Display warning in booking status page when pending booking expiration time is set
* Minor changes to booking status page
* Improved image upload URL in map-editor

= 1.17.1 =
* Fixed registration view initial zoom out if map is too large for the screen

= 1.17.0 =
* Text added with text tool can now be resized.
* Booking table added to booking status page
* Booking table is added to booking notification email (admin).

= 1.16.1 =
* Fixed issue where QR code was not sent when using approved booking email template

= 1.16.0 =
* You can now customize verification, pending booking and approved booking emails.

= 1.15.0 =
* Removed booking info from email verification.
* Send out booking update email when booking gets pending state
* Fixed issue when confirming a booking can cause approved booking state change. 
* Added setting to control how long can booking be in pending state.

= 1.14.0 =
* Fixed issue where certain characters in registration name would break booking manager
* Fixed registration view when Google page translate is used
* Not using language files from the project. Language files will be pulled from translate.wordpress.org/projects/wp-plugins/seatreg
* Booking status will be set to 0 when related PayPal payment is refunded or reversed
* Improved booking activity logging

= 1.13.0 =
* Map editor can now add text to registration view

= 1.12.0 =
* Shortcode modal added to more items
* Added functionality to settings to show custom field data in registration view

= 1.11.0 =
* More item added to Home page items
* Added functionality to copy existing registration
* Custom fields can now be created with space characters

= 1.10.3 =
* Added booking id to XLSX
* Removed Payment txn id and Payment received from XLSX
* Fixed booking status page link in registration page

= 1.10.2 =
* Fixed PayPal payment bugs
* Code maintenance

= 1.10.1 =
* Fixed wrong QR code in receipt emails when approving multiple bookings with booking-manager

= 1.10.0 =
* Added booking status link to booking manager and receipt email
* Booking manager improvements
* Code maintenance

= 1.9.1 =
* Booking manager bug fix on new registrations when adding a booking

= 1.9.0 =
* Booking manager can now add bookings
* Added more logging for QR Code sending
* Fixed booking manager loading spinner position
* When email confirm is turned off and you need to pay for you booking then booking success dialog has text telling people to click to pay for booking

= 1.8.0 =
* Added QR testing tool
* Changed QR code save directory
* Bug fix on receipt email custom fields

= 1.7.0 =
* Booking receipt email is now sent to booker when booking is approved (enabled by default).
* You can enable QR code for receipt email in settings (not enabled by default).

= 1.6.0 =
* Danish translations added. Thank you Kim Soenderup.
* You can now set approved bookings back to pending.
* You can now view booking and registration activity logs.
* Fixed an issue with custom field labels.
* Minor UI improvements

= 1.5.0 =
* Added support for WordPress 5.8
* You can now set registration close reason.
* Minor UI and style improvements.

= 1.4.0 =
* Added tools submenu page with email testing
* Minor UI improvements

= 1.3.0 =
* Added support for PayPal payments
* With map builder you can now add prices to seats
* In settings you can turn on and configure PayPal

= 1.2.0 =
* Added pot file for translations
* Added Estonian translations
* Text fixes and changes
* Fixed bug when trying to remove image from room

= 1.1.0 =
* Added shortcode
* Some style fixes

= 1.0.9 =
* Don't ask confirmation email when multi seats enabled and email confirmation turned off.

= 1.0.8 =
* Fixed issue with multiple seat booking edit

= 1.0.7 =
* Using Unix timestamps in DB.
* Some fixes and improvements.

= 1.0.6 =
* added logic for DB updates

= 1.0.5 =
* removed default values from db tables where it is not supported. Set table engine to innoDB.
* bug fixes

= 1.0.4 =
* xlsxwriter class update. PHP 8 compatible.

= 1.0.3 =
* PHP warning fixed

= 1.0.2 =
* Removed captcha 

= 1.0.1 =
* Using template_include filter instead of page_template
* Missing includes fix

