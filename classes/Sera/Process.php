<?php

/**
 * A wrapper around the process control extension for forking child processes
 */
abstract class Sera_Process
{
	const SPAWN_CONTINUE=0;
	const SPAWN_TERMINATE=10;

	private $_parent=false;
	private $_terminate=false;

	/**
	 * Template function, called in the child process
	 */
	abstract public function main();

	/**
	 * Called when a forked child process terminates
	 */
	protected function onChildTerminate($exitcode) {}

	/**
	 * Called when a process is forked
	 */
	protected function onChildStart() {}

	/**
	 * Called when the daemon starts processing
	 */
	protected function onStart() {}

	/**
	 * Called when a SIGTERM is received
	 */
	protected function onTerminate() {}

	/**
	 * Called when a SIGHUP is received
	 */
	protected function onRestart() {}

	/**
	 * Runs process once
	 */
	final public function run()
	{
		$this->onStart();
		$this->main();
	}

	/**
	 * Rather than simply running and exiting, the main function is called
	 * @param $parallel int the number of parallel children to spawn
	 * @return void
	 */
	public function spawn($parallel=1)
	{
		$children = array();
		$parent = getmypid();
		$this->onStart();

		while(!$this->_terminate)
		{
			// fork a process if we can
			if(count($children) < $parallel)
			{
				$pid = pcntl_fork();
				if ($pid == -1)
				{
					throw new Sera_Exception('Failed to fork process');
				}
				else if($pid)
				{
					$children[$pid] = $pid;
				}
				else
				{
					$this->_parent = $parent;
					$this->onChildStart();
					exit($this->main());
				}
			}

			if(count($children) >= $parallel)
			{
				// patiently wait for a child to die
				pcntl_wait($status);

				// check the exit code
				if(pcntl_wexitstatus($status) == self::SPAWN_TERMINATE)
				{
					exit(self::SPAWN_TERMINATE);
				}

				// remove dead child processes
				foreach($children as $child)
				{
					if(!posix_kill($child, 0)) unset($children[$child]);
				}
			}
		}
	}

	/**
	 * Forks the daemon and kills it's parent
	 */
	public function daemonize()
	{
		// fork and kill parent
		$pid = pcntl_fork();
		if($pid > 0) exit();
	}

	/**
	 * Handle signals sent to process
	 */
	public function signal($signo)
	{
		if($signo == SIGINT)
		{
			$this->onTerminate();
			exit(0);
		}
		elseif($signo == SIGHUP)
		{
			$this->onRestart();
		}
	}

	/**
	 * Registers a signal handler for the process
	 */
	public function catchSignals()
	{
		// set up signal handling
		declare(ticks = 1);
		pcntl_signal(SIGHUP, array($this,"signal"), false);
		pcntl_signal(SIGINT, array($this,"signal"), false);
	}
}
