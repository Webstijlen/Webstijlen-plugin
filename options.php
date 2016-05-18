<?php 

// add the admin options page

add_action('admin_menu', 'plugin_admin_add_page');

function plugin_admin_add_page() {
  add_options_page('Custom Plugin Page', 'Custom Plugin Menu', 'manage_options', 'plugin', 'plugin_options_page');
}

?>
