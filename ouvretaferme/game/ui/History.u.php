<?php
namespace game;

class HistoryUi {

	public static function getMessage(string $history, array $values = []): string {

		return match($history) {
			'seedling' => s("Vous avez semé des {value}", $values[0]),
			'watering' => s("Vous avez arrosé des {value}", $values[0]),
			'weeding' => s("Vous avez désherbé des {value}", $values[0]),
		};

	}

	public static function getFoodMessage(string $history, \Collection $cGrowing, array $values): string {

		return match($history) {
			'harvesting' => ($values[0] > 0) ?
				s("Vous avez récolté {value}", ['value' => '<b>'.$values[0].'</b>  '.GrowingUi::getVignette($cGrowing->first(), '1rem')]) :
				s("Vous avez consommé {value}", ['value' => '<b>'.$values[0].'</b>  '.GrowingUi::getVignette($cGrowing->first(), '1rem')]),
			'soup-eat' => p("Vous avez mangé {value} soupe", "Vous avez mangé {value} soupes", -1 * last($values)),
			'soup-cook' => p("Vous avez cuisiné {value} soupe avec vos légumes", "Vous avez cuisiné {value} soupes avec vos légumes", last($values)),
		};

	}

	public static function display(\Collection $cHistory): string {

		if($cHistory->empty()) {
			return '';
		}

		$h = '<div class="game-intro">';
			$h .= '<h3>'.s("Derniers événements").'</h3>';
			$h .= '<table class="game-table tr-bordered">';
				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th class="td-min-content">'.s("Date").'</th>';
						$h .= '<th>'.s("Message").'</th>';
						$h .= '<th class="text-end">'.s("Temps de travail").'</th>';
					$h .= '</tr>';
				$h .= '</thead>';
				$h .= '<tbody>';

					foreach($cHistory as $eHistory) {

						$h .= '<tr>';
							$h .= '<td class="td-min-content">'.\util\DateUi::numeric($eHistory['createdAt'], \util\DateUi::DATE_HOUR_MINUTE).'</td>';
							$h .= '<td>';
								$h .= $eHistory['message'];
							$h .= '</td>';
							$h .= '<td class="text-end">';
								if($eHistory['time'] !== NULL) {
									$h .= PlayerUi::getTime($eHistory['time']);
								} else {
									$h .= '-';
								}
							$h .= '</td>';
						$h .= '</tr>';
					}
				$h .= '</tbody>';
			$h .= '</table>';
		$h .= '</div>';

		return $h;

	}

}
?>
