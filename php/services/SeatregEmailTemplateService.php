<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

/**
 * Wraps generated email content in the branded MJML-compiled base layout.
 *
 * The base layout is authored in php/templates/email/mjml/base.mjml and compiled
 * to php/templates/email/base.html via the "npm run build:emails" script. Dynamic
 * content (booking tables, links, QR codes, custom templates) is generated elsewhere
 * and injected into the {{content}} slot here.
 */
class SeatregEmailTemplateService {

    /**
     * Cached contents of the compiled base template. `false` means the file could
     * not be read, `null` means it has not been loaded yet.
     *
     * @var string|false|null
     */
    private static $baseTemplate = null;

    private static function getBaseTemplate() {
        if ( self::$baseTemplate === null ) {
            $templatePath = SEATREG_PLUGIN_FOLDER_DIR . 'php/templates/email/base.html';
            self::$baseTemplate = is_readable( $templatePath ) ? file_get_contents( $templatePath ) : false;
        }

        return self::$baseTemplate;
    }

    /**
     * Wrap generated HTML content in the branded email layout.
     *
     * @param string $contentHtml The email body HTML to inject into the layout.
     * @param array  $args        Optional overrides: 'heading', 'preheader', 'bgColor', 'textColor', 'headingColor'.
     * @return string The full HTML email, or the raw content if the template is unavailable.
     */
    public static function renderEmail( $contentHtml, $args = array() ) {
        $template = self::getBaseTemplate();

        if ( $template === false ) {
            // Template missing/unreadable - never block email delivery.
            return $contentHtml;
        }

        $heading      = isset( $args['heading'] ) ? $args['heading'] : get_bloginfo( 'name' );
        $preheader    = isset( $args['preheader'] ) ? $args['preheader'] : $heading;
        $bgColor      = ! empty( $args['bgColor'] ) ? $args['bgColor'] : SEATREG_EMAIL_DEFAULT_BG_COLOR;
        $textColor    = ! empty( $args['textColor'] ) ? $args['textColor'] : SEATREG_EMAIL_DEFAULT_TEXT_COLOR;
        $headingColor = ! empty( $args['headingColor'] ) ? $args['headingColor'] : SEATREG_EMAIL_DEFAULT_HEADING_COLOR;

        return str_replace(
            array( '{{heading}}', '{{preheader}}', '{{content}}', '{{year}}', '{{emailBgColor}}', '{{emailTextColor}}', '{{emailHeadingColor}}' ),
            array( $heading, $preheader, $contentHtml, gmdate( 'Y' ), $bgColor, $textColor, $headingColor ),
            $template
        );
    }
}
