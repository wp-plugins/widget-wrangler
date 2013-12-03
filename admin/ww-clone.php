<?php

/*
 * Handles creation of new cloned widgets, and displays clone new widget page
 */
function ww_clone_page_handler()
{
  if(isset($_GET['ww-clone-action'])){
    switch($_GET['ww-clone-action']){
      case 'insert':
        // create new cloned widget
        $new_post_id = ww_clone_insert($_POST);
        // goto new widget page
        wp_redirect(get_bloginfo('wpurl').'/wp-admin/post.php?post='.$new_post_id.'&action=edit');
        break;
    }
  }
  else{
    // show clone page
    ww_clone_form();
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
  $instance = array();
  
  if(isset($posted[$posted['ww-keyname']]))
  {
    $this_class_name = $posted['ww-classname'];
    foreach($posted[$posted['ww-keyname']] as $i => $settings)
    {
      foreach($settings as $key => $value)
      {
        $instance[$key] = $value;
      }
    }
  }
  
  // prep new widget info for saving
  $new_widget = array();
  $new_widget['post_author'] = 1; // for now
  $new_widget['post_title'] = ($instance['title']) ? $instance['title'] : "Clone of ".$this_class_name;
  $new_widget['post_excerpt'] = 'Cloned from '.$this_class_name;
  $new_widget['comment_status'] = 'closed';
  $new_widget['ping_status'] = 'closed';
  $new_widget['post_status'] = 'draft';
  $new_widget['post_type'] = 'widget';
  // Herb contributed fix for problem cloning
  $new_widget['post_content'] = '';
  $new_widget['to_ping'] = '';
  $new_widget['pinged'] = '';
  $new_widget['post_content_filtered'] = '';
  
  
  // insert new widget into db
  $new_post_id = wp_insert_post($new_widget);
  $instance['ID'] = $new_post_id;
  $instance['hide_title'] = '';
  
  // post as meta values
  add_post_meta($new_post_id,'ww-widget-type', 'clone');
  add_post_meta($new_post_id,'ww-clone-classname', $this_class_name);
  add_post_meta($new_post_id,'ww-clone-instance', $instance);
  
  return $new_post_id;
  exit;
}

/*
 * Read the clone instance data from WW Copy widget form
 */
function ww_make_clone_instance($posted){
	global $wp_widget_factory;
	$clone_class = $posted['ww-data']['clone']['clone-class'];
  $option_name = "widget-".$wp_widget_factory->widgets[$clone_class]->control_options['id_base'];
  $instance = array();
  
  // loop through instance values and create an instance array
	foreach($posted[$option_name] as $i => $settings){
		foreach($settings as $key => $value){
			$instance[$key] = $value;
		}
	}
  
	return $instance;
}
/*
 * Display widgets available for cloning.
 */
function ww_clone_form()
{
  global $wp_widget_factory,$wp_registered_widget_controls,$wp_registered_widget_updates,$wp_registered_widgets;
  $total_widgets = count($wp_widget_factory->widgets);
  $half = round($total_widgets/2);
  $i = 0;
  
  ?>
  <div class='wrap'>
    <h2>Clone a Wordpress Widget</h2>
    <p>Here you can clone an existing Wordpress widget into the Widget Wrangler system.</p>
    <ul class='ww-clone-widgets'>
    <?php
      foreach ($wp_widget_factory->widgets as $class_name => $widget)
      {
        $posted_array_key = "widget-".$widget->id_base;
        
        // break into 2 columns
        if ($i == $half)
        { ?>
          </ul><ul class='ww-clone-widgets'>
          <?php
        }
        
        ob_start();
        eval('$w = new '.$class_name.'(); $w->form(array());');
        $new_class_form = ob_get_clean();
        ?>
          <li>
            <div class='widget'>
              <div class='widget-top'>
                <div class='widget-title-action'>
                  <div class='widget-action'></div>
                </div>
                <h4><?php print $widget->name; ?></h4>
              </div>
              <div class='widget-inside'>            
                <form action='edit.php?post_type=widget&page=ww-clone&ww-clone-action=insert&noheader=true' method='post'>
                  <input type='hidden' name='ww-classname' value='<?php print $class_name; ?>' />
                  <input type='hidden' name='ww-keyname' value='<?php print $posted_array_key; ?>' />
                  <?php print $new_class_form; ?>
                  <input class='ww-clone-submit button button-primary button-large' type='submit' value='Create' />
                </form>
              </div>
            </div>
          </li>
        <?php
        $i++;
      }
    ?>
    </ul>
  </div>
  <?php
}
