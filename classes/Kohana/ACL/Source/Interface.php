<?php defined('SYSPATH') OR die('No direct script access.');

interface Kohana_ACL_Source_Interface {

	/**
	 * Gets roles
	 *
	 * @return array
	 */
	public function roles();

	/**
	 * Gets resources
	 *
	 * @return array
	 */
	public function resources();

	/**
	 * Gets rules
	 *
	 * @return array
	 */
	public function rules();
}
