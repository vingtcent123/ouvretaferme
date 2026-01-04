<?php
namespace overview;

Class SigUi {

	public function __construct() {
		\Asset::css('overview', 'sig.css');
	}

	public function getSearch(\Search $search, \Collection $cFinancialYear, \account\FinancialYear $eFinancialYear): string {

		if($cFinancialYear->count() === 1) {
			return '';
		}

		$excludedProperties = ['ids'];
		if($search->get('view') === IncomeStatementLib::VIEW_BASIC) {
			$excludedProperties[] = 'view';
		}

		$h = '<div id="sig-search" class="util-block-search '.($search->empty($excludedProperties) === TRUE ? 'hide' : '').'">';

		$form = new \util\FormUi();
		$url = LIME_REQUEST_PATH;

		$h .= $form->openAjax($url, ['method' => 'get', 'class' => 'util-search']);

			if($cFinancialYear->count() > 1) {

				$h .= '<fieldset>';
					$h .= '<legend>'.s("Comparer avec un autre exercice").'</legend>';
					$h .= $form->select('financialYearComparison', $cFinancialYear
						->filter(fn($e) => !$e->is($eFinancialYear))
						->makeArray(function($e, &$key) {
							$key = $e['id'];
							return s("Exercice {value}", $e->getLabel());
						}), $search->get('financialYearComparison'));
				$h .= '</fieldset>';

			}

			$h .= '<div class="util-search-submit">';
				$h .= $form->submit(s("Valider"));
				$h .= '<a href="'.$url.'" class="btn">'.\Asset::icon('x-lg').'</a>';
			$h .= '</div>';

		$h .= $form->close();

		$h .= '</div>';

		return $h;

	}


	public function display(\farm\Farm $eFarm, array $values, \account\FinancialYear $eFinancialYear, \account\FinancialYear $eFinancialYearComparison): string {

		$hasComparison = $eFinancialYearComparison->notEmpty();
		$valuesCurrent = $values[$eFinancialYear['id']];

		if($hasComparison) {

			$isComparisonBefore = $eFinancialYearComparison['startDate'] < $eFinancialYear['startDate'];
			$valuesComparison = $values[$eFinancialYearComparison['id']] ?? [];

			if($eFinancialYear['endDate'] > $eFinancialYearComparison['endDate']) {
				if($eFinancialYear['endDate'] > date('Y-m-d')) {
					$date = date('Y-m-d');
				} else {
					$date = $eFinancialYear['endDate'];
				}
			} else {
				if($eFinancialYearComparison['endDate'] > date('Y-m-d')) {
					$date = date('Y-m-d');
				} else {
					$date = $eFinancialYearComparison['endDate'];
				}
			}

		} else {
			if($eFinancialYear['endDate'] > date('Y-m-d')) {
				$date = date('Y-m-d');
			} else {
				$date = $eFinancialYear['endDate'];
			}
		}

		$h = '<div class="util-overflow-xs stick-xs">';

			$h .= '<table class="tr-even overview tr-hover'.($hasComparison ? ' sig_has_previous' : '').'">';

				$h .= '<thead class="thead-sticky">';
					$h .= '<tr class="sig-table-title">';
						$h .= '<th class="text-center" colspan="'.($hasComparison ? 7 : 3).'">';
							$h .= encode($eFarm['legalName']).'<br />';
							if($hasComparison) {
								$h .= s("Soldes intermédiaires de gestion comparés au {value}", \util\DateUi::numeric($date));
							} else {
								$h .= s("Soldes intermédiaires de gestion au {value}", \util\DateUi::numeric($date));
							}
						$h .= '</th>';
					$h .= '</tr>';
					$h .= '<tr class="tr-title">';
						$h .= '<th rowspan="2"></th>';
						$h .= '<th colspan="2" class="text-center">'.s("Exercice {value}", $eFinancialYear->getLabel()).'</th>';
						if($hasComparison) {
							$h .= '<th colspan="2" class="text-center">'.s("Exercice {value}", $eFinancialYearComparison->getLabel()).'</th>';
							$h .= '<th colspan="2" class="text-center">'.s("Comparaison").'</th>';
						}
					$h .= '</tr>';
					$h .= '<tr class="tr-title">';
						$h .= '<th class="text-end highlight-stick-right">'.s("Montant (€)").'</th>';
						$h .= '<th class="text-center highlight-stick-left">'.s("Répartition (%)").'</th>';
						if($hasComparison) {
							$h .= '<th class="text-end highlight-stick-right">'.s("Montant (€)").'</th>';
							$h .= '<th class="text-center highlight-stick-left">'.s("Répartition (%)").'</th>';
							$h .= '<th class="text-center highlight-stick-right">'.s("Variation (€)").'</th>';
							$h .= '<th class="text-center highlight-stick-left">'.s("Variation (%)").'</th>';
						}
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

				foreach(SigLib::ACCOUNTS_ORDER as $account) {

					$isCategory = in_array($account, SigLib::ACCOUNTS_TITLES);
					$isPercentedCategory = ($isCategory and array_search($account, SigLib::ACCOUNTS_ORDER) >= array_search(SigLib::PRODUCTION_EXERCICE_NET_ACHAT_ANIMAUX, SigLib::ACCOUNTS_ORDER));

					$h .= '<tr class="';
						if($isCategory) {
							$h .= 'sig-title';
						} else {
							$h .= 'sig-content';
						}
					$h .= '">';
						$h .= '<td class="sig-category-name">'.$this->name($account, '=').'</td>';
						$h .= '<td class="text-end highlight-stick-right">'.\util\TextUi::money($valuesCurrent[$account]).'</td>';
						$h .= '<td class="text-center highlight-stick-left">';
							if($isPercentedCategory and $valuesCurrent[SigLib::PRODUCTION_EXERCICE_NET_ACHAT_ANIMAUX] !== 0.0) {
								$h .= round(($valuesCurrent[$account] / $valuesCurrent[SigLib::PRODUCTION_EXERCICE_NET_ACHAT_ANIMAUX]) * 100).'%';
							}
						$h .= '</td>';
						if($hasComparison) {
							list($value, $percent) = $this->getComparison($valuesCurrent[$account], $valuesComparison[$account], $isComparisonBefore);
							$h .= '<td class="text-end highlight-stick-right">'.\util\TextUi::money($valuesComparison[$account]).'</td>';
							$h .= '<td class="text-center highlight-stick-left">';
								if($isPercentedCategory and $valuesCurrent[SigLib::PRODUCTION_EXERCICE_NET_ACHAT_ANIMAUX] !== 0.0) {
									$h .= round(($valuesComparison[$account] / $valuesComparison[SigLib::PRODUCTION_EXERCICE_NET_ACHAT_ANIMAUX]) * 100).'%';
								}
							$h .= '</td>';
							$h .= '<td class="text-end highlight-stick-right">';
								$h .= \util\TextUi::money($value);
							$h .= '</td>';
							$h .= '<td class="text-center highlight-stick-left">';
								if(mb_strlen($percent) > 0) {
									$h .= s("{value} %", $percent);
								}
							$h .= '</td>';
						}
					$h .= '</tr>';

					// Répéter ces lignes
					if(in_array($account, [SigLib::VALEUR_AJOUTEE, SigLib::EBE, SigLib::RESULTAT_EXPLOITATION, SigLib::RCAI])) {

						$h .= '<tr class="sig-content">';
							$h .= '<td class="sig-category-name">'.$this->name($account, '+').'</td>';
							$h .= '<td class="text-end highlight-stick-right">'.\util\TextUi::money($valuesCurrent[$account]).'</td>';
							$h .= '<td class="text-end highlight-stick-left"></td>';
							if($hasComparison) {
								list($value, $percent) = $this->getComparison($valuesCurrent[$account], $valuesComparison[$account], $isComparisonBefore);
								$h .= '<td class="text-end highlight-stick-right">'.\util\TextUi::money($valuesComparison[$account]).'</td>';
								$h .= '<td class="text-end highlight-stick-left"></td>';
								$h .= '<td class="text-end highlight-stick-right">';
									$h .= \util\TextUi::money($value);
								$h .= '</td>';
								$h .= '<td class="text-center highlight-stick-left">';
									if(mb_strlen($percent) > 0) {
										$h .= s("{value} %", $percent);
									}
								$h .= '</td>';
							}
						$h .= '</tr>';
					}
				}

				$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</div>';
		return $h;

	}

	private function getComparison(float $valueCurrent, float $valueComparison, bool $isComparisonBefore) {

		if($isComparisonBefore) {
			$value = $valueCurrent - $valueComparison;
			if($valueComparison === 0.0) {
				$percent = '';
			} else {
				$percent = round(($valueCurrent - $valueComparison) / abs($valueComparison), 2) * 100;
			}
		} else {
			$value = $valueComparison - $valueCurrent;
			if($valueCurrent === 0.0) {
				$percent = '';
			} else {
				$percent = round(($valueComparison - $valueCurrent) / abs($valueCurrent), 2) * 100;
			}
		}

		return [$value, $percent];
	}
	
	public function name(string $account, string $categorySymbol): string {
		
		return match($account) {
			SigLib::CHIFFRE_AFFAIRES => s("Chiffre d'affaires"),
			SigLib::VENTE_MARCHANDISES => s("+ Vente de marchandises"),
			SigLib::COUT_ACHAT_MARCHANDISES => s("- Coût d'achat des marchandises"),
			SigLib::MARGE_COMMERCIALE => s("{value} Marge commerciale (MC)", $categorySymbol),
			SigLib::PRODUCTION_VENDUE => s("+ Production vendue"),
			SigLib::PRODUCTION_STOCKEE => s("+ Production stockée ou destockage"),
			SigLib::PRODUCTION_IMMOBILISEE => s("+ Production immobilisée ou autoconsommée"),
			SigLib::PRODUCTION_EXERCICE => s("{value} Production de l'exercice", $categorySymbol),
			SigLib::ACHAT_ANIMAUX => s("- Achats d'animaux"),
			SigLib::PRODUCTION_EXERCICE_NET_ACHAT_ANIMAUX => s("{value} Production de l'exercice nette d'achats d'animaux", $categorySymbol),
			SigLib::MP_APPROVISIONNEMENTS_CONSOMMES => s("- Matières premières et approvisionnements consommés"),
			SigLib::SOUS_TRAITANCE_DIRECTE => s("- Sous-traitance directe"),
			SigLib::MARGE_BRUTE_PRODUCTION => s("{value} Marge brute de production (MB)", $categorySymbol),
			SigLib::MARGE_TOTALE => s("{value} Marge totale (MC + MB)", $categorySymbol),
			SigLib::AUTRES_ACHATS => s("- Autres achats"),
			SigLib::CHARGES_EXTERNES => s("- Charges externes"),
			SigLib::VALEUR_AJOUTEE => s("{value} Valeur ajoutée", $categorySymbol),
			SigLib::SUBVENTIONS_EXPLOITATION => s("+ Subventions et indemnités d'exploitation"),
			SigLib::IMPOTS_TAXES_VERSEMENTS => s("- Impôts, taxes et versements assimilés"),
			SigLib::REMUNERATIONS => s("- Traitements et salaires"),
			SigLib::EBE => s("{value} Excédent brut d'exploitation", $categorySymbol),
			SigLib::AUTRES_PRODUITS_EXPLOITATION => s("- Autres produits d'exploitation"),
			SigLib::REPRISE_AMORTISSEMENTS_PROVISIONS_TRANSFERTS => s("+ Reprise des amortissements, provisions et transferts d'exploitation"),
			SigLib::DOTATIONS_AMORTISSEMENTS_PROVISIONS => s("- Dotation aux amortissements, provisions"),
			SigLib::AUTRES_CHARGES_GESTION_COURANTE => s("- Autres charges de gestion courante"),
			SigLib::RESULTAT_EXPLOITATION => s("{value} Résultat d'exploitation", $categorySymbol),
			SigLib::QP_RESULTAT_POSITIF => s("+ QP de résultat positif sur opérations en commun"),
			SigLib::PRODUITS_FINANCIERS => s("+ Produits financiers"),
			SigLib::QP_RESULTAT_NEGATIF => s("- QP de résultat négatif sur opérations en commun"),
			SigLib::CHARGES_FINANCIERES => s("- Charges financières"),
			SigLib::RCAI => s("{value} Résultat courant avant impôt", $categorySymbol),
			SigLib::PRODUITS_EXCEPTIONNELS => s("+ Produits exceptionnels"),
			SigLib::CHARGES_EXCEPTIONNELLES => s("- Charges exceptionnelles"),
			SigLib::RESULTAT_EXCEPTIONNEL => s("{value} Résultat exceptionnel", $categorySymbol),
			SigLib::PARTICIPATION_SALARIES => s("- Participations des salariés"),
			SigLib::IMPOTS_BENEFICES => s("- Impôts sur les bénéfices"),
			SigLib::RESULTAT_NET => s("{value} Résultat net", $categorySymbol),
		};
		
	}

}

?>
