<?php
defined( 'ABSPATH' ) || exit;

class BetterSOMM_Shortcode {

    public static function init() {
        add_shortcode( 'bettersomm', array( __CLASS__, 'render' ) );
    }

    /**
     * [bettersomm] shortcode.
     *
     * Supported attributes (all optional — fall back to admin settings):
     *   title        Widget heading text
     *   locale       "es" or "en"
     *   accent       Hex color e.g. #8B1A1A
     *   catalog      Override product feed URL
     *   placeholder  Search input placeholder text
     */
    public static function render( $atts ) {
        $opts = BetterSOMM_Admin::get_opts();

        // Merge shortcode atts over defaults from settings
        $atts = shortcode_atts(
            array(
                'title'       => $opts['widget_title'] ?? '',
                'locale'      => $opts['locale'] ?? 'es',
                'accent'      => $opts['accent_color'] ?? '#6b2737',
                'catalog'     => $opts['catalog_url'] ?? '',
                'placeholder' => '',
                'c7tenant'    => $opts['c7_tenant'] ?? '',
            ),
            $atts,
            'bettersomm'
        );

        $license    = $opts['license_key'] ?? '';
        $worker_url = $opts['worker_url'] ?? '';

        // Bail early with a helpful message if not configured (only visible to admins)
        if ( empty( $license ) || empty( $worker_url ) || empty( $atts['catalog'] ) ) {
            if ( current_user_can( 'manage_options' ) ) {
                $url = admin_url( 'options-general.php?page=bettersomm' );
                return sprintf(
                    '<p style="padding:16px;background:#fff3cd;border:1px solid #ffc107;border-radius:6px;">' .
                    '🍷 <strong>BetterSOMM:</strong> <a href="%s">Complete the setup</a> to display the wine finder.' .
                    '</p>',
                    esc_url( $url )
                );
            }
            return '';
        }

        // Enqueue the widget script
        self::enqueue_widget( $opts );

        // Build suggestions JSON from settings (newline-separated)
        $raw_suggestions = $opts['suggestions'] ?? '';
        $suggestions_arr = array_values( array_filter( array_map( 'trim', explode( "\n", $raw_suggestions ) ) ) );
        $suggestions_json = ! empty( $suggestions_arr ) ? wp_json_encode( $suggestions_arr ) : '';

        // Build data attributes — values are escaped for HTML attribute context
        $data = array(
            'data-bettersomm'  => '',
            'data-worker'      => esc_url( $worker_url ),
            'data-license'     => esc_attr( $license ),
            'data-catalog'     => esc_url( $atts['catalog'] ),
            'data-locale'      => esc_attr( $atts['locale'] ),
        );

        if ( ! empty( $atts['title'] ) ) {
            $data['data-title'] = esc_attr( $atts['title'] );
        }
        if ( ! empty( $atts['accent'] ) ) {
            $data['data-accent-color'] = esc_attr( $atts['accent'] );
        }
        if ( ! empty( $atts['placeholder'] ) ) {
            $data['data-placeholder'] = esc_attr( $atts['placeholder'] );
        }
        if ( $suggestions_json ) {
            $data['data-suggestions'] = esc_attr( $suggestions_json );
        }
        if ( ! empty( $atts['c7tenant'] ) ) {
            $data['data-c7-tenant'] = esc_attr( $atts['c7tenant'] );
        }

        $attr_string = '';
        foreach ( $data as $key => $value ) {
            if ( $value === '' && $key === 'data-bettersomm' ) {
                $attr_string .= ' ' . $key;
            } else {
                $attr_string .= sprintf( ' %s="%s"', $key, $value );
            }
        }

        return sprintf( '<div%s></div>', $attr_string );
    }

    private static function enqueue_widget( array $opts ) {
        if ( wp_script_is( 'bettersomm-widget', 'enqueued' ) ) {
            return;
        }

        // Use custom CDN URL if set, otherwise fall back to bundled file
        $cdn = $opts['cdn_url'] ?? '';
        if ( $cdn ) {
            $src = esc_url( $cdn );
        } else {
            $src = BETTERSOMM_URL . 'assets/bettersomm.min.js';
        }

        wp_enqueue_script(
            'bettersomm-widget',
            $src,
            array(),           // no WP dependencies
            BETTERSOMM_VERSION,
            array(
                'strategy'  => 'defer',
                'in_footer' => true,
            )
        );
    }
}
