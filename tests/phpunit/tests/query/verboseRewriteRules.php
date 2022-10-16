<?php

require_once dirname( __FILE__ ) . '/conditionals.php';

/**
 * @group query
 * @group rewrite
 */
class Tests_Query_VerbosePageRules extends Tests_Query_Conditionals {
	function set_up() {
		parent::set_up();

		$this->set_permalink_structure( '/%category%/%year%/%postname%/' );
		create_initial_taxonomies();
	}
}
