<?php
/**
 * Settings provider class file.
 *
 * @since 4.15.2
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Settings;

use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Settings service provider class.
 *
 * @since 4.15.2
 */
class Provider extends ServiceProvider {
	/**
	 * Registers service providers.
	 *
	 * @since 4.15.2
	 *
	 * @return void
	 */
	public function register(): void {
		$this->container->register( Fields\Provider::class );
	}
}
