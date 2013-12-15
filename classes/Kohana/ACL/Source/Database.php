<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_ACL_Source_Database implements ACL_Source_Interface {

	/**
	 * @var array
	 */
	protected $_config;

	/**
	 * Table name for "roles"
	 *
	 * @var string
	 */
	protected $_tbl_roles = 'roles';

	/**
	 * Table name for "resources"
	 *
	 * @var string
	 */
	protected $_tbl_resources = 'resources';

	/**
	 * Table name for "rules"
	 *
	 * @var string
	 */
	protected $_tbl_rules = 'rules';

	/**
	 * @return void
	 */
	public function __construct()
	{
		$this->_config = Kohana::$config->load('acl');

		if (isset($this->_config['db_tables'])
			AND ! empty($this->_config['db_tables'])
			AND is_array($this->_config['db_tables']))
		{
			$this->_tbl_roles 		= $this->_config['db_tables']['roles'];
			$this->_tbl_resources 	= $this->_config['db_tables']['resources'];
			$this->_tbl_rules 		= $this->_config['db_tables']['rules'];
		}
	}

	/**
	 * Gets roles
	 *
	 * @return array
	 */
	public function roles()
	{
		try
		{
			$query = DB::select(array('role.name', 'role_name'), array('parent.name', 'parent_name'))
				->from(DB::expr('`'.$this->_tbl_roles.'` as role'))
				->join(DB::expr('`'.$this->_tbl_roles.'` as parent'), 'LEFT OUTER')
				->on('role.parent_id', '=', 'parent.id')
				->execute()
				->as_array('role_name', 'parent_name');
		}
		catch (Database_Exception $e)
		{
			throw new Database_Exception($e);
		}

		return $query;
	}

	/**
	 * Gets resources
	 *
	 * @return array
	 */
	public function resources()
	{
		try
		{
			$query = DB::select(array('resource.name', 'resource_name'), array('parent.name', 'parent_name'))
				->from(DB::expr('`'.$this->_tbl_resources.'` as resource'))
				->join(DB::expr('`'.$this->_tbl_resources.'` as parent'), 'LEFT OUTER')
				->on('resource.parent_id', '=', 'parent.id')
				->execute()
				->as_array('resource_name', 'parent_name');
		}
		catch (Database_Exception $e)
		{
			throw new Database_Exception($e);
		}

		return $query;
	}

	/**
	 * Gets rules
	 *
	 * @return array
	 */
	public function rules()
	{
		try
		{
			$query = DB::select(
					array($this->_tbl_rules.'.type', 'type'),
					array($this->_tbl_roles.'.name', 'role'),
					array($this->_tbl_resources.'.name', 'resource'),
					array($this->_tbl_rules.'.privilege', 'privilege')
				)
				->from($this->_tbl_rules)
				->join($this->_tbl_resources, 'LEFT OUTER')
				->on($this->_tbl_resources.'.id', '=', $this->_tbl_rules.'.resource_id')
				->join($this->_tbl_roles, 'LEFT OUTER')
				->on($this->_tbl_roles.'.id', '=', $this->_tbl_rules.'.role_id')
				->execute()
				->as_array();
		}
		catch (Database_Exception $e)
		{
			throw new Database_Exception($e);
		}

		$rules = array(ACL::ALLOW => array(), ACL::DENY => array());

		foreach ($query as $rule)
		{
			if (in_array($rule['type'], array(ACL::ALLOW, ACL::DENY)))
			{
				$rules[$rule['type']][] = array(
					'role' 		=> $rule['role'],
					'resource' 	=> $rule['resource'],
					'privilege' => $rule['privilege'],
				);
			}
		}

		return $rules;
	}
}
