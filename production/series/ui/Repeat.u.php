<?php
namespace series;

class RepeatUi {

	public static function getSequence(Repeat $e): string {

		$e->expects(['frequency', 'stop']);

		$frequency = self::p('frequency')->values[$e['frequency']];

		if($e['stop'] !== NULL) {
			$repeated = s("{frequency} jusqu'en semaine {week}, {year}", ['frequency' => $frequency, 'week' => week_number($e['stop']), 'year' => week_year($e['stop'])]);
		} else {
			$repeated = $frequency;
		}

		return $repeated;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Repeat::model()->describer($property);

		switch($property) {

			case 'frequency' :
				$d->values = [
					Repeat::W1 => s("Chaque semaine"),
					Repeat::W2 => s("Toutes les 2 semaines"),
					Repeat::W3 => s("Toutes les 3 semaines"),
					Repeat::W4 => s("Toutes les 4 semaines"),
					Repeat::M1 => s("Chaque mois"),
				];
				$d->attributes['mandatory'] = TRUE;
				break;

			case 'stop' :
				$d->after = \util\FormUi::info(s("Laisser vide si vous ne souhaitez pas mettre de limite de temps."));
				break;

		}

		return $d;

	}


}
?>
