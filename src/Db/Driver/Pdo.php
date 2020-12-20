<?php

namespace Polaris\Db\Driver;

use Polaris\Db\Driver;
use Polaris\Db\Exception\Exception;
use Polaris\Db\Exception\InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Class Pdo
 *
 * @package Polaris\Db\Driver
 */
class Pdo extends \PDO implements Driver
{

	/**
	 * @var array
	 */
	protected $statements = [];


//	public function __construct($dsn, LoggerInterface $logger = null)
//	{
//		parent::__construct($dsn, $username, $passwd, [
//			\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
//		]);
//	}

	/**
	 * @param string $sql
	 * @param array $args
	 * @return bool|mixed|\PDOStatement
	 * @throws InvalidArgumentException
	 */
	public function prepare($sql, &$args = [])
	{
		$index = 0;
		$sql = preg_replace_callback('/(\?\?\?|\?)/', function ($matches) use (&$args, &$index) {
			if (empty($args) || !isset($args[$index])) {
				throw new InvalidArgumentException('sql param not match!', -__LINE__);
			}
			switch ($matches[0]) {
				case '?':
					if (is_array($args[$index])) {
						$param = current($args[$index]);
						unset($args[$index]);
						$args = array_values($args);
						return $param;
					} else {
						$index++;
						return '?';
					}
				case '???':
					if (!is_array($args[$index])) {
						throw new InvalidArgumentException('sql param not match!', -__LINE__);
					}
					$param = $args[$index];
					$args = array_merge(array_slice($args, 0, $index - 1), $param, array_slice($args, $index));
					$index += sizeof($param);
					return implode(', ', array_pad([], sizeof($param), '?'));
				default:
					throw new InvalidArgumentException('sql param not match!', -__LINE__);
			}
		}, $sql);
		$hash = md5($sql);
		if (!isset($this->statements[$hash])) {
			$this->statements[$hash] = parent::prepare($sql);
			if (!$this->statements[$hash]) {
				$error = $this->errorInfo();
				throw new InvalidArgumentException($error ? sprintf('[%s]%s', $error[0], $error[2]) : 'prepare error!', -abs($error[1] ?: __LINE__));
			}
		}
		return $this->statements[$hash];
	}

	/**
	 * @param mixed|string $sql
	 * @param mixed ...$args
	 * @return mixed
	 * @throws Exception
	 * @throws InvalidArgumentException
	 */
	public function query($sql, ...$args)
	{
		$stmt = $this->prepare($sql, $args);
		if (!$stmt->execute($args)) {
			$error = $stmt->errorInfo();
			throw new Exception($error ? sprintf('[%s]%s', $error[0], $error[2]) : 'query error!', -abs($error[1] ?: __LINE__));
		}
		$result = $stmt->fetchAll();
		$stmt->closeCursor();
		return $result;
	}

	/**
	 * @param mixed $sql
	 * @param mixed ...$args
	 * @return int|mixed|string
	 * @throws Exception
	 * @throws InvalidArgumentException
	 */
	public function execute($sql, ...$args)
	{
		$stmt = $this->prepare($sql, $args);
		if (!$stmt->execute($args)) {
			$error = $stmt->errorInfo();
			throw new Exception($error ? sprintf('[%s]%s', $error[0], $error[2]) : 'execute error!', -abs($error[1] ?: __LINE__));
		}
		$result = $this->lastInsertId() ?: $stmt->rowCount();
		$stmt->closeCursor();
		return $result;
	}

	/**
	 * @return bool
	 */
	public function begin()
	{
		return $this->beginTransaction();
	}

	/**
	 *
	 */
	public function __destruct()
	{
		if ($this->inTransaction()) {
			$this->rollBack();
		}
	}

}