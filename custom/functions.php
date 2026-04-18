<?php
/**
 * Functions.php
 *
 * @package  Theme_Customisations
 * @author   WooThemes
 * @since    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * functions.php
 * Add PHP snippets here
 */

// Agregar un mensaje personalizado en el header
//add_action( 'storefront_header_cart', 'mi_mensaje_personalizado', 20 );
//function mi_mensaje_personalizado() {
//    echo '<div class="mensaje-header">¡Bienvenido a mi tiendita!</div>';
//}

add_action('storefront_before_header', 'custom_header_icons', 10 );

require_once dirname( __FILE__ ) . '/acf-initialization.php';
require_once dirname( __FILE__ ) . '/custom-measures.php';

add_action('woocommerce_account_dashboard', 'mostrar_y_editar_medidas_personalizadas_en_mi_cuenta');
add_action('init', 'guardar_medidas_personalizadas_en_mi_cuenta');


//Adds account button on the top menu, with login log out functionalitites
function custom_header_icons() {
    if ( class_exists( 'WooCommerce' ) ) {
        echo "<template id='added-account-icon'>
			<div class='custom-header-icons'>
            <!-- Ícono de usuario -->";
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			echo "<div class='dropdown'>";
			echo "<button class='dropdown-button'>".esc_html( $current_user->display_name )."</button>";
			echo "<div class='dropdown-content'>";
			echo "<a class='my-account-name' href='" . esc_url( wc_get_page_permalink("myaccount")) . "'>Mi cuenta</a>";
			echo "<a class='logout-icon' href='".esc_url( wp_logout_url( home_url() ) )."'>Cerrar sesión</a>";
			echo "</div></div>";
		} else {
			echo "<a class='my-account-custom' href='" . esc_url( wc_get_page_permalink("myaccount")) . "'></a>";
		}
        echo "</div></template>";
    }
}




// Mostrar y editar campos personalizados en la sección "Mi Cuenta"
function mostrar_y_editar_medidas_personalizadas_en_mi_cuenta() {
    $user_id = get_current_user_id();
    
    // Todas las medidas posibles dinámicas
    $medidas = tf_get_medidas_config();

    echo "<h3>Mis Medidas</h3>
		<form method='post'>".wp_nonce_field('guardar_medidas_personalizadas', 'medidas_personalizadas_nonce', true, false)."
			<table class='form-table'>";
			
    foreach ($medidas as $key => $label) {
        $valor = get_field($key, 'user_' . $user_id);
        echo "<tr>
                <th><label for='" . esc_attr($key) . "'>" . esc_html($label) . "</label></th>
                <td>
                    <input type='number' step='0.1' name='" . esc_attr($key) . "' id='" . esc_attr($key) . "' value='". esc_attr($valor) ."' class='regular-text' />
                </td>
            </tr>";
    }

	echo "	</table>
			<p>
				<input type='submit' class='button' value='Guardar Medidas' />
			</p>
		</form>";
}


// Guardar los campos personalizados desde "Mi Cuenta"
function guardar_medidas_personalizadas_en_mi_cuenta() {
    if (isset($_POST['medidas_personalizadas_nonce']) && wp_verify_nonce($_POST['medidas_personalizadas_nonce'], 'guardar_medidas_personalizadas')) {
        $user_id = get_current_user_id();
        
        $medidas_keys = array_keys(tf_get_medidas_config());
        foreach ($medidas_keys as $key) {
            if (isset($_POST[$key])) {
                update_field($key, sanitize_text_field($_POST[$key]), 'user_' . $user_id);
            }
        }
    }
}
