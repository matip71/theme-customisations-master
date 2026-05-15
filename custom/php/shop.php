<?php
/**
 * Shop features — Mobile Off-Canvas Filters and other shop UI tweaks.
 *
 * @package Theme_Customisations
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add the Mobile Filters toggle button before the shop loop.
 */
function tf_add_mobile_filters_toggle() {
    // Only show on shop or product taxonomy pages where filters are likely used
    if ( ! is_shop() && ! is_product_taxonomy() ) {
        return;
    }

    // Wrap in a container to hold the toggle button and the active filters clone
    echo '<div class="tf-mobile-filters-bar">';
    
    // The toggle button
    echo '<button type="button" id="tf-mobile-filters-toggle" class="button alt">';
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-sliders"><line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line><line x1="17" y1="16" x2="23" y2="16"></line></svg>';
    echo '<span>Filtros</span>';
    echo '</button>';

    // Container where active filters will be rendered via PHP
    echo '<div id="tf-mobile-active-filters" class="tf-mobile-active-filters">';
    if ( class_exists( 'WC_Widget_Layered_Nav_Filters' ) ) {
        the_widget( 'WC_Widget_Layered_Nav_Filters', array(
            'title' => '' // No title needed
        ), array(
            'before_widget' => '',
            'after_widget'  => '',
            'before_title'  => '',
            'after_title'   => '',
        ) );
    }
    echo '</div>';
    
    echo '</div>';
}
// Hook it just before the shop loop, priority 15 to be between sorting and products usually, or 10.
// In Storefront, woocommerce_before_shop_loop has:
// 10: woocommerce_result_count
// 20: woocommerce_catalog_ordering
// 30: storefront_sorting_wrapper
// Let's attach at 25 or 35 so it's inside or near the sorting wrapper. We'll use 25.
add_action( 'woocommerce_before_shop_loop', 'tf_add_mobile_filters_toggle', 25 );
