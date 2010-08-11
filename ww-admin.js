jQuery(function(){

  function refresh_all()
  {
    // Auto change sort order when drag and drop
    jQuery("#ww-active-items").sortable({
      update: function(event,ui){
        var active_widgets = jQuery("#ww-active-items .ww-widget:checked");
        //console.log(active_widgets);
         jQuery.each(active_widgets, function(i){
            jQuery(this).siblings(".ww-widget-weight").val(i+1);
            //console.log((i+1)+" - "+jQuery(this).attr("id"));
        });
         //console.log("--");
      }
    });
    jQuery("#ww-active-items").disableSelection();
    
    
    var checkboxes = jQuery("#widget-wrangler-form .nojs input[type=checkbox]");
    jQuery.each(checkboxes, function(){
      jQuery(this).parent('.ww-item').removeClass('nojs');
      jQuery(this).click(function(){
        if (jQuery(this).attr('checked'))
        {
          jQuery(this).parent('.ww-item').clone().addClass('nojs').prependTo("#ww-active-items").children(".ww-widget-weight").attr("disabled","");
          jQuery(this).parent('.ww-item').remove();
          refresh_all();
        }
        else
        {
          jQuery(this).siblings('.ww-widget-weight').val('').parent('.ww-item').clone().addClass('nojs disabled').appendTo("#ww-disabled-items").children(".ww-widget-weight").attr("disabled","disabled");
          jQuery(this).parent('.ww-item').remove();
          refresh_all();
        }
      });
    });
  }
  
  refresh_all();
});