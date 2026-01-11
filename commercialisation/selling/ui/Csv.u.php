<?php
namespace selling;

class CsvUi {

	public function __construct() {

		\Asset::css('main', 'csv.css');
		\Asset::js('main', 'csv.js');

	}

	public function getImportFile(\farm\Farm $eFarm, array $data, \Collection $cPlant): string {

		['import' => $import, 'errorsCount' => $errorsCount, 'errorsGlobal' => $errorsGlobal] = $data;

		$h = '';

		if($errorsCount > 0) {
			$h .= \main\CsvUi::getGlobalErrors($errorsCount, '/doc/import:products');
		} else {
			$h .= '<div class="util-block">';
				$h .= '<h4>'.s("Vos données sont prêtes à être importées").'</h4>';
				$h .= '<ul>';
					$h .= '<li>'.s("Les produits présents dans le tableau ci-dessous seront créés et associés à votre ferme").'</li>';
					$h .= '<li>'.s("Il est encore temps de faire des modifications dans votre fichier CSV si vous n'êtes pas totalement satisfait de la version actuelle").'</li>';
					$h .= '<li>'.s("Si vous changez d'avis, vous pourrez toujours supprimer ultérieurement les produits que vous importez maintenant").'</li>';
				$h .= '</ul>';
				$h .= '<a data-url="/selling/csv:doCreateProducts" post-id="'.$eFarm['id'].'" class="btn btn-secondary" data-confirm-text="'.p("Importer maintenant {value} produit ?", "Importer maintenant {value} produits ?", count($data['import'])).'" data-waiter="'.s("Importation en cours, merci de patienter...").'" onclick="Csv.import(this)">'.s("Importer maintenant").'</a>';
			$h .= '</div>';
		}

		foreach($errorsGlobal as $type => $values) {

			if(empty($values)) {
				continue;
			}

			switch($type) {

				case 'vatRates' :

					$vatRates = SellingSetting::getVatRates($eFarm);
					array_walk($vatRates, fn(&$value) => $value .= ' %');

					array_walk($values, fn(&$value) => $value .= ' %');

					$h .= '<div class="util-block">';
						$h .= '<h4 class="color-danger">'.s("Taux de TVA invalides").'</h4>';
						$h .= '<p>'.s("Les taux de TVA suivants ne sont pas reconnus par Ouvretaferme, corrigez votre fichier CSV :", ['values' => '<a href="'.UnitUi::urlManage($eFarm).'" target="_blank">']).'</p>';
						$h .= '<p style="font-style: italic">'.encode(implode(', ', $values)).'</p>';
						$h .= '<p>'.s("Pour votre pays, {siteName} supporte les taux de TVA suivants : {value}", implode(', ', $vatRates)).'</p>';
					$h .= '</div>';
					break;

				case 'units' :
					$h .= '<div class="util-block">';
						$h .= '<h4 class="color-danger">'.s("Unités manquantes").'</h4>';
						$h .= '<p>'.s("Les unités suivantes n'existent pas sur votre ferme, corrigez votre fichier CSV pour les faire correspondre à une unité existante ou ajoutez-les à votre ferme. Pour rappel, vous devez utiliser <link>le nom des unités au singulier</link> pour qu'elles soient reconnues.", ['link' => '<a href="'.UnitUi::urlManage($eFarm).'" target="_blank">']).'</p>';
						$h .= '<p style="font-style: italic">'.encode(implode(', ', $values)).'</p>';
						$h .= '<a href="'.UnitUi::urlManage($eFarm).'" target="_blank" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Ajouter des unités").'</a>';
					$h .= '</div>';
					break;

				case 'species' :
					$h .= '<div class="util-block">';
						$h .= '<h4 class="color-danger">'.s("Espèces manquantes").'</h4>';
						$h .= '<p>'.s("Les espèces suivantes n'existent pas ou sont désactivées sur votre ferme, corrigez votre fichier CSV pour les faire correspondre à une espèce existante ou ajoutez-les à votre ferme :", ['link' => '<a href="'.\plant\PlantUi::urlManage($eFarm).'" target="_blank">']).'</p>';
						$h .= '<p style="font-style: italic">'.encode(implode(', ', $values)).'</p>';
						$h .= '<a href="'.\plant\PlantUi::urlManage($eFarm).'" target="_blank" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Ajouter des espèces").'</a>';
					$h .= '</div>';
					break;

				case 'references' :
					$h .= '<div class="util-block">';
						$h .= '<h4 class="color-danger">'.s("Références utilisées plusieurs fois").'</h4>';
						$h .= '<p>'.s("Les références suivantes seront utilisées plusieurs fois si vous ajoutez ces produits. Or une référence ne peut être utilisée que pour un seul produit.").'</p>';
						$h .= '<p style="font-style: italic">'.encode(implode(', ', $values)).'</p>';
					$h .= '</div>';
					break;

			}

		}

		$h .= '<div class="util-overflow-lg">';

			$h .= '<table class="tr-even">';

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th rowspan="2">'.s("Nom").'</th>';
						$h .= '<th rowspan="2">'.s("Unité").'</th>';
						$h .= '<th colspan="2" class="text-center highlight">'.s("Prix").'</th>';
						$h .= '<th rowspan="2" class="text-end">'.s("TVA").'</th>';
						$h .= '<th rowspan="2">'.s("Autres données").'</th>';
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<th class="highlight-stick-right text-end">'.s("Particulier").'</th>';
						$h .= '<th class="highlight-stick-left text-end">'.s("Pro").'</th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody class="td-vertical-align-top">';

				foreach($import as $product) {

					$h .= '<tr class="'.($product['errors'] ? 'csv-error' : ($product['warnings'] ? 'csv-warning' : '')).'">';

						$h .= '<td class="td-min-content">';

							if($product['name'] === NULL) {
								$h .= '<span class="color-danger">'.\Asset::icon('exclamation-triangle').' '.s("Nom manquant").'</span>';
							} else {
								$h .= encode($product['name']);
							}

							if($product['profile'] !== NULL) {
								$h .= '<div class="util-annotation">'.ProductUi::p('profile')->values[$product['profile']].'</div>';
							}

							if($product['description'] !== NULL) {
								$h .= $product['description'];
							}

						$h .= '</td>';
						$h .= '<td>';
							if($product['eUnit']->notEmpty()) {
								$h .= encode($product['eUnit']['singular']);
							} else if($product['unit'] !== NULL) {
								$h .= '<span class="color-danger">'.\Asset::icon('exclamation-triangle').' '.encode($product['unit']).'</span>';
							}
						$h .= '</td>';
						$h .= '<td class="highlight-stick-right text-end">';
							if($product['price_private'] !== NULL) {
								$h .= \util\TextUi::money($product['price_private']);
								$h .= $eFarm->getConf('hasVat') ? ' <span class="util-annotation">'.CustomerUi::getTaxes(Customer::PRIVATE).'</span>' : '';
							}
						$h .= '</td>';
						$h .= '<td class="highlight-stick-left text-end">';
							if($product['price_pro'] !== NULL) {
								$h .= \util\TextUi::money($product['price_pro']);
								$h .= $eFarm->getConf('hasVat') ? ' <span class="util-annotation">'.CustomerUi::getTaxes(Customer::PRO).'</span>' : '';
							}
						$h .= '</td>';
						$h .= '<td class="text-end">';
							if($product['vat'] !== NULL) {
								$h .= s("{value} %", $product['vat_rate']);
							} else {
								$h .= '<span class="color-danger">'.\Asset::icon('exclamation-triangle').' '.encode(s("{value} %", $product['vat_rate'])).'</span>';
							}
						$h .= '</td>';
						$h .= '<td>';
							$h .= '<dl class="util-presentation util-presentation-1" style="grid-row-gap: 0.125rem">';

								if($product['reference']) {
									$h .= '<dt>'.ProductUi::p('reference')->label.'</dt>';
									$h .= '<dd>';
										if(in_array($product['reference'], $errorsGlobal['references'])) {
											$h .= '<span class="color-danger">'.\Asset::icon('exclamation-triangle').' '.s("{value} en doublon", encode($product['reference'])).'</span>';
										} else if(in_array('referenceInvalid', $product['errors'])) {
											$h .= '<span class="color-danger">'.\Asset::icon('exclamation-triangle').' '.encode($product['reference']).'</span>';
										} else {
											$h .= $product['reference'];
										}
									$h .= '</dd>';
								}

								if($product['additional']) {
									$h .= '<dt>'.s("Complément").'</dt>';
									$h .= '<dd>'.encode($product['additional']).'</dd>';
								}

								if($product['quality']) {
									$h .= '<dt>'.ProductUi::p('quality')->label.'</dt>';
									$h .= '<dd>';
										if(in_array('qualityInvalid', $product['errors'])) {
											$h .= '<span class="color-danger">'.\Asset::icon('exclamation-triangle').' '.encode($product['quality']).'</span>';
										} else {
											$h .= ProductUi::p('quality')->values[$product['quality']];
										}
									$h .= '</dd>';
								}

								if($product['origin']) {
									$h .= '<dt>'.ProductUi::p('origin')->label.'</dt>';
									$h .= '<dd>'.encode($product['origin']).'</dd>';
								}

								if($product['ePlant']->notEmpty()) {
									$h .= '<dt>'.ProductUi::p('unprocessedPlant')->label.'</dt>';
									$h .= '<dd>'.encode($product['ePlant']['name']).'</dd>';
								} else if($product['species'] !== NULL and in_array($product['profile'], Product::getProfiles('unprocessedPlant'))) {
									$h .= '<dt>'.ProductUi::p('unprocessedPlant')->label.'</dt>';
									$h .= '<dd><span class="color-danger">'.\Asset::icon('exclamation-triangle').' '.encode($product['species']).'</span></dd>';
								}

								if($product['variety']) {
									$h .= '<dt>'.ProductUi::p('unprocessedVariety')->label.'</dt>';
									$h .= '<dd>'.encode($product['variety']).'</dd>';
								}

								if($product['frozen']) {
									$h .= '<dt>'.ProductUi::p('mixedFrozen')->label.'</dt>';
									$h .= '<dd>'.s("Oui").'</dd>';
								}

								if($product['allergen']) {
									$h .= '<dt>'.ProductUi::p('processedAllergen')->label.'</dt>';
									$h .= '<dd>'.encode($product['allergen']).'</dd>';
								}

								if($product['composition']) {
									$h .= '<dt>'.ProductUi::p('processedComposition')->label.'</dt>';
									$h .= '<dd>'.encode($product['composition']).'</dd>';
								}

								if($product['packaging']) {
									$h .= '<dt>'.ProductUi::p('processedPackaging')->label.'</dt>';
									$h .= '<dd>'.encode($product['packaging']).'</dd>';
								}

							$h .= '</dl>';
						$h .= '</td>';
					$h .= '</tr>';

					if($product['errors'] or $product['warnings']) {

						$messages = [
							'unitInvalid' => s("L'unité n'est pas reconnue"),
							'vatRateInvalid' => s("Le taux de TVA n'est pas reconnu"),
							'profileMissing' => s("Il manque le profil du produit"),
							'profileInvalid' => s("Le profil du produit est incorrect dans le fichier CSV ({value})", implode(', ', Product::getProfiles('import'))),
							'nameMissing' => s("Il manque le nom du produit"),
							'speciesInvalid' => s("Cette espèce n'existe pas sur votre ferme"),
							'speciesIncompatible' => s("Le choix d'une espèce est incompatible avec ce type de produit et sera ignoré"),
							'varietyIncompatible' => s("Le choix d'une variété est incompatible avec ce type de produit et sera ignoré"),
							'allergenIncompatible' => s("Les allergènes sont incompatibles avec ce type de produit et seront ignorés"),
							'packagingIncompatible' => s("Le choix d'un conditionnement est incompatible avec ce type de produit et sera ignoré"),
							'compositionIncompatible' => s("Le choix d'une composition est incompatible avec ce type de produit et sera ignoré"),
							'frozenIncompatible' => s("La surgélation est incompatible avec ce type de produit et sera ignorée"),
							'qualityInvalid' => s("Le signe de qualité choisi n'est pas disponible"),
							'qualityIncompatible' => s("Le choix d'un signe de qualité est incompatible avec ce type de produit et sera ignoré"),
							'referenceInvalid' => s("La référence ne respecte pas le format alphanumérique"),
						];

						$h .= '<tr>';
							$h .= '<td colspan="9">';
								$h .= '<ul class="mb-0">';
									foreach($product['errors'] as $error) {
										$h .= '<li class="color-danger">'.$messages[$error].'</li>';
									}
									foreach($product['warnings'] as $warning) {
										$h .= '<li class="color-warning">'.$messages[$warning].'</li>';
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

}
?>
