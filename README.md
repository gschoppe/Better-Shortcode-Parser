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

### Potential Breakage?

The changes listed above mean that it is no longer possible to give context to a shortcode in its wrapper, making structures like the following impossible with the new system:

```
[post id="37"]
  [featured-image]
  [title]
  [excerpt]
[/post]
```

Instead, you would build the same structure as:

```
[post]
  [featured-image id="37"]
  [title id="37"]
  [excerpt id="37"]
[/post]
```

I have implemented `rawTag` and `rawContent` fields in the tree structure that could likely be used to address this inconsistency, where necessary. However, it would require some restructuring of the shortcode rendering callback.
