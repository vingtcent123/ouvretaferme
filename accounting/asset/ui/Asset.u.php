<?php
namespace asset;

Class AssetUi {

	public function __construct() {
		\Asset::css('company', 'company.css');
		\Asset::css('asset', 'asset.css');
		\Asset::js('asset', 'asset.js');
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

	public function createOrUpdate(\farm\Farm $eFarm, \Collection $cFinancialYear, Asset $eAsset, \Collection $cOperation, \Collection $cAmortizationDuration): \Panel {

		$script = '<script type="text/javascript">';
			$script .= 'Asset.initFiscalDurations('.json_encode($cAmortizationDuration).', '.AssetSetting::AMORTIZATION_DURATION_TOLERANCE.')';
		$script .= '</script>';

		$form = new \util\FormUi();

		$h = $script;

		if($eAsset->exists()) {
			$page = 'doUpdate';
		} else {
			$page = 'doCreate';
		}

		$h .= $form->openAjax(\company\CompanyUi::urlAsset($eFarm).'/:'.$page, ['id' => 'asset-asset-create', 'autocomplete' => 'off']);

		$h .= $form->asteriskInfo();

		if($eAsset->exists()) {

			$h .= $form->hidden('id', $eAsset['id']);

		} else if($cOperation->notEmpty()) {

			foreach($cOperation as $eOperation) {
				$h .= $form->hidden('operations[]', $eOperation['id']);
				$eAsset['account'] = $eOperation['account'];
				$eAsset['accountLabel'] = $eOperation['accountLabel'];
				$eAsset['description'] = $eOperation['description'];
				$eAsset['acquisitionDate'] = $eOperation['date'];
				$eAsset['startDate'] = $eOperation['date'];
			}
			$eAsset['value'] = $cOperation->sum('amount');
		}

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

		$h .= $form->dynamicGroups($eAsset, ['value*', 'residualValue', 'acquisitionDate*', 'startDate']);

		$h .= '<div class="mb-1"><a onclick="Asset.showAlreadyAmortizePart();" class="color-muted font-md" data-already-amortize-icon>';
			$h .= \Asset::icon('chevron-down', ['class' => 'hide']).\Asset::icon('chevron-right');
			$h .= ' ';
			$h .= s("L'amortissement de cette immobilisation a déjà commencé");
		$h .= '</a></div>';
		$h .= '<div data-already-amortize-part class="hide">';
			$h .= '<h3>'.s("Réintégration de l'immobilisation").'</h3>';
			$h .= $form->group(
				s("Exercice"),
				$form->select(
					'resumeDate',
					$cFinancialYear->toArray(fn($e) => [
						'value' => $e['startDate'], 'label' => s("Exercice {value}", $e->getLabel())
					])
				).\util\FormUi::info(s("Exercice à partir duquel réintégrer l'immobilisation dans {siteName}"))
			);
		$h .= '</div>';
		$h .= '<div id="amortization-duration-recommandation" class="util-block-help mt-2 '.(($eAsset->exists() or $cOperation->notEmpty()) ? '' : 'hide').'" data-url="'.\company\CompanyUi::urlFarm($eFarm).'/asset/:getRecommendedDuration">';
			if($eAsset->exists()) {
				$h .= $this->getDurationRecommandation($eAsset['accountLabel'], $cAmortizationDuration);
			} else if($cOperation->notEmpty()) {
				$h .= $this->getDurationRecommandation($cOperation->first()['accountLabel'], $cAmortizationDuration);
			}
		$h .= '</div>';

		$h .= '<h3>'.s("Amortissement économique").'</h3>';
		$h .= '<div class="util-block bg-background-light">';
			$h .= $form->dynamicGroups($eAsset, ['economicMode*', 'economicDuration'], [
				'economicDuration' => function($d) use($form) {
					$d->group += ['class' => 'company_form_group-with-tip'];
					$append = '<a data-economic-duration-suggested class="btn btn-outline-warning hide" data-dropdown="bottom" data-dropdown-hover="true">';
						$append .= \Asset::icon('exclamation-triangle');
					$append .= '</a>';
					$append .= '<div class="dropdown-list bg-primary dropdown-list-bottom">';
						$append .= '<span class="dropdown-item">';
							$append .= '<span data-suggestion-one-year class="hide">'.s("La durée recommandée par l'administration fiscale est <br />de {minYear} ans, soit, avec la marge de tolérance, <br /> entre <b>{minMois} et {maxMois}</b> mois. Il y aura donc des amortissements <br />dérogatoires pour cette immobilisation.", [
								'minYear' => '<span data-min-year></span>',
								'minMois' => '<span data-min-month></span>',
								'maxMois' => '<span data-max-month></span>',
							]).'</span>';
							$append .= '<span data-suggestion-several-years class="hide">'.s("La durée recommandée par l'administration fiscale est comprise <br />entre {minYear} et {maxYear} ans, soit, avec la marge de tolérance, <br />entre <b>{minMois} et {maxMois}</b> mois. Il y aura donc des amortissements <br />dérogatoires pour cette immobilisation.", [
								'minYear' => '<span data-min-year></span>',
								'maxYear' => '<span data-max-year></span>',
								'minMois' => '<span data-min-month></span>',
								'maxMois' => '<span data-max-month></span>',
							]).'</span>';
						$append .= '</span>';
					$append .= '</div>';
					$d->after = $append;
				}
			]);
		$h .= '</div>';

		$h .= '<h3>'.s("Amortissement fiscal").'</h3>';
		$h .= '<div class="util-block bg-background-light">';
			$h .= $form->dynamicGroups($eAsset, ['fiscalMode*', 'fiscalDuration']);
		$h .= '</div>';

		if($eAsset->exists()) {
			$text = s("Enregistrer");
		} else {
			$text = s("Créer l'immobilisation");
		}

		$h .= $form->group(
			content: $form->submit($text)
		);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-asset-create',
			title: s("Ajouter une immobilisation ou une subvention"),
			body: $h,
			close: 'passthrough',
		);

	}

	public function getDurationRecommandation(string $accountLabel, \Collection $cAmortizationDuration): string {

		foreach($cAmortizationDuration as $eAmortizationDuration) {

			if(\account\AccountLabelLib::isFromClass($accountLabel, $eAmortizationDuration['class'])) {

				$minYear = $eAmortizationDuration['durationMin'];
				$maxYear = $eAmortizationDuration['durationMax'];
				$minMonth = $minYear * 12 * (1 - AssetSetting::AMORTIZATION_DURATION_TOLERANCE);
				$maxMonth = $maxYear * 12 * (1 + AssetSetting::AMORTIZATION_DURATION_TOLERANCE);

				if($minYear === $maxYear) {

					return s(
						"La durée recommandée d'amortissement pour les immobilisations avec le numéro de compte {account} est de <b>{year} ans</b> (soit {minMonth} mois à {maxMonth} mois avec un écart de {tolerance}%).", [
						'year' => $minYear,
						'minMonth' => $minMonth,
						'maxMonth' => $maxMonth,
						'account' => '<b>'.$accountLabel.'</b>',
						'tolerance' => AssetSetting::AMORTIZATION_DURATION_TOLERANCE * 100,
					]);

				}

				return s(
					"La durée recommandée d'amortissement pour les immobilisations avec le numéro de compte {account} est de <b>{minYear} à {maxYear} ans</b> (soit {minMonth} mois à {maxMonth} mois avec un écart de {tolerance}%).", [
					'minYear' => $minYear,
					'maxYear' => $maxYear,
					'minMonth' => $minMonth,
					'maxMonth' => $maxMonth,
					'account' => '<b>'.$accountLabel.'</b>',
					'tolerance' => AssetSetting::AMORTIZATION_DURATION_TOLERANCE * 100,
				]);
			}

		}

		return '';
	}

	public function query(\PropertyDescriber $d, \farm\Farm $eFarm, bool $multiple = FALSE, array $query = []): void {

		$d->prepend = \Asset::icon('house');
		$d->field = 'autocomplete';

		$d->placeholder ??= s("Commencez à saisir l'immobilisation...");
		$d->multiple = $multiple;

		$d->autocompleteUrl = \company\CompanyUi::urlAsset($eFarm).'/:query?'.http_build_query($query);
		$d->autocompleteResults = function(Asset $e) use ($eFarm) {
			return self::getAutocomplete($eFarm, $e);
		};

	}

	public static function getAutocomplete(\farm\Farm $eFarm, Asset $eAsset, \Search $search = new \Search()): array {

		\Asset::css('media', 'media.css');

		$cumulatedAmortization = $eAsset['cAmortization']->sum('amount');
		$itemHtml = '<div>';
			$itemHtml .= encode($eAsset['accountLabel'].' ' .$eAsset['description']);
			$itemHtml .= '<div class="ml-1 color-muted font-sm">'.s("Date d'acquisition : {value}", \util\DateUi::numeric($eAsset['acquisitionDate'])).'</div>';
			if($eAsset['endedDate'] !== NULL) {
				$itemHtml .= '<div class="ml-1 color-muted font-sm">'.s("Fin d'amortissement : {value}", \util\DateUi::numeric($eAsset['endedDate'])).'</div>';
			}
			$itemHtml .= '<div class="ml-1 color-muted font-sm">'.s("Val. : {value} / Base amortissable : {base} / Amort. cumulés : {cumulated}", ['value' => \util\TextUi::money($eAsset['value']), 'base' => \util\TextUi::money(AssetLib::getAmortizableBase($eAsset, 'economic')), 'cumulated' => \util\TextUi::money($cumulatedAmortization)]).'</div>';
		$itemHtml .= '</div>';

		return [
			'value' => $eAsset['id'],
			'description' => $eAsset['description'],
			'farm' => $eFarm['id'],
			'itemHtml' => $itemHtml,
			'itemText' => $eAsset['accountLabel'].' '.$eAsset['description']
		];

	}

	public function getAcquisitionTable(\farm\Farm $eFarm, \Collection $cAsset, string $type): string {

		if($cAsset->empty() === TRUE) {

				return '<div class="util-empty">'.s("Aucune écriture n'a encore été enregistrée").'</div>';

		}

		$h = '<div class="stick-sm util-overflow-sm ">';

			$h .= '<table class="tr-even td-vertical-top tr-hover">';

				$h .= '<thead class="thead-sticky">';
					$h .= '<tr>';
						$h .= '<th class="" rowspan="2">'.self::p('accountLabel')->label.'</th>';
						$h .= '<th class="" rowspan="2">'.self::p('description')->label.'</th>';
						$h .= '<th class="text-center" colspan="2">'.s("Type").'</th>';
						$h .= '<th class="text-center" rowspan="2">'.s("Durée (en mois)").'</th>';
						$h .= '<th class="text-center" rowspan="2">'.s("Date").'</th>';
						$h .= '<th class="text-end highlight-stick-left" rowspan="2">'.s("Valeur d'acquisition").'</th>';
						$h .= '<th class="text-end highlight-stick-left" rowspan="2">'.s("Base amortissable").'</th>';
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<th class="text-center">'.s("Eco").'</th>';
						$h .= '<th class="text-center">'.s("Fiscal").'</th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

					$total = 0;
					$totalAmortizable = 0;

					foreach($cAsset as $eAsset) {

						$amortizableBase = AssetLib::getAmortizableBase($eAsset, 'economic');

						$h .= '<tr>';
							$h .= '<td>';
								$h .= '<div data-dropdown="bottom" data-dropdown-hover="true">';
									$h .= encode($eAsset['accountLabel']);
								$h .= '</div>';
								$h .= '<div class="dropdown-list bg-primary">';
									$h .= '<span class="dropdown-item">'.encode($eAsset['account']['class']).' '.encode($eAsset['account']['description']).'</span>';
								$h .= '</div>';
							$h .= '</td>';
							$h .= '<td><a href="'.\company\CompanyUi::urlFarm($eFarm).'/immobilisation/'.$eAsset['id'].'/">'.encode($eAsset['description']).'</a></td>';
							$h .= '<td class="text-center">';
								$h .= match($eAsset['economicMode']) {
									AssetElement::LINEAR => s("LIN"),
									AssetElement::DEGRESSIVE => s("DEG"),
									AssetElement::WITHOUT => s("SANS"),
								};
							$h .= '</td>';
							$h .= '<td class="text-center">';
								$h .= match($eAsset['fiscalMode']) {
									AssetElement::LINEAR => s("LIN"),
									AssetElement::DEGRESSIVE => s("DEG"),
									AssetElement::WITHOUT => s("SANS"),
								};
							$h .= '</td>';
							$h .= '<td class="text-center">';
								$h .= $eAsset['economicDuration'];
							$h .= '</td>';
							$h .= '<td class="text-center">'.\util\DateUi::numeric($eAsset['startDate'], \util\DateUi::DATE).'</td>';
							$h .= '<td class="text-end highlight-stick-left">'.$this->number($eAsset['value'], '', 2).'</td>';
							$h .= '<td class="text-end highlight-stick-left">'.$this->number($amortizableBase, '', 2).'</td>';

						$h .= '</tr>';
						$total += $eAsset['value'];
						$totalAmortizable += $amortizableBase;

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
						$h .= '<td class="text-end highlight-stick-left">'.$this->number($total, '', 2).'</td>';
						$h .= '<td class="text-end highlight-stick-left">'.$this->number($totalAmortizable, '', 2).'</td>';
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
						$h .= '<th colspan="4" class="text-center">'.s("Valeurs brutes").'</th>';
						$h .= '<th colspan="6" class="text-center">'.s("Amortissements économiques").'</th>';
						$h .= '<th rowspan="4" class="text-center">'.s("VNC").'</th>';
						$h .= '<th colspan="4" class="text-center">'.s("Amortissements dérogatoires").'</th>';
						$h .= '<th rowspan="4" class="text-center">'.s("VNF").'</th>';
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<th rowspan="3" colspan="2" class="text-center border-bottom">'.s("Libellé").'</th>';
						$h .= '<th rowspan="3" class="text-center">'.s("Valeur début").'</th>';
						$h .= '<th rowspan="3" class="text-center">'.s("Acquis. ou apport").'</th>';
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
							$h .= '<td class="util-unit text-end">'.new AssetUi()->number($asset['acquisitionValue'], '', 2).'</td>';

							$h .= '<td class="util-unit text-end">'.new AssetUi()->number($asset['economic']['startFinancialYearValue'], '', 2).'</td>';
							$h .= '<td class="util-unit text-end">'.new AssetUi()->number($asset['economic']['currentFinancialYearAmortization'] + $asset['economic']['currentFinancialYearDegressiveAmortization'], '', 2).'</td>';
							$h .= '<td class="util-unit text-end">'.new AssetUi()->number($asset['economic']['currentFinancialYearAmortization'], '', 2).'</td>';
							$h .= '<td class="util-unit text-end">'.new AssetUi()->number($asset['economic']['currentFinancialYearDegressiveAmortization'], '', 2).'</td>';
							$h .= '<td class="util-unit text-end">'.new AssetUi()->number($asset['economic']['endFinancialYearValue'], '', 2).'</td>';

							$h .= '<td class="util-unit text-end">'.new AssetUi()->number($asset['netFinancialValue'], '', 2).'</td>';

							$h .= '<td class="util-unit text-end">'.new AssetUi()->number($asset['excess']['startFinancialYearValue'], '', 2).'</td>';
							$h .= '<td class="util-unit text-end">'.new AssetUi()->number($asset['excess']['currentFinancialYearAmortization'], '', 2).'</td>';
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

		$amortizationCumulated = 0;

		if($eAsset['cAmortization']->count() > 0) {

			$eFinancialYearLast = $eAsset['cAmortization']->first()->notEmpty() ? $eAsset['cAmortization'][0]['financialYear'] : new \account\FinancialYear();

			foreach($eAsset['cAmortization'] as $eAmortization) {

				if($eAmortization['financialYear']->notEmpty() and $eAmortization['financialYear']['endDate'] > $eFinancialYearLast) {
					$eFinancialYearLast = $eAmortization['financialYear'];
				}

				$amortizationCumulated += $eAmortization['amount'];

			}

		} else { // Acquisition de l'année

			$eFinancialYearLast = new \account\FinancialYear();

		}

		$amortizableBase = AssetLib::getAmortizableBase($eAsset, 'economic');

		$h = '<div class="util-block stick-xs bg-background-light">';
			$h .= '<dl class="util-presentation util-presentation-2">';
				$h .= '<dt>'.s("Numéro de compte").'</dt>';
				$h .= '<dd>'.encode($eAsset['accountLabel']).' - <span class="font-sm" style="font-weight: normal;">'.encode($eAsset['account']['description']).'</span></dd>';
				$h .= '<dt>'.self::p('description')->label.'</dt>';
				$h .= '<dd>'.encode($eAsset['description']).'</dd>';
				$h .= '<dt>'.self::p('acquisitionDate')->label.'</dt>';
				$h .= '<dd>'.\util\DateUi::numeric($eAsset['acquisitionDate'], \util\DateUi::DATE).'</dd>';
				$h .= '<dt>'.self::p('startDate')->label.'</dt>';
				$h .= '<dd>'.\util\DateUi::numeric($eAsset['startDate'], \util\DateUi::DATE).'</dd>';
				$h .= '<dt>'.self::p('value')->label.'</dt>';
				$h .= '<dd>'.\util\TextUi::money($eAsset['value']).'</dd>';
				$h .= '<dt>'.self::p('amortizableBase')->label.'</dt>';
				$h .= '<dd>'.\util\TextUi::money($eAsset['value']).'</dd>';
				$h .= '<dt>'.self::p('economicMode')->label.'</dt>';
				$h .= '<dd>';
					$h .= AssetUi::p('economicMode')->values[$eAsset['economicMode']];
					if($eAsset['isExcess']) {
						$h .= ' ('.s("avec amortissement dérogatoire").')';
					}
				$h .= '</dd>';
				$h .= '<dt>'.self::p('residualValue')->label.'</dt>';
				$h .= '<dd>'.\util\TextUi::money($eAsset['residualValue']).'</dd>';
				$h .= '<dt>'.self::p('economicDuration')->label.'</dt>';
				$h .= '<dd>'.p("{value} mois" ,"{value} mois", $eAsset['economicDuration']).' ('.s("soit {value}", self::getDurationInYears($eAsset['economicDuration'])).')</dd>';
				$h .= '<dt>'.self::p('status')->label.'</dt>';
				$h .= '<dd>';
					$h .= self::p('status')->values[$eAsset['status']];
					if($eAsset['endedDate'] !== NULL) {
						$h .= ' '.s("le {value}", \util\DateUi::numeric($eAsset['endedDate']));
					}
				$h .= '</dd>';
				if($eAsset['endedDate'] !== NULL) {
					$h .= '<dt>'.s("Valeur nette comptable au {value}", \util\DateUi::numeric($eAsset['endedDate'])).'</dt>';
					$h .= '<dd>'.\util\TextUi::money(round($amortizableBase - $amortizationCumulated)).'</dd>';
				} elseif($eFinancialYearLast->notEmpty()) {
					$h .= '<dt>'.s("Valeur nette comptable au {value}", \util\DateUi::numeric($eFinancialYearLast['endDate'])).'</dt>';
					$h .= '<dd>'.\util\TextUi::money(round($amortizableBase - $amortizationCumulated)).'</dd>';
				}
			$h .= '</dl>';
		$h .= '</div>';

		return $h;

	}

	public static function dispose(\farm\Farm $eFarm, Asset $eAsset): \Panel {

		\Asset::js('asset', 'asset.js');

		$h = self::getHeader($eAsset);

		$form = new \util\FormUi();
		$statuses = [
			AssetElement::SCRAPPED => s("Mettre au rebut"),
			AssetElement::SOLD => s("Vendre"),
		];

		$assetAccountLabel = rtrim($eAsset['accountLabel'], '0');
		$amortizationAccountLabel = \account\AccountLabelLib::getAmortizationClassFromClass($assetAccountLabel);
		$amortizableBase = AssetLib::getAmortizableBase($eAsset, 'economic');

		$amortizationCumulated = $eAsset['cAmortization']->sum('amount');

		$h .= '<div class="util-info">';
			if($amortizationCumulated < $amortizableBase) {
				$h .= s("En validant ce formulaire, deux mouvements seront créés automatiquement : ");
			} else {
				$h .= s("En validant ce formulaire, un mouvement sera créée automatiquement : ");
			}

			$h .= '<ul>';
				if($amortizationCumulated < $amortizableBase) {
					$h .= '<li>';
						$h .= s("Écritures de la <b>dotation complémentaire</b> jusqu'à la date de sortie de l'immobilisation (débit 681, crédit {value})", $amortizationAccountLabel);
					$h .= '</li>';
				}
				$h .= '<li>';
					$h .= s("Écritures de la <b>sortie de l'immobilisation</b> du patrimoine (débit {debit}, débit 657,  crédit {credit})", ['debit' => $amortizationAccountLabel, 'credit' => $assetAccountLabel]);
				$h .= '</li>';
				$h .= '</li>';
			$h .= '</ul>';

		$h .= '</div>';

		$h .= '<div class="util-info hide" type="sold">';
		$h .= '<h2>'.\Asset::icon('exclamation-octagon').' '.s("Point de vigilance").'</h2>';
			$h .= s("Dans le cas d'une vente de l'immobilisation, les écritures de cession doivent être enregistrées dans le livre-journal. Il s'agit de :");
			$h .= '<ul>';
				$h .= '<li>';
					$h .= s("<b>débiter</b> le compte 462 (créance sur cession d'immobilisation) ou le compte 512 (banque) ou le compte 411 (client),");
				$h .= '</li>';
				$h .= '<li>';
					$h .= s("<b>créditer</b> le compte 757 (produit de cession d'immobilisation) au montant HT,");
				$h .= '</li>';
				$h .= '<li>';
					$h .= s("<b>créditer</b> le compte 44571 (TVA collectée) du montant de la TVA correspondante.");
				$h .= '</li>';
			$h .= '</ul>';
		$h .= '</div>';

		$h .= '<div class="util-info hide" id="dispose-scrap-warning">';
			$h .= s(
			"Attention, si vous percevez une indemnisation suite à un sinistre, cette indemnisation doit être comptabilisée comme le produit d'une vente de votre immobilisation : créditer le compte 757 (produit de cession d'immobilisation) et débiter le compte 512 (banque)",
		);
		$h .= '</div>';

		$h .= '<div class="util-block">';

			$dialogOpen = $form->openAjax(
				\company\CompanyUi::urlAsset($eFarm).'/:doDispose',
				[
					'id' => 'panel-asset-dispose',
					'class' => 'panel-dialog',
				]
			);

				$h .= $form->hidden('farm', $eFarm['id']);
				$h .= $form->hidden('id', $eAsset['id']);

				$h .= $form->asteriskInfo();

				$h .= $form->group(
					s("Date de cession").' '.\util\FormUi::asterisk(),
					$form->date('endedDate', date('Y-m-d'))
				);

				$h .= $form->group(
					s("Motif de cession").' '.\util\FormUi::asterisk(),
					$form->select('status', $statuses, attributes: ['onchange' => 'Asset.onchangeStatus(this);']),
				);

				$footer = '<div class="text-end">'.$form->submit(
					s("Céder"),
					['id' => 'submit-dispose-asset'],
				).'</div>';

			$h .= $form->close();

		$h .= '</div>';

		return new \Panel(
			id: 'panel-asset-dispose',
			title: s("Cession de l'immobilisation #{id}", ['id' => $eAsset['id']]),
			body: $h,
			dialogOpen : $dialogOpen,
			dialogClose: $form->close(),
			footer: $footer,
		);
	}

	public static function view(\farm\Farm $eFarm, Asset $eAsset): \Panel {

		$h = self::getHeader($eAsset);

		if($eAsset['status'] === AssetElement::ONGOING) {

			$h .= '<div class="mt-1 mb-1">';

				if($eAsset->acceptUdpate()) {
					$h .= '<a href="'.\company\CompanyUi::urlAsset($eFarm).'/:update?id='.$eAsset['id'].'" class="btn btn-primary mr-1">'.s("Modifier l'immobilisation").'</a>';
					$h .= '<a data-ajax="'.\company\CompanyUi::urlAsset($eFarm).'/:doDelete" post-id='.$eAsset['id'].'" class="btn btn-danger mr-1" data-confirm="'.s("Confirmez-vous la suppression ? La ou les écritures comptables liées n'auront plus de fiche d'immobilisation.").'">';
						$h .= \Asset::icon('trash').' '.s("Supprimer l'immobilisation");
					$h .= '</a>';
				}

				$h .= '<a href="'.\company\CompanyUi::urlAsset($eFarm).'/:dispose?id='.$eAsset['id'].'" class="btn btn-primary">'.\Asset::icon('box-arrow-right').' '.s("Céder l'immobilisation").'</a>';
			$h .= '</div>';

		}

		$h .= '<h3 class="mt-2">'.s("Plan d'amortissement").'</h3>';

		$h .= '<div class="stick-sm util-overflow-sm">';

			$h .= '<table class="tr-even td-vertical-top tr-hover">';

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th class="text-center">'.s("Exercice").'</th>';
						$h .= '<th class="text-end highlight-stick-right">'.self::p('amortizableBase')->label.'</th>';

							if($eAsset['economicMode'] === Asset::DEGRESSIVE) {

								$h .= '<th class="text-center">'.s("Taux linéaire").'</th>';
								$h .= '<th class="text-center">'.s("Taux dégressif").'</th>';
								$h .= '<th class="text-center">'.s("Taux retenu").'</th>';

							} else {

								$h .= '<th class="text-center">'.s("Taux").'</th>';
							}

						$h .= '<th class="text-end highlight-stick-right">'.s("Amortissement éco").'</th>';
						$h .= '<th class="text-end highlight-stick-right">'.s("Cumul d'amortissement éco").'</th>';

						if($eAsset['isExcess']) {

							$h .= '<th class="text-end highlight-stick-right">'.s("Amortissement fiscal").'</th>';
							$h .= '<th class="text-end highlight-stick-right">'.s("Cumul d'amortissement fiscal").'</th>';
							$h .= '<th class="text-end highlight-stick-right">'.s("Amortissement dérogatoire").'</th>';
							$h .= '<th class="text-end highlight-stick-right">'.s("Reprise dérogatoire").'</th>';

						}

						$h .= '<th class="text-end highlight-stick-right">'.s("VNC fin").'</th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

					foreach($eAsset['table'] as $period) {

						$h .= '<tr class="'.(($period['amortization'] ?? new Amortization())->empty() ? 'asset-line-future' : '').'">';

							$h .= '<td class="text-center">';
								$h .= $period['financialYear']->getLabel();
							$h .= '</td>';
							$h .= '<td class="text-end highlight-stick-right">';
								$h .= \util\TextUi::money($period['base']);
							$h .= '</td>';

							if($eAsset['economicMode'] === Asset::DEGRESSIVE) {

								$h .= '<td class="text-center td-min-content">';
									$h .= s("{value} %", $period['linearRate']);
								$h .= '</td>';


								$h .= '<td class="text-center td-min-content">';
									$h .= s("{value} %", $period['degressiveRate']);
								$h .= '</td>';


								$h .= '<td class="text-center td-min-content">';
									$h .= s("{value} %", $period['rate']);
								$h .= '</td>';

							} else {

								$h .= '<td class="text-center td-min-content">';
									$h .= s("{value} %", $period['rate']);
								$h .= '</td>';

							}
							$h .= '<td class="text-end highlight-stick-right">';
								$h .= \util\TextUi::money($period['amortizationValue']);
							$h .= '</td>';
							$h .= '<td class="text-end highlight-stick-right">';
								$h .= \util\TextUi::money($period['amortizationValueCumulated']);
							$h .= '</td>';

							if($eAsset['isExcess']) {

								$h .= '<td class="text-end highlight-stick-right">';
									$h .= \util\TextUi::money($period['fiscalAmortizationValue']);
								$h .= '</td>';
								$h .= '<td class="text-end highlight-stick-right">';
									$h .= \util\TextUi::money($period['fiscalAmortizationValueCumulated']);
								$h .= '</td>';
									$h .= '<td class="text-end highlight-stick-right">';
										$h .= \util\TextUi::money($period['excessDotation'] ?? 0);
									$h .= '</td>';
									$h .= '<td class="text-end highlight-stick-right">';
										$h .= \util\TextUi::money($period['excessRecovery'] ?? 0);
									$h .= '</td>';

							}

							$h .= '<td class="text-end highlight-stick-right">';
								$h .= \util\TextUi::money($period['endValue']);
							$h .= '</td>';
						$h .= '</tr>';
					}
				$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</div>';

		if($eAsset['cOperation']->notEmpty()) {

			$h .= '<h3 class="mt-2">'.s("Écritures comptables liées à cette immobilisation").'</h3>';

			$h .= '<div class="bg-background">';
				$h .= new \journal\JournalUi()->list($eFarm, NULL, $eAsset['cOperation'], $eFarm['eFinancialYear'], readonly: TRUE);
			$h .= '</div>';

		}


		return new \Panel(
			id: 'panel-asset-view',
			title: s("Fiche de l'immobilisation #{id}", ['id' => $eAsset['id']]),
			body: $h
		);

	}

	public function listForClosing(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, \util\FormUi $form, \Collection $cAsset): string {

		if($cAsset->empty()) {
			return '';
		}

		$h = '<h3 class="mt-2">'.s("Amortissements").'</h3>';

		$h .= '<div class="util-info">';
			$h .= s("Les écritures suivantes seront automatiquement enregistrées lors de la clôture.");
		$h .= '</div>';

		$h .= '<div class="stick-sm util-overflow-sm">';

			$h .= '<table class="financial-year-asset-table tr-even tr-hover">';

				$h .= '<thead>';

					$h .= '<tr>';

						$h .= '<th rowspan="2">'.s("N° compte").'</th>';
						$h .= '<th rowspan="2">'.s("Immobilisation").'</th>';
						$h .= '<th rowspan="2" class="text-end highlight-stick-right">'.s("Base amortissable").'</th>';
						$h .= '<th rowspan="2" class="text-end highlight-stick-right">'.s("Amortissement économique").'</th>';
						$h .= '<th rowspan="2" class="text-end highlight-stick-right">'.s("VNC fin").'</th>';
						$h .= '<th colspan="2" class="text-center">'.s("Écritures proposées").'</th>';

					$h .= '</tr>';

					$h .= '<tr>';

						$h .= '<th class="text-center">'.s("Numéro de compte Débit").'</th>';
						$h .= '<th class="text-center">'.s("Numéro de compte Crédit").'</th>';

					$h .= '</tr>';

				$h .= '</thead>';

				$h .= '<tbody>';
					foreach($cAsset as $eAsset) {

						$currentPeriod = NULL;
						foreach($eAsset['table'] as $period) {
							if(
								$period['financialYear']['startDate'] === $eFinancialYear['startDate'] and
								$period['financialYear']['endDate'] === $eFinancialYear['endDate']
							) {
								$currentPeriod = $period;
								break;
							}
						}

						if($currentPeriod === NULL) {
							continue;
						}

						$h .= '<tr id="'.$eAsset['id'].'">';

							$h .= '<td>';
								$h .= '<div data-dropdown="bottom" data-dropdown-hover="true">';
									$h .= encode($eAsset['accountLabel']);
								$h .= '</div>';
								$h .= '<div class="dropdown-list bg-primary">';
								$h .= '<span class="dropdown-item">'.encode($eAsset['account']['class']).' '.encode($eAsset['account']['description']).'</span>';
								$h .= '</div>';
							$h .= '</td>';
							$h .= '<td><a href="'.\company\CompanyUi::urlAsset($eFarm).'/'.$eAsset['id'].'/">'.encode($eAsset['description']).'</a></td>';
							$h .= '<td class="text-end highlight-stick-right">'.\util\TextUi::money($period['base']).'</td>';
							$h .= '<td class="text-end highlight-stick-right">'.\util\TextUi::money($period['amortizationValue']).'</td>';
							$h .= '<td class="text-end highlight-stick-right">'.\util\TextUi::money($period['endValue']).'</td>';
							$h .= '<td class="text-center">';
								foreach($eAsset['operations'] as $eOperation) {
									if($eOperation['type'] === \journal\Operation::DEBIT) {
										$h .= '<div>'.$eOperation['accountLabel'].'</div>';
									}
								}
							$h .= '</td>';
							$h .= '<td class="text-center">';
								foreach($eAsset['operations'] as $eOperation) {
									if($eOperation['type'] === \journal\Operation::CREDIT) {
										$h .= '<div>'.$eOperation['accountLabel'].'</div>';
									}
								}
							$h .= '</td>';

						$h .= '</tr>';

					}

				$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</div>';

		return $h;
	}
	public function listGrantsForClosing(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, \util\FormUi $form, \Collection $cAssetGrant): string {

		if($cAssetGrant->empty()) {
			return '';
		}

		$h = '<h3 class="mt-2">'.s("Subventions").'</h3>';

		$h .= '<div class="stick-sm util-overflow-sm">';

			$h .= '<table class="financial-year-stock-table tr-even tr-hover">';

				$h .= '<thead>';

					$h .= '<tr>';

						$h .= '<th rowspan="2">'.s("N° compte").'</th>';
						$h .= '<th rowspan="2">'.s("Immobilisation").'</th>';
						$h .= '<th rowspan="2" class="text-end highlight-stick-right">'.s("Base amortissable").'</th>';
						$h .= '<th rowspan="2" class="text-end highlight-stick-right">'.s("Amortissement économique").'</th>';
						$h .= '<th rowspan="2" class="text-end highlight-stick-right">'.s("VNC fin").'</th>';
						$h .= '<th colspan="2" class="text-center">'.s("Écritures").'</th>';

					$h .= '</tr>';

					$h .= '<tr>';

						$h .= '<th class="text-center">'.s("Numéro de compte Débit").'</th>';
						$h .= '<th class="text-center">'.s("Numéro de compte Crédit").'</th>';

					$h .= '</tr>';

				$h .= '</thead>';

				$h .= '<tbody>';
					foreach($cAssetGrant as $eAsset) {

						$currentPeriod = NULL;
						foreach($eAsset['table'] as $period) {
							if(
								$period['financialYear']['startDate'] === $eFinancialYear['startDate'] and
								$period['financialYear']['endDate'] === $eFinancialYear['endDate']
							) {
								$currentPeriod = $period;
								break;
							}
						}

						if($currentPeriod === NULL) {
							continue;
						}

						$h .= '<tr id="'.$eAsset['id'].'">';

							$h .= '<td>';
								$h .= '<div data-dropdown="bottom" data-dropdown-hover="true">';
									$h .= encode($eAsset['accountLabel']);
								$h .= '</div>';
								$h .= '<div class="dropdown-list bg-primary">';
								$h .= '<span class="dropdown-item">'.encode($eAsset['account']['class']).' '.encode($eAsset['account']['description']).'</span>';
								$h .= '</div>';
							$h .= '</td>';
							$h .= '<td><a href="'.\company\CompanyUi::urlAsset($eFarm).'/'.$eAsset['id'].'/">'.encode($eAsset['description']).'</a></td>';
							$h .= '<td class="text-end highlight-stick-right">'.\util\TextUi::money($period['base']).'</td>';
							$h .= '<td class="text-end highlight-stick-right">'.\util\TextUi::money($period['amortizationValue']).'</td>';
							$h .= '<td class="text-end highlight-stick-right">'.\util\TextUi::money($period['endValue']).'</td>';
							$h .= '<td class="text-center">';
								foreach($eAsset['operations'] as $eOperation) {
									if($eOperation['type'] === \journal\Operation::DEBIT) {
										$h .= '<div>'.$eOperation['accountLabel'].'</div>';
									}
								}
								$h .= '</td>';
								$h .= '<td class="text-center">';
								foreach($eAsset['operations'] as $eOperation) {
									if($eOperation['type'] === \journal\Operation::CREDIT) {
										$h .= '<div>'.$eOperation['accountLabel'].'</div>';
									}
								}
							$h .= '</td>';

						$h .= '</tr>';

					}

				$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public function getTranslation(string|int $class): string {

		return match($class) {
			'amortization' => s("Amortissement"),
			'asset' => s("Immobilisation"),
			'depreciation-asset' => s("Dépréciation des immobilisations"),
			'recovery-depreciation-asset' => s("Reprises sur dépréciations des immobilisations"),
			'recovery-depreciation-exceptional' => s("Reprises sur dépréciations exceptionnelles"),
			\account\AccountSetting::INTANGIBLE_ASSETS_AMORTIZATION_CHARGE_CLASS => s("Dotation aux amortissements"),
			\account\AccountSetting::TANGIBLE_ASSETS_AMORTIZATION_CHARGE_CLASS => s("Dotation aux amortissements"),
			\account\AccountSetting::ASSETS_AMORTIZATION_CHARGE_CLASS => s("Dotation aux amortissements"),
			\account\AccountSetting::CHARGE_ASSET_NET_VALUE_CLASS => s("VNC d'immobilisation"),
			\account\AccountSetting::ASSETS_AMORTIZATION__EXCEPTIONAL_CHARGE_CLASS => s("Dotation aux amortissements (dérogatoires)"),
			\account\AccountSetting::EXCESS_AMORTIZATION_CLASS => s("Amortissements dérogatoires"),
			\account\AccountSetting::RECOVERY_EXCEPTIONAL_ON_ASSET_DEPRECIATION => s("Reprise sur amortissements dérogatoires"),
		};

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Asset::model()->describer($property, [
			'account' => s("Compte"),
			'accountLabel' => s("Numéro de compte"),
			'value' => s("Valeur d'acquisition (HT)"),
			'amortizableBase' => s("Base amortissable (HT)"),
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
			'residualValue' => s('Valeur résiduelle'),
			'resumeFinancialYear' => s("Exercice de reprise sur {siteName}"),
		]);

		switch($property) {

			case 'account':
				$d->autocompleteBody = function(\util\FormUi $form, Asset $e) {
					return [
					];
				};
				$d->group += ['wrapper' => 'account'];
				new \account\AccountUi()->query($d, GET('farm', 'farm\Farm'), query: ['classPrefixes' => [\account\AccountSetting::INTANGIBLE_ASSETS_CLASS, \account\AccountSetting::TANGIBLE_ASSETS_CLASS, \account\AccountSetting::TANGIBLE_LIVING_ASSETS_CLASS, \account\AccountSetting::GRANT_ASSET_CLASS]]);
				break;

			case 'accountLabel':
				$d->autocompleteBody = function(\util\FormUi $form, Asset $e) {
					return [
					];
				};
				$d->group += ['wrapper' => 'accountLabel'];
				new \account\AccountUi()->queryLabel($d, GET('farm', 'farm\Farm'), query: GET('query'));
				break;

			case 'acquisitionDate' :
			case 'startDate' :
				$d->prepend = \Asset::icon('calendar-date');
				break;

			case 'value':
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

			case 'economicDuration':
			case 'fiscalDuration':
				$d->append = s("mois");
				break;

			case 'residualValue':
				$d->field = 'calculation';
				$d->append = function(\util\FormUi $form, Asset $e) {
					return $form->addon(s("€"));
				};
				$d->after = \util\FormUi::info(s("La valeur résiduelle n'est prise en compte que dans l'amortissement économique et donnera lieu à des amortissements dérogatoires."));
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
