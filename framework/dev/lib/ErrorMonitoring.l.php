<?php
namespace dev;

/**
 * Monitor errors
 */
class ErrorMonitoringLib {

	public static function export(): \Collection {

		$cError = Error::model()
			->select(Error::model()->getProperties())
			->whereExported(FALSE)
			->sort('createdAt')
			->getCollection(0, 1000);

		if($cError->notEmpty()) {

			Error::model()
				->whereId('IN', $cError)
				->update([
					'exported' => TRUE
				]);

		}

		foreach($cError as $key => $eError) {
			unset($cError[$key]['id']);
		}

		return $cError;

	}

	/**
	 * Returns information about fatal errors or exceptions
	 *
	 * @return array
	 */
	public static function getCrash(): array {

		$result = [];

		// Get statistics for the last 5 minutes / 1 hour / 1 day
		if(self::getCrashModule()
			->select([
				'minute5' => new \Sql('SUM(IF(createdAt > SUBDATE(NOW(), INTERVAL 5 MINUTE), 1, 0))', 'int'),
				'hour' => new \Sql('SUM(IF(createdAt > SUBDATE(NOW(), INTERVAL 1 HOUR), 1, 0))', 'int'),
				'day' => new \Sql('COUNT(*)', 'int'),
			])
			->get($result) === FALSE) {

			$result = [
				'minute5' => 0,
				'hour' => 0,
				'day' => 0
			];

		}

		// Get the last 250 errors grouped by message and request
		$cError = self::getCrashModule()
			->select([
				'message', 'request',
				'minute5' => new \Sql('SUM(IF(createdAt > SUBDATE(NOW(), INTERVAL 5 MINUTE), 1, 0))', 'int'),
				'hour' => new \Sql('SUM(IF(createdAt > SUBDATE(NOW(), INTERVAL 1 HOUR), 1, 0))', 'int'),
				'day' => new \Sql('COUNT(*)', 'int'),
				'hasClose' => new \Sql("SUM(IF(status='close', 1, 0))"),
			])
			->group(['message', 'request'])
			->having('hasClose = 0 ')
			->sort(['id' => SORT_DESC])
			->getCollection(0, 250);

		$result['file'] = [];

		foreach($cError as $eError) {
			if($eError['hour'] > 0) {
				$result['file']['errorsHour'][] = [
					'message' => $eError['message'],
					'request' => $eError['request'],
					'number' => $eError['hour']
				];
			}
			if($eError['day'] > 0) {
				$result['file']['errorsDay'][] = [
					'message' => $eError['message'],
					'request' => $eError['request'],
					'number' => $eError['day']
				];
			}
			if($eError['minute5'] > 0) {
				$result['file']['errorsMinute5'][] = [
					'message' => $eError['message'],
					'request' => $eError['request'],
					'number' => $eError['day']
				];
			}
		}

		// Get the broken tables since the last hour
		$cError = Error::model()
			->select([
				'table',
				'errors' => new \Sql('COUNT(*)', 'int'),
			])
			->whereType(Error::EXCEPTION)
			->where("
				message LIKE '%is marked as crashed%' OR
				message LIKE '%Incorrect key file for table%' OR
				message LIKE '%Got error 134 from storage engine%'
			")
			->where('createdAt > NOW() - INTERVAL 1 HOUR')
			->whereStatus(Error::OPEN)
			->group('table')
			->getCollection();

		$result['table'] = $cError;

		return $result;

	}

	private static function getCrashModule(): \ModuleModel {

		return Error::model()
			->whereType('IN', [Error::PHP, Error::EXCEPTION, Error::NGINX])
			->where("type = '".Error::EXCEPTION."' OR code = 'fatal' OR message LIKE '%Fatal error%'")
			->where("code != 9991045")
			->where('createdAt > NOW() - INTERVAL 1 DAY')
			->where('
				message NOT LIKE "%SQLSTATE%Connection timed out%" AND
				message NOT LIKE "Access denied for user%" AND
				message NOT LIKE "%Unknown table engine%" AND
				message NOT LIKE "%Lost connection to MySQL%" AND
				message NOT LIKE "%Can\'t connect to MySQL%" AND
				message NOT LIKE "Unable to connect to%"
			')
			->whereStatus(Error::OPEN);

	}

	/**
	 * Returns timeout errors
	 *
	 * @return array
	 */
	public static function getTimeout(): array {

		$result = [];

		if(Error::model()
			->select([
				'message',
				'timeoutMinute5' => new \Sql('SUM(IF(createdAt > SUBDATE(NOW(), INTERVAL 5 MINUTE), 1, 0))', 'int'),
				'timeoutHour' => new \Sql('SUM(IF(createdAt > SUBDATE(NOW(), INTERVAL 1 HOUR), 1, 0))', 'int'),
				'timeoutDay' => new \Sql('COUNT(*)', 'int'),
			])
			->whereType(Error::EXCEPTION)
			->where("message LIKE 'Unable to connect to%'")
			->where('createdAt > NOW() - INTERVAL 1 DAY')
			->whereStatus(Error::OPEN)
			->get($result) === FALSE) {

			$result = [
				'timeoutMinute5' => 0,
				'timeoutHour' => 0,
				'timeoutDay' => 0
			];

		}

		return $result;

	}

	/**
	 * Returns MySQL errors
	 */
	public static function getMySQL(): array {

		$result = [];

		if(Error::model()
			->select([
				'mysqlHour' => new \Sql('SUM(IF(createdAt > SUBDATE(NOW(), INTERVAL 1 HOUR), 1, 0))', 'int'),
				'mysqlDay' => new \Sql('COUNT(*)', 'int'),
			])
			->where("
				message LIKE '%SQLSTATE%Connection timed out%' OR
				message LIKE '%Lost connection to MySQL%' OR
				message LIKE '%Can\'t connect to MySQL%' OR
				message LIKE '%Unknown table engine%'
			")
			->where('createdAt > NOW() - INTERVAL 1 DAY')
			->whereStatus(Error::OPEN)
			->get($result) === FALSE) {

			$result = [
				'mysqlHour' => 0,
				'mysqlDay' => 0,
			];

		}

		return $result;

	}

	/**
	 * Returns Nginx errors
	 *
	 * @return array
	 */
	public static function getNginx(): array {

		$result = [];

		$result['nginxTooLarge'] = Error::model()
			->whereType(Error::PHP)
			->whereMessage('LIKE', 'Log file % is too big %')
			->where('createdAt > NOW() - INTERVAL 1 HOUR')
			->whereStatus(Error::OPEN)
			->count();

		return $result;

	}

	/**
	 * Close an error
	 */
	public static function close(Error $eError) {

		$eError->merge([
			'status' => Error::CLOSE,
			'statusUpdatedAt' => new \Sql('NOW()')
		]);

		Error::model()
			->select('status', 'statusUpdatedAt')
			->update($eError);

	}

	/**
	 * Close errors
	 */
	public static function closeByMessage($message): int {

		return Error::model()
			->whereMessage('LIKE', '%'.$message.'%')
			->update([
				'status' => Error::CLOSE,
				'statusUpdatedAt' => new \Sql('NOW()')
			]);

	}

	/**
	 * Get an error by ID
	 */
	public static function getById(mixed $id): \Element {

		return Error::model()
			->select(self::getSelection())
			->whereId($id)
			->get();

	}

	/**
	 * Get last errors
	 *
	 */
	public static function getLast(int $offset, int $limit, \Search $search): array {

		if($search->get('message')) {
			Error::model()->whereMessage('LIKE', '%'.$search->get('message').'%');
		}

		if($search->get('unexpected') === FALSE) {
			Error::model()->whereType('!=', Error::UNEXPECTED);
		}

		if($search->get('user')->notEmpty()) {
			Error::model()->whereUser($search->get('user'));
		}

		switch($search->get('type')) {
			case 'web' :
				Error::model()->whereType('NOT IN', [Error::ANDROID, Error::IOS]);
				break;
			case 'app' :
				Error::model()->whereType('IN', [Error::ANDROID, Error::IOS]);
				break;
		}

		$cError = Error::model()
			->select(self::getSelection())
			->option('count')
			->where('
				(status = \''.Error::OPEN.'\') OR
				(status = \''.Error::CLOSE.'\' AND statusUpdatedAt > NOW() - INTERVAL 30 DAY)
			')
			->sort([
				'status' => SORT_ASC,
				'id' => SORT_DESC
			])
			->getCollection($offset, $limit, 'id');

		return [$cError, Error::model()->found()];

	}

	/**
	 * Get all errors
	 */
	public static function getAll(string $startDate, string $lastDate, int $offset, int $limit): \Collection {

		$cError = Error::model()
			->select(self::getSelection())
			->where('createdAt > "'.$startDate.'" and createdAt <= "'.$lastDate.'"')
			->sort('id')
			->getCollection($offset, $limit, 'id');

		return $cError;

	}

	private static function getSelection(): array {

		return [
			'id', 'message', 'code', 'app', 'file', 'line', 'mode', 'modeVersion', 'method', 'type', 'request', 'createdAt', 'status', 'browser', 'table', 'server', 'tag', 'device', 'referer', 'deprecated',
			'user',
			'cTrace' => ErrorTrace::model()
				->select('id', 'file', 'line', 'class', 'function', 'arguments')
				->delegateCollection('error'),
			'cParameter' => ErrorParameter::model()
				->select('id', 'type', 'name', 'value')
				->delegateCollection('error'),
		];

	}

}
?>
