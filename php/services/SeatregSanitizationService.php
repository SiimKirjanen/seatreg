<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregSanitizationService {
    /**
     * Sanitize email templates using wp_kses.
     *
     * @param string $template The email template content.
     * @return string The sanitized email template.
     */
    public static function sanitizeEmailTemplate($template) {
         // Remove backslashes
         $template = str_replace('\\', '', $template);

        return wp_kses($template, []);
    }
}