<p align="center">
    <img src="img/seatreg.png" alt="SeatReg">
</p>


# SeatReg - Create and manage online registrations
Create and manage online registrations. Design your own registration layout and manage bookings.

## Install
Clone this project to your local WordPress installation plugins directory. Make sure you name the folder as seatreg.
Activate the plugin through the ‘Plugins’ menu in WordPress. You can also download it directly from WordPress plugins directory https://wordpress.org/plugins/seatreg/

## Translations
Translations are taken from https://translate.wordpress.org/projects/wp-plugins/seatreg/

## Capabilities
The plugin utilizes two capabilities to control access to admin area. Administrators have these capabilities out of the box. 

**seatreg_manage_bookings**\
Has access to admin areas to manage bookings.

**seatreg_manage_events**\
Has access to admin areas to manage events.

## Plugin actions
SeatReg exposes couple of actions you can hook into to run your own code.

**seatreg_action_booking_submitted**\
Triggered when booking is submitted with registration page. Gets booking ID as parameter.

**seatreg_action_booking_manually_added**\
Triggered when booking is added with booking manager. Gets booking ID as parameter.

**seatreg_action_booking_pending**\
Triggered when booking gets pending status. Gets booking ID as parameter.

**seatreg_action_booking_approved**\
Triggered when booking gets approved status. Gets booking ID as parameter.

**seatreg_action_booking_removed**
Triggered when booking gets removed. Gets booking ID as parameter.

## NPM Scripts

### registration-styles
Compiles registration view styles

### registration-scripts
Compiles registration view scripts (currently not used)

### watch
Watches and compiles registration view styles and scripts

### builder-styles
Compiles map builder styles

### admin-styles
Compiles admin styles
