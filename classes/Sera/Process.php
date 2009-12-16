<?php

/**
 * A wrapper around the process control extension for forking child processes
 */
abstract class Sera_Process
{
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
			$this->uncatchSignals();
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
	 * Unregisters signal handers for the process
	 */
	protected function uncatchSignals()
	{
		pcntl_signal(SIGHUP, SIG_DFL);
		pcntl_signal(SIGINT, SIG_DFL);
		pcntl_signal(SIGQUIT, SIG_DFL);
		pcntl_signal(SIGTERM, SIG_DFL);
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
