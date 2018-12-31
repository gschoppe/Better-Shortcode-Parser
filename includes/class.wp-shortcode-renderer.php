<?php if( !defined( 'ABSPATH' ) ) die;


class WP_Shortcode_Renderer {


	public function __construct( ) {

	}
	/**
	 * Parses a document tree and returns a rendered block of HTML
	 *
	 * @since 0.1.0
	 *
	 * @param array $tree Input document tree from WP_Shortcode_Parser.
	 * @return string
	 */
	function render( $tree ) {
		$rendered_shortcodes = array_map(
			array( $this, 'render_shortcode' ),
			$tree
		);
		return implode( '', $rendered_shortcodes );
	}

	/**
	 * renders a shortcode tree node into a flat HTML string.
	 *
	 * Returns the rendered shortcode as a string
	 *
	 * @internal
	 * @since 0.1.0
	 *
	 * @param array $shortcode Shortcode tree node
	 * @return string
	 */
	function render_shortcode( $shortcode ) {
		global $shortcode_tags;
		$tag = '';
		if( !is_null( $shortcode['shortcodeName'] ) ) {
			$tag = $shortcode['shortcodeName'];
		}

		/**
		* short circuit filter to either replace the nested renderer, or to set global contexts for nested shortcodes
		*
		* @since 0.2.0
		*
		* @param string|false $content   Shortcode content.
		* @param string       $tag       Shortcode name.
		* @param array|string $attr      Shortcode attributes array or empty string.
		* @param array        $shortcode shortcode tree node.
		*/
		$content = apply_filters( 'shortcode_pre_render_nested_content', false, $tag, $shortcode['attrs'], $shortcode );
		if( $content === false ) {
			if( empty( $shortcode['innerShortcodes'] ) ) {
				$content = implode('', $shortcode['innerContent'] );
			} else {
				$content = $this->interleave_shortcodes( $shortcode['innerContent'], $shortcode['innerShortcodes'] );
			}
		}
		/**
		* filter the nested content of the shortcode, after parsing. can be used to reset global contexts, such as $post
		*
		* @since 0.2.0
		*
		* @param string       $content   Shortcode content.
		* @param string       $tag       Shortcode name.
		* @param array|string $attr      Shortcode attributes array or empty string.
		* @param array        $shortcode shortcode tree node.
		*/
		$content = apply_filters( 'shortcode_post_render_nested_content', $content, $tag, $shortcode['attrs'], $shortcode );

		// if there is no tag, this isn't a shortcode, but rather just a "freeform" content area
		if( !$tag ) {
			return $content;
		}

		// error state
		if( ! is_callable( $shortcode_tags[ $tag ] ) ) {
			/* translators: %s: shortcode tag */
			$message = sprintf( __( 'Attempting to parse a shortcode without a valid callback: %s' ), $tag );
			_doing_it_wrong( __FUNCTION__, $message, '4.3.0' );
			return $shortcode->rawTag;
		}

		/**
		* short circuit filter for shortcode parsing function
		*
		* @since 0.1.0
		*
		* @param string       $output    Shortcode output.
		* @param string       $tag       Shortcode name.
		* @param array|string $attr      Shortcode attributes array or empty string.
		* @param string       $content   Shortcode content or empty string.
		* @param array        $shortcode shortcode tree node.
		*/
		$return = apply_filters( 'pre_do_shortcode_tag', false, $tag, $shortcode['attrs'], $content, $shortcode );
		if ( false !== $return ) {
			return $return;
		}

		$output = call_user_func( $shortcode_tags[ $tag ], $shortcode['attrs'], $content, $tag );

		/**
		* Filters the output created by a shortcode callback.
		*
		* @since 0.1.0
		*
		* @param string       $output    Shortcode output.
		* @param string       $tag       Shortcode name.
		* @param array|string $attr      Shortcode attributes array or empty string.
		* @param string       $content   Shortcode content or empty string.
		* @param array        $shortcode shortcode tree node.
		*/
		return apply_filters( 'do_shortcode_tag', $output, $tag, $shortcode->attrs, $content, $shortcode );
	}

	function interleave_shortcodes( $content_parts, $shortcodes ) {
		$content = '';
		$j = 0;
		for( $i = 0; $i < count( $content_parts ); $i++ ) {
			if( !is_null( $content_parts[$i] ) ) {
				$content .= $content_parts[$i];
			} else {
				if( $j < count($shortcodes) ) {
					$content .= $this->render_shortcode( $shortcodes[$j] );
				}
				$j++;
			}
		}
		if( $j < count( $shortcodes ) ) {
			// this is an error state, since interleaving failed
			for( $j; $j < count($shortcodes); $j++ ) {
				$content .= $this->render_shortcode( $shortcodes[$j] );
			}
		}
		return $content;
	}
}
