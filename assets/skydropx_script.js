function mostrar_mas(){
    jQuery('.ul_tipos_envio li:hidden').css("display", "flex");
    if (jQuery('.ul_tipos_envio li').length == jQuery('.ul_tipos_envio li:visible').length) {
        jQuery('#mostrar_mas').hide();
    }

    jQuery("#txt_mostrarmas").val("si");
}

function setRate(data){
    var id = data.id;
    var value = data.value;
    var arr = id.split("_");
    jQuery("#txt_rateid").val(arr[1]);
    jQuery("#txt_preciodelivery").val(value);
}