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
		$rendered_shortcodes = $this->render_shortcode_array( $tree );
		return implode( '', $rendered_shortcodes );
	}
	/**
	 * Processes the next token from the input document
	 * and returns whether to proceed eating more tokens
	 *
	 * This is the "next step" function that essentially
	 * takes a token as its input and decides what to do
	 * with that token before descending deeper into a
	 * nested shortcode tree or continuing along the document
	 * or breaking out of a level of nesting.
	 *
	 * @internal
	 * @since 0.1.0
	 * @return bool
	 */
	function render_shortcode_array( $shortcodes ) {
		return array_map( array( $this, 'render_shortcode' ), $shortcodes );
	}
	/**
	 * Scans the document from where we last left off
	 * and finds the next valid token to parse if it exists
	 *
	 * Returns the type of the find: kind of find, shortcode information, attributes
	 *
	 * @internal
	 * @since 0.1.0
	 * @return array
	 */
	function render_shortcode( $shortcode ) {
		global $shortcode_tags;
		if( empty( $shortcode['innerShortcodes'] ) ) {
			$content = implode('', $shortcode['innerContent'] );
		} else {
			$content = $this->interleave_shortcodes( $shortcode['innerContent'], $shortcode['innerShortcodes'] );
		}
		if( is_null( $shortcode['shortcodeName'] ) ) {
			return $content;
		}
		$tag = $shortcode['shortcodeName'];
		if( ! is_callable( $shortcode_tags[ $tag ] ) ) {
			/* translators: %s: shortcode tag */
			$message = sprintf( __( 'Attempting to parse a shortcode without a valid callback: %s' ), $tag );
			_doing_it_wrong( __FUNCTION__, $message, '4.3.0' );
			return $shortcode->rawTag;
		}
		$return = apply_filters( 'pre_do_shortcode_tag', false, $tag, $shortcode['attrs'], $content, $shortcode );
		if ( false !== $return ) {
			return $return;
		}

		$output = call_user_func( $shortcode_tags[ $tag ], $shortcode['attrs'], $content, $tag );

		/**
		* Filters the output created by a shortcode callback.
		*
		* @since 4.7.0
		*
		* @param string       $output    Shortcode output.
		* @param string       $tag       Shortcode name.
		* @param array|string $attr      Shortcode attributes array or empty string.
		* @param string       $content   Shortcode content or empty string.
		* @param array        $shortcode shortcode tree entry.
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
				$content .= $this->render_shortcode( $shortcodes[$j] );
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
