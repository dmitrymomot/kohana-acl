<?php defined('SYSPATH') OR die('No direct script access.');

interface Kohana_ACL_Auth_Interface extends ACL_Role_Interface {

	/**
	 * Singleton pattern
	 *
	 * @return object of class
	 */
	public static function instance();

	/**
	 * Gets the currently logged in user from the session.
	 * Returns NULL if no user is currently logged in.
	 *
	 * @param   mixed  $default  Default value to return if the user is currently not logged in.
	 * @return  mixed
	 */
	public function get_user($default = NULL);
}
