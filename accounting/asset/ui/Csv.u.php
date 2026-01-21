<?php
namespace asset;

class CsvUi {

	public function getImportFile(\farm\Farm $eFarm, array $import): string {

		\Asset::css('main', 'csv.css');

		['import' => $assets, 'errorsCount' => $errorsCount, 'resumeDate' => $resumeDate] = $import;

		$messages = [
			'accountId' => s("Le <b>numéro de compte</b> n'a pas été retrouvé. Vous pouvez le créer dans les <link>paramétrages des comptes</link>.", ['link' => '<a href="'.\company\CompanyUi::urlAccount($eFarm).'/account">']),
			'residualValue' => s("La <b>valeur résiduelle</b> n'est pas cohérente avec la valeur d'acquisition"),
			'acquisitionDate' => s("La <b>date d'acquisition</b> n'est pas reconnue"),
			'acquisitionDateFuture' => s("La <b>date d'acquisition</b> doit être dans le passé"),
			'startDate' => s("La <b>date de mise en service</b> n'est pas reconnue ou incohérence avec la date d'acquisition"),
			'startDateFuture' => s("La <b>date de mise en service</b> doit être dans le passé"),
			'economicMode' => s("Le <b>mode d'amortissement économique</b> n'est pas reconnu"),
			'fiscalMode' => s("Le <b>mode d'amortissement fiscal</b> n'est pas reconnu"),
			'economicDuration' => s("La <b>durée d'amortissement économique</b> n'est pas reconnue"),
			'fiscalDuration' => s("La <b>durée d'amortissement fiscal</b> n'est pas reconnue"),
			'economicAmortization' => s("Le <b>montant déjà amorti</b> n'est pas cohérent avec la valeur d'acquisition"),
		];

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

				$h .= '<a data-ajax="'.\company\CompanyUi::urlAsset($eFarm).'/csv:doCreateAssets" post-id="'.$eFarm['id'].'" class="btn btn-secondary" data-confirm="'.p("Importer maintenant {value} immobilisation ?", "Importer maintenant {value} immobilisations ?", count($assets)).'" data-waiter="'.s("Importation en cours, merci de patienter...").'">'.s("Importer maintenant").'</a>';
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

						if($asset['errors']) {
							$h .= '<tr>';
								$h .= '<td colspan="9">';
									$h .= '<ul>';
										foreach($asset['errors'] as $error) {
											$h .= '<li>';
												$h .= $messages[$error];
											$h .= '</li>';
										}
									$h .= '</ul>';
								$h .= '</td>';
							$h .= '</tr>';
						}

					}

				$h .= '</tbody>';
			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public function displayDanger(mixed $text): string {

		return '<span class="color-danger">'.encode($text).'</span>';

	}

}
