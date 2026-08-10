<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

/**
 * Shared shell for the standalone pages the booker lands on: the payment return page,
 * the booking confirm page and the booking status page.
 */
class SeatregPublicPageService {

    /**
     * The registration specific colors for the page shell.
     *
     * @param object|null $options Registration options row. Colors fall back to the plugin defaults
     *                             when it is null or a color is not configured.
     * @return string CSS
     */
    public static function getColorStyles( $options = null ) {
        $bgColor      = sanitize_hex_color( $options->page_background_color ?? '' ) ?: SEATREG_PAGE_DEFAULT_BG_COLOR;
        $textColor    = sanitize_hex_color( $options->page_text_color ?? '' ) ?: SEATREG_PAGE_DEFAULT_TEXT_COLOR;
        $headingColor = sanitize_hex_color( $options->page_heading_color ?? '' ) ?: SEATREG_PAGE_DEFAULT_HEADING_COLOR;

        return '.seatreg-page{background-color:' . $bgColor . ';color:' . $textColor . ';}' .
            '.seatreg-card__title{color:' . $headingColor . ';}';
    }

    /**
     * Open the page shell and print the logo, registration name and title.
     *
     * @param array $args 'title' (string),
     *                    'name' (string) shown above the title - the registration name, falling back to the site name,
     *                    'logoId' (int) WordPress attachment id of the registration's page logo.
     */
    public static function renderPageStart( $args = array() ) {
        $title   = isset( $args['title'] ) ? $args['title'] : '';
        $name    = ! empty( $args['name'] ) ? $args['name'] : get_bloginfo( 'name' );
        $logoUrl = ! empty( $args['logoId'] ) ? wp_get_attachment_image_url( (int) $args['logoId'], 'medium' ) : '';
        ?>
        <div class="seatreg-page">
            <div class="seatreg-card">
                <div class="seatreg-card__body">
                    <?php if( $logoUrl ): ?>
                        <img class="seatreg-card__logo" src="<?php echo esc_url( $logoUrl ); ?>" alt="<?php echo esc_attr( $name ); ?>" />
                    <?php endif; ?>
                    <div class="seatreg-card__name"><?php echo esc_html( $name ); ?></div>
                    <h1 class="seatreg-card__title"><?php echo esc_html( $title ); ?></h1>
                    <div class="seatreg-card__content">
        <?php
    }

    /**
     * Close the page shell opened by renderPageStart().
     */
    public static function renderPageEnd() {
        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
