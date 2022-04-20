<?php
/*
Plugin Name: Integración Skydropx
Plugin URI: https://syncromind.net/
Description: Plugin para integrar Skydropx con WooCommerce
Version: 1.0
Author: Jesus Diaz
Author URI: https://syncromind.net/
License: GPLv2 or later
Text Domain: skydropx
*/

include_once __DIR__ . '/woocommerce-distance-rate-shipping/class-wc-shipping-distance-rate.php';


//-----------------------------
//Carga archivos js y css
//-----------------------------
add_action( 'wp_enqueue_scripts', 'skydrops_enqueue' );
function skydrops_enqueue() {
	
 	wp_enqueue_script( 'skydropx-script', plugin_dir_url( __FILE__ ) . 'assets/skydropx_script.js', array(), '260521am' );
	wp_enqueue_style( 'skydropx-css', plugin_dir_url( __FILE__ ) . 'assets/skydropx_css.css' );

    wp_enqueue_script ("skydropx-shipments", plugin_dir_url( __FILE__ ) . "assets/shipments.js", array(), '260521am' ); 
    wp_localize_script( 'skydropx-shipments', 'ajax_var', array(
        'url'    => admin_url( 'admin-ajax.php' ),
        'nonce'  => wp_create_nonce( 'my-ajax-nonce' ),
        'action' => 'get_providers'
    ) );
	
	wp_localize_script( 'skydropx-shipments', 'ajax_var_zip', array(
        'url'    => admin_url( 'admin-ajax.php' ),
        'nonce'  => wp_create_nonce( 'my-ajax-nonce' ),
        'action' => 'obtener_distancia'
    ) );

    wp_localize_script( 'skydropx-shipments', 'ajax_var_distancia', array(
        'url'    => admin_url( 'admin-ajax.php' ),
        'nonce'  => wp_create_nonce( 'my-ajax-nonce' ),
        'action' => 'get_distancia'
    ) );
}
//-----------------------------
//Carga archivos js y css
//-----------------------------

//-----------------------------
//Agrega el menu de administracion para Integracion Skydropx
//-----------------------------
add_action('acf/init', 'my_acf_op_init');
function my_acf_op_init() {
    if( function_exists('acf_add_options_page') ) {
        acf_add_options_page(array(
            'page_title' 	=> 'Configuración Skydropx',
            'menu_title'	=> 'Configuración Skydropx',
            'menu_slug' 	=> 'config-skydropx',
            'capability'	=> 'edit_posts',
            'position'		=> false,
            'redirect'		=> false
        ));   
    }
}
//-----------------------------
//Agrega el menu de administracion para Integracion Skydropx
//-----------------------------

add_action( 'wp_ajax_nopriv_get_distancia', 'get_distancia' );
add_action( 'wp_ajax_get_distancia', 'get_distancia' );
function get_distancia(){
    $zip = $_POST['zip'];
	$direccion1 = $_POST['direccion1'];
	$direccion2 = $_POST['direccion2'];
	$ciudad = $_POST['ciudad'];
	$pais = $_POST['pais'];
    $provincia = $_POST['provincia'];
    
    if ( isset( WC()->countries->states[ $pais ], WC()->countries->states[ $pais ][ $provincia ] ) ) {
        $state = WC()->countries->states[ $pais ][ $provincia ];
        $country = WC()->countries->countries[ $pais ];
    }

    if ( isset( WC()->countries->countries[ $pais ] ) ) {
        $country = WC()->countries->countries[ $pais ];
    }

    $destino = $direccion1.','.$direccion2.','.$ciudad.','.$state.','.$zip.','.$country;
    $ejemplo = new WC_Shipping_Distance_Rate();
    $distance = $ejemplo->get_api()->get_distance( "ANDADOR EL CAPULIN 5B, COL. ORTIZ RUBIO, OCOYOAC, 52740, MEXICO", $destino, false, $ejemplo->mode, $ejemplo->avoid, $ejemplo->unit, $region );
    //print_r($distance);
    $distancia = 0;
    if ( is_object( $distance ) ) {
        $distance_value = $distance->rows[0]->elements[0]->distance->value;
        $distancia = round( $distance_value / 1000 );
    }
    setcookie( 'distancia', $distancia, (time()+86400), COOKIEPATH, COOKIE_DOMAIN );
    //echo($distancia);
}

//-----------------------------
//Request al API de Skydropx
//-----------------------------
add_action( 'wp_ajax_nopriv_get_providers', 'get_providers' );
add_action( 'wp_ajax_get_providers', 'get_providers' );
function get_providers(){
	WC()->session->__unset('delivery');
    $nonce = sanitize_text_field( $_POST['nonce'] );
    if ( ! wp_verify_nonce( $nonce, 'my-ajax-nonce' ) ) {
        die ( 'Busted!');
    }
	
	$zip = $_POST['zip'];
	$email = $_POST['email'];
	$nombres = $_POST['nombres'];
	$direccion1 = $_POST['direccion1'];
	$direccion2 = $_POST['direccion2'];
	$empresa = $_POST['empresa'];
	$ciudad = $_POST['ciudad'];
	$telefono = $_POST['telefono'];
	$provincia = $_POST['provincia'];

	global $woocommerce;
	//$items = $woocommerce->cart->get_cart();
	$items = WC()->cart->get_cart();
	$request = array();
	$parcels = array();
	$x = 0;

	foreach($items as $item) { 
		$quantity = $item['quantity'];
		$product =  wc_get_product( $item['data']->get_id()); 
		if($quantity > 1){
			for($y=0;$y<$quantity;$y++){
				$weight = $product->get_weight();
				$length = $product->get_length();
				$width = $product->get_width();
				$height = $product->get_height();
				$parcels[$x]['weight'] = (int)ceil($weight);
				$parcels[$x]['distance_unit'] = 'CM';
				$parcels[$x]['mass_unit'] = 'KG';
				$parcels[$x]['height'] = (int)ceil($height);
				$parcels[$x]['width'] = (int)ceil($width);
				$parcels[$x]['length'] = (int)ceil($length);
				$x++;
			}
		}else{
			$weight = $product->get_weight();
			$length = $product->get_length();
			$width = $product->get_width();
			$height = $product->get_height();
			$parcels[$x]['weight'] = (int)ceil($weight);
			$parcels[$x]['distance_unit'] = 'CM';
			$parcels[$x]['mass_unit'] = 'KG';
			$parcels[$x]['height'] = (int)ceil($height);
			$parcels[$x]['width'] = (int)ceil($width);
			$parcels[$x]['length'] = (int)ceil($length);	
		}
		
		//$x++;
	} 

	$request['address_from']['province'] = get_field("provincia", "option");
	$request['address_from']['city'] = get_field("ciudad", "option");
	$request['address_from']['name'] = get_field("nombre_del_contacto", "option");
	$request['address_from']['zip'] = get_field("zip", "option");
	$request['address_from']['country'] = get_field("pais", "option");
	$request['address_from']['address1'] = get_field("direccion_1", "option");
	$request['address_from']['company'] = get_field("empresa", "option");
	$request['address_from']['address2'] = get_field("direccion_2", "option");
	$request['address_from']['phone'] = get_field("telefono", "option");
	$request['address_from']['email'] = get_field("correo", "option");

	$request['parcels'] = $parcels;

	$request['address_to']['province'] = $provincia;
	$request['address_to']['city'] = $ciudad;
	$request['address_to']['name'] = $nombres;
	$request['address_to']['zip'] = $zip;
	$request['address_to']['country'] = "MX";
	$request['address_to']['address1'] = $direccion1;
	$request['address_to']['company'] = $empresa;
	$request['address_to']['address2'] = $direccion2;
	$request['address_to']['phone'] = $telefono;
	$request['address_to']['email'] = $email;
	$request['address_to']['contents'] = "productos";

	$request = json_encode($request);
	echo($request);

	$curl = curl_init();
	$url = "https://api.skydropx.com/v1/shipments";
	curl_setopt_array($curl, array(
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "POST",
		CURLOPT_HTTPHEADER => array(
			"Authorization: Token token=" . get_field("api_key", "option"),
			'Content-Type: application/json',
		),
		CURLOPT_POSTFIELDS => $request
	));

	$response = curl_exec($curl);
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	$err = curl_error($curl);
	curl_close($curl);
	$shipments = array();

    if($httpcode == '500'){
        setcookie( 'respuesta', 500, (time()+86400), COOKIEPATH, COOKIE_DOMAIN );
    }else{
        setcookie( 'respuesta', 200, (time()+86400), COOKIEPATH, COOKIE_DOMAIN );
        if ($err) {
            echo("error");
    
        } else {
            $responseObj = json_decode($response);
            //print_r($responseObj);
            if (! empty($responseObj->included)) {
                $x=0;
                foreach ($responseObj->included as $included) {
                    if($included->type == "rates"){
                        $shipments[$x]['id'] = $included->id;
                        $shipments[$x]['provider'] = $included->attributes->provider;
                        $shipments[$x]['service_level_name'] = $included->attributes->service_level_name;
                        $shipments[$x]['amount_local'] = $included->attributes->amount_local;
                        $shipments[$x]['out_of_area_pricing'] = $included->attributes->out_of_area_pricing;
                        $shipments[$x]['days'] = $included->attributes->days;
                        $shipments[$x]['total_pricing'] = $included->attributes->total_pricing;
                        $x++;
                    }
                }
            }
            WC()->session->set( 'shipments', $shipments );
        }	
    }
    //echo("msg: ".$httpcode);	
}
//-----------------------------
//Request al API de Skydropx
//-----------------------------

//-----------------------------
//Arma el listado con los proveedores
//-----------------------------
add_action( 'woocommerce_review_order_before_order_total', 'checkout_delivery_radio_buttons' );
function checkout_delivery_radio_buttons() {
    $shipments = WC()->session->get( 'shipments' );
	$distancia = $_COOKIE['distancia'];
	
	if(!empty($shipments) && $distancia > get_field("limite_de_km", "option") && $_COOKIE['respuesta'] == '200'){
	    //Ordena los proveedores por precio de envio 
        usort($shipments, function($a, $b) {
            return $a['total_pricing'] <=> $b['total_pricing'];
        });

        //Proveedores seleccionados por el cliente
        $proveedores_array = array();
        $proveedores = strtoupper(get_field("lista_de_proveedores", "option"));

        $proveedores_array = explode(";", $proveedores);
        
        $options = array();

        for ($x=0;$x<count($shipments);$x++) {
            for($y=0;$y<count($proveedores_array);$y++){
                $existe = strpos($shipments[$x]['provider'], $proveedores_array[$y]);
                
                if($existe !== false){
                    $options[$shipments[$x]['id']] = $shipments[$x]['provider'].'|'.$shipments[$x]['total_pricing'].'|'.$shipments[$x]['days'].'|'.$shipments[$x]['service_level_name'];
                }
            }
        }

        echo '<tr class="delivery-radio">
                <th>'.__("Proveedores").'</th><td><div>';

        $chosen = WC()->session->get( 'delivery' );
        $chosen = empty( $chosen ) ? WC()->checkout->get_value( 'delivery' ) : $chosen;
        $chosen = empty( $chosen ) ? '0' : $chosen;

        woocommerce_form_field( 'delivery',  array(
            'type'      => 'radio',
            'class'     => array( 'update_totals_on_change' ),
            'options'   => $options,
        ), $chosen );

        echo '</div></td></tr>';
    }else{
        //echo("SIN LISTA");
    }
}
//-----------------------------
//Arma el listado con los proveedores
//-----------------------------


add_action( 'woocommerce_cart_calculate_fees', 'checkout_delivery_fee', 20, 1 );
function checkout_delivery_fee( $cart ) {
	//echo(WC()->session->get( 'precio_delivery' ));
    if (WC()->session->get( 'precio_delivery' ) != "" && $_COOKIE['distancia'] > get_field("limite_de_km", "option")) {
        $cart->add_fee( 'Precio de Envío', WC()->session->get( 'precio_delivery' ) );
    }
}

add_action( 'woocommerce_checkout_update_order_review', 'checkout_delivery_choice_to_session' );
function checkout_delivery_choice_to_session( $posted_data ) {

	parse_str( $posted_data, $output );


    $packages = WC()->cart->get_shipping_packages();
    foreach ($packages as $key => $value) {
        $shipping_session = "shipping_for_package_$key";
        unset(WC()->session->$shipping_session);
    }


    //print_r($posted_data);
	//output - valores que se reciben del post
    if ( isset( $output['delivery'] ) ){
        WC()->session->set( 'delivery', $output['delivery'] );
    }else{
        WC()->session->__unset( 'delivery');
    }

    if ( isset( $output['mostrarmas'] ) && $output['mostrarmas'] != "" ){
        WC()->session->set( 'mostrarmas', $output['mostrarmas'] );
    }

    if ( isset( $output['precio_delivery'] ) && $output['precio_delivery'] != "" ){
        WC()->session->set( 'precio_delivery', $output['precio_delivery'] );
    }

    if($_COOKIE['distancia'] < get_field("limite_de_km", "option")){
        setcookie( 'respuesta', '200', (time()+86400), COOKIEPATH, COOKIE_DOMAIN );
    }
}

function hide_shipping_methods( $rates, $package ) {
    if($_COOKIE['respuesta'] == '500'){
        unset( $rates['distance_rate:11'] );
        unset( $rates['distance_rate:12'] );
        unset( $rates['distance_rate:20'] );
    }
    return $rates;
}
add_filter( 'woocommerce_package_rates', 'hide_shipping_methods', 10, 2 );

add_filter( 'woocommerce_form_field_radio', 'filter_woocommerce_form_field_radio', 10, 4 );
function filter_woocommerce_form_field_radio( $field, $key, $args, $value ) {
    if(isset($_COOKIE['distancia']) && $_COOKIE['distancia'] > get_field("limite_de_km", "option") &&  $_COOKIE['respuesta'] == '200' ){
        if ( $key == 'delivery' ) {
        
            $chosen = WC()->session->get( 'precio_delivery' );
            $chosen = empty( $chosen ) ? WC()->checkout->get_value( 'precio_delivery' ) : $chosen;
            $chosen = empty( $chosen ) ? '0' : $chosen;
            
            if ( ! empty( $args['options'] ) ) {
                
                $field = '<div class="update_totals_on_change"><ul class="ul_tipos_envio">';
                $cont = 0;
                
                foreach ( $args['options'] as $option_key => $option_text ) {
                    $values = explode('|', $option_text);
                    
                    if(WC()->session->get( 'mostrarmas' ) == "si"){
                        $field .= '<li class="li_tipos_envio">';
                    }
    
                    if(WC()->session->get('mostrarmas') == ""){
                        if($cont<3){
                            $field .= '<li class="li_tipos_envio">';
                        }else{
                            $field .= '<li class="li_tipos_envio" style="display:none;">';
                        }
                    }
                    
                    if(isset($chosen) && $chosen != "" && $chosen == esc_attr( $values[1] )){
                        $field .= '<input onclick="javascript:setRate(this);" checked="checked" class="input_radio" type="radio" value="' . esc_attr( $option_key ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '_' . esc_attr( $option_key ) . '" />';
                        WC()->session->set( 'rateid',  esc_attr( $option_key ));
                    }else{
                        $field .= '<input onclick="javascript:setRate(this);" class="input_radio" type="radio" value="' . esc_attr( $values[1] ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '_' . esc_attr( $option_key ) . '" />';
                    }
                    
                    $field .= '<div class="detalle_envio">
                        <p><b>'.esc_html( $values[0] ).'</b></p>
                        <p>'.esc_html( $values[3] ).' (Entrega estimada: <b>'.esc_html( $values[2] ).' días)</b><br>
                        <b>Precio:</b> <span class="amount">$'.esc_attr( $values[1] ).'</span>
                        </p>
                    </div>';
    
                    $field .= '</li>';
                    $cont++;
                }
                
                if(WC()->session->get( 'mostrarmas' ) == ""){
                    $field .= '</ul>
                    <div id="mostrar_mas" onclick="javascript:mostrar_mas();">Mostrar más</div></div>';
                }
            }
        }
    }else{
        $field = "<div>empty</div>";
    }

    
    
    return $field;
}

//-----------------------------
//Bloque para agregar campos relacionados a Skydropx
//-----------------------------
add_filter( 'woocommerce_checkout_fields' , 'woocommerce_checkout_field_editor' );
function woocommerce_checkout_field_editor( $fields ) { 
    $fields['order']['rateid'] = array(
		'type'			=> 'hidden',
		'class'			=> array('form-row-wide'),
		'id'			=> 'txt_rateid',
		'required'  	=> true,
		'input_class' => array(       			   
			'input-text',						   		 
		)
	);
    $fields['order']['rateid']['priority'] = 0;
    if(WC()->session->get( 'rateid' ) != ""){
        $fields['order']['rateid']['default'] = WC()->session->get( 'rateid' );
    }

    $fields['order']['mostrarmas'] = array(
		'type'			=> 'hidden',
		'class'			=> array('form-row-wide'),
		'id'			=> 'txt_mostrarmas',
		'required'  	=> false,
		'input_class' => array(       			   
			'input-text',						   		 
		)
	);
    $fields['order']['mostrarmas']['priority'] = 20;

    $fields['order']['precio_delivery'] = array(
		'type'			=> 'hidden',
		'class'			=> array('form-row-wide'),
		'id'			=> 'txt_preciodelivery',
		'required'  	=> false,
		'input_class' => array(       			   
			'input-text',						   		 
		)
	);
    $fields['order']['precio_delivery']['priority'] = 30;

    if(WC()->session->get( 'precio_delivery' ) != ""){
        $fields['order']['precio_delivery']['default'] = WC()->session->get( 'precio_delivery' );
    }
    
	return $fields;
}
//-----------------------------
//Bloque para agregar campos relacionados a Skydropx
//-----------------------------

add_action( 'woocommerce_cart_calculate_fees', 'woo_add_cart_fee' );
function woo_add_cart_fee( $cart ){
    if ( ! $_POST || ( is_admin() && ! is_ajax() ) ) {
        return;
    }

    if ( isset( $_POST['post_data'] ) ) {
        parse_str( $_POST['post_data'], $post_data );
    } else {
        $post_data = $_POST; 
    }
	
    if (isset($post_data['delivery']) && $post_data['delivery'] != "" && $_COOKIE['distancia'] > get_field("limite_de_km", "option")){
        //WC()->cart->set_shipping_total($post_data['delivery']);	
    }
}

//-----------------------------
//Bloque que se ejecuta al culminar la orden
//-----------------------------
add_action( 'woocommerce_thankyou', 'code_after_payment' );
function code_after_payment( $order_id ){
	
    if( ! get_post_meta( $order_id, '_thankyou_action_done', true ) ) {
		if($_COOKIE['distancia'] > get_field("limite_de_km", "option")){
			$request = array();
			$order = wc_get_order($order_id);
			$rateid = $order->get_meta('rate_id');

			$request['rate_id'] = (int)$rateid;
			$request['label_format'] = "pdf";
			$request = json_encode($request);

			/*$curl = curl_init();
			$url = "https://api.skydropx.com/v1/labels";
			curl_setopt_array($curl, array(
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_HTTPHEADER => array(
					"Authorization: Token token=" . get_field("api_key", "option"),
					'Content-Type: application/json',
				),
				CURLOPT_POSTFIELDS => $request
			));

			$response = curl_exec($curl);
			$err = curl_error($curl);
			curl_close($curl);*/
			
			$order->update_meta_data( '_thankyou_action_done', true );
			$order->save();
		}
        
        WC()->session->__unset('shipments');
        WC()->session->__unset('delivery');
        WC()->session->__unset('mostrarmas');
        WC()->session->__unset('precio_delivery');
        WC()->session->__unset('rateid');
		//setcookie( 'distancia', 10, (time()+86400), COOKIEPATH, COOKIE_DOMAIN );
    }
}
//-----------------------------
//Bloque que se ejecuta al culminar la orden
//-----------------------------

/*add_filter( 'woocommerce_distance_rate_shipping_calculated_distance', function($distance, $distance_value){
	if(isset($_COOKIE['distancia'])){
		$distance = $_COOKIE['distancia'];
	}
	setcookie( 'distancia', $distance, (time()+86400), COOKIEPATH, COOKIE_DOMAIN );
	return $distance;
},10,4);*/

//-----------------------------
//Bloque que se ejecuta cada vez que el shipping rate cambie
//-----------------------------
add_filter( 'woocommerce_distance_rate_shipping_rule_cost_distance_shipping', function( $rule_cost, $rule, $distance, $package ) {
	//$order_total = $package['contents_cost'];
	//if ( $order_total > 100 && $distance <= 5 ) {
	//	$rule_cost = 0;
	//}
    //if($distance > 20){
        //$rule_cost = 0;
    //}
    
	//if(is_checkout()){
	//	setcookie( 'distancia', $distance, (time()+86400), COOKIEPATH, COOKIE_DOMAIN );
	//}
	setcookie( 'distancia', $distance, (time()+86400), COOKIEPATH, COOKIE_DOMAIN );
	//echo("distancoa: ".$distance);
	return $rule_cost;
}, 10, 4 );
//-----------------------------
//Bloque que se ejecuta cada vez que el shipping rate cambie
//-----------------------------


add_action( 'woocommerce_checkout_update_order_meta', 'save_order_data', 10, 2);
function save_order_data( $order_id ) {
	if (isset($_POST['rateid'])) {
		$rateid = sanitize_text_field( $_POST['rateid'] );
		update_post_meta( $order_id, 'rate_id', $rateid);
	}
}

//-----------------------------
//Bloque para volver inactivos los productos congelados
//-----------------------------
add_filter('woocommerce_loop_add_to_cart_link', function( $add_to_cart_html, $product ) {
	if( strpos($product->get_categories(), "congelados") != false && ($_COOKIE['distancia'] > get_field("limite_de_km", "option"))) {
		return str_replace("%", get_field("limite_de_km", "option"), get_field("mensaje_no_disponible", "option")); 
	}
	return $add_to_cart_html;
 
}, 10, 2 );
//-----------------------------
//Bloque para volver inactivos los productos congelados
//-----------------------------

//-----------------------------
//Bloque para inactivar productos congelados
//-----------------------------
add_filter('woocommerce_is_purchasable', 'filter_is_purchasable', 10, 2);
function filter_is_purchasable($is_purchasable, $product ) {
	$categories = get_the_terms( $product->id, 'product_cat');
    $my_terms_ids = array(62);
	if ($categories && !is_wp_error($categories)) {
        foreach ($categories as $cat) {
            if ( in_array($cat->term_id, $my_terms_ids ) && ($_COOKIE['distancia'] > get_field("limite_de_km", "option"))) {
                return false;
            }
            return $is_purchasable;
        }
    }
}
//-----------------------------
//Bloque para inactivar productos congelados
//-----------------------------

function my_custom_shipping_calculator_field() {
	$postcode = isset( $_REQUEST['calc_shipping_postcode'] ) ? $_REQUEST['calc_shipping_postcode'] : '';
	if ( $postcode ) {
		WC()->customer->__set( 'billing_postcode', $postcode );
		WC()->customer->__set( 'shipping_postcode', $postcode );
	}
}
//add_action( 'woocommerce_calculated_shipping', 'my_custom_shipping_calculator_field' );




//-----------------------------
//Bloque que se agrega en el footer
//-----------------------------
add_action( 'wp_footer', 'woocommerce_add_ziptext' );
function woocommerce_add_ziptext() {
	?>
	<input type="hidden" id="limite" value="<?PHP echo(get_field("limite_de_km", "option")); ?>"  />

	<script>
		function getCookie(name) {
			// Split cookie string and get all individual name=value pairs in an array
			var cookieArr = document.cookie.split(";");
			
			// Loop through the array elements
			for(var i = 0; i < cookieArr.length; i++) {
				var cookiePair = cookieArr[i].split("=");
				
				/* Removing whitespace at the beginning of the cookie name
				and compare it with the given string */
				if(name == cookiePair[0].trim()) {
					// Decode the cookie value and return
					return decodeURIComponent(cookiePair[1]);
				}
			}
			
			// Return null if not found
			return null;
		}

		jQuery(document.body).on('updated_checkout', function(){
			jQuery("#txt_distancia").val(getCookie('distancia'));
		});
	</script>
		
	<?PHP
	if(is_cart()){
		?>
		<!--input type="text" id="limite"  /-->
		<script type="text/javascript">
			//jQuery("[name='update_cart']")
			jQuery(document).ready(function(){
				jQuery("[name='calc_shipping']").click(function(){
					//jQuery("[name='update_cart']").trigger('click');
					var destino = jQuery("#calc_shipping_postcode").val();
					//location.reload();
					/*var extra = "&destino=" + destino;
					jQuery("#txt_rateid").val("");
					jQuery.ajax({
						type: "POST",
						url: ajax_var_zip.url,
						data: "action=" + ajax_var_zip.action + "&nonce=" + ajax_var_zip.nonce + extra,
						success: function (data) {
							if(data != "error"){
								console.log(data);
								var limite = jQuery("#limite").val();
								if(data < limite){
								   jQuery("#txt_rateid").val("-");
								}
								jQuery("#txtchosenzip").val(data);
								//Vuelve a cargar el carrito
								//location.reload();
							}else{
								console.log(data);
							}
						},
						error: function (jqXHR, textStatus, errorThrown) {
							console.log("error");
						}

					});*/
				});
			});
		</script>
		<?PHP
	}
	
	if(isset($_COOKIE['distancia'])){
		$distancia = $_COOKIE['distancia'];
	?>
	<input type="hidden" id="txt_distancia" value="<?php echo($distancia); ?>" />
	  
    <?php
	}else{
		?>
	<input type="hidden" id="txt_distancia"  />

<?php
	}
}
//-----------------------------
//Bloque que se agrega en el footer
//-----------------------------


?>