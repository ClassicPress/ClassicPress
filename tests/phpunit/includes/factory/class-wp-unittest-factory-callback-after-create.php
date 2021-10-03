<?php

class WP_UnitTest_Factory_Callback_After_Create {
	var $callback;

	function __construct( $callback ) {
		$this->callback = $callback;
	}

	/**
	 * Calls the set callback on a given object.
	 *
	 * @param mixed $object The object to apply the callback on.
	 *
	 * @return mixed The possibly altered object.
	 */
	public function call( $object ) {
		return call_user_func( $this->callback, $object );
	}
}
