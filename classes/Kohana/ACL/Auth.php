<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * This class is wrapper for Auth
 * and used as the auth driver of module ACL.
 */
class Kohana_ACL_Auth implements ACL_Auth_Interface {

	/**
	 * This class instance
	 *
	 * @var object
	 */
	protected static $_instance;

	/**
	 * Auth instance
	 *
	 * @var object
	 */
	protected $_auth;

	/**
	 * Current user or default role
	 *
	 * @var mixed
	 */
	protected $_user;

	/**
	 * Return an instance of this class.
	 *
	 * @return  object
	 */
	public static function instance()
	{
		if ( ! static::$_instance instanceof static)
		{
			static::$_instance = new static(TRUE);
		}

		return static::$_instance;
	}

	/**
	 * Returns current user
	 *
	 * @return boolean
	 */
	public function logged_in()
	{
		return $this->_auth->logged_in();
	}

	/**
	 * Returns current user
	 *
	 * @return object
	 */
	public function get_user($default = NULL)
	{
		$default = ($default == NULL) ? $this->_config['guest_role'] : $default;
		$this->_user = $this->_auth->get_user($default);
		return (is_object($this->_user)) ? $this : $default;
	}

	/**
	 * Returns the string identifier of the Role
	 *
	 * @return string
	 */
	public function get_role_id()
	{
		return $this->_role;
	}

	/**
	 * @return void
	 */
	public function __construct()
	{
		$this->_config 	= Kohana::$config->load('acl');
		$this->_auth 	= Auth::instance();
		$this->_user 	= $this->_auth->get_user($this->_config['guest_role']);
		$this->_role 	= $this->_config['guest_role'];

		if (isset($this->_user->role_id))
		{
			$id = $this->_user->role_id;

			$query = DB::select('id', 'name')
				->from($this->_config['db_tables']['roles'])
				->where('id', '=', $id)
				->limit(1)
				->execute()
				->as_array('id', 'name');

			$this->_role = (isset($query[$id]))
				? $query[$id]
				: $this->_config['guest_role'];
		}
	}

	/**
	 * Gets model_user properties
	 *
	 * @param string $prop
	 * @return mixed
	 */
	public function __get($prop)
	{
		return (isset($this->_user->{$prop}))
			? $this->_user->{$prop}
			: NULL;
	}

	/**
	 * Gets model_user functions
	 *
	 * @param string $prop
	 * @param array $arguments
	 * @return mixed
	 */
	public function __call($prop, $arguments)
	{
		return (isset($this->_user->{$prop}))
			? $this->_user->{$prop}($arguments)
			: NULL;
	}
}
