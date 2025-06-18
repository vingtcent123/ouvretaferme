<?php
namespace asset;

Class AssetUi {

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

	public function getAcquisitionTable(\Collection $cAsset, string $type): string {

		if($cAsset->empty() === TRUE) {

				return '<div class="util-info">'.s("Aucune écriture n'a encore été enregistrée").'</div>';

		}

		$h = '<div class="stick-sm util-overflow-sm">';

			$h .= '<table class="tr-even td-vertical-top tr-hover table-bordered">';

				$h .= '<thead class="thead-sticky">';
					$h .= '<tr>';
						$h .= '<th class="text-center" rowspan="2">'.s("Code").'</th>';
						$h .= '<th class="text-center" rowspan="2">'.s("Compte").'</th>';
						$h .= '<th class="text-center" rowspan="2">'.s("Désignation").'</th>';
						$h .= '<th class="text-center" colspan="2">'.s("Type").'</th>';
						$h .= '<th class="text-center" rowspan="2">'.s("Durée (en années)").'</th>';
						$h .= '<th class="text-center" rowspan="2">'.s("Date").'</th>';
						$h .= '<th class="text-center" rowspan="2">'.s("Valeur").'</th>';
						$h .= '<th class="text-center" rowspan="2">'.s("DPI affectée").'</th>';
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

							$h .= '<td></td>';
							$h .= '<td>'.encode($eAsset['accountLabel']).'</td>';
							$h .= '<td>'.encode($eAsset['description']).'</td>';
							$h .= '<td>';
								$h .= match($eAsset['type']) {
									AssetElement::LINEAR => s("LIN"),
									AssetElement::WITHOUT => s("SANS"),
								};
							$h .= '</td>';
							$h .= '<td>';
								$h .= match($eAsset['type']) {
									AssetElement::LINEAR => s("LIN"),
									AssetElement::WITHOUT => s("SANS"),
								};
							$h .= '</td>';
							$h .= '<td class="text-center">';
								$h .= match($eAsset['type']) {
									AssetElement::LINEAR => encode($eAsset['duration']),
									AssetElement::WITHOUT => '',
								};
							$h .= '</td>';
							$h .= '<td class="text-end">'.\util\DateUi::numeric($eAsset['startDate'], \util\DateUi::DATE).'</td>';
							$h .= '<td class="text-end">'.$this->number($eAsset['value'], '', 2).'</td>';
							$h .= '<td></td>';

						$h .= '</tr>';
						$total += $eAsset['value'];
					}
					$h .= '<tr class="row-bold">';
						$h .= '<td></td>';
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
						$h .= '<td></td>';
					$h .= '</tr>';

				$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}
	public static function getSummary(\accounting\FinancialYear $eFinancialYear, array $assetSummary): string {

		$h = '';

		if($eFinancialYear['status'] === \accounting\FinancialYearElement::OPEN) {

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

	private static function getHeader(Asset $eAsset): string {

		$h = '<div class="util-block stick-xs bg-background-light">';
			$h .= '<dl class="util-presentation util-presentation-2">';
				$h .= '<dt>'.s("N° compte").'</dt>';
				$h .= '<dd>'.encode($eAsset['accountLabel']).'</dd>';
				$h .= '<dt>'.s("Date d'acquisition").'</dt>';
				$h .= '<dd>'.\util\DateUi::numeric($eAsset['acquisitionDate'], \util\DateUi::DATE).'</dd>';
				$h .= '<dt>'.s("Libellé").'</dt>';
				$h .= '<dd>'.encode($eAsset['description']).'</dd>';
				$h .= '<dt>'.s("Type").'</dt>';
				$h .= '<dd>'.AssetUi::p('type')->values[$eAsset['type']].'</dd>';
				$h .= '<dt>'.s("Valeur d'achat").'</dt>';
				$h .= '<dd>'.\util\TextUi::money($eAsset['value']).'</dd>';
				$h .= '<dt>'.s("Statut").'</dt>';
				$h .= '<dd>'.self::p('status')->values[$eAsset['status']].'</dd>';
			$h .= '</dl>';
		$h .= '</div>';

		return $h;

	}

	public static function dispose(\company\Company $eCompany, \accounting\FinancialYear $eFinancialYear, Asset $eAsset): \Panel {

		\Asset::js('asset', 'asset.js');

		$h = self::getHeader($eAsset);

		$form = new \util\FormUi();
		$statuses = [
			AssetElement::SCRAPPED => s("Mettre au rebut"),
			AssetElement::SOLD => s("Vendre"),
		];

		$h .= '<div class="util-block">';

			$h .= $form->openAjax(
				\company\CompanyUi::urlAsset($eCompany).'/:doDispose',
				['id' => 'panel-asset-dispose']
			);

				$h .= $form->hidden('company', $eCompany['id']);
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

	public static function view(\company\Company $eCompany, Asset $eAsset): \Panel {

		$h = self::getHeader($eAsset);

		$h .= '<div>';
			$h .= '<a href="'.\company\CompanyUi::urlJournal($eCompany).'/?asset='.$eAsset['id'].'">'.s("Voir toutes les écritures comptables de cette immobilisation pour cet exercice comptable").'&nbsp;'.\Asset::icon('box-arrow-up-right').'</a>';
		$h .= '</div>';

		if($eAsset['status'] === AssetElement::ONGOING) {


			$h .= '<div class="mt-1 mb-1">';
				$h .= '<a href="'.\company\CompanyUi::urlAsset($eCompany).'/:dispose?id='.$eAsset['id'].'" class="btn btn-primary">'.\Asset::icon('box-arrow-right').' '.s("Céder l'immobilisation").'</a>';
			$h .= '</div>';

		}

		$h .= '<h2>'.s("Amortissements").'</h2>';

		if($eAsset['cDepreciation']->empty()) {

			$h .= '<div class="util-info">';
				$h .= s("Il n'y a pas encore eu d'amortissement enregistré pour cette immobilisation.");
			$h .= '</div>';

		} else {

			$h .= '<div class="stick-sm util-overflow-sm">';

				$h .= '<table class="tr-even td-vertical-top tr-hover table-bordered">';

					$h .= '<thead class="thead-sticky">';
						$h .= '<tr>';
							$h .= '<th>'.s("Date").'</th>';
							$h .= '<th>'.s("Type").'</th>';
							$h .= '<th>'.s("Montant").'</th>';
							$h .= '<th>'.s("Exercice comptable").'</th>';
						$h .= '</tr>';
					$h .= '</thead>';

					$h .= '<tbody>';

						foreach($eAsset['cDepreciation'] as $eDepreciation) {

							$h .= '<tr>';
								$h .= '<td>'.\util\DateUi::numeric($eDepreciation['date'], \util\DateUi::DATE).'</td>';
								$h .= '<td>'.DepreciationUi::p('type')->values[$eDepreciation['type']].'</td>';
								$h .= '<td>'.\util\TextUi::money($eDepreciation['amount']).'</td>';
								$h .= '<td>'.\accounting\FinancialYearUi::getYear($eDepreciation['financialYear']).'</td>';
							$h .= '</tr>';

						}

					$h .= '</tbody>';

				$h .= '</table>';

			$h .= '</div>';

		}

		return new \Panel(
			id: 'panel-asset-view',
			title: s("Immobilisation #{id}", ['id' => $eAsset['id']]),
			body: $h
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = \journal\Operation::model()->describer($property, [
			'accountLabel' => s("Compte"),
			'value' => s("Valeur (HT)"),
			'type' => s("Type d'amortissement"),
			'mode' => s("Mode"),
			'acquisitionDate' => s("Date d'acquisition"),
			'startDate' => s("Date de mise en service"),
			'duration' => s("Durée (en années)"),
			'status' => s("Statut"),
			'endDate' => s('Date de fin'),
			'description' => s('Libellé'),
		]);

		switch($property) {

			case 'acquisitionDate' :
			case 'startDate' :
				$d->prepend = \Asset::icon('calendar-date');
				break;

			case 'type':
				$d->values = [
					AssetElement::LINEAR => s("Linéaire"),
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
