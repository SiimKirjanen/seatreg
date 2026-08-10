<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

/**
 * Shared shell for the standalone pages the booker lands on: the payment return page,
 * the booking confirm page and the booking status page.
 *
 * These pages are rendered outside the theme (see seatreg_remove_all_styles) so the shell
 * brings its own styles. They are printed inline instead of enqueued: the pages run behind
 * a style allowlist, and an inline block cannot be stripped from it by accident.
 */
class SeatregPublicPageService {

    /**
     * Print the shell styles. Must be called before wp_head() so that a registration's own
     * custom CSS (injected on wp_head at priority 100) still overrides the shell.
     *
     * @param object|null $options Registration options row. Colors fall back to the plugin defaults
     *                             when it is null or a color is not configured.
     */
    public static function renderStyles( $options = null ) {
        $bgColor      = sanitize_hex_color( $options->page_background_color ?? '' ) ?: SEATREG_PAGE_DEFAULT_BG_COLOR;
        $textColor    = sanitize_hex_color( $options->page_text_color ?? '' ) ?: SEATREG_PAGE_DEFAULT_TEXT_COLOR;
        $headingColor = sanitize_hex_color( $options->page_heading_color ?? '' ) ?: SEATREG_PAGE_DEFAULT_HEADING_COLOR;
        ?>
        <style>
            .seatreg-page {
                box-sizing: border-box;
                min-height: 100vh;
                margin: 0;
                padding: 40px 24px;
                background-color: <?php echo esc_html( $bgColor ); ?>;
                color: <?php echo esc_html( $textColor ); ?>;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
                font-size: 15px;
                line-height: 1.6;
            }
            .seatreg-page * {
                box-sizing: border-box;
            }
            .seatreg-card {
                /* Sized to its content: a short message stays compact, a wide booking table grows the card */
                width: fit-content;
                min-width: min(440px, 100%);
                max-width: 100%;
                margin: 0 auto;
                background-color: #ffffff;
                border-radius: 12px;
                box-shadow: 0 2px 4px rgba(16, 24, 40, 0.06), 0 12px 32px rgba(16, 24, 40, 0.08);
                overflow: hidden;
            }
            .seatreg-card__body {
                padding: 28px 40px 36px;
                text-align: center;
            }
            .seatreg-card__logo {
                display: block;
                max-height: 60px;
                max-width: 100%;
                margin: 0 auto 16px;
            }
            .seatreg-card__name {
                margin-bottom: 6px;
                color: #7a8699;
                font-size: 12px;
                font-weight: 600;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                word-break: break-word;
            }
            .seatreg-card__title {
                margin: 0 0 20px;
                padding: 0;
                color: <?php echo esc_html( $headingColor ); ?>;
                font-size: 22px;
                font-weight: 600;
                letter-spacing: -0.3px;
                line-height: 1.35;
            }
            .seatreg-card__content {
                text-align: center;
                word-wrap: break-word;
            }
            .seatreg-card__content a {
                color: #2b6cb0;
            }
            .seatreg-card__content p {
                margin: 0 0 14px;
            }
            .seatreg-card__content p:last-child {
                margin-bottom: 0;
            }
            .seatreg-card__content img {
                max-width: 100%;
                height: auto;
            }
            .seatreg-card__content h2 {
                margin: 0 0 12px;
                color: #1a2233;
                font-size: 18px;
                line-height: 1.4;
            }
            .seatreg-card__content h3 {
                margin: 0 0 12px;
                font-size: 16px;
                line-height: 1.4;
            }
            .seatreg-table-scroll {
                overflow-x: auto;
                margin: 16px 0 20px;
            }
            @media (max-width: 480px) {
                .seatreg-page {
                    padding: 20px 12px;
                }
                .seatreg-card__body {
                    padding: 24px 18px 26px;
                }
                .seatreg-card__title {
                    font-size: 20px;
                }
            }
        </style>
        <?php
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
