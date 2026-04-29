<?php
/**
 * Product Sizing — WooCommerce integration.
 *
 * Handles: rendering, validation, cart storage, cart display, and order persistence
 * for the "Talle" selector and custom measures on products.
 *
 * Single Responsibility: this class owns the product→cart→order data flow.
 * Every public method maps 1:1 to a WooCommerce hook.
 *
 * @package Theme_Customisations
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TF_Product_Sizing {

    /**
     * Wire up all WooCommerce hooks in one auditable place.
     */
    public function __construct() {
        add_action( 'woocommerce_before_add_to_cart_button',       array( $this, 'render_sizing_fields' ) );
        add_filter( 'woocommerce_add_to_cart_validation',          array( $this, 'validate' ), 10, 3 );
        add_filter( 'woocommerce_add_cart_item_data',              array( $this, 'save_to_cart' ), 10, 2 );
        add_filter( 'woocommerce_get_item_data',                   array( $this, 'display_in_cart' ), 10, 2 );
        add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'save_to_order' ), 10, 4 );
    }

    // ─── A. Render ──────────────────────────────────────────────────

    /**
     * Output the 'talle' selector and conditional measure inputs on the
     * single-product page, right before the "Add to cart" button.
     */
    public function render_sizing_fields() {
        if ( ! is_product() ) {
            return;
        }

        global $product;
        $product_id = $product->get_id();

        $habilitado          = (bool) get_field( 'habilitar_talle_a_medida', $product_id );
        $talles_estandar     = tf_get_product_standard_sizes( $product );
        $talle_is_variation  = $this->is_talle_variation_attribute( $product );

        // When talle is a variation attribute WooCommerce already renders
        // its own select. If "A medida" is not enabled either, there is
        // nothing extra for us to render.
        if ( $talle_is_variation && ! $habilitado ) {
            return;
        }

        // Non-variation product with no sizes and no custom sizing.
        if ( ! $talle_is_variation && empty( $talles_estandar ) && ! $habilitado ) {
            return;
        }

        $medidas_requeridas = (array) get_field( 'medidas_requeridas', $product_id );
        $defaults           = tf_get_user_defaults( get_current_user_id() );

        ?>
        <div class="tf-custom-sizing">
            <?php if ( ! $talle_is_variation ) : ?>
            <p class="form-row form-row-wide">
                <label for="tf_talle">
                    <?php esc_html_e( 'Talle', 'woocommerce' ); ?>
                </label>
                <select name="tf_talle" id="tf_talle" class="select" required>
                    <option value=""><?php esc_html_e( 'Seleccionar', 'woocommerce' ); ?></option>
                    <?php foreach ( $talles_estandar as $tal ) : ?>
                        <option value="<?php echo esc_attr( $tal ); ?>"><?php echo esc_html( $tal ); ?></option>
                    <?php endforeach; ?>
                    <?php if ( $habilitado ) : ?>
                        <option value="a_medida"><?php esc_html_e( 'A medida', 'woocommerce' ); ?></option>
                    <?php endif; ?>
                </select>
            </p>
            <?php endif; ?>

            <?php if ( $habilitado && ! empty( $medidas_requeridas ) ) : ?>
            <div id="tf_medidas_wrap" class="tf-medidas-wrap">
                <p><strong><?php esc_html_e( 'Completá tus medidas (cm)', 'woocommerce' ); ?></strong></p>

                <?php foreach ( $medidas_requeridas as $campo ) :
                    $value = isset( $defaults[ $campo ] ) ? $defaults[ $campo ] : '';
                    ?>
                    <p class="form-row form-row-wide">
                        <label for="tf_medida_<?php echo esc_attr( $campo ); ?>">
                            <?php echo esc_html( tf_format_measure_label( $campo ) ); ?>
                        </label>
                        <input
                            type="number"
                            step="0.1"
                            class="input-text"
                            name="tf_medidas[<?php echo esc_attr( $campo ); ?>]"
                            id="tf_medida_<?php echo esc_attr( $campo ); ?>"
                            value="<?php echo esc_attr( $value ); ?>"
                        />
                    </p>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    // ─── B. Validate ────────────────────────────────────────────────

    /**
     * Reject add-to-cart when required fields are missing.
     *
     * @param  bool $passed
     * @param  int  $product_id
     * @param  int  $quantity
     * @return bool
     */
    public function validate( $passed, $product_id, $quantity ) {
        if ( ! tf_product_requires_sizing( $product_id ) ) {
            return $passed;
        }

        $talle = tf_get_posted_talle();

        if ( '' === $talle ) {
            wc_add_notice( 'Por favor seleccioná un talle para tu producto.', 'error' );
            return false;
        }

        if ( 'a_medida' === $talle ) {
            $medidas_requeridas = (array) get_field( 'medidas_requeridas', $product_id );

            foreach ( $medidas_requeridas as $campo ) {
                $valor = isset( $_POST['tf_medidas'][ $campo ] )
                    ? trim( wp_unslash( $_POST['tf_medidas'][ $campo ] ) )
                    : '';

                if ( '' === $valor ) {
                    wc_add_notice(
                        'Completá la medida obligatoria: ' . tf_format_measure_label( $campo ),
                        'error'
                    );
                    return false;
                }
            }
        }

        return $passed;
    }

    // ─── C. Save to cart ────────────────────────────────────────────

    /**
     * Attach talle + measures to the cart item data.
     *
     * @param  array $cart_item_data
     * @param  int   $product_id
     * @return array
     */
    public function save_to_cart( $cart_item_data, $product_id ) {
        $talle = tf_get_posted_talle();

        if ( '' === $talle ) {
            return $cart_item_data;
        }

        $cart_item_data['tf_talle'] = $talle;

        if ( 'a_medida' === $talle && isset( $_POST['tf_medidas'] ) && is_array( $_POST['tf_medidas'] ) ) {
            $medidas = array();
            foreach ( $_POST['tf_medidas'] as $key => $value ) {
                $medidas[ sanitize_key( $key ) ] = sanitize_text_field( wp_unslash( $value ) );
            }
            $cart_item_data['tf_medidas'] = $medidas;

            // Unique key so WooCommerce won't merge line-items with different measures
            $cart_item_data['tf_unique_key'] = md5( microtime() . wp_rand() );
        }

        return $cart_item_data;
    }

    // ─── D. Display in cart / checkout ──────────────────────────────

    /**
     * Show talle and measure details under the product name in cart.
     *
     * @param  array $item_data
     * @param  array $cart_item
     * @return array
     */
    public function display_in_cart( $item_data, $cart_item ) {
        if ( empty( $cart_item['tf_talle'] ) ) {
            return $item_data;
        }

        $item_data[] = array(
            'key'   => 'Talle',
            'value' => tf_format_talle_display( $cart_item['tf_talle'] ),
        );

        if ( tf_has_custom_measures( $cart_item ) ) {
            foreach ( $cart_item['tf_medidas'] as $key => $value ) {
                if ( '' !== $value ) {
                    $item_data[] = array(
                        'key'   => tf_format_measure_label( $key ),
                        'value' => $value . ' cm',
                    );
                }
            }
        }

        return $item_data;
    }

    // ─── E. Persist to order ────────────────────────────────────────

    /**
     * Copy sizing data from cart item into the order line-item meta.
     * This is what production staff sees in WP Admin.
     *
     * @param  WC_Order_Item_Product $item
     * @param  string                $cart_item_key
     * @param  array                 $values
     * @param  WC_Order              $order
     */
    public function save_to_order( $item, $cart_item_key, $values, $order ) {
        if ( empty( $values['tf_talle'] ) ) {
            return;
        }

        $item->add_meta_data( 'Talle', tf_format_talle_display( $values['tf_talle'] ), true );

        if ( tf_has_custom_measures( $values ) ) {
            foreach ( $values['tf_medidas'] as $key => $value ) {
                if ( '' !== $value ) {
                    $item->add_meta_data( tf_format_measure_label( $key ), $value . ' cm', true );
                }
            }
        }
    }

    // ─── F. Helpers ─────────────────────────────────────────────────

    /**
     * Check whether "talle" is used as a WooCommerce variation attribute.
     *
     * @param  WC_Product $product
     * @return bool
     */
    private function is_talle_variation_attribute( $product ) {
        if ( ! $product->is_type( 'variable' ) ) {
            return false;
        }

        $variation_attributes = $product->get_variation_attributes();

        foreach ( array_keys( $variation_attributes ) as $attr_name ) {
            $normalised = strtolower( sanitize_title( $attr_name ) );
            if ( 'talle' === $normalised || 'pa_talle' === $normalised ) {
                return true;
            }
        }

        return false;
    }
}
