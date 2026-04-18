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

add_action('woocommerce_after_order_notes', 'agregar_campos_medidas_personalizadas_checkout', 10);
add_action('woocommerce_checkout_update_order_meta', 'guardar_medidas_personalizadas_checkout', 10);

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


// Añadir campos personalizados en el checkout
function agregar_campos_medidas_personalizadas_checkout($checkout) {
    $user_id = get_current_user_id();
    $radio_de_mama = get_field('radio_de_mama', 'user_' . $user_id);
    $contorno_busto = get_field('contorno_busto', 'user_' . $user_id);

    echo '<div id="medidas_personalizadas_checkout"><h3>' . __('Medidas Personalizadas') . '</h3>';

    woocommerce_form_field('radio_de_mama', array(
        'type' => 'number',
        'class' => array('form-row-wide'),
        'label' => __('Radio de mama (cm)'),
        'required' => true,
        'default' => $radio_de_mama,
    ), $checkout->get_value('radio_de_mama'));

    woocommerce_form_field('contorno_busto', array(
        'type' => 'number',
        'class' => array('form-row-wide'),
        'label' => __('Contorno de busto (cm)'),
        'required' => true,
        'default' => $contorno_busto,
    ), $checkout->get_value('contorno_busto'));

    echo '</div>';
}

// Guardar los campos personalizados en el pedido
function guardar_medidas_personalizadas_checkout($order_id) {
    if (!empty($_POST['radio_de_mama'])) {
        update_post_meta($order_id, '_radio_de_mama', sanitize_text_field($_POST['radio_de_mama']));
    }
    if (!empty($_POST['contorno_busto'])) {
        update_post_meta($order_id, '_contorno_busto', sanitize_text_field($_POST['contorno_busto']));
    }
}



// Mostrar y editar campos personalizados en la sección "Mi Cuenta"
function mostrar_y_editar_medidas_personalizadas_en_mi_cuenta() {
    $user_id = get_current_user_id();
    $radio_de_mama = get_field('radio_de_mama', 'user_' . $user_id);
    $contorno_busto = get_field('contorno_busto', 'user_' . $user_id);
	echo "<h3>Mis Medidas</h3>
		<form method='post'>".wp_nonce_field('guardar_medidas_personalizadas', 'medidas_personalizadas_nonce')."
			<table class='form-table'>
				<tr>
					<th><label for='busto'>Radio de mama (cm)</label></th>
					<td>
						<input type='number' name='radio_de_mama' id='radio_de_mama' value='". esc_attr($radio_de_mama) ."' class='regular-text' />
					</td>
				</tr>
				<tr>
					<th><label for='contorno_busto'>Contorno de busto (cm)</label></th>
					<td>
						<input type='number' name='contorno_busto' id='contorno_busto' value='". esc_attr($contorno_busto) ."' class='regular-text' />
					</td>
				</tr>
			</table>
			<p>
				<input type='submit' class='button' value='Guardar Medidas' />
			</p>
		</form>";
}


// Guardar los campos personalizados desde "Mi Cuenta"
function guardar_medidas_personalizadas_en_mi_cuenta() {
    if (isset($_POST['medidas_personalizadas_nonce']) && wp_verify_nonce($_POST['medidas_personalizadas_nonce'], 'guardar_medidas_personalizadas')) {
        $user_id = get_current_user_id();
        update_field('radio_de_mama', sanitize_text_field($_POST['radio_de_mama']), 'user_' . $user_id);
        update_field('contorno_busto', sanitize_text_field($_POST['contorno_busto']), 'user_' . $user_id);
    }
}


