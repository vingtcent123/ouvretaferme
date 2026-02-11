<?php
namespace util;

/**
 * Common methods for text
 *
 */
class AmountUi {

	public static function fromIncluding(float $including, float $vatRate): float {
		return round($including / (1 + $vatRate / 100), 2);
	}

	public static function vatFromIncluding(float $including, float $vatRate): float {
		return round($including - self::fromIncluding($including, $vatRate), 2);
	}

	public static function fromExcluding(float $excluding, float $vatRate): float {
		return round($excluding * (1 + $vatRate / 100), 2);
	}

	public static function vatFromExcluding(float $excluding, float $vatRate): float {
		return round(self::fromExcluding($excluding, $vatRate) - $excluding, 2);
	}

}
?>
