<?php
/**
 * Helper functions — Pure, reusable, repeated logic to comply with DRY.
 *
 * @package Theme_Customisations
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get the standard sizes from a WooCommerce product attribute.
 *
 * Looks for 'tf_talle' first, then 'talle'. Supports both
 * pipe (|) and comma (,) separators used by WooCommerce.
 *
 * @param  WC_Product $product
 * @return string[]   Array of trimmed, non-empty size labels.
 */
function tf_get_product_standard_sizes( $product ) {
    $raw = $product->get_attribute( 'tf_talle' );

    if ( empty( $raw ) ) {
        $raw = $product->get_attribute( 'talle' );
    }

    if ( empty( $raw ) ) {
        return array();
    }

    return array_values( array_filter( array_map( 'trim', preg_split( '/[|,]/', $raw ) ) ) );
}

/**
 * Convert a measure slug to a human-readable label.
 *
 * Example: 'bajo_busto' → 'Bajo Busto'
 *
 * @param  string $slug
 * @return string
 */
function tf_format_measure_label( $slug ) {
    return ucwords( str_replace( '_', ' ', $slug ) );
}

/**
 * Format a talle value for display to the customer.
 *
 * @param  string $talle Raw talle value (e.g. 'a_medida', 'XS').
 * @return string
 */
function tf_format_talle_display( $talle ) {
    return 'a_medida' === $talle ? 'A medida' : $talle;
}

/**
 * Check whether a cart item carries custom measures.
 *
 * @param  array $cart_item
 * @return bool
 */
function tf_has_custom_measures( $cart_item ) {
    return ! empty( $cart_item['tf_talle'] )
        && 'a_medida' === $cart_item['tf_talle']
        && ! empty( $cart_item['tf_medidas'] )
        && is_array( $cart_item['tf_medidas'] );
}

/**
 * Get pre-saved user measure defaults keyed by measure slug.
 *
 * @param  int $user_id  WordPress user ID (0 = guest).
 * @return array          slug => value (empty string when missing).
 */
function tf_get_user_defaults( $user_id ) {
    $defaults = array();
    $user_ref = $user_id ? 'user_' . $user_id : null;

    foreach ( array_keys( tf_get_medidas_config() ) as $key ) {
        $defaults[ $key ] = $user_ref ? (string) get_field( $key, $user_ref ) : '';
    }

    return $defaults;
}

/**
 * Determine whether a product should show the sizing selector at all.
 * Returns true when the product has standard sizes OR has the "a medida" ACF toggle enabled.
 *
 * @param  int $product_id
 * @return bool
 */
function tf_product_requires_sizing( $product_id ) {
    $product = wc_get_product( $product_id );
    if ( ! $product ) {
        return false;
    }

    $has_standard = ! empty( tf_get_product_standard_sizes( $product ) );
    $has_custom   = (bool) get_field( 'habilitar_talle_a_medida', $product_id );

    return $has_standard || $has_custom;
}

/**
 * Normalise a raw talle value coming from either our custom select
 * (value = "a_medida") or WooCommerce's variation attribute
 * (value = "A medida" for custom attrs, "a-medida" for taxonomy slugs).
 *
 * Standard sizes (S, M, L…) pass through unchanged.
 *
 * @param  string $value  Raw value from POST.
 * @return string         Normalised value.
 */
function tf_normalize_talle_value( $value ) {
    $check = strtolower( str_replace( array( ' ', '-' ), '_', trim( $value ) ) );
    return 'a_medida' === $check ? 'a_medida' : $value;
}

/**
 * Read the selected talle from the current POST request.
 *
 * Checks our custom field first, then falls back to WooCommerce's
 * variation attribute. Normalises "A medida" variants to "a_medida".
 *
 * @return string  The talle value, or empty string if none submitted.
 */
function tf_get_posted_talle() {
    // 1. Our custom select (non-variation products).
    if ( ! empty( $_POST['tf_talle'] ) ) {
        return sanitize_text_field( wp_unslash( $_POST['tf_talle'] ) );
    }

    // 2. WooCommerce variation attribute (when talle is a variation).
    if ( ! empty( $_POST['attribute_talle'] ) ) {
        return tf_normalize_talle_value(
            sanitize_text_field( wp_unslash( $_POST['attribute_talle'] ) )
        );
    }

    return '';
}
