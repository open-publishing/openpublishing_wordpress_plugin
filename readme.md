# OpenPublishing #

Enrich wordpress content with data from the OpenPublishing services

## How It Works ##

To visualize Open Publishing data on your page please use smart substitution tags in place you want to insert a data, like so `[large_document_view:document.123]`

This substitution tag will be replaced by the data this plugin retrieve from OP server.

Smart substitution notation contain: ``[ "tag_name":"object"."object_id" ]``

You can use: `document` and `bestseller` objects.

### Preparatory steps ###

To make this work please create templates for each tag_name you would like to use. You can do this in two different ways.

* Using Elementor plugin:

  1. Go to Elementor <a href="/wp-admin/edit.php?post_type=elementor_library">page</a>
  2. Create new template, remember a template id (you can see it in the edit url)
  3. You can easaly style your template by means of Elementor


* Using Wordpress posts:
  1. Go to <a href="wp-admin/edit.php">posts</a>
  2. Create new post and treat it like a template, remember a template id (you can see it in the edit url)
  3. (Optional) you can assign special category (like op-templates) to each post to distinguish between others
  4. Use special keywords in your template:`{title} {subtitle} {price} {grin_url} {source_url} {document_id} {cdn_host} {brand_id}`


Add newely created templates/posts on this page with corresponding ids.

### Usage: ###

To use substitution please insert into your page this tag with desired 'tag_name' and 'object_id' like:

    <h2>Our bestsellers:</h2>
      <div>[large_document_view:bestseller.1] [large_document_view:bestseller.2] [large_document_view:bestseller.3]</div>


This tags should be replaced right away.

### Debug: ###

To debug plugin work please add next code into your page after all text:

    <script type="text/javascript">
      var debug = document.getElementsByClassName("OP_debug");
      for(i=0; i<debug.length; i++) { debug[i].style.display = 'inline'; }
    </script>

This will allow you to see some more information about substitution.
