<?php
/*
Plugin Name: Widget Wrangler
Plugin URI: http://www.daggerhart.com/widget-wrangler
Description: Widget Wrangler gives the wordpress admin a clean interface for managing widgets on a page by page basis.
It also provides widgets as a post type, and the ability to clone existing wordpress widgets.
Author: Jonathan Daggerhart
Version: 1.1rc6
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
  $settings = ww_get_settings();
  $show_panel = false;
  
  if ($settings['capabilities'] == 'roles')
  {
    if (current_user_can('manage_widgets'))
    {
      $show_panel = true;
    }
  }
  else
  {
    $show_panel = true;
  }
  
  if ($show_panel)
  {  
    // Add panels into the editing sidebar(s)
    add_meta_box('ww_admin_meta_box', __('Widget Wrangler'), 'ww_admin_sidebar_panel', 'page', 'normal', 'high');
    // Add some CSS to the admin header
    if ($_REQUEST['action'] == 'edit' ||
        $_GET['page'] == 'ww-defaults'||
        $_GET['page'] == 'ww-debug'   ||
        $_GET['page'] == 'ww-new'     ||
        $_GET['page'] == 'ww-clone'     ||
        $_GET['page'] == 'ww-sidebars')
    {
      add_action('admin_head', 'ww_admin_js');
      add_action('admin_head', 'ww_admin_css');
    }
  }
  
  add_action('admin_head', 'ww_adjust_css');
  //disable autosave
  //wp_deregister_script('autosave');
}
/*
 * All my hook_menu implementations
 */
function ww_menu()
{
  add_submenu_page( 'edit.php?post_type=widget', 'Widget Sidebars', 'Sidebars', 'manage_options', 'ww-sidebars', 'ww_sidebars_page');
  add_submenu_page( 'edit.php?post_type=widget', 'Default Widgets', 'Set Defaults', 'manage_options', 'ww-defaults', 'ww_defaults_page');
  add_submenu_page( 'edit.php?post_type=widget', 'Clone WP Widget', 'Clone WP Widget', 'manage_options', 'ww-clone', 'ww_clone_page');
  add_submenu_page( 'edit.php?post_type=widget', 'Settings', 'Settings', 'manage_options', 'ww-settings', 'ww_settings_page');
  //add_submenu_page( 'edit.php?post_type=widget', 'Debug Widgets', 'Debug', 'manage_options', 'ww-debug', 'ww_debug_page');
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
function ww_adjust_css()
{
  print "<style type='text/css'>
         li#menu-posts-widget a.wp-has-submenu {
          letter-spacing: -1px;
         }";
  if ($_GET['post_type'] == 'widget')
  {
    print "#wpbody-content #icon-edit {
             background: transparent url('".WW_PLUGIN_URL."/images/wrangler_post_icon.png') no-repeat top left; 
           }";
  }
  print  "</style>";
}
/*
 * Add css to admin interface
 */
function ww_admin_css()
{
	print '<link rel="stylesheet" type="text/css" href="'.WW_PLUGIN_URL.'/ww-admin.css" />';
}
/*
 * Helper function for making sidebar slugs
 */
function ww_make_slug($string)
{
  return stripcslashes(preg_replace('/[\s_\'\"]/','_', strtolower(strip_tags($string))));
}

/*
 * for whatever.
 */
function ww_debug_page(){
  //global $wp_registered_widgets, $wp_registered_widget_controls, $wp_registered_widget_updates, $_wp_deprecated_widgets_callbacks;
  //global $wp_widget_factory,$wp_registered_widgets, $wpdb;
}

/* * * * * * * *
 * Page handling
 */
function ww_sidebars_page()
{
  include WW_PLUGIN_DIR.'/ww-sidebars.php';
  if($_GET['ww-sidebar-action'])
  {
    switch($_GET['ww-sidebar-action'])
    {
      case 'insert':
        $new_sidebar_id = ww_sidebar_insert($_POST);
        break;
      case 'delete':
        ww_sidebar_delete($_POST);
        break;
      case 'update':
        ww_sidebar_update($_POST);
        break;
      case 'sort':
        ww_sidebar_sort($_POST);
        break;
    }
    wp_redirect(get_bloginfo('wpurl').'/wp-admin/edit.php?post_type=widget&page=ww-sidebars');
  }
  ww_sidebars_create_form();
}
/*
 * Handles creation of new cloned widgets, and displays clone new widget page
 */
function ww_clone_page()
{
  include WW_PLUGIN_DIR.'/ww-clone.php';
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
 * Handles settings page
 */
function ww_settings_page()
{
  include WW_PLUGIN_DIR.'/ww-settings.php';
  if ($_GET['ww-settings-action'])
  {
    switch($_GET['ww-settings-action'])
    {
      case "save":
        ww_save_settings($_POST);
        break;
    }
    wp_redirect(get_bloginfo('wpurl').'/wp-admin/edit.php?post_type=widget&page=ww-settings');  
  }
  else
  {
    ww_edit_settings_page();    
  }
}

/*
 * Produce the Default Widgets Page
 */
function ww_defaults_page()
{
  include WW_PLUGIN_DIR."/ww-defaults.php";
  // save defaults if posted
  if ($_GET['ww-defaults-action'])
  {
    switch($_GET['ww-defaults-action'])
    {
      case 'update':
        $defaults_array = ww_save_default_widgets($_POST);
        break;
    }
    wp_redirect(get_bloginfo('wpurl').'/wp-admin/edit.php?post_type=widget&page=ww-defaults');
  }
  else
  {
    include WW_PLUGIN_DIR."/ww-sidebars.php";
    ww_theme_defaults_page();
  }
}
/* end page handling */

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
    $widgets[$i]->wpautop = get_post_meta($widgets[$i]->ID,'ww-wpautop', TRUE);
    $i++;
  }
  return $widgets;
}

/*
 * Retrieve and return a single widget by its ID
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
  $widget->wpautop = get_post_meta($widget->ID,'ww-wpautop', TRUE);
  return $widget;
}

/*
 * Shortcode support for all widgets
 */
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
  // maybe they don't want auto p ?
  if ($widget->wpautop == "on")
  {
    $widget->post_content = wpautop($widget->post_content);
  }
  // apply shortcode
  $widget->post_content = do_shortcode($widget->post_content);  
  
  //print_r($widget);
  if($widget->adv_enabled)
  {
    $themed = ww_adv_parse_widget($widget);
  }
  else
  {
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
  $replace = array($widget->post_title, $widget->post_content);
  $parsed = preg_replace($pattern,$replace,$widget->parse);
  ob_start();
    eval('?>'.$parsed);
    $output = ob_get_clean();
  return $output;
}

/*
 * Retrieve list of sidebars
 */
function ww_get_all_sidebars()
{
  if ($sidebars_string = get_option('ww_sidebars'))
  {
    $sidebars_array = unserialize($sidebars_string);
  }
  else
  {
    $sidebars_array = array('No Sidebars Defined');
  }
  return $sidebars_array;
}

/*
 * Output a sidebar
 */
function ww_dynamic_sidebar($sidebar_slug = 'default')
{
  // get the post and sidebars
  global $post;
  $sidebars = ww_get_all_sidebars();
  $output = '';
    // look for post meta
    if ($widgets_string = get_post_meta($post->ID,'ww_post_widgets', TRUE))
    {
      $widgets_array = unserialize($widgets_string);
    }
    else
    {
      // use defaults instead
      if ($defaults_string = get_option('ww_default_widgets'))
      {
        $widgets_array = unserialize($defaults_string);
      }
      else
      {
        // no widgets in post and no defaults
        return;
      }
    }
  
  if (is_array($widgets_array[$sidebar_slug]))
  {
    $i = 0;
    $total = count($widgets_array[$sidebar_slug]) - 1;
    
    usort($widgets_array[$sidebar_slug],'ww_cmp');
    $sorted_widgets = array_reverse($widgets_array[$sidebar_slug]);

    while($i <= $total)
    {
      $widget = ww_get_single_widget($widgets_array[$sidebar_slug][$i]['id']);
      $output.= ww_theme_single_widget($widget);
      $i++;
    }
  }
  print $output;
}

/*
 * Provide Widget Wrangler selection when editing a page
 */
function ww_admin_sidebar_panel($pid)
{
  // dirty hack to get post id, prob a better way.
  $pid = $_GET['post'];
  $temp = array();
  if (is_numeric($pid))
  {
    // put into array
    $all_widgets = ww_get_all_widgets(); 
    $sidebars = ww_get_all_sidebars();
    
    $sorted_widgets = array();
    
    // get post meta for this post
    // array of chosen widgets //get post meta for this post
    if ($active = get_post_meta($pid,'ww_post_widgets',TRUE))
    {
      $active_array = unserialize($active);
      $default_message = "Defaults are Disabled. This page is wrangling widgets on its own.";
      $default_bool = 1;
    }  
    elseif($default_widgets = get_option('ww_default_widgets'))
    {
      // pull default widgets 
      $active_array = unserialize($default_widgets); //explode(",",$default_widgets);
     
      $total = count($active_array);
      if ($total > 0)
      {
        $default_string = "<dl>";
        foreach($active_array as $sidebar => $temp_widgets)
        {
          if ($sidebar != 'disabled')
          {
            $default_string.= "<dt>".$sidebar." : </dt><dd>";
            foreach($temp_widgets as $widget)
            {
              $default_string.= " ".$widget['name'].",";
            }
            $default_string = rtrim($default_string, ",");
            $default_string.= "</dd>";
          }
        }
        $default_string.= "</dl>";
        $default_message = "This page is using the Defaults Widgets";
        $default_bool = 0;
      }
    }
    else
    {
      $default_string = '';
      $default_bool = 0;
      $default_message = 'Defaults are Not Defined, click <a href="/edit.php?post_type=widget&page=ww-defaults">here</a> to select your default widgets.';
    }
    // end defaults
 
  $output = array();
  $output['open'] = "
            <div id='widget-wrangler-form' class='new-admin-panel'>
                <div class='outer'>
                  <div id='ww-defaults'>
                    <p><span>".$default_message."</span></p>
                    <p>".$default_string."</p>
                  </div>
                  <input value='true' type='hidden' name='widget-wrangler-edit' />
                  <input type='hidden' name='ww_noncename' id='ww_noncename' value='" . wp_create_nonce( plugin_basename(__FILE__) ) . "' />";

  if (is_array($all_widgets))
  {
    $temp = ww_create_widget_list($all_widgets, $active_array, $sidebars);
    if(is_array($temp))
    {
      $output = array_merge($temp, $output);
    }
  }
  $output['close'] = " <!-- .inner -->
               </div><!-- .outer -->
             </div>";
  
  if ($output['active'])
  {
    foreach($output['active'] as $sidebar => $unsorted_widgets)
    {
      //print_r($unsorted_widgets);
      if ($output['active'][$sidebar])
      {
        ksort($output['active'][$sidebar]);
      }
    }
  }
  
  // theme it out
  ww_theme_page_edit($output);
  }  
}

/*
 * Put all widgets into a list for output
 */
function ww_create_widget_list($widgets, $ref_array, $sidebars)
{

    $i = 0;
    foreach($widgets as $widget)
    {
      // fix widgets with no title
      if ($widget->post_title == "")
      {
        $widget->post_title = "(no title) - Widget ID: ".$widget->ID;
      }
      
      $temp = array();
      $keys = array_searchRecursive($widget->ID, $ref_array);
      
      if ($keys[0] == '' || (!array_key_exists($keys[0], $sidebars)))
      {
        $keys[0] = "disabled";
      }
      
      // setup initial info
      $sidebar_slug = $keys[0];
      ($sidebar_slug == 'disabled') ? $disabled = "disabled='disabled'" : $disabled = '';
      // get weight
        //$keys[1] = specific widget array
      $weight = $ref_array[$sidebar_slug][$keys[1]]['weight'];
      
      
      // build select box
      $sidebars_options = "<option value='disabled'>Disabled</option>";
      foreach($sidebars as $slug => $sidebar)
      {
        ($slug == $sidebar_slug) ? $selected = "selected='selected'" : $selected = '';
        $sidebars_options.= "<option name='".$slug."' value='".$slug."' ".$selected.">".$sidebar."</option>";   
      }
      
      $temp[$weight] = "<li class='ww-item ".$sidebar_slug." nojs' width='100%'>
                                      <input class='ww-widget-weight' name='ww-".$widget->post_name."-weight' type='text' size='2' value='$weight' $disabled />
                                      <select name='ww-".$widget->post_name."-sidebar'>
                                      ".$sidebars_options."
                                      </select>
                                      <input class='ww-widget-name' name='ww-".$widget->post_name."' type='hidden' value='".$widget->post_name."' />
                                      <input class='ww-widget-id' name='ww-id-".$widget->ID."' type='hidden' value='".$widget->ID."' />
                                      ".$widget->post_title."
                                    </li>";
      // place into output array
      if ($sidebar_slug == 'disabled')
      {
        $output['disabled'][] = $temp[$weight];
      }
      else
      {
        $output['active'][$sidebar_slug][$weight] = $temp[$weight];
      }
      
      $i++;
    }
    return $output;
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
  $sidebars = ww_get_all_sidebars();
  $new_output = $output['open'];
  if (is_array($sidebars))
  {
    foreach($sidebars as $slug => $sidebar)
    {
      $new_output.= "<h4>".$sidebar."</h4>";
      $new_output.= "<ul name='".$slug."' id='ww-sidebar-".$slug."-items' class='inner ww-sortable' width='100%'>";
      
      if (is_array($output['active'][$slug]))
      {
        foreach($output['active'][$slug] as $item)
        {
          $new_output.= $item;
        }
        $style = "style='display: none;'";
      }
      else
      {
        $style = '';
      }
      $new_output.= "<li class='ww-no-widgets' ".$style.">No Widgets in this sidebar.</li>";
      $new_output.= "</ul>";
    }
  }
  
  $new_output.= "<h4>Disabled</h4><ul name='disabled' id='ww-disabled-items' class='inner ww-sortable' width='100%'>";
  if (is_array($output['disabled']))
  {
    foreach ($output['disabled'] as $disabled)
    {
      $new_output.= $disabled;
    }
    $style = "style='display: none;'";
  }
  else
  {
    $style = '';
  }
  $new_output.= "<li class='ww-no-widgets' ".$style.">No disabled Widgets</li>";
  $new_output.= "</ul>";
  
  $new_output.= $output['close'];
  
  print $new_output;
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

  $all_widgets = ww_get_all_widgets();
  
  $i = 1;

  foreach($all_widgets as $key => $widget)
  {
    $name = $_POST["ww-".$widget->post_name];
    $weight = $_POST["ww-".$widget->post_name."-weight"];
    $sidebar = $_POST["ww-".$widget->post_name."-sidebar"];
    
    //print $name." - ".$weight." / ".$sidebar."<br>";
    // if something was submitted without a weight, make it neutral
    if ($weight < 1)
    {
      $weight = $i;
    }
    if (($sidebar && $name) &&
        ($sidebar != 'disabled'))
    {
      $active_widgets[$sidebar][] = array(
            'id' => $widget->ID,
            'name' => $widget->post_title,
            'weight' => $weight,
            );
    }
    $i++;
  }
  //print_r($active_widgets);
  //exit;
  /*
   * Assign true weights to avoid conflicts
   */
  if(is_array($active_widgets))
  {
    $this_post_widgets = serialize($active_widgets);
    update_post_meta( $id, 'ww_post_widgets', $this_post_widgets);
  }
  else
  {
    delete_post_meta( $id, 'ww_post_widgets');
  }
}

/*
 * Settings
 */
function ww_get_settings()
{
  if ($settings = get_option("ww_settings"))
  {
    return unserialize($settings);
  }
  else
  {
    ww_set_default_settings();
    
    return ww_get_settings();
  }
}

function ww_set_default_settings()
{
  $settings["capabilities"] = "simple";
  
  update_option("ww_settings", serialize($settings));
}

/*
 * Extra helper functions I likely stole from somewhere
 */
// helps find the weight.. or something
function ww_cmp($a,$b) {
  if ($a['weight'] == $b['weight']) return 0;
  return ($a['weight'] < $b['weight'])? -1 : 1;
}

// recursive array search
function array_searchRecursive( $needle, $haystack, $strict=false, $path=array() )
{
    if( !is_array($haystack) ) {
        return false;
    }
 
    foreach( $haystack as $key => $val ) {
        if( is_array($val) && $subPath = array_searchRecursive($needle, $val, $strict, $path) ) {
            $path = array_merge($path, array($key), $subPath);
            return $path;
        } elseif( (!$strict && $val == $needle) || ($strict && $val === $needle) ) {
            $path[] = $key;
            return $path;
        }
    }
    return false;
}