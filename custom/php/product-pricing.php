<?php
/**
 * Product pricing customisations.
 *
 * @package Theme_Customisations
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── Variable product: show min price instead of range ──────────────
add_filter( 'woocommerce_variable_sale_price_html', 'tf_variable_price_show_min', 10, 2 );
add_filter( 'woocommerce_variable_price_html',      'tf_variable_price_show_min', 10, 2 );

/**
 * Replace the default product variable price ej: "$690 – $780" range with just the minimum price.
 * JS in custom.js will update this container when a variation is selected.
 */
function tf_variable_price_show_min( $price_html, $product ) {
    $prices = $product->get_variation_prices( true );

    if ( empty( $prices['price'] ) ) {
        return $price_html;
    }

    $min_price = current( $prices['price'] );

    return wc_price( $min_price );
}
