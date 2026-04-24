<?php
/**
 * ACF Field Group Definitions.
 *
 * Single source of truth for measure field keys and ACF registration.
 * No business logic — only declarations.
 *
 * @package Theme_Customisations
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Canonical list of body measurement slugs and their labels.
 *
 * Used as the single source of truth for the ACF field registration
 * To add or rename a measure, change it ONLY here.
 */
define( 'TF_MEDIDAS_DEFAULT', array(
    'radio_de_mama'  => 'Radio de mama (cm)',
    'contorno_busto' => 'Contorno de busto (cm)',
    'bajo_busto'     => 'Bajo busto (cm)',
    'cintura'        => 'Cintura (cm)',
    'cadera'         => 'Cadera (cm)',
    'tiro'           => 'Tiro (cm)',
    'largo'          => 'Largo (cm)',
) );

/**
 * Return the canonical map of measure slugs → labels.
 *
 * @return array  slug => label
 */
function tf_get_medidas_config() {
    return TF_MEDIDAS_DEFAULT;
}

/**
 * Register ACF field groups via code so they travel with the plugin.
 */
add_action( 'acf/init', 'tf_register_acf_measure_fields' );
function tf_register_acf_measure_fields() {

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    $medidas_disponibles = tf_get_medidas_config();

    // ── Product group ──────────────────────────────────────────────
    acf_add_local_field_group( array(
        'key'    => 'group_producto_medidas_custom',
        'title'  => 'Configuración de medidas del producto',
        'fields' => array(
            array(
                'key'           => 'field_habilitar_talle_a_medida',
                'label'         => 'Habilitar talle a medida',
                'name'          => 'habilitar_talle_a_medida',
                'type'          => 'true_false',
                'instructions'  => 'Habilita la opción de "A medida" en la ficha de producto.',
                'message'       => 'Sí, permitir compras a medida',
                'default_value' => 0,
                'ui'            => 1,
                'ui_on_text'    => 'Activo',
                'ui_off_text'   => 'Inactivo',
            ),
            array(
                'key'               => 'field_medidas_requeridas_producto',
                'label'             => 'Medidas requeridas',
                'name'              => 'medidas_requeridas',
                'type'              => 'checkbox',
                'instructions'      => 'Selecciona las medidas que la clienta deberá completar.',
                'conditional_logic' => array( array( array(
                    'field'    => 'field_habilitar_talle_a_medida',
                    'operator' => '==',
                    'value'    => '1',
                ) ) ),
                'choices'       => $medidas_disponibles,
                'default_value' => array(),
                'layout'        => 'vertical',
                'toggle'        => 1,
                'return_format' => 'value',
            ),
        ),
        'location' => array( array( array(
            'param'    => 'post_type',
            'operator' => '==',
            'value'    => 'product',
        ) ) ),
        'position' => 'normal',
        'active'   => true,
    ) );

    // ── User group ─────────────────────────────────────────────────
    $fields_array = array();
    foreach ($medidas_disponibles as $name => $label) {
        $fields_array[] = array(
            'key'    => 'field_user_' . $name,
            'label'  => $label,
            'name'   => $name,
            'type'   => 'number',
            'append' => 'cm',
        );
    }

    acf_add_local_field_group( array(
        'key'    => 'group_usuario_medidas_custom',
        'title'  => 'Medidas del cliente (Perfil)',
        'fields' => $fields_array,
        'location' => array( array( array(
            'param'    => 'user_form',
            'operator' => '==',
            'value'    => 'all',
        ) ) ),
        'position' => 'normal',
        'active'   => true,
    ) );
}
