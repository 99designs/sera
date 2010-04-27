<?php

/**
 * Helpers for parallel execution of callbacks
 */
class Sera_Parallel
{
	/**
	 * Processes an iterator of things in parallel
	 * @return void
	 */
	public static function map($callback, $iterator, $concurrency=1, $chunksize=1000)
	{
		$children = array();
		$iterator->rewind();

		while($iterator->valid())
		{
			while(count($children) < $concurrency && $iterator->valid())
			{
				$chunk = array();

				// grab entries from the iterator
				for($i=0; $i<$chunksize && $iterator->valid(); $i++)
				{
					$chunk[$iterator->key()] = $iterator->current();
					$iterator->next();
				}

				// fork and process
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
					foreach($chunk as $idx=>$item) call_user_func($callback, $item, $idx);
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
