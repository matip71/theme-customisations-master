<?php
/**
 * Functions.php — Orchestrator.
 *
 * Loads all feature modules and registers non-WooCommerce hooks.
 * Keep this file thin: no business logic, just wiring.
 *
 * @package  Theme_Customisations
 * @author   WooThemes
 * @since    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── Load modules (order matters: helpers first) ────────────────────
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/acf-fields.php';
require_once __DIR__ . '/class-tf-product-sizing.php';
require_once __DIR__ . '/account-measures.php';

// ── Boot WooCommerce sizing integration ────────────────────────────
new TF_Product_Sizing();

// ── Header icons (Storefront-specific, unrelated to measures) ──────
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
