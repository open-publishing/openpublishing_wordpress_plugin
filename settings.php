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

function openpublishing_add_menu()
{
    ?>
    <h1>Open Publishing Plugin</h1>
    <p class="about-description">Enrich content with data from the OpenPublishing service</p>


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
                    <label class="description" for="openpublishing_legacy_substitution">If enabled, legacy smart substitutions will be replaced as well (disable if not needed)</label>
                </td>
                </td>
            </tr>

        </table>
        <h3>Open Publishing substitution templates:</h3>
        <p class="about-description">Get more information about Open Publishing substitutions <a href="#faq">here</a>
        </p>
        <table class="form-table">
            <?php for ($element = 1; $element <= 10; $element++) :
                $tag = 'openpublishing_template_tag_' . $element;
                $template = 'openpublishing_template_id_' . $element;
                $id = get_option($template); ?>
                <tr valign="top">
                    <th scope="row">Template #<?= $element ?></th>
                    <td class="regular-text"><input type="text" title="tag_name" placeholder="tag_name"
                                                    name="<?= $tag ?>" value="<?= esc_attr(get_option($tag)) ?>"
                                                    class="regular-text"/></td>
                    <td><input type="number" title="template id (Elementor template or post id)" placeholder="id"
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
    <a name="faq">
        <!--Version updated on 10.06.2019-->
        <div class="postbox" style="padding:20px;">
            <h1 style="padding-left:20px;">Openpublishing substitution help</h1>
    </a>
    <div>
        <p>To visualize Open Publishing data on your page please use smart substitution tags in place you want to insert
            a data, like so [<b>large_document_view:document.123</b>] or [<b>sidebar:newest.1:de</b>]</p>
        <p>This substitution tag will be replaced by the data this plugin retrieve from OP server. </p>
        <h3>Smart substitution</h3>
        <p>Smart substitution placeholder syntax is: <code>{ "tag_name":"object"."object_id":"language" ]</code></p>
        <h4>Language</h4>
        <p>Language is an optional parameter used to get language specific results. Possible values are: <code>en</code>,
            <code>de</code>, <code>fr</code>, <code>es</code>.</p>
        <h4>Objects</h4>
        <p>You can use: <code>document</code>, <code>bestseller</code>, <code>most_read</code>, <code>newest</code> as
            objects.</p>
        <h3>Legacy Smart substitution</h3>
        <p>Until version 1.6 the smart substitution notation was: <code>[ "tag_name":"object"."object_id":"language" ]</code></p>
        <p><code>language</code> is an optional parameter, use it to get language specific results. Possible values:
            <code>en</code>, <code>de</code>, <code>fr</code>, <code>es</code>.</p>
        <p>You can use: <code>document</code>, <code>bestseller</code>, <code>most_read</code>, <code>newest</code>
            objects.</p>
        <h3>Preparatory steps:</h3>
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
            <li>Use special keywords in your template:<code>{title} {subtitle} {price} {grin_url} {source_url}
                    {document_id} {cdn_host} {realm_id}</code></li>
        </ul>
        <p>Add newly created templates/posts on this page with corresponding ids.</p>
        <h3>Usage:</h3>
        <p>To use substitution please insert into your page this tag with desired 'tag_name' and 'object_id' like:</p>
        <xmp>
            <h2>Our bestsellers:</h2>
            <div>[large_document_view:bestseller.1] [large_document_view:bestseller.2]
                [large_document_view:bestseller.3]
            </div>
        </xmp>
        <p>This tags should be replaced right away.</p>
        <h4>Debug:</h4>
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
    <a name="faq_count">
        <h1 style="padding-left:20px; padding-top:20px">Openpublishing document count help</h1></a>
    <div>
        <p>There is a scheduled job which is retrieving the brand statistics from OP server and saves the value to <i>Document
                count</i> on a daily basis.</p>
        <p>To print document count use 'openpublishing_document_count' option.</p>
        <h3>Usage:</h3>
        <xmp><?php echo "        <h1><?php echo 'Total count of published documents: ' . get_option('openpublishing_document_count'); ?></h1>"; ?></xmp>
        <p>Next example shows how to create placeholder text for header search input with text <i>"219.240 eBooks &amp;
                Bücher"</i>:</p>
        <xmp><?php echo '        <input data-widget="SearchTagAutocomplete" type="text" class="search-input ac_input" name="searchstring" value="" autocomplete="off"
        placeholder="<?php echo get_option("openpublishing_document_count") . " "; pll_e("Text Suchleiste"); ?>" />'; ?>
        </xmp>
    </div>
    </div>
    <?php
}


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
