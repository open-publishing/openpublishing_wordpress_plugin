<?php
/**
 * Plugin Name:       OpenPublishing
 * Plugin URI:        https://de.wordpress.org/plugins/openpublishing/
 * Description:       Enrich content with data from the Open Publishing service
 * Version:           2.0
 * Requires at least: 5.0
 * Requires PHP:      7.0
 * Author:            Open Publishing GmbH
 * Author URI:        https://openpublishing.com/
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once plugin_dir_path( __FILE__ ) . 'includes/base.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/document_count.php';

define('OPENPUBLISHING_DISPLAY_MAX', 10);
define('OPENPUBLISHING_LEGACY_COLLECTION_OBJECTS', array('bestseller', 'newest', 'most_read'));
define('OPENPUBLISHING_LEGACY_OBJECTS', array_merge(OPENPUBLISHING_LEGACY_COLLECTION_OBJECTS, array('document')));

if (get_option('openpublishing_realm_id') && get_option('openpublishing_api_host')) {
    add_filter( 'the_content', 'OpenPublishing\openpublishing_replace_shortcodes' );
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
