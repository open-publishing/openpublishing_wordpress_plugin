<?php
namespace Openpublishing\Render;

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

function openpublishing_do_template_replacement($tmpl, $guid, $all_objects, $index = 1) {
    //replace: 1. hardcoded placeholders 2. object properties if placeholders present
    $obj = $all_objects[$guid];
    $content = $tmpl;
    $id = explode('.', $obj->{'GUID'})[1];
    $content = str_replace('{title}', $obj->{'title'}, $content );
    $content = str_replace('{subtitle}', $obj->{'subtitle'}, $content );
    $content = str_replace('{price}', \Openpublishing\Render\openpublishing_get_price($obj), $content );
    $content = str_replace('{subject}', \Openpublishing\Render\openpublishing_get_subject($obj, $all_objects), $content );
    $content = str_replace('{grin_url}', $obj->{'grin_url'} ?? '', $content );
    $content = str_replace('{source_url}', \Openpublishing\Render\openpublishing_get_picture_source($obj), $content );
    $content = str_replace('{document_id}', $id, $content );
    $content = str_replace('{index}', $index, $content );

    // experimental
    if (get_option('openpublishing_experimental_mode', false)) {
        $obj_props = get_object_vars($obj);
        foreach ( $obj_props as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $content = str_replace('{' . $key . '}', $value, $content);
            }
        }
    }
    return $content;
}