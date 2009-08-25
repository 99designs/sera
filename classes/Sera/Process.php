<?php

/**
 * A wrapper around the process control extension
 */
abstract class Sera_Process
{
	const SPAWN_CONTINUE=0;
	const SPAWN_TERMINATE=10;

	public function __construct()
	{
		// set up signal handling
		declare(ticks = 1);
		pcntl_signal(SIGTERM, array($this,"signal"));
		pcntl_signal(SIGHUP, array($this,"signal"));
	}

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
	 * Runs the daemon
	 */
	final public function run()
	{
		$this->onStart();
		$this->main();
	}

	/**
	 * Rather than simply running and exiting, the main function is called
	 * until a child exits with a state of SPAWN_TERMINATE.
	 */
	public function spawn()
	{
		$this->onStart();

		while(true)
		{
			$pid = $this->fork();

			// the parent
			if($pid)
			{
				$exitCode = $this->wait();
				$this->onChildTerminate($exitCode);
				if($exitCode == self::SPAWN_TERMINATE)
				{
					exit($exitCode);
				}
			}
			// the child
			else
			{
				$this->onChildStart();
				exit($this->main());
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
	 * Fork the process and return the PID
	 */
	public function fork()
	{
		$pid = pcntl_fork();
		if ($pid == -1)
		{
			throw new Exception("Failed to fork process");
		}

		return $pid;
	}

	/**
	 * Wait for any child processes
	 */
	public function wait()
	{
		pcntl_wait($status);
		return pcntl_wexitstatus($status);
	}

	/**
	 * Handle signals sent to process
	 */
	public function signal($signo)
	{
		if($signo == SIGTERM)
		{
			$this->onTerminate();
			exit(0);
		}
		elseif($signo == SIGHUP)
		{
			$this->onRestart();
		}
	}
}
