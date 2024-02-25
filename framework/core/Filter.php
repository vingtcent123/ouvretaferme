<?php


/**
 * Filter : Variable manager
 */
class Filter {

	/**
	 * Max value of a 8-bits signed int
	 *
	 */
	const MAX_INT8 = "127";

	/**
	 * Max value of a 16-bits signed int
	 *
	 */
	const MAX_INT16 = "32767";

	/**
	 * Max value of a 16-bits signed int
	 *
	 */
	const MAX_INT24 = "8388607";

	/**
	 * Max value of a 32-bits signed int
	 *
	 */
	const MAX_INT32 = "2147483647";

	/**
	 * Max value of a 64-bits signed int
	 *
	 */
	const MAX_INT64 = "9223372036854775807";

	/**
	 * Min value of a 8-bits signed int
	 *
	 */
	const MIN_INT8 = "-128";

	/**
	 * Min value of a 16-bits signed int
	 *
	 */
	const MIN_INT16 = "-32768";

	/**
	 * Min value of a 24-bits signed int
	 *
	 */
	const MIN_INT24 = "-8388608";

	/**
	 * Min value of a 32-bits signed int
	 *
	 */
	const MIN_INT32 = "-2147483648";

	/**
	 * Min value of a 64-bits signed int
	 *
	 */
	const MIN_INT64 = "-9223372036854775807";

	/**
	 * Max value of a 8-bits unsigned int
	 *
	 */
	const MAX_INT8_UNSIGNED = "255";

	/**
	 * Max value of a 16-bits unsigned int
	 *
	 */
	const MAX_INT16_UNSIGNED = "65535";

	/**
	 * Max value of a 24-bits unsigned int
	 *
	 */
	const MAX_INT24_UNSIGNED = "16777215";

	/**
	 * Max value of a 32-bits unsigned int
	 *
	 */
	const MAX_INT32_UNSIGNED = "4294967295";

	/**
	 * Max value of a 64-bits unsigned int
	 *
	 */
	const MAX_INT64_UNSIGNED = "18446744073709551615";

	/**
	 * Max value of a 32-bits signed float
	 *
	 */
	const MAX_FLOAT32 = "999999";

	/**
	 * Max value of a 64-bits signed float
	 *
	 */
	const MAX_FLOAT64 = "999999999999999";

	/**
	 * Max value of a 32-bits signed float
	 *
	 */
	const MIN_FLOAT32 = "-999999";

	/**
	 * Max value of a 64-bits signed float
	 *
	 */
	const MIN_FLOAT64 = "-999999999999999";

	/**
	 * Max length for TEXT8 fields
	 *
	 */
	const MAX_TEXT8_SIZE = 255;

	/**
	 * Max length for TEXT16 fields
	 *
	 */
	const MAX_TEXT16_SIZE = 32767;

	/**
	 * Fully qualified name max size
	 */
	const MAX_FQN_SIZE = 255;

	/**
	 * Fully qualified name min size
	 */
	const MIN_FQN_SIZE = 1;

	/**
	 * Maximum default length for STRING values
	 */
	const MAX_STRING_SIZE = 255;

	/**
	 *  Flag to accept NULL value
	 *
	 */
	const NULL = 1;

	/**
	 *  Flag to specify that it is a unique value
	 *
	 */
	const UNIQUE = 2;

	/**
	 * Get the range of a string, float or integer structure
	 *
	 * @param array $name A name of structure
	 */
	public static function getRange(array $mask): ?array {

		$type = $mask[0];

		if(
			strpos($type, 'int') === 0 or
			strpos($type, 'float') === 0 or
			$type === 'decimal'
		) {

			$start = $mask['min'] ?? NULL;
			$stop = $mask['max'] ?? NULL;

			if($start === NULL or $stop === NULL) {

				$bits = (int)substr($type, -1);

				switch($bits) {

					case 8 :
						$start ??= (int)self::MIN_INT8;
						$stop ??= ($start < 0 ? (int)self::MAX_INT8 : (int)self::MAX_INT8_UNSIGNED);
						break;

					case /*1*/6 :
						$start ??= (int)self::MIN_INT16;
						$stop ??= ($start < 0 ? (int)self::MAX_INT16 : (int)self::MAX_INT16_UNSIGNED);
						break;

					case /*6*/4 :
						$start ??= (int)self::MIN_INT64;
						$stop ??= ($start < 0 ? (int)self::MAX_INT64 : (int)self::MAX_INT64_UNSIGNED);
						break;

					case /*3*/2 :
					default :
						$start ??= (int)self::MIN_INT32;
						$stop ??= ($start < 0 ? (int)self::MAX_INT32 : (int)self::MAX_INT32_UNSIGNED);
						break;

				}

			}

			return [$start, $stop];

		} else if(
			strpos($type, 'serial') === 0 or
			strpos($type, 'text') === 0 or
			strpos($type, 'editor') === 0 or
			strpos($type, 'binary') === 0
		) {

			$start = $mask['min'] ?? NULL;
			$stop = $mask['max'] ?? NULL;

			if($start === NULL or $stop === NULL) {

				$bits = (int)substr($type, -1);

				switch($bits) {

					case 8 :
						$start ??= 0;
						$stop ??= (int)self::MAX_INT8_UNSIGNED;
						break;

					case /*1*/6 :
						$start ??= 0;
						$stop ??= (int)self::MAX_INT16_UNSIGNED;
						break;

					case /*6*/4 :
						$start ??= 0;
						$stop ??= (int)self::MAX_INT64; // PHP does not support unsigned 64bits numbers
						break;

					case /*3*/2 :
					default :
						$start ??= 0;
						$stop ??= (int)self::MAX_INT32_UNSIGNED;
						break;

				}

			}

			return [$start, $stop];

		} else if(
			$type === 'char'
		) {

			$start = $mask['min'] ?? 0;
			$stop = $mask['max'] ?? self::MAX_INT8_UNSIGNED;

			return [$start, $stop];

		} else if($type === 'date' or $type === 'datetime' or $type === 'week' or $type === 'month' or $type === 'year') {

			$start = $mask['min'] ?? NULL;
			$stop = $mask['max'] ?? NULL;

			if($start !== NULL or $stop !== NULL) {

				switch($type) {
					case 'date' :
						$format = 'Y-m-d';
						break;
					case 'datetime' :
						$format = 'Y-m-d H:i:s';
						break;
					case 'week' :
						$format = 'o-\WW';
						break;
					case 'month' :
						$format = 'Y-m';
						break;
					case 'year' :
						$format = 'Y';
						break;
				}

				if($start !== NULL) {
					$date = new DateTime($start);
					$start = $date->format($format);
				}

				if($stop !== NULL) {
					$date = new DateTime($stop);
					$stop = $date->format($format);
				}

				return [$start, $stop];

			}

		} else if($type === 'collection') {
			return [0, (int)$mask[2]];
		}

		return NULL;

	}

	/**
	 * Check a variable with a Filter structure
	 *
	 * <code>
	 *
	 * $value = $_GET['id'];
	 *
	 * if(Filter::check("int8", $value)) {
	 * 	echo "No SQL injection!";
	 * }
	 * </code>
	 *
	 */
	public static function check(string|array $mask, mixed $value): bool {

		if(is_string($mask)) {

			$isNull = substr($mask, 0, 1) === '?';
			$type = substr($mask, $isNull ? 1 : 0);

			$mask = [
				$type,
				'null' => $isNull
			];

		} else {
			$type = $mask[0];
			$isNull = $mask['null'] ?? FALSE;
		}

		if(isset($mask['charset']) and mb_check_encoding($value, $mask['charset']) === FALSE) {
			return FALSE;
		}

		// NULL values
		if($isNull) {

			if(strpos($type, 'element') === 0) {

				if(
					$value === NULL or
					($value instanceof Element and $value->empty())
				) {
					return TRUE;
				}

			} else {

				if($value === NULL) {
					return TRUE;
				}

			}

		} else {
			if($value === NULL) {
				return FALSE;
			}
		}

		if($type === 'bool') {
			return TRUE;
		} else if(
			str_starts_with($type, 'int') or
			str_starts_with($type, 'serial') or
			str_starts_with($type, 'float') or
			$type === 'decimal' or
			str_starts_with($type, 'text') or
			str_starts_with($type, 'editor') or
			str_starts_with($type, 'json') or
			str_starts_with($type, 'binary') or
			$type === 'char'
		) {

			if(is_array($value) === TRUE) {
				if(strpos($type, 'json') === 0) { // test if the value is a json string
					json_decode(json_encode($value)); // from array to string then from string to array

					if(json_last_error() !== JSON_ERROR_NONE) {
						return FALSE;
					}
				} else {
					return FALSE;
				}
			}
			else {
				if(is_scalar($value) === FALSE or is_bool($value)) {
					return FALSE;
				}

				$value = (string)$value;

				if(str_starts_with($type, 'int')) {
					if(!preg_match("/^\-?\d+$/", $value)) {
						return FALSE;
					}
				} else if(str_starts_with($type, 'serial')) {
					if(ctype_digit($value) === FALSE) {
						return FALSE;
					}
				} else if(str_starts_with($type, 'float') or $type === 'decimal') {
					if(!preg_match("/^\-?\d+(\.\d+)?$/", $value)) {
						return FALSE;
					}
				}
			}

			$range = self::getRange($mask);

			if($range) {

				$start = $range[0];
				$stop = $range[1];

				if(str_starts_with($type, 'int') or str_starts_with($type, 'serial')) {
					$size = (int)$value;
				} else if(str_starts_with($type, 'float') or $type === 'decimal') {
					$size = (float)$value;
				} else if(str_starts_with($type, 'binary')) {
					$size = strlen($value);
				} else {
					$size = mb_strlen($value);
				}

				if(is_null($start) === FALSE and $size < $start) {
					return FALSE;
				}
				if(is_null($stop) === FALSE and $size > $stop) {
					return FALSE;
				}

			}

			return TRUE;

		} else if(strpos($type, 'element') === 0) {

			if($value instanceof Element === FALSE) {
				return FALSE;
			}

			$id = $value['id'] ?? NULL;

			if($id === NULL) {
				return FALSE;
			}

			if(
				is_scalar($id) === FALSE or ((string)(int)$id) !== (string)$id or
				bccomp((string)$id, self::getOverflowMin($type)) !== 1 or // Smaller than zero
				bccomp((string)$id, self::getOverflow($type)) !== -1 // Greater than max allowed value
			) {
				return FALSE;
			}

			return TRUE;

		} else if($type === 'enum') {

			if(in_array($value, $mask[1], TRUE)) {
				return TRUE;
			} else {
				return FALSE;
			}

		} else if($type === 'set') {

			if($value instanceof Set) {
				$value = $value->get();
			}

			if(is_scalar($value) and preg_match("/^[0-9]+$/", $value) and $value < 2 ** 32) {
				return TRUE;
			} else {
				return FALSE;
			}

		} else if($type === 'collection') {

			$listType = $mask[1];
			$range = self::getRange($mask);

			if($value instanceof Collection === FALSE) {
				return FALSE;
			} else if(count($value) > $range[1]) {
				return FALSE;
			} else {

				switch($listType) {

					case 'serial8' :
						$min = 0;
						$max = (int)self::MAX_INT8_UNSIGNED;
						break;
					case 'serial16' :
						$min = 0;
						$max = (int)self::MAX_INT16_UNSIGNED;
						break;
					case 'serial32' :
						$min = 0;
						$max = (int)self::MAX_INT32_UNSIGNED;
						break;
					case 'serial64' :
						$min = 0;
						$max = (int)self::MAX_INT64; // PHP does not support unsigned 64bits numbers
						break;

				}

				foreach($value as $e) {

					if(is_int($e['id'])) {

						if($e['id'] < $min or $e['id'] > $max) {
							return FALSE;
						}

					} else if($e['id'] !== NULL) {
						return FALSE;
					}

				}
			}

			return TRUE;

		} else if($type === 'point') {

			return (
				is_array($value) and
				count($value) === 2 and
				preg_match("/^\-?\d+(\.\d+)?$/", $value[0]) === 1 and
				preg_match("/^\-?\d+(\.\d+)?$/", $value[1]) === 1
			);

		} else if($type === 'polygon') {

			if(is_array($value) === FALSE) {
				return FALSE;
			}

			foreach($value as $point) {

				if(self::check('point', $point) === FALSE) {
					return FALSE;
				}

			}

			return TRUE;

		} else {

			$date = '[0-9]{4}\\-(0[1-9]|1[0-2])\\-(0[1-9]|[12][0-9]|3[0-1])';
			$hours = '([01][0-9]|2[0-3])';
			$minutes = '[0-5][0-9]';
			$seconds = '[0-5][0-9]';
			$time = $hours.'\\:'.$minutes.'\\:'.$seconds;

			$pcreDate = ':^'.$date.'$:s';
			$pcreDateTime = ':^('.$date.' '.$time.'|'.$date.'T'.$hours.'\\:'.$minutes.')$:s';
			$pcreTime = ':^('.$time.'|'.$hours.'\\:'.$minutes.')$:s';

			switch($type) {
				case 'date' :
					$pcre = $pcreDate;
					break;
				case 'datetime' :
					$pcre = $pcreDateTime;
					break;
				case 'year' :
					$pcre = ':^[0-9]{4}$:s';
					break;
				case 'month' :
					$pcre = ':^[0-9]{4}\\-(0[1-9]|1[0-2])$:s';
					break;
				case 'week' :
					$pcre = ':^[0-9]{4}\\-W(0[1-9]|[1-4][0-9]|50|51|52|53)$:s';
					break;
				case 'time' :
					$pcre = $pcreTime;
					break;
				case 'email' :
					$domains = '[a-z]{2,6}';
					$pcre = '#^\w[-\+.\w]*@[-a-z0-9]+(?:\.[-a-z0-9]+)*\.(?:'.$domains.')$#si';
					break;
				case 'url' :
					$pcre = ':^(https?|ftps?)\\://[0-9a-z\\-\\.]{1,'.self::MAX_TEXT16_SIZE.'}(/|$):si';
					break;
				case 'fqn' :
					$pcre = ':^[a-z0-9\\-\\.]{'.self::MIN_FQN_SIZE.','.self::MAX_FQN_SIZE.'}$:i';
					break;
				case 'ipv4' :
					$pcre = '/^[1-2]?[0-9]{1,2}(\.[1-2]?[0-9]{1,2}){3}$/i';
					break;
				case 'ipv6' :
					$pcre = '/^[a-f0-9]{4}(:[a-f0-9]{4}){7}$/i';
					break;
				case 'ip' :
					$pcre = '/^[1-2]?[0-9]{1,2}(\.[1-2]?[0-9]{1,2}){3}$|^[a-f0-9]{4}(:[a-f0-9]{4}){7}$/i';
					break;
				case 'md5' :
					$pcre = '/^[a-f0-9]{32}$/i';
					break;
				case 'sid' :
					$pcre = '/^[a-z0-9-\,]{22,40}$/i';
					break;
				case 'color' :
					$pcre = '/^\#[a-f0-9]{6}$/i';
					break;
				default :
					$pcre = NULL;
			}

			if($pcre !== NULL) {

				if(is_scalar($value) and !is_bool($value)) {

					$value = (string)$value;

					if((bool)preg_match($pcre, $value) === FALSE) {
						return FALSE;
					}

					if($type === 'date' or $type === 'datetime') {

					 	$date = substr($value, 0, 10);

				 		list($year, $month, $day) = explode('-', $date);

				 		if(!checkdate((int)$month, (int)$day, (int)$year)) {
				 			return FALSE;
				 		}

					}

					if($type === 'week') {

				 		if(strtotime($value) === FALSE) {
				 			return FALSE;
				 		}

					}

					if($type === 'date' or $type === 'datetime' or $type === 'week' or $type === 'month' or $type === 'year') {

				 		$range = self::getRange($mask);

				 		if($range) {

							[$min, $max] = $range;

							if($min !== NULL and strcmp($min, $value) > 0) {
								return FALSE;
							}

							if($max !== NULL and strcmp($max, $value) < 0) {
								return FALSE;
							}

				 		}

					}

					return TRUE;

				}

			}

		}

		return FALSE;

	}

	/**
	 * Return the max possible value of a type
	 *
	 * @param string $type
	 *
	 * @return int
	 */
	public static function getOverflow(string $type): int {
		switch($type) {
			case 'int8' :
				return self::MAX_INT8;
			case 'element8' :
				return self::MAX_INT8;
			case 'int16' :
				return self::MAX_INT16;
			case 'element16' :
				return self::MAX_INT16;
			case 'int24' :
				return self::MAX_INT24;
			case 'element24' :
				return self::MAX_INT24;
			case 'serial32' :
			case 'int32' :
			case 'int' :
				return self::MAX_INT32;
			case 'element' :
			case 'element32' :
				return self::MAX_INT32;
			case 'int64' :
			case 'serial64' :
				return self::MAX_INT64;
			case 'element64' :
				return self::MAX_INT64;
			case 'float32' :
			case 'float' :
				return self::MAX_FLOAT32;
			case 'float64' :
				return self::MAX_FLOAT64;
		}
	}

	/**
	 * Return the max possible value of a type
	 *
	 * @param string $type
	 *
	 * @return int
	 */
	public static function getOverflowMin(string $type): int {
		switch($type) {
			case 'int8' :
				return self::MIN_INT8;
			case 'element8' :
				return self::MIN_INT8;
			case 'int16' :
				return self::MIN_INT16;
			case 'element16' :
				return self::MIN_INT16;
			case 'int24' :
				return self::MIN_INT24;
			case 'element24' :
				return self::MIN_INT24;
			case 'int32' :
			case 'int' :
				return self::MIN_INT32;
			case 'element' :
			case 'element32' :
				return self::MIN_INT32;
			case 'int64' :
				return self::MIN_INT64;
			case 'element64' :
				return self::MIN_INT64;
			case 'float32' :
			case 'float' :
				return self::MIN_FLOAT32;
			case 'float64' :
				return self::MIN_FLOAT64;
		}
	}

}
?>
