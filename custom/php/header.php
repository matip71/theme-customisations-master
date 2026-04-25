<?php
/**
 * Header customisations.
 * Storefront-specific header icons and user dropdown.
 *
 * @package Theme_Customisations
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── Header icons (Storefront-specific) ─────────────────────────────
add_action( 'storefront_before_header', 'custom_header_icons', 10 );

function custom_header_icons() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    echo "<template id='added-account-icon'>
        <div class='custom-header-icons'>
            <!-- Ícono de usuario -->";

    if ( is_user_logged_in() ) {
        $current_user = wp_get_current_user();
        echo "<div class='dropdown'>";
        echo "<button class='dropdown-button'>" . esc_html( $current_user->display_name ) . "</button>";
        echo "<div class='dropdown-content'>";
        echo "<a class='my-account-name' href='" . esc_url( wc_get_page_permalink( 'myaccount' ) ) . "'>Mi cuenta</a>";
        echo "<a class='logout-icon' href='" . esc_url( wp_logout_url( home_url() ) ) . "'>Cerrar sesión</a>";
        echo "</div></div>";
    } else {
        echo "<a class='my-account-custom' href='" . esc_url( wc_get_page_permalink( 'myaccount' ) ) . "'></a>";
    }

    echo "</div></template>";
}
