<?php
function ww_theme_defaults_page()
{
  $defaults = get_option('ww_default_widgets');
  $defaults_array = array('');
  if(is_string($defaults))
  {
    $defaults_array = unserialize($defaults);
    //print_r($defaults_array);
  }  
  
  print "<div class='wrap'>
          <h2>Default Widgets</h2>
          <p>Set the default widgets for your sidebar.</p>";
          ww_default_page_widgets_brand_new($defaults_array);
  print "</div>";
}
/*
 * Save widgts on the default page.  Stored as wp_option
 */
function ww_save_default_widgets($posted)
{
  $all_widgets = ww_get_all_widgets();
  //$weight = 1;
  $i = 1;
 // print_r($posted);
 // exit;
  //print_r($posted);
  foreach($all_widgets as $key => $widget)
  {
    $name = $posted["ww-".$widget->post_name];
    $weight = $posted["ww-".$widget->post_name."-weight"];
    $sidebar = $posted["ww-".$widget->post_name."-sidebar"];
    
    //print $name." - ".$weight." / ".$sidebar."<br>";
    // if something was submitted without a weight, make it neutral
    if ($weight < 1)
    {
      $weight = $i;
    }
    if ($sidebar && $name)
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
    $defaults = serialize($active_widgets);
    update_option('ww_default_widgets', $defaults);
    return $active_array;
  }
  else
  {
    update_option('ww_default_widgets', 'N;');
    return array();
  }
  
}
/*
 * Retrieve and theme the widgets on the Default Widgets Page
 */
function ww_default_page_widgets_brand_new($defaults_array)
{
  //global $defaults_array;
  $widgets = ww_get_all_widgets();
  $sidebars = ww_get_all_sidebars();
  
  //print_r($widgets);
  //print_r($sidebars);
  //print_r($defaults);
 
  $output = array();
  $output['open'] = "
            <div id='widget-wrangler-form' class='new-admin-panel' style='width: 50%;'>
              <form action='edit.php?post_type=widget&page=ww-defaults&ww-defaults-action=update&noheader=true' method='post' name='widget-wrangler-form'>
              <div class='outer'>
                <input value='true' type='hidden' name='widget-wrangler-edit' />
                <input type='hidden' name='ww_noncename' id='ww_noncename' value='" . wp_create_nonce( plugin_basename(__FILE__) ) . "' />";
  
  $i = 1;
  foreach($widgets as $widget)
  {
    $keys = array_searchRecursive($widget->ID, $defaults_array);
    // $keys[0] = sidebar slug
    if ($keys[0])
    {
      $sidebar_slug = $keys[0];
      //$keys[1] = specific widget array
      $weight = $defaults_array[$sidebar_slug][$keys[1]]['weight'];
      
      //print $widget->post_name." / ".$weight." - weight <br>";
      
      // build select box
      $sidebars_options = "<option value='disabled'>Disabled</option>";
      foreach($sidebars as $slug => $sidebar)
      {
        ($slug == $sidebar_slug) ? $selected = "selected='selected'" : $selected = '';
        $sidebars_options.= "<option name='".$slug."' value='".$slug."' ".$selected.">".$sidebar."</option>";   
      }
      
      $output['active'][$sidebar_slug][$weight] = "<li class='ww-item nojs' width='100%'>
                                      <input class='ww-widget-weight' name='ww-".$widget->post_name."-weight' type='text' size='2' value='$weight' />
                                      <select name='ww-".$widget->post_name."-sidebar'>
                                      ".$sidebars_options."
                                      </select>
                                      <input class='ww-widget-name' name='ww-".$widget->post_name."' type='hidden' value='".$widget->post_name."' />
                                      <input class='ww-widget-id' name='ww-id-".$widget->ID."' type='hidden' value='".$widget->ID."' />
                                      ".$widget->post_title."
                                    </li>";
    }
    else
    {
      $sidebars_options = "<option value='disabled' selected>Disabled</option>";
      foreach($sidebars as $slug => $sidebar)
      {
        $sidebars_options.= "<option name='".$slug."'value='".$slug."'>".$sidebar."</option>";   
      }
      
      $output['disabled'][] = "<li class='ww-item disabled nojs' width='100%'>
                                <input class='ww-widget-weight' name='ww-".$widget->post_name."-weight' type='text' size='2' value='$weight' disabled='disabled' />
                                <select name='ww-".$widget->post_name."-sidebar'>
                                ".$sidebars_options."
                                </select>
                                <input class='ww-widget-name' name='ww-".$widget->post_name."' type='hidden' size='2' value='".$widget->post_name."' />
                                      <input class='ww-widget-it' name='ww-id-".$widget->ID."' type='hidden' value='".$widget->ID."' />
                                ".$widget->post_title." 
                              </li>";
    }
    $i++;
    //print_r($widget);
  }
  
  $output['close'] = " <!-- .inner -->
               </div><!-- .outer -->
               <input name='ww-save-default' type='submit' value='Save' />
               </form>
             </div>";
             
  foreach($output['active'] as $sidebar => $unsorted_widgets)
  {
    //print_r($unsorted_widgets);
    ksort($output['active'][$sidebar]);  
  }
  //print_r($output['active']);
  // theme it out
  ww_theme_page_edit($output);
}