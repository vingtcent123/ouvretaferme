<?php
namespace asset;

Class AssetUi {

	public function __construct() {
		\Asset::css('company', 'company.css');
		\Asset::css('asset', 'asset.css');
		\Asset::js('asset', 'asset.js');
	}

	public function getAssetShortTranslation(): string {

		return s("I");

	}

	public static function getAcquisitionTitle(): string {

		$h = '<div class="util-action">';

			$h .= '<h1>';
				$h .= s("Tableau des acquisitions");
			$h .= '</h1>';

		$h .= '</div>';

		return $h;
	}

	public static function getTitle(): string {

		$h = '<div class="util-action">';

			$h .= '<h1>';
				$h .= s("Synthèse générale par compte");
			$h .= '</h1>';

		$h .= '</div>';

		return $h;
	}

	public function number(mixed $number, ?string $valueIfEmpty, ?int $decimals = NULL): string {

		if(is_null($number) === true or $number === 0 or $number === 0.0) {

			if(is_null($valueIfEmpty) === FALSE) {
				return $valueIfEmpty;
			}

			return number_format(0, $decimals ?? 2, '.', ' ');

		}

		return number_format($number, $decimals ?? 2, '.', ' ');

	}

	public function create(\farm\Farm $eFarm, Asset $eAsset = new Asset()): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax(\company\CompanyUi::urlAsset($eFarm).'/asset:doCreate', ['id' => 'asset-asset-create', 'autocomplete' => 'off']);

		$h .= $form->asteriskInfo();

		$h .= $form->dynamicGroups($eAsset, ['description*']);

		$h .= $form->dynamicGroups($eAsset, ['account*', 'accountLabel*'], ['account*' => function($d) use($form, $eAsset) {
			$d->autocompleteDispatch = '[data-account="'.$form->getId().'"]';
			$d->attributes['data-wrapper'] = 'account';
			$d->attributes['data-account'] = $form->getId();
			$d->autocompleteDefault = $eAsset['account'];
		}, 'accountLabel*' => function($d) use($form) {
			$d->autocompleteDispatch = '[data-account-label="'.$form->getId().'"]';
			$d->attributes['data-account-label'] = $form->getId();
		}]);

		$h .= $form->dynamicGroups($eAsset, ['value*', 'depreciableBase*', 'acquisitionDate*', 'startDate*']);
		$h .= '<h3>'.s("Amortissement économique").'</h3>';
		$h .= '<div class="util-block bg-background-light">';
			$h .= $form->dynamicGroups($eAsset, ['economicMode*', 'economicDuration*']);
		$h .= '</div>';

		$h .= '<h3>'.s("Amortissement fiscal").'</h3>';
		$h .= '<div class="util-block bg-background-light">';
			$h .= $form->dynamicGroups($eAsset, ['fiscalMode*', 'fiscalDuration*']);
		$h .= '</div>';

		$h .= $form->group(
			content: $form->submit(s("Créer l'immobilisation"))
		);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-asset-create',
			title: s("Ajouter une immobilisation"),
			body: $h,
			close: 'passthrough',
		);

	}

	public function query(\PropertyDescriber $d, int $farm, bool $multiple = FALSE, array $query = []): void {

		$d->prepend = \Asset::icon('house');
		$d->field = 'autocomplete';

		$d->placeholder ??= s("Commencez à saisir l'immobilisation...");
		$d->multiple = $multiple;

		$d->autocompleteUrl = \company\CompanyUi::urlAsset($farm).'/asset:query?'.http_build_query($query);
		$d->autocompleteResults = function(Asset $e) use ($farm) {
			return self::getAutocomplete($farm, $e);
		};

	}

	public static function getAutocomplete(int $farm, Asset $eAsset, \Search $search = new \Search()): array {

		\Asset::css('media', 'media.css');

		$itemHtml = '<div>';
			$itemHtml .= encode($eAsset['accountLabel'].' ' .$eAsset['description']);
			$itemHtml .= '<div class="ml-1 color-muted font-sm">'.s("Date d'acquisition : {value}", \util\DateUi::numeric($eAsset['acquisitionDate'])).'</div>';
			$itemHtml .= '<div class="ml-1 color-muted font-sm">'.s("Valeur : {value} / Base amortissable : {base}", ['value' => \util\TextUi::money($eAsset['value']), 'base' => \util\TextUi::money($eAsset['depreciableBase'])]).'</div>';
		$itemHtml .= '</div>';

		return [
			'value' => $eAsset['id'],
			'description' => $eAsset['description'],
			'farm' => $farm,
			'itemHtml' => $itemHtml,
			'itemText' => $eAsset['accountLabel'].' '.$eAsset['description']
		];

	}

	public function getAcquisitionTable(\farm\Farm $eFarm, \Collection $cAsset, string $type): string {

		if($cAsset->empty() === TRUE) {

				return '<div class="util-info">'.s("Aucune écriture n'a encore été enregistrée").'</div>';

		}

		$h = '<div class="stick-sm util-overflow-sm ">';

			$h .= '<table class="tr-even td-vertical-top tr-hover">';

				$h .= '<thead class="thead-sticky">';
					$h .= '<tr>';
						$h .= '<th class="" rowspan="2">'.s("Compte").'</th>';
						$h .= '<th class="" rowspan="2">'.s("Libellé").'</th>';
						$h .= '<th class="text-center" colspan="2">'.s("Type").'</th>';
						$h .= '<th class="text-center" rowspan="2">'.s("Durée (en mois)").'</th>';
						$h .= '<th class="text-center" rowspan="2">'.s("Date").'</th>';
						$h .= '<th class="text-end highlight-stick-left" rowspan="2">'.s("Valeur").'</th>';
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<th class="text-center">'.s("Eco").'</th>';
						$h .= '<th class="text-center">'.s("Fiscal").'</th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

				$total = 0;
					foreach($cAsset as $eAsset) {
						$h .= '<tr>';
							$h .= '<td>';
								$h .= '<div data-dropdown="bottom" data-dropdown-hover="true">';
									$h .= encode($eAsset['accountLabel']);
								$h .= '</div>';
								$h .= '<div class="dropdown-list bg-primary">';
									$h .= '<span class="dropdown-item">'.encode($eAsset['account']['class']).' '.encode($eAsset['account']['description']).'</span>';
								$h .= '</div>';
							$h .= '</td>';
							$h .= '<td><a href="'.\company\CompanyUi::urlAsset($eFarm).'/'.$eAsset['id'].'/">'.encode($eAsset['description']).'</a></td>';
							$h .= '<td class="text-center">';
								$h .= match($eAsset['economicMode']) {
									AssetElement::LINEAR => s("LIN"),
									AssetElement::WITHOUT => s("SANS"),
								};
							$h .= '</td>';
							$h .= '<td class="text-center">';
								$h .= match($eAsset['fiscalMode']) {
									AssetElement::LINEAR => s("LIN"),
									AssetElement::WITHOUT => s("SANS"),
								};
							$h .= '</td>';
							$h .= '<td class="text-center">';
								$h .= $eAsset['economicDuration'];
							$h .= '</td>';
							$h .= '<td class="text-center">'.\util\DateUi::numeric($eAsset['startDate'], \util\DateUi::DATE).'</td>';
							$h .= '<td class="text-end highlight-stick-left">'.$this->number($eAsset['value'], '', 2).'</td>';

						$h .= '</tr>';
						$total += $eAsset['value'];
					}
					$h .= '<tr class="row-bold">';
						$h .= '<td></td>';
						$h .= '<td>';
							$h .= match($type) {
								'asset' => s("Total immobilisations"),
								'subvention' => s("Total subventions"),
							};
						$h .= '</td>';
						$h .= '<td></td>';
						$h .= '<td></td>';
						$h .= '<td></td>';
						$h .= '<td></td>';
						$h .= '<td class="text-end">'.$this->number($total, '', 2).'</td>';
					$h .= '</tr>';

				$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}
	public static function getSummary(\account\FinancialYear $eFinancialYear, array $assetSummary): string {

		$h = '';

		if($eFinancialYear['status'] === \account\FinancialYearElement::OPEN) {

			$h .= '<div class="util-warning">';
				$h .= s("L'exercice comptable n'étant pas terminé, les données affichées sont une projection de l'actuel à la fin de l'exercice.");
			$h .= '</div>';

		}

		$h .= '<div class="stick-sm util-overflow-sm">';

			$h .= '<table class="tr-even td-vertical-top tr-hover table-bordered">';

				$h .= '<thead class="thead-sticky">';
					$h .= '<tr class="row-bold">';
						$h .= '<th colspan="2" class="text-center">'.s("Caractéristiques").'</th>';
						$h .= '<th colspan="5" class="text-center">'.s("Valeurs brutes").'</th>';
						$h .= '<th colspan="6" class="text-center">'.s("Amortissements économiques").'</th>';
						$h .= '<th rowspan="4" class="text-center">'.s("VNC").'</th>';
						$h .= '<th colspan="4" class="text-center">'.s("Amortissements dérogatoires").'</th>';
						$h .= '<th rowspan="4" class="text-center">'.s("VNF").'</th>';
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<th rowspan="3" colspan="2" class="text-center border-bottom">'.s("Libellé").'</th>';
						$h .= '<th rowspan="3" class="text-center">'.s("Valeur début").'</th>';
						$h .= '<th rowspan="3" class="text-center">'.s("Acquis. ou apport").'</th>';
						$h .= '<th rowspan="3" class="text-center">'.s("Diminution poste à p.").'</th>';
						$h .= '<th rowspan="3" class="text-center">'.s("Sortie d'actif").'</th>';
						$h .= '<th rowspan="3" class="text-center">'.s("Val. fin d'exercice").'</th>';
						$h .= '<th rowspan="3" class="text-center">'.s("Début d'exercice").'</th>';
						$h .= '<th colspan="3" class="text-center">'.s("Augmentation (dotation de l'exercice)").'</th>';
						$h .= '<th rowspan="3" class="text-center">'.s("Diminution").'</th>';
						$h .= '<th rowspan="3" class="text-center">'.s("Fin exercice").'</th>';
						$h .= '<th rowspan="3" class="text-center">'.s("Début exercice").'</th>';
						$h .= '<th rowspan="3" class="text-center">'.s("Dotation").'</th>';
						$h .= '<th rowspan="3" class="text-center">'.s("Reprise").'</th>';
						$h .= '<th rowspan="3" class="text-center">'.s("Fin exercice").'</th>';
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<th rowspan="2" class="text-center no-border-left">'.s("Global").'</th>';
						$h .= '<th colspan="2" class="text-center">'.s("dont :").'</th>';
					$h .= '</tr>';
						$h .= '<th class="text-center no-border-left">'.s("Linéaire").'</th>';
						$h .= '<th class="text-center">'.s("Dégressif").'</th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';
					foreach($assetSummary as $asset) {
						$h .= '<tr>';
							$h .= '<td>'.encode($asset['accountLabel']).'</td>';
							$h .= '<td>'.encode($asset['description'] ?? '').'</td>';

							$h .= '<td class="util-unit text-end">'.new AssetUi()->number(0, '', 2) .'</td>';
							$h .= '<td class="util-unit text-end">'.new AssetUi()->number($asset['acquisitionValue'], '', 2).'</td>';
							$h .= '<td class="util-unit text-end">'.new AssetUi()->number(0, '', 2).'</td>';
							$h .= '<td class="util-unit text-end">'.new AssetUi()->number(0, '', 2).'</td>';
							$h .= '<td class="util-unit text-end">'.new AssetUi()->number($asset['acquisitionValue'], '', 2).'</td>';

							$h .= '<td class="util-unit text-end">'.new AssetUi()->number($asset['economic']['startFinancialYearValue'], '', 2).'</td>';
							$h .= '<td class="util-unit text-end">'.new AssetUi()->number($asset['economic']['currentFinancialYearDepreciation'] + $asset['economic']['currentFinancialYearDegressiveDepreciation'], '', 2).'</td>';
							$h .= '<td class="util-unit text-end">'.new AssetUi()->number($asset['economic']['currentFinancialYearDepreciation'], '', 2).'</td>';
							$h .= '<td class="util-unit text-end">'.new AssetUi()->number($asset['economic']['currentFinancialYearDegressiveDepreciation'], '', 2).'</td>';
							$h .= '<td class="util-unit text-end">'.new AssetUi()->number($asset['economic']['financialYearDiminution'], '', 2).'</td>';
							$h .= '<td class="util-unit text-end">'.new AssetUi()->number($asset['economic']['endFinancialYearValue'], '', 2).'</td>';

							$h .= '<td class="util-unit text-end">'.new AssetUi()->number($asset['netFinancialValue'], '', 2).'</td>';

							$h .= '<td class="util-unit text-end">'.new AssetUi()->number($asset['excess']['startFinancialYearValue'], '', 2).'</td>';
							$h .= '<td class="util-unit text-end">'.new AssetUi()->number($asset['excess']['currentFinancialYearDepreciation'], '', 2).'</td>';
							$h .= '<td class="util-unit text-end">'.new AssetUi()->number($asset['excess']['reversal'], '', 2).'</td>';
							$h .= '<td class="util-unit text-end">'.new AssetUi()->number($asset['excess']['endFinancialYearValue'], '', 2).'</td>';

							$h .= '<td class="util-unit text-end">'.new AssetUi()->number($asset['fiscalNetValue'], '', 2).'</td>';

						$h .= '</tr>';
					}
				$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</table>';

		return $h;

	}

	private static function getDurationInYears(int $duration): string {

		$years = floor($duration / 12);
		$months = ($duration % 12);

		if($months === 0) {
			return p("{years} an", "{years} ans", $years, ['years' => $years]);
		}
		return p("{years} an et {months} mois", "{years} ans et {months} mois", $years, ['years' => $years, 'months' => $months]);

	}

	private static function getHeader(Asset $eAsset): string {

		$h = '<div class="util-block stick-xs bg-background-light">';
			$h .= '<dl class="util-presentation util-presentation-2">';
				$h .= '<dt>'.s("Compte").'</dt>';
				$h .= '<dd>'.encode($eAsset['accountLabel']).' - <span class="font-sm" style="font-weight: normal;">'.encode($eAsset['account']['description']).'</span></dd>';
				$h .= '<dt>'.s("Libellé").'</dt>';
				$h .= '<dd>'.encode($eAsset['description']).'</dd>';
				$h .= '<dt>'.s("Date d'acquisition").'</dt>';
				$h .= '<dd>'.\util\DateUi::numeric($eAsset['acquisitionDate'], \util\DateUi::DATE).'</dd>';
				$h .= '<dt>'.s("Date de mise en service").'</dt>';
				$h .= '<dd>'.\util\DateUi::numeric($eAsset['startDate'], \util\DateUi::DATE).'</dd>';
				$h .= '<dt>'.s("Valeur d'acquisition").'</dt>';
				$h .= '<dd>'.\util\TextUi::money($eAsset['value']).'</dd>';
				$h .= '<dt>'.s("Type d'amortissement économique").'</dt>';
				$h .= '<dd>'.AssetUi::p('economicMode')->values[$eAsset['economicMode']].'</dd>';
				$h .= '<dt>'.s("Statut").'</dt>';
				$h .= '<dd>'.self::p('status')->values[$eAsset['status']].'</dd>';
				$h .= '<dt>'.s("Durée").'</dt>';
				$h .= '<dd>'.p("{value} mois" ,"{value} mois", $eAsset['economicDuration']).' ('.self::getDurationInYears($eAsset['economicDuration']).')</dd>';
			$h .= '</dl>';
		$h .= '</div>';

		return $h;

	}

	public static function dispose(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, Asset $eAsset): \Panel {

		\Asset::js('asset', 'asset.js');

		$h = self::getHeader($eAsset);

		$form = new \util\FormUi();
		$statuses = [
			AssetElement::SCRAPPED => s("Mettre au rebut"),
			AssetElement::SOLD => s("Vendre"),
		];

		$h .= '<div class="util-block">';

			$h .= $form->openAjax(
				\company\CompanyUi::urlAsset($eFarm).'/:doDispose',
				['id' => 'panel-asset-dispose']
			);

				$h .= $form->hidden('farm', $eFarm['id']);
				$h .= $form->hidden('id', $eAsset['id']);

				$h .= $form->asteriskInfo();

				$h .= $form->group(
					s("Date de cession").' '.\util\FormUi::asterisk(),
					$form->date('date', date('Y-m-d'), ['min' => $eFinancialYear['startDate'], 'max' => $eFinancialYear['endDate']])
				);

				$h .= $form->group(
					s("Motif de cession").' '.\util\FormUi::asterisk(),
					$form->select('status', $statuses, attributes: ['onchange' => 'Asset.onchangeStatus(this);']),
				);


				$h .= '<div class="hide" type="sold">';
					$h .= $form->group(
						s("Créer une créance").' '.\util\FormUi::asterisk(),
						$form->checkbox('createReceivable').
							'<div class="util-info mt-1">'.s("Une opération bancaire ou une créance devra être créée pour matérialiser la vente. Cochez pour créer une créance.").'</div>',
					);
				$h .= '</div>';

				$h .= $form->group(
					s("Valeur de sortie").' '.\util\FormUi::asterisk(),
					$form->inputGroup($form->number('amount').$form->addon('€ ')).
					'<div class="util-info mt-1 hide" type="sold">'.s(" Attention la vente d’une immobilisation peut être assujettie à TVA. Renseignez-vous auprès de votre expert-comptable ou organisme agréé sur ce point.").'</div>',
				);

				$h .= '<div class="util-info hide" id="dispose-scrap-warning">';
					$h .= s(
					"Attention, si vous percevez une indemnisation suite à un sinistre, cette indemnisation doit être comptabilisée comme le produit d'une vente de votre immobilisation !",
				);
				$h .= '</div>';

				$buttons = $form->submit(
					s("Céder"),
					['id' => 'submit-dispose-asset'],
				);

				$h .= $form->group(content: $buttons);

			$h .= $form->close();

		$h .= '</div>';

		return new \Panel(
			id: 'panel-asset-dispose',
			title: s("Cession de l'immobilisation #{id}", ['id' => $eAsset['id']]),
			body: $h
		);
	}

	public static function view(\farm\Farm $eFarm, Asset $eAsset): \Panel {

		$h = self::getHeader($eAsset);

		$h .= '<div>';
			$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/operations?asset='.$eAsset['id'].'" target="_blank">'.s("Voir toutes les écritures comptables de cette immobilisation pour cet exercice comptable").'&nbsp;'.\Asset::icon('box-arrow-up-right').'</a>';
		$h .= '</div>';

		if($eAsset['status'] === AssetElement::ONGOING) {


			$h .= '<div class="mt-1 mb-1">';
				$h .= '<a href="'.\company\CompanyUi::urlAsset($eFarm).'/:dispose?id='.$eAsset['id'].'" class="btn btn-primary">'.\Asset::icon('box-arrow-right').' '.s("Céder l'immobilisation").'</a>';
			$h .= '</div>';

		}

		$h .= '<h2>'.s("Plan d'amortissement").'</h2>';

			$h .= '<div class="stick-sm util-overflow-sm">';

				$h .= '<table class="tr-even td-vertical-top tr-hover">';

					$h .= '<thead>';
						$h .= '<tr>';
							$h .= '<th class="text-center">'.s("Exercice").'</th>';
							$h .= '<th class="text-end highlight-stick-right">'.s("Base amortissable").'</th>';

								if($eAsset['economicMode'] === Asset::DEGRESSIVE) {

									$h .= '<th class="text-center">'.s("Taux linéaire").'</th>';
									$h .= '<th class="text-center">'.s("Taux dégressif").'</th>';
									$h .= '<th class="text-center">'.s("Taux retenu").'</th>';

								} else {

									$h .= '<th class="text-center">'.s("Taux").'</th>';
								}

							$h .= '<th class="text-end highlight-stick-right">'.s("Amortissement").'</th>';
							$h .= '<th class="text-end highlight-stick-right">'.s("Cumul d'amortissement").'</th>';
							$h .= '<th class="text-end highlight-stick-right">'.s("VNC fin").'</th>';
							$h .= '<th>'.s("Statut").'</th>';
						$h .= '</tr>';
					$h .= '</thead>';

					$h .= '<tbody>';
						foreach($eAsset['table'] as $period) {
							$h .= '<tr class="'.($period['depreciation']->empty() ? 'asset-line-future' : '').'">';
								$h .= '<td class="text-center">';
									$h .= \account\FinancialYearUi::getYear($period['financialYear']);
								$h .= '</td>';
								$h .= '<td class="text-end highlight-stick-right">';
									$h .= \util\TextUi::money($period['base']);
								$h .= '</td>';

								if($eAsset['economicMode'] === Asset::DEGRESSIVE) {

									$h .= '<td class="text-center">';
										$h .= s("{value} %", $period['linearRate']);
									$h .= '</td>';


									$h .= '<td class="text-center">';
										$h .= s("{value} %", $period['degressiveRate']);
									$h .= '</td>';


									$h .= '<td class="text-center">';
										$h .= s("{value} %", $period['rate']);
									$h .= '</td>';

								} else {

									$h .= '<td class="text-center">';
										$h .= s("{value} %", $period['rate']);
									$h .= '</td>';

								}
								$h .= '<td class="text-end highlight-stick-right">';
									$h .= \util\TextUi::money($period['depreciationValue']);
								$h .= '</td>';
								$h .= '<td class="text-end highlight-stick-right">';
									$h .= \util\TextUi::money($period['depreciationValueCumulated']);
								$h .= '</td>';
								$h .= '<td class="text-end highlight-stick-right">';
									$h .= \util\TextUi::money($period['endValue']);
								$h .= '</td>';
								$h .= '<td>';
									if($period['depreciation']->empty()) {
										$h .= s("En attente...");
									} else {
										$h .= s("Comptabilisé le {value} ", \util\DateUi::numeric($period['depreciation']['date']));
									}
								$h .= '</td>';
							$h .= '</tr>';
						}
					$h .= '</tbody>';

				$h .= '</table>';

			$h .= '</div>';

		return new \Panel(
			id: 'panel-asset-view',
			title: s("Immobilisation #{id}", ['id' => $eAsset['id']]),
			body: $h
		);

	}

	public function listGrantsForClosing(\util\FormUi $form, \Collection $cAssetGrant): string {

		if($cAssetGrant->empty()) {
			return '';
		}

		$h = '<h3 class="mt-2">'.s("Reprise finale des subventions").'</h3>';

		$h .= '<div class="util-block-help">';
			$h .= s("Si des subventions n'ont pas été entièrement reprises au compte de résultat alors que l'immobilisation correspondante est totalement amortie, vous pouvez <b>intégrer</b> cette reprise dans l'exercice comptable.");
		$h .= '</div>';

		$h .= '<div class="stick-sm util-overflow-sm">';

			$h .= '<table class="financial-year-stock-table tr-even tr-hover">';

				$h .= '<thead>';

					$h .= '<tr>';

						$h .= '<th>'.s("Subvention").'</th>';
						$h .= '<th>'.s("Immobilisation liée").'</th>';
						$h .= '<th class="text-end">'.s("Reprise déjà faite").'</th>';
						$h .= '<th class="text-end">'.s("Solde à reprendre").'</th>';
						$h .= '<th>'.s("Écriture proposée").'</th>';
						$h .= '<th class="text-center">'.s("Intégrer ?").'</th>';

					$h .= '</tr>';

				$h .= '</thead>';

				$h .= '<tbody>';
					foreach($cAssetGrant as $eAsset) {

						$h .= '<tr id="'.$eAsset['id'].'">';

						$h .= '<td>'.encode($eAsset['description']).'</td>';
						$h .= '<td>'.encode($eAsset['asset']['description']).'</td>';
						$h .= '<td class="text-end">'.\util\TextUi::money($eAsset['alreadyRecognized']).'</td>';
						$h .= '<td class="text-end">'.\util\TextUi::money($eAsset['value'] - $eAsset['alreadyRecognized']).'</td>';
						$h .= '<td>'.s("Débit {accountDebit} / Crédit {accountCredit}", [
							'accountDebit' => encode($eAsset['account']['class']),
							'accountCredit' => \account\AccountSetting::GRANT_ASSET_AMORTIZATION_CLASS,
						]).'</td>';
						$h .= '<td class="text-center">';
							$h .= $form->checkbox('grantsToRecognize[]', $eAsset['id']);
						$h .= '</td>';

						$h .= '</tr>';

					}

				$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public function getFinalRecognitionTranslation(): string {
		return s("Reprise finale de subvention");
	}

	public static function p(string $property): \PropertyDescriber {

		$d = Asset::model()->describer($property, [
			'account' => s("Classe de compte"),
			'accountLabel' => s("Compte"),
			'value' => s("Valeur (HT)"),
			'depreciableBase' => s("Base amortissable (HT)"),
			'type' => s("Type d'amortissement"),
			'economicMode' => s("Mode économique"),
			'economicDuration' => s("Durée économique (en mois)"),
			'fiscalMode' => s("Mode fiscal"),
			'fiscalDuration' => s("Durée fiscale (en mois)"),
			'mode' => s("Mode"),
			'acquisitionDate' => s("Date d'acquisition"),
			'startDate' => s("Date de mise en service"),
			'duration' => s("Durée (en années)"),
			'status' => s("Statut"),
			'endDate' => s('Date de fin'),
			'description' => s('Libellé'),
			'grant' => s('Subvention'),
			'asset' => s('Immobilisation liée'),
		]);

		switch($property) {

			case 'account':
				$d->autocompleteBody = function(\util\FormUi $form, Asset $e) {
					return [
					];
				};
				$d->group += ['wrapper' => 'account'];
				new \account\AccountUi()->query($d, GET('farm', '?int'), query: ['classPrefixes' => [\account\AccountSetting::ASSET_GENERAL_CLASS, \account\AccountSetting::GRANT_ASSET_CLASS]]);
				break;

			case 'accountLabel':
				$d->autocompleteBody = function(\util\FormUi $form, Asset $e) {
					return [
					];
				};
				$d->group += ['wrapper' => 'accountLabel'];
				new \account\AccountUi()->queryLabel($d, GET('farm', '?int'), query: GET('query'));
				break;

			case 'acquisitionDate' :
			case 'startDate' :
				$d->prepend = \Asset::icon('calendar-date');
				break;

			case 'value':
			case 'depreciableBase':
				$d->field = 'calculation';
				$d->append = function(\util\FormUi $form, Asset $e) {
					return $form->addon(s("€"));
				};
				break;

			case 'type':
			case 'economicMode':
			case 'fiscalMode':
				$d->values = [
					AssetElement::LINEAR => s("Linéaire"),
					AssetElement::DEGRESSIVE => s("Dégressif"),
					AssetElement::WITHOUT => s("Sans"),
				];
				break;

			case 'status':
				$d->values = [
					AssetElement::ONGOING => s("En cours"),
					AssetElement::SCRAPPED => s("Mis au rebut"),
					AssetElement::SOLD => s("Vendu"),
					AssetElement::ENDED => s("Terminé"),
				];
				break;

		}

		return $d;

	}
}
?>
