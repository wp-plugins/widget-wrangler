<?php

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
  $html_string.= "\$args['before_widget'] = '<div class=\"widget ".strtolower($this_class_name)."\">';\n";
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
  
  $output = "<div class='wrap'>
              <h2>Clone a Wordpress Widget</h2>
              <p>Here you can clone an existing Wordpress widget into the Widget Wrangler system.</p>";
              
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
                <div class='widget'>
                  <div class='widget-top'>
                    <div class='widget-title-action'>
                      <div class='widget-action'></div>
                    </div>
                    <h4>".$widget->name."</h4>
                  </div>
                  <div class='widget-inside'>            
                    <form action='edit.php?post_type=widget&page=ww-clone&ww-clone-action=insert&noheader=true' method='post'>
                      <input type='hidden' name='ww-classname' value='$class_name' />
                      <input type='hidden' name='ww-keyname' value='$posted_array_key' />
                      ".$new_class."
                      <input class='ww-clone-submit' type='submit' value='Create' />
                    </form>
                  </div>
                </div>
              </li>";
    
    $i++;
  }
  $output.= " </ul></div>";
  print $output;
  
}
