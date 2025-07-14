<?php
namespace account;

class FinancialYearUi {

	public function __construct() {

		\Asset::js('account', 'financialYear.js');
		\Asset::css('account', 'financialYear.css');
		\Asset::css('company', 'company.css');

	}

	public function getManageTitle(\farm\Farm $eFarm, \Collection $cFinancialYearOpen): string {

		$h = '<div class="util-action">';

			$h .= '<h1>';
				$h .= '<a href="'.\company\CompanyUi::urlSettings($eFarm).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
				$h .= s("Les exercices comptables");
			$h .= '</h1>';

			$h .= '<div>';
			if($cFinancialYearOpen->count() < 2) {
				$h .= '<a href="'.\company\CompanyUi::urlAccount($eFarm).'/financialYear/:create" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Créer un exercice comptable").'</a> ';
			}
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	protected function getAction(\farm\Farm $eFarm, FinancialYear $eFinancialYear): string {

		$h = '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-outline-primary">'.\Asset::icon('gear-fill').'</a>';
		$h .= '<div class="dropdown-list">';
			$h .= '<div class="dropdown-title">'.s("Exercice {year}", ['year' => self::getYear($eFinancialYear)]).'</div>';
			$h .= '<a data-ajax="'.\company\CompanyUi::urlAccount($eFarm).'/financialYear/document:opening" post-id="'.$eFinancialYear['id'].'" class="dropdown-item">'.s("Télécharger le Bilan d'Ouverture (Bientôt disponible !)").'</a>';
			$h .= '<div class="dropdown-divider"></div>';
			$h .= '<a href="'.\company\CompanyUi::urlAccount($eFarm).'/financialYear/:update?id='.$eFinancialYear['id'].'" class="dropdown-item">';
				$h .= s("Modifier");
			$h .= '</a>';
			$h .= '<a href="'.\company\CompanyUi::urlAccount($eFarm).'/financialYear/:close?id='.$eFinancialYear['id'].'" class="dropdown-item">'.s("Clôturer").'</a>';;
		$h .= '</div>';

		return $h;

	}

	protected function getReadAction(\farm\Farm $eFarm, FinancialYear $eFinancialYear): string {

		$h = '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-outline-primary">'.\Asset::icon('gear-fill').'</a>';
		$h .= '<div class="dropdown-list">';
			$h .= '<div class="dropdown-title">'.s("Exercice {year}", ['year' => self::getYear($eFinancialYear)]).'</div>';
			//$h .= '<a href="'.\company\CompanyUi::urlJournal($eCompany).'/vat:pdf?type='.$type.'&financialYear='.$eFinancialYear['id'].'" data-ajax-navigation="never" class="btn btn-primary">'.\Asset::icon('download').'&nbsp;'.s("Télécharger en PDF").'</a>';
			$h .= '<a href="'.\company\CompanyUi::urlAccount($eFarm).'/financialYear/document:fec?id='.$eFinancialYear['id'].'" data-ajax-navigation="never" class="dropdown-item">';
				$h .= s("Télécharger le FEC");
			$h .= '</a>';
			$h .= '<a data-ajax="'.\company\CompanyUi::urlAccount($eFarm).'/financialYear/document:opening" post-id="'.$eFinancialYear['id'].'" class="dropdown-item">'.s("Télécharger le Bilan d'Ouverture (Bientôt disponible !)").'</a>';;
			$h .= '<a data-ajax="'.\company\CompanyUi::urlAccount($eFarm).'/financialYear/document:closing" post-id="'.$eFinancialYear['id'].'" class="dropdown-item">'.s("Télécharger le Bilan de Clôture (Bientôt disponible !)").'</a>';;
		$h .= '</div>';

		return $h;

	}

	public function getManage(\farm\Farm $eFarm, \Collection $cFinancialYear): string {

		if($cFinancialYear->empty() === TRUE) {

			return '<div class="util-info">'
				.s("Aucun exercice comptable n'a encore été enregistré, créez-en un maintenant pour pouvoir démarrer !")
			.'</div>'
			.(new \account\FinancialYearUi()->create($eFarm, new FinancialYear()))->body;
		}

		$h = '';

		$h .= '<div class="stick-sm util-overflow-sm">';

			$h .= '<table class="financialYear-item-table tr-even tr-hover">';

				$h .= '<thead>';

					$h .= '<tr>';

						$h .= '<th class="text-center">'.s("#").'</th>';
						$h .= '<th>'.s("Date de début").'</th>';
						$h .= '<th>'.s("Date de fin").'</th>';
						$h .= '<th>'.s("Assujettissement à la TVA").'</th>';
						$h .= '<th>'.s("Fréquence de <br />déclaration de TVA").'</th>';
						$h .= '<th>'.s("Régime fiscal").'</th>';
						$h .= '<th class="text-center">'.s("Statut").'</th>';
						$h .= '<th></th>';

					$h .= '</tr>';

				$h .= '</thead>';

				$h .= '<tbody>';

					$h .= '<div class="util-overflow-sm">';

					foreach($cFinancialYear as $eFinancialYear) {

						$h .= '<tr>';

							$h .= '<td class="td-min-content text-center">';
								if($eFinancialYear['status'] === FinancialYear::CLOSE) {
									$h .= $eFinancialYear['id'];
								} else {
									$h .= '<a href="'.\company\CompanyUi::urlAccount($eFarm).'/financialYear/:update?id='.$eFinancialYear['id'].'" class="btn btn-sm btn-outline-primary">'.$eFinancialYear['id'].'</a>';
								}
							$h .= '</td>';

							$h .= '<td>';
								$h .= \util\DateUi::numeric($eFinancialYear['startDate']);
							$h .= '</td>';

							$h .= '<td>';
								$h .= \util\DateUi::numeric($eFinancialYear['endDate']);
							$h .= '</td>';

							$h .= '<td>';

								$h .= match($eFinancialYear['hasVat']) {
									TRUE => s("Oui"),
									FALSE => s("Non"),
								};

							$h .= '</td>';

							$h .= '<td>';

								if($eFinancialYear['vatFrequency'] === NULL) {
									$h .= 'n/a';
								} else {
									$h .= self::p('vatFrequency')->values[$eFinancialYear['vatFrequency']];
								}

							$h .= '</td>';

							$h .= '<td>';

								if($eFinancialYear['taxSystem'] === NULL) {
									$h .= '?';
								} else {
									$h .= self::p('taxSystem')->values[$eFinancialYear['taxSystem']];
								}

							$h .= '</td>';

							$h .= '<td class="text-center">';
								$h .= match($eFinancialYear['status']) {
									FinancialYearElement::OPEN => s("En cours"),
									FinancialYearElement::CLOSE => s("Clôturé"),
								};
							$h .= '</td>';

							$h .= '<td>';
							$h .= '</td>';

							$h .= '<td>';
								if($eFinancialYear['status'] === FinancialYearElement::OPEN) {
									$h .= self::getAction($eFarm, $eFinancialYear);
								} else {
									$h .= self::getReadAction($eFarm, $eFinancialYear);
								}
							$h .= '</td>';

						$h .= '</tr>';
					}


				$h .= '</tbody>';
			$h .= '</table>';
		$h .= '</div>';

		return $h;

	}


	public function create(\farm\Farm $eFarm, FinancialYear $eFinancialYear): \Panel {

		$form = new \util\FormUi();

		$h = '';

		if(GET('message') === 'FinancialYear::toCreate') {
			$h .= '<div class="util-info">';
				$h .= s("Avant de démarrer, votre ferme a besoin d'un premier exercice comptable. Vous pouvez le créer ci-dessous :");
			$h .= '</div>';
		}

		$h .= $form->openAjax(\company\CompanyUi::urlAccount($eFarm).'/financialYear/:doCreate', ['id' => 'account-financialYear-create', 'autocomplete' => 'off']);

			$h .= $form->asteriskInfo();

			$h .= $form->hidden('farm', $eFarm['id']);

			$h .= $form->dynamicGroups($eFinancialYear, ['startDate*', 'endDate*', 'hasVat*', 'taxSystem*']);

			$h .= $form->group(
				content: $form->submit(s("Créer l'exercice"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-account-financialYear-create',
			title: s("Créer un exercice comptable"),
			body: $h
		);

	}

	public function update(\farm\Farm $eFarm, FinancialYear $eFinancialYear): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax(\company\CompanyUi::urlAccount($eFarm).'/financialYear/:doUpdate', ['id' => 'account-financialYear-update', 'autocomplete' => 'off']);

			$h .= $form->asteriskInfo();

			$h .= $form->hidden('farm', $eFarm['id']);
			$h .= $form->hidden('id', $eFinancialYear['id']);

			$h .= $form->dynamicGroups($eFinancialYear, ['startDate*', 'endDate*', 'hasVat*', 'vatFrequency*', 'taxSystem*'], [
				'hasVat' => function($d) use($form) {
					$d->attributes['callbackRadioAttributes'] = fn() => ['onclick' => 'FinancialYear.changeHasVat(this)'];
				},
				'vatFrequency' => function($d) use($form, $eFinancialYear) {
					$d->group['class'] = $eFinancialYear['hasVat'] ? '' : 'hide';
				},
			]);

			$h .= $form->group(
				content: $form->submit(s("Enregistrer"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-account-financialYear-update',
			title: s("Modifier un exercice comptable"),
			body: $h
		);

	}

	public function close(\farm\Farm $eFarm, FinancialYear $eFinancialYear, \Collection $cOperationCharges): string {

		$form = new \util\FormUi();

		$h = '<div class="util-block stick-xs bg-background-light mt-1 mb-1">';

			$h .= '<dl class="util-presentation util-presentation-2">';

				$h .= '<dt>'.s("Exercice").'</dt>';
				$h .= '<dd>'.self::getYear($eFinancialYear).' ('.s("du {startDate} au {endDate}", [
					'startDate' => \util\DateUi::numeric($eFinancialYear['startDate'], \util\DateUi::DATE),
					'endDate' => \util\DateUi::numeric($eFinancialYear['endDate'], \util\DateUi::DATE),
				]).')</dd>';

				$h .= '<dt>'.self::p('hasVat')->label.'</dt>';
				$h .= '<dd>'.$eFinancialYear['hasVat'] ? s("Oui") : s("Non").'</dd>';

				$h .= '<dt>'.self::p('taxSystem')->label.'</dt>';
				$h .= '<dd>'.self::p('taxSystem')->values[$eFinancialYear['taxSystem']].'</dd>';

				$h .= '<dt>'.self::p('vatFrequency')->label.'</dt>';
				$h .= '<dd>'.$eFinancialYear['vatFrequency'] ? self::p('vatFrequency')->values[$eFinancialYear['vatFrequency']] : ''.'</dd>';

			$h .= '</dl>';
		$h .= '</div>';

		if($eFinancialYear['hasVat'] and count($eFinancialYear['vatData']) > 0) {

			$h .= '<h3>'.s("Vérification de la TVA").'</h3>';

			$messages = [];

			if(($eFinancialYear['vatData']['undeclaredVatOperations'] ?? 0) > 0) {
				$messages[] = p("{value} opération de TVA n'a pas été déclarée.", "{value} opérations de TVA n'ont pas été déclarées.", $eFinancialYear['vatData']['undeclaredVatOperations']);
			}

			if(count($eFinancialYear['vatData']['missingPeriods'] ?? []) > 0) {
				$periodList = [];
				foreach($eFinancialYear['vatData']['missingPeriods'] as $periods) {
					$periodList[] = s("du {startDate} au {endDate}", [
						'startDate' => \util\DateUi::numeric($periods['start'], \util\DateUi::DATE),
						'endDate' => \util\DateUi::numeric($periods['end'], \util\DateUi::DATE),
					]);
				}

				$periodText = '<ul>';
					$periodText .= '<li>';
						$periodText .= join('</li><li>', $periodList);
					$periodText .= '</li>';
				$periodText .= '</ul>';

				$messages[] = p("{value} période de TVA n'a pas été déclarée : {period}", "{value} périodes de TVA n'ont pas été déclarées : {period}", count($eFinancialYear['vatData']['missingPeriods']), ['period' => $periodText]);
			}

			if(count($messages) > 0) {

				$h .= '<div>';
					$h .= '<ul>';
						$h .= '<li>';
							$h .= join('</li><li>', $messages);
						$h .= '</li>';
					$h .= '</ul>';
				$h .= '</div>';

				$h .= '<a class="btn btn-outline-secondary" href="'.\company\CompanyUi::urlJournal($eFarm).'/vat">'.s("Voir mes déclarations").'</a>';

			} else {

				$h .= '<p>'.s("Aucune anomalie à signaler concernant les déclarations de TVA").'</p>';

			}

		}

		$h .= '<h3 class="mt-2">'.s("Charges à reporter").'</h3>';

		if($cOperationCharges->empty()) {

			$h .= '<div class="util-info">'.s("Aucune charge à reporter sur cet exercice").'</div>';

		} else {

			$countDeferred = $cOperationCharges->find(fn($e) => $e['deferredCharge'] !== NULL)->count();
			$totalDeferred = $countDeferred;

			$h .= '<div class="util-info">';
				$h .= s("Toutes les écritures de charge de cet exercice comptable ont été listées ci-après. Si vous souhaitez que certaines d'entre elles soient en partie reportées au prochain exercice, vous pouvez modifier leur période de consommation ou le montant à reporter");
			$h .= '</div>';

			$h .= '<div class="stick-sm util-overflow-sm mb-1">';

				$h .= '<table class="financial-year-cca-table tr-even tr-hover">';

					$h .= '<thead>';

						$h .= '<tr>';

							$h .= '<th>'.s("Date").'</th>';
							$h .= '<th>'.s("Compte").'</th>';
							$h .= '<th>'.s("Libellé").'</th>';
							$h .= '<th>'.s("Montant HT").'</th>';
							$h .= '<th>'.s("Période de<br />consommation").'</th>';
							$h .= '<th class="text-end">'.s("À reporter").'</th>';

						$h .= '</tr>';

					$h .= '</thead>';

					$h .= '<tbody>';

						foreach($cOperationCharges as $eOperation) {

							if(($eOperation['deferredCharge'] ?? NULL) !== NULL) {

								$isDeferred = TRUE;
								$countDeferred--;

								$period = s("{startDate} - {endDate}", [
									'startDate' => \util\DateUi::numeric($eOperation['date'], \util\DateUi::DATE),
									'endDate' => \util\DateUi::numeric($eOperation['deferredCharge']['endDate'], \util\DateUi::DATE),
								]);
								$amount = \util\TextUi::money($eOperation['deferredCharge']['amount']);

							} else {

								$isDeferred = FALSE;

								$period = '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/deferredCharge:set?operation='.$eOperation['id'].'&financialYear='.$eFinancialYear['id'].'&field=dates">'.s("modifier").'</a>';
								$amount = '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/deferredCharge:set?operation='.$eOperation['id'].'&financialYear='.$eFinancialYear['id'].'&field=amount">'.s("modifier").'</a>';

							}

							if($countDeferred === 0 and $isDeferred === FALSE and $totalDeferred > 0) {

								$class = 'tr-border-top hide';
								$countDeferred = NULL;

							} else if($isDeferred === FALSE) {

								$class = 'hide';

							} else {

								$class = '';

							}

							$h .= '<tr id="'.$eOperation['id'].'" class="'.$class.'">';

								$h .= '<td>'.\util\DateUi::numeric($eOperation['date'], \util\DateUi::DATE).'</td>';
								$h .= '<td>'.encode($eOperation['accountLabel']).'</td>';
								$h .= '<td>'.encode($eOperation['description']).'</td>';
								$h .= '<td class="text-end">'.\util\TextUi::money($eOperation['amount']).'</td>';
								$h .= '<td>'.$period.'</td>';
								$h .= '<td class="text-end">'.$amount.'</td>';

							$h .= '</tr>';

						}

					$h .= '</tbody>';

				$h .= '</table>';

				if($cOperationCharges->count() > $totalDeferred) {
					$h .= '<a style="width: 100%" class="btn btn-outline-secondary" onclick="FinancialYear.displayCharges(this)">'.s("Afficher toutes les charges").'</a>';
				}

			$h .= '</div>';
		}

		$h .= $form->openAjax(\company\CompanyUi::urlAccount($eFarm).'/financialYear/:doClose', ['id' => 'account-financialYear-close', 'autocomplete' => 'off']);


			$h .= $form->hidden('farm', $eFarm['id']);
			$h .= $form->hidden('id', $eFinancialYear['id']);

			$h .= '<div>'.$form->submit(s("Clôturer l'exercice comptable {year}", ['year' => self::getYear($eFinancialYear)])).'</div>';

		$h .= $form->close();

		return $h;

	}

	public static function getYear(\account\FinancialYear $eFinancialYear): string {

		if($eFinancialYear->empty()) {
			return '';
		}

		if(substr($eFinancialYear['startDate'], 0, 4) === substr($eFinancialYear['endDate'], 0, 4)) {
			return substr($eFinancialYear['startDate'], 0, 4);
		}

		return substr($eFinancialYear['startDate'], 0, 4).' - '.substr($eFinancialYear['endDate'], 0, 4);

	}

	public function getFinancialYearTabs(\Closure $url, \Collection $cFinancialYear, \account\FinancialYear $eFinancialYearSelected): string {

		$h = ' <a data-dropdown="bottom-start" data-dropdown-hover="TRUE" data-dropdown-offset-x="2" class="nav-year">';
			$h .= s("Exercice {year}", ['year' => self::getYear($eFinancialYearSelected).'  '.\Asset::icon('chevron-down')]);
		$h .= '</a>';

		$h .= '<div class="dropdown-list bg-primary">';

		$h .= '<div class="dropdown-title">'.s("Changer d'exercice").'</div>';

		foreach($cFinancialYear as $eFinancialYear) {
			$h .= '<a href="'.$url($eFinancialYear).'" class="dropdown-item '.($eFinancialYear['id'] === $eFinancialYearSelected['id'] ? 'selected' : '').'">'.s("Exercice {year}", ['year' => self::getYear($eFinancialYear)]).'</a>';
		}

		$h .= '</div>';

		return $h;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = FinancialYear::model()->describer($property, [
			'startDate' => s("Date de début"),
			'endDate' => s("Date de fin"),
			'hasVat' => s("Assujettissement à la TVA"),
			'vatFrequency' => s("Fréquence de déclaration de TVA"),
			'taxSystem' => s("Régime fiscal"),
		]);

		switch($property) {

			case 'startDate' :
			case 'endDate' :
				$d->prepend = \Asset::icon('calendar-date');
				break;

			case 'hasVat' :
				$d->field = 'yesNo';
				break;

			case 'vatFrequency' :
				$d->values = [
					FinancialYear::MONTHLY => s("Mensuelle"),
					FinancialYear::QUARTERLY => s("Trimestrielle"),
					FinancialYear::ANNUALLY => s("Annuelle"),
				];
				$d->attributes['mandatory'] = TRUE;
				break;

			case 'taxSystem':
				$d->values = [
					FinancialYear::MICRO_BA => s("Micro-BA"),
					FinancialYear::BA_REEL_SIMPLIFIE => s("Réel simplifié agricole"),
					FinancialYear::BA_REEL_NORMAL => s("Réel normal agricole"),
					FinancialYear::AUTRE_BIC => s("BIC"),
					FinancialYear::AUTRE_BNC => s("BNC"),
				];
				$d->attributes['mandatory'] = TRUE;
		}

		return $d;

	}
}

?>
