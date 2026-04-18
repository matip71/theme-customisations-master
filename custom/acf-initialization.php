<?php
/**
 * Inicialización y configuración dinámica de Advanced Custom Fields (ACF)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Función Helper para obtener dinámicamente las medidas requeridas.
 * Se alimenta del mismo grupo de ACF generado abajo.
 * Devuelve un array de clave (slug) => Nombre completo
 */
function tf_get_medidas_config() {
    $medidas = array();
    
    // Intenta obtener los campos del grupo de usuario dinámicamente
    if( function_exists('acf_get_fields') ) {
        $fields = acf_get_fields('group_usuario_medidas_custom');
        if ( !empty($fields) && is_array($fields) ) {
            foreach ( $fields as $field ) {
                $medidas[ $field['name'] ] = $field['label'];
            }
        }
    }
    
    // Fallback estático en caso de que ACF no haya cargado a tiempo o falle
    if ( empty($medidas) ) {
        $medidas = array(
            'radio_de_mama'  => 'Radio de mama (cm)',
            'contorno_busto' => 'Contorno de busto (cm)',
            'bajo_busto'     => 'Bajo busto (cm)',
            'cintura'        => 'Cintura (cm)',
            'cadera'         => 'Cadera (cm)',
            'tiro'           => 'Tiro (cm)',
            'largo'          => 'Largo (cm)',
        );
    }
    return $medidas;
}


/**
 * Añadir la configuración de ACF (Mapping) directamente en código.
 * De este modo no es necesario configurar los ACF a mano en el backend.
 */
add_action('acf/init', 'tf_register_acf_measure_fields');
function tf_register_acf_measure_fields() {

    if( function_exists('acf_add_local_field_group') ):

        // Opciones dinámicas para el Producto leyendo del mismo helper
        $medidas_disponibles = tf_get_medidas_config();

        // 1. Grupo en PRODUCTOS: Habilitar talle a medida y Medidas requeridas
        acf_add_local_field_group(array(
            'key' => 'group_producto_medidas_custom',
            'title' => 'Configuración de medidas del producto',
            'fields' => array(
                array(
                    'key' => 'field_habilitar_talle_a_medida',
                    'label' => 'Habilitar talle a medida',
                    'name' => 'habilitar_talle_a_medida',
                    'type' => 'true_false',
                    'instructions' => 'Habilita la opción de "A medida" en la ficha de producto.',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'message' => 'Sí, permitir compras a medida',
                    'default_value' => 0,
                    'ui' => 1,
                    'ui_on_text' => 'Activo',
                    'ui_off_text' => 'Inactivo',
                ),
                array(
                    'key' => 'field_medidas_requeridas_producto',
                    'label' => 'Medidas requeridas',
                    'name' => 'medidas_requeridas',
                    'type' => 'checkbox',
                    'instructions' => 'Selecciona las medidas que la clienta deberá completar.',
                    'required' => 0,
                    'conditional_logic' => array(
                        array(
                            array(
                                'field' => 'field_habilitar_talle_a_medida',
                                'operator' => '==',
                                'value' => '1',
                            ),
                        ),
                    ),
                    'choices' => $medidas_disponibles,
                    'allow_custom' => 0,
                    'default_value' => array(),
                    'layout' => 'vertical',
                    'toggle' => 1,
                    'return_format' => 'value',
                    'save_custom' => 0,
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'product',
                    ),
                ),
            ),
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => '',
            'active' => true,
            'description' => '',
        ));

        // 2. Grupo en USUARIOS: Medidas base guardadas en cuenta
        acf_add_local_field_group(array(
            'key' => 'group_usuario_medidas_custom',
            'title' => 'Medidas del cliente (Perfil)',
            'fields' => array(
                array(
                    'key' => 'field_user_radio_de_mama',
                    'label' => 'Radio de mama (cm)',
                    'name' => 'radio_de_mama',
                    'type' => 'number',
                    'append' => 'cm',
                ),
                array(
                    'key' => 'field_user_contorno_busto',
                    'label' => 'Contorno de busto (cm)',
                    'name' => 'contorno_busto',
                    'type' => 'number',
                    'append' => 'cm',
                ),
                array(
                    'key' => 'field_user_bajo_busto',
                    'label' => 'Bajo busto (cm)',
                    'name' => 'bajo_busto',
                    'type' => 'number',
                    'append' => 'cm',
                ),
                array(
                    'key' => 'field_user_cintura',
                    'label' => 'Cintura (cm)',
                    'name' => 'cintura',
                    'type' => 'number',
                    'append' => 'cm',
                ),
                array(
                    'key' => 'field_user_cadera',
                    'label' => 'Cadera (cm)',
                    'name' => 'cadera',
                    'type' => 'number',
                    'append' => 'cm',
                ),
                array(
                    'key' => 'field_user_tiro',
                    'label' => 'Tiro (cm)',
                    'name' => 'tiro',
                    'type' => 'number',
                    'append' => 'cm',
                ),
                array(
                    'key' => 'field_user_largo',
                    'label' => 'Largo (cm)',
                    'name' => 'largo',
                    'type' => 'number',
                    'append' => 'cm',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'user_form',
                        'operator' => '==',
                        'value' => 'all',
                    ),
                ),
            ),
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => '',
            'active' => true,
            'description' => '',
        ));

    endif;
}
