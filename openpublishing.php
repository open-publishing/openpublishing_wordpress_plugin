<?php
/**
 * Plugin Name: OpenPublishing
 * Description: Enrich content with data from the OpenPublishing service
 * Version: 1.7
 * Author: OpenPublishing GmbH
 */

require_once 'base.php';
require_once 'document_count.php';

define('OPENPUBLISHING_COLLECTION_OBJECTS', array('bestseller', 'newest', 'most_read'));
define('OPENPUBLISHING_OBJECTS', array_merge(OPENPUBLISHING_COLLECTION_OBJECTS, array('document')));

if (get_option('openpublishing_realm_id') && get_option('openpublishing_api_host')) {
    add_filter( 'the_content', 'OpenPublishing\openpublishing_replace_tags' );
    add_shortcode( 'openpublishing', 'Openpublishing\Cache\openpublishing_add_shortcodes_to_cache' );
}
else {
    error_log("[ERROR] " . 'Please configure Openpublishing plugin with API Host and Realm ID and Brand name');
    print('<span class="OP_debug" style="display:none;color:red;margin-left:5%;">Please configure Openpublishing plugin with API Host and Realm ID</span>');
}


// Schedule cron task for daily fetch of document count
register_activation_hook(__FILE__, 'openpublishing_activation');
function openpublishing_activation() {
    wp_clear_scheduled_hook( 'openpublishing_daily_document_count_fetch' );
    wp_schedule_event( time(), 'daily', 'openpublishing_daily_document_count_fetch');
}

add_action('openpublishing_daily_document_count_fetch', 'OpenPublishing\DocumentCount\update');

register_deactivation_hook( __FILE__, 'openpublishing_deactivation');
function openpublishing_deactivation() {
    wp_clear_scheduled_hook('openpublishing_daily_document_count_fetch');
}
