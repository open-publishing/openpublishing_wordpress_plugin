<?php
/*
Plugin Name: OpenPublishing
Description: Enrich content with data from the OpenPublishing service
Version: 1.5.0
Author: OpenPublishing GmbH
*/

require_once 'base.php';

add_filter( 'the_content', 'OpenPublishing\openpublishing_replace_tags' );
?>
