<?php
class Widget_Wrangler {
var $meta_fields = array("ww-adv-enabled","ww-parse");
  
  /*
   * Constructor, build the new post type
   */
  function Widget_Wrangler()
  {
    $labels = array(
      'name' => _x('Widgets', 'post type general name'),
      'singular_name' => _x('Widget', 'post type singular name'),
      'add_new' => _x('Add New', 'widget'),
      'add_new_item' => __('Add New Widget'),
      'edit_item' => __('Edit Widget'),
      'new_item' => __('New Widget'),
      'view_item' => __('View Widget'),
      'search_items' => __('Search Widgets'),
      'not_found' =>  __('No widgets found'),
      'not_found_in_trash' => __('No widgets found in Trash'), 
      'parent_item_colon' => ''
    );

    // Register custom post types
    register_post_type('widget', array(
      'labels' =>$labels,
      'public' => true,
      'show_ui' => true, // UI in admin panel
      '_builtin' => false, // It's a custom post type, not built in
      '_edit_link' => 'post.php?post=%d',
      'capability_type' => 'post',
      'hierarchical' => false,
      'rewrite' => array("slug" => "widget"), // Permalinks
      'query_var' => "widget", // This goes to the WP_Query schema
      'supports' => array('title','excerpt','editor' /*,'custom-fields'*/) // Let's use custom fields for debugging purposes only
    ));
   
    add_filter("manage_edit-widget_columns", array(&$this, "edit_columns"));
    add_action("manage_posts_custom_column", array(&$this, "custom_columns"));
   
    // Admin interface init
    add_action("admin_init", array(&$this, "admin_init"));
    //add_action("template_redirect", array(&$this, 'template_redirect'));
    
    // Insert post hook
    add_action("wp_insert_post", array(&$this, "wp_insert_post"), 10, 2);
  }
  /*
   * Custom columns for the main Widgets mangement page
   */  
  function edit_columns($columns)
  {
    $columns = array(
      "cb" => "<input type=\"checkbox\" />",
      "title" => "Widget Title",
      "ww_description" => "Description",
      "ww_adv_enabled" => "Adv Parse",
    );
   
    return $columns;
  }
  /*
   * Handler for custom columns
   */
  function custom_columns($column)
  {
    global $post;
    switch ($column)
    {
      case "ww_description":
       the_excerpt();
       break;
      case "ww_adv_enabled":
       $custom = get_post_custom();
       echo $custom["ww-adv-enabled"][0];
       break;
    }
  }

  
  /*
   * When a post is inserted or updated
   */ 
  function wp_insert_post($post_id, $post = null)
  {
    if ($post->post_type == "widget")
    {
      // Loop through the POST data
      foreach ($this->meta_fields as $key)
      {
        $value = @$_POST[$key];
        if (empty($value))
        {
         delete_post_meta($post_id, $key);
         continue;
        }
     
        // If value is a string it should be unique
        if (!is_array($value))
        {
          // Update meta
          if (!update_post_meta($post_id, $key, $value))
          {
           // Or add the meta data
           add_post_meta($post_id, $key, $value);
          }
        }
        else
        {
          // If passed along is an array, we should remove all previous data
          delete_post_meta($post_id, $key);
          
          // Loop through the array adding new values to the post meta as different entries with the same name
          foreach ($value as $entry){
            add_post_meta($post_id, $key, $entry);
          }
        }
      }
    }
  }
  /*
   * Add meta box to widget posts
   */
  function admin_init() 
  {
    // Custom meta boxes for the edit widget screen
    add_meta_box("ww-parse", "Advanced Parsing", array(&$this, "meta_parse"), "widget", "normal", "high");
  }
  
  // Admin post meta contents
  function meta_parse()
  {
    global $post;
    $custom = get_post_custom($post->ID);
    $parse = $custom["ww-parse"][0];
    $adv_enabled = $custom["ww-adv-enabled"][0];
    (isset($adv_enabled)) ? $checked = 'checked' : $checked = '';
    ?><div id="template">
        <label><input type="checkbox" name="ww-adv-enabled" <?php print $checked; ?> /> Enable Advanced Parsing</label>
        <br />
        <textarea name="ww-parse" cols="40" rows="16" style="width: 100%;"><?php print $parse; ?></textarea>
        <div class="adv-parse-description">
          <h5>Here you can:</h5>
          <ul>
            <li>Use PHP</li>
            <li>Use {{title}} and {{content}} tags to insert the widget's title or content</li>
            <li>Access the $post object for data concerning the page being displayed</li>
            <li>Access the $widget object for more widget data</li>
          </ul>
        </div>
      </div>
    <?php
  }
}
// end widget class
