<?php
/***
 * Base class for shortcodes.
 *
 * @since 2.0.9
 *
 * @package LearnDash\Course_Grid
 */

namespace LearnDash\Course_Grid\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Base class for shortcodes.
 *
 * @since 2.0.9
 */
abstract class Base {
	/**
	 * Shortcode tag.
	 *
	 * @since 2.0.9
	 *
	 * @var string
	 */
	protected $tag;

	/**
	 * Constructor.
	 *
	 * @since 2.0.9
	 */
	public function __construct() {
		$this->register();
	}

	/**
	 * Register shortcode.
	 *
	 * @since 2.0.9
	 *
	 * @return void
	 */
	private function register() {
		add_shortcode( $this->tag, [ $this, 'render' ] );
	}

	/**
	 * Render shortcode.
	 *
	 * @since 2.0.9
	 *
	 * @param array<string, string> $atts    Shortcode attributes.
	 * @param string                $content Shortcode content.
	 *
	 * @return string Shortcode output.
	 */
	abstract protected function render( $atts, $content );

	/**
	 * Get default shortcode attributes.
	 *
	 * @since 2.0.9
	 *
	 * @return array<string, mixed>
	 */
	abstract protected function get_default_atts();

	/**
	 * Validate attributes type.
	 *
	 * @since 2.0.9
	 *
	 * @param array $atts Attributes.
	 *
	 * @return array
	 */
	protected function validate_atts_type( array $atts ): array {
		$default_atts = $this->get_default_atts();

		foreach ( $default_atts as $key => $value ) {
			$type = gettype( $value );
			$value_type = gettype( $atts[ $key ] );

			if ( $type !== $value_type ) {
				switch ( $type ) {
					case 'boolean':
						$atts[ $key ] = 'true' === $atts[ $key ] || true === $atts[ $key ] || '1' === $atts[ $key ] || 1 === $atts[ $key ];
						break;

					case 'integer':
						$atts[ $key ] = intval( $atts[ $key ] );
						break;

					case 'double':
						$atts[ $key ] = floatval( $atts[ $key ] );
						break;
				}
			}
		}

		return $atts;
	}

	/**
	 * Process attributes as HTML data attributes.
	 *
	 * @since 2.0.9
	 *
	 * @param array<string, mixed> $atts Attributes.
	 *
	 * @return string
	 */
	protected function process_attributes_as_html_attributes( array $atts = [] ): string {
		$attributes = '';

		foreach ( $atts as $key => $value ) {
			if ( is_array( $value ) ) {
				$value = implode( ',', $value );
			}

			$attributes .= ' data-' . $key . '="' . $value . '"';
		}

		return $attributes;
	}
}
