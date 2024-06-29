<?php
/**
 * Provider for initializing the LearnDash Core plugin.
 *
 * @since 4.6.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core;

use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Class Provider for the LearnDash Core.
 *
 * @since 4.6.0
 */
class Provider extends ServiceProvider {
	/**
	 * Registers the service provider bindings.
	 *
	 * @since 4.6.0
	 *
	 * @throws ContainerException If the registration fails.
	 *
	 * @return void
	 */
	public function register(): void {
		// Registering implementations.

		$this->container->register( Settings\Provider::class );
		$this->container->register( Modules\Provider::class );
		$this->container->register( Payments\Provider::class ); // TODO: Move to modules one day.
		$this->container->register( Infrastructure\Provider::class );

		// Registering in-progress features.

		// bail early if in-progress features are not enabled.
		if ( ! defined( 'LEARNDASH_ENABLE_IN_PROGRESS_FEATURES' ) || ! LEARNDASH_ENABLE_IN_PROGRESS_FEATURES ) { // @phpstan-ignore-line -- constant can be changed.
			return;
		}

		// Breezy template.
		if ( defined( 'LEARNDASH_ENABLE_FEATURE_BREEZY_TEMPLATE' ) && LEARNDASH_ENABLE_FEATURE_BREEZY_TEMPLATE ) { // @phpstan-ignore-line -- constant can be changed.
			$this->container->register( Themes\Provider::class );
		}
	}
}
