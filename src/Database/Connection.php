<?php

namespace Polaris\Database;

use PDO;
use PDOStatement;
use Polaris\Config\Config;
use Polaris\Config\ConfigInterface;
use Polaris\Pool\Manager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 *
 */
class Connection implements LoggerAwareInterface
{

	use LoggerAwareTrait;

	/**
	 * @var array
	 */
	protected static array $paramTypes = [
		'boolean' => PDO::PARAM_BOOL,
		'integer' => PDO::PARAM_INT,
		'double' => PDO::PARAM_INT,
		'NULL' => PDO::PARAM_NULL,
	];

	/**
	 * @var ContainerInterface|null
	 */
	protected ?ContainerInterface $container;

	/**
	 * @var ConfigInterface|null
	 */
	protected ?ConfigInterface $config;

	/**
	 * @var PDO|null
	 */
	protected ?PDO $connection = null;

	/**
	 * @var string[]
	 */
	protected array $context = [];

	/**
	 * @param ContainerInterface|null $container
	 * @throws \Psr\Container\ContainerExceptionInterface
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 * @throws \Polaris\Exception
	 */
	public function __construct(ContainerInterface $container = null)
	{
		$this->container = $container;
		$this->config = $container && $container->has(ConfigInterface::class) ?
			$container->get(ConfigInterface::class) : new Config(dirname(__DIR__, 6));
		$this->setLogger($container && $container->has(LoggerInterface::class) ?
			$container->get(LoggerInterface::class) : new NullLogger());
	}

	/**
	 * @return array
	 */
	protected function getConnectionOptions(): array
	{
		return [
			sprintf('%s:host=%s;port=%d;dbname=%s;%s',
				parse_url(getenv('DATABASE_URL'), PHP_URL_SCHEME),
				parse_url(getenv('DATABASE_URL'), PHP_URL_HOST),
				parse_url(getenv('DATABASE_URL'), PHP_URL_PORT) ?? 3306,
				trim(parse_url(getenv('DATABASE_URL'), PHP_URL_PATH), '/'),
				str_replace('&', ';',  parse_url(getenv('DATABASE_URL'), PHP_URL_QUERY))
			),
			parse_url(getenv('DATABASE_URL'), PHP_URL_USER),
			parse_url(getenv('DATABASE_URL'), PHP_URL_PASS)
		];
	}

	/**
	 * @return PDO
	 * @throws \Polaris\Exception
	 */
	public function getConnection(): PDO
	{
		if ($this->connection) {
			return $this->connection;
		}
		$args = $this->getConnectionOptions();
		$identity = implode('@', $args);
		$factory = function () use ($args) {
			return new PDO(...$args);
		};
		try {
			if (Manager::available()) {
				$pool = Manager::has($identity) ? Manager::get($identity) : null;
				if (!$pool) {
					$pool = Manager::create($identity, function () use ($args) {
						return new PDO(...$args);
					}, $this->config->get('database.size', 32));
				}
				$this->connection = $pool->pop();
			} else {
				$this->connection = $factory();
			}
			return $this->connection;
		} catch (\Polaris\Exception $e) {
			throw $e;
		} catch (Throwable $e) {
			throw new Exception($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * @return bool
	 * @throws \Polaris\Exception
	 */
	public function begin(): bool
	{
		return $this->getConnection()->beginTransaction();
	}

	/**
	 * @return bool
	 * @throws \Polaris\Exception
	 */
	public function commit(): bool
	{
		return $this->getConnection()->commit();
	}

	/**
	 * @return bool
	 * @throws \Polaris\Exception
	 */
	public function rollback(): bool
	{
		return $this->getConnection()->rollBack();
	}

	/**
	 * @param string $sql
	 * @param array $args
	 * @return array
	 * @throws \Polaris\Exception
	 */
	protected function prepare(string $sql, array $args = []): array
	{
		//@todo cache stmt
		$index = 0;
		$sql = preg_replace_callback('/(\?)/', function () use (&$args, &$index) {
			if (is_array($args[$index])) {
				$param = current($args[$index]);
				unset($args[$index]);
				$args = array_values($args);
				return $param;
			} else {
				$index++;
				return '?';
			}
		}, $sql);
		$this->logger->info('[Prepare]' . $sql, $this->context);
		return [$this->getConnection()->prepare($sql), $args];
	}

	/**
	 * @param PDOStatement $statement
	 * @param array $values
	 * @return PDOStatement
	 */
	private function bindStmt(PDOStatement $statement, array $values): PDOStatement
	{
		foreach ($values as $k => $v) {
			$typ = gettype($v);
			$statement->bindValue($k + 1, $v, static::$paramTypes[$typ] ?? PDO::PARAM_STR);
		}
		return $statement;
	}

	/**
	 * @param string $sql
	 * @param array $args
	 * @return integer
	 * @throws \Polaris\Exception
	 */
	public function execute(string $sql, array $args = []): int
	{
		/**
		 * @var PDOStatement $stmt
		 */
		list($stmt, $args) = $this->prepare($sql, $args);
		$this->logger->info('[Execute]' . str_replace(["\n", "\r", "\t"], '', var_export($args, true)), $this->context);
		if (!$this->bindStmt($stmt, $args)->execute()) {
			list(, $code, $message) = $stmt->errorInfo();
			throw new Exception($message, $code);
		}
		return $stmt->rowCount();
	}

	/**
	 * @param string $sql
	 * @param array $args
	 * @return array|null
	 * @throws \Polaris\Exception
	 */
	public function query(string $sql, array $args = []): ?array
	{
		list($stmt, $args) = $this->prepare($sql, $args);
		$this->logger->info('[Query]' . str_replace(["\n", "\r", "\t"], '', var_export($args, true)), $this->context);
		if (!$this->bindStmt($stmt, $args)->execute()) {
			list(, $code, $message) = $stmt->errorInfo();
			throw new Exception($message, $code);
		}
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * @param string $sql
	 * @param array $args
	 * @return array|null
	 * @throws \Polaris\Exception
	 */
	public function fetchAll(string $sql, array $args = []): ?array
	{
		return $this->query($sql, $args);
	}

	/**
	 * @param string $sql
	 * @param array $args
	 * @return array|null
	 * @throws \Polaris\Exception
	 */
	public function fetch(string $sql, array $args = []): ?array
	{
		$result = $this->fetchAll($sql, $args);
		reset($result);
		return $result ? current($result) : $result;
	}

	/**
	 * @param string $sql
	 * @param array $args
	 * @return mixed
	 * @throws \Polaris\Exception
	 */
	public function fetchColumn(string $sql, array $args = [])
	{
		$result = $this->fetch($sql, $args);
		reset($result);
		return $result ? current($result) : null;
	}

	/**
	 * @param string|null $value
	 * @param string $sql
	 * @param array $args
	 * @param mixed $offset
	 * @return array
	 * @throws \Polaris\Exception
	 */
	public function fetchPair(?string $value, string $sql, array $args = [], $offset = null): array
	{
		$result = $this->fetchAll($sql, $args);
		return $result ? array_column(empty($result) ? [] : $result, $value, $offset) : [];
	}

	/**
	 * @throws Exception
	 * @throws \Polaris\Exception
	 */
	public function __destruct()
	{
		if (!$this->connection) {
			return;
		}
		if ($this->connection->inTransaction()) {
			$this->connection->rollBack();
		}
		try {
			if (Manager::available()) {
				$args = $this->getConnectionOptions();
				$identity = implode('@', $args);
				if (Manager::has($identity)) {
					Manager::get($identity)->push($this->connection);
				}
			}
		} catch (\Polaris\Exception $e) {
			throw $e;
		} catch (Throwable $e) {
			throw new Exception($e->getMessage(), $e->getCode(), $e);
		} finally {
			$this->connection = null;
		}
	}

}