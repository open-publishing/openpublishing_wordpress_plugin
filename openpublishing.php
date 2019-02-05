<?php
/*
Plugin Name: OpenPublishing
Description: Enrich content with data from the OpenPublishing service
Version: 1.5.3
Author: OpenPublishing GmbH
*/

require_once 'base.php';

if (get_option('openpublishing_realm_id') && get_option('openpublishing_api_host')) {
    add_filter( 'the_content', 'OpenPublishing\openpublishing_replace_tags' );
}
else {
    print('<span class="OP_debug" style="display:none;color:red;margin-left:5%;">Please configure Openpublishing plugin with API Host and Realm ID</span>');}
?>
