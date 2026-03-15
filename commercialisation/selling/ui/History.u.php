<?php
namespace selling;

class HistoryUi {

	public function getList(Sale|Invoice $eElement, \Collection $cHistory) {

		if(
			($eElement instanceof Sale and $eElement->isComposition()) or
			$cHistory->empty()
		) {
			return '';
		}

		$h = '<h3>'.s("Historique").'</h3>';

		$h .= '<div class="util-overflow-sm stick-xs">';

			$h .= '<table>';

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th>'.s("Date").'</th>';
						$h .= '<th>'.s("Événement").'</th>';
						$h .= '<th>'.s("Par").'</th>';
					$h .= '</tr>';
				$h .= '</thead>';
				$h .= '<tbody>';

					foreach($cHistory as $eHistory) {

						$h .= '<tr>';

							$h .= '<td class="td-min-content">';
								$h .= \util\DateUi::numeric($eHistory['date']);
							$h .= '</td>';

							$h .= '<td>';
								$h .= $eHistory['event']['name'];
								if($eHistory['comment']) {
									$h .= '<div class="util-annotation color-muted">';
										$h .= encode($eHistory['comment']);
									$h .= '</div>';
								}
							$h .= '</td>';

							$h .= '<td>';
								$h .= $eHistory['user']->empty() ? '-' : $eHistory['user']->getName();
							$h .= '</td>';

						$h .= '</tr>';

					}

				$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

}
