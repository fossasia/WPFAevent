<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
/**
 * Simple loader to collect actions and filters and register them on run().
 */
class Wpfaevent_Loader {
    protected $actions = [];
    protected $filters = [];

    public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
        $this->actions[] = compact('hook','component','callback','priority','accepted_args');
    }

    public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
        $this->filters[] = compact('hook','component','callback','priority','accepted_args');
    }

    public function run() {
        foreach ( $this->filters as $f ) {
            add_filter( $f['hook'], [ $f['component'], $f['callback'] ], $f['priority'], $f['accepted_args'] );
        }
        foreach ( $this->actions as $a ) {
            add_action( $a['hook'], [ $a['component'], $a['callback'] ], $a['priority'], $a['accepted_args'] );
        }
    }
}
