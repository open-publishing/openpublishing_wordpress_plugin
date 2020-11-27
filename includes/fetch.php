<?php
namespace Openpublishing\Fetch;

// When authorization request fails retry 1 time
$GLOBALS['openpublishing_auth_retry'] = true;

function openpublishing_print_debug($msg) {
    print('<span class="OP_debug" style="display:none;">' . $msg . '<br></span>');
}

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
        error_log("[ERROR] " . $response->get_error_message() . ' ' . $url);
        openpublishing_print_debug("[ERROR] " . $response->get_error_message() . ' ' . $url);
    } else {
        $status = wp_remote_retrieve_response_code($response);

        if ( 200 == $status ) {
            return json_decode(wp_remote_retrieve_body($response));
        }
    }
}

/**
 * @param string $object_name
 * @param int $id
 * @param string $lang
 * @param bool $is_collection
 * @return string
 */
function openpublishing_legacy_generate_api_url($object_name, $id, $lang, $is_collection = false) {
//    var_dump($object_name);
//    var_dump($id);
//    var_dump($lang);
//    var_dump($is_collection);
    $HOST = 'https://' . get_option('openpublishing_api_host') . '/resource/v2/';
    $ASPECT = '[:basic,non_academic.realm_genres.*]';
    $OBJECT = 'document';
    $guid = $object_name . '.' . $id;

    if ($is_collection) {
        $url = $HOST.$OBJECT.$ASPECT.'?sort='.$object_name.'__asc&cache=yes&display=10'.($lang?'&language='.$lang:'');
    }
    else {
        $url = $HOST.$guid.$ASPECT.($lang?'?language='.$lang:'');
    }
    openpublishing_print_debug('<b>'.$object_name.':'.$id.($lang?':'.$lang:'').'</b></br>'.$url);

    return $url;
}
