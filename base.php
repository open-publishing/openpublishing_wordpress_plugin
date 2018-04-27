<?php
namespace Openpublishing;
require_once 'fetch.php';


if (is_admin() == true)
{
  require_once 'settings.php';
}

function openpublishing_get_all_tags($tags, $text) {
    define('OPENPUBLISHING_COLLECTION_OBJECTS', array('bestseller'));
    define('OPENPUBLISHING_OBJECTS', array_merge(OPENPUBLISHING_COLLECTION_OBJECTS, array('document')));

    $pattern = '/\[(' . implode('|', $tags) . '):(' . implode('|', OPENPUBLISHING_OBJECTS) . ')\.?(\d+)\]/';
    // matched 0: whole, 1: tagname, 2: object, 3: id
    preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);
    return $matches;
}

function openpublishing_get_price($obj) {
    $price = '';
    if (!$obj->{'current_price'}->{'free'}) {
        $price = $obj->{'current_price'}->{'price'}->{'formatted'};
    }
    return $price;
}
function openpublishing_get_catalog($obj, $allObjects) {
    $catalog = '';

    $catalogGuid = $obj->{'academic'}->{'catalog'};
    if ($catalogGuid) {
        $catalog = $allObjects[$catalogGuid]->{'name'};
    }
    return $catalog;
}

function openpublishing_get_picture_source($obj) {
    define('OPENPUBLISHING_PROTOCOL', 'https');
    $object_type = explode('.', $obj->{'GUID'})[0];

    $source = '';
    if ($object_type == 'document') {
        $type = 'normal';
        $source = OPENPUBLISHING_PROTOCOL.'://{cdn_host}/images/cover/brand/e-book/{brand_id}/{document_id}_'.$type.'.jpg';
    }
    return $source;
}

function openpublishing_do_template_replacement($tmpl, $obj) {
    //replace: 1. hardcoded placeholders 2. object properties if placeholders present
    $content = $tmpl;
    $id = explode('.', $obj->{'GUID'})[1];
    $content = str_replace('{title}', $obj->{'title'}, $content );
    $content = str_replace('{subtitle}', $obj->{'subtitle'}, $content );
    $content = str_replace('{price}', openpublishing_get_price($obj), $content );
    $content = str_replace('{catalog}', openpublishing_get_catalog($obj, $all_objects), $content );
    $content = str_replace('{grin_url}', $obj->{'grin_url'}, $content );
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

function openpublishing_replace_tags( $text ) {
    $all_objects = array();
    $templates = Fetch\openpublishing_fetch_templates();
    $all_tags = openpublishing_get_all_tags(array_keys($templates), $text);


    foreach ($all_tags as $set) {
        $tag = $set[1]; $object_name = $set[2]; $id = $set[3];
        $guid = $object_name . '.' . $id;
        $replacer = '[' . $tag .':'. $guid . ']';
        $content = '';
        if (!array_key_exists($guid, $all_objects)) {
            // (array) thing give us an empty array if function return is empty
            foreach ((array)Fetch\openpublishing_fetch_objects($guid, in_array($object_name, OPENPUBLISHING_COLLECTION_OBJECTS)) as $obj) {
                $all_objects[$obj->{'GUID'}] = $obj;
                // collection fetch request returns new property like 'bestseller.1'
                if ($obj->{'collection_guid'}) {
                    $all_objects[$obj->{'collection_guid'}] = $obj;
                }
            }
        }
        if (!array_key_exists($guid, $all_objects)) {
            $content = 'Object "'.$guid.'" was not found. ';
        }
        elseif (!array_key_exists($tag, $templates)) {
            $content = $content.' Template "'.$tag.'" does not exist. ';
        }
        else {
            // if template exists and we got an object data from the server
            $content = openpublishing_do_template_replacement($templates[$tag], $all_objects[$guid]);
        }

        //replace not found objects with debug message
        if (ini_get('display_errors')) {
            $content = '<span class="OP_debug" style="display:block; color:red;">'.'[<b>'. $tag .':'. $guid.'</b>]'.$content.'</span>';
        }
        else {
            // add debug info, which is hidden by default
            $content = '<span class="OP_debug" style="display:none; color:blue;">'.'[<b>'. $tag .':'. $guid.'</b>]'.$content.'</span>';
        }

        //main replace
        $text = str_replace( $replacer, $content, $text);
    }
    //replace common tags with case-insensitive version of str_replace
    $cdn_host = str_replace('api.', 'cdn.', get_option('openpublishing_api_host'));
    $text = str_ireplace('{cdn_host}', $cdn_host, $text );
    $text = str_ireplace('{brand_id}', get_option('openpublishing_brand_id'), $text );

    return $text;
}
?>
