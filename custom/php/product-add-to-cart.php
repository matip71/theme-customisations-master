<?php
/**
 * Product add-to-cart — PRG redirect fix.
 *
 * Prevents duplicate cart additions on browser refresh by implementing
 * the Post/Redirect/Get pattern.
 *
 * @package Theme_Customisations
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WooCommerce fires this filter right after a product is added to the
 * cart from a single-product page. Returning the product permalink
 * turns the POST response into a 302 redirect (GET), which means
 * hitting "refresh" in the browser will only reload the GET — no
 * duplicate addition.
 *
 * @param  string $url  The default redirect URL (usually empty for non-AJAX).
 * @return string       The product permalink to redirect to.
 */
add_filter( 'woocommerce_add_to_cart_redirect', 'tf_prg_redirect_after_add_to_cart' );

function tf_prg_redirect_after_add_to_cart( $url ) {
    // Only act on single-product add-to-cart submissions.
    if ( isset( $_POST['add-to-cart'] ) && is_numeric( $_POST['add-to-cart'] ) ) {
        $product_id = absint( $_POST['add-to-cart'] );
        $product    = wc_get_product( $product_id );

        if ( $product ) {
            return $product->get_permalink();
        }
    }

    return $url;
}
