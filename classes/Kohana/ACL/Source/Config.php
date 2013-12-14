<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_ACL_Source_Config implements ACL_Source_Interface {

	/**
	 * @var array
	 */
	protected $_config;

	/**
	 * @return void
	 */
	public function __construct()
	{
		$this->_config = Kohana::$config->load('acl');
	}

	/**
	 * Gets roles
	 *
	 * @return array
	 */
	public function roles()
	{
		return $this->_config['roles'];
	}

	/**
	 * Gets resources
	 *
	 * @return array
	 */
	public function resources()
	{
		return $this->_config['resources'];
	}

	/**
	 * Gets rules
	 *
	 * @return array
	 */
	public function rules()
	{
		return $this->_config['rules'];
	}
}
