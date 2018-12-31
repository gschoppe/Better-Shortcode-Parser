<?php if( !defined( 'ABSPATH' ) ) die;
/*
 Plugin Name: Better Shortcode Parser
 Plugin URI:  https://gschoppe.com
 Description: Better Shortcode Parser, supporting nested shortcodes
 Version:     0.3.0
 Author:      Greg Schoppe
 Author URI:  https://gschoppe.com
 License:     GPL-2.0+
 License URI: http://www.gnu.org/licenses/gpl-2.0.txt
*/

require_once('includes/class.wp-shortcode-parser.php');
require_once('includes/class.wp-shortcode-renderer.php');

class Better_Shortcode_Parser {
	private static $instance = null;

	private function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	public static function getInstance() {
		if( self::$instance == null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init() {
		remove_filter( 'the_content'        , 'do_shortcode', 11 );
		remove_filter( 'widget_text_content', 'do_shortcode', 11 );
		add_filter( 'the_content'        , array( $this, 'do_shortcode' ), 11 );
		add_filter( 'widget_text_content', array( $this, 'do_shortcode' ), 11 );
	}

	public function do_shortcode( $content, $ignore_html = false ) {
		$parser   = new WP_Shortcode_Parser( false );
		$renderer = new WP_Shortcode_Renderer();
		$doc_tree = $parser->parse( $content );
		$output   = $renderer->render( $doc_tree );

		return $output;
	}
}
Better_Shortcode_Parser::getInstance();
