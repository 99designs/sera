<?php

/**
 * A worker that monitors a work queue
 */
class Sera_WorkerFarm extends Sera_Process
{
	const SPAWN_CONTINUE=0;
	const SPAWN_TERMINATE=10;

	private $_processMax=0;
	private $_processLimit=false;
	private $_terminate=false;
	private $_parent=false;
	private $_logger;
	private $_workers=array();
	private $_worker;

	/**
	 * Constructor
	 */
	public function __construct(Sera_Worker $worker=null)
	{
		$this->_logger = Ergo::loggerFor($this);

		if(!empty($worker))
			$this->addWorker($worker);
	}

	/**
	 * Adds a worker, with the number of processes to keep active
	 * @chainable
	 */
	public function addWorker($worker, $processes=1)
	{
		$worker->_spawn_id = count($this->_workers)+1;
		$worker->_spawn_config = array('processes'=>$processes);
		$this->_processMax += $processes;
		$this->_workers[$worker->_spawn_id] = $worker;
		return $this;
	}

	/**
	 * Enforce a maximum number of processes, false for none.
	 * @chainable
	 */
	public function setProcessLimit($limit)
	{
		$this->_processLimit = $limit;
		return $this;
	}

	/**
	 * Gets the process limit
	 */
	public function getProcessLimit()
	{
		return $this->_processLimit
			? min($this->_processMax, $this->_processLimit)
			: $this->_processMax
			;
	}

	/* (non-phpdoc)
	 * @see Sera_Process::main()
	 */
	public function main()
	{
		$this->spawn();
	}

	/**
	 * Spawns another worker, based on which need more.
	 * @return void
	 */
	private function _spawnWorker(&$children)
	{
		$count = array_count_values($children);
		$parent = getmypid();
		$candidates = array();

		// find a list of candidate workers
		foreach($this->_workers as $id=>$worker)
		{
			if(!isset($count[$id]) || $count[$id] < $worker->_spawn_config['processes'])
			{
				$candidates[] = $worker;
			}
		}

		// shuffle the candidates, take the first
		shuffle($candidates);
		$worker = array_pop($candidates);

		// fork the process
		if($pid = $this->fork())
		{
			//$this->_logger->trace("spawned child #%d for worker %d",$pid,$worker->_spawn_id);
			$children[$pid] = $worker->_spawn_id;
			return;
		}
		else
		{
			$this->_worker = $worker;
			$this->_parent = $parent;
			$this->onFork(getmypid());
			$code = $this->_executeWorker($worker);
			$this->onTerminate($code);
			exit($code);
		}
	}

	/**
	 * Sends all children a posix 0 signal, to check if they are alive. If not
	 * they are removed from the children array
	 */
	private function _reapWorkers(&$children)
	{
		// remove dead child processes
		foreach($children as $pid=>$workerId)
		{
			if(!posix_kill($pid, 0)) unset($children[$pid]);
		}
	}

	/**
	 * Executes a worker
	 */
	private function _executeWorker($worker)
	{
		// use a custom error handler, chain to the existing loggers
		$errorHandler = $worker->getErrorHandler();
		$errorHandler->logger()->addLoggers(
			Ergo::application()->errorHandler()->logger()
			);

		Ergo::application()->setErrorHandler($errorHandler);
		return $worker->execute();
	}

	/**
	 * Spawns workers until one of them returns Sera_WorkerFarm::SPAWN_TERMINATE.
	 * @return void
	 */
	public function spawn()
	{
		// needed for PHP 5.2
		if(!function_exists('pcntl_signal_dispatch'))
			declare(ticks = 1);

		$this->catchSignals();

		$children = array();
		$parent = getmypid();
		$this->onStart();

		$this->_logger->info("spawning workers");

		// enter spawn loop
		while(!$this->_terminate || count($children))
		{
			// needed for PHP 5.3
			if(function_exists('pcntl_signal_dispatch'))
				pcntl_signal_dispatch();

			// if we haven't hit the process limit yet
			if(!$this->_terminate && count($children) < $this->getProcessLimit())
			{
				$this->_spawnWorker($children);
			}
			else
			{
				// patiently wait for a child to die
				if(($pid = pcntl_wait($status, WUNTRACED)) > 0)
				{
					unset($children[$pid]);

					// check the exit code
					if(pcntl_wexitstatus($status) == self::SPAWN_TERMINATE && !$this->_terminate)
					{
						foreach(array_keys($children) as $pid)
							posix_kill($pid, SIGINT);

						$this->_terminate = true;
					}
				}
				else
				{
					// if wait fails, check each child
					$this->_reapWorkers($children);
				}
			}
		}

		$this->onTerminate(0);
	}

	/**
	 * Returns the parent process or false if it's the parent
	 * @return int
	 */
	protected function getParentPid()
	{
		return $this->_parent;
	}

	/* (non-phpdoc)
	 * @see Sera_Process::spawn()
	 */
	public function signal($signo)
	{
		if($this->isSignalTerminate($signo))
		{
			// if we are a child process, terminate the worker
			if($this->getParentPid())
			{
				// force a terminate on a double signal
				if($this->_terminate)
				{
					$this->_logger->info("forcefully terminating process #%d", getmypid());
					$this->_worker->terminate();
				}
				// terminate immediately if interuptable
				if($this->_worker->isInteruptable())
				{
					$this->onTerminate(self::SPAWN_TERMINATE);
					$this->_worker->terminate();
				}
				// otherwise wait for the work to finish
				else
				{
					$this->_logger->trace("waiting for work to finish in process #%d", getmypid());
					$this->_terminate = true;
				}
			}
			else
			{
				if(!$this->_terminate)
				{
					$this->_logger->info("gracefully terminating workers, press ctrl-c to kill");
					$this->_terminate = true;
				}
			}
		}
	}
}
