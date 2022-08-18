=== SeatReg ===
Donate link: https://www.paypal.com/donate?hosted_button_id=9QSGHYKHL6NMU&source=url
Tags: seat registration, booking, booking seats, booking events, seat map, booking management, registration
Requires at least: 5.3
Requires PHP: 7.2.28
Tested up to: 5.9.3
Stable tag: 1.25.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
 
Create and manage seat registrations. Design your own seat maps and manage seat bookings.
 

== Description ==

Create and manage seat registrations. Design your own seat maps and manage seat bookings. 

SeatReg is a plugin that offers the following and more to build and manage your online seat registrations.
 
* Map builder helps you design your seat maps. Create, delete, resize, move around, add price, add custom legends, add custom seat numbers, add hover text and change color of your seats. Add custom text to seat map.
* Each registration can have as many seats and rooms you wish.
* With manager you keep an eye on your bookings. You can view, search, remove, confirm, change, add and download (PDF, XLSX, text) them. 
* Get an overview of your registrations. See how many open, approved or pending bookings you have.
* Many settings to control the booking flow. For example you can create custom fields that allow customers to enter extra data.
* Email templates.
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

