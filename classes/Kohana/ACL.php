<?php defined('SYSPATH') OR die('No direct script access.');

/**------------------------------------------------------
 * New to ACL? Read the Zend documentation:
 *   http://framework.zend.com/manual/en/zend.acl.html
 * All their examples work with this lib
 * ------------------------------------------------------
 *
 * This is a Kohana port of the Zend_ACL library, with a few changes.
 *
 * Things that are different from Zend_ACL:
 * 1) Your ACL definition is saved using the string identifiers of the roles/resources,
 *    NOT the objects. This way, if you serialize the ACL, you won't end up with a
 *    unneccesary large serialization string. You don't have to supply objects when
 *    adding roles/resources. EG a $acl->add_role('user') is fine.
 * 2) If you have defined assertions in your rules, the assert methods will have access
 *    to the arguments you provided in the ->allow($role,$resource,$privilege) call.
 *    So, if you provide a User_Model as $role, the assert method will receive this object,
 *    and not the role_id of this object. This way, assertions become way more powerful.
 * 3) Not all methods are implemented, because they weren't needed by me at the time.
 *    However, the essential methods (the core of ACL) are implemented, so the missing methods
 *    can be implemented easily when needed.
 * 4) The methods are underscored instead of camelCased, so add_role, add_resource and is_allowed.
 *
 * Ported to Kohana & modified by Wouter - see Kohana Forum.
 *
 * Based on Zend_ACL:
 *
 * @category   Zend
 * @package    Zend_ACL
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: ACL.php 9417 2008-05-08 16:28:31Z darby $
 *
 *
 * Took some ideas, but mostly the comments from Bonafide ACL by Woody Gilk.
 *
 * @package    Bonafide
 * @category   Base
 * @author     Woody Gilk <woody.gilk@kohanaframework.org>
 * @copyright  (c) 2011 Woody Gilk
 * @license    MIT
 */

abstract class Kohana_ACL {

	// Privileges
	const CREATE 	= 'create';
	const READ 		= 'read';
	const UPDATE 	= 'update';
	const DELETE 	= 'delete';

	// permission types
	const ALLOW 	= 'allow';
	const DENY 		= 'deny';

	/**
	 * @var object
	 */
	protected static $_instance;

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
	 * Alias of the allowed method
	 *
	 * @return boolean
	 */
	public static function check($resource = NULL, $privilege = NULL, $exception = NULL)
	{
		return static::instance()->allowed($resource, $privilege, $exception);
	}

	/**
	 * Alias of the logged_in method
	 */
	public static function logged_in()
	{
		return static::instance()->_auth->logged_in();
	}

	/**
	 * Alias of the get_user method
	 */
	public static function get_user()
	{
		return static::instance()->_auth->get_user();
	}

	/**
	 * Returns authentication instance
	 */
	public static function auth()
	{
		return static::instance()->_auth;
	}

	/**
	 * Wildcard for all types
	 */
	const WILDCARD = '*';

	/**
	 * Authentication instance
	 */
	protected $_auth;

	/**
	 * Config object
	 */
	protected $_config;

	/**
	 * @var  array  Current role/resource/privilege being matched
	 */
	protected $_command      = array();

	/**
	 * @var  array  ACL roles
	 */
	protected $_roles       = array();

	/**
	 * @var  array  ACL resources
	 */
	protected $_resources   = array();

	/**
	 * @var  array  ACL permissions
	 */
	protected $_permissions = array();

	/**
	 * @return void
	 */
	public function __construct($init = FALSE)
	{
		if ($init)
		{
			$this->_config 		= Kohana::$config->load('acl');
			$this->_guest_role 	= $this->_config['guest_role'];

			$instance = new ReflectionMethod($this->_config->lib['class'], 'instance');
			$params   = Arr::get($this->_config->lib, 'params', array());

			$this->_auth = $instance->invokeArgs(NULL, $params);

			// Add Guest Role as role
			if ( ! array_key_exists($this->_config['guest_role'], $this->_config['roles']))
			{
				$this->add_role($this->_config['guest_role']);
			}

			// Load ACL data
			$this->load();
		}
	}

	/**
	 * Load ACL data (roles/resources/rules)
	 *
	 * This allows you to add context specific rules
	 * roles and resources.
	 *
	 * @param  array|Kohana_Config  configiration data
	 */
	public function load()
	{
		$driver = (isset($this->_config['data_source_driver']))
			? $this->_config['data_source_driver']
			: 'config';

		$driver = 'ACL_Source_'.UTF8::ucfirst($driver);

		if ( ! class_exists($driver))
		{
			throw new ACL_Exception(__(
				'Driver :driver not found.',
				array(':driver' => $driver)
			));
		}

		$data_source = new $driver;

		if ( ! $data_source instanceof ACL_Source_Interface )
		{
			throw new ACL_Exception(__(
				'Driver :driver not implemented ACL_Source_Interface.',
				array(':driver' => $driver)
			));
		}

		// Get data
		$roles 		= $data_source->roles();
		$resources 	= $data_source->resources();
		$rules 		= $data_source->rules();

		// Roles
		foreach ($roles as $role => $parent)
		{
			$this->add_role($role, $parent);
		}

		// Resources
		foreach ($resources as $resource => $parent)
		{
			$this->add_resource($resource, $parent);
		}

		// Rules
		foreach (array('allow','deny') as $method)
		{
			if ( isset($rules[$method]) )
			{
				foreach ($rules[$method] as $rule)
				{
					// create variables
					$role = $resource = $privilege = $assertion = NULL;

					// extract variables from rule
					extract($rule);

					// create assert object
					if ( $assertion )
					{
						if ( is_array($assertion))
						{
							$assertion = count($assertion) === 2
								? new $assertion[0]($assertion[1])
								: new $assertion[0];
						}
						else
						{
							$assertion = new $assertion;
						}
					}

					// this is faster than calling $this->$method
					if ( $method === 'allow')
					{
						$this->allow($role, $resource, $privilege, $assertion);
					}
					else
					{
						$this->deny($role, $resource, $privilege, $assertion);
					}
				}
			}
		}
	}

	/**
	 * Check if logged in user (or guest) has access to resource/privilege.
	 *
	 * @param   mixed     Resource
	 * @param   string    Privilege
	 * @param   boolean   Override exception handling set by config
	 * @return  boolean   Is user allowed
	 * @throws  A2_Exception   In exception modus, when user is not allowed
	 */
	public function allowed($resource = NULL, $privilege = NULL, $exception = NULL)
	{
		if ( ! is_bool($exception))
		{
			// take config value
			$exception = $this->_config['exception'];
		}

		// retrieve user
		$role = ($user = $this->_auth->get_user()) ? $user : $this->_config['guest_role'];

		$result = $this->is_allowed($role, $resource, $privilege);

		if ( ! $exception OR $result === TRUE )
		{
			return $result;
		}
		else
		{
			$resources = $privileges = $errors = array();

			if ( $resource !== NULL)
			{
				$resources[] = $resource instanceof ACL_Resource_Interface
					? $resource->get_resource_id()
					: (string) $resource;
			}

			if ( $privilege !== NULL)
			{
				$privileges[] = $privilege;
			}

			$resources[]  = 'default';
			$privileges[] = 'default';

			foreach ( $resources as $r)
			{
				foreach ( $privileges as $p)
				{
					if ( $message = Kohana::message('acl', $r . '.' . $p))
					{
						throw new $this->_config['exception_type']($message);
					}
				}
			}

			// this only happens when someone has removed the 'default.default' error message from messages/a2.php
			throw new ACL_Exception('No error messages defined');
		}
	}

	/**
	 * Add a new role.
	 *
	 *     // Add a "guest" role
	 *     $acl->role('guest');
	 *
	 *     // Add a "member" role that inherits from "guest"
	 *     $acl->role('member', 'guest');
	 *
	 *     // Add a "owner" role that inherits from "guest" and "member"
	 *     $acl->role('owner', array('guest','member'));
	 *
	 * @param   string  role name
	 * @param   mixed   role name or array of roles role inherits from
	 * @return  ACL_Core
	 */
	public function add_role($role, $parents = NULL)
	{
		$role = $role instanceof ACL_Role_Interface
			? $role->get_role_id()
			: (string) $role;

		if ( ! is_array($parents))
		{
			if ($parents === NULL)
			{
				$parents = array();
			}
			else
			{
				$parents = array($parents);
			}
		}

		$this->_roles[$role] = $parents;

		return $this;
	}

	/**
	 * Add a new resource.
	 *
	 *     // Add a "users" resource
	 *     $acl->resource('users');
	 *
	 *     // Add a "news" resource
	 *     $acl->resource('news');
	 *
	 *     // Add a "latest" resource with inherits from "news"
	 *     $acl->resource('latest', 'news');
	 *
	 * @param   string  resource name
	 * @param   mixed   single resource or array of resources resource inherits from
	 * @return  ACL_Core
	 */
	public function add_resource($resource, $parents = NULL)
	{
		$resource = $resource instanceof ACL_Resource_Interface
			? $resource->get_resource_id()
			: (string) $resource;

		if ( ! is_array($parents))
		{
			if ( ! $parents)
			{
				$parents = array();
			}
			else
			{
				$parents = array($parents);
			}
		}

		$this->_resources[$resource] = $parents;

		return $this;
	}

	/**
	 * Get an array of role and all its parents.
	 *
	 *     // Get all roles for the 'member' role
	 *     $roles = $acl->roles('member');
	 *
	 * @return  array
	 */
	public function roles($name)
	{
		// Add this role to the set
		$roles = array($name);

		if ( isset($this->_roles[$name]))
		{
			foreach ( $this->_roles[$name] as $role)
			{
				// Inherit parents
				$roles = array_merge($this->roles($role), $roles);
			}
		}

		return $roles;
	}

	/**
	 * Get an array of resource and all its parents.
	 *
	 *     // Get all resources for the 'news' resource
	 *     $roles = $acl->resources('news');
	 *
	 * @return  array
	 */
	protected function resources($name)
	{
		// Add this resource to the set
		$resources = array($name);

		if ( isset($this->_resources[$name]))
		{
			foreach ( $this->_resources[$name] as $resource)
			{
				// Inherit parents
				$resources = array_merge($this->resources($resource), $resources);
			}
		}

		return $resources;
	}

	/**
	 * Add "allow" access to a role.
	 *
	 *     // Allow "guest" to "view" the news
	 *     $acl->allow('guest', 'news', 'view');
	 *
	 *     // Allow "member" to "comment" on "news"
	 *     $acl->allow('member', 'news', 'comment');
	 *
	 *     // Allow "admin" to do anything
	 *     $acl->allow('admin');
	 *
	 * @param   string   single role or array of roles
	 * @param   mixed    single resource or array of resources
	 * @param   mixed    single privilege or array of privileges
	 * @param   object   assertion object
	 * @return  ACL_Core
	 */
	public function allow($roles = NULL, $resources = NULL, $privileges = NULL, ACL_Assert_Interface $assertion = NULL)
	{
		return $this->add_rule(TRUE, $roles, $resources, $privileges, $assertion);
	}

	/**
	 * Add "deny" access to a role.
	 *
	 *     // Deny "member" to "edit" on "news"
	 *     $acl->deny('member', 'news', 'edit');
	 *
	 * [!!] By default, everything in an access control list is denied. It is
	 * not necessary to explicitly deny privileges except when an inherited role
	 * is allowed access.
	 *
	 * @param   string   single role or array of roles
	 * @param   mixed    single resource or array of resources
	 * @param   mixed    single privilege or array of privileges
	 * @param   object   assertion object
	 * @return  ACL_Core
	 */
	public function deny($roles = NULL, $resources = NULL, $privileges = NULL, ACL_Assert_Interface $assertion = NULL)
	{
		return $this->add_rule(FALSE, $roles, $resources, $privileges, $assertion);
	}

	/**
	 * Add a permission for a role, setting the resources, privileges, and
	 * access type (allow, deny).
	 *
	 *     // Allow "admin" to access everything
	 *     $acl->add_rule(TRUE, 'admin', NULL, NULL);
	 *
	 * [!!] It is not recommended to use this method directly. Instead, use
	 * the [ACL_Core::allow] and [ACL_Core::deny] methods.
	 *
	 * @param   boolean  allow/deny
	 * @param   mixed    single role or array of roles
	 * @param   mixed    single resource or array of resources
	 * @param   mixed    single privilege or array of privileges
	 * @param   object   assertion object
	 * @return  ACL_Core
	 */
	private function add_rule($allow, $roles, $resources, $privileges, $assertion)
	{
		$entities = array('roles', 'resources', 'privileges');

		foreach ($entities as $entity)
		{
			if ($$entity)
			{
				if ( ! is_array($$entity))
				{
					// Make the entity into an array
					$$entity = array($$entity);
				}
			}
			else
			{
				// Modify "any" entity.
				$$entity = array(ACL::WILDCARD);
			}
		}

		$allow = array(
			'allow' => (bool) $allow
		);

		if ( $assertion)
		{
			$allow['assert'] = $assertion;
		}

		foreach ( $privileges as $privilege)
		{
			foreach ( $resources as $resource)
			{
				$resource = $resource instanceof ACL_Resource_Interface
					? $resource->get_resource_id()
					: (string) $resource;

				foreach ( $roles as $role)
				{
					$role = $role instanceof ACL_Role_Interface
						? $role->get_role_id()
						: (string) $role;

					$this->_permissions[$role][$resource][$privilege] = $allow;
				}
			}
		}

		return $this;
	}

	/**
	 * Check if a role is is allowed to a privilege on a resource.
	 * Recursively checks all inherited roles and resources.
	 *
	 *     // Is "guest" allowed to "commment" the "news"?
	 *     $acl->is_allowed('guest', 'news', 'comment');
	 *
	 *     // Is "member" allowed to "commment" the "news"?
	 *     $acl->is_allowed('member', 'news', 'commment');
	 *
	 * @param   mixed    single role or array of roles
	 * @param   string   resource name
	 * @param   string   privilege name
	 * @return  boolean  is allowed
	 */
	public function is_allowed($role = NULL, $resource = NULL, $privilege = NULL)
	{
		$roles = is_array($role)
			? $role
			: array($role);

		foreach ( $roles as $role)
		{
			$this->_command = array(
				'role'      => $role,
				'resource'  => $resource,
				'privilege' => $privilege
			);

			if ( $role)
			{
				$role = $role instanceof ACL_Role_Interface
					? $role->get_role_id()
					: (string) $role;
			}

			// create another role array because get_role_id() sometimes returns an array
			$role_array = is_array($role)
				? $role
				: array($role);

			if ( $resource)
			{
				$resource = $resource instanceof ACL_Resource_Interface
					? $resource->get_resource_id()
					: (string) $resource;
			}

			foreach ( $role_array as $role)
			{
				$allowed = $this->match($role, $resource, $privilege);

				if ( $allowed === TRUE AND in_array(NULL, $this->_command))
				{
					// wildcard active - for each wildcard in the query, take every possible value

					// if role is wildcard, check all possible roles
					$_roles = isset($role)
						? array($role)
						: array_keys($this->_roles);

					// if resource is wildcard, check all possible resources
					$_resources = isset($resource)
						? array($resource)
						: array_keys($this->_resources);

					// if privilege is wildcard, check all possible privileges
					if ( ! isset($privilege))
					{
						$_privileges = array();

						foreach ( $this->_permissions as $res)
						{
							foreach ( $res as $privs)
							{
								$_privileges = array_merge($_privileges, array_keys($privs));
							}
						}

						// removes wildcard and duplicate values
						$_privileges = array_diff($_privileges, array(ACL::WILDCARD));
					}
					else
					{
						$_privileges = array($privilege);
					}

					// if there are zero possible values for a wildcard, fallback to the wildcard itself
					if ( count($_roles) === 0)      $_roles      = array(ACL::WILDCARD);
					if ( count($_resources) === 0)  $_resources  = array(ACL::WILDCARD);
					if ( count($_privileges) === 0) $_privileges = array(ACL::WILDCARD);

					// look for a disallowed match
					foreach ( $_roles as $_ro)
					{
						foreach ( $_resources as $_re)
						{
							foreach ( $_privileges as $_pr)
							{
								if ( ! $this->match($_ro, $_re, $_pr))
								{
									return FALSE;
								}
							}
						}
					}
				}

				if ( $allowed === TRUE)
				{
					return $allowed;
				}
			}
		}

		return FALSE;
	}

	/**
	 * Check if a role is is allowed to a privilege on a resource.
	 * Recursively checks all inherited roles and resources.
	 *
	 * @param   mixed    role name
	 * @param   string   resource name
	 * @param   string   privilege name
	 * @return  boolean  is allowed
	 */
	protected function match($role, $resource, $privilege)
	{
		// default
		$roles = $resources = $privileges = array(ACL::WILDCARD);

		if ( $role)
		{
			$roles = array_merge($roles, $this->roles($role));
		}

		if ( $resource)
		{
			$resources = array_merge($resources, $this->resources($resource));
		}

		if ( $privilege)
		{
			$privileges[] = $privilege;
		}

		$allowed    = NULL;
		$roles      = array_reverse($roles);
		$resources  = array_reverse($resources);
		$privileges = array_reverse($privileges);

		// find highest matching permission - walk through from most specific to most generic (wildcards)
		foreach ( $roles as $roid => $_role)
		{
			foreach ( $resources as $reid => $_resource)
			{
				foreach ( $privileges as $prid => $_privilege)
				{
					if ( isset($this->_permissions[$_role][$_resource][$_privilege]))
					{
						$match = $this->_permissions[$_role][$_resource][$_privilege];

						if ( ! isset($match['assert']) OR $match['assert']->assert($this, $this->_command['role'], $this->_command['resource'], $this->_command['privilege']))
						{
							$allowed = $match['allow'];
							break 3;
						}
					}
				}
			}
		}

		return $allowed === TRUE;
	}

	public function __sleep()
	{
		return array('_roles','_resources','_permissions'); // no need to save the current _command ($this->_command)
	}
}
