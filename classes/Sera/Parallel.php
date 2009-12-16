<?php

/**
 * Helpers for parallel execution of callbacks
 */
class Sera_Parallel
{
	/**
	 * Processes an array of things in parallel
	 * @return void
	 */
	public static function map($callback, $array, $concurrency=1, $chunksize=1000)
	{
		$children = array();

		while(count($array))
		{
			while(count($array) && count($children) < $concurrency)
			{
				$chunk = array_slice($array, 0, $chunksize);
				$array = array_slice($array, count($chunk));
				$pid = pcntl_fork();

				if ($pid == -1)
				{
					throw new Sera_Exception('Failed to fork process');
				}
				// parent process
				else if($pid)
				{
					$children[$pid] = $pid;
				}
				// child process
				else
				{
					foreach($chunk as $item) call_user_func($callback, $item);
					exit(0);
				}
			}

			// remove dead child processes
			foreach($children as $child)
			{
				if(!posix_kill($child, 0)) unset($children[$child]);
			}

			// wait for a child to die
			if(count($children) >= $concurrency) pcntl_waitpid(0, $status);
		}
	}

	/**
	 * Runs a callback in a separate process.
	 * @param $wait bool whether to wait for the process to finish before returning
	 * @returns the pid of the forked process
	 */
	public static function fork($callback, $params=array(), $wait=false)
	{
		$pid = pcntl_fork();
		if ($pid == -1)
		{
			throw new Sera_Exception('Failed to fork process');
		}
		else if(!$pid)
		{
			exit(call_user_func_array($callback, $params));
		}
		else if($wait)
		{
			pcntl_waitpid(0, $status);
		}
		return $pid;
	}
}
