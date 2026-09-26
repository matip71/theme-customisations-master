<?php
/**
 * Account Measures — "Mi Cuenta" form for user-level measure defaults.
 *
 * Reads dynamically from tf_get_medidas_config() so adding a field in
 * acf-fields.php automatically propagates here.
 *
 * @package Theme_Customisations
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'woocommerce_account_dashboard', 'tf_render_account_measures_form' );
add_action( 'init', 'tf_save_account_measures' );

/**
 * Render the editable measures table on the My Account dashboard.
 */
function tf_render_account_measures_form() {
    $user_id = get_current_user_id();
    $medidas = tf_get_medidas_config();

    echo '<h4>Mis Medidas</h4>';
    echo '<form method="post">';
    echo wp_nonce_field( 'guardar_medidas_personalizadas', 'medidas_personalizadas_nonce', true, false );
    echo '<table class="form-table">';

    foreach ( $medidas as $key => $label ) {
        $valor     = get_field( $key, 'user_' . $user_id );
        $image_url = tf_get_medida_image( $key );

        $tooltip_html = '';
        if ( $image_url ) {
            $tooltip_html = sprintf(
                '<span class="tf-measure-help">
                    <span class="tf-measure-tooltip-trigger">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                    </span>
                    <span class="tf-measure-tooltip-bubble">
                        <img src="%s" alt="%s" />
                    </span>
                </span>',
                esc_url( $image_url ),
                esc_attr( $label )
            );
        }

        printf(
            '<tr>
                <th><label for="%1$s">%2$s%4$s</label></th>
                <td><input type="number" step="0.1" name="%1$s" id="%1$s" value="%3$s" class="regular-text" /></td>
            </tr>',
            esc_attr( $key ),
            esc_html( $label ),
            esc_attr( $valor ),
            $tooltip_html
        );
    }

    echo '</table>';
    echo '<p><input type="submit" class="button" value="Guardar Medidas" /></p>';
    echo '</form>';
}

/**
 * Persist measure values submitted from the My Account form.
 */
function tf_save_account_measures() {
    if ( ! isset( $_POST['medidas_personalizadas_nonce'] ) ) {
        return;
    }

    if ( ! wp_verify_nonce( $_POST['medidas_personalizadas_nonce'], 'guardar_medidas_personalizadas' ) ) {
        return;
    }

    $user_id = get_current_user_id();

    foreach ( array_keys( tf_get_medidas_config() ) as $key ) {
        if ( isset( $_POST[ $key ] ) ) {
            update_field( $key, sanitize_text_field( $_POST[ $key ] ), 'user_' . $user_id );
        }
    }
}
