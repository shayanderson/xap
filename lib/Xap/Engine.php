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
 * Xap Engine class
 *
 * @author Shay Anderson 07.14 <http://www.shayanderson.com/contact>
 */
class Engine
{
	/**
	 * Decorate types
	 */
	const
		DECORATE_TYPE_ARRAY = 1,
		DECORATE_TYPE_DETECT = 2,
		DECORATE_TYPE_STRING = 3,
		DECORATE_TYPE_TEST = 4;

	/**
	 * Default primary key column name
	 */
	const DEFAULT_PRIMARY_KEY = 'id';

	/**
	 * Command part keys
	 */
	const
		KEY_CMD = 1,
		KEY_CMD_COLUMNS = 2,
		KEY_CMD_CONN_ID = 3,
		KEY_CMD_ID = 4,
		KEY_CMD_OPTIONS = 5,
		KEY_CMD_SQL = 6,
		KEY_CMD_TABLE = 7;

	/**
	 * Configuration keys
	 */
	const
		KEY_CONF_DEBUG = 'debug',
		KEY_CONF_ERROR_HANDLER = 'error_handler',
		KEY_CONF_ERRORS = 'errors',
		KEY_CONF_LOG_HANDLER = 'log_handler',
		KEY_CONF_OBJECTS = 'objects';

	/**
	 * Connection keys
	 */
	const
		KEY_CONN_DATABASE = 'database',
		KEY_CONN_HOST = 'host',
		KEY_CONN_ID = 'id',
		KEY_CONN_PASSWORD = 'password',
		KEY_CONN_USER = 'user';

	/**
	 * Pagination keys
	 */
	const
		KEY_PAGE_NEXT = 'next',
		KEY_PAGE_NEXT_STR = 'next_string',
		KEY_PAGE_OFFSET = 'offset',
		KEY_PAGE_PAGE = 'page',
		KEY_PAGE_PREV = 'prev',
		KEY_PAGE_PREV_STR = 'prev_string',
		KEY_PAGE_RPP = 'rpp';

	/**
	 * Query options
	 */
	const
		OPT_ARRAY = 0x1,
		OPT_CACHE = 0x2,
		OPT_FIRST = 0x4,
		OPT_MODEL = 0x8,
		OPT_PAGINATION = 0x10,
		OPT_QUERY = 0x20;

	/**
	 * Forced query types
	 */
	const
		QUERY_TYPE_AFFECTED = 1,
		QUERY_TYPE_ROWS = 2;

	/**
	 * Connections
	 *
	 * @var array
	 */
	private static $__connections = [];

	/**
	 * Configuration settings
	 *
	 * @var array
	 */
	private $__conf = [];

	/**
	 * Last error message (when error occurs)
	 *
	 * @var string (or null when no error)
	 */
	private $__error;

	/**
	 * Connection ID
	 *
	 * @var int
	 */
	private $__id;

	/**
	 * Logging enabled flag
	 *
	 * @var boolean
	 */
	private static $__is_logging = true;

	/**
	 * Table name to primary key column name map
	 *
	 * @var array
	 */
	private $__key_map;

	/**
	 * Debug log
	 *
	 * @var array
	 */
	private $__log = [];

	/**
	 * PDO object
	 *
	 * @var \PDO
	 */
	private $__pdo;

	/**
	 * Init (internal only)
	 *
	 * @param array $connection
	 */
	private function __construct(array $connection)
	{
		// init config
		$this->__conf = [
			self::KEY_CONF_DEBUG => true,
			self::KEY_CONF_ERROR_HANDLER => null,
			self::KEY_CONF_ERRORS => true,
			self::KEY_CONF_LOG_HANDLER => null,
			self::KEY_CONF_OBJECTS => true
		];

		$this->__id = $connection[self::KEY_CONN_ID];

		foreach($connection as $k => $v) // config setter
		{
			if(isset($this->__conf[$k]) || array_key_exists($k, $this->__conf))
			{
				if(($k === self::KEY_CONF_ERROR_HANDLER || $k === self::KEY_CONF_LOG_HANDLER) && !is_callable($v))
				{
					continue; // handlers must be callable
				}

				$this->__conf[$k] = $v;
			}
		}

		$this->__getPdo($connection); // set PDO object
	}

	/**
	 * Decorate command data
	 *
	 * @param mixed $data
	 * @param mixed $filters
	 * @param string $decorator
	 * @return mixed (string or data)
	 */
	private static function __decorate($data, &$decorator, &$filters, $type)
	{
		if($decorator !== null)
		{
			switch($type)
			{
				case self::DECORATE_TYPE_ARRAY:
					return Decorate::data($data, $decorator, $filters);
					break;

				case self::DECORATE_TYPE_DETECT: // detect decorate type
					if(is_array($data))
					{
						return Decorate::data($data, $decorator, $filters);
					}
					else if(strpos($decorator, Decorate::PLACEHOLDER_TEST_VALUE_SEP) !== false)
					{
						return Decorate::test($data, $decorator, $filters);
					}
					else
					{
						return Decorate::string($data, $decorator, $filters);
					}
					break;

				case self::DECORATE_TYPE_STRING:
					return Decorate::string($data, $decorator, $filters);
					break;

				case self::DECORATE_TYPE_TEST:
					return Decorate::test($data, $decorator, $filters);
					break;
			}
		}

		return $data;
	}

	/**
	 * Trigger error
	 *
	 * @param string $message
	 * @return void
	 * @throws \Exception
	 */
	private function __error($message)
	{
		$this->__error = $message;

		$this->__log('Error: ' . $message);

		if($this->__conf[self::KEY_CONF_ERRORS])
		{
			if($this->__conf[self::KEY_CONF_ERROR_HANDLER] !== null && self::$__is_logging)
			{
				$this->__conf[self::KEY_CONF_ERROR_HANDLER]($message); // custom error handler
			}
			else
			{
				if($this->__conf[self::KEY_CONF_DEBUG] && $this->__conf[self::KEY_CONF_LOG_HANDLER] === null)
				{
					print_r($this->getLog()); // print debug log
				}

				throw new \Exception(__NAMESPACE__ . ': ' . $message);
			}
		}
	}

	/**
	 * PDO connection getter
	 *
	 * @param int $id
	 * @return \self
	 * @throws \Exception (when connection does not exist)
	 */
	private static function &__getConnection($id)
	{
		if(self::__isConnection($id))
		{
			return self::$__connections[$id];
		}

		throw new \Exception('Connection ID \'' . $id . '\' does not exist');
	}

	/**
	 * PDO object getter (lazy loader) and connection data setter
	 *
	 * @staticvar array $hosts
	 * @param mixed $connection (array when connection setter, null for getter)
	 * @return \PDO (or null on connection setter)
	 */
	public function &__getPdo($connection = null)
	{
		static $hosts = [];

		if($connection !== null) // register
		{
			$hosts[$this->__id] = $connection;

			$this->__log('Connection \'' . $this->__id . '\' registered (host: \'' . $connection[self::KEY_CONN_HOST]
				. '\', database: \'' . $connection[self::KEY_CONN_DATABASE] . '\')');
		}
		else if($this->__pdo === null) // init
		{
			try
			{
				$this->__pdo = new \PDO('mysql:host=' . $hosts[$this->__id][self::KEY_CONN_HOST] . ';dbname='
					. $hosts[$this->__id][self::KEY_CONN_DATABASE], $hosts[$this->__id][self::KEY_CONN_USER],
					$hosts[$this->__id][self::KEY_CONN_PASSWORD]);
				$this->__pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			}
			catch (\PDOException $ex)
			{
				$this->__error($ex->getMessage());
			}
		}

		return $this->__pdo;
	}

	/**
	 * Connection exists flag getter
	 *
	 * @param int $id
	 * @return boolean
	 */
	private static function __isConnection($id)
	{
		return isset(self::$__connections[$id]);
	}

	/**
	 * Add debug log message
	 *
	 * @param string $message
	 * @return void
	 */
	private function __log($message)
	{
		if($this->__conf[self::KEY_CONF_DEBUG] && self::$__is_logging)
		{
			if($this->__conf[self::KEY_CONF_LOG_HANDLER] !== null)
			{
				if($this->__conf[self::KEY_CONF_LOG_HANDLER]($message)) // custom log handler
				{
					return;
				}
			}

			$this->__log[] = $message;
		}
	}

	/**
	 * Prepare recordset and pagination controls
	 *
	 * @param array $data
	 * @param array $paging (pagination controls)
	 * @return void
	 */
	private static function __paginationPrepData(&$data, &$paging)
	{
		if(count($data) > $paging[self::KEY_PAGE_RPP])
		{
			array_pop($data); // rm last row (more rows)
			$paging[self::KEY_PAGE_NEXT] = $paging[self::KEY_PAGE_PAGE] + 1;
		}

		if($paging[self::KEY_PAGE_PAGE] > 1)
		{
			$paging[self::KEY_PAGE_PREV] = $paging[self::KEY_PAGE_PAGE] - 1;
		}

		if($paging[self::KEY_PAGE_NEXT_STR] !== null && $paging[self::KEY_PAGE_NEXT] > 0) // next decorator
		{
			$paging[self::KEY_PAGE_NEXT_STR] = str_replace('{$next}', $paging[self::KEY_PAGE_NEXT],
				$paging[self::KEY_PAGE_NEXT_STR]);
		}
		else
		{
			$paging[self::KEY_PAGE_NEXT_STR] = '';
		}

		if($paging[self::KEY_PAGE_PREV_STR] !== null && $paging[self::KEY_PAGE_PREV] > 0) // prev decorator
		{
			$paging[self::KEY_PAGE_PREV_STR] = str_replace('{$prev}', $paging[self::KEY_PAGE_PREV],
				$paging[self::KEY_PAGE_PREV_STR]);
		}
		else
		{
			$paging[self::KEY_PAGE_PREV_STR] = '';
		}
	}

	/**
	 * Prepare query and pagination controls
	 *
	 * @param string $query
	 * @param array $pagination
	 * @return array
	 * @throws \Exception (when LIMIT clause already in query)
	 */
	private static function __paginationPrepQuery(&$query, &$pagination)
	{
		if(!preg_match('/LIMIT[\s]+[\d,]+(OFFSET[\s]+[\d]+)?/i', $query))
		{
			$p = [self::KEY_PAGE_RPP => $pagination[self::KEY_PAGE_RPP], self::KEY_PAGE_PAGE =>
				$pagination[self::KEY_PAGE_PAGE], self::KEY_PAGE_NEXT => 0, self::KEY_PAGE_PREV => 0,
				self::KEY_PAGE_OFFSET => 0,
				self::KEY_PAGE_NEXT_STR => $pagination[self::KEY_PAGE_NEXT_STR],
				self::KEY_PAGE_PREV_STR => $pagination[self::KEY_PAGE_PREV_STR]];
			$p[self::KEY_PAGE_OFFSET] = ($p[self::KEY_PAGE_PAGE] - 1) * $p[self::KEY_PAGE_RPP];
			$query = rtrim(trim($query), ';') . ' LIMIT ' . $p[self::KEY_PAGE_OFFSET] . ', '
				. ($p[self::KEY_PAGE_RPP] + 1);

			return $p;
		}
		else // LIMIT already exists
		{
			throw new \Exception('Failed to apply pagination to query,'
				. ' LIMIT clause already exists in query');
		}
	}

	/**
	 * Parse command
	 *
	 * @param string $cmd
	 * @return array (command parts)
	 */
	private static function __parseCmd($cmd)
	{
		$c = [ // init defaults
			self::KEY_CMD_COLUMNS => '*',
			self::KEY_CMD_CONN_ID => 1, // default ID
			self::KEY_CMD_OPTIONS => '',
			self::KEY_CMD_SQL => '',
			self::KEY_CMD_TABLE => ''
		];

		if(empty($cmd))
		{
			return $c;
		}

		// test for connection ID: '[1]*'
		if($cmd[0] === '[' && preg_match('/^\[([\d]+)\]/', $cmd, $m)) // match '[1]'
		{
			$c[self::KEY_CMD_CONN_ID] = (int)$m[1];
			$cmd = substr($cmd, strpos($cmd, ']') + 1); // rm connection ID '[1]'
		}

		// test for table: 'table:cmd' or 'table.id' or 'table/option' or 'table [SQL]' or 'table(cols)'
		if(preg_match('/^([\w]+)(?:\:|\.|\/|\s|\()/', $cmd, $m)) // match 'table(:|.|/| |()'
		{
			$c[self::KEY_CMD_TABLE] = $m[1];
			$cmd = substr($cmd, strlen($m[1])); // rm table
		}

		// test for columns: '(c1, c2)'
		if($cmd[0] === '(' && preg_match('/^\(([\w\,\s]+)\)/', $cmd, $m)) // match '(c1, c2)'
		{
			$c[self::KEY_CMD_COLUMNS] = trim($m[1]);
			$cmd = substr($cmd, strlen($m[1]) + 2); // rm columns
		}

		// test for cmd: ':cmd'
		if($cmd[0] === ':' && preg_match('/^\:([\w]+)/', $cmd, $m))
		{
			$c[self::KEY_CMD] = $m[1];
			$cmd = substr($cmd, strlen($m[1]) + 1, strlen($cmd)); // rm cmd
		}
		// test for select ID: '.1'
		else if($cmd[0] === '.' && preg_match('/^\.([\d]+)/', $cmd, $m))
		{
			$c[self::KEY_CMD_ID] = $m[1];
			$cmd = substr($cmd, strlen($m[1]) + 1, strlen($cmd)); // rm select ID
		}

		// test for options: '/opt1/opt2'
		if($cmd[0] === '/' && preg_match('/^\/([\w\/]+)/', $cmd, $m)) // match '/opt1/opt2'
		{
			$c[self::KEY_CMD_OPTIONS] = explode('/', $m[1]);
			$cmd = substr($cmd, strlen($m[1]) + 1, strlen($cmd)); // rm options
		}

		$cmd = trim($cmd);

		// test for SQL: 'WHERE x' or 'LIMIT 1'
		if(!empty($cmd))
		{
			$c[self::KEY_CMD_SQL] = $cmd;
		}

		if(isset($c[self::KEY_CMD_ID]) && isset($c[self::KEY_CMD_TABLE])) // add select ID to SQL
		{
			$sql = 'WHERE ' . self::__getConnection($c[self::KEY_CMD_CONN_ID])->getKey($c[self::KEY_CMD_TABLE])
				. '=' . self::__getConnection($c[self::KEY_CMD_CONN_ID])->__getPdo()->quote($c[self::KEY_CMD_ID]);

			if(strcasecmp(substr($c[self::KEY_CMD_SQL], 0, 5), 'where') === 0) // WHERE exists in SQL
			{
				$c[self::KEY_CMD_SQL] = $sql . ' AND ' . trim(substr($c[self::KEY_CMD_SQL], 5)); // rm WHERE, add AND
			}
			else
			{
				$c[self::KEY_CMD_SQL] = $sql . $c[self::KEY_CMD_SQL];
			}
		}

		if(!empty($c[self::KEY_CMD_SQL]))
		{
			$c[self::KEY_CMD_SQL] = ' ' . $c[self::KEY_CMD_SQL];
		}

		return $c;
	}

	/**
	 * Connection setter
	 *
	 * @staticvar int $connection_id
	 * @param array $connection
	 * @return int (connection ID)
	 * @throws \Exception (when connection ID not int, or connection already exists, or invalid connection params)
	 */
	private static function __setConnection(array $connection)
	{
		static $connection_id = 0;

		if(isset($connection[self::KEY_CONN_HOST], $connection[self::KEY_CONN_DATABASE],
			$connection[self::KEY_CONN_USER], $connection[self::KEY_CONN_PASSWORD]))
		{
			if(isset($connection[self::KEY_CONN_ID])) // manual connection ID
			{
				if(!is_int($connection[self::KEY_CONN_ID]) && !ctype_digit($connection[self::KEY_CONN_ID]))
				{
					throw new \Exception('Connection ID \'' . $connection[self::KEY_CONN_ID]
						. '\' must be integer only');
				}

				$connection[self::KEY_CONN_ID] = (int)$connection[self::KEY_CONN_ID];

				if(self::__isConnection($connection[self::KEY_CONN_ID]))
				{
					throw new \Exception('Connection ID \'' . $connection[self::KEY_CONN_ID] . '\' already exists');
				}
			}
			else // auto ID
			{
				$connection[self::KEY_CONN_ID] = ++$connection_id;

				while(self::__isConnection($connection[self::KEY_CONN_ID])) // enforce unique ID
				{
					$connection[self::KEY_CONN_ID] = ++$connection_id;
				}
			}

			self::$__connections[$connection[self::KEY_CONN_ID]] = new self($connection);

			return $connection[self::KEY_CONN_ID];
		}
		else
		{
			throw new \Exception('Invalid connection parameters (required: host, database, user, password)');
		}
	}

	/**
	 * Query options setter
	 *
	 * @staticvar array $map
	 * @param array $cmd
	 * @return int
	 */
	private static function &__setOptions(&$cmd)
	{
		static $map = [
			'ARRAY' => self::OPT_ARRAY,
			'CACHE' => self::OPT_CACHE,
			'FIRST' => self::OPT_FIRST,
			'MODEL' => self::OPT_MODEL,
			'PAGINATION' => self::OPT_PAGINATION,
			'QUERY' => self::OPT_QUERY
		];

		$options = 0;

		if(!empty($cmd[self::KEY_CMD_OPTIONS]))
		{
			$opts = $cmd[self::KEY_CMD_OPTIONS];
			$cmd[self::KEY_CMD_OPTIONS] = '';

			foreach($opts as $v)
			{
				$v = strtoupper($v);

				if(isset($map[$v])) // option flag
				{
					$options |= $map[$v];
				}
				else // SQL option
				{
					$cmd[self::KEY_CMD_OPTIONS] .= ' ' . $v;
				}
			}
		}

		return $options;
	}

	/**
	 * Configuration settings getter
	 *
	 * @param mixed $key (string for key, null for get all)
	 * @return mixed
	 */
	public function conf($key)
	{
		if(is_null($key)) // get all
		{
			return $this->__conf;
		}

		if(isset($this->__conf[$key]) || array_key_exists($key, $this->__conf))
		{
			return $this->__conf[$key];
		}
	}

	/**
	 * Execute command
	 *
	 * @staticvar array $pagination
	 * @param array $args
	 * @return mixed
	 * @throws \Exception (on command error)
	 */
	public static function exec(array $args)
	{
		$cmd = array_shift($args);

		$decorator = $decorator_filters = null;
		foreach($args as $k => $v)
		{
			if(is_string($v))
			{
				$decorator = $v;
				unset($args[$k]);

				// detect decorator callable filters
				if(isset($args[++$k]) && is_array($args[$k]) && is_callable(current($args[$k])))
				{
					$decorator_filters = $args[$k];
					unset($args[$k]);
				}

				break;
			}
		}

		if(is_string($cmd)) // parse cmd
		{
			static $pagination = [self::KEY_PAGE_RPP => 10, self::KEY_PAGE_PAGE => 1, self::KEY_PAGE_NEXT_STR => null,
				self::KEY_PAGE_PREV_STR => null];
			$cmd = self::__parseCmd($cmd); // parse cmd
			$options = &self::__setOptions($cmd);
			$params = []; // query params

			if(!isset($cmd[self::KEY_CMD])) // SELECT cmd
			{
				$q = 'SELECT' . $cmd[self::KEY_CMD_OPTIONS] . ' ' . $cmd[self::KEY_CMD_COLUMNS] . ' FROM '
					. $cmd[self::KEY_CMD_TABLE] . $cmd[self::KEY_CMD_SQL];

				if($options & self::OPT_PAGINATION) // add pagination
				{
					$p = self::__paginationPrepQuery($q, $pagination);
				}

				if(isset($args[0]) && is_array($args[0])) // query params
				{
					$params = &$args[0];
				}

				if($options & self::OPT_QUERY) // query as string
				{
					return $q;
				}
				// option /model return first record as model object (but not when using table.[id])
				else if($options & self::OPT_MODEL && !isset($cmd[self::KEY_CMD_ID]))
				{
					// LIMIT clause cannot exist in query SQL for model object
					if(stripos($cmd[self::KEY_CMD_SQL], 'limit') !== false
						&& preg_match('/LIMIT[\s]+[\d]+/i', $cmd[self::KEY_CMD_SQL]))
					{
						throw new \Exception('Failed to initialize model object, LIMIT clause already exists'
							. ' in query');
					}

					// prep query SQL
					if(strcasecmp(substr($cmd[self::KEY_CMD_SQL], 1, 5), 'where') === 0) // WHERE exists in SQL
					{
						// replace WHERE with AND
						$cmd[self::KEY_CMD_SQL] = ' AND' . substr($cmd[self::KEY_CMD_SQL], 6);
					}

					$cmd[self::KEY_CMD_SQL] = ' WHERE ' // add record key = :[key]
							. self::__getConnection($cmd[self::KEY_CMD_CONN_ID])->getKey($cmd[self::KEY_CMD_TABLE])
							. '=:'
							. self::__getConnection($cmd[self::KEY_CMD_CONN_ID])->getKey($cmd[self::KEY_CMD_TABLE])
							. $cmd[self::KEY_CMD_SQL];

					return new Model($cmd[self::KEY_CMD_COLUMNS] === '*' ? []
						: array_map('trim', explode(',', $cmd[self::KEY_CMD_COLUMNS])), // columns
						$cmd[self::KEY_CMD_TABLE], // table
						self::__getConnection($cmd[self::KEY_CMD_CONN_ID])->getKey($cmd[self::KEY_CMD_TABLE]), // key
						$cmd[self::KEY_CMD_CONN_ID], $params, $cmd[self::KEY_CMD_SQL], // connection ID, params, sql
						$decorator, $decorator_filters); // decorator
				}
				else if(isset($p)) // exec query with pagination
				{
					$r = self::__getConnection($cmd[self::KEY_CMD_CONN_ID])->query($q, $params, self::QUERY_TYPE_ROWS,
						$options & self::OPT_CACHE, $options & self::OPT_ARRAY);

					self::__paginationPrepData($r, $p);

					return ['pagination' =>
						self::__getConnection($cmd[self::KEY_CMD_CONN_ID])->conf(self::KEY_CONF_OBJECTS)
						? (object)$p : $p, 'rows' => self::__decorate($r, $decorator, $decorator_filters,
						self::DECORATE_TYPE_ARRAY)];
				}
				else // exec query
				{
					// select ID or /first option, return first row only
					if(isset($cmd[self::KEY_CMD_ID]) || $options & self::OPT_FIRST)
					{
						if(!preg_match('/LIMIT[\s]+[\d]+/i', $q))
						{
							$q = rtrim(rtrim($q), ';') . ' LIMIT 1';
						}

						$r = self::__getConnection($cmd[self::KEY_CMD_CONN_ID])->query($q, $params,
							self::QUERY_TYPE_ROWS, $options & self::OPT_CACHE, $options & self::OPT_ARRAY);

						if(isset($r[0]))
						{
							return self::__decorate($r[0], $decorator, $decorator_filters, self::DECORATE_TYPE_ARRAY);
						}

						return $decorator !== null ? '' : null; // no record
					}
					else
					{
						return self::__decorate(self::__getConnection($cmd[self::KEY_CMD_CONN_ID])->query($q, $params,
							self::QUERY_TYPE_ROWS, $options & self::OPT_CACHE, $options & self::OPT_ARRAY),
							$decorator, $decorator_filters,	self::DECORATE_TYPE_ARRAY);
					}
				}
			}
			else // process :cmd
			{
				switch($cmd[self::KEY_CMD])
				{
					case 'add': // insert|replace
					case 'insert':
					case 'log_handler':
					case 'replace':
						if(is_object($args[0])) // object add
						{
							$obj_arr = [];

							foreach(get_object_vars($args[0]) as $k => $v)
							{
								$obj_arr[$k] = $v;
							}

							$args[0] = &$obj_arr;
						}

						$values = [];
						foreach($args[0] as $k => $v)
						{
							if(is_array($v)) // plain SQL
							{
								if(isset($v[0]) && strlen($v[0]) > 0)
								{
									$values[] = $v[0];
								}
							}
							else // named param
							{
								$params[$k] = $v;
								$values[] = ':' . $k;
							}
						}

						$q = ( $cmd[self::KEY_CMD] === 'replace' ? 'REPLACE' : 'INSERT' ) . $cmd[self::KEY_CMD_OPTIONS]
							. ' INTO ' . $cmd[self::KEY_CMD_TABLE] . '(' . implode(', ', array_keys($args[0]))
							. ') VALUES(' . implode(', ', $values) . ')';

						if($options & self::OPT_QUERY)
						{
							return $q;
						}
						else if($cmd[self::KEY_CMD] === 'log_handler')
						{
							self::$__is_logging = false;
							if(self::__isConnection($cmd[self::KEY_CMD_CONN_ID]))
							{
								self::__getConnection($cmd[self::KEY_CMD_CONN_ID])->query($q, $params,
									self::QUERY_TYPE_AFFECTED);
							}
							self::$__is_logging = true;
							return;
						}
						else
						{
							return self::__decorate(self::__getConnection($cmd[self::KEY_CMD_CONN_ID])->query($q,
								$params, self::QUERY_TYPE_AFFECTED), $decorator, $decorator_filters,
								self::DECORATE_TYPE_TEST);
						}
						break;

					case 'call': // call SP/SF
					case 'call_affected':
					case 'call_rows':
						$params_str = '';

						if(isset($args[0]) && is_array($args[0]))
						{
							for($i = 0; $i <= count($args[0]) - 1; $i++)
							{
								$sep = empty($params_str) ? '' : ', ';
								if(!is_array($args[0][$i])) // param
								{
									$params_str .= $sep . '?';
									$params[] = $args[0][$i];
								}
								else if(isset($args[0][$i][0]) && strlen($args[0][$i][0]) > 0) // plain SQL
								{
									$params_str .= $sep . $args[0][$i][0];
								}
							}
						}

						$q = 'CALL' . $cmd[self::KEY_CMD_SQL] . '(' . $params_str . ')';

						if($options & self::OPT_QUERY)
						{
							return $q;
						}
						else
						{
							return self::__decorate(self::__getConnection($cmd[self::KEY_CMD_CONN_ID])->query($q,
								$params, $cmd[self::KEY_CMD] === 'call_affected' ? self::QUERY_TYPE_AFFECTED
								: ( $cmd[self::KEY_CMD] === 'call_rows' ? self::QUERY_TYPE_ROWS : 0 )), $decorator,
								$decorator_filters,	$cmd[self::KEY_CMD] === 'call_rows' ? self::DECORATE_TYPE_ARRAY
								: self::DECORATE_TYPE_TEST);
						}
						break;

					case 'cache': // set single cache expire time
						if(!empty($cmd[self::KEY_CMD_SQL]))
						{
							Cache::setExpire($cmd[self::KEY_CMD_SQL]);
						}
						break;

					case 'columns': // show table columns
						$q = 'SHOW COLUMNS FROM ' . $cmd[self::KEY_CMD_TABLE];

						if($options & self::OPT_QUERY)
						{
							return $q;
						}

						$r = self::__getConnection($cmd[self::KEY_CMD_CONN_ID])->query($q, null,
							self::QUERY_TYPE_ROWS);

						$c = [];
						if(isset($r) && is_array($r))
						{
							foreach($r as $v)
							{
								$v = array_values((array)$v);
								$c[] = $v[0];
							}
						}
						return $c;
						break;

					case 'commit': // commit transaction
						return self::__getConnection($cmd[self::KEY_CMD_CONN_ID])->__getPdo()->commit();
						break;

					case 'count': // count records
						$q = 'SELECT COUNT(1) AS count FROM ' . $cmd[self::KEY_CMD_TABLE] . $cmd[self::KEY_CMD_SQL];

						if($options & self::OPT_QUERY)
						{
							return $q;
						}
						else
						{
							$r = self::__getConnection($cmd[self::KEY_CMD_CONN_ID])->query($q,
								isset($args[0]) ? $args[0] : null, self::QUERY_TYPE_ROWS);

							if(isset($r[0]))
							{
								$r = (array)$r[0];
								return self::__decorate((int)$r['count'], $decorator, $decorator_filters,
									self::DECORATE_TYPE_TEST);
							}

							return $decorator !== null ? self::__decorate(0, $decorator, $decorator_filters,
								self::DECORATE_TYPE_TEST) : 0;
						}
						break;

					case 'debug': // debug info
						$d = [];

						foreach(self::$__connections as $k => $v)
						{
							$d[$k] = [
								'conf' => self::__getConnection($k)->conf(null),
								'keys' => self::__getConnection($k)->getKey(null),
								'log' => self::__getConnection($k)->getLog()
							];
						}

						return $d;
						break;

					case 'del': // delete
					case 'delete':
						$q = 'DELETE' . $cmd[self::KEY_CMD_OPTIONS] . ' FROM ' . $cmd[self::KEY_CMD_TABLE]
							. $cmd[self::KEY_CMD_SQL];

						if($options & self::OPT_QUERY)
						{
							return $q;
						}
						else
						{
							return self::__decorate(self::__getConnection($cmd[self::KEY_CMD_CONN_ID])->query($q,
								isset($args[0]) ? $args[0] : null, self::QUERY_TYPE_AFFECTED), $decorator,
								$decorator_filters,	self::DECORATE_TYPE_TEST);
						}
						break;

					case 'error': // error check
						return self::__decorate(self::__getConnection($cmd[self::KEY_CMD_CONN_ID])->isError(),
							$decorator, $decorator_filters, self::DECORATE_TYPE_TEST);
						break;

					case 'error_last': // get last error
						return self::__decorate(self::__getConnection($cmd[self::KEY_CMD_CONN_ID])->getError(),
							$decorator, $decorator_filters, self::DECORATE_TYPE_STRING);
						break;

					case 'exists': // check if record(s) exists
						$q = 'SELECT EXISTS(SELECT 1 FROM ' . $cmd[self::KEY_CMD_TABLE] . $cmd[self::KEY_CMD_SQL]
							. ') AS is_set';

						if($options & self::OPT_QUERY)
						{
							return $q;
						}
						else
						{
							$r = self::__getConnection($cmd[self::KEY_CMD_CONN_ID])->query($q,
								isset($args[0]) ? $args[0] : null, self::QUERY_TYPE_ROWS);

							if(isset($r[0]))
							{
								$r = (array)$r[0];
								return self::__decorate((int)$r['is_set'] > 0, $decorator, $decorator_filters,
									self::DECORATE_TYPE_TEST);
							}

							return $decorator !== null ? self::__decorate(false, $decorator, $decorator_filters,
								self::DECORATE_TYPE_TEST) : false;
						}
						break;

					case 'id': // get last insert ID
						return self::__decorate(
							self::__getConnection($cmd[self::KEY_CMD_CONN_ID])->__getPdo()->lastInsertId(),
								$decorator, $decorator_filters, self::DECORATE_TYPE_TEST);
						break;

					case 'key': // table primary key column name getter/setter
						if(isset($args[0]) && is_array($args[0])) // array setter
						{
							foreach($args[0] as $k => $v)
							{
								self::__getConnection($cmd[self::KEY_CMD_CONN_ID])->setKey($k, $v);
							}

							return self::__getConnection($cmd[self::KEY_CMD_CONN_ID])->getKey(null); // return all
						}

						$cmd[self::KEY_CMD_SQL] = trim($cmd[self::KEY_CMD_SQL]);
						if(strlen($cmd[self::KEY_CMD_SQL]) > 0) // setter
						{
							self::__getConnection($cmd[self::KEY_CMD_CONN_ID])->setKey($cmd[self::KEY_CMD_TABLE],
								$cmd[self::KEY_CMD_SQL]);
						}

						return self::__getConnection($cmd[self::KEY_CMD_CONN_ID])->getKey($cmd[self::KEY_CMD_TABLE]);
						break;

					case 'log': // log getter
						return self::__getConnection($cmd[self::KEY_CMD_CONN_ID])->getLog();
						break;

					case 'mod': // update
					case 'update':
						$values = [];
						if(is_array($args[0]) || is_object($args[0]))
						{
							foreach($args[0] as $k => $v)
							{
								if(is_array($v)) // plain SQL
								{
									if(isset($v[0]) && strlen($v[0]) > 0)
									{
										$values[] = $k . ' = ' . $v[0];
									}
								}
								else // named param
								{
									$params[$k] = $v;
									$values[] = $k . ' = :' . $k;
								}
							}
						}
						else
						{
							throw new \Exception('Update failed: using scalar value for setting columns and values'
								. ' (use array or object)');
						}

						if(isset($args[1]) && is_array($args[1])) // statement params
						{
							$params = array_merge($params, $args[1]);
						}

						$q = 'UPDATE' . $cmd[self::KEY_CMD_OPTIONS] . ' ' . $cmd[self::KEY_CMD_TABLE] . ' SET '
							. implode(', ', $values) . $cmd[self::KEY_CMD_SQL];

						if($options & self::OPT_QUERY)
						{
							return $q;
						}
						else
						{
							return self::__decorate(
								self::__getConnection($cmd[self::KEY_CMD_CONN_ID])->query($q, $params,
									self::QUERY_TYPE_AFFECTED), $decorator, $decorator_filters,
									self::DECORATE_TYPE_TEST);
						}
						break;

					case 'pagination': // pagination params getter/setter
						if(isset($args[0]) && is_array($args[0])) // setter
						{
							foreach($args[0] as $k => $v)
							{
								if(isset($pagination[$k]) || array_key_exists($k, $pagination))
								{
									if($k === self::KEY_PAGE_PAGE || $k === self::KEY_PAGE_RPP)
									{
										$v = (int)$v;

										if($v < 1)
										{
											continue;
										}
									}

									$pagination[$k] = $v;
								}
							}
						}

						return $pagination;
						break;

					case 'query': // manual query
						if($options & self::OPT_QUERY)
						{
							return $cmd[self::KEY_CMD_SQL];
						}

						if($options & self::OPT_PAGINATION) // apply pagination
						{
							if(preg_match('/^\s*select/i', $cmd[self::KEY_CMD_SQL]))
							{
								$p = self::__paginationPrepQuery($cmd[self::KEY_CMD_SQL], $pagination);
							}
							else
							{
								throw new \Exception('Failed to apply pagination to query,'
									. ' query must being with SELECT keyword');
							}
						}

						if(isset($p)) // preg pagination data
						{
							$r = self::__getConnection($cmd[self::KEY_CMD_CONN_ID])->query($cmd[self::KEY_CMD_SQL],
								isset($args[0]) ? $args[0] : null, self::QUERY_TYPE_ROWS, $options & self::OPT_CACHE,
								$options & self::OPT_ARRAY);

							self::__paginationPrepData($r, $p);

							return ['pagination' =>
								self::__getConnection($cmd[self::KEY_CMD_CONN_ID])->conf(self::KEY_CONF_OBJECTS)
								? (object)$p : $p, 'rows' => self::__decorate($r, $decorator, $decorator_filters,
									self::DECORATE_TYPE_ARRAY)];
						}
						else // execute query
						{
							if($options & self::OPT_FIRST) // first record only
							{
								if(!preg_match('/LIMIT[\s]+[\d]+/i', $cmd[self::KEY_CMD_SQL]))
								{
									$cmd[self::KEY_CMD_SQL] = rtrim(rtrim($cmd[self::KEY_CMD_SQL]), ';') . ' LIMIT 1';
								}

								$r = self::__getConnection($cmd[self::KEY_CMD_CONN_ID])->query($cmd[self::KEY_CMD_SQL],
										isset($args[0]) ? $args[0] : null, 0, $options & self::OPT_CACHE,
										$options & self::OPT_ARRAY);

								if(isset($r[0]))
								{
									return self::__decorate($r[0], $decorator, $decorator_filters,
										self::DECORATE_TYPE_ARRAY);
								}

								return $decorator !== null ? '' : null; // no record
							}
							else // all records
							{
								return self::__decorate(
									self::__getConnection($cmd[self::KEY_CMD_CONN_ID])->query($cmd[self::KEY_CMD_SQL],
										isset($args[0]) ? $args[0] : null, 0, $options & self::OPT_CACHE,
										$options & self::OPT_ARRAY), $decorator, $decorator_filters,
										self::DECORATE_TYPE_DETECT);
							}
						}

						break;

					case 'rollback': // rollback transaction
						return self::__getConnection($cmd[self::KEY_CMD_CONN_ID])->__getPdo()->rollBack();
						break;

					case 'tables': // show database tables
						$q = 'SHOW TABLES';

						if($options & self::OPT_QUERY)
						{
							return $q;
						}

						$r = self::__getConnection($cmd[self::KEY_CMD_CONN_ID])->query($q, null,
							self::QUERY_TYPE_ROWS);
						$t = [];
						if(isset($r) && is_array($r))
						{
							foreach($r as $v)
							{
								$v = array_values((array)$v);
								$t[] = $v[0];
							}
						}
						return $t;
						break;

					case 'transaction': // begin transaction
						return self::__getConnection($cmd[self::KEY_CMD_CONN_ID])->__getPdo()->beginTransaction();
						break;

					case 'truncate': // truncate table
						return self::__getConnection($cmd[self::KEY_CMD_CONN_ID])->query('TRUNCATE '
							. $cmd[self::KEY_CMD_TABLE]);
						break;

					default: // unknown command
						throw new \Exception('Invalid command \'' . $cmd[self::KEY_CMD] . '\'');
						break;
				}
			}
		}
		else if(is_array($cmd)) // connection/config
		{
			return self::__setConnection($cmd);
		}
	}

	/**
	 * Last error message getter
	 *
	 * @return string
	 */
	public function getError()
	{
		return $this->__error;
	}

	/**
	 * Table primary key column name(s) getter
	 *
	 * @param mixed $table (string for table, null for get all)
	 * @return mixed (string for key, array for get all)
	 */
	public function getKey($table)
	{
		if(is_null($table)) // get all
		{
			return $this->__key_map;
		}

		if(isset($this->__key_map[$table]))
		{
			return $this->__key_map[$table];
		}

		return self::DEFAULT_PRIMARY_KEY;
	}

	/**
	 * Debug log getter
	 *
	 * @return array
	 */
	public function getLog()
	{
		return $this->__log;
	}

	/**
	 * Error has occurred flag getter
	 *
	 * @return boolean
	 */
	public function isError()
	{
		return $this->__error !== null;
	}

	/**
	 * Execute query
	 *
	 * @param string $query
	 * @param array $params (prepared statement params)
	 * @param int $force_query_type
	 * @param boolean $use_cache
	 * @param boolean $force_array
	 * @return mixed (array|boolean|int)
	 */
	public function query($query, $params = null, $force_query_type = 0, $use_cache = false, $force_array = false)
	{
		$this->__log('Query: ' . $query);
		if(is_array($params) && !empty($params))
		{
			$q_params = [];
			foreach($params as $k => $v)
			{
				if(is_array($v))
				{
					$this->__error('Invalid query parameter(s) type: array (only use scalar values)');
					return false;
				}

				$q_params[] = $k . ' => ' . $v;
			}

			$this->__log('(Query params: ' . implode(', ', $q_params) . ')');
		}

		try
		{
			if($use_cache) // cache
			{
				$cache_key = Cache::getKey($this->__id, $query, $params);

				if(Cache::has($cache_key)) // is cached
				{
					$this->__log('(Cache read: ' . $cache_key . ')');
					return Cache::read($cache_key);
				}
			}

			if($this->__getPdo()) // verify valid connection (suppress error)
			{
				$sh = $this->__getPdo()->prepare($query);
				if($sh->execute( is_array($params) ? $params : null ))
				{
					if($force_query_type === self::QUERY_TYPE_ROWS
						|| preg_match('/^\s*(select|show|describe|optimize|pragma|repair)/i', $query)) // fetch
					{
						if(isset($cache_key)) // write/return cache
						{
							$this->__log('(Cache write: ' . $cache_key . ')');
							return Cache::write($cache_key, $sh->fetchAll( $this->conf(self::KEY_CONF_OBJECTS)
								&& !$force_array ? \PDO::FETCH_CLASS : \PDO::FETCH_ASSOC ));
						}
						else // no cache
						{
							return $sh->fetchAll( $this->conf(self::KEY_CONF_OBJECTS) && !$force_array
								? \PDO::FETCH_CLASS : \PDO::FETCH_ASSOC );
						}
					}
					else if($force_query_type === self::QUERY_TYPE_AFFECTED
						|| preg_match('/^\s*(delete|insert|update)/i', $query)) // affected
					{
						return $sh->rowCount();
					}
					else // other
					{
						return true;
					}
				}
				else
				{
					$this->__error($sh->errorInfo());
				}
			}
		}
		catch(\Exception $ex)
		{
			$this->__error($ex->getMessage());
		}

		return false;
	}

	/**
	 * Table primary key column name setter
	 *
	 * @param string $table
	 * @param string $key
	 * @return void
	 */
	public function setKey($table, $key)
	{
		if(!empty($key))
		{
			$this->__key_map[$table] = $key;
		}
	}
}