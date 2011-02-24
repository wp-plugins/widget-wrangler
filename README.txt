=== Widget Wrangler ===
Contributors: daggerhart
Donate link: http://www.daggerhart.com/wordpress/widget-wrangler#donate
Tags: widget, admin, widgets, administration, sidebar
Requires at least: 3
Tested up to: 3.0.4
Stable tag: trunk

A plugin for managing the display of widgets on a page by page basis. Using widgets as a post type.

== Description ==

Widget Wrangler is a plugin for Wordpress that gives admin a clean interface for managing widgets on a page by page basis.
It's basically Drupal Blocks on every page, with the ability to set the default location of the widgets in specific sidebars.
Widget Wrangler provides widgets as a post type, gives you the ability to clone existing wordpress widgets, and provides shortcode support for individual widgets.
Now with multiple 'sidebars' (groups of widgets, like drupal regions)!

Screencasts

* [Getting Started Screencast](http://screencast.com/t/YjUwNDM3Zjk)
* [Basic Examples w/ Advanced Parsing Screencast](http://screencast.com/t/NjI2NDYzY)
* [Templating & PHP](http://screencast.com/t/YmI2Mjg1NT)


== Installation ==

1. Upload `widget-wrangler` to the `/wp-content/plugins/` directory
1. Activate the plugin through the `Plugins` menu in WordPress
1. Add a new sidebar under the Widgets menu
1. Begin adding widgets under the Widgets menu
1. Set default widgets on the defaults
1. Add the function 'ww_dynamic_sidebar('sidebar_slug');' to your template

== Frequently Asked Questions ==

= How do I display a single widget within a Page's content? =

Widget Wrangler comes with built in shortcode support for each widget.  To show a single widget in a content area, use the shortcode pattern of [ww_widget id={post_id}].
ie. [ww_widget id=240] where 240 is the post_id of the widget.

= How do I display my widgets? =

You must edit your template files and find any instance of the dynamic_sidebar() function.  Replace these functions with ww_dynamic_sidebar('name_of_sidebar')

= How can I control the widget's template (HTML)? =

In the widget-wrangler directory there is a file named 'widget-template.php'.  Copy this file to your theme's root directory and rename it 'widget.php'.  You can edit the HTML in the file to have widgets defaultly appear as you want.

= Can I use existing wordpress widgets? = 

Some of them.  With this plugin I am focusing on the post-Wordpress-3.0 users.  Widgets programmed for older versions of wordpress will likely not work.

= What does it mean to Clone a widget? =

When you clone a wordpress widget, it creates a new widget post in the Widget Wrangler system with the settings for the original wordpress widget pre-filled in the Advanced Parsing area of the new widget.


== Screenshots ==

1. Widget Page Manager
1. Individual Widget
1. Cloned Wordpress Widget

== Changelog ==
= 1.1rc6 =

* Fix for disappearing menu items with wordpress 3.1 update.  

= 1.1rc5 =

* IE 8 Bug fixes.
* Clone Widget widget name now filling into advanced parse correctly.

= 1.1rc4 =

* Bug fix from rc3 changes.   Capability for access to submenus corrected.

= 1.1rc3 =

* Added 'Auto Paragraph' checkbox for each widget
* Added a basic level of capability control.  Now possible to change capability type for use with other plugins.

= 1.1rc2 =

* Found another important bug related to recent changes. 

= 1.1rc1 =

* Fixed bug where disabled widgets disappear
* Fixed the need to save multiple times when enabling widgets
* Fixed disappearance of widgets assigned to deleted sidebars

= 1.1beta =
Initial Release

== Upgrade Notice ==

RC6 Fixes a bug with the menu items dissappearing after updating to Wordpress 3.1
