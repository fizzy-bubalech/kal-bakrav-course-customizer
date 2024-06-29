<?php
/**
 * @license GPL-3.0-or-later
 *
 * Modified by learndash on 18-June-2024 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace StellarWP\Learndash\StellarWP\Models\Contracts;

use StellarWP\Learndash\StellarWP\Models\ModelQueryBuilder;

/**
 * @since 1.0.0
 */
interface ModelReadOnly {
	/**
	 * @since 1.0.0
	 *
	 * @param int $id
	 *
	 * @return Model
	 */
	public static function find( $id );

	/**
	 * @since 1.0.0
	 *
	 * @return ModelQueryBuilder
	 */
	public static function query();

	/**
	 * @since 1.0.0
	 *
	 * @param $object
	 *
	 * @return Model
	 */
	public static function fromQueryBuilderObject( $object );
}
