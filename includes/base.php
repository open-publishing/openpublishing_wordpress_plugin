<?php
namespace Openpublishing;
require_once plugin_dir_path( __FILE__ ) . 'fetch.php';
require_once plugin_dir_path( __FILE__ ) . 'render.php';

if (is_admin() == true)
{
  require_once plugin_dir_path( dirname(__FILE__), 1 ) . 'admin/settings.php';
}

/**
 * @param string $content
 * @return string
 */
function openpublishing_replace_shortcodes( string $content ) {
    if (get_option('openpublishing_legacy_substitution', true)) {
        $content = openpublishing_legacy_replace_tags($content);
    }

    $shortcode_data = openpublishing_get_shortcode_data($content);
    foreach ( $shortcode_data as $shortcode ) {
        $template_name = $shortcode['template'];
        $templates[$template_name] = $templates[$template_name]
            ?? Render\openpublishing_get_templates($template_name)[$template_name] ?? false;
        $template = $templates[$template_name];

        $url = Fetch\openpublishing_generate_api_url($shortcode);
        $res = Fetch\openpublishing_fetch_objects($url);
        if (!isset($res->ERROR) && isset($res->OBJECTS) && isset($res->RESULTS)) {
            $replacement = Render\openpublishing_do_template_replacement_collection($template, $res);
        } else {
            openpublishing_print_debug('ERROR -- Shortcode: "' . print_r($shortcode, true) . '" -- ' . print_r($res, true));
            $replacement = 'Error while fetching data.';
        }
        $content = str_replace( $shortcode['replacer'], $replacement, $content );
    }

    //replace common tags with case-insensitive version of str_replace
    $cdn_host_array = explode( '.', get_option( 'openpublishing_api_host' ) );
    $cdn_host_array[0] = 'cdn';
    $content = str_ireplace( '{cdn_host}', implode('.', $cdn_host_array), $content );
    $content = str_ireplace( '{realm_id}', get_option('openpublishing_realm_id'), $content );

    return $content;
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
 * Returns an array with the shortcodes information found in the content
 * @param string $content The complete page or post content
 * @return array
 */
function openpublishing_get_shortcode_data( $content ) {
    $errors = new \WP_Error();
    $data_array = [];
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

        if ( empty($attributes['get_by_id']) ) {
            $data = openpublishing_get_collection_shortcode_data($attributes);
        } else {
            $data = openpublishing_get_single_shortcode_data($attributes);
        }
        $data_array[] = array_merge($data, [
                'replacer' => $match[0],
                'tag' => $match[2],
                'template' => $template,
            ]);
    }
    if ($errors->has_errors()) {
        openpublishing_print_debug('<p>' . implode( "</p>\n<p>", $errors->get_error_messages() ) . '</p>');
    }
    return $data_array;
}

/**
 * @param array $attributes All attributes of the shortcodes for a single result defined by id
 * @return array
 */
function openpublishing_get_single_shortcode_data( array $attributes ) : array
{
    return [
        'get_by_id' => $attributes['get_by_id'],
        'filters' => empty($attributes['language']) ? [] : ['language' => $attributes['language']],
    ];
}

/**
 * @param array $attributes All attributes of the shortcodes for collections
 * @return array
 */
function openpublishing_get_collection_shortcode_data( array $attributes ) : array
{
    // Max number of hits to get. Will be set to 1 if missing and limited by constant
    $limit = $attributes['display'] ?? 1;
    if ($limit > OPENPUBLISHING_DISPLAY_MAX) {
        $limit = OPENPUBLISHING_DISPLAY_MAX;
    }
    $sort = $attributes['sort'] ?? null;
    $order = $attributes['order'] ?? 'asc';
    $get_by_position = $attributes['get-by-position'] ?? null;
    unset($attributes['display'], $attributes['template'], $attributes['sort'],
        $attributes['get-by-position'], $attributes['get_by_id'], $attributes['order']);

    return [
        'get-by-position' => $get_by_position,
        'limit' => $limit,
        'sort' => $sort,
        'order' => $order,
        'filters' => $attributes,
    ];
}

/**
 * @param string $content Normally the page or post content
 * @return string
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
            $is_collection = in_array($object_name, OPENPUBLISHING_LEGACY_COLLECTION_OBJECTS);
            $url = Fetch\openpublishing_legacy_generate_api_url($object_name, $id, $lang, $is_collection);
            $res = Fetch\openpublishing_fetch_objects($url);
            $iter = 1;

            foreach ($res->OBJECTS as $obj) {
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
            $object = $all_objects[$all_objects[$guid]];
            $replacement = Render\openpublishing_do_template_replacement($templates[$tag], $object, $all_objects);
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
    $pattern = '/\[(' . implode('|', $templates) . '):(' . implode('|', OPENPUBLISHING_LEGACY_OBJECTS) . ')\.?(\d+)\:?(en|de|fr|es)?\]/';

    // matched 0: whole, 1: tagname, 2: object, 3: id, 4: language (optional)
    preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);
    return $matches;
}

/**
 * @param string $message
 * @param boolean $return_content If false, message will be echoed, if true message html will be returned
 * @return string
 */
function openpublishing_print_debug(string $message, $return_content = false) {
    $html_message = '';
    if ( WP_DEBUG ) {
        $html_message = ('<span class="OP_debug" style="display:none;">' . esc_html__($message) . "<br></span>\n");

        if ( !$return_content ) {
            echo $html_message;
        }
    }
    return $html_message;
}