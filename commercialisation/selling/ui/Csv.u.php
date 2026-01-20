<?php
namespace selling;

class CsvUi {

	public function __construct() {

		\Asset::css('main', 'csv.css');

	}

	public function getProducts(\farm\Farm $eFarm, array $data): string {

		['import' => $import, 'errorsCount' => $errorsCount, 'errorsGlobal' => $errorsGlobal, 'infoGlobal' => $infoGlobal] = $data;

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
				$h .= '<a data-ajax="/selling/csv:doCreateProducts" post-id="'.$eFarm['id'].'" class="btn btn-secondary" data-confirm="'.p("Importer maintenant {value} produit ?", "Importer maintenant {value} produits ?", count($data['import'])).'" data-ajax-waiter="'.s("Importation en cours, merci de patienter...").'">'.s("Importer maintenant").'</a>';
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
					array_walk($values, 'encode');
					$h .= '<div class="util-block">';
						$h .= '<h4 class="color-danger">'.s("Unités manquantes").'</h4>';
						$h .= '<p>'.s("Les unités suivantes n'existent pas sur votre ferme, corrigez votre fichier CSV pour les faire correspondre à une unité existante ou ajoutez-les à votre ferme. Pour rappel, vous devez utiliser <link>le nom des unités au singulier</link> pour qu'elles soient reconnues.", ['link' => '<a href="'.UnitUi::urlManage($eFarm).'" target="_blank">']).'</p>';
						$h .= '<p style="font-style: italic">'.encode(implode(', ', $values)).'</p>';
						$h .= '<a href="'.UnitUi::urlManage($eFarm).'" target="_blank" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Ajouter des unités").'</a>';
					$h .= '</div>';
					break;

				case 'species' :
					array_walk($values, 'encode');
					$h .= '<div class="util-block">';
						$h .= '<h4 class="color-danger">'.s("Espèces manquantes").'</h4>';
						$h .= '<p>'.s("Les espèces suivantes n'existent pas ou sont désactivées sur votre ferme, corrigez votre fichier CSV pour les faire correspondre à une espèce existante ou ajoutez-les à votre ferme :", ['link' => '<a href="'.\plant\PlantUi::urlManage($eFarm).'" target="_blank">']).'</p>';
						$h .= '<p style="font-style: italic">'.encode(implode(', ', $values)).'</p>';
						$h .= '<a href="'.\plant\PlantUi::urlManage($eFarm).'" target="_blank" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Ajouter des espèces").'</a>';
					$h .= '</div>';
					break;

				case 'profiles' :
					array_walk($values, 'encode');
					$h .= '<div class="util-block">';
						$h .= '<h4 class="color-danger">'.s("Types de produits non reconnus").'</h4>';
						$h .= '<p>'.s("Les types de produits suivants ne peuvent pas être importés, vous devez les retirer de votre fichier CSV.").'</p>';
						$h .= '<p style="font-style: italic">'.encode(implode(', ', $values)).'</p>';
					$h .= '</div>';
					break;

			}

		}

		foreach($infoGlobal as $type => $values) {

			if(empty($values)) {
				continue;
			}

			switch($type) {

				case 'references' :
					$h .= '<div class="util-block">';
					$h .= '<h4>'.s("Références déjà connues").'</h4>';
					$h .= '<p>'.s("Les références suivantes ont été reconnues. Les produits concernés ne seront pas ajoutés une deuxième fois mais seront modifiés avec les nouvelles valeurs. <b>Toutes les valeurs, à l'exception de l'unité de vente qui ne peut pas être modifiée par un import, seront mises à jour y compris celles qui sont vides ou ne sont pas présentes dans votre fichier CSV, soyez vigilant pour ne pas perdre des données !</b>").'</p>';
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

							if(in_array($product['reference'], $infoGlobal['references'])) {
								$h .= '<div class="color-secondary mt-1">'.\Asset::icon('info-circle').' '.s("Référence {value} déjà connue", encode($product['reference'])).'</div>';
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
										if(in_array('referenceInvalid', $product['errors'])) {
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
							'referenceInvalid' => s("Une référence de produit ne peut contenir que des lettres, des chiffres ou des tirets"),
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

	public function getCustomers(\farm\Farm $eFarm, array $data): string {

		['import' => $import, 'errorsCount' => $errorsCount, 'errorsGlobal' => $errorsGlobal, 'infoGlobal' => $infoGlobal] = $data;

		$h = '';

		if($errorsCount > 0) {
			$h .= \main\CsvUi::getGlobalErrors($errorsCount, '/doc/import:customers');
		} else {
			$h .= '<div class="util-block">';
				$h .= '<h4>'.s("Vos données sont prêtes à être importées").'</h4>';
				$h .= '<ul>';
					$h .= '<li>'.s("Les clients présents dans le tableau ci-dessous seront créés et associés à votre ferme").'</li>';
					$h .= '<li>'.s("Il est encore temps de faire des modifications dans votre fichier CSV si vous n'êtes pas totalement satisfait de la version actuelle").'</li>';
					$h .= '<li>'.s("Si vous changez d'avis, vous pourrez toujours supprimer ultérieurement les clients que vous importez maintenant").'</li>';
				$h .= '</ul>';
				$h .= '<a data-ajax="/selling/csv:doCreateCustomers" post-id="'.$eFarm['id'].'" class="btn btn-secondary" data-confirm="'.p("Importer maintenant {value} client ?", "Importer maintenant {value} clients ?", count($data['import'])).'" data-ajax-waiter="'.s("Importation en cours, merci de patienter...").'">'.s("Importer maintenant").'</a>';
			$h .= '</div>';
		}

		foreach($errorsGlobal as $type => $values) {

			if(empty($values)) {
				continue;
			}

			switch($type) {

				case 'countries' :
					array_walk($values, 'encode');
					$h .= '<div class="util-block">';
						$h .= '<h4 class="color-danger">'.s("Pays non reconnus").'</h4>';
						$h .= '<p>'.s("Les pays suivants ne peuvent pas être importés, corrigez votre fichier CSV pour les faire correspondre à un pays reconnu.").'</p>';
						$h .= '<p style="font-style: italic">'.encode(implode(', ', $values)).'</p>';
					$h .= '</div>';
					break;

				case 'groups' :
					array_walk($values, 'encode');
					$h .= '<div class="util-block">';
						$h .= '<h4 class="color-danger">'.s("Groupes de clients non reconnus").'</h4>';
						$h .= '<p>'.s("Les groups de clients suivants ne peuvent pas être importés, corrigez votre fichier CSV pour les faire correspondre à un groupe existant o ajoutez-les d'abord à votre ferme.").'</p>';
						$h .= '<p style="font-style: italic">'.encode(implode(', ', $values)).'</p>';
						$h .= '<a href="/selling/customerGroup:manage?farm='.$eFarm['id'].'" target="_blank" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Ajouter des groupes de clients").'</a>';
					$h .= '</div>';
					break;

			}

		}

		foreach($infoGlobal as $type => $values) {

			if(empty($values)) {
				continue;
			}

			switch($type) {

				case 'emails' :
					$h .= '<div class="util-block">';
					$h .= '<h4>'.s("E-mails déjà connus").'</h4>';
					$h .= '<p>'.s("Il y a déjà un client pour les adresses e-mail suivantes. Les clients en question ne seront pas importés une nouvelle fois.").'</p>';
					$h .= '<p style="font-style: italic">'.encode(implode(', ', $values)).'</p>';
					$h .= '</div>';
					break;

			}

		}

		$h .= '<div class="util-overflow-lg">';

			$h .= '<table class="tr-even">';

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th>'.s("Client").'</th>';
						$h .= '<th>'.s("Contact").'</th>';
						$h .= '<th>'.s("Livraison").'</th>';
						$h .= '<th>'.s("Facturation").'</th>';
						$h .= '<th>'.s("Autres données").'</th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody class="td-vertical-align-top">';

				foreach($import as $customer) {

					$h .= '<tr class="'.($customer['errors'] ? 'csv-error' : ($customer['warnings'] ? 'csv-warning' : '')).'">';

						$h .= '<td class="td-min-content">';

							switch($customer['type']) {

								case CustomerUi::getCategories()[Customer::PRIVATE] :

									if($customer['private_first_name'] !== NULL) {
										$h .= encode($customer['private_first_name']).' ';
									}

									$h .= '<b>'.encode($customer['private_last_name']).'</b>';

									break;

								case CustomerUi::getCategories()[Customer::PRO] :
									$h .= '<b>'.encode($customer['pro_commercial_name']).'</b>';
									break;

								default :
									$h .= '<span class="color-danger">'.\Asset::icon('exclamation-triangle').' '.s("Type de client manquant").'</span>';
									break;

							}

							if($customer['type'] !== NULL) {
								$h .= '<div class="util-annotation">'.encode($customer['type']).'</div>';
							}

						$h .= '</td>';
						$h .= '<td>';

							if($customer['type'] === Customer::PRO and $customer['pro_contact_name']) {
								$h .= '<div>';
									$h .= \Asset::icon('person-vcard').'  '.encode($customer['pro_contact_name']);
								$h .= '</div>';
							}
							if($customer['email']) {
								$h .= '<div>';
									$h .= \Asset::icon('at').'  '.encode($customer['email']);
								$h .= '</div>';
							}
							if($customer['phone']) {
								$h .= '<div>';
									$h .= \Asset::icon('telephone').'  '.encode($customer['phone']);
								$h .= '</div>';
							}

						$h .= '</td>';

						foreach(['delivery', 'invoice'] as $mode) {

							$h .= '<td>';

								if($customer[$mode.'_street_1']) {
									$h .= '<div>';
										$h .= encode($customer[$mode.'_street_1']);
									$h .= '</div>';
								}
								if($customer[$mode.'_street_2']) {
									$h .= '<div>';
										$h .= encode($customer[$mode.'_street_2']);
									$h .= '</div>';
								}
								if($customer[$mode.'_postcode'] or $customer[$mode.'_city']) {
									$h .= '<div>';
										if($customer[$mode.'_postcode']) {
											$h .= encode($customer[$mode.'_postcode']);
										}
										$h .= ' ';
										if($customer[$mode.'_city']) {
											$h .= encode($customer[$mode.'_city']);
										}
									$h .= '</div>';
								}
								if($customer[$mode.'_country']) {
									$h .= '<div>';
										$h .= encode($customer[$mode.'_country']);
									$h .= '</div>';
								}

							$h .= '</td>';

						}

						$h .= '<td>';
							$h .= '<dl class="util-presentation util-presentation-1" style="grid-row-gap: 0.125rem">';

								if($customer['invite']) {
									$h .= '<dt>'.s("Invitation").'</dt>';
									$h .= '<dd>'.s("oui").'</dd>';
								}

								if($customer['type'] === Customer::PRO) {

									if($customer['pro_legal_name']) {
										$h .= '<dt>'.s("Raison sociale").'</dt>';
										$h .= '<dd>'.encode($customer['pro_legal_name']).'</dd>';
									}

									if($customer['pro_siret']) {
										$h .= '<dt>'.s("SIRET").'</dt>';
										$h .= '<dd>'.encode($customer['pro_siret']).'</dd>';
									}

									if($customer['pro_vat_number']) {
										$h .= '<dt>'.s("Numéro de TVA").'</dt>';
										$h .= '<dd>'.encode($customer['pro_vat_number']).'</dd>';
									}

								}

								if($customer['groups']) {
									$h .= '<dt>'.s("Groupe").'</dt>';
									$h .= '<dd>';
										$h .= encode(implode(', ', $customer['groups']));
									$h .= '</dd>';
								}

							$h .= '</dl>';
						$h .= '</td>';
					$h .= '</tr>';

					if($customer['errors']) {

						$messages = [
							'typeMissing' => s("Il manque le type de client"),
							'typeInvalid' => s("Le type du produit est incorrect dans le fichier CSV ({private}, {pro})", ['private' => Customer::PRIVATE, 'pro' => Customer::PRO]),
							'lastNameMissing' => s("Il manque le nom de famille du client"),
							'lastNameIncompatible' => s("Le nom de famille sera ignoré car c'est un client professionnel"),
							'firstNameIncompatible' => s("Le prénom sera ignoré car c'est un client professionnel"),
							'commercialNameMissing' => s("Il manque le nom commercial du client"),
							'commercialNameIncompatible' => s("Le nom commercial sera ignoré car c'est un client particulier"),
							'legalNameIncompatible' => s("La raison sociale sera ignorée car c'est un client particulier"),
							'countryMissing' => s("Vous devez indiquer au moins un pays (livraison ou facturation) pour le client"),
							'groupError' => s("Un ou plusieurs groupes ne sont pas reconnus"),
							'invoiceCountryError' => s("Le pays de facturation n'est pas reconnu"),
							'invoiceStreet1Error' => s("L'adresse est incorrecte"),
							'invoicePostCodeError' => s("Le code postal est incorrect"),
							'invoiceCityError' => s("La ville est incorrecte"),
							'invoiceAddressError' => s("L'adresse de facturation est incomplète (la première ligne, le code postal et la ville sont requis)"),
							'deliveryCountryError' => s("Le pays de livraison n'est pas reconnu"),
							'deliveryStreet1Error' => s("L'adresse est incorrecte"),
							'deliveryPostCodeError' => s("Le code postal est incorrect"),
							'deliveryCityError' => s("La ville est incorrecte"),
							'deliveryAddressError' => s("L'adresse de livraison est incomplète (la première ligne, le code postal et la ville sont requis)"),
							'emailError' => s("L'adresse e-mail est incorrecte"),
							'emailExisting' => s("Vous avez déjà un client avec la même adresse e-mail et ce client ne sera pas importé à nouveau"),
							'phoneError' => s("Le numéro de téléphone est incorrect"),
							'contactNameError' => s("Le contact est incorrect"),
							'siretError' => s("Le SIRET est incorrect"),
							'vatNumberError' => s("Le numéro de TVA est incorrect"),
							'inviteNoEmail' => s("Vous ne pouvez pas inviter ce client à créer un compte client si vous ne fournissez pas d'adresse e-mail"),
						];

						$h .= '<tr>';
							$h .= '<td colspan="5">';
								$h .= '<ul class="mb-0">';
									foreach($customer['errors'] as $error) {
										$h .= '<li class="color-danger">'.$messages[$error].'</li>';
									}
									foreach($customer['warnings'] as $warning) {
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
