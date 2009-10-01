<?php

/**
 * A worker encapsulates an distributed action, for instance
 * parsing a log or executing a task.
 * @see Sera_WorkerFarm
 */
interface Sera_Worker
{
	const WORKER_SUCCESS=0;
	const WORKER_TERMINATE=10;

	/**
	 * Executes the workers job. This is executed in a seperate process.
	 * @return a process exit code, either 0 or 10 to terminate the worker farm.
	 */
	public function execute();

	/**
	 * Gets the error handler for uncaught exceptions in the worker
	 * @return Ergo_ErrorHandler
	 */
	public function getErrorHandler();

	/**
	 * If true is returned from this function, the worker can be interupted
	 * or terminated by the {@link Sera_WorkerFarm}
	 */
	public function isInteruptable();
}

