<?php if( !defined( 'ABSPATH' ) ) die;

/**
 * Class WP_Shortcode_Parser_Shortcode
 *
 * Holds the shortcode structure in memory
 *
 * @since 0.1.0
 */
class WP_Shortcode_Parser_Shortcode {
	/**
	 * Name of shortcode
	 *
	 * @example "gallery"
	 *
	 * @since 0.1.0
	 * @var string
	 */
	public $shortcodeName;
	/**
	 * Optional set of attributes from shortcode
	 *
	 * @example null
	 * @example array( 'columns' => 3 )
	 *
	 * @since 0.1.0
	 * @var array|null
	 */
	public $attrs;
	/**
	 * List of inner shortcodes (of this same class)
	 *
	 * @since 0.1.0
	 * @var WP_Shortcode_Parser_Shortcode[]
	 */
	public $innerShortcodes;
	/**
	 * Raw shortcode
	 *
	 * @example "[shortcode]Just [test] testing[/shortcode]"
	 *
	 * @since 0.1.0
	 * @var string
	 */
	public $rawTag;
	/**
	 * Raw content of shortcode
	 *
	 * @example "...Just [test] testing..."
	 *
	 * @since 0.1.0
	 * @var string
	 */
	public $rawContent;
	/**
	 * List of string fragments and null markers where nested shortcodes were found
	 *
	 * @example array(
	 *   'innerHTML'    => 'BeforeInnerAfter',
	 *   'innerShortcodes'  => array( shortcode, shortcode ),
	 *   'innerContent' => array( 'Before', null, 'Inner', null, 'After' ),
	 * )
	 *
	 * @since 0.1.0
	 * @var array
	 */
	public $innerContent;
	/**
	 * Constructor.
	 *
	 * Will populate object properties from the provided arguments.
	 *
	 * @since 3.8.0
	 *
	 * @param string $name         Name of shortcode.
	 * @param array  $attrs        Optional set of attributes from shortcode.
	 * @param array  $innerBlocks  List of inner shortcodes (of this same class).
	 * @param string $rawContent   raw content of a shortcode, including all nested shortcodes
	 * @param array  $innerContent List of string fragments and null markers where nested shortcodes were found.
	 */
	function __construct( $name, $attrs, $innerShortcodes, $rawTag, $rawContent, $innerContent ) {
		$this->shortcodeName   = $name;
		$this->attrs           = $attrs;
		$this->innerShortcodes = $innerShortcodes;
		$this->rawTag          = $rawTag;
		$this->rawContent      = $rawContent;
		$this->innerContent    = $innerContent;
	}
}
/**
 * Class WP_Shortcode_Parser_Frame
 *
 * Holds partial shortcodes in memory while parsing
 *
 * @internal
 * @since 0.1.0
 */
class WP_Shortcode_Parser_Frame {
	/**
	 * Full or partial shortcode
	 *
	 * @since 0.1.0
	 * @var WP_Shortcode_Parser_Block
	 */
	public $shortcode;
	/**
	 * Byte offset into document for start of parse token
	 *
	 * @since 0.1.0
	 * @var int
	 */
	public $token_start;
	/**
	 * Byte length of entire parse token string
	 *
	 * @since 0.1.0
	 * @var int
	 */
	public $token_length;
	/**
	 * Byte offset into document for after parse token ends
	 * (used during reconstruction of stack into parse production)
	 *
	 * @since 0.1.0
	 * @var int
	 */
	public $prev_offset;
	/**
	 * Byte offset into document where leading HTML before token starts
	 *
	 * @since 0.1.0
	 * @var int
	 */
	public $leading_html_start;
	/**
	 * Constructor
	 *
	 * Will populate object properties from the provided arguments.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_Shortcode_Parser_Shortcode $shortcode          Full or partial block.
	 * @param int                           $token_start        Byte offset into document for start of parse token.
	 * @param int                           $token_length       Byte length of entire parse token string.
	 * @param int                           $prev_offset        Byte offset into document for after parse token ends.
	 * @param int                           $leading_html_start Byte offset into document where leading HTML before token starts.
	 */
	function __construct( $shortcode, $token_start, $token_length, $prev_offset = null, $leading_html_start = null ) {
		$this->shortcode          = $shortcode;
		$this->token_start        = $token_start;
		$this->token_length       = $token_length;
		$this->prev_offset        = isset( $prev_offset ) ? $prev_offset : $token_start + $token_length;
		$this->leading_html_start = $leading_html_start;
	}
}


class WP_Shortcode_Parser {
	/**
	 * flag enabling debug output
	 *
	 * @since 0.1.0
	 * @var boolean
	 */
	public $debug;
	/**
	 * Input document being parsed
	 *
	 * @example "Pre-text\n[shortcode att="example"]This is inside a shortcode![/shortcode]"
	 *
	 * @since 0.1.0
	 * @var string
	 */
	public $document;
	/**
	 * Tracks parsing progress through document
	 *
	 * @since 0.1.0
	 * @var int
	 */
	public $offset;
	/**
	 * List of parsed shortcodes0
	 *
	 * @since 0.1.0
	 * @var WP_Shortcode_Parser_Shortcode[]
	 */
	public $output;
	/**
	 * Stack of partially-parsed structures in memory during parse
	 *
	 * @since 0.1.0
	 * @var WP_Shortcode_Parser_Frame[]
	 */
	public $stack;

	public function __construct( $debug = false ) {
		$this->debug = $debug;
	}
	/**
	 * Parses a document and returns a list of shortcode structures
	 *
	 * When encountering an invalid parse will return a best-effort
	 * parse. In contrast to the specification parser this does not
	 * return an error on invalid inputs.
	 *
	 * @since 0.1.0
	 *
	 * @param string $document Input document being parsed.
	 * @return WP_Shortcode_Parser_Shortcode[]
	 */
	function parse( $document ) {
		$this->document    = $document;
		$this->offset      = 0;
		$this->output      = array();
		$this->stack       = array();
		do {
			// twiddle our thumbs.
		} while ( $this->proceed() );
		return $this->output;
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
	function proceed() {
		$next_token = $this->next_token();

		$token_type     = $next_token['token_type'    ];
		$shortcode_name = $next_token['shortcode_name'];
		$attrs          = $next_token['attrs'         ];
		$start_offset   = $next_token['start_offset'  ];
		$token_length   = $next_token['token_length'  ];
		$rawToken       = substr( $this->document, $start_offset, $token_length );

		$stack_depth = count( $this->stack );
		// we may have some HTML soup before the next shortcode.
		$leading_html_start = null;
		if( $start_offset > $this->offset ) {
			$leading_html_start = $this->offset;
		}

		switch ( $token_type ) {
			case 'no-more-tokens':
				// if not in a shortcode then flush output.
				if ( 0 === $stack_depth ) {
					$this->add_freeform();
					return false;
				}

				// NOTE: THIS NEEDS TO BE REPLACED, but will work for testing.
				/*
				 * for the nested case where it's more difficult we'll
				 * have to assume that multiple closers are missing
				 * and so we'll collapse the whole stack piecewise
				 */
				while ( 0 < count( $this->stack ) ) {
					$this->add_shortcode_from_stack();
				}
				return false;
			case 'escaped-shortcode':
				$this->output[] = (array) self::freeform(
					substr(
						$this->document,
						$start_offset + 1,
						$token_length - 2
					)
				);
				$this->offset = $start_offset + $token_length;
				return true;
			case 'void-shortcode':
				/*
				 * easy case is if we stumbled upon a void shortcode
				 * in the top-level of the document
				 */
				if ( 0 === $stack_depth ) {
					if ( isset( $leading_html_start ) ) {
						$this->output[] = (array) self::freeform(
							substr(
								$this->document,
								$leading_html_start,
								$start_offset - $leading_html_start
							)
						);
					}
					$this->output[] = (array) new WP_Shortcode_Parser_Shortcode( $shortcode_name, $attrs, array(), $rawToken, '', array() );
					$this->offset   = $start_offset + $token_length;
					return true;
				}
				// otherwise we found an inner shortcode.
				$this->add_inner_shortcode(
					new WP_Shortcode_Parser_Shortcode( $shortcode_name, $attrs, array(), $rawToken, '', array() ),
					$start_offset,
					$token_length
				);
				$this->offset = $start_offset + $token_length;
				return true;
			case 'shortcode-opener':
				// track all newly-opened shortcodes on the stack.
				array_push(
					$this->stack,
					new WP_Shortcode_Parser_Frame(
						new WP_Shortcode_Parser_Shortcode( $shortcode_name, $attrs, array(), $rawToken, '', array() ),
						$start_offset,
						$token_length,
						$start_offset + $token_length,
						$leading_html_start
					)
				);
				$this->offset = $start_offset + $token_length;
				return true;
			case 'shortcode-closer':
				/*
				 * if we're missing an opener we're in trouble
				 * This is an error
				 */
				 if ( 0 === $stack_depth ) {
 					$this->add_freeform( $token_length );
 					return false;
 				}
				$stack_position = $this->find_last_in_stack( $shortcode_name );
				if( $stack_position === false ) {
					$this->add_freeform( $token_length );
					return true;
				}


				$this->reflow_to_self_closing( $stack_position );
				$stack_depth = count( $this->stack );

				// if we're not nesting then this is easy - close the block.
				if ( 1 === $stack_depth ) {
					$this->add_shortcode_from_stack( $start_offset, $start_offset + $token_length );
					$this->offset = $start_offset + $token_length;
					return true;
				}

				$stack_top  = array_pop( $this->stack );
				$rawTag     = substr( $this->document, $stack_top->token_start, $start_offset + $token_length - $stack_top->token_start );
				$stack_top->shortcode->rawTag     = $rawTag;
				$start_of_content = $stack_top->token_start + $stack_top->token_length;
				$rawContent = substr( $this->document, $start_of_content, $start_offset - $start_of_content );
				$stack_top->shortcode->rawContent     = $rawContent;
				$html = substr( $this->document, $stack_top->prev_offset, $start_offset - $stack_top->prev_offset );
				$stack_top->shortcode->innerContent[] = $html;
				$stack_top->prev_offset           = $start_offset + $token_length;
				$this->add_inner_shortcode(
					$stack_top->shortcode,
					$stack_top->token_start,
					$stack_top->token_length,
					$start_offset + $token_length
				);
				$this->offset = $start_offset + $token_length;
				return true;

			default:
				// This is an error.
				$this->add_freeform();
				return false;
		}
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
	function next_token() {
		$matches = null;

		$regex = $this->build_tokenizer_regex();
		$has_match = preg_match(
			$regex,
			$this->document,
			$matches,
			PREG_OFFSET_CAPTURE,
			$this->offset
		);

		// no more matches
		if ( false === $has_match || 0 === $has_match ) {
			return array(
				'token_type'     => 'no-more-tokens',
				'shortcode_name' => null,
				'attrs'          => null,
				'start_offset'   => null,
				'token_length'   => null
			);
		}
		list( $match, $started_at ) = $matches[0];
		$length     = strlen( $match );
		$is_escaped = isset( $matches['escleft'] ) && -1 !== $matches['escleft'][1]
					&& isset( $matches['escright'] ) && -1 !== $matches['escright'][1];
		$is_closer  = isset( $matches['closer'] ) && -1 !== $matches['closer'][1];
		$is_void    = isset( $matches['void'] ) && -1 !== $matches['void'][1];
		$name       = $matches['name'][0];
		$has_attrs  = isset( $matches['attrs'] ) && -1 !== $matches['attrs'][1];
		$attrs = $has_attrs
			? $this->decode_attributes( $matches['attrs'][0] )
			: array();

		$type = 'error';
		if($is_escaped) {
			$type = 'escaped-shortcode';
		} elseif ( $is_void ) {
			$type = 'void-shortcode';
		} elseif ( $is_closer ) {
			$type = 'shortcode-closer';
			$attrs = null; // closing tags don't have attributes
		} else {
			$type = 'shortcode-opener';
		}
		$this->debug( $type . ':' . $name, array( $started_at, $started_at + $length ) );
		return array(
			'token_type'     => $type,
			'shortcode_name' => $name,
			'attrs'          => $attrs,
			'start_offset'   => $started_at,
			'token_length'   => $length
		);
	}
	/**
	 * Returns the tokenizer regex used to identify shortcodes in the content
	 *
	 * @internal
	 * @since 0.1.0
	 *
	 * @return string tokenizer regular expression.
	 */
	function build_tokenizer_regex() {
		/*
		 * aye the magic
		 * we're using a single RegExp to tokenize the shortcode delimiters
		 * we're also using a trick here because the only difference between a
		 * shortcode opener and a shortcode closer is the leading `/` (and
		 * a closer has no attributes). we can trap them both and process the
		 * match back in PHP to see which one it was.
		 */
		global $shortcode_tags;

		if ( empty( $tagnames ) ) {
			$tagnames = array_keys( $shortcode_tags );
		}
		$tags = join( '|', array_map('preg_quote', $tagnames) );


		$regex = '' // blank line to improve readability
		. '/\\['                   // open bracket
		. '(?<escleft>\\[)?'       // optional second bracket to escape shortcode
		. '(?<closer>\\/)?'        // if this is a closing tag, it starts with a slash
		. '\\s*'                   // optional whitespace
		. '(?<name>' . $tags . ')' // the shortcode name
		. '(?![\\w-])'             // the shortcode name must not be followed by more word-like characters

		// NOTE: this portion is lifted from WordPress's existing regex, but not fully understood
		. '(?<attrs>'              // Unroll the loop: Inside the opening shortcode tag
		.     '[^\\]\\/]*'         // Not a closing bracket or forward slash
		.     '(?:'
		.         '\\/(?!\\])'     // A forward slash not followed by a closing bracket
		.         '[^\\]\\/]*'     // Not a closing bracket or forward slash
		.     ')*?'
		. ')'
		// END NOTE

		. '(?<void>\\/)?'
		. '(?<escright>\\])?'      // optional second close bracket to escape shortcode
		. '\\]/s';                 // close bracket

		return $regex;
	}

	/**
	 * Returns a new shortcode object for freeform HTML
	 *
	 * @internal
	 * @since 0.1.0
	 *
	 * @param string $rawContent HTML content of shortcode.
	 * @return WP_Shortcode_Parser_Shortcode freeform shortcode object.
	 */
	function freeform( $rawContent ) {
		return new WP_Shortcode_Parser_Shortcode( null, array(), array(), $rawContent, $rawContent, array( $rawContent ) );
	}
	/**
	 * Pushes a length of text from the input document
	 * to the output list as a freeform shortcode.
	 *
	 * @internal
	 * @since 0.1.0
	 * @param null $length how many bytes of document text to output.
	 */
	function add_freeform( $length = null ) {
		$length = $length ? $length : strlen( $this->document ) - $this->offset;
		if ( 0 === $length ) {
			return;
		}
		$this->output[] = (array) self::freeform( substr( $this->document, $this->offset, $length ) );
	}
	/**
	 * Given a shortcode structure from memory pushes
	 * a new shortcode to the output list.
	 *
	 * @internal
	 * @since 3.8.0
	 * @param WP_Shortcode_Parser_Shortcode $shortcode    The shortcode to add to the output.
	 * @param int                           $token_start  Byte offset into the document where the first token for the shortcode starts.
	 * @param int                           $token_length Byte length of entire shortcode from start of opening token to end of closing token.
	 * @param int|null                      $last_offset  Last byte offset into document if continuing form earlier output.
	 */
	function add_inner_shortcode( WP_Shortcode_Parser_Shortcode $shortcode, $token_start, $token_length, $last_offset = null ) {
		$parent                               = $this->stack[ count( $this->stack ) - 1 ];
		$parent->shortcode->innerShortcodes[] = (array) $shortcode;
		$html                                 = substr( $this->document, $parent->prev_offset, $token_start - $parent->prev_offset );
		if ( ! empty( $html ) ) {
			$parent->shortcode->innerContent[] = $html;
		}
		$parent->shortcode->innerContent[] = null;
		$rawTag = substr( $this->document, $parent->token_start, $token_start + $token_length - $parent->token_start );
		$parent->shortcode->rawTag = $rawTag;
		$start_of_content = $parent->token_start + $parent->token_length;
		$parent->shortcode->rawContent = substr( $this->document, $start_of_content, $token_start - $start_of_content );
		$parent->prev_offset = $last_offset ? $last_offset : $token_start + $token_length;
	}
	/**
	 * Pushes the top shortcode from the parsing stack to the output list.
	 *
	 * @internal
	 * @since 3.8.0
	 * @param int|null $end_offset byte offset into document for where we should stop sending text output as HTML.
	 */
	function add_shortcode_from_stack( $end_offset = null, $end_tag = null ) {
		$stack_top   = array_pop( $this->stack );
		$prev_offset = $stack_top->prev_offset;
		$html = isset( $end_offset )
			? substr( $this->document, $prev_offset, $end_offset - $prev_offset )
			: substr( $this->document, $prev_offset );
		if ( ! empty( $html ) ) {
			$stack_top->shortcode->innerContent[] = $html;
		}
		$rawTag = isset( $end_tag )
			? substr( $this->document, $stack_top->token_start, $end_tag - $stack_top->token_start )
			: substr( $this->document, $stack_top->token_start );
		if ( ! empty( $rawTag ) ) {
			$stack_top->shortcode->rawTag     = $rawTag;
		}
		$start_of_content = $stack_top->token_start + $stack_top->token_length;
		$content = isset( $end_offset )
			? substr( $this->document, $start_of_content, $end_offset - $start_of_content )
			: substr( $this->document, $start_of_content );
		if ( ! empty( $content ) ) {
			$stack_top->shortcode->rawContent     = $content;
		}
		if ( isset( $stack_top->leading_html_start ) ) {
			$this->output[] = (array) self::freeform(
				substr(
					$this->document,
					$stack_top->leading_html_start,
					$stack_top->token_start - $stack_top->leading_html_start
				)
			);
		}
		$this->output[] = (array) $stack_top->shortcode;
	}

	function find_last_in_stack( $shortcode_name = '' ) {
		$last_index = count( $this->stack ) - 1;
		for( $i = $last_index; $i >= 0; $i-- ) {
			$name = $this->stack[$i]->shortcode->shortcodeName;
			if( $name == $shortcode_name ) {
				return $i;
			}
		}
		return false;
	}

	function reflow_to_self_closing( $index = 0 ) {
		$to_reflow = array_splice( $this->stack, $index + 1 );
		foreach( $to_reflow as $stack_entry ) {
			$this->debug( "Reflowing to close: " . $stack_entry->shortcode->shortcodeName, array( $stack_entry->token_start, $stack_entry->token_start + $stack_entry->token_length ) );
			$this->add_inner_shortcode(
				$stack_entry->shortcode,
				$stack_entry->token_start,
				$stack_entry->token_length
			);
		}
	}

	function decode_attributes( $raw_atts ) {
		$atts = shortcode_parse_atts( $raw_atts );

		return $atts;
	}

	private function debug( $message, $markers, $echo = true ) {
		if( !$this->debug ) {
			return;
		}
		if( !is_array( $markers ) ) {
			$markers = array( $markers );
		}
		$markers[] = strlen( $this->document ) -1;
		$parts = array();
		$last_index = 0;
		foreach( $markers as $marker ) {
			$part = substr( $this->document, $last_index, $marker - $last_index );
			$part = htmlspecialchars( $part );
			$part = str_replace( array( "\n","\r", "\t" ), array( "\\n","\\r", "\\t" ), $part );
			$parts[] = $part;
			$last_index = $marker;
		}
		$output  = "\n";
		$output .= '<div class="shortcode-parser-debug">';
		$output .= '<div class="spb-header">DEBUG: '.htmlspecialchars( $message ) .'</div>' . "\n";
		$output .= '<pre class="spd-body">';
		$output .= implode( '<span class="spb-marker">|</span>', $parts );
		$output .= '</pre></div>' . "\n";
		if( $echo ) {
			echo $output;
		}
		return $output;
	}
}
