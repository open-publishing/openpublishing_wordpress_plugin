<?php

// create custom plugin settings menu
add_action('admin_menu', 'openpublishing_create_menu');

function openpublishing_create_menu()
{

    //create new top-level menu
    add_menu_page('Open Publishing Page', 'Open Publishing', 'manage_options', 'openpublishing', 'openpublishing_add_menu');

    //call register settings function
    add_action('admin_init', 'openpublishing_register_settings');
}

function openpublishing_add_menu() { ?>
    <h1>Open Publishing Plugin</h1>
    <p class="about-description">Enrich content with data from the Open Publishing service</p>


    <form method="post" action="options.php">
        <?php settings_fields('openpublishing-settings-group'); ?>
        <?php do_settings_sections('openpublishing-settings-group'); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Realm ID*</th>
                <td>
                    <input type="text" name="openpublishing_realm_id"
                           value="<?php echo esc_attr(get_option('openpublishing_realm_id')); ?>"/>
                    <span class="description">The Id of your Openpublishing Realm</span>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">Brand Name*</th>
                <td>
                    <input type="text" name="openpublishing_brand_name"
                           value="<?php echo esc_attr(get_option('openpublishing_brand_name')); ?>"/>
                    <span class="description">The name of your Openpublishing Brand</span>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">API Host*</th>
                <td>
                    <input type="text" name="openpublishing_api_host"
                           value="<?php echo esc_attr(get_option('openpublishing_api_host')); ?>"/>
                    <span class="description">Your Openpublishing API url</span>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Document count</th>
                <td>
                    <input type="text" name="openpublishing_document_count"
                           value="<?php echo esc_attr(get_option('openpublishing_document_count')); ?>"/>
                    <span class="description">Brand statistics: <a href="#faq_count">document count</a></span>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Auth Token</th>
                <td>
                    <input disabled type="text" name="openpublishing_auth_token"
                           value="<?php echo esc_attr(get_option('openpublishing_auth_token')); ?>"/>
                    <span class="description">A token which allows access to your realm as world user</span></td>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Legacy Mode</th>
                <td>
                    <input type="checkbox" id="openpublishing_legacy_substitution" name="openpublishing_legacy_substitution"
                           value="1" <?= checked(1, get_option('openpublishing_legacy_substitution'), false) ?> />
                    <label class="description" for="openpublishing_legacy_substitution">If enabled, <a href="#legacy" title="Jump to legacy tag documentation">legacy smart substitutions</a> will be replaced as well (disable if not needed)</label>
                </td>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Experimental Mode</th>
                <td>
                    <input type="checkbox" id="openpublishing_experimental_mode" name="openpublishing_experimental_mode"
                           value="1" <?= checked(1, get_option('openpublishing_experimental_mode'), false) ?> />
                    <label class="description" for="openpublishing_experimental_mode">Tries to replace all available object properties, in addition to the default ones like {title}, {price} etc. (disable if not needed)</label>
                </td>
                </td>
            </tr>
        </table>

        <h3>Open Publishing substitution templates:</h3>
        <p class="about-description">Get more information about Open Publishing substitutions <a href="#faq">here</a><br>
            (Please don't leave empty rows in the middle.)
        </p>
        <table class="form-table">
            <?php for ($element = 1; $element <= 10; $element++) :
                $tag = 'openpublishing_template_tag_' . $element;
                $template = 'openpublishing_template_id_' . $element;
                $id = get_option($template); ?>
                <tr valign="top">
                    <th scope="row">Template #<?= $element ?></th>
                    <td class="regular-text"><input type="text" title="tag_name" placeholder="template name"
                                                    name="<?= $tag ?>" value="<?= esc_attr(get_option($tag)) ?>"
                                                    class="regular-text"/></td>
                    <td><input type="number" title="template id (Elementor template or post id)" placeholder="post id"
                               name="<?= $template ?>" value="<?= esc_attr(get_option($template)) ?>"/>
                        <?php if ($id) : ?>
                            <?php if (defined('ELEMENTOR_PATH') && class_exists('Elementor\Widget_Base')) : ?>
                                <a href="/wp-admin/post.php?post=<?= get_option($template) ?>&action=elementor">edit
                                    with Elementor</a> |
                            <?php endif; ?>
                            <a href="/wp-admin/post.php?post=<?= get_option($template) ?>&action=edit">edit</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endfor; ?>
        </table>
        <?php submit_button(); ?>
    </form>


    <!--Version updated on 27.11.2020 -->
    <div class="postbox" style="padding:20px" id="faq">
        <h1 style="padding-left:20px;">Open Publishing substitution help</h1>
        <p>For a detailed documentation please refer to <a href="https://github.com/open-publishing/openpublishing_wordpress_plugin/blob/master/readme.md#openpublishing" target="_blank">GitHub</a>.</p>
        <p>To visualize Open Publishing data on your page please use shortcodes in place you want to insert
            data. Here some shortcodes examples:<br>
            <code>[openpublishing template="sidebar" sorting="bestseller" category_id="2" display="6"]</code>,<br>
            <code>[openpublishing template="teaser" sorting="newest" display="1"]</code>,<br>
            <code>[openpublishing template="highlight" get_by_id="4356"]</code>
        </p>
        <p>This shortcode will be replaced by the data this plugin retrieves from the OP server. </p>
        <h2>Shortcodes</h2>
        <p>A shortcode must contain at least these attributes: <code>[openpublishing template="my-template" sort="my-sorting"]</code></p>
        <style>.openpublish-param-table th {text-align: left} table.openpublish-param-table {border-spacing: 0; border-collapse: collapse;} .openpublish-param-table th, .openpublish-param-table td {padding: 5px; line-height: normal; border: 1px solid black;}</style>
        <table class="openpublish-param-table" style="width: 50%; min-width: 500px;">
            <thead>
            <tr>
                <th>Attribute</th>
                <th>Type</th>
                <th>Description</th>
                <th>Example</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <th>template</th>
                <td>string</td>
                <td>This attribute is mandatory. Use the name of the desired substitution template entered in the form above.</td>
                <td><code>template="template-name"</code></td>
            </tr>
            <tr>
                <th>display</th>
                <td>integer</td>
                <td>The number of results you want to display (max. 10). The default is 1.</td>
                <td><code>display="5"</code></td>
            </tr>
            <tr>
                <th>sort</th>
                <td>string</td>
                <td>Sort the results by one of the following. Allowed values are <code>bestseller</code>, <code>most_read</code>, <code>newest</code></td>
                <td><code>sort="bestseller"</code></td>
            </tr>
            <tr>
                <th>order</th>
                <td>string</td>
                <td>Change the direction of the results sorting. Where ascending means te latest or best selling is displayed first. Allowed values are <code>asc</code> (default) and <code>desc</code></td>
                <td><code>order="desc"</code></td>
            </tr>
            <tr>
                <th>get_by_id</th>
                <td>integer</td>
                <td>Use this attribute to get a certain object, identified by its global id. All others attributes, except language and template, will be ignored.</td>
                <td><code>get_by_id="4356"</code></td>
            </tr>
            <tr>
                <th>main_subject_id</th>
                <td>integer</td>
                <td>Filter results by main subject id.</td>
                <td><code>main_subject_id="12"</code></td>
            </tr>
            <tr>
                <th>language</th>
                <td>string</td>
                <td>Filter results by language code (ISO639) of the text. Allowed values are <code>en</code>, <code>de</code>, <code>fr</code>, <code>es</code></td>
                <td><code>language="en"</code></td>
            </tr>
            <tr>
                <th>category_id</th>
                <td>integer</td>
                <td>Filter results by ID of the category of the text.</td>
                <td><code>category_id="17"</code> (Essays)</td>
            </tr>
            <tr>
                <th>imprint_id</th>
                <td>integer</td>
                <td>Filter results by imprint id.</td>
                <td><code>imprint_id="1"</code></td>
            </tr>
            <tr>
                <th>brand_id</th>
                <td>integer</td>
                <td>Filter results by brand id.</td>
                <td><code>brand_id="12"</code></td>
            </tr>
            <tr>
                <th>genre</th>
                <td>string</td>
                <td>Filter results by genre (non academic).</td>
                <td><code>genre="reisebericht"</code></td>
            </tr>
            <tr>
                <th>thema</th>
                <td>string</td>
                <td>Filter results by THEMA category.</td>
                <td><code>thema="1DDU"</code></td>
            </tr>
            <tr>
                <th>bisac</th>
                <td>string</td>
                <td>Filter results by BISAC category.</td>
                <td><code>bisac="12"</code></td>
            </tr>
            <tr>
                <th>ean</th>
                <td>string</td>
                <td>Filter results by ean.</td>
                <td><code>ean="9783638876346"</code></td>
            </tr>
            <tr>
                <th>title</th>
                <td>string</td>
                <td>Filter results by title.</td>
                <td><code>title="Hamlets+Melancholie"</code></td>
            </tr>
            </tbody>
        </table>

        <h2>Preparatory steps:</h2>
        <p>To make this work please create templates for each tag_name you would like to use. You can do this in two
            different ways.</p>
        <ul>
            <li>Using Elementor plugin:</li>
            <li>
                <ol>
                    <li>Go to Elementor <a href="/wp-admin/edit.php?post_type=elementor_library">page</a></li>
                    <li>Create new template, remember a template id (you can see it in the edit url)</li>
                    <li>You can easily style your template by means of Elementor</li>
                </ol>
            </li>
            <li>Using Wordpress posts:</li>
            <li>
                <ol>
                    <li>Go to <a href="wp-admin/edit.php">posts</a></li>
                    <li>Create new post and treat it like a template, remember a template id (you can see it in the edit
                        url)
                    </li>
                    <li>(Optional) you can assign special category (like openpublishing-templates) to each post to
                        distinguish between others
                    </li>
                </ol>
            </li>
            </br>
            <li>Use special keywords in your template:<code>{title}</code> <code>{subtitle}</code> <code>{price}</code>
                <code>{grin_url}</code> <code>{source_url}</code> <code>{document_id}</code> <code>{cdn_host}</code>
                <code>{realm_id}</code></li>
        </ul>
        <p>Add newly created templates/posts on this page with corresponding ids.</p>
        <h2>Usage:</h2>
        <p>To use substitution please insert into your page this tag with desired 'tag_name' and 'object_id' like:</p>
        <xmp>
        <h2>Our bestsellers:</h2>
        <div>[openpublishing template="presentation" sorting="bestseller" category_id=2 display="6"]</div>
        </xmp>
        <p>This tags should be replaced right away.</p>
        <h2>Debug:</h2>
        <p>To debug plugin work please add next code into your page after all text:</p>
        <xmp>
    <script type="text/javascript">
        var debug = document.getElementsByClassName("OP_debug");
        for (i = 0; i < debug.length; i++) {
            debug[i].style.display = 'inline';
        }
    </script>
        </xmp>
        <p>This will allow you to see some more information about substitution.</p>

    </div>

    <div class="postbox" style="padding:20px">
    <h1 id="legacy" style="padding-left:20px;">Legacy Smart substitution</h1>
    <p>Until version 1.6 the smart substitution notation was: <code>["template_name":"object"."object_id":"language"]</code>
        e.g. [<b>large_document_view:document.123</b>] or [<b>sidebar:newest.1:de</b>]</p>
    <h4>Language</h4>
    <p>Language is an optional attribute used to get language specific results. Possible values are: <code>en</code>,
        <code>de</code>, <code>fr</code>, <code>es</code>.</p>
    <p></p>
    <h4>Objects</h4>
    <p>You can use: <code>document</code>, <code>bestseller</code>, <code>most_read</code>, <code>newest</code>
        objects.</p>
    </div>


    <div class="postbox" style="padding:20px;">
        <h1 id="faq_count" style="padding-left:20px; padding-top:20px">Open Publishing document count help</h1>

        <p>There is a scheduled job which is retrieving the brand statistics from OP server and saves the value to <i>Document
                count</i> on a daily basis.</p>
        <p>To print document count use 'openpublishing_document_count' option.</p>
        <h3>Usage:</h3>
        <xmp><?php echo "    <h1><?php echo 'Total count of published documents: ' . get_option('openpublishing_document_count'); ?></h1>"; ?></xmp>
        <p>Next example shows how to create placeholder text for header search input with text <i>"219.240 eBooks &amp;
                Bücher"</i>:</p>
        <xmp><?php echo '    <input data-widget="SearchTagAutocomplete" type="text" class="search-input ac_input" name="searchstring" value="" autocomplete="off"
    placeholder="<?php echo get_option("openpublishing_document_count") . " "; pll_e("Text Suchleiste"); ?>" />'; ?>
        </xmp>

    </div>
<?php }

function openpublishing_register_settings()
{

    //register our settings
    register_setting('openpublishing-settings-group', 'openpublishing_realm_id', array(
        'type' => 'integer',
        'description' => 'The Id of your Openpublishing Realm'
    ));
    register_setting('openpublishing-settings-group', 'openpublishing_brand_name', array(
        'type' => 'integer',
        'description' => 'The Name of your Openpublishing Brand'
    ));
    register_setting('openpublishing-settings-group', 'openpublishing_auth_token', array(
        'type' => 'string',
        'description' => 'An auth token which allows access to your realm as world user'
    ));
    register_setting('openpublishing-settings-group', 'openpublishing_api_host', array(
        'type' => 'string',
        'description' => 'Your Openpublishing api url'
    ));
    register_setting('openpublishing-settings-group', 'openpublishing_document_count', array(
        'type' => 'string',
        'description' => 'Brand statistics: document count'
    ));
    register_setting('openpublishing-settings-group', 'openpublishing_legacy_substitution', array(
        'type' => 'bool',
        'description' => 'Legacy smart substitution enabled or disabled'
    ));
    register_setting('openpublishing-settings-group', 'openpublishing_experimental_mode', array(
        'type' => 'bool',
        'description' => 'Enable experimental replacement mode'
    ));
    for ($element = 1; $element <= 10; $element++) {
        register_setting('openpublishing-settings-group', 'openpublishing_template_id_' . $element, array(
            'type' => 'integer',
            'description' => 'Template id #' . $element
        ));

        register_setting('openpublishing-settings-group', 'openpublishing_template_tag_' . $element, array(
            'type' => 'string',
            'description' => 'Template tag name #' . $element
        ));
    }
}
