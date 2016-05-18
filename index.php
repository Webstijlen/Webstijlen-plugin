<?php
/*
Plugin Name: Webstijlen All-in-1 Plugin
Description: Webstijlen All in one Plugin
More info: Meer informatie:
*/

add_action('wp_head', 'ga');
function ga() { ?>
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');
 
  ga('create', 'UA-76258001-1', 'auto');
  ga('send', 'pageview');
 
</script>
<?php } 
	
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Htaccess directive block xmlrcp for extra security.
 * Here are some rewrite examples:
 *   404 - RewriteRule xmlrpc\.php$ - [R=404,L]
 *   301 - RewriteRule ^xmlrpc\.php$ index.php [R=301]
 * If you want custom 404 make sure your server is finding it by also adding this 'ErrorDocument 404 /index.php?error=404' or 'ErrorDocument 404 /wordpress/index.php?error=404' for sites in subdirectory.
 */ 
add_filter('mod_rewrite_rules', 'noxmlrpc_mod_rewrite_rules'); // should we put this inside wp_loaded or activation hook
function noxmlrpc_mod_rewrite_rules($rules) {
  $insert = "RewriteRule xmlrpc\.php$ - [F,L]";
  $rules = preg_replace('!RewriteRule!', "$insert\n\nRewriteRule", $rules, 1);
  return $rules;
}

register_activation_hook(__FILE__, 'noxmlrpc_htaccess_activate');
function noxmlrpc_htaccess_activate() {
  flush_rewrite_rules(true);
}

register_deactivation_hook(__FILE__, 'noxmlrpc_htaccess_deactivate');
function noxmlrpc_htaccess_deactivate() {
  remove_filter('mod_rewrite_rules', 'noxmlrpc_mod_rewrite_rules');
  flush_rewrite_rules(true);
}


// Remove rsd_link from filters- link rel="EditURI"
add_action('wp', function(){
    remove_action('wp_head', 'rsd_link');
}, 9);


// Remove pingback from head (link rel="pingback")
if (!is_admin()) {      
    function link_rel_buffer_callback($buffer) {
        $buffer = preg_replace('/(<link.*?rel=("|\')pingback("|\').*?href=("|\')(.*?)("|\')(.*?)?\/?>|<link.*?href=("|\')(.*?)("|\').*?rel=("|\')pingback("|\')(.*?)?\/?>)/i', '', $buffer);
                return $buffer;
    }
    function link_rel_buffer_start() {
        ob_start("link_rel_buffer_callback");
    }
    function link_rel_buffer_end() {
        ob_flush();
    }
    add_action('template_redirect', 'link_rel_buffer_start', -1);
    add_action('get_header', 'link_rel_buffer_start');
    add_action('wp_head', 'link_rel_buffer_end', 999);
}


// Return pingback_url empty (<link rel="pingback" href>).
add_filter('bloginfo_url', function($output, $property){
    return ($property == 'pingback_url') ? null : $output;
}, 11, 2);


// Disable xmlrcp/pingback
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'pre_update_option_enable_xmlrpc', '__return_false' );
add_filter( 'pre_option_enable_xmlrpc', '__return_zero' );

// Disable trackbacks
add_filter( 'rewrite_rules_array', function( $rules ) {
    foreach( $rules as $rule => $rewrite ) {
        if( preg_match( '/trackback\/\?\$$/i', $rule ) ) {
            unset( $rules[$rule] );
        }
    }
    return $rules;
});


// Disable X-Pingback HTTP Header.
add_filter('wp_headers', function($headers, $wp_query){
    if(isset($headers['X-Pingback'])){
        unset($headers['X-Pingback']);
    }
    return $headers;
}, 11, 2);


add_filter( 'xmlrpc_methods', function($methods){
    unset( $methods['pingback.ping'] );
    unset( $methods['pingback.extensions.getPingbacks'] );
    unset( $methods['wp.getUsersBlogs'] ); // Block brute force discovery of existing users
    unset( $methods['system.multicall'] );
    unset( $methods['system.listMethods'] );
    unset( $methods['system.getCapabilities'] );
    return $methods;
});


// Just disable pingback.ping functionality while leaving XMLRPC intact?
add_action('xmlrpc_call', function($method){
    if($method != 'pingback.ping') return;
    wp_die(
        'This site does not have pingback.',
        'Pingback not Enabled!',
        array('response' => 403)
    );
});
