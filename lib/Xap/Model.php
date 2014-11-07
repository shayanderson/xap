<?php
/**
 * Xap - MySQL Rapid Development Engine for PHP 5.5+
 *
 * @package Xap
 * @version 0.0.6
 * @copyright 2014 Shay Anderson <http://www.shayanderson.com>
 * @license MIT License <http://www.opensource.org/licenses/mit-license.php>
 * @link <https://github.com/shayanderson/xap>
 */
namespace Xap;

/**
 * Xap Model class
 *
 * @author Shay Anderson 07.14 <http://www.shayanderson.com/contact>
 */
class Model
{
	/**
	 * Connection ID
	 *
	 * @var int
	 */
	private $__connection_id;

	/**
	 * Model record data
	 *
	 * @var array
	 */
	private $__data;

	/**
	 * Decorator pattern
	 *
	 * @var string
	 */
	private $__decorator;

	/**
	 * Decorator callable filters
	 *
	 * @var mixed (array|null)
	 */
	private $__decorator_filters;

	/**
	 * Model record loaded flag
	 *
	 * @var boolean
	 */
	private $__is_loaded = false;

	/**
	 * Model primary key column name
	 *
	 * @var string
	 */
	private $__key;

	/**
	 * Query params
	 *
	 * @var array
	 */
	private $__query_params;

	/**
	 * Query SQL
	 *
	 * @var string
	 */
	private $__query_sql;

	/**
	 * Table name
	 *
	 * @var string
	 */
	private $__table;

	/**
	 * Init
	 *
	 * @param array $columns
	 * @param string $table
	 * @param string $key
	 * @param int $connection_id
	 * @param array $query_params
	 * @param string $query_sql
	 */
	public function __construct(array $columns, $table, $key, $connection_id, $query_params, $query_sql,
		&$decorator = null, &$decorator_filters = null)
	{
		$this->__data = array_fill_keys($columns, null);
		$this->__data[$key] = null; // init primary key column
		$this->__table = $table;
		$this->__key = $key;
		$this->__connection_id = $connection_id;
		$this->__query_params = $query_params;
		$this->__query_sql = rtrim(rtrim($query_sql), ';') . ' LIMIT 1';
		$this->__decorator = &$decorator;
		$this->__decorator_filters = &$decorator_filters;
	}

	/**
	 * Model record column data getter
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		if($this->isColumn($name))
		{
			return $this->__data[$name];
		}

		return $this->{$name};
	}

	/**
	 * Model record column data setter
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return boolean (true on column data value set)
	 */
	public function __set($name, $value)
	{
		if($this->isColumn($name))
		{
			$this->__data[$name] = $value;

			if($name === $this->__key) // set key value in query params
			{
				$this->__query_params = [$this->__key => $value] + $this->__query_params;
			}

			return true; // flag data value set
		}

		return false;
	}

	/**
	 * Model printer
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->__decorator !== null && $this->__is_loaded
			? Decorate::data($this->__data, $this->__decorator, $this->__decorator_filters) : implode(', ',
			array_filter($this->__data, function($v) { return $v !== null; }));
	}

	/**
	 * Connection string for command getter
	 *
	 * @return string
	 */
	private function __getConnectionStr()
	{
		return $this->__connection_id !== 1 ? '[' . $this->__connection_id . ']' : '';
	}

	/**
	 * Check if model primary key value is set
	 *
	 * @return void
	 * @throws \Exception (when model primary key value is not set)
	 */
	private function __validateKeyValue()
	{
		if(!isset($this->__data[$this->__key]))
		{
			throw new \Exception('Model primary key value is required');
		}
	}

	/**
	 * Add (insert) model record data
	 *
	 * @param boolean $ignore_errors (ignore insert errors)
	 * @return boolean (true on insert)
	 * @throws \Exception (when no model columns defined)
	 */
	public function add($ignore_errors = true)
	{
		if(count($this->getData(false)) < 1)
		{
			throw new \Exception('Failed to add model record, no columns defined');
		}

		$affected = Engine::exec([$this->__getConnectionStr() . $this->__table . ':add'
			. ( $ignore_errors ? '/ignore' : '' ) . $this->__query_sql, $this->getData(false),
			$this->__query_params]);

		if($affected > 0)
		{
			// set key value (insert ID)
			$this->__set($this->__key, Engine::exec([$this->__getConnectionStr() . ':id']));
			return true;
		}

		return false;
	}

	/**
	 * Delete model record
	 *
	 * @param type $ignore_errors (ignore delete errors)
	 * @return boolean (true on delete)
	 */
	public function delete($ignore_errors = true)
	{
		$this->__validateKeyValue();

		return Engine::exec([$this->__getConnectionStr() . $this->__table . ':del'
			. ( $ignore_errors ? '/ignore' : '' ) . $this->__query_sql, $this->__query_params]) > 0;
	}

	/**
	 * Check if record exists in table flag getter
	 *
	 * @return boolean
	 */
	public function exists()
	{
		$this->__validateKeyValue();

		$r = Engine::exec([$this->__getConnectionStr() . ':query SELECT EXISTS(SELECT 1 FROM ' . $this->__table
			. $this->__query_sql . ') AS is_record', $this->__query_params]);

		if(isset($r[0]))
		{
			$r = (array)$r[0];
			return (int)$r['is_record'] > 0;
		}

		return false;
	}

	/**
	 * Model column names getter
	 *
	 * @return array (ex: ['col1', 'col2', ...])
	 */
	public function getColumns()
	{
		return array_keys($this->__data);
	}

	/**
	 * Model data (columns and values) getter
	 *
	 * @param boolean $include_key (include model primary key column and value in return data)
	 * @return array (ex: ['col1' => x, 'col2' => y, ...])
	 */
	public function getData($include_key = true)
	{
		if($include_key)
		{
			return $this->__data;
		}

		$data = $this->__data;
		unset($data[$this->__key]); // rm primary key data

		return $data;
	}

	/**
	 * Model primary key column name getter
	 *
	 * @return string
	 */
	public function getKey()
	{
		return $this->__key;
	}

	/**
	 * Table name getter
	 *
	 * @return string
	 */
	public function getTable()
	{
		return $this->__table;
	}

	/**
	 * Column exists in model flag getter
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function isColumn($name)
	{
		return isset($this->__data[$name]) || array_key_exists($name, $this->__data);
	}

	/**
	 * Model record is loaded flag getter
	 *
	 * @return boolean
	 */
	public function isLoaded()
	{
		return $this->__is_loaded;
	}

	/**
	 * Load model record
	 *
	 * @param int $id (optional)
	 * @return boolean (true on record exists and record data set)
	 */
	public function load($id = 0)
	{
		$this->__is_loaded = false; // reset
		$id = (int)$id;

		if($id > 0)
		{
			$this->__set($this->__key, $id);
		}
		else
		{
			$this->__validateKeyValue();
		}

		$cols = count($this->getColumns()) > 1 ? implode(',', $this->getColumns()) : '*';

		$r = Engine::exec([$this->__getConnectionStr() . ':query SELECT ' . $cols . ' FROM ' . $this->__table
			. $this->__query_sql, $this->__query_params]);

		if(isset($r[0]))
		{
			$r = (array)$r[0];

			if($cols === '*') // auto define columns
			{
				$this->__data = array_fill_keys(array_keys($r), null);
			}

			if($this->setData($r))
			{
				unset($r);
				$this->__is_loaded = true;
				return true;
			}
		}

		return false;
	}

	/**
	 * Save (update) model record data
	 *
	 * @param boolean $ignore_errors (ignore update error)
	 * @return boolean (true on update)
	 */
	public function save($ignore_errors = true)
	{
		if(count($this->getData(false)) < 1)
		{
			throw new \Exception('Failed to save model record, no columns defined');
		}

		$this->__validateKeyValue();

		return Engine::exec([$this->__getConnectionStr() . $this->__table . ':mod'
			. ( $ignore_errors ? '/ignore' : '' ) . $this->__query_sql, $this->getData(false),
			$this->__query_params]) > 0;
	}

	/**
	 * Model record data setter
	 *
	 * @param array $columns_and_values (ex: ['col1' => x, 'col2' => y, ...])
	 * @return boolean (true on one or more columns and values set)
	 */
	public function setData(array $columns_and_values)
	{
		$is_set = false;

		foreach($columns_and_values as $k => $v)
		{
			if($this->__set($k, $v))
			{
				$is_set = true;
			}
		}

		return $is_set;
	}
}