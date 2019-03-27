<?php
function init_wc_culqi_subscriptio_payment_gateway() {
    if (!class_exists('WC_Culqi')) {
        return;
    }
  
   class WC_CulqiSubscriptio extends WC_Culqi {
     
      public function __construct() {
            global $woocommerce;
            $this->includes();
            $this->id = 'culqi';
            $this->icon = home_url() . '/wp-content/plugins/' . dirname(plugin_basename(__FILE__)) . '/assets/images/cards.png';
            $this->method_title = __('Culqi Subscriptio Support', 'WC_culqi');
            $this->method_description = __('Extension de Culqi para el plugin Subscriptio.', 'WC_culqi');
            $this->order_button_text = __('Pagar', 'WC_culqi');
            $this->has_fields = false;
            $this->supports = array(
                'products',
                'subscriptio'
            );
            $this->init_form_fields();
            $this->init_settings();
            //$this->title = 'Tarjeta de crédito o débito';
            $this->description = 'Paga con tarjeta de crédito, débito o prepagada de todas las marcas.';
            // Obtener credenciales y entorno
            $this->culqi_codigoComercio = $this->get_option('culqi_codigoComercio');
            $this->culqi_key = $this->get_option('culqi_key');
            $this->culqi_nombre_comercio = get_bloginfo('name');
            add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'crear_cargo'));// Crear Cargo
            add_action('woocommerce_receipt_culqi', array(&$this, 'receipt_page'));
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('subscriptio_automatic_payment_'. $this->id, array($this, 'process_subscriptio_payment'), 10, 3);
            if (!$this->is_valid_for_use()) $this->enabled = false;
        
            $this->wc_api = 'WC_CulqiSubscriptio';
      }     
      
      function crear_cargo() {
            if (isset($_POST['token_id']) && isset($_POST['order_id'])) {
                global $wpdb, $woocommerce;
                $order = new WC_Order($_POST['order_id']);
                $order_id = $_POST['order_id'];
                $hasSubscription = false;
                $items = $order->get_items();
                foreach($items as $item){
                  $product_name = $item->get_name();
                  $product_id = $item->get_product_id();
                  //$product = get_post($product_id);
                  $subscriptio = get_post_meta($product_id,'_subscriptio',true);
                  if($subscriptio == 'yes'){
                    $hasSubscription = true;
                  }                  
                }                
              
                $numeroPedido = str_pad($order->id, 2, "0", STR_PAD_LEFT);
                $total = str_replace('.', '', number_format($order->get_total(), 2, '.', ''));
                $total = str_replace(',', '',$total);
                $culqi = new Culqi\Culqi(array('api_key' => $this->culqi_key));
                // Generamos un Código de pedido único (ejemplo)
                $pedidoId = $this->generateRandomString(4)."-".$numeroPedido;
                error_log("Número de pedido: ". $pedidoId);
                error_log("Token: ". $_POST['token_id'] );
                /**
                 * Validando y formateando datos (one more time)							 *
                 *
                 */
                 $dataUser = $order->get_user();
                 $fono = $dataUser->billing_phone;
                 $descripcion = '';
                 $i = 1;
                 $separador = ' - ';
                 foreach ($order->get_items() as $product ){
                     if($i == count($order->get_items())){
                         $separador = '';
                     }
                     $descripcion .= $product['name'].$separador;
                     $i++;
                 }
                 if(strlen ($descripcion)>5 && strlen ($descripcion)<60) {
                     error_log("Descripción correcto");
                 } else {
                     $descripcion = "Compra";
                 }
                 $datos_ciudad = "";
                 $datos_correo = "";
                 $datos_apellido = "";
                 $datos_nombre = "";
                 $datos_telefono = "";
                 $datos_direccion = "";
                 if ($order->billing_city == null) {
                     $datos_ciudad = "Ciudad";
                 } else {
                     $datos_ciudad = $order->billing_city;
                 }
                 if ($order->billing_first_name == null){
                     $datos_nombre = "Nombre";
                 } else {
                     $datos_nombre = $order->billing_first_name;
                 }
                 if ($order->billing_last_name == null){
                     $datos_apellido = "Apellido";
                 } else {
                     $datos_apellido = $order->billing_last_name;
                 }
                 if ($order->billing_email == null){
                     $datos_correo = "correo@tienda.com";
                 } else {
                     $datos_correo = $order->billing_email;
                 }
                 if ($order->billing_phone == null){
                     $datos_telefono = "12313123";
                 } else {
                     $datos_telefono = $order->billing_phone;
                 }
                 if ($order->billing_address_1 == null) {
                     $datos_direccion = "Avenida 123";
                 } else {
                     $datos_direccion = $order->billing_address_1;
                 }
                // Creando Cargo
                try {
                  //echo 'creando cargo culqi'; exit;                  
                  $token_id = $_POST['token_id'];
                  $source_id = $token_id;
                  
                  if($hasSubscription){
                    $customer_id = null;
                    $customer_exists = false;
                    $customers_list_response = 
                      $culqi->Customers->getList();
                    //print_r($customers);exit;
                    foreach($customers_list_response->data as $customer){
                      //print_r($customer);
                      if($customer->email == $datos_correo){
                        $customer_id = $customer->id;
                        $customer_exists = true;
                        //print_r($customer);exit;
                      }
                    }
                    //echo 'no existe';exit;
                    //print_r($customers);exit;
                    if(!$customer_exists){//Crear Cliente                  
                      $customer_response = 
                        $culqi->Customers->create(
                        array(
                          "address" => $datos_direccion,
                          "address_city" => $datos_ciudad,
                          "country_code" => $order->billing_country,
                          "email" => $datos_correo,
                          "first_name" => $datos_nombre,
                          "last_name" => $datos_apellido,
                          "phone_number" => $datos_telefono
                        )
                      );
                      $customer_id = $customer_response->id;
                      //print_r($customer_response); exit;
                    }
                    //Crear Tarjeta
                    $card_response = 
                      $culqi->Cards->create(
                      array(
                        "customer_id" => $customer_id,
                        "token_id" => $token_id
                      )
                    );
                    //print_r($card_response); exit;
                    $card_id = $card_response->id;
                    $source_id = $card_id;
                    
                    add_post_meta($order_id, '_customer_id', $customer_id, false);
                    add_post_meta($order_id, '_card_id', $card_id, false);
                  }                  
                  $charge = $culqi->Charges->create(array(
                        "amount" => $total,
                        "antifraud_details" => array(
                            "address" => $datos_direccion,
                            "address_city" => $datos_ciudad,
                            "country_code" => $order->billing_country,
                            "first_name" => $datos_nombre,
                            "last_name" => $datos_apellido,
                            "phone_number" => $datos_telefono,
                        ),
                        "capture" => true,
                        "currency_code" => $order->order_currency,
                        "description" => $descripcion,
                        "email" => $datos_correo,
                        "installments" => (int)$_POST['installments'],
                        "metadata" => array(
                            "order_id" => (string)$pedidoId
                        ),
                        "source_id" => $source_id
                    ));
                    if($charge->object == "charge") {
                        $order->payment_complete();
                    }
                    echo wp_send_json($charge);
                } catch(Exception $e) {
                    // ERROR: El cargo tuvo algún error o fue rechazado
                    //echo 'Se dio una excepcion';
                    echo wp_send_json($e->getMessage());
                }
           } else {
                global $woocommerce;
                $woocommerce->cart->empty_cart();
           }
           exit;
        }
     
      function process_subscriptio_payment($payment_successful, $order, $subscription){
        //error_log('WC_CulqiSubscriptio::process_subscriptio_payment');
        //error_log('$order');
        //error_log(print_r($order,true));
        //error_log('$subscription');
        //error_log(print_r($subscription,true));                     
        
        $total = str_replace('.', '', number_format($order->get_total(), 2, '.', ''));
        $total = str_replace(',', '',$total);
        
        $datos_ciudad = $order->billing_city == null ? $datos_ciudad = "Ciudad" : $datos_ciudad = $order->billing_city;
        $datos_nombre = $order->billing_first_name == null ? $datos_nombre = "Nombre" : $datos_nombre = $order->billing_first_name;
        $datos_apellido = $order->billing_last_name == null ? $datos_apellido = "Apellido" : $datos_apellido = $order->billing_last_name;
        $datos_correo = $order->billing_email == null ? $datos_correo = "correo@tienda.com" : $datos_correo = $order->billing_email;
        $datos_telefono = $order->billing_phone == null ? $datos_telefono = "12313123" : $datos_telefono = $order->billing_phone;
        $datos_direccion = $order->billing_address_1 == null ? $datos_direccion = "Avenida 123" : $datos_direccion = $order->billing_address_1;         
        $descripcion = "Cobro Suscripcion";
        
        $culqi = new Culqi\Culqi(array('api_key' => $this->culqi_key));
        
        try {
            
            $subscription_id = $subscription->id;
            
            $first_order_id = get_post_meta($subscription_id,'all_order_ids',true);
            //error_log('$first_order_id: '. $first_order_id);
                
            $numeroPedido = str_pad($order->id, 2, "0", STR_PAD_LEFT);
            $pedidoId = $this->generateRandomString(4)."-".$numeroPedido;
        
            $source_id = get_post_meta($first_order_id, '_card_id', true);
            //error_log('$source_id: '. $source_id);
            
            $charge = $culqi->Charges->create(array(
                "amount" => $total,
                "antifraud_details" => array(
                    "address" => $datos_direccion,
                    "address_city" => $datos_ciudad,
                    "country_code" => $order->billing_country,
                    "first_name" => $datos_nombre,
                    "last_name" => $datos_apellido,
                    "phone_number" => $datos_telefono,
                ),
                "capture" => true,
                "currency_code" => $order->order_currency,
                "description" => $descripcion,
                "email" => $datos_correo,
                //"installments" => (int)$_POST['installments'],
                "metadata" => array(
                    "order_id" => (string)$pedidoId
                ),
                "source_id" => $source_id
            ));
            if($charge->object == "charge") {
                $order->payment_complete();
            }
                            
            //echo wp_send_json($charge);
            return true;
        } catch(Exception $e) {
            // ERROR: El cargo tuvo algún error o fue rechazado
            //echo 'Se dio una excepcion';
            //echo wp_send_json($e->getMessage());
            return false;
        }
      }
   }     
}