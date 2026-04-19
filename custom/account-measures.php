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

    echo '<h3>Mis Medidas</h3>';
    echo '<form method="post">';
    echo wp_nonce_field( 'guardar_medidas_personalizadas', 'medidas_personalizadas_nonce', true, false );
    echo '<table class="form-table">';

    foreach ( $medidas as $key => $label ) {
        $valor = get_field( $key, 'user_' . $user_id );
        printf(
            '<tr>
                <th><label for="%1$s">%2$s</label></th>
                <td><input type="number" step="0.1" name="%1$s" id="%1$s" value="%3$s" class="regular-text" /></td>
            </tr>',
            esc_attr( $key ),
            esc_html( $label ),
            esc_attr( $valor )
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
