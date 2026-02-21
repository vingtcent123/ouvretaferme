<?php
namespace invoicing;

Class EventUi {

	public function list(\Collection $cEvent): string {

		if($cEvent->empty()) {
			return '';
		}


		$h = '<h3>'.s("Historique").'</h3>';

		$h .= '<div class="util-overflow-sm stick-xs">';

			$h .= '<table>';

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th>'.s("Date").'</th>';
						$h .= '<th>'.s("Code").'</th>';
						$h .= '<th>'.s("Événement").'</th>';
					$h .= '</tr>';
				$h .= '</thead>';
				$h .= '<tbody>';

					foreach($cEvent as $eEvent) {

						$h .= '<tr>';

							$h .= '<td>';
								$h .= \util\DateUi::numeric($eEvent['createdAt']);
							$h .= '</td>';

							$h .= '<td>';
								$h .= $eEvent['statusCode'];
							$h .= '</td>';

							$h .= '<td>';
								$h .= encode($eEvent['statusText']);
							$h .= '</td>';

						$h .= '</tr>';

					}

				$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</div>';

		return $h;
	}

}
