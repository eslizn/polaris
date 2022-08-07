<?php

namespace Polaris\Pool;

use Throwable;

/**
 *
 */
class Pool extends \Swoole\Coroutine\Channel
{

    /**
     * @var callable
     */
    protected $factory = null;

    /**
     * @var int|null
     */
    protected ?int $idle = null;

    /**
     * @var float
     */
    protected float $timeout = 0;

    /**
     * @var callable
     */
    protected $destruct = null;

	/**
	 * @param callable $factory
	 * @param int $size
	 * @param float $timeout
	 * @param callable|null $destruct
	 * @throws Exception
	 */
    public function __construct(callable $factory, int $size = 32, float $timeout = 0.1, ?callable $destruct = null)
    {
        $this->factory = $factory;
        $this->idle = $size;
        $this->timeout = $timeout;
        $this->destruct = $destruct;
		try {
			parent::__construct($size);
		} catch (Throwable $e) {
			throw new Exception($e->getMessage(), $e->getCode(), $e);
		}
    }

    /**
     * @param mixed $data
     * @param float|null $timeout
     * @return mixed
     * @throws \Polaris\Exception
     */
    public function push($data, $timeout = null)
    {
        if (is_null($this->idle)) {
            return false;
        }
        if (is_null($data)) {
            try {
				$data = ($this->factory)();
				return parent::push($data, $timeout ?: $this->timeout);
			} catch (\Polaris\Exception $e){
				$this->idle--;
				throw $e;
            } catch (Throwable $e) {
                $this->idle--;
				throw new Exception($e->getMessage(), $e->getCode(), $e);
            }
        }
    }

    /**
     * @param float|null $timeout
     * @return mixed
     * @throws \Polaris\Exception
     */
    public function pop($timeout = null)
    {
        try {
			if (is_null($this->idle)) {
				return false;
			}
			if ($this->isEmpty() && $this->idle > 0) {
				$this->idle--;
				$this->push(null, $timeout ?: $this->timeout);
			}
			return parent::pop($timeout ?: $this->timeout);
		} catch (\Polaris\Exception $e){
			throw $e;
		} catch (Throwable $e) {
			throw new Exception($e->getMessage(), $e->getCode(), $e);
		}
    }

    /**
     * @return bool
     * @throws \Polaris\Exception
     */
    public function close(): bool
    {
        if (is_null($this->idle)) {
            return true;
        }
		try {
			$this->idle = 0;
			while (!$this->isEmpty()) {
				if (is_null($this->destruct)) {
					$this->pop($this->timeout);
				} else {
					($this->destruct)($this->pop($this->timeout));
				}
			}
			$this->idle = null;
			return parent::close();
		} catch (\Polaris\Exception $e){
			throw $e;
		} catch (Throwable $e) {
			throw new Exception($e->getMessage(), $e->getCode(), $e);
		}
    }

    /**
     * @throws \Polaris\Exception
     */
    public function __destruct()
    {
        if (is_null($this->idle)) {
            $this->close();
        }
    }

}