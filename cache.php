<?php
namespace Openpublishing\Cache;

$openpublishing_cache = [
    'tags' => [],
    'requests' => [],
    'request_relevant_base_attributes' => [
        'ean', 'title', 'language', 'bisac', 'thema', 'genre', 'main_subject_id', 'category_id'
    ],
    'request_relevant_attribute_suffixes' => [
        'eq', 'ne', 'lt', 'gt', 'le', 'ge', 'startswith', 'endswith', 'contains'
    ],
    'request_relevant_attributes' => [
        'sort', 'sort__asc', 'sort__desc'
    ]
];

function openpublishing_complete_relevant_attributes() {
    global $openpublishing_cache;

    // Add all combinations of suffixes to the attributes
    foreach ($openpublishing_cache['request_relevant_base_attributes'] as $attribute) {
        $openpublishing_cache['request_relevant_attributes'] = array_merge(
            $openpublishing_cache['request_relevant_attributes'],
            array_map(function ($suffix) use ($attribute) {
                return $attribute . '__' . $suffix;
            }, $openpublishing_cache['request_relevant_attribute_suffixes'])
        );
    }
}
openpublishing_complete_relevant_attributes();

/**
 * @param array $attributes Attributes of the shortcode
 * @param string $content The content that is between the shortcode (empty here)
 * @param string $tag The name of the tag (should always be 'openpublishing' here)
 * @return string
 */
function openpublishing_add_shortcodes_to_cache ($attributes, $content, $tag) {
    global $openpublishing_cache;
    ksort($attributes);
    $tempTag = '[[opTemp:' . openpublishing_get_hash_from_all_attributes($attributes) . ']]';
    $openpublishing_cache['tags'][$tempTag] = [
        'key' => openpublishing_get_hash_from_relevant_attributes($attributes),
        'display' => $attributes['display'] ?? 1,
    ];
    return $tempTag;
}

/**
 * @param array $attributes
 * @return string
 */
function openpublishing_get_hash_from_all_attributes($attributes) {
    return md5(serialize($attributes));
}

/**
 * @param array $attributes
 * @return string
 */
function openpublishing_get_hash_from_relevant_attributes(array $attributes) {
    global $openpublishing_cache;
    return openpublishing_get_hash_from_all_attributes(
        array_intersect_key(
            $attributes,
            array_flip($openpublishing_cache['request_relevant_attributes'])
        )
    );
}