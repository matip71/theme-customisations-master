<?php
/**
 * Talle a medida WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * A. Mostrar selector de talle + campos de medidas
 */
add_action( 'woocommerce_before_add_to_cart_button', 'tf_talle_a_medida_fields' );
function tf_talle_a_medida_fields() {
    if ( ! is_product() ) return;

    global $product;
    $product_id = $product->get_id();

    // Verificar si el producto soporta talle a medida a través de ACF
    $habilitado = get_field( 'habilitar_talle_a_medida', $product_id );

    // Obtener los talles estándar del producto (Atributo global pa_talle o atributo local talle)
    $talle_attr = $product->get_attribute( 'tf_talle' );
    if ( empty($talle_attr) ) {
        $talle_attr = $product->get_attribute( 'talle' );
    }

    $talles_estandar = array();
    if ( ! empty($talle_attr) ) {
        $talles_estandar = array_filter( array_map( 'trim', preg_split('/[|,]/', $talle_attr) ) );
    }

    // Si no tiene talles estándar ni está habilitado 'a medida', no mostramos el selector
    if ( empty($talles_estandar) && ! $habilitado ) {
        return;
    }

    // Obtener las medidas requeridas para este producto (ej. Array de checkboxes)
    $medidas_requeridas = get_field( 'medidas_requeridas', $product_id );
    if ( empty( $medidas_requeridas ) || ! is_array( $medidas_requeridas ) ) {
        $medidas_requeridas = [];
    }

    $user_id = get_current_user_id();
    $user_ref = $user_id ? 'user_' . $user_id : null;

    // Obtener las medidas guardadas en el perfil para precargar
    $defaults = [];
    $medidas_keys = array_keys(tf_get_medidas_config());
    
    foreach( $medidas_keys as $key ) {
        $defaults[$key] = $user_ref ? get_field( $key, $user_ref ) : '';
    }

    ?>
    <div class="tf-custom-sizing" style="margin-bottom: 2em;">
        <p class="form-row form-row-wide">
            <label for="tf_talle"><strong><?php esc_html_e('Talle', 'woocommerce'); ?> <abbr class="required" title="obligatorio">*</abbr></strong></label>
            <select name="tf_talle" id="tf_talle" class="select" required style="width:100%;">
                <option value=""><?php esc_html_e('Seleccionar', 'woocommerce'); ?></option>
                <?php foreach ( $talles_estandar as $tal ) : ?>
                    <option value="<?php echo esc_attr( $tal ); ?>"><?php echo esc_html( $tal ); ?></option>
                <?php endforeach; ?>
                <?php if ( $habilitado ) : ?>
                    <option value="a_medida"><?php esc_html_e('A medida', 'woocommerce'); ?></option>
                <?php endif; ?>
            </select>
        </p>

        <div id="tf_medidas_wrap" style="display:none; margin-top:20px; padding: 15px; border: 1px solid #e5e5e5; border-radius: 4px; background: #fafafa;">
            <p><strong><?php esc_html_e('Completá tus medidas (cm)', 'woocommerce'); ?></strong></p>

            <?php foreach ( $medidas_requeridas as $campo ) : 
                $value = isset( $defaults[$campo] ) ? $defaults[$campo] : '';
                $label = ucwords( str_replace('_', ' ', $campo ) );
                ?>
                <p class="form-row form-row-wide">
                    <label for="tf_medida_<?php echo esc_attr($campo); ?>">
                        <?php echo esc_html( $label ); ?> <abbr class="required" title="obligatorio">*</abbr>
                    </label>
                    <input
                        type="number"
                        step="0.1"
                        class="input-text"
                        name="tf_medidas[<?php echo esc_attr($campo); ?>]"
                        id="tf_medida_<?php echo esc_attr($campo); ?>"
                        value="<?php echo esc_attr( $value ); ?>"
                        style="width:100%;"
                    />
                </p>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const talle = document.getElementById('tf_talle');
        const wrap = document.getElementById('tf_medidas_wrap');

        if(talle && wrap) {
            function toggleMedidas() {
                if (talle.value === 'a_medida') {
                    wrap.style.display = 'block';
                    // Hacer que los campos de medida sean obligatorios
                    const inputs = wrap.querySelectorAll('input');
                    inputs.forEach(i => i.setAttribute('required', 'required'));
                } else {
                    wrap.style.display = 'none';
                    // Remover obligatoriedad
                    const inputs = wrap.querySelectorAll('input');
                    inputs.forEach(i => i.removeAttribute('required'));
                }
            }

            talle.addEventListener('change', toggleMedidas);
            toggleMedidas();
        }
    });
    </script>
    <?php
}

/**
 * B. Validar antes de agregar al carrito
 */
add_filter( 'woocommerce_add_to_cart_validation', 'tf_validar_talle_a_medida', 10, 3 );
function tf_validar_talle_a_medida( $passed, $product_id, $quantity ) {
    $habilitado = get_field( 'habilitar_talle_a_medida', $product_id );

    // Para saber si se debió imprimir el selector o no
    $product = wc_get_product($product_id);
    $talle_attr = $product->get_attribute( 'tf_talle' );
    if ( empty($talle_attr) ) {
        $talle_attr = $product->get_attribute( 'talle' );
    }
    $talles_estandar = array_filter( array_map( 'trim', preg_split('/[|,]/', $talle_attr) ) );

    // Si el producto no requiere talle en absoluto, saltar validación
    if ( empty($talles_estandar) && ! $habilitado ) {
        return $passed;
    }

    $talle = isset($_POST['tf_talle']) ? sanitize_text_field($_POST['tf_talle']) : '';
    $medidas_requeridas = get_field( 'medidas_requeridas', $product_id );

    if ( empty($talle) ) {
        wc_add_notice( 'Por favor seleccioná un talle para tu producto.', 'error' );
        return false;
    }

    if ( $talle === 'a_medida' && ! empty($medidas_requeridas) ) {
        foreach ( $medidas_requeridas as $campo ) {
            $valor = isset($_POST['tf_medidas'][$campo]) ? trim(wp_unslash($_POST['tf_medidas'][$campo])) : '';
            if ( $valor === '' ) {
                wc_add_notice( 'Completá la medida obligatoria: ' . ucwords(str_replace('_', ' ', $campo)), 'error' );
                return false;
            }
        }
    }

    return $passed;
}

/**
 * C. Guardar la configuración en carrito temporal (SESSION)
 */
add_filter( 'woocommerce_add_cart_item_data', 'tf_guardar_datos_talle_a_medida', 10, 2 );
function tf_guardar_datos_talle_a_medida( $cart_item_data, $product_id ) {
    if ( isset($_POST['tf_talle']) ) {
        $cart_item_data['tf_talle'] = sanitize_text_field( wp_unslash($_POST['tf_talle']) );
    }

    if ( isset($_POST['tf_medidas']) && is_array($_POST['tf_medidas']) ) {
        $medidas = [];
        foreach ( $_POST['tf_medidas'] as $key => $value ) {
            $medidas[ sanitize_key($key) ] = sanitize_text_field( wp_unslash($value) );
        }
        $cart_item_data['tf_medidas'] = $medidas;
    }

    // Identificador único para el line-item
    if ( !empty($_POST['tf_talle']) && $_POST['tf_talle'] === 'a_medida' ) {
        $cart_item_data['tf_unique_key'] = md5( microtime().rand() ); // Para que las medidas distintas no se fusionen
    } else if (!empty($_POST['tf_talle'])) {
        $cart_item_data['tf_unique_key'] = md5( sanitize_text_field( wp_unslash($_POST['tf_talle']) ) ); // Para separar XS de M por ejemplo
    }

    return $cart_item_data;
}

/**
 * D. Mostrar los metadatos en carrito y checkout al comprador
 */
add_filter( 'woocommerce_get_item_data', 'tf_mostrar_datos_en_carrito', 10, 2 );
function tf_mostrar_datos_en_carrito( $item_data, $cart_item ) {
    if ( ! empty( $cart_item['tf_talle'] ) ) {
        $item_data[] = [
            'key'   => 'Talle',
            'value' => $cart_item['tf_talle'] === 'a_medida' ? 'A medida' : $cart_item['tf_talle'],
        ];
    }

    if ( ! empty( $cart_item['tf_medidas'] ) && is_array( $cart_item['tf_medidas'] ) && isset($cart_item['tf_talle']) && $cart_item['tf_talle'] === 'a_medida' ) {
        foreach ( $cart_item['tf_medidas'] as $key => $value ) {
            if($value !== '') {
                $item_data[] = [
                    'key'   => ucwords( str_replace('_', ' ', $key ) ),
                    'value' => $value . ' cm',
                ];
            }
        }
    }

    return $item_data;
}

/**
 * E. Transferencia de carrito al pedido (Orden final admins)
 */
add_action( 'woocommerce_checkout_create_order_line_item', 'tf_guardar_meta_en_pedido', 10, 4 );
function tf_guardar_meta_en_pedido( $item, $cart_item_key, $values, $order ) {
    if ( ! empty( $values['tf_talle'] ) ) {
        $item->add_meta_data(
            'Talle',
            $values['tf_talle'] === 'a_medida' ? 'A medida' : $values['tf_talle'],
            true
        );
    }

    if ( ! empty( $values['tf_medidas'] ) && is_array( $values['tf_medidas'] ) && isset($values['tf_talle']) && $values['tf_talle'] === 'a_medida' ) {
        foreach ( $values['tf_medidas'] as $key => $value ) {
             if($value !== '') {
                $item->add_meta_data(
                    ucwords( str_replace('_', ' ', $key ) ),
                    $value . ' cm',
                    true
                );
             }
        }
    }
}


