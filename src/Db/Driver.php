<?php

namespace Polaris\Db;

/**
 * Interface Driver
 *
 * @package Polaris\Db
 */
interface Driver
{

	/**
	 * query
	 *
	 * @param mixed $sql
	 * @param array $args
	 * @return mixed
	 */
	public function query($sql, ...$args);

	/**
	 * execute
	 *
	 * @param mixed $sql
	 * @param array $args
	 * @return mixed
	 */
	public function execute($sql, ...$args);

	/**
	 * @param mixed $name
	 * @return mixed
	 */
	public function lastInsertId($name = null);

	/**
	 * @param mixed $str
	 * @return mixed
	 */
	public function quote($str);

	/**
	 * @return bool
	 */
	public function begin();

	/**
	 * @return bool
	 */
	public function commit();

	/**
	 * @return bool
	 */
	public function rollBack();

	/**
	 * @return bool
	 */
	public function inTransaction();

}