jQuery(document).ready(function(){
	
	jQuery("[name='checkout_next_step']").on("click", function() {
        var zip = "-";
        var email = "-";
        var nombres = "-";
        var direccion1 = "-";
        var direccion2 = "-";
        var empresa = "-";
        var ciudad = "-";
        var telefono = "-";
        var provincia = "-";
		var distancia = "";
		var pais = "";
        
        zip = jQuery("#billing_postcode").val();
        email = jQuery("#billing_email").val();
        nombres = jQuery("#billing_first_name").val() + " " + jQuery("#billing_last_name").val();
        direccion1 = jQuery("#billing_address_1").val();
		
        if(jQuery("#billing_address_2").val() != ""){
            direccion2 = jQuery("#billing_address_2").val();
        }
        
        if(jQuery("#billing_company").val() != ""){
            empresa = jQuery("#billing_company").val();
        }
        
        ciudad = jQuery("#billing_city").val();
        telefono = jQuery("#billing_phone").val();
        provincia = jQuery("#billing_state").val();
		pais = jQuery("#billing_country").val();
		
        var extra = "&zip=" + zip + "&email=" + email + "&nombres=" + nombres + "&direccion1=" + direccion1 +
                    "&direccion2=" + direccion2 + "&empresa=" + empresa + "&ciudad=" + ciudad + "&telefono=" + telefono
                    + "&provincia=" + provincia + "&pais=" + pais;
        
        var shipping = jQuery("#timeline-1");
        var payment = jQuery("#timeline-2");
        
		//Distancia
		if(shipping.hasClass("active")){
			//alert("ajax query");
			//alert("dddddd");
			//jQuery('body').trigger('updated_shipping_method');
			//Consulta de distancia
			jQuery.blockUI({
				message: '',
				onBlock: function() { 
					jQuery(".blockUI").addClass("blockOverlay");
				} 
			});
			
			jQuery.ajax({
				type: "POST",
				url: ajax_var_distancia.url,
				data: "action=" + ajax_var_distancia.action + "&nonce=" + ajax_var_distancia.nonce + extra,
				success: function (data) {
					//jQuery('body').trigger('update_checkout');
					jQuery.unblockUI();
				},
				error: function (jqXHR, textStatus, errorThrown) {
					console.log("error");
					jQuery.unblockUI();
				}

			});		
		}
		//Distancia
		
		if(payment.hasClass("active")){
			//alert("payment");
			//jQuery('body').trigger('update_checkout');
			var limite = jQuery("#limite").val();
			//var distancia = jQuery("#txt_distancia").val();
			var distancia = getCookie('distancia');
				
			if(eval(distancia) < eval(limite)){
			//if(distancia < limite){
				//alert("menor a " + limite);
				jQuery("#txt_preciodelivery").val("");
				jQuery("#txt_rateid").val("-");
			}else{
				//alert("mayor a " + limite);
				jQuery.blockUI({
					message: '',
					onBlock: function() { 
						jQuery(".blockUI").addClass("blockOverlay");
					} 
				});
				jQuery.ajax({
					type: "POST",
					url: ajax_var.url,
					data: "action=" + ajax_var.action + "&nonce=" + ajax_var.nonce + extra,
					success: function (data) {
						console.log("info: ", data);
						jQuery('body').trigger('update_checkout');
						jQuery.unblockUI();
					},
					error: function (jqXHR, textStatus, errorThrown) {
						console.log("error");
					}

				});				
			}
        }
    });    
	
});