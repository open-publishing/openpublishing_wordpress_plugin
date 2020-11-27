<?php
namespace Openpublishing\Render;

function openpublishing_get_price($obj) {
    $price = '';

    if (isset($obj->current_prices->ebook)) {
        if (is_object($obj->current_prices->ebook->price) && !$obj->current_prices->ebook->free) {
            $price = $obj->current_prices->ebook->price->formatted;
        }
    } elseif (isset($obj->current_prices->pod)) {
        if (is_object($obj->current_prices->pod->price) && !$obj->current_prices->pod->free) {
            $price = $obj->current_prices->pod->price->formatted;
        }
    }

    return $price;
}

function openpublishing_get_subject($obj, $allObjects) {
    $subject = '';

    if (isset($obj->is_academic)) {
        if (is_object($obj->academic)) {
            $catalogGuid = $obj->academic->catalog;
            if ($catalogGuid) {
                $catalog = $allObjects[$catalogGuid]->name;
                // truncate at first hyphen "-"
                $subject = explode('-', $catalog)[0];
            }
        }
    }
    elseif (isset($obj->non_academic)) {
        $genreGuid = $obj->non_academic->realm_genres;
        if ($genreGuid[0]) {
            // lets take first realm_genre from a list
            $subject = $allObjects[$genreGuid[0]]->name;
        }
    }
    return $subject;
}

function openpublishing_get_picture_source($obj) {
    $object_type = explode('.', $obj->GUID)[0];

    $source = '';
    if ($object_type == 'document') {
        $type = 'normal';
        $source = 'https://{cdn_host}/images/cover/brand/e-book/{realm_id}/{document_id}_'.$type.'.jpg';
    }
    return $source;
}

function openpublishing_do_template_replacement($tmpl, $guid, $all_objects, $index = 1) {
    //replace: 1. hardcoded placeholders 2. object properties if placeholders present
    $obj = $all_objects[$all_objects[$guid]];
    $content = $tmpl;
    $id = explode('.', $obj->GUID)[1];
    $replacements = [
        '{title}' => $obj->title,
        '{subtitle}' => $obj->subtitle,
        '{price}' => \Openpublishing\Render\openpublishing_get_price($obj),
        '{subject}' => \Openpublishing\Render\openpublishing_get_subject($obj, $all_objects),
        '{grin_url}' => $obj->grin_url ?? '',
        '{source_url}' => \Openpublishing\Render\openpublishing_get_picture_source($obj),
        '{document_id}' => $id,
        '{index}' => $index,
    ];
    $content = str_replace( array_keys($replacements), $replacements, $content );

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

/**
 * @param string $template
 * @return array
 */
function openpublishing_get_templates($template = null) {
    $templates = [];
    for ( $element = 1; $element <= 10; $element++ ) {
        $name = get_option('openpublishing_template_tag_' . $element);

        // when a specific template is desired, skipp all others
        if ( ($template !== null && $template != $name) ) {
            continue;
        }

        $content_id = get_option('openpublishing_template_id_' . $element);
        // to reduce sql requests, we only read until there is an incomplete template pair
        if ( empty($content_id) || empty($name) ) {
            break;
        }

        // if Elementor is installed
        if ( defined('ELEMENTOR_PATH') && class_exists('Elementor\Widget_Base') ) {
            $tmpl_content = \Elementor\Plugin::$instance->frontend->get_builder_content( $content_id );
        }
        // if content is still empty then retrieve it by means of wordpress
        if ( empty($tmpl_content) ) {
            $tmpl_content = get_post_field( 'post_content', $content_id );
        }

        if ( !empty($tmpl_content) ) {
            $templates[$name] = $tmpl_content;
        }
    }
    return $templates;
}
