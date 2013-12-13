<?php defined('SYSPATH') OR die('No direct script access.');

interface Kohana_ACL_Assert_Interface {

	public function assert(ACL $acl, $role = null, $resource = null, $privilege = null);
}
