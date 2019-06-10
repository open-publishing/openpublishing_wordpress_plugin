<?php
namespace Openpublishing\Fetch;

// When authorization request fails retry 1 time
$GLOBALS['openpublishing_auth_retry'] = true;

function openpublishing_print_debug($msg) {
    print('<span class="OP_debug" style="display:none;">' . $msg . '</span>');
}

function openpublishing_get_auth_token()  {
    $new_token = '';
    $HOST = 'https://' . get_option('openpublishing_api_host') . '/auth/auth?';
    $REALM_ID = get_option('openpublishing_realm_id');
    $url = $HOST . 'realm_id=' . $REALM_ID . '&type=world';

    $response = wp_remote_get($url);
    $status = wp_remote_retrieve_response_code($response);

    if (200 == $status) {
        $json = json_decode(wp_remote_retrieve_body($response));
        $new_token = $json->{'auth_token'};
        update_option('openpublishing_auth_token', $new_token);
    }
    else {
        openpublishing_print_debug($status . ' ' . $url . '<br>');
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
    $status = wp_remote_retrieve_response_code($response);

    if (200 != $status) {
        openpublishing_print_debug($status . ' ' . $url . '<br>');
        if ($try_again) {
             $response = openpublishing_get_with_auth($url, false);
             update_option('openpublishing_auth_token', '');
        }
    }
    return $response;
}

function openpublishing_fetch_objects($object_name, $id, $lang, $is_collection = false) {
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

    openpublishing_print_debug('<b>'.$object_name.':'.$id.($lang?':'.$lang:'').'</b></br>'.$url.'</br>');
    $response = openpublishing_get_with_auth($url, true);
    $status = wp_remote_retrieve_response_code($response);

    if (200 == $status) {
        $json = json_decode(wp_remote_retrieve_body($response));
        return $json;
    }
}

function openpublishing_fetch_templates() {
    $tmpls = array();
    for ($element = 1; $element <= 10; $element++) {
        $tag = 'openpublishing_template_tag_' . $element;
        $template = 'openpublishing_template_id_' . $element;
        $id = get_option($template);
        $name = get_option($tag);
        $tmpl_content = '';
        if (empty($id) && empty($name)) {
            break;
        }
        // if Elementor is installed
        if(defined('ELEMENTOR_PATH') && class_exists('Elementor\Widget_Base')) {
            $tmpl_content = \Elementor\Plugin::$instance->frontend->get_builder_content($id);
        }
        //if content is still empty then retrieve it by means of wordpress
        if (empty($tmpl_content)) {
            $tmpl_content = get_post_field('post_content', $id);
        }

        if (!empty($tmpl_content)) {
            $tmpls[$name] = $tmpl_content;
        }
    }
    return $tmpls;
}
?>
