# Better Shortcode Parser
[Greg Schoppe](https://gschoppe.com)

---
an exploratory WordPress plugin that reimplements Shortcodes, using a three stage process, consisting of a tokenizer, a parser, and a renderer.
>**NOTE:** This is an early-stage proof of concept. It should not be used on any production site.
### Why?
The current shortcode parser uses a single regular expression to parse both self-closing shortcodes, like `[image id="14"]` and wrapping shortcodes, like `[callout]This is some content[/callout]`.
Because of the limitations of regular expressions, This prevents WordPress from natively supporting nested shortcodes, such as `[row][col]left content[/col][col]right content[/col][/row]`. Even with complicated systems that nest calls to the `do_shortcode` functionality, the current parser still can't handle self-nested shortcodes, such as `[container type="outer"][container type="inner"]area 1[/container][container type="inner"]area 2[/container][/container]`.

Beyond this, the current shortcode parser is just logically inconsistent. Its specification is defined by the technical limitations of the parser, rather than vice versa.

### How?

Better Shortcode Parser is based on the PHP Block Parser from Gutenberg, which was spearheaded by Dennis Snell. It has been improved with support for matching opening and closing tags, and some shortcode-specific enhancements, like retroactively converting wrapping shortcodes to self-closing shortcodes if no closing tag is found.

### Changes

If you are currently using a plugin that supports nestable shortcodes it will probably continue to work fine, however there are a couple of changes you should be aware of:

**Better Shortcode Parser renders nested shortcodes from the innermost level outwards**
This is necessary to meet the specifications for existing shortcode functions, which expect `$content` to be a string, rather than a tree structure.
**Shortcodes in Better Shortcode Parser are context-free.**
This means that shortcode rendering functions don't know what other shortcodes the current content is wrapped in.

### Resolving Context issues

The changes listed above mean that it is no longer possible to give context to nested shortcodes from inside the shortcode render callback itself. There are two possible routes to address this change

#### Modify Shortcodes to be Context-Free (preferred option)

In the case of shortcode structures like

```
[post id="37"]
  [featured-image]
  [title]
  [excerpt]
[/post]
```

It is a much better idea to refactor into a structure like this:

```
[post]
  [featured-image id="37"]
  [title id="37"]
  [excerpt id="37"]
[/post]
```

Shortcodes were not originally designed to be contextual, and keeping them atomic highlights the value of other structures, such as ACF repeaters, private post types, etc.

this sort of structure could also be accomplished via a private "template" post type, such that the main post would have a structure like this:

```
[post id="37" template="12"/]
```

and the accompanying template post would have a structure like this:

```
  [featured-image]
  [title]
  [excerpt]
```

#### Inject context via filter (alternate route)

For those use cases where it is unfeasible to refactor existing tropes, or where some confounding factor makes context a necessity, the renderer offers two filters that can be used to provide context to nested shortcodes.

Here is a simple example of how to implement such context-passing for the example structure used above:

```
function shortcode_context_injector( $retval, $tag, $atts, $shortcode ) {
  global $post, $my_backup_post;
  if( $tag == 'post' ) {
    if( !empty( $atts['id'] ) ) {
      $my_backup_post = $post;
      $post = get_post( $atts['id'] );
      setup_postdata( $post );
    } else {
      return ''; // in case of error, prevent all nested shortcodes from being parsed
    }
  }
  return $retval; // this is a short circuit filter. You must return the initial value for parsing to continue.
}

function shortcode_context_restorer( $retval, $tag, $atts, $shortcode ) {
  global $post, $my_backup_post;
  if( $tag == 'post' ) {
    if( !empty( $atts['id'] ) ) {
      $post = $my_backup_post;
      $my_backup_post = null;
      setup_postdata( $post );
    }
  }
  return $retval;
}

add_filter('shortcode_pre_render_nested_content',  'shortcode_context_injector', 10, 4);
add_filter('shortcode_post_render_nested_content', 'shortcode_context_restorer', 10, 4);
```

It should be noted that rather than using a single temporary global, as seen above, a more complete implementation would be implemented with a class property, containing a stack of parent-contexts that could be pushed and popped, as needed. This would enable self-nested shortcode structures with attached context, such as:

```
[context-switcher context="1"]
  [inner-shortcode]
  [context-switcher context="2"]
    [inner-shortcode]
  [/context-switcher]
[/context-switcher]
```
