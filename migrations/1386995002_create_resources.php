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
class Create_Resources extends Migration
{
	public function up()
	{
		$table_name = Kohana::$config->load('acl')->get('db_tables.resources', 'resources');

		$this->create_table($table_name,
			array(
				'id' => 'primary_key',
				'parent_id' => array('type' => 'int(10)', 'null' => FALSE, 'unsigned' => TRUE, 'default' => 0),
				'name' => array('type' => 'varchar(32)', 'null' => FALSE),
				'description' => array('type' => 'varchar(255)', 'null' => FALSE),
			),
			array(
				'options' => 'ENGINE=innoDB DEFAULT CHARSET=utf8',
			)
		);

		$this->add_index($table_name, 'uniq_name', 'name', 'unique');

		$this->execute("
			INSERT INTO `".$table_name."` (`id`, `parent_id`, `name`, `description`) VALUES(1, 0, 'blog', 'Blog');
			INSERT INTO `".$table_name."` (`id`, `parent_id`, `name`, `description`) VALUES(2, 1, 'comments', 'Posts comments');
		");
	}

	public function down()
	{
		$table_name = Kohana::$config->load('acl')->get('db_tables.resources', 'resources');
		$this->drop_table($table_name);
	}
}
