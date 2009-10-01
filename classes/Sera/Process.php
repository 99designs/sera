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
	private $_daemon=false;

	/**
	 * Template function, called in the child process
	 */
	abstract public function main();

	/**
	 * Called when a process is forked
	 */
	protected function onFork($pid) {}

	/**
	 * Called when the process starts
	 */
	protected function onStart() {}

	/**
	 * Called when the process terminates
	 */
	protected function onTerminate($exitCode) {}

	/**
	 * Runs process once
	 */
	public function run()
	{
		$this->onStart();
		$code = $this->main();
		$this->onTerminate($code);
		exit($code);
	}

	/**
	 * Rather than simply running and exiting, the main function is called continually
	 * until it returns Sera_Process::SPAWN_TERMINATE.
	 * @param $count int the number of processes to spawn
	 * @return void
	 */
	public function spawn($count=1)
	{
		// needed for signal handling
		declare(ticks = 1);
		$this->catchSignals();

		$children = array();
		$parent = getmypid();
		$this->onStart();

		while(!$this->_terminate || count($children))
		{
			// fork a process if we can
			if(!$this->_terminate && count($children) < $count)
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
					$this->onFork(getmypid());
					$code = $this->main();
					$this->onTerminate($code);
					exit($code);
				}
			}

			// wait for a child to die
			if($this->_terminate || count($children) >= $count)
			{
				// patiently wait for a child to die
				pcntl_waitpid(0, $status);

				// check the exit code
				if(pcntl_wexitstatus($status) == self::SPAWN_TERMINATE)
				{
					$this->_terminate = true;
				}

				// remove dead child processes
				foreach($children as $child)
				{
					if(!posix_kill($child, 0)) unset($children[$child]);
				}
			}
		}

		$this->onTerminate(0);
	}

	/**
	 * Forks the daemon and kills it's parent
	 */
	public function daemonize()
	{
		$this->_daemon = true;

		// fork and kill parent
		if(pcntl_fork() > 0) exit();
	}

	/**
	 * Handle signals sent to process
	 */
	protected function signal($signo)
	{
		if($this->isSignalTerminate($signo))
		{
			$this->_terminate = true;
		}
	}

	/**
	 * Registers a signal handler for the process
	 */
	protected function catchSignals()
	{
		// set up signal handling
		pcntl_signal(SIGHUP, array($this,"signal"), false);
		pcntl_signal(SIGINT, array($this,"signal"), false);
		pcntl_signal(SIGQUIT, array($this,"signal"), false);
		pcntl_signal(SIGTERM, array($this,"signal"), false);
	}

	/**
	 * Returns the parent process or false if it's the parent
	 * @return int
	 */
	protected function getParentPid()
	{
		return $this->_parent;
	}

	/**
	 * Determines if a signal should cause the process to terminate
	 */
	protected function isSignalTerminate($signo)
	{
		return ($signo == SIGHUP && !$this->_daemon) ||
			in_array($signo,array(SIGINT,SIGQUIT,SIGTERM));
	}
}
