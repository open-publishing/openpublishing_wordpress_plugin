<?php
namespace Openpublishing\Fetch;


function openpublishing_fetch_objects($object_name, $id, $lang, $is_collection = false) {
    $HOST = 'https://' . get_option('openpublishing_api_host') . '/resource/v2/';
    $ASPECT = '[:basic,non_academic.realm_genres.*]';
    $AUTH_TOKEN = get_option('openpublishing_auth_token');
    $COLLECTION_OBJECT = 'document';
    $guid = $object_name . '.' . $id;

    $options = array( 'sslverify' => false);
    if ($is_collection) {
        $m_objects_array = array();
        // if we need more that 10 elements, please use pagination: page = <id>
        $url = $HOST.$COLLECTION_OBJECT.$ASPECT.'?sort='.$object_name.'__asc&display=10&auth_token='.$AUTH_TOKEN.($lang?'&language='.$lang:'');

        $response = wp_remote_get($url, $options);
        $status = wp_remote_retrieve_response_code( $response );
        if(is_wp_error($response) && $status) {
            print($response->get_error_message());
        }

        if ( 200 == $status || 401 == $status ) {
            $json = json_decode(wp_remote_retrieve_body($response));
            $sequence_number = 1;
            $part_guid = $COLLECTION_OBJECT . '.';

            foreach ($json->{'OBJECTS'} as $obj) {
                if (strpos($obj->{'GUID'}, $part_guid) === 0 ) {
                    $obj->{'collection_guid'} = $object_name.'.'. $sequence_number++;
                }
            }
            return $json->{'OBJECTS'};
         }
    }
    else {
        $url = $HOST.$guid.$ASPECT.'?auth_token='.$AUTH_TOKEN.($lang?'&language='.$lang:'');
        $response = wp_remote_get($url, $options);
        $status = wp_remote_retrieve_response_code( $response );

        if(is_wp_error($response)) {
            print($response->get_error_message());
        }
        if ( 200 == $status || 401 == $status ) {
            $json = json_decode(wp_remote_retrieve_body($response));
            return $json->{'OBJECTS'};
        }
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
