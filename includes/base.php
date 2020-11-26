<?php
namespace Openpublishing;
require_once plugin_dir_path( __FILE__ ) . 'fetch.php';
//require_once plugin_dir_path( __FILE__ ) . 'cache.php';

if (is_admin() == true)
{
  require_once plugin_dir_path( dirname(__FILE__), 1 ) . 'admin/settings.php';
}

function openpublishing_legacy_get_all_tags($tags, $text) {
    $pattern = '/\[(' . implode('|', $tags) . '):(' . implode('|', OPENPUBLISHING_OBJECTS) . ')\.?(\d+)\:?(en|de|fr|es)?\]/';

    // matched 0: whole, 1: tagname, 2: object, 3: id, 4: language (optional)
    preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);
    return $matches;
}

function openpublishing_get_price($obj) {
    $price = '';

    if (isset($obj->{'current_prices'}->{'ebook'})) {
            if (is_object($obj->{'current_prices'}->{'ebook'}->{'price'}) && !$obj->{'current_prices'}->{'ebook'}->{'free'}) {
                $price = $obj->{'current_prices'}->{'ebook'}->{'price'}->{'formatted'};
            }
    } elseif (isset($obj->{'current_prices'}->{'pod'})) {
            if (is_object($obj->{'current_prices'}->{'pod'}->{'price'}) && !$obj->{'current_prices'}->{'pod'}->{'free'}) {
                $price = $obj->{'current_prices'}->{'pod'}->{'price'}->{'formatted'};
            }
    }

    return $price;
}

function openpublishing_get_subject($obj, $allObjects) {
    $subject = '';

    if (isset($obj->{'is_academic'})) {
            if (is_object($obj->{'academic'})) {
                $catalogGuid = $obj->{'academic'}->{'catalog'};
                if ($catalogGuid) {
                    $catalog = $allObjects[$catalogGuid]->{'name'};
                    // truncate at first hyphen "-"
                    $subject = explode('-', $catalog)[0];
                }
        }
    }
    elseif (isset($obj->{'non_academic'})) {
        $genreGuid = $obj->{'non_academic'}->{'realm_genres'};
        if ($genreGuid[0]) {
            // lets take first realm_genre from a list
            $subject = $allObjects[$genreGuid[0]]->{'name'};
        }
    }
    return $subject;
}

function openpublishing_get_picture_source($obj) {
    $object_type = explode('.', $obj->{'GUID'})[0];

    $source = '';
    if ($object_type == 'document') {
        $type = 'normal';
        $source = 'https://{cdn_host}/images/cover/brand/e-book/{realm_id}/{document_id}_'.$type.'.jpg';
    }
    return $source;
}

function openpublishing_do_template_replacement($tmpl, $guid, $all_objects) {
    //replace: 1. hardcoded placeholders 2. object properties if placeholders present
    $obj = $all_objects[$guid];
    $content = $tmpl;
    $id = explode('.', $obj->{'GUID'})[1];
    $content = str_replace('{title}', $obj->{'title'}, $content );
    $content = str_replace('{subtitle}', $obj->{'subtitle'}, $content );
    $content = str_replace('{price}', openpublishing_get_price($obj), $content );
    $content = str_replace('{subject}', openpublishing_get_subject($obj, $all_objects), $content );
    $content = str_replace('{grin_url}', $obj->{'grin_url'} ?? '', $content );
    $content = str_replace('{source_url}', openpublishing_get_picture_source($obj), $content );
    $content = str_replace('{document_id}', $id, $content );

    // experimental
    $obj_props = get_object_vars($obj);
    foreach ( $obj_props as $key => $value) {
        if (is_string($value) || is_numeric($value)) {
            $content = str_replace('{'.$key.'}', $value, $content);
        }
    }
    return $content;
}

/**
 * @param string $text
 * @return string
 */
function openpublishing_replace_tags( $text ) {
    if (get_option('openpublishing_legacy_substitution', true)) {
        $text = openpublishing_legacy_replace_tags($text);
    }
    //replace common tags with case-insensitive version of str_replace
    $cdn_host_array = explode('.', get_option('openpublishing_api_host'));
    $cdn_host_array[0] = 'cdn';
    $text = str_ireplace('{cdn_host}', implode('.', $cdn_host_array), $text );
    $text = str_ireplace('{realm_id}', get_option('openpublishing_realm_id'), $text );

    return $text;
}

/**
 * @param string $text
 * @return string|string[]
 */
function openpublishing_legacy_replace_tags( $text ) {
    $all_objects = [];
    $templates = Fetch\openpublishing_fetch_templates();
    $all_tags = openpublishing_legacy_get_all_tags(array_keys($templates), $text);

    foreach ($all_tags as $set) {
        $tag = $set[1];
        $object_name = $set[2];
        $id = isset($set[3]) ? '.' . $set[3] : '';
        $lang = empty($set[4]) ? '' : ':' . $set[4];
        $guid = $object_name . $id;
        $replacer = '[' . $tag .':'. $guid . $lang . ']';
        $content = '';
        $debug_content = '';

        // was the content already fetched by the api?
        if (!array_key_exists($guid, $all_objects)) {
            // (array) thing give us an empty array if function return is empty
            $res = Fetch\openpublishing_fetch_objects($object_name, $id, $lang, in_array($object_name, OPENPUBLISHING_COLLECTION_OBJECTS));
            $objs = (array)($res->{'OBJECTS'});
            $iter = 1;

            foreach ($objs as $obj) {
                $all_objects[$obj->{'GUID'}] = $obj;
            }

            foreach ((array)$res->{'RESULTS'} as $obj) {
                $all_objects[$object_name . '.' . $iter++] = $objs[$obj];

            }
        }

        // do the replacement
        if (!array_key_exists($guid, $all_objects)) {
            $debug_content = '<span style="color:orange;"> Object was not found</span>';
        }
        elseif (!array_key_exists($tag, $templates)) {
            $debug_content = '<span style="color:red;"> Template "' . $tag . '" does not exist.<span>';
        }
        else {
            // if template exists and we got an object data from the server
            $content = openpublishing_do_template_replacement($templates[$tag], $guid, $all_objects);
        }

        // add debug info, which is hidden by default
        if ( WP_DEBUG || strstr($text, 'document.getElementsByClassName("OP_debug")') ) {
            $content = '<span class="OP_debug" style="display: none">[<b>' . $tag . ':' . $guid . $lang . '</b>]' . $debug_content . '</span>' . $content;
        }

        //main replace
        $text = str_replace( $replacer, $content, $text);
    }

    return $text;
}
