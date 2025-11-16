<?php
namespace game;

class HistoryUi {

	public static function getMessageProduction(Growing $eGrowing, int $value): string {
		return s("Vous avez produit {value}", ['value' => '<b>'.$value.'</b>  '.GrowingUi::getVignette($eGrowing, '1rem')]);
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
