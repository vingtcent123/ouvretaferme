<?php
/**
 * Parallel
 * Enables parallel execution of PHP functions
 *
 * @author Nicolas Favre-Felix
 */
class Parallel {

	/**
	 * Current queue ID.
	 * You can see the active queues in a shell with "ipcs", and remove them with ipcrm -Q
	 */
	private $queueId;

	/**
	 * Actual queue object as returned by msg_get_queue
	 */
	private $queue;

	/**
	 * Array of $id => $result where $result is the return value of the $i-eth fun call.
	 */
	private $results;

	/**
	 * Array of dead pids to reap
	 */
	private $pids;

	/**
	 * These are used to handle SIGINT kills, properly removing the queues
	 * and killing the processes. Non-static doesn't work here with signals.
	 */
	private static $spawnedPids = [];
	private static $registeredQueues = [];

	public function __construct() {
		declare(ticks = 1); // enables signal handling.
	}

	/**
	 * Exits cleanly on SIGINT. Removes existing queues and kills children processes.
	 * NOT to be called directly.
	 */
	public function cleanExit() {

		foreach(self::$spawnedPids as $pid) {
			posix_kill($pid, SIGKILL);
		}
		foreach(self::$registeredQueues as $queueId) {
			msg_remove_queue(msg_get_queue($queueId));
		}
		foreach(self::$spawnedPids as $pid) {
			$status = NULL;
			pcntl_wait($status);
		}
		exit(0);
	}

	/**
	 * Get one process out of the pool by waiting for a message.
	 * - Adds the process PID to $this->pids
	 * - Adds the function result to $this->results
	 *
	 * @return nothing
	 */
	private function removeOne() {

		$message = NULL;
		$status = msg_receive($this->queue, 0, $message, 1024, $infos, true);

		$status = NULL;
		pcntl_wait($status);

		// message is triple(function number, pid, return value)
		list($i, $pid, $return) = $infos;
		$this->results[$i] = $return; // added with the key $i so that we can reorder the results.

		$this->pids []= $pid; // add to the kill-list.
	}

	/**
	 * Parallel loop. Runs several functions in parallel, with an optional limit
	 * on the maximum number of concurrently alive processes.
	 *
	 * @param $functions array of $fun where $fun is [$function, array $args]
	 * @param $poolSize The maximum number of processes allowed to run simultaneously
	 *
	 * @return The results of each call, in an array. The order of $functions is preserved.
	 */
	public function pmap(array $functions, int $poolSize = NULL): array {

		pcntl_signal(SIGINT, [$this, "cleanExit"]); // NOTE: we CAN'T put this in the constructor.

		$this->queueId = rand();
		self::$registeredQueues []= $this->queueId; // keep track.

		$this->pids = [];
		$this->results = [];
		$this->queue = msg_get_queue($this->queueId);

		$nFunctions = count($functions);

		if($poolSize === NULL) {
			$poolSize = $nFunctions;
		}

		// Close all MySQL and cache connections (avoid side effects)
		// They will be automatically reopened in children
		ModuleModel::dbClean();
		MemCacheCache::closeClients();

		$active = 0;
		$i = 0; // function position in the $functions array. used to sort the results.
		while($functions !== []) {

			if($poolSize === $active) { // pool's closed, wait for a process to finish
				$this->removeOne();
				$active--;
			}

			$function = array_shift($functions);
			list($fun, $args) = $function;

			$pid = pcntl_fork();
			switch($pid) {

				case -1: // uh-oh, FAIL.
					array_unshift($functions, $function); // put it back.
					sleep(1);	// delay a bit before retrying
					continue 2;

				case 0: // in the child

					$return = $fun(...$args);

					msg_send($this->queue, 1, [$i, posix_getpid(), $return], true);
					exit(0); // becomes a Zombie. The parent will ack its death.

				default: // in the parent
					self::$spawnedPids []= $pid;
					$active++;
			}

			$i++;
		}

		while(count($this->pids) !== $nFunctions) {
			$this->removeOne(); // some aren't finished yet, wait for them.
		}
		msg_remove_queue($this->queue);

		foreach($this->pids as $pid) { // we have to acknowledge their deaths.
			$status = NULL;
			pcntl_wait($status);
		}

		ksort($this->results);	// reorders based on the position in $functions()
		return $this->results;
	}

	/**
	 * Splits a dataset into N parts and group the elements with the same property in the same group. Note: this _DOES NOT_
	 * preserve the order of the original dataset; everything is scattered across the resulting array.
	 *
	 * For instance, split 1000 horses in 10 different blocks while making sure all horses with the same "race" field are in
	 * the same block: $c = list of horses, $n = 10, $key = 'race'. This means that the resulting array is _not_ balanced.
	 *
	 * This can be used to prepare a parallel run, while removing the constraint of shared data.
	 *
	 * @param $c A Collection to split across several blocks
	 * @param $n The number of blocks to generate (> 0)
	 * @param $key The key in each element on which to discriminate the items
	 *
	 * @return an array of $n Collections
	 */
	public function blockSplit(\Collection $c, array $n, $key): array {

		$ret = array_fill(0, $n, []);

		foreach($c as $e) {
			$target = crc32(serialize($e[$key])) % $n; // select the target block using crc `mod` n
			$ret[$target][] = $e;
		}
		return $ret;
	}
}
?>
