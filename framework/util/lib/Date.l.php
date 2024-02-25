<?php
namespace util;


/**
 * Translated dates handling
 */
class DateLib {

	const DATE = 'date';

	/**
	 * Compare two dates
	 *
	 * @param mixed $date1
	 * @param mixed $date2
	 * @return int 0 if the dates are equal, < 0 if $date1 is the smallest or > 0 if $date1 is the biggest
	 */
	public static function compare($date1, $date2): int {

		if(is_int($date1)) {
			$compare1 = date('Y-m-d H:i:s', $date1);
		} else {
			$date = new \DateTime($date1);
			$compare1 = $date->format('Y-m-d H:i:s');
		}

		if(is_int($date2)) {
			$compare2 = date('Y-m-d H:i:s', $date2);
		} else {
			$date = new \DateTime($date2);
			$compare2 = $date->format('Y-m-d H:i:s');
		}

		return strcmp($compare1, $compare2);

	}

	/**
	 * Get a timestamp from a YYYY-MM-DD HH:MM:SS
	 *
	 * @param string $date The date
	 */
	public static function timestamp(string $date): string {

		if(ctype_digit($date)) {
			return (int)$date;
		}

		if(strlen($date) === 10) {
			$date .= ' 00:00:00';
		}

		return mktime(
			(int)substr($date, 11, 2),
			(int)substr($date, 14, 2),
			(int)substr($date, 17, 2),
			(int)substr($date, 5, 2),
			(int)substr($date, 8, 2),
			(int)substr($date, 0, 4)
		);

	}

	/**
	 * Checks if a string match YYYY-MM-DD date
	 *
	 * @param string $date The date
	 * @param string $format Date format
	 */
	public static function isValid(string $date): string {

		return checkdate(substr($date, 5, 2), substr($date, 8, 2), substr($date, 0, 4));

	}

	/**
	 * Return interval timestamp between two date
	 * @param string $maxDate date/datetime/time
	 * @param string $minDate date/datetime/time
	 */
	public static function interval(string $maxDate, string $minDate): string {

		$maxDate = self::timestamp($maxDate);
		$minDate = self::timestamp($minDate);

		return ($maxDate - $minDate);

	}

	/**
	 * Convert weeks to months
	 */
	public static function convertWeeksToMonths(array $weeks, bool $mostDays = TRUE) {

		$months = [];

		foreach($weeks as $week) {

			$firstDate = strtotime($week);

			if($mostDays === FALSE or date('t', $firstDate) - date('d', $firstDate) >= 3) {
				$months[] = date('Y-m', $firstDate);
			}

			$lastDate = strtotime($week.' + 6 DAY');

			if($mostDays === FALSE or (int)date('d', $lastDate) > 3) {
				$months[] = date('Y-m', $lastDate);
			}

		}

		$months = array_unique($months);
		$months = array_merge($months);

		return $months;

	}

	/**
	 * Convert months to weeks
	 */
	public static function convertMonthsToWeeks(array $months, bool $mostDays = TRUE) {

		$weeks = [];

		foreach($months as $month) {

			$firstDay = (int)date('d', strtotime('first monday of '.$month));
			$firstWeek = $month.'-'.sprintf('%02d', $firstDay);
			$firstMonday = strtotime($firstWeek);

			if($mostDays) {
				if($firstDay > 4) {
					$firstMonday = strtotime($firstWeek.' - 1 week');
				}
			} else {
				if($firstDay !== 1) {
					$firstMonday = strtotime($firstWeek.' - 1 week');
				}
			}

			if($mostDays) {
				$lastDate = strtotime(date('Y-m-d', strtotime('last day of '.$month)).' - 3 days');
			} else {
				$lastDate = strtotime(date('Y-m-d', strtotime('last day of '.$month)));
			}

			for($monday = $firstMonday; $monday <= $lastDate; $monday = strtotime(date('Y-m-d', $monday).' + 1 week')) {
				$weeks[] = date('o-\WW', $monday);
			}

		}

		$weeks = array_unique($weeks);
		$weeks = array_merge($weeks);

		return $weeks;

	}

	/**
	 * return age at today date
	 * if $dateRef != null compute age at $dateRef date
	 * @author julien delsescaux
	 * @return int
	 */
	public static function getAge(string $date, string $dateRef = NULL): int {

		$month = (int)substr($date, 5, 2);
		$day = (int)substr($date, 8, 2);
		$year = (int)substr($date, 0, 4);

		if($dateRef === NULL) {
			$d = (int)date('d') - $day;
			$m = (int)date('m') - $month;
			$y = (int)date('Y') - $year;
		} else {
			$d = (int)substr($dateRef, 8, 2) - $day;
			$m = (int)substr($dateRef, 5, 2) - $month;
			$y = (int)substr($dateRef, 0, 4) - $year;
		}
		return $y + max(-1, min(0, $m * 40 + $d));
	}

	/**
	 * Convert date to new timezone
	 *
	 * @param String $date Date string
	 * @param String $timezoneFrom Current timezone of the date
	 * @param String $timezoneTo Timezone wish
	 * @throws Exception : when timezone given is incorrect
	 * @see DateTime
	 * @see DateTimeZone
	 * @return String Date in the new timezone
	 */
	public static function convertTimezone(string $date, string $timezoneFrom, string $timezoneTo): string {

		if(
			isTimeZone($timezoneTo) and
			(isTimeZone($timezoneFrom) or $timezoneFrom === NULL)
		) {

			$datetime = new \DateTime($date, $timezoneFrom ? new \DateTimeZone($timezoneFrom) : NULL);
			$datetime->setTimezone(new \DateTimeZone($timezoneTo));

			return $datetime->format('Y-m-d H:i:s');
		} else {
			throw new \Exception('Timezone incorrect');
		}
	}

	/**
	 * Return offset between two time-zone in seconds
	 *
	 * @param string $fromTZ TimeZone from wich to convert
	 * @param string $toTZ TimeZone to convert
	 * @return int Offset in seconds between the two time-zone
	 * @example getTimeZoneOffset('Europe/Paris', 'America/Guatemala') ===> return 28800
	 */
	public static function getTimeZoneOffset(string $fromTZ, string $toTZ = NULL): int {

		if($toTZ === NULL) {
			if(!is_string($toTZ = date_default_timezone_get())) {
				return FALSE;
			}
		}

		$toDTZ = new DateTimeZone($toTZ);
		$fromDTZ = new DateTimeZone($fromTZ);

		$datetime = new DateTime("now", $fromDTZ);

		return $fromDTZ->getOffset($datetime) - $toDTZ->getOffset($datetime);

	}

}
?>
