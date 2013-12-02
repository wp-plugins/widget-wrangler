<?php

/*
 * Template wrangler hook
 */
function ww_display_templates($templates)
{
  // template applied by files
  $templates['ww_widget'] = array(
    // string of single template suggestion
    // or array of multiple template suggestions
    'files' => array(
        // use argument keys as replacable patterns with the argument's value
        // order is important as the order here is the order inwhich templates are searched for
				//'widget-corral_[corral_id]-[widget_type]-[widget_id].php',
				'widget-[widget_type]-[widget_id].php',
        'widget-[widget_id].php',
        'widget-[post_name].php',
        //'widget-corral_[corral_id]-[widget_type].php',
        'widget-[widget_type].php',
        //'widget-corral_[corral_id].php',
				'widget.php'
    ),

    // location of the default template if nothing is found
    'default_path' => WW_PLUGIN_DIR.'/templates',

    // optional arguments to be passed to the themeing function
    'arguments' => array(
        // must be key => value pairs
				'widget' => NULL,
        'widget_id' => 0,
				//'corral_id' => 0,
        'widget_type' => '',
        'post_name' => '',
    ),
  );
/*	
  $templates['ww_corral'] = array(
    'files' => array(
			'corral-[corral_id].php',
			'corral.php'
    ),
    'default_path' => dirname(__FILE__).'/templates',
    'arguments' => array(
				'corral_id' => 0,
    ),
  );
*/	
  return $templates;
}
add_filter('tw_templates', 'ww_display_templates');

/*
 * Apply templating and parsing to a single widget
 * @return themed widget for output or templating
 */
function ww_theme_single_widget($widget)
{
  // clone
  if ($widget->widget_type == "clone"){
    $widget->clone_instance['post_name'] = $widget->post_name;
    $widget->clone_instance['ww_widget'] = $widget;
    $themed = ww_the_widget($widget->clone_classname, $widget->clone_instance);
  }
  // standard
  else
  {
    // maybe they don't want auto p ?
    if ($widget->wpautop == "on"){
      $widget->post_content = wpautop($widget->post_content);
    }
  
    // apply shortcode
    $widget->post_content = do_shortcode($widget->post_content);
  
    // see if this should use advanced parsing
    if($widget->adv_enabled){
      $themed = ww_adv_parse_widget($widget);
    }
    else{
      $themed = ww_template_widget($widget);
    }
  }

  return $themed;
}

/*
 * Look for possible custom templates, then default to widget-template.php
 * @return templated widget
 */
function ww_template_widget($widget)
{
	$output = '';
	//$corral_id = (isset($widget->in_corral) && isset($widget->corral['id']) && $widget->in_corral == true) ? $widget->corral['id'] : 0;
	
	$args = array(
		'widget' => $widget, // needed in final template
		'widget_id' => $widget->ID,
		'widget_type' => $widget->widget_type,
    'post_name' => $widget->post_name,
		//'corral_id' => $corral_id,
	);
  
  // preview bypasses the theme compatibility assignment
  if (!isset($widget->theme_compat)){
    $widget->theme_compat = 0;
  }
  
  // theme compatibility
  // remove post title from templating
  // and include it manually later
  if ($widget->theme_compat){
    $title = $widget->post_title;
    $widget->post_title = NULL;
  }
  
  // template-wrangler.inc
  $output = theme('ww_widget', $args);
    
  // handle final theme compat issues
  if ($widget->theme_compat){
    $theme_compat =  $widget->wp_widget_args['before_widget'];
    // title can also be NULL with clones
    if ($title) {
      $theme_compat.= $widget->wp_widget_args['before_title'] .
                        $title .
                      $widget->wp_widget_args['after_title'];
    }
    $theme_compat.= $output . $widget->wp_widget_args['after_widget'];
    $output = $theme_compat;
  }

	return $output;  
}

/*
 * Handle the advanced parsing for a widget
 * @return advanced parsed widget
 */
function ww_adv_parse_widget($widget)
{
  // make $post and $page available
  global $post;
  $page = $post;
  
  // find and replace title and content tokens
  // this should happen after eval() to prevent code-like content from attempting to execute
  // use str_replace to avoid $<digits> issue with preg_replace
  // replace \$ with $ for backwards compat w/ users who have added their own backslashes
  $search = array('{{title}}','{{content}}', '\$');
  $replace = array($widget->post_title, $widget->post_content, '$');
  
  // handle advanced templating
  if($widget->adv_template)
  {
    $returned_array = eval('?>'.$widget->parse);
    if (is_array($returned_array))
    {
      // only change values if passed into returned array
      if (isset($returned_array['title'])) {
        // tokens
        $returned_array['title'] = str_replace($search, $replace, $returned_array['title']);
        $widget->post_title = $returned_array['title'];
      }
      if (isset($returned_array['content'])) {
        // tokens
        $returned_array['content'] = str_replace($search, $replace, $returned_array['content']);
        $widget->post_content = $returned_array['content'];
      }
      $output = ww_template_widget($widget);
    }
    else {
      $output = "<!-- Error:  This widget did not return an array. -->";
    }
  }
  else
  {
    // execute adv parsing area - no advanced templating
    ob_start();
      eval('$instance["ww_widget"] = $widget; ?>'.$widget->parse);
		$output = ob_get_clean();
    
    // theme compatibility
    // adv parse w/o templating doesn't have separate title
    if ($widget->theme_compat){
      $output = $widget->wp_widget_args['before_widget'].$output.$widget->wp_widget_args['after_widget'];
    }
    // tokens
    $output = str_replace($search, $replace, $output);
    
		// fix for recent post widget not resetting the query
		$post = $page;
  }
      
  return $output;
}

/**
 * Taken from wp-includes/widgets.php, adjusted for my needs
 *
 * @param string $wp_widget_class the widget's PHP class name (see default-widgets.php)
 * @param array $instance the widget's instance settings
 * @return void
 **/
function ww_the_widget($wp_widget_class, $instance = array())
{
  // load widget from widget factory ?
  global $wp_widget_factory;
  $wp_widget_class_obj = $wp_widget_factory->widgets[$wp_widget_class];

  // get as much ww widget data as possible 
  $ww_widget = (isset($instance['ww_widget'])) ? $instance['ww_widget'] : ww_get_single_widget($instance['ID']);
  
  if ( !is_a($wp_widget_class_obj, 'WP_Widget') )
   return;

  // args for spliting title from content
  $args = array('before_widget'=>'','after_widget'=>'','before_title'=>'','after_title'=>'[eXpl0de]');

  // output to variable for replacements
  ob_start();
     $wp_widget_class_obj->widget($args, $instance);
  $temp = ob_get_clean();

  // get title and content separate
  $array = explode("[eXpl0de]", $temp);

  // prep object for template
  $ww_widget->post_title    = ($array[0]) ? $array[0]: $instance['title'];
  $ww_widget->post_content  = $array[1];

  if (isset($instance['hide_title']) && $instance['hide_title']){
    $ww_widget->post_title = NULL;
  }

  // template with WW template
  print ww_template_widget($ww_widget);
}
