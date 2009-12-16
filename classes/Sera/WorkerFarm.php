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
	private $_worker;
	private $_workers=array();

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
		$worker->_spawn_processes = array();
		$worker->_spawn_config = array(
			'processes'=>$processes
			);

		$this->_processMax += $processes;
		$this->_workers[] = $worker;
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

	/* (non-phpdoc)
	 * @see Sera_Process::main()
	 */
	public function main()
	{
		// use a custom error handler, chain to the existing loggers
		$errorHandler = $this->_worker->getErrorHandler();
		$errorHandler->logger()->addLoggers(
			Ergo::application()->errorHandler()->logger()
			);

		Ergo::application()->setErrorHandler($errorHandler);
		return $this->_worker->execute();
	}

	/**
	 * Checks whether another worker should be spawned
	 */
	private function _spawnMoreWorkers($worker)
	{
		return count($worker->_spawn_processes) < $worker->_spawn_config['processes'];
	}

	/**
	 * Spawns workers until one of them returns Sera_WorkerFarm::SPAWN_TERMINATE.
	 * @return void
	 */
	public function spawn()
	{
		// needed for signal handling
		declare(ticks = 1);
		$this->catchSignals();

		$children = array();
		$parent = getmypid();
		$this->onStart();

		// enter spawn loop
		while(!$this->_terminate || count($children))
		{
			foreach($this->_workers as $worker)
			{
				$processCap = $this->_processLimit
					? min($this->_processLimit, $this->_processMax)
					: $this->_processMax
					;

				// fork a process if we can
				if(!$this->_terminate
					&& $this->_spawnMoreWorkers($worker)
					&& count($children) < $processCap)
				{
					$pid = pcntl_fork();
					if ($pid == -1)
					{
						throw new Sera_Exception('Failed to fork process');
					}
					else if($pid)
					{
						$children[$pid] = $pid;
						$worker->_spawn_processes[$pid] = $pid;
					}
					else
					{
						$this->_parent = $parent;
						$this->_worker = $worker;
						$this->onFork(getmypid());
						$code = $this->main();
						$this->onTerminate($code);
						exit($code);
					}
				}

				// wait for a child to die
				if($this->_terminate || count($children) >= $processCap)
				{
					// patiently wait for a child to die
					pcntl_waitpid(0, $status);

					// check the exit code
					if(pcntl_wexitstatus($status) == self::SPAWN_TERMINATE)
					{
						$this->_terminate = true;
					}

					// remove dead child processes
					foreach($worker->_spawn_processes as $child)
					{
						if(!posix_kill($child, 0))
						{
							unset($worker->_spawn_processes[$child]);
							unset($children[$child]);
						}
					}
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
			if($this->getParentPid())
			{
				// terminate immediately if interuptable
				if($this->_worker->isInteruptable())
				{
					$this->onTerminate(self::SPAWN_TERMINATE);
					exit(self::SPAWN_TERMINATE);
				}
				// otherwise wait for the work to finish
				else
				{
					$this->_logger->trace("waiting for work to finish");
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
				else
				{
					// force a terminate on a double signal
					$this->_logger->trace("forcing worker termination");
					exit(1);
				}
			}
		}
	}
}
