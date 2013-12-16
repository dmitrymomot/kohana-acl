<?php defined('SYSPATH') OR die('No direct script access.');
/**
* create_table($table_name, $fields, array('id' => TRUE, 'options' => ''))
* drop_table($table_name)
* rename_table($old_name, $new_name)
* add_column($table_name, $column_name, $params)
* rename_column($table_name, $column_name, $new_column_name)
* change_column($table_name, $column_name, $params)
* remove_column($table_name, $column_name)
* add_index($table_name, $index_name, $columns, $index_type = 'normal')
* remove_index($table_name, $index_name)
*/
class Create_Rules extends Migration
{
	public function up()
	{
		$table_name = Kohana::$config->load('acl')->get('db_tables.rules', 'rules');

		$this->create_table($table_name,
			array(
				'id' => 'primary_key',
				'type' => array('type' => 'enum', 'values' => array(ACL::ALLOW, ACL::DENY), 'null' => FALSE, 'default' => ACL::DENY),
				'role_id' => array('type' => 'int(10)', 'null' => FALSE, 'unsigned' => TRUE),
				'resource_id' => array('type' => 'int(10)', 'null' => FALSE, 'unsigned' => TRUE),
				'privilege' => array('type' => 'enum', 'values' => array('create', 'read', 'update', 'delete'), 'null' => TRUE, 'default' => NULL),
			),
			array(
				'options' => 'ENGINE=innoDB DEFAULT CHARSET=utf8',
			)
		);

		$this->add_index($table_name, 'uniq_record', array('type', 'role_id', 'resource_id', 'privilege'), 'unique');

		$this->execute("
			INSERT INTO `".$table_name."` (`type`, `role_id`, `resource_id`, `privilege`) VALUES('allow', 0, 0, 'read');
			INSERT INTO `".$table_name."` (`type`, `role_id`, `resource_id`, `privilege`) VALUES('allow', 1, 1, 'read');
			INSERT INTO `".$table_name."` (`type`, `role_id`, `resource_id`, `privilege`) VALUES('allow', 1, 2, 'read');
			INSERT INTO `".$table_name."` (`type`, `role_id`, `resource_id`, `privilege`) VALUES('allow', 1, 2, 'create');
			INSERT INTO `".$table_name."` (`type`, `role_id`, `resource_id`, `privilege`) VALUES('allow', 2, 0, NULL);
		");
	}

	public function down()
	{
		$table_name = Kohana::$config->load('acl')->get('db_tables.rules', 'rules');
		$this->drop_table($table_name);
	}
}
