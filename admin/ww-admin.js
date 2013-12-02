
WidgetWrangler.sortable = {};

/*
 * Show & Hide the "no widget" list item if corral is empty
 */
WidgetWrangler.sortable.toggle_no_widgets = function(){
	var lists = jQuery("ul.ww-sortable");
	jQuery.each(lists, function(){
		var num_items = jQuery(this).children('.ww-item');
		if(num_items.length > 0)
		{
			jQuery(this).children('.ww-no-widgets').hide();
		}
		else
		{
			jQuery(this).children('.ww-no-widgets').show();
		}
		//console.log(num_items.length);
	});
}

/*
 * Update Widget weight value when sorted
 */
WidgetWrangler.sortable.update_widget_weights = function(){
	var lists = jQuery("ul.ww-sortable");
	jQuery.each(lists, function(){
		var this_list = jQuery(this).children(".ww-item");
			jQuery.each(this_list, function(i){
				jQuery(this).children(".ww-widget-weight").val(i+1);// stop working in 3.2:  .attr("disabled","");
			});
	});
}

/*
 * Indicate changes have occured
 */
WidgetWrangler.sortable.message = function() {
	jQuery('#ww-post-edit-message').show();
}

/*
 * Refresh all the sortable lists
 */
WidgetWrangler.sortable.refresh_all = function() {
	// Auto change sort order when drag and drop
	var sortable_lists = jQuery("ul.ww-sortable");
	jQuery("ul.ww-sortable").sortable({
		items: '> li:not(.ww-no-widgets)',
		connectWith: '.ww-sortable',
		cancel: 'select,input',
		update: function(event,ui){
			var active_widgets = jQuery(this).children(".ww-item");
			var corral_name = jQuery(this).attr("name");
			 jQuery.each(active_widgets, function(i){
					jQuery(this).children("select").val(corral_name);
			});
			WidgetWrangler.sortable.toggle_no_widgets();
			WidgetWrangler.sortable.update_widget_weights();
			WidgetWrangler.sortable.message();
		}
	}).disableSelection();
	
	
	var selects = jQuery("#widget-wrangler-form .nojs select");
	jQuery.each(selects, function(){
		jQuery(this).parent('.ww-item').removeClass('nojs');
		jQuery(this).change(function(){
			var select_val = jQuery(this).val();
			var select_name = jQuery(this).attr("name");
			
			if ( select_val != 'disabled')
			{
				jQuery(this).parent('.ww-item').clone()
					.addClass('nojs').prependTo("#ww-corral-"+select_val+"-items").removeClass("disabled");
				jQuery(this).parent('.ww-item').remove();
				jQuery("#ww-corral-"+select_val+"-items select[name='"+select_name+"']").val(select_val);
				
				var this_list = jQuery("#ww-corral-"+select_val+"-items").children(".ww-item");
			}
			else
			{
				jQuery(this).siblings('.ww-widget-weight').val('').parent('.ww-item').clone().addClass('nojs disabled').appendTo("#ww-disabled-items").children(".ww-widget-weight").attr("disabled","disabled");
				jQuery(this).parent('.ww-item').remove();
				jQuery("#ww-disabled-items select[name='"+select_name+"']").val(select_val);
			}
			WidgetWrangler.sortable.update_widget_weights();
			WidgetWrangler.sortable.toggle_no_widgets();
			WidgetWrangler.sortable.refresh_all();
		});
	});
}

/*
 * Disable sorting
 */
WidgetWrangler.sortable.disable = function() {
	var selects = jQuery("#widget-wrangler-form select, #widget-wrangler-form input[type='text']");
	jQuery.each(selects, function(i, element){
		jQuery(element).attr('disabled','disabled');
	});
}

/*
 * Enable sorting
 */
WidgetWrangler.sortable.enable = function() {
	var selects = jQuery("#widget-wrangler-form select, #widget-wrangler-form input[type='text']");
	jQuery.each(selects, function(i, element){
		jQuery(element).removeAttr('disabled');
	});
	WidgetWrangler.sortable.refresh_all();	
}

/*
 * Initialize sorting and enable if no preset is selected
 */
WidgetWrangler.sortable.init = function() {
	var preset_id = jQuery('select#ww_post_preset').val();
	
	// handle disabling from outside of object definition
	if (typeof preset_id === 'undefined') {
    preset_id = 0;
  }

  if (preset_id == 0) {
		WidgetWrangler.sortable.enable(); 
	}
	else {
		WidgetWrangler.sortable.disable();
	}
}

jQuery(document).ready(function(){

	WidgetWrangler.sortable.init();
	
	// fix some form input issues
	// http://stackoverflow.com/questions/13898027/jquery-ui-sortable-cant-type-in-input-fields-with-cancel-method
	jQuery("#widget-wrangler-form select, #widget-wrangler-form input[type='text']").live('click', function(e) {
		jQuery(this).trigger({
			type: 'mousedown',
			which: 3
		});
	});
	
	jQuery("#widget-wrangler-form select, #widget-wrangler-form input[type='text']").live('mousedown', function(e) {
		if(e.which == 3){
			jQuery(this).focus();   
		}
	});
	// */

  // open and close widget menu
  jQuery('.widget-action').click(function(){
    jQuery(this).parent('div').parent('div').next('.widget-inside').slideToggle();
  });
  
	/*
	 * Corrals
	 */
	jQuery('.ww-delete-corral').submit(function(){
			var ask = confirm('Whoa-there partner! Are you sure you want to delete this corral?');			
			return (ask) ? true : false;
	});
	
	jQuery('#ww-corrals-sort-list').sortable({
		update: function(event,ui){
			var all_corrals = jQuery('#ww-corrals-sort-list').children('.ww-corral-sort-item');
			
			jQuery.each(all_corrals, function(i){
					var weight_input = jQuery(this).children('.ww-corral-weight');
					var count = i+1;
					jQuery(weight_input).attr("name", "weight["+count+"]");
			});
		}
	}).disableSelection();
});
