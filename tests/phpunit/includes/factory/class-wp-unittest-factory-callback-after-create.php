<?php

class WP_UnitTest_Factory_Callback_After_Create {
	var $callback;

	function __construct( $callback ) {
		$this->callback = $callback;
	}

<<<<<<< HEAD
	function call( $object ) {
=======
	/**
	 * Calls the set callback on a given object.
	 *
	 * @param mixed $object The object to apply the callback on.
	 *
	 * @return mixed The possibly altered object.
	 */
	public function call( $object ) {
>>>>>>> 0d81dcbfb1 (Docs: Add `@method` notation for `WP_UnitTest_Factory_For_Term::create_and_get()` for consistency with other factories.)
		return call_user_func( $this->callback, $object );
	}
}
