<?php
namespace Openpublishing;
require_once plugin_dir_path( __FILE__ ) . 'fetch.php';
require_once plugin_dir_path( __FILE__ ) . 'render.php';
//require_once plugin_dir_path( __FILE__ ) . 'cache.php';

if (is_admin() == true)
{
  require_once plugin_dir_path( dirname(__FILE__), 1 ) . 'admin/settings.php';
}

/**
 * @param string $text
 * @return string
 */
function openpublishing_replace_shortcodes( $text ) {
    if (get_option('openpublishing_legacy_substitution', true)) {
        $text = openpublishing_legacy_replace_tags($text);
    }

    $shortcode_data = openpublishing_get_shortcode_data($text);

    //replace common tags with case-insensitive version of str_replace
    $cdn_host_array = explode( '.', get_option( 'openpublishing_api_host' ) );
    $cdn_host_array[0] = 'cdn';
    $text = str_ireplace( '{cdn_host}', implode('.', $cdn_host_array), $text );
    $text = str_ireplace( '{realm_id}', get_option('openpublishing_realm_id'), $text );

    return $text;
}

/**
 * Searches all shortcodes in the given content and returns the matches
 * @param string $content
 * @param string $shortcode
 * @return array
 */
function openpublishing_get_all_shortcodes( string $content, string $shortcode = 'openpublishing' ) {
    preg_match_all(
        '/' . get_shortcode_regex( [$shortcode] ) . '/',
        $content,
        $shortcode_matches,
        PREG_SET_ORDER
    );
    return $shortcode_matches;
}

/**
 * @param string $content The complete page or post content
 * @return array
 */
function openpublishing_get_shortcode_data( $content ) {
    ;
    $errors = new \WP_Error();
    $data = [];
    foreach ( openpublishing_get_all_shortcodes($content) as $match ) {
        // Allow [[foo]] syntax for escaping a tag.
        if ( '[' === $match[1] && ']' === $match[6] ) {
            return substr( $match[0], 1, -1 );
        }
        $attributes = shortcode_parse_atts( $match[3] );

        // template is mandatory, skipp if missing and add error
        if (empty($attributes['template'])) {
            $errors->add( 'openpublish_template_missing', 'No template defined in shortcode', $match[0] );
            continue;
        } else {
            $template = $attributes['template'];
        }

        // Max number of hits to get. Will be set to 1 if missing and limited by constant OPENPUBLISHING_DISPLAY_MAX
        $limit = $attributes['display'] ?? 1;
        if ($limit > OPENPUBLISHING_DISPLAY_MAX) {
            $limit = OPENPUBLISHING_DISPLAY_MAX;
        }

        $sort = $attributes['sort'] ?? null;
        $order = $attributes['order'] ?? 'asc';
        $get_position = $attributes['get-position'] ?? null;
        unset($attributes['display'], $attributes['template'], $attributes['sort'], $attributes['get-position']);

        $data[] = [
            'tag'  => $match[2],
            'template' => $template,
            'get-position' => $get_position,
            'limit' => $limit,
            'sort' => $sort,
            'order' => $order,
            'filters' => $attributes,
        ];
    }
    if ($errors->has_errors()) {
        echo '<div class="error"><p>' . implode( "</p>\n<p>", $errors->get_error_messages() ) . '</p></div>';
    }
    return $data;
}

/**
 * @param string $content
 * @return string|string[]
 */
function openpublishing_legacy_replace_tags( $content ) {
    $all_objects = [];
    $templates = Render\openpublishing_get_templates();
    $all_tags = openpublishing_legacy_get_all_tags(array_keys($templates), $content);

    foreach ($all_tags as $set) {
        $tag = $set[1];
        $object_name = $set[2];
        $id = isset($set[3]) ? '.' . $set[3] : '';
        $lang = empty($set[4]) ? '' : ':' . $set[4];
        $guid = $object_name . $id;
        $replacer = '[' . $tag .':'. $guid . $lang . ']';
        $replacement = '';
        $debug_content = '';

        // was the content already fetched by the api?
        if (!array_key_exists($guid, $all_objects)) {
            // (array) thing give us an empty array if function return is empty
            $is_collection = in_array($object_name, OPENPUBLISHING_COLLECTION_OBJECTS);
            $url = Fetch\openpublishing_legacy_generate_api_url($object_name, $id, $lang, $is_collection);
            $res = Fetch\openpublishing_fetch_objects($url);
            $objs = $res->OBJECTS;
            $iter = 1;

            foreach ($objs as $obj) {
                $all_objects[$obj->GUID] = $obj;
            }

            foreach ($res->RESULTS as $obj) {
                $all_objects[$object_name . '.' . $iter++] = $obj;
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
            $replacement = Render\openpublishing_do_template_replacement($templates[$tag], $guid, $all_objects);
        }

        // add debug info, which is hidden by default
        if ( WP_DEBUG || strstr($content, 'document.getElementsByClassName("OP_debug")') ) {
            $replacement = '<span class="OP_debug" style="display: none">[<b>' . $tag . ':' . $guid . $lang . '</b>]' . $debug_content . '</span>' . $replacement;
        }

        //main replace
        $content = str_replace( $replacer, $replacement, $content);
    }

    return $content;
}

/**
 * @param array $templates
 * @param string $content
 * @return array
 */
function openpublishing_legacy_get_all_tags($templates, $content) {
    if (empty($templates)) {
        return [];
    }
    $pattern = '/\[(' . implode('|', $templates) . '):(' . implode('|', OPENPUBLISHING_OBJECTS) . ')\.?(\d+)\:?(en|de|fr|es)?\]/';

    // matched 0: whole, 1: tagname, 2: object, 3: id, 4: language (optional)
    preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);
    return $matches;
}
