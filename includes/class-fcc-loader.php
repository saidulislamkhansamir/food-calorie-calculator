<?php
/**
 * Registers all actions and filters for the plugin.
 *
 * @package FCC
 */

namespace FCC;

defined( 'ABSPATH' ) || exit;

/**
 * Maintains a list of hooks and bulk-registers them with WordPress.
 */
class Loader {

	/** @var array<array{hook:string,instance:object,method:string,priority:int,args:int}> */
	private array $actions = [];

	/** @var array<array{hook:string,instance:object,method:string,priority:int,args:int}> */
	private array $filters = [];

	/**
	 * Queue an action hook.
	 */
	public function add_action( string $hook, object $instance, string $method, int $priority = 10, int $args = 1 ): void {
		$this->actions[] = [
			'hook'     => $hook,
			'instance' => $instance,
			'method'   => $method,
			'priority' => $priority,
			'args'     => $args,
		];
	}

	/**
	 * Queue a filter hook.
	 */
	public function add_filter( string $hook, object $instance, string $method, int $priority = 10, int $args = 1 ): void {
		$this->filters[] = [
			'hook'     => $hook,
			'instance' => $instance,
			'method'   => $method,
			'priority' => $priority,
			'args'     => $args,
		];
	}

	/**
	 * Register all queued hooks with WordPress.
	 */
	public function run(): void {
		foreach ( $this->filters as $hook ) {
			add_filter( $hook['hook'], [ $hook['instance'], $hook['method'] ], $hook['priority'], $hook['args'] );
		}
		foreach ( $this->actions as $hook ) {
			add_action( $hook['hook'], [ $hook['instance'], $hook['method'] ], $hook['priority'], $hook['args'] );
		}
	}
}
