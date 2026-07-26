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
     * @param array  $args        Optional overrides: 'heading', 'preheader', 'bgColor', 'textColor', 'headingColor', 'logo'.
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
        $heading      = esc_html( $heading );
        $preheader    = esc_html( $preheader );
        $bgColor      = sanitize_hex_color( $args['bgColor'] ?? '' ) ?: SEATREG_EMAIL_DEFAULT_BG_COLOR;
        $textColor    = sanitize_hex_color( $args['textColor'] ?? '' ) ?: SEATREG_EMAIL_DEFAULT_TEXT_COLOR;
        $headingColor = sanitize_hex_color( $args['headingColor'] ?? '' ) ?: SEATREG_EMAIL_DEFAULT_HEADING_COLOR;
        $logo         = isset( $args['logo'] ) ? $args['logo'] : '';

        return strtr( $template, array(
            '{{heading}}'           => $heading,
            '{{preheader}}'         => $preheader,
            '{{content}}'           => $contentHtml,
            '{{emailBgColor}}'      => $bgColor,
            '{{emailTextColor}}'    => $textColor,
            '{{emailHeadingColor}}' => $headingColor,
            '{{emailLogo}}'         => $logo,
        ) );
    }

    /**
     * Whether the phpmailer_init logo-embed handler has been registered this request.
     *
     * @var bool
     */
    private static $logoEmbedRegistered = false;

    /**
     * Prepare a registration's email logo for embedding.
     *
     * Resolves the WordPress attachment to an absolute file path, schedules it to be
     * embedded in the next outgoing email as a CID image (never a remote URL), and returns
     * the HTML to inject into the {{emailLogo}} slot. Returns '' when there is no usable logo.
     *
     * @param int|string $attachmentId WordPress attachment ID of the logo.
     * @param string     $position     Horizontal alignment: 'left', 'center' or 'right'.
     * @return string The logo <img> HTML, or '' when no logo should be shown.
     */
    public static function prepareLogo( $attachmentId, $position = 'center' ) {
        // Reset first so an email without a logo never inherits a previous one's image.
        $GLOBALS['seatreg_email_logo_path'] = null;

        $attachmentId = (int) $attachmentId;

        if ( ! $attachmentId ) {
            return '';
        }

        $logoPath = get_attached_file( $attachmentId );

        if ( ! $logoPath || ! is_readable( $logoPath ) ) {
            return '';
        }

        $GLOBALS['seatreg_email_logo_path'] = $logoPath;
        self::registerLogoEmbed();

        $align = in_array( $position, array( 'left', 'center', 'right' ), true ) ? $position : 'center';

        return '<div style="text-align:' . $align . ';padding-bottom:16px;"><img src="cid:emaillogo" alt="" style="max-height:60px;max-width:100%;" /></div>';
    }

    /**
     * Register (once per request) the phpmailer_init handler that attaches the current
     * logo as an embedded CID image. Reads the path from a global at send time so multiple
     * emails in one request each embed their own logo. wp_mail clears attachments per send.
     */
    private static function registerLogoEmbed() {
        if ( self::$logoEmbedRegistered ) {
            return;
        }

        self::$logoEmbedRegistered = true;

        add_action( 'phpmailer_init', function( $phpmailer ) {
            if ( ! empty( $GLOBALS['seatreg_email_logo_path'] ) && is_readable( $GLOBALS['seatreg_email_logo_path'] ) ) {
                $phpmailer->AddEmbeddedImage( $GLOBALS['seatreg_email_logo_path'], 'emaillogo', 'logo.png' );
            }
        } );
    }
}
