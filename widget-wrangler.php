<?php
/*
Plugin Name: Widget Wrangler
Plugin URI: http://www.daggerhart.com/widget-wrangler
Description: Widget Wrangler gives the wordpress admin a clean interface for managing widgets on a page by page basis.
It also provides widgets as a post type, and the ability to clone existing wordpress widgets.
Currently only supports one widgets sidebar. 
Author: Jonathan Daggerhart
Version: 1.0beta
Author URI: http://www.daggerhart.com
*/
/*  Copyright 2010  Jonathan Dagegrhart  (email : jonathan@daggerhart.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as 
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define('WW_PLUGIN_DIR', dirname(__FILE__));
define('WW_PLUGIN_URL', get_bloginfo('wpurl')."/wp-content/plugins/widget-wrangler");

include WW_PLUGIN_DIR.'/ww-widget-class.php';

// Initiate the plugin
add_action("init", "Widget_Wrangler_Init");
add_action('admin_menu', 'ww_menu');

// HOOK IT UP TO WORDPRESS
//include_once WW_PLUGIN_DIR.'/ww-includes.php';
add_action( 'admin_init', 'ww_admin_init' );
add_action( 'save_post', 'ww_save_post' );
add_action( 'admin_head', 'ww_admin_css');


/*
 * Initialize the plugin within the admin interface for Pages
 */

function Widget_Wrangler_Init() {
  global $ww;
  $ww = new Widget_Wrangler();
}
/*
 * Initialize the plugin onadmin screen
 */
function ww_admin_init()
{
  // Add panels into the editing sidebar(s)
  add_meta_box('ww_admin_meta_box', __('Widget Wrangler'), 'ww_admin_sidebar_panel', 'page', 'normal', 'high');
  
  // Add some CSS to the admin header
  if ($_REQUEST['action'] == 'edit' ||
      $_GET['page'] == 'ww-defaults'||
      $_GET['page'] == 'ww-debug'   ||
      $_GET['page'] == 'ww-new')
  {
    add_action('admin_head', 'ww_admin_js');
    add_action('admin_head', 'ww_admin_css');
  }
  //disable autosave
  //wp_deregister_script('autosave');
}
/*
 * All my hook_menu implementations
 */
function ww_menu()
{
  add_submenu_page( 'edit.php?post_type=widget', 'Default Widgets', 'Defaults', 'manage_options', 'ww-defaults', 'ww_defaults_page');
  add_submenu_page( 'edit.php?post_type=widget', 'Default Widgets', 'Clone WP Widget', 'manage_options', 'ww-clone', 'ww_clone_page');
  add_submenu_page( 'edit.php?post_type=widget', 'Debug Widgets', 'Debug', 'manage_options', 'ww-debug', 'ww_debug_page');
}

/*
 * for whatever.
 */
function ww_debug_page(){
  //global $wp_registered_widgets, $wp_registered_widget_controls, $wp_registered_widget_updates, $_wp_deprecated_widgets_callbacks;
  global $wp_widget_factory,$wp_registered_widgets;
  print "<pre>";
  print_r($wp_widget_factory);
  //print_r($wp_registered_widget_controls);
  print_r($wp_registered_widgets);
  print "</pre>";
}
/*
 * Handles creation of new cloned widgets, and displays clone new widget page
 */
function ww_clone_page()
{
  if($_GET['ww-clone-action'])
  {
    switch($_GET['ww-clone-action'])
    {
      case 'insert':
        $new_post_id = ww_clone_insert($_POST);
        wp_redirect(get_bloginfo('wpurl').'/wp-admin/post.php?post='.$new_post_id.'&action=edit');
        break;
    }
  }
  else
  {
    ww_clone_new_page();
  }

}
/*
 * Inserts a cloned WP widget as a WW widget
 */
function ww_clone_insert($posted)
{
  global $wpdb,$wp_widget_factory;
  
  //Start our outputs
  $this_class_name = '';
  $html_string = "\n// Widget HTML\n";
  $instance_string = "\n// Widget Settings\n";
  $parse_string = "<?php \n";
  
  if(isset($posted[$posted['ww-keyname']]))
  {
    $this_class_name = $posted['ww-classname'];
    foreach($posted[$posted['ww-keyname']] as $i => $settings)
    {
      //print_r($settings);
      //print " - ".$i;
      foreach($settings as $key => $value)
      {
        //print $key. " - ".$value."<br>";
        $instance[$key] = $value;
        $instance_string.= "\$instance['".$key."'] = '".$value."';\n";
      }
    }
  }
  $html_string.= "\$args['before_widget'] = '<div class=\"widget $widget_name\">';\n";
  $html_string.= "\$args['after_widget'] = '</div>';\n";
  $html_string.= "\$args['before_title'] = '<h2 class=\"widgettitle\">';\n";
  $html_string.= "\$args['after_title'] = '</h2>';\n";
  
  $parse_string.= $html_string.$instance_string;
  $parse_string.= "\n// Run Widget \nthe_widget('".$this_class_name."',\$instance,\$args); \n?>";
  
  //print_r($instance);
  $new_widget = array();
  $new_widget['post_author'] = 1; // for now
  if($instance['title'])
  {
    $new_widget['post_title'] = $instance['title'];
  }
  else
  {
    $new_widget['post_title'] = "Clone of ".$this_class_name;
  }
  $new_widget['post_excerpt'] = 'Cloned from '.$this_class_name;
  $new_widget['comment_status'] = 'closed';
  $new_widget['ping_status'] = 'closed';
  $new_widget['post_status'] = 'draft';
  $new_widget['post_type'] = 'widget';
  // post as meta values
  $new_meta['parse'] = $parse_string;
  $new_meta['adv_enabled'] = 'on';
  
  //print_r($new_widget);

  $format = array('%d','%s','%s','%s','%s','%s','%s');
  $wpdb->insert($wpdb->prefix."posts", $new_widget, $format);
  $new_post_id = $wpdb->insert_id;
  update_post_meta($new_post_id,'ww-parse', $new_meta['parse']);
  update_post_meta($new_post_id,'ww-adv-enabled', $new_meta['adv_enabled']);
  
  return $new_post_id;
  exit;
  
}
/*
 * Display widgets available for cloning.
 */
function ww_clone_new_page()
{
  global $wp_widget_factory,$wp_registered_widget_controls,$wp_registered_widget_updates,$wp_registered_widgets;
  $total_widgets = count($wp_widget_factory->widgets);
  $half = round($total_widgets/2);
  $i = 0;
  
  $output = "<h1>Clone a Wordpress Widget</h1>
              <p>Here you can clone an existing Wordpress widget into the Widget Wrangler system.</p>
              <script type='text/javascript'>
                jQuery(document).ready(function(){          
                  jQuery('.ww-clone-widget-click').click(function(){
                    jQuery(this).siblings('.ww-clone-widget-details').slideToggle();
                  });
                });
              </script>";
  $output.= "<ul class='ww-clone-widgets'>";
  foreach ($wp_widget_factory->widgets as $class_name => $widget)
  {
    $posted_array_key = "widget-".$widget->id_base;
    //print_r($widget);
    if ($i == $half)
    {
      $output.= "</ul><ul class='ww-clone-widgets'>";
    }
    ob_start();
    eval('$w = new '.$class_name.'(); $w->form(array());');
    $new_class = ob_get_clean();
    $output.= "<li>
                <div class='ww-clone-widget-click'>Clone</div>
                <h4>".$widget->name."</h4>
                <div class='ww-clone-widget-details'>            
                  <form action='edit.php?post_type=widget&page=ww-clone&ww-clone-action=insert&noheader=true' method='post'>
                    <input type='hidden' name='ww-classname' value='$class_name' />
                    <input type='hidden' name='ww-keyname' value='$posted_array_key' />
                    ".$new_class."
                    <input class='ww-clone-submit' type='submit' value='Create' />
                  </form>
                </div>
              </li>";
    
    $i++;
  }
  $output.= " </ul>";
  print $output;
  
}
/*
 * Javascript drag and drop for sorting
 */ 
function ww_admin_js()
{
	print '<script type="text/javascript" src="'.WW_PLUGIN_URL.'/ww-admin.js"></script>';
 print "<script type='text/javascript' src='".get_bloginfo('wpurl')."/wp-includes/js/jquery/ui.core.js'></script>
        <script type='text/javascript' src='".get_bloginfo('wpurl')."/wp-includes/js/jquery/ui.sortable.js'></script>";      
   
}
/*
 * Add css to admin interface
 */
function ww_admin_css()
{
	print '<link rel="stylesheet" type="text/css" href="'.WW_PLUGIN_URL.'/ww-admin.css" />';
}
/*
 * Produce the Default Widgets Page
 */
function ww_defaults_page()
{
  print "<h1>Default Widgets</h1>
        <p>Set the default widgets for your sidebar.</p>
        <div>";
          ww_default_page_widgets();
  print "</div>";
}
/*
 * Save widgts on the default page.  Stored as wp_option
 */
function ww_save_default_widgets($posted)
{
  $all_widgets = ww_get_all_widgets();
  // foreach registered widget, lets see if the checkbox has a value set ?
  foreach($all_widgets as $key => $widget)
  {
    $name = "ww-".$widget->post_name;
    $weight = "ww-".$widget->post_name."-weight";
    
    // if something was submitted without a weight, make it neutral
    if (!$weight = $_POST[$weight])
    {
      $weight = 0;
    }
    
    // if the box was checked, then it was posted.
    if($_POST[$name])
    {
      // Pattern: a[widget_name] = weight
      $active_widgets[$widget->ID] = $weight;
    }
  }
  /*
   * Assign true weights to avoid conflicts
   */
  $i = 1;
  if(is_array($active_widgets))
  {
    foreach ($active_widgets as $wwid => $old_weight)
    {
      $sorted_widgets[] = $i.":".$wwid;
      $i++;
    }
    $defaults = implode(",",$sorted_widgets);
    update_option('ww_default_widgets', $defaults);
  }
  else
  {
    delete_option('ww_default_widgets');
  }
    //print_r($defaults);
}
/*
 * Retrieve and theme the widgets on the Default Widgets Page
 */
function ww_default_page_widgets()
{
  // save defaults if posted
  if (isset($_POST['ww-save-default']))
  {
    ww_save_default_widgets($_POST);
  }
  
  $widgets = ww_get_all_widgets();
  $defaults_array = explode(",",get_option('ww_default_widgets'));
  $defaults = array();
  foreach($defaults_array as $wstring)
  {
    $temp = explode(":",$wstring);
    $defaults[$temp[0]] = $temp[1];
  }
  //print_r($defaults_array);
  
  $output = array();
  $output['open'] = "
            <div id='widget-wrangler-form' class='new-admin-panel' style='width: 50%;'>
              <form action='edit.php?post_type=widget&page=ww-defaults' method='post' name='widget-wrangler-form'>
              <div class='outer'>
                <input value='true' type='hidden' name='widget-wrangler-edit' />
                <input type='hidden' name='ww_noncename' id='ww_noncename' value='" . wp_create_nonce( plugin_basename(__FILE__) ) . "' />";

  // foreach widget, check to see if it has been selected
  foreach($widgets as $widget)
  {
    // check anything that has been checked before
    $checked = '';
    /*
     * If it is 'sorted' check it and put it at the top
     */
    if($weight = array_search($widget->ID,$defaults))
    {
      $checked = "checked='checked'";
      $output['active'][$weight] = "<li class='ww-item nojs' width='100%'>
                                      <input class='ww-widget-weight' name='ww-".$widget->post_name."-weight' type='text' size='2' value='$weight' />
                                      <input id='ww-".$widget->post_name."' class='ww-widget' name='ww-".$widget->post_name."' type='checkbox' value='".$widget->ID."' $checked />
                                      ".$widget->post_name." 
                                    </li>";
    } else { 
      $output['disabled'][] = "<li class='ww-item disabled nojs' width='100%'>
                                <input class='ww-widget-weight' name='ww-".$widget->post_name."-weight' type='text' size='2' value='$weight' disabled='disabled' />
                                <input id='ww-".$widget->post_name."' class='ww-widget' name='ww-".$widget->post_name."' type='checkbox' value='".$widget->ID."' $checked />
                                ".$widget->post_name." 
                              </li>";
    }
  }
  
  $output['close'] = " <!-- .inner -->
               </div><!-- .outer -->
               <input name='ww-save-default' type='submit' value='Save' />
               </form>
             </div>";
             
  // theme it out
  ww_theme_page_edit($output);
}
/*
 * Returns all published widgets
 */
function ww_get_all_widgets()
{
  global $wpdb;
  $query = "SELECT
              ID,post_name,post_title,post_content
            FROM
              ".$wpdb->prefix."posts
            WHERE
              post_type = 'widget' AND
              post_status = 'publish'";
  $widgets = $wpdb->get_results($query);
  
  $i=0;
  $total = count($widgets);
  while($i < $total)
  {
    $widgets[$i]->adv_enabled = get_post_meta($widgets[$i]->ID,'ww-adv-enabled',TRUE);
    $widgets[$i]->parse = get_post_meta($widgets[$i]->ID,'ww-parse', TRUE);
    $i++;
  }
  return $widgets;
}
/*
 * Retrieve and return a single widget by it's ID
 */
function ww_get_single_widget($post_id){
  global $wpdb;
  $query = "SELECT
              ID,post_name,post_title,post_content
            FROM
              ".$wpdb->prefix."posts
            WHERE
              post_type = 'widget' AND
              post_status = 'publish' AND
              ID = ".$post_id;
  $widget = $wpdb->get_row($query);
  $widget->adv_enabled = get_post_meta($widget->ID,'ww-adv-enabled',TRUE);
  $widget->parse = get_post_meta($widget->ID,'ww-parse', TRUE);
  return $widget;
}
function ww_single_widget_shortcode($atts)
{
  $short_array = shortcode_atts(array('id' => ''), $atts);
		//print_r($short_array);
  extract($short_array);
  $widget = ww_get_single_widget($id);
  return ww_theme_single_widget($widget);
}
add_shortcode('ww_widget','ww_single_widget_shortcode');
/*
 * Apply templating and parsing to a single widget
 */
function ww_theme_single_widget($widget)
{
  //print_r($widget);
  if($widget->adv_enabled)
  {
    $themed = ww_adv_parse_widget($widget);
  }else {
    $themed = ww_template_widget($widget);
  }
  return $themed;
}
  
/*
 * Look for possible custom templates, then default to widget-template.php
 */ 
function ww_template_widget($widget)
{
  ob_start();
  if (file_exists(TEMPLATEPATH . "/widget.php"))
  {
    include TEMPLATEPATH . "/widget.php";
  }
  else
  {
    include WW_PLUGIN_DIR. '/widget-template.php';
  }
  $templated = ob_get_clean();
  //ob_end_clean();
  
  return $templated;
}
/*
 * Handle the advanced parsing for a widget
 */
function ww_adv_parse_widget($widget)
{
  global $post;
  $page = $post;
  $pattern = array('/{{title}}/','/{{content}}/');
  $replace = array($widget->post_title,$widget->post_content);
  $parsed = preg_replace($pattern,$replace,$widget->parse);
  ob_start();
    eval('?>'.$parsed);
    $output = ob_get_clean();
  return $output;
}
/*
 * Display the widgets for this page
 */
function ww_display_widgets()
{
  global $post;
  $output = '';
  
  // lets get that custom field and break it into pieces
  if ($widgets_string = get_post_meta($post->ID, 'ww_post_widgets',TRUE))
  {
    $selected_widgets = ww_decode_widgets($widgets_string);
    /*
     * Run the functions / widgets
     */ 
    foreach($selected_widgets as $order => $wid)
    {
      $widget = ww_get_single_widget($wid);
      $output .= ww_theme_single_widget($widget);
    }
  }
  else
  {
    /*
     * Default widgets
     */
    $default_widgets = get_option('ww_default_widgets');
    $default_widgets = ww_decode_widgets($default_widgets);
    //print_r($default_widgets);
    foreach ($default_widgets as $order => $wid)
    {
      $widget = ww_get_single_widget($wid);
      $output .= ww_theme_single_widget($widget);
    }
  }
  print $output;
}
/*
 * Decode the widget strings
 */
function ww_decode_widgets($widgets_string = '')
{
  $sorted_widgets = array();
  /*
   * Take the comma separated list and 'explode' it into an array
   * Order of widgets is determined by order of input
   */
  $ww_widgets = explode(',',$widgets_string);
  // now foreach item in this array, lets see if we have a wigdet to show	
  foreach ($ww_widgets as $widget)
  {
    $temp = explode(':',$widget);
    // var[weight] = name
    $sorted_widgets[$temp[0]] = $temp[1];
  }
  ksort($sorted_widgets);
  //print_r($sorted_widgets);
  return $sorted_widgets;

}
/*
 * Provide exploding widget selection when editing a page
 */
function ww_admin_sidebar_panel($pid)
{
  // dirty hack to get post id, prob a better way.
  $pid = $_GET['post'];
  if (is_numeric($pid))
  {
    // get available widgets
    $ww_post_widgets = get_option('ww_post_widgets');
    // put into array
    $all_widgets = ww_get_all_widgets();   
    $sorted_widgets = array();   
    //get post meta for this post
    // array of chosen widgets //get post meta for this post
    if ($active = get_post_meta($pid,'ww_post_widgets',TRUE))
    {
      $active_array = ww_decode_widgets($active);
      $default_indicator = "Disabled";
      $default_bool = 1;
    }  
    elseif($default_widgets = get_option('ww_default_widgets'))
    {
      // pull default widgets 
      $default_array = ww_decode_widgets($default_widgets); //explode(",",$default_widgets);
     
      $total = count($default_array);
      if ($total > 0)
      {
        $weight = 1;
        while($weight <= $total)
        {
          $defaults[$weight] = get_the_title($default_array[$weight]);
          $weight++;
        }
      
        $default_string = implode(", ",$defaults);
        $default_indicator = "Enabled";
        $default_bool = 0;
      }
    }
    else
    {
      $default_string = '';
      $default_bool = 0;
      $default_indicator = 'Not Defined, click <a href="edit.php?post_type=widget&page=ww-defaults">here</a> to select your default widgets.';
    }
    // end defaults

        
    // build array for output
    $output = array();
    $output['open'] = "<div id='widget-wrangler-form' class='new-admin-panel'>
                  <div id='ww-defaults' style='border-bottom: 1px solid #666;padding-top: 8px;'>
                    Default Widgets are <span>".$default_indicator."</span>: <br />
                    <p style='padding-left: 20px;'><strong>".$default_string."</strong></p>
                  </div>
                <form method='POST' name='widget-wrangler-form'>
                <div class='outer'>
                  <input value='true' type='hidden' name='widget-wrangler-edit' />
                  <input type='hidden' name='ww_noncename' id='ww_noncename' value='" . wp_create_nonce( plugin_basename(__FILE__) ) . "' />";
    
    //print_r($all_widgets);
    // foreach widget, check to see if it has been selected
    foreach($all_widgets as $widget)
    {
      if($default_bool == 0)
      {
        $weight = 0;
      }
      else
      {
        $weight = array_search($widget->ID,$active_array);
      }
      // check anything that has been checked before
      $checked = '';
      /*
       * If it is 'sorted' check it and put it at the top
       */
      if($weight > 0)
      {
        $checked = "checked='checked'";
        //$enabled = get_post_meta($pid);
        $output['active'][$weight] = "<li class='ww-item nojs' width='100%'>
                                        <input class='ww-widget-weight' name='ww-".$widget->post_name."-weight' type='text' size='2' value='$weight' />
                                        <input id='ww-".$widget->post_name."' class='ww-widget' name='ww-".$widget->post_name."' type='checkbox' value='".$widget->ID."' $checked />
                                        ".$widget->post_title." 
                                      </li>";
      } else { 
        //$enabled = get_post_meta($pid);
        $output['disabled'][] = "<li class='ww-item disabled nojs' width='100%'>
                                  <input class='ww-widget-weight' name='ww-".$widget->post_name."-weight' type='text' size='2' value='$weight' disabled='disabled' />
                                  <input id='ww-".$widget->post_name."' class='ww-widget' name='ww-".$widget->post_name."' type='checkbox' value='".$widget->ID."' $checked />
                                  ".$widget->post_title." 
                                </li>";
      }
    }
    
    $output['close'] = " <!-- .inner -->
                 </div><!-- .outer -->
                 </form>
               </div>";
               
    ww_theme_page_edit($output);
  }  
}
/*
 * Make sure to show our plugin on the admin screen
 */
function ww_hec_show_dbx( $to_show )
{
  array_push( $to_show, 'widget-wrangler' );
  return $to_show;
}

/*
 * Theme the output for editing widgets on a page
 */
function ww_theme_page_edit($output)
{
  print $output['open'];
  print "<h5>Enabled</h5><ul id='ww-active-items' class='inner' width='100%'>";
  if (is_array($output['active']))
  {
    ksort($output['active']);
    foreach ($output['active'] as $active)
    {
      print $active;
    }
  }
  print "</ul>";
  
  print "<h5>Disabled</h5><ul id='ww-disabled-items' class='inner' width='100%'>";
  if (is_array($output['disabled']))
  {
    foreach ($output['disabled'] as $disabled)
    {
      print $disabled;
    }
  }
  print "</ul>";
  
  print $output['close'];
}


/*
 * Hook into saving a page
 * Save the post meta for this post
 */
function ww_save_post($id)
{
    // verify this came from the our screen and with proper authorization,
  // because save_post can be triggered at other times

  if ( !wp_verify_nonce( $_POST['ww_noncename'], plugin_basename(__FILE__) )) {
    return $id;
  }

  // verify if this is an auto save routine. If it is our form has not been submitted, so we dont want
  // to do anything
  if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
    return $id;
  }

  
  // Check permissions
  if ( 'page' == $_POST['post_type'] ) {
    if ( !current_user_can( 'edit_page', $id ) )
      return $id;
  } else {
    if ( !current_user_can( 'edit_post', $id ) )
      return $id;
  }

  // OK, we're authenticated: we need to find and save the data

  // get available widgets
  $ww_post_widgets = ww_get_all_widgets();//unserialize(get_option('ww_post_widgets'));
  //print_r($ww_post_widgets);
  // foreach registered widget, lets see if the checkbox has a value set ?
  foreach($ww_post_widgets as $key => $widget)
  {
    $name = "ww-".$widget->post_name;
    $weight = "ww-".$widget->post_name."-weight";
    
    // if something was submitted without a weight, make it neutral
    if (!$weight = $_POST[$weight])
    {
      $weight = 0;
    }
    
    // if the box was checked, then it was posted.
    if($_POST[$name])
    {
      // Pattern: a[widget_name] = weight
      $active_widgets[$widget->ID] = $weight;
    }
  }
  /*
   * Deal with submitted widgets
   */
  if ($active_widgets)
  {
    asort($active_widgets);
  
    /*
     * Get the defaults to compare before inserting
     */
    $default_widgets = get_option('ww_default_widgets');
    // pull default widgets 
    $default_array = unserialize($default_widgets);
    if (is_array($default_array))
    {
      $i = 1;
      // for string matching
      foreach ($default_array as $widget_wwid)
      {
        $default[] = $i.":".$widget_wwid;
        $i++;
      }
    }
    if((count($default)-1) > 0){
      $default_string = implode(",",$default);
    }
    else{
      $default_string = '';
    }
    
    /*
     * Assign true weights to avoid conflicts
     */
    $i = 1;
    //print_r($active_widgets);
    foreach ($active_widgets as $wwid => $old_weight)
    {
      $sorted_widgets[] = $i.":".$wwid;
      $i++;
    }
    
    if($sorted_widgets)
    {
      // Pattern: 1:second,2:first
      $data_string = implode(',',$sorted_widgets);
      //print $data_string;
      
      // if its using defaults, don't update the database
      if($data_string == $default_string)
      {
        delete_post_meta( $id, 'ww_post_widgets');
      }
      else
      {
        update_post_meta( $id, 'ww_post_widgets', $data_string);
      }
    }
  }
  else
  {
    delete_post_meta( $id, 'ww_post_widgets');
  }
}