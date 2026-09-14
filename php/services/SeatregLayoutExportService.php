<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

class SeatregLayoutExportService {
    const FORMAT_VERSION = 1;

    /**
     *
     * Build the contents of a layout export file. Everything above the layout itself is
     * there to tell the importer what it is holding, or to answer "where did this come
     * from" when someone sends in a file that will not import.
     *
     * @param object $registration A row from the seatreg table
     * @return array|null Null when the layout column does not hold valid JSON
     *
    */
    public static function buildExport($registration) {
        $layout = json_decode($registration->registration_layout);

        if( !is_object($layout) ) {
            return null;
        }

        return array(
            'seatregLayoutExport' => self::FORMAT_VERSION,
            'pluginVersion' => self::getPluginVersion(),
            'siteUrl' => get_site_url(),
            'registrationCode' => $registration->registration_code,
            'registrationName' => $registration->registration_name,
            'exportedAt' => gmdate('c'),
            'layout' => $layout
        );
    }

    /**
     *
     * Return the file name to offer the export under. A name made only of characters
     * sanitize_file_name strips leaves nothing behind, so the code stands in for it.
     *
     * @param object $registration A row from the seatreg table
     * @return string
     *
    */
    public static function getFileName($registration) {
        $name = sanitize_file_name($registration->registration_name);

        if( $name === '' ) {
            $name = $registration->registration_code;
        }

        return 'seatreg-layout-' . $name . '-' . gmdate('Ymd') . '.json';
    }

    private static function getPluginVersion() {
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        $pluginData = get_plugin_data( SEATREG_PLUGIN_FOLDER_DIR . 'seatreg.php', false, false );

        return isset($pluginData['Version']) ? $pluginData['Version'] : '';
    }
}
