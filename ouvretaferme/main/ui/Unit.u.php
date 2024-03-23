<?php
namespace main;

class UnitUi {

	public static function getValue(string $value, ?string $unit, bool $short = FALSE, bool $noWrap = TRUE): string {

		switch($unit) {

			case 'kg' :
				$text = s("{value} kg", $value);
				break;

			case 'gram' :
				$text = s("{value} g", $value);
				break;

			case 'gram-100' :
				$text = s("{value} x 100 g", $value);
				break;

			case 'gram-250' :
				$text = s("{value} x 250 g", $value);
				break;

			case 'gram-500' :
				$text = s("{value} x 500 g", $value);
				break;

			case 'bunch' :
				$text = $short ? s("{value} bte", $value) : p("{value} botte", "{value} bottes", $value, ['value' => $value]);
				break;

			case 'unit' :
				$text = $short ? s("{value} p.", $value) : p("{value} pièce", "{value} pièces", $value, ['value' => $value]);
				break;

			case 'plant' :
				$text = $short ? s("{value} p.", $value) : p("{value} plant", "{value} plants", $value, ['value' => $value]);
				break;

			default :
				$text = $value;
				break;

		}

		return $noWrap ? str_replace(' ', ' ', $text) : $text;

	}

	public static function getNeutral(?string $unit, bool $short = FALSE): string {

		return match($unit) {
			'kg' => s("kg"),
			'gram' => s("g"),
			'gram-100' => s("100 g"),
			'gram-250' => s("250 g"),
			'gram-500' => s("500 g"),
			'bunch' => $short ? s("bte(s)") : s("botte(s)"),
			'unit' => $short ? s("p.") : s("pièce(s)"),
			'plant' => $short ? s("p.") : s("plant(s)"),
			default => $short ? s("u") : s("unité(s)")
		};

	}

	public static function getSingular(?string $unit, bool $short = FALSE, bool $by = FALSE, bool $noWrap = TRUE): string {

		$text = match($unit) {
			'kg' => s("kg"),
			'gram' => s("g"),
			'gram-100' => $by ? s("100 g") : s("x 100 g"),
			'gram-250' => $by ? s("250 g") : s("x 250 g"),
			'gram-500' => $by ? s("500 g") : s("x 500 g"),
			'bunch' => $short ? s("bte") : s("botte"),
			'unit' => $short ? s("p.") : s("pièce"),
			'plant' => $short ? s("p.") : s("plant"),
			default => $short ? s("u") : s("unité")
		};

		return $noWrap ? str_replace(' ', '&nbsp;', $text) : $text;

	}

	public static function getList($noWrap = TRUE): array {

		return [
			'kg' => self::getSingular('kg', noWrap: $noWrap),
			'gram' => self::getSingular('gram', noWrap: $noWrap),
			'gram-100' => self::getSingular('gram-100', noWrap: $noWrap),
			'gram-250' => self::getSingular('gram-250', noWrap: $noWrap),
			'gram-500' => self::getSingular('gram-500', noWrap: $noWrap),
			'bunch' => self::getSingular('bunch', noWrap: $noWrap),
			'unit' => self::getSingular('unit', noWrap: $noWrap),
			'plant' => self::getSingular('plant', noWrap: $noWrap),
		];

	}

	public static function getBasicList($noWrap = TRUE): array {

		return [
			'kg' => self::getSingular('kg', noWrap: $noWrap),
			'bunch' => self::getSingular('bunch', noWrap: $noWrap),
			'unit' => self::getSingular('unit', noWrap: $noWrap),
		];

	}

	public static function getLightList(): array {

		return [
			'kg' => self::getSingular('kg'),
			'bunch' => self::getSingular('bunch'),
			'unit' => self::getSingular('unit'),
		];

	}

}
?>
