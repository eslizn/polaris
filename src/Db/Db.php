<?php

namespace Polaris\Db;

use Polaris\Db\Exception\Exception;
use Throwable;

/**
 * Class Db
 * @package Polaris\Db
 */
class Db
{

	/**
	 * driver name
	 *
	 * @var Driver
	 */
	protected $driver = null;

	/**
	 * @var array
	 */
	protected $options = [];

	/**
	 * Db constructor.
	 * @param mixed $driver
	 * @param array $options
	 */
	public function __construct($driver, $options = [])
	{
		$this->driver = $driver;
		$this->options = $options;
	}

	/**
	 * @return Driver
	 */
	public function getDriver()
	{
		if (!is_object($this->driver)) {
			$this->driver = new ($this->driver)($this->options);
		}
		return $this->driver;
	}

	/**
	 * @param string $sql
	 * @param array $args
	 * @return integer
	 * @throws Throwable
	 */
	public function execute($sql, $args = [])
	{
		try {
			return $this->getDriver()->execute($sql, $args);
		} catch (Throwable $e) {
			throw new Exception($e->getMessage(), $e->getCode(), $this);
		}
	}

	/**
	 * @param string $sql
	 * @param array $args
	 * @return array
	 * @throws Throwable
	 */
	public function query($sql, $args = [])
	{
		try {
			return $this->getDriver()->query($sql, $args);
		} catch (Throwable $e) {
			throw new Exception($e->getMessage(), $e->getCode(), $this);
		}
	}

	/**
	 * @param mixed $name
	 * @return mixed
	 */
	public function lastInsertId($name = null)
	{
		return $this->getDriver()->lastInsertId($name);
	}

	/**
	 * @param mixed $str
	 * @return mixed
	 */
	public function quote($str)
	{
		return $this->getDriver()->quote($str);
	}

	/**
	 * @return bool
	 */
	public function begin()
	{
		return $this->getDriver()->begin();
	}

	/**
	 * @return bool
	 */
	public function commit()
	{
		return $this->getDriver()->commit();
	}

	/**
	 * @return bool
	 */
	public function rollBack()
	{
		return $this->getDriver()->rollBack();
	}

	/**
	 * @return bool
	 */
	public function inTransaction()
	{
		return $this->getDriver()->inTransaction();
	}

	/**
	 * @param string $sql
	 * @param array $args
	 * @return array
	 * @throws Throwable
	 */
	public function fetchAll($sql, $args = [])
	{
		return $this->query($sql, $args);
	}

	/**
	 * @param string $sql
	 * @param array $args
	 * @return mixed
	 * @throws Throwable
	 */
	public function fetch($sql, $args = [])
	{
		$result = $this->fetchAll($sql, $args);
		reset($result);
		return $result ? current($result) : $result;
	}

	/**
	 * @param string $sql
	 * @param array $args
	 * @return mixed
	 * @throws Throwable
	 */
	public function fetchColumn($sql, $args = [])
	{
		$result = $this->fetch($sql, $args);
		reset($result);
		return $result ? current($result) : null;
	}

	/**
	 * @param string $value
	 * @param string $sql
	 * @param array $args
	 * @param mixed $offset
	 * @return array
	 * @throws Throwable
	 */
	public function fetchPair($value, $sql, $args = [], $offset = null)
	{
		$result = $this->fetchAll($sql, $args);
		return $result ? array_column(empty($result) ? [] : $result, $value, $offset) : [];
	}

}