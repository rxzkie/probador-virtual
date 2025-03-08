<?php
/**
 * Plugin Name: Integración Probador Virtual con WooCommerce
 * Description: Integra el probador virtual de Sartoria Cielo Milano con WooCommerce
 * Version: 1.0
 * Author: Soporte Técnico
 */

// Aseguramos que no se acceda directamente
if (!defined('ABSPATH')) {
    exit;
}

class Probador_Virtual_Integration {
    
    public function __construct() {
        // Agregar scripts y estilos necesarios
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Crear endpoint AJAX para manejar la acción de añadir al carrito
        add_action('wp_ajax_add_outfit_to_cart', array($this, 'add_outfit_to_cart'));
        add_action('wp_ajax_nopriv_add_outfit_to_cart', array($this, 'add_outfit_to_cart'));
        
        // Agregar shortcode para el botón de añadir al carrito en el probador
        add_shortcode('probador_add_to_cart', array($this, 'add_to_cart_button_shortcode'));
    }
    
    /**
     * Cargar scripts y estilos necesarios
     */
    public function enqueue_scripts() {
        // Solo cargar en la página del probador
        if (is_page('probador-virtual') || strpos($_SERVER['REQUEST_URI'], 'probador-virtual') !== false) {
            wp_enqueue_script(
                'probador-woocommerce-integration',
                plugin_dir_url(__FILE__) . 'js/probador-integration.js',
                array('jquery'),
                '1.0',
                true
            );
            
            // Pasar variables al script
            wp_localize_script(
                'probador-woocommerce-integration',
                'probador_integration',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('probador_integration_nonce')
                )
            );
            
            wp_enqueue_style(
                'probador-woocommerce-style',
                plugin_dir_url(__FILE__) . 'css/probador-integration.css',
                array(),
                '1.0'
            );
        }
    }
    
    /**
     * Función para añadir el outfit completo al carrito
     */
    public function add_outfit_to_cart() {
        // Verificar nonce para seguridad
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'probador_integration_nonce')) {
            wp_send_json_error(array('message' => 'Error de seguridad. Intente nuevamente.'));
            exit;
        }
        
        // Comprobar que tenemos datos de prendas
        if (!isset($_POST['outfit_items']) || empty($_POST['outfit_items'])) {
            wp_send_json_error(array('message' => 'No se han seleccionado prendas.'));
            exit;
        }
        
        $items_added = 0;
        $error_items = array();
        $outfit_items = json_decode(stripslashes($_POST['outfit_items']), true);
        
        // Recorrer cada prenda y añadirla al carrito
        foreach ($outfit_items as $item) {
            // Validar que tenemos un ID de producto
            if (!isset($item['product_id']) || empty($item['product_id'])) {
                $error_items[] = 'Prenda sin ID';
                continue;
            }
            
            $product_id = intval($item['product_id']);
            $variation_id = isset($item['variation_id']) ? intval($item['variation_id']) : 0;
            $quantity = isset($item['quantity']) ? intval($item['quantity']) : 1;
            
            // Preparar datos adicionales (talla, color, etc.)
            $cart_item_data = array();
            if (isset($item['custom_data']) && is_array($item['custom_data'])) {
                $cart_item_data = $item['custom_data'];
            }
            
            // Añadir meta personalizada para identificar que viene del probador
            $cart_item_data['_probador_virtual'] = true;
            
            // Intentar añadir al carrito
            $added = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, array(), $cart_item_data);
            
            if ($added) {
                $items_added++;
            } else {
                $product = wc_get_product($product_id);
                $error_items[] = $product ? $product->get_name() : 'Producto #' . $product_id;
            }
        }
        
        // Preparar respuesta
        if ($items_added > 0) {
            $response = array(
                'success' => true,
                'items_added' => $items_added,
                'cart_url' => wc_get_cart_url(),
                'message' => sprintf(
                    _n(
                        'Se añadió %d prenda al carrito.',
                        'Se añadieron %d prendas al carrito.',
                        $items_added,
                        'probador-integration'
                    ),
                    $items_added
                )
            );
            
            if (!empty($error_items)) {
                $response['warning'] = sprintf(
                    'No se pudieron añadir las siguientes prendas: %s',
                    implode(', ', $error_items)
                );
            }
            
            wp_send_json_success($response);
        } else {
            wp_send_json_error(array(
                'message' => 'No se pudo añadir ninguna prenda al carrito.',
                'error_items' => $error_items
            ));
        }
        
        exit;
    }
    
    /**
     * Shortcode para mostrar el botón de añadir al carrito en el probador
     */
    public function add_to_cart_button_shortcode($atts) {
        $atts = shortcode_atts(
            array(
                'text' => 'Añadir outfit al carrito',
                'class' => 'button probador-add-to-cart',
            ),
            $atts,
            'probador_add_to_cart'
        );
        
        return sprintf(
            '<button id="probador-add-to-cart" class="%s">%s</button>
            <div id="probador-cart-message"></div>',
            esc_attr($atts['class']),
            esc_html($atts['text'])
        );
    }
}

// Iniciar la clase cuando todos los plugins están cargados
add_action('plugins_loaded', function() {
    // Verificar que WooCommerce esté activo
    if (class_exists('WooCommerce')) {
        new Probador_Virtual_Integration();
    }
});
