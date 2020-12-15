<?php
namespace Openpublishing\Fetch;

// When authorization request fails retry 1 time
$GLOBALS['openpublishing_auth_retry'] = true;

/**
 * @return string
 */
function openpublishing_get_auth_token() {
    $new_token = '';
    $HOST = 'https://' . get_option('openpublishing_api_host') . '/auth/auth?';
    $REALM_ID = get_option('openpublishing_realm_id');
    $url = $HOST . 'realm_id=' . $REALM_ID . '&type=world';

    $response = wp_remote_get($url);
    if ( is_wp_error( $response ) ) {
        error_log("[ERROR] " . $response->get_error_message() . ' ' . $url);
    } else {
            $status = wp_remote_retrieve_response_code( $response );
            if ( $status === 200 ) {
                    $json = json_decode(wp_remote_retrieve_body($response));
                    $new_token = $json->{'auth_token'};
                    update_option('openpublishing_auth_token', $new_token);
                } else {
                    error_log("[ERROR]" . $url .' - '. $status);
            }
    }
    return $new_token;
}

/**
 * @param string $url
 * @param bool $try_again
 * @return array|\WP_Error
 */
function openpublishing_get_with_auth($url, $try_again) {
    $token = get_option('openpublishing_auth_token');
    if (!$token) {
        $token = openpublishing_get_auth_token();
    }
    $options = array( 'headers' => array( 'Authorization' => 'Bearer ' . $token));
    $response = wp_remote_get($url, $options);

    if ( is_wp_error( $response ) ) {
        error_log("[ERROR] failed to get " . $url);

        if ($try_again) {
             $response = openpublishing_get_with_auth($url, false);
             update_option('openpublishing_auth_token', '');
        }
    }
    return $response;
}

/**
 * @param string $url
 * @return array
 */
function openpublishing_fetch_objects(string $url) {
    $response = openpublishing_get_with_auth($url, true);
    if ( is_wp_error( $response ) ) {
        $error = $response->get_error_message() . "\t" . $url;
        error_log('[ERROR] ' . $error);
        \Openpublishing\openpublishing_print_debug('[ERROR] ' . $error);
    } else {
        $status = wp_remote_retrieve_response_code($response);

        if ( 200 == $status ) {
            return json_decode(wp_remote_retrieve_body($response));
        }
        $error = '[Status-Code] ' . $status . "\t" . $url;
    }
    return ['ERROR' => $error];
}

/**
 * @param string $object_name
 * @param int $id
 * @param string $lang
 * @param bool $is_collection
 * @return string
 */
function openpublishing_legacy_generate_api_url($object_name, $id, $lang, $is_collection = false) {
    $base_url = 'https://' . get_option('openpublishing_api_host') . '/resource/v2/';
    $ASPECT = '[:basic,non_academic.realm_genres.*]';
    $OBJECT = 'document';
    $guid = $object_name . '.' . $id;

    $language = $lang ? '?language=' . $lang : '';

    if ($is_collection) {
        $url = $base_url.$OBJECT.$ASPECT.'?sort='.$object_name.'__asc&cache=yes&display=10'. $language;
    }
    else {
        $url = $base_url . $guid . $ASPECT . $language;
    }
    \Openpublishing\openpublishing_print_debug('<b>'.$object_name.':'.$id.($lang?':'.$lang:'').'</b></br>'.$url);

    return $url;
}

/**
 * @param array $shortcode_data
 * @return string
 */
function openpublishing_generate_api_url(array $shortcode_data) {
    $base_url = 'https://' . get_option('openpublishing_api_host') . '/resource/v2/document';
    $ASPECT = '[:basic,non_academic.realm_genres.*]';

    if (empty($shortcode_data['filters'])) {
        $filter_query = '';
    } else {
        $filters = array_map('urlencode', $shortcode_data['filters']);
        $filter_query = '&' . http_build_query($filters);
    }

    if ( empty($shortcode_data['get_by_id']) ) {
        $sort = $shortcode_data['sort'] ?
            '&sort=' . urlencode($shortcode_data['sort'] . '__' . $shortcode_data['order']) : '';
        $url = $base_url . $ASPECT . '?cache=yes&display=' . intval($shortcode_data['limit']) . $sort . $filter_query;
    }
    else {
        $url = $base_url . '.' . intval($shortcode_data['get_by_id']) . $ASPECT . $filter_query . '&cache=yes';
    }
    \Openpublishing\openpublishing_print_debug('<b>' . $shortcode_data['replacer'] . ' : ' . '</b></br>' . $url);

    return $url;
}
