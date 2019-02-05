<?php

// create custom plugin settings menu
add_action('admin_menu', 'openpublishing_create_menu');


function openpublishing_create_menu() {

  //create new top-level menu
  add_menu_page( 'Open Publishing Page', 'Open Publishing', 'manage_options', 'openpublishing', 'openpublishing_add_menu' );

  //call register settings function
  add_action( 'admin_init', 'openpublishing_register_settings' );
}

function openpublishing_add_menu() {
?>
  <h1>Open Publishing Plugin</h1>
  <p class="about-description">Enrich content with data from the OpenPublishing service</p>


  <form method="post" action="options.php">
    <?php settings_fields( 'openpublishing-settings-group' ); ?>
    <?php do_settings_sections( 'openpublishing-settings-group' ); ?>
    <table class="form-table">
        <tr valign="top">
        <th scope="row">Realm ID*</th>
        <td>
            <input type="text" name="openpublishing_realm_id" value="<?php echo esc_attr( get_option('openpublishing_realm_id') ); ?>" />
            <span class="description">The Id of your Openpublishing Realm</span>
        </td>
        </tr>

        <tr valign="top">
        <th scope="row">API Host*</th>
        <td>
            <input type="text" name="openpublishing_api_host" value="<?php echo esc_attr( get_option('openpublishing_api_host') ); ?>" />
            <span class="description">Your Openpublishing API url</span>
        </td>
        </tr>
        <tr valign="top">
        <th scope="row">Auth Token</th>
        <td>
            <input disabled type="text" name="openpublishing_auth_token" value="<?php echo esc_attr( get_option('openpublishing_auth_token') ); ?>" />
            <span class="description">A token which allows access to your realm as world user</span></td>
        </td>
        </tr>

    </table>
    <h3>Open Publishing substitutions:</h3>
    <p class="about-description">Get more information about Open Publishing substitutions <a href="#faq">here</a></p>
    <table class="form-table">
        <?php
            for ($element = 1; $element <= 10; $element++) {
                $tag = 'openpublishing_template_tag_' . $element;
                $template = 'openpublishing_template_id_' . $element;
                $id = get_option($template);
                echo '<tr valign="top"><th scope="row">Tag   ' . $element . '</th>';
                echo '<td><input type="text" title="tag_name" name="' . $tag . '" value="' . esc_attr( get_option($tag) ) . '" />';
                echo '<input type="number" title="id (tag_name template id)" name="' . $template . '" value="' . esc_attr( get_option($template) ) . '" />';
                if ($id) {
                    if(defined('ELEMENTOR_PATH') && class_exists('Elementor\Widget_Base')) {
                        echo ' <a href="/wp-admin/post.php?post='.get_option($template).'&action=elementor">edit with Elementor</a>';
                        echo  ' | ';
                    }
                        echo ' <a href="/wp-admin/post.php?post='.get_option($template).'&action=edit">edit</a>';
                }
                echo '</td></tr></tr>';
            }
        ?>
    </table>
    <?php submit_button(); ?>
    </form>
    <a name="faq">
    <!--Version updated on 04.02.2019-->
    <div class="postbox" style="padding:20px;">
        <h1 style="padding-left:20px;">Openpublishing substitution help</h1></a>
        <div>
            <p>To visualize Open Publishing data on your page please use smart substitution tags in place you want to insert a data, like so [<b>large_document_view:document.123</b>] or [<b>sidebar:newest.1:de</b>]</p>
            <p>This substitution tag will be replaced by the data this plugin retrieve from OP server. </p>
            <p>Smart substitution notation contain: <code>[ "tag_name":"object"."object_id":"language" ]</code></p>
            <p><code>language</code> is an optional parameter, use it to get language specific results. Possible values: <code>en</code>, <code>de</code>, <code>fr</code>, <code>es</code>.</p>
            <p>You can use: <code>document</code>, <code>bestseller</code>, <code>most_read</code>, <code>newest</code> objects.</p>
            <h3>Preparatory steps:</h5>
            <p>To make this work please create templates for each tag_name you would like to use. You can do this in two different ways.</p>
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
                        <li>Create new post and treat it like a template, remember a template id (you can see it in the edit url)</li>
                        <li>(Optional) you can assign special category (like openpublishing-templates) to each post to distinguish between others</li>
                    </ol>
                </li></br>
                <li>Use special keywords in your template:<code>{title} {subtitle} {price} {grin_url} {source_url} {document_id} {cdn_host} {realm_id}</code></li>
            </ul>
            <p>Add newly created templates/posts on this page with corresponding ids.</p>
            <h3>Usage:</h3>
            <p>To use substitution please insert into your page this tag with desired 'tag_name' and 'object_id' like:</p>
                <xmp>
        <h2>Our bestsellers:</h2>
        <div>[large_document_view:bestseller.1] [large_document_view:bestseller.2] [large_document_view:bestseller.3]</div>
                </xmp>
            <p>This tags should be replaced right away.</p>
            <h4>Debug:</h4>
            <p>To debug plugin work please add next code into your page after all text:</p>
            <xmp>
        <script type="text/javascript">
            var debug = document.getElementsByClassName("OP_debug");
            for(i=0; i<debug.length; i++) { debug[i].style.display = 'inline'; }
        </script></xmp>
            <p>This will allow you to see some more information about substitution.</p>
        </div>
    </div>
<?php
}



function openpublishing_register_settings() {

  //register our settings
  register_setting( 'openpublishing-settings-group', 'openpublishing_realm_id', array(
    'type'              => 'integer',
    'description'       => 'The Id of your Openpublishing Realm'
  ));
  register_setting( 'openpublishing-settings-group', 'openpublishing_auth_token', array(
    'type'              => 'string',
    'description'       => 'An auth token which allows access to your realm as world user'
  ));
  register_setting( 'openpublishing-settings-group', 'openpublishing_api_host', array(
    'type'              => 'string',
    'description'       => 'Your Openpublishing api url'
  ));
  for ($element = 1; $element <= 10; $element++) {
      register_setting( 'openpublishing-settings-group', 'openpublishing_template_id_' . $element, array(
          'type'              => 'integer',
          'description'       => 'Template id #' . $element
      ));

    register_setting( 'openpublishing-settings-group', 'openpublishing_template_tag_' . $element, array(
        'type'              => 'string',
        'description'       => 'Tag name '. $element
    ));
  }
}
?>
