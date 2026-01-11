<?php
namespace asset;

class CsvUi {

	public function getImportFile(\farm\Farm $eFarm, array $import): string {

		\Asset::css('main', 'csv.css');
		\Asset::js('main', 'csv.js');

		['import' => $assets, 'errorsCount' => $errorsCount, 'resumeDate' => $resumeDate] = $import;

		$h = '';

		if($errorsCount > 0) {

			$h .= \main\CsvUi::getGlobalErrors($errorsCount, '/doc/accounting:asset');

		} else {

			$h .= '<div class="util-block">';

				$h .= '<h4>'.s("Vos données sont prêtes à être importées").'</h4>';

				$h .= '<ul>';
					$h .= '<li>'.s("Les immobilisations présentes dans le tableau ci-dessous seront créées et associées à votre ferme").'</li>';
					$h .= '<li>'.s("Il est encore temps de faire des modifications dans votre fichier CSV si vous n'êtes pas totalement satisfait de la version actuelle").'</li>';
					$h .= '<li>'.s("Si vous changez d'avis, vous pourrez toujours supprimer ultérieurement les immobilisations que vous importez maintenant").'</li>';
					$h .= '<li>'.s("La reprise de ces amortissements sera comptabilisée à partir du {value}", \util\DateUi::numeric($resumeDate)).'</li>';
				$h .= '</ul>';

				$h .= '<a data-url="'.\company\CompanyUi::urlAsset($eFarm).'/csv:doCreateAssets" post-id="'.$eFarm['id'].'" class="btn btn-secondary" data-confirm-text="'.p("Importer maintenant {value} immobilisation ?", "Importer maintenant {value} immobilisation ?", count($assets)).'" onclick="Csv.import(this)" data-waiter="'.s("Importation en cours, merci de patienter...").'">'.s("Importer maintenant").'</a>';
			$h .= '</div>';

		}

		$h .= '<div class="util-overflow-lg">';

			$h .= '<table class="tr-even tr-hover">';

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th>'.s("Immobilisation").'</th>';
						$h .= '<th>'.s("Numéro de compte").'</th>';
						$h .= '<th class="text-end">'.s("Valeur").'</th>';
						$h .= '<th class="text-end">'.s("Valeur résiduelle").'</th>';
						$h .= '<th class="text-center">'.s("Date d'acquisition").'</th>';
						$h .= '<th class="text-center">'.s("Date de mise en service").'</th>';
						$h .= '<th class="text-center">'.s("Mode d'amortissement<br />Éco/Fiscal").'</th>';
						$h .= '<th class="text-center">'.s("Durée d'amortissement<br />Éco/Fiscal (en mois)").'</th>';
						$h .= '<th class="text-center">'.s("Montant déjà amorti").'</th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

					foreach($assets as $asset) {

						$h .= '<tr class="'.($asset['errors'] ? 'csv-error' : '').'">';

							$h .= '<td>';
								$h .= encode($asset['description']);
							$h .= '</td>';

							$h .= '<td>';

								if(in_array('accountId', $asset['errors'])) {
									$h .= $this->displayDanger($asset['account']);
								} else {
									$h .= encode($asset['account']);
								}

							$h .= '</td>';

							$h .= '<td class="text-end">';
								$h .= \util\TextUi::money($asset['value']);
							$h .= '</td>';

							$h .= '<td class="text-end">';
								$h .= \util\TextUi::money($asset['residualValue']);
							$h .= '</td>';

							$h .= '<td class="text-center">';
								if(in_array('acquisitionDate', $asset['errors'])) {
									$h .= $this->displayDanger($asset['acquisitionDate']);
								} else {
									$h .= \util\DateUi::numeric($asset['acquisitionDate']);
								}
							$h .= '</td>';

							$h .= '<td class="text-center">';
								if(in_array('startDate', $asset['errors'])) {
									$h .= $this->displayDanger($asset['startDate']);
								} else {
									$h .= \util\DateUi::numeric($asset['startDate']);
								}
							$h .= '</td>';

							$h .= '<td class="text-center">';
								if(in_array('economicMode', $asset['errors'])) {
									$h .= $this->displayDanger(match($asset['economicMode']) {
										AssetElement::LINEAR => s("L"),
										AssetElement::DEGRESSIVE => s("D"),
										AssetElement::WITHOUT => s("S"),
										default => '?'
									});
								} else {
									$h .= match($asset['economicMode']) {
										AssetElement::LINEAR => s("L"),
										AssetElement::DEGRESSIVE => s("D"),
										AssetElement::WITHOUT => s("S"),
									};
								}
								$h .= '/';
								if(in_array('fiscalMode', $asset['errors'])) {
									$h .= $this->displayDanger(match($asset['fiscalMode']) {
										AssetElement::LINEAR => s("L"),
										AssetElement::DEGRESSIVE => s("D"),
										AssetElement::WITHOUT => s("S"),
										default => '?'
									});
								} else {
									$h .= match($asset['fiscalMode']) {
										AssetElement::LINEAR => s("L"),
										AssetElement::DEGRESSIVE => s("D"),
										AssetElement::WITHOUT => s("S"),
									};
								}
							$h .= '</td>';

							$h .= '<td class="text-center">';
								$h .= encode($asset['economicDuration']).'/'.encode($asset['fiscalDuration']);
							$h .= '</td>';


							$h .= '<td class="text-end">';
								if(in_array('economicAmortization', $asset['errors'])) {
									$h .= $this->displayDanger(\util\TextUi::money($asset['economicAmortization']));
								} else {
									$h .= \util\TextUi::money($asset['economicAmortization']);
								}
							$h .= '</td>';

						$h .= '</tr>';

					}

				$h .= '</tbody>';
			$h .= '</table>';

		$h .= '</div>';

		if($errorsCount > 0) {

			$messages = [
				'accountId' => s("Un <b>numéro de compte</b> n'a pas été retrouvé. Vous pouvez le créer dans les <link>paramétrages des comptes</link>.", ['link' => '<a href="'.\company\CompanyUi::urlAccount($eFarm).'/account">']),
				'residualValue' => s("<b>Valeur résiduelle</b> : Le montant n'est pas cohérent avec la valeur d'acquisition"),
				'acquisitionDate' => s("<b>Date d'acquisition</b> : La date n'est pas reconnue"),
				'startDate' => s("<b>Date de mise en service</b> : La date n'est pas reconnue ou incohérence avec la date d'acquisition"),
				'economicMode' => s("<b>Mode d'amortissement économique</b> : La valeur n'est pas reconnue"),
				'fiscalMode' => s("<b>Mode d'amortissement fiscal</b> : La valeur n'est pas reconnue"),
				'economicDuration' => s("<b>Durée d'amortissement économique</b> : La valeur n'est pas reconnue"),
				'fiscalDuration' => s("<b>Durée d'amortissement fiscal</b> : La valeur n'est pas reconnue"),
				'economicAmortization' => s("<b>Montant déjà amorti</b> : Le montant n'est pas cohérent avec la valeur d'acquisition"),
			];

			foreach($messages as $key => $text) {

				$field = (array_find(array_column($assets, 'errors'), fn($errors) => in_array($key, $errors)));
				if($field !== NULL and count($field) > 0) {
					$h .= '<p>'.$text.'</p>';
				}
			}
		}

		return $h;

	}

	public function displayDanger(mixed $text): string {

		return '<span class="color-danger">'.encode($text).'</span>';

	}

}
