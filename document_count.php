<?php

namespace Openpublishing\DocumentCount;


function update() {
    $HOST = 'https://' . get_option('openpublishing_api_host');
    $url = $HOST . '/rpc/v2/brand_statistics?method=document_count&brand_name=' . get_option('openpublishing_brand_name');
    $count_formatted = '0';

    $response = \Openpublishing\Fetch\openpublishing_get_with_auth($url, true);

    if ( is_wp_error( $response ) ) {
        error_log("[ERROR] " . $response->get_error_message() . ' ' . $url, 0);
    }
    else {
        $status = wp_remote_retrieve_response_code($response);
        if (200 == $status) {
            $number = json_decode(wp_remote_retrieve_body($response));
            $count_formatted = number_format($number, 0, ',', '.');
        }
        else {
            error_log("[ERROR]" . $url .' status - '. $status);
        }
    }

    update_option('openpublishing_document_count', $count_formatted);
}
?>
