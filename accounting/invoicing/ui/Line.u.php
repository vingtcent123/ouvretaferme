<?php
namespace invoicing;

Class LineUi {

	public function list(\Collection $cLine): string {

		if($cLine->empty()) {
			return '';
		}

		$h = '<h3>';
			$h .= s("Articles").'  <span class="util-badge bg-primary">'.$cLine->count().'</span>';
		$h .= '</h3>';

		$h .= '<div class="stick-xs">';

			$h .= '<table class="tr-even">';

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th>'.s("Référence").'</th>';
						$h .= '<th>'.s("Désignation").'</th>';
						$h .= '<th class="text-end">'.s("Quantité").'</th>';
						$h .= '<th class="text-end">'.s("Prix unitaire").'</th>';
						$h .= '<th class="text-end">'.s("Montant").' <span class="util-annotation">'.s("HT").'</span></th>';
						$h .= '<th class="text-center">'.s("TVA").'</th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';
					foreach($cLine as $eLine) {
					$h .= '<tr>';
						$h .= '<td>'.encode($eLine['identifier']).'</td>';
						$h .= '<td>'.encode($eLine['name']).'</td>';
						$h .= '<td class="text-end">'.encode(str_replace('.', ',', $eLine['quantity'])).'</td>';
						$h .= '<td class="text-end">'.\util\TextUi::money($eLine['unitPrice']).'</td>';
						$h .= '<td class="text-end">'.\util\TextUi::money($eLine['price']).'</td>';
						$h .= '<td class="text-center">'.s('{value} %', $eLine['vatRate']).'</td>';
					$h .= '</tr>';

					}
				$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}
}
