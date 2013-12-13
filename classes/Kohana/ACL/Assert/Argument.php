<?php defined('SYSPATH') OR die('No direct script access.');

/*
 * Argument Assertion - check if certain keys of role and resource are the same
 *
 * Possible use when you want to check if the resource object has a user_id attribute
 * with the same value of the role object (a user object).
 *
 * The assertion object requires an array of KEY => VALUE pairs, where the KEYs refer
 * to role attributes, and VALUEs to resource attributes.
 *
 * For example new ACL_Assert_Argument(array('primary_key_value'=>'user_id'));
 */

class Kohana_ACL_Assert_Argument implements ACL_Assert_Interface {

	protected $_arguments;

	public function __construct($arguments)
	{
		$this->_arguments = $arguments;
	}

	public function assert(ACL $acl, $role = null, $resource = null, $privilege = null)
	{
		foreach($this->_arguments as $role_key => $resource_key)
		{
			if($role->$role_key !== $resource->$resource_key)
			{
				return FALSE;
			}
		}

		return TRUE;
	}
}
