<?php
namespace analyze;

class ReportUi {

	public function __construct() {

		\Asset::js('analyze', 'report.js');
		\Asset::css('analyze', 'report.css');

	}

	public static function link(Report $eReport, bool $newTab = FALSE): string {
		return '<a href="'.self::url($eReport).'" '.($newTab ? 'target="_blank"' : '').'>'.encode($eReport['name']).'</a>';
	}

	public static function url(Report $eReport): string {

		$eReport->expects(['id']);

		return '/rapport/'.$eReport['id'];

	}

	public static function getName(Report $eReport): string {

		$h = encode($eReport['plant']['name']).' ';
		$h .= $eReport['season'].' ';
		if($eReport['name'] !== NULL) {
			$h .= ' - '.encode($eReport['name']);
		}

		return $h;

	}

	public function getOne(Report $eReport, \Collection $cCultivation) {

		$h = '<div class="util-vignette">';
			$h .= \plant\PlantUi::getVignette($eReport['plant'], size: '5rem');
			$h .= '<div>';
				$h .= '<div class="util-action">';
					$h .= '<h1>'.\analyze\ReportUi::getName($eReport).'</h1>';
					$h .= '<div>';
						if($eReport->canWrite()) {
							$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-primary">'.\Asset::icon('gear-fill').'</a>';
							$h .= '<div class="dropdown-list bg-primary">';
								$h .= '<div class="dropdown-title">'.\analyze\ReportUi::getName($eReport).'</div>';
								$h .= '<a href="/analyze/report:update?id='.$eReport['id'].'" class="dropdown-item">'.s("Modifier le rapport").'</a>';
								$h .= '<a href="/analyze/report:create?farm='.$eReport['farm']['id'].'&season='.$eReport['season'].'&from='.$eReport['id'].'" class="dropdown-item">'.s("Actualiser le rapport").'</a>';
								$h .= '<div class="dropdown-divider"></div>';
								$h .= '<a data-ajax="/analyze/report:doDelete" post-id="'.$eReport['id'].'" class="dropdown-item">'.s("Supprimer le rapport").'</a>';
							$h .= '</div>';
						}
					$h .= '</div>';
				$h .= '</div>';
				$h .= '<div class="util-action-subtitle color-muted">';
					$h .= s("Rapport créé le {value}", \util\DateUi::numeric($eReport['createdAt'], \util\DateUi::DATE));
				$h .= '</div>';
			$h .= '</div>';
		$h .= '</div>';

		if($eReport['description'] !== NULL) {

			$h .= '<div class="report-description util-block">';

				$description = (new \editor\EditorUi())->value($eReport['description']);

				$h .= '<span class="report-description-icon">'.\Asset::icon('chat-right-text').'</span>';
				$h .= '<span>'.$description.'</span>';

			$h .= '</div>';

		}

		$h .= $this->getOneDisplay($eReport, $cCultivation);

		$h .= '<br/>';

		return $h;

	}

	public function getHarvested(Report $eReport, \Collection $cCultivation): string {

		$h = '<h2>'.s("Rendements").'</h2>';

		$h .= '<div class="util-overflow-xs stick-xs">';

			$h .= '<table class="report-item-table report-harvested-table"">';


				$h .= '<thead>';
					$h .= '<tr>';
						$h .= $cCultivation->count() > 1 ? '<th></th>' : '<th>'.s("Série").'</th>';
						$h .= '<th class="text-end">'.s("Surface").'</th>';
						$h .= '<th class="text-end">'.s("Récolte").'</th>';
						$h .= '<th class="text-end report-item-ratio">'.s("/ m²").'</th>';
					$h .= '</tr>';
				$h .= '</thead>';

				if($cCultivation->count() > 1) {

					$area = $eReport['area'];

					$totalHarvestedByUnit = [];

					$cCultivation->map(function($e) use (&$totalHarvestedByUnit) {
						if($e['harvestedByUnit']) {
							foreach($e['harvestedByUnit'] as $unit => $value) {
								$totalHarvestedByUnit[$unit] ??= 0;
								$totalHarvestedByUnit[$unit] += $value;
							}
						}
					});

					$h .= '<tbody>';
						$h .= '<tr class="report-item-total">';
							$h .= '<td class="util-grid-header"></td>';

							$h .= '<td class="text-end">';
								$h .= ($area > 0) ? s("{value} m²", $area) : '-';
							$h .= '</td>';

							$h .= '<td class="text-end">';
								foreach($totalHarvestedByUnit as $unit => $value) {
									$h .= \main\UnitUi::getValue(round($value, 2), $unit).'<br/>';
								}
							$h .= '</td>';

							$h .= '<td class="text-end report-item-ratio">';
								foreach($totalHarvestedByUnit as $unit => $value) {
									$h .= \main\UnitUi::getValue(round($value / $area, 1), $unit).'<br/>';
								}
							$h .= '</td>';
						$h .= '</tr>';
					$h .= '</tbody>';

				}

				$h .= $this->getHarvestedByCultivation($cCultivation);

			$h .= '</table>';

		$h .= '</div>';

		$h .= '<br/>';

		return $h;

	}

	public function getTest(Report $eReport, Report $eReportTest): string {

		if(
			$eReport['area'] === 0 and
			$eReport['workingTime'] === 0.0 and
			$eReport['turnover'] === 0 and
			$eReport['costs'] === 0
		) {
			return '';
		}

		$form = new \util\FormUi();

		$h = '<h4>'.s("Tester une hypothèse alternative").'</h4>';
		$h .= $form->openAjax('/analyze/report:doTest');

			$h .= $form->hidden('id', $eReport['id']);

			$h .= '<ul class="util-summarize report-test-form">';
				if($eReport['area'] > 0) {
					$h .= '<li>';
						$h .= '<h5>'.s("Surface").'</h5>';
						$h .= $this->getTestField($form, $eReport, 'area');
					$h .= '</li>';
				}
				if($eReport['workingTime'] > 0) {
					$h .= '<li>';
						$h .= '<h5>'.s("Heures travaillées").'</h5>';
						$h .= $this->getTestField($form, $eReport, 'workingTime');
					$h .= '</li>';
				}
				if($eReport['turnover'] > 0) {
					$h .= '<li>';
						$h .= '<h5>'.s("Ventes").'</h5>';
						$h .= $this->getTestField($form, $eReport, 'turnover');
					$h .= '</li>';
				}
				if($eReport['costs'] > 0) {
					$h .= '<li>';
						$h .= '<h5>'.s("Coûts directs").'</h5>';
						$h .= $this->getTestField($form, $eReport, 'costs');
					$h .= '</li>';
				}
				$h .= '<li>';
					$h .= '<h5>&nbsp;</h5>'; // Tricky
					$h .= '<div>';
						$h .= $form->submit(s("Calculer")).' ';
						if(
							$eReport['testArea'] !== NULL or
							$eReport['testWorkingTime'] !== NULL or
							$eReport['testCosts'] !== NULL or
							$eReport['testTurnover'] !== NULL
						) {
							$h .= '<a data-ajax="/analyze/report:doTest" post-id="'.$eReport['id'].'" class="btn btn-outline-primary">'.s("Réinitialiser").'</a>';
						}
					$h .= '</div>';
				$h .= '</li>';
			$h .= '</ul>';

		$h .= $form->close();

		if($eReportTest->notEmpty()) {
			$h .= '<br/>';
			$h .= $this->getTestDisplay($eReport, $eReportTest);
		}

		$h .= '<br/>';

		return $h;

	}

	protected function getTestField(\util\FormUi $form, Report $eReport, string $name): string {

		$h = '<div class="input-group">';
			$h .= '<div class="input-group-addon">'.\Asset::icon('plus-slash-minus').'</div>';
			$h .= $form->dynamicField($eReport, 'test'.ucfirst($name));
			$h .= $form->dynamicField($eReport, 'test'.ucfirst($name).'Operator');
		$h .= '</div>';

		return $h;

	}

	protected function getTestDisplay(Report $eReport, Report $eReportTest): string {

		$h = '<div class="util-overflow-md stick-xs">';

			$h .= '<table class="report-item-table"">';

				$h .= $this->getOneHead('<td></td>');

				$h .= '<tbody>';

					$h .= '<tr>';
						$h .= '<td>'.s("Rapport").'</td>';
						$h .= $this->getStats($eReport);
					$h .= '</tr>';

					$h .= '<tr class="report-selected">';
						$h .= '<td>';
							$h .= '<b>'.s("Alternative").'</b>';
						$h .= '</td>';
						$h .= $this->getStats($eReportTest);
					$h .= '</tr>';

				$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	protected function getOneDisplay(Report $eReport, \Collection $cCultivation): string {

		if($cCultivation->empty()) {
			return '<div class="util-warning">'.s("La ou les séries utilisées dans ce rapport n'existent plus.").'</div>';
		}

		$h = '<div class="util-overflow-md stick-xs">';

			$h .= '<table>';

				$h .= $this->getOneHead($cCultivation->count() > 1 ? '<th colspan="3"></th>' : '<th colspan="3">'.s("Série").'</th>');

				if($cCultivation->count() > 1) {
					$h .= '<tbody>';
						$h .= '<tr class="report-item-total">';
							$h .= '<td colspan="3"></td>';
							$h .= $this->getStats($eReport);
						$h .= '</tr>';
					$h .= '</tbody>';
				}

				$h .= '<tbody>';
					$h .= $this->getOneByCultivation($eReport, $cCultivation);
				$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	protected function getOneHead(string $start): string {

		$h = '<thead>';
			$h .= '<tr>';
				$h .= $start;
				$h .= '<th class="text-end">'.s("Surface").'</th>';
				$h .= '<th class="text-end">'.s("Heures travaillées").'</th>';
				$h .= '<th class="text-end">'.s("Ventes").'</th>';
				$h .= '<th class="text-end report-item-ratio">'.s("/ m²").'</th>';
				$h .= '<th class="text-end">'.s("Coûts directs").'</th>';
				$h .= '<th class="text-end">'.s("Valeur ajoutée").'</th>';
				$h .= '<th class="text-end report-item-ratio">'.s("/ m²").'</th>';
				$h .= '<th class="text-end report-item-ratio">'.s("/ heure travaillée").'</th>';
			$h .= '</tr>';
		$h .= '</thead>';

		return $h;

	}

	public function getList(\Collection $cReport, \Search $search, ?Report $eReportSelected = NULL) {

		if($cReport->empty()) {
			$h = '<div class="util-block-help">';
				$h .= '<p>'.s("Les rapports de production permet d'obtenir des données chrono-technico-économiques sur les espèces que vous cultivez, en faisant le rapprochement entre les saisies de temps que vous avez faites sur vos séries et les ventes que vous avez réalisées.").'</p>';
				$h .= '<p>'.s("Vous pouvez ainsi calculer vos coûts de production et mesurer la rentabilité de chacune de vos cultures.").'</p>';
			$h .= '</div>';
			return $h;
		}

		$h = '<div class="util-overflow-md stick-xs">';

			$h .= '<table class="report-item-table tr-bordered tbody-even">';

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th colspan="3">'.$search->linkSort('name', s("Rapport")).'</th>';
						$h .= '<th class="text-end">'.$search->linkSort('area', s("Surface"), SORT_DESC).'</th>';
						$h .= '<th class="text-end">'.$search->linkSort('workingTime', s("Heures travaillées"), SORT_DESC).'</th>';
						$h .= '<th class="text-end">'.$search->linkSort('turnover', s("Ventes"), SORT_DESC).'</th>';
						$h .= '<th class="text-end report-item-ratio">'.$search->linkSort('turnoverByArea', s("/ m²"), SORT_DESC).'</th>';
						$h .= '<th class="text-end">'.s("Coûts directs").'</th>';
						$h .= '<th class="text-end">'.$search->linkSort('grossMargin', s("Valeur ajoutée", SORT_DESC)).'</th>';
						$h .= '<th class="text-end report-item-ratio">'.$search->linkSort('grossMarginByArea', s("/ m²", SORT_DESC)).'</th>';
						$h .= '<th class="text-end report-item-ratio">'.$search->linkSort('grossMarginByWorkingTime', s("/ heure travaillée"), SORT_DESC).'</th>';
					$h .= '</tr>';
				$h .= '</thead>';

				foreach($cReport as $eReport) {

					$cCultivation = $eReport['cCultivation'];

					$h .= '<tbody '.(($eReportSelected !== NULL and $eReportSelected['id'] === $eReport['id']) ? 'class="report-selected"' : '').'>';

						$ePlant = $eReport['plant'];

						$h .= '<tr>';

							$h .= '<td class="td-min-content">';
								$h .= \plant\PlantUi::getVignette($ePlant, '3rem');
							$h .= '</td>';

							$h .= '<td>';
								$h .= '<div>';
									$h .= '<a href="'.ReportUi::url($eReport).'">';
										$h .= $this->getName($eReport);
									$h .= '</a>';
								$h .= '</div>';
								$h .= '<div class="report-item-date">';
									$h .= s("Créé le {value}", \util\DateUi::numeric($eReport['createdAt'], \util\DateUi::DATE));
								$h .= '</div>';
							$h .= '</td>';

							$h .= '<td class="td-min-content">';
								if($cCultivation->count() > 1) {
									$h .= '<a onclick="Report.toggleCultivations(this)" data-id="'.$eReport['id'].'" data-toggle="collapse" class="btn btn-outline-primary btn-xs">'.\Asset::icon('chevron-down').'</a>';
								}
							$h .= '</td>';

							$h .= $this->getStats($eReport);

						$h .= '</tr>';

						if($cCultivation->count() > 1) {
							$h .= $this->getOneByCultivation($eReport, $cCultivation, hide: TRUE);
						}

					$h .= '</tbody>';

				}

			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public function getSiblings(Report $eReport, \Collection $cReport) {

		$h = '<h2>'.s("Comparer le rapport").'</h2>';

		if($cReport->count() <= 1) {
			$h .= '<p class="util-info">'.s("Il n'y a pas d'autre rapport avec le même nom et pour la plante {name} avec lequel faire une comparaison.", ['name' => encode($eReport['plant']['name'])]).'</p>';
			return $h;
		}

		$h .= '<p class="util-info">';
			$h .= p("Il y a {value} autre rapport avec le même nom et pour la plante {name} avec lequel faire une comparaison.", "Il y a {value} autres rapports avec le même nom et pour la plante {name} avec lesquels faire une comparaison.", $cReport->count() - 1, ['name' => encode($eReport['plant']['name'])]);
		$h .= '</p>';

		$search = new \Search();
		$search->setSortStatus(FALSE);

		$h .= $this->getList($cReport, $search, $eReport);

		return $h;

	}

	protected function getOneByCultivation(Report $eReport, \Collection $cCultivation, bool $hide = FALSE) {

		$h = '';

		if($cCultivation->count() > 1) {

			$h .= '<tr class="tr-bordered '.($hide ? 'hide' : '').'" data-ref="report-'.$eReport['id'].'">';

				$h .= '<th colspan="6">';
					$h .= s("Par série");
				$h .= '</th>';

				$h .= '<td class="report-item-ratio"></td>';
				$h .= '<td colspan="2"></td>';
				$h .= '<td colspan="2" class="report-item-ratio"></td>';

			$h .= '</tr>';

		}

		foreach($cCultivation as $eCultivation) {

			$h .= '<tr class="tr-bordered '.($hide ? 'hide' : '').'" data-ref="report-'.$eReport['id'].'">';

				$h .= '<td class="report-item-series" colspan="3">';
					$h .= \series\SeriesUi::link($eCultivation['series']);
				$h .= '</td>';

				$h .= $this->getStats($eCultivation, $hide === FALSE);

			$h .= '</tr>';

		}

		return $h;

	}

	protected function getHarvestedByCultivation(\Collection $cCultivation) {

		$h = '';

		if($cCultivation->count() > 1) {

			$h .= '<thead>';
				$h .= '<tr class="tr-bordered">';

					$h .= '<th colspan="3">';
						$h .= s("Par série");
					$h .= '</th>';

					$h .= '<td class="report-item-ratio"></td>';

				$h .= '</tr>';
			$h .= '</thead>';

		}

		$h .= '<tbody>';

		foreach($cCultivation as $eCultivation) {

			$harvestedByUnit = $eCultivation['harvestedByUnit'];

			if(empty($harvestedByUnit)) {
				continue;
			}

			$h .= '<tr class="tr-bordered">';

				$h .= '<td class="report-item-series">';
					$h .= \series\SeriesUi::link($eCultivation['series']);
				$h .= '</td>';

				$h .= '<td class="text-end">';
					$h .= ($eCultivation['area'] > 0) ? s("{value} m²", $eCultivation['area']) : '-';
				$h .= '</td>';

				$h .= '<td class="text-end">';
					foreach($harvestedByUnit as $unit => $value) {
						$h .= \main\UnitUi::getValue(round($value, 2), $unit).'<br/>';
					}
				$h .= '</td>';

				$h .= '<td class="text-end report-item-ratio">';
					foreach($harvestedByUnit as $unit => $value) {
						$h .= \main\UnitUi::getValue(round($value / $eCultivation['area'], 1), $unit).'<br/>';
					}
				$h .= '</td>';

			$h .= '</tr>';

		}

		$h .= '</tbody>';

		return $h;

	}

	public function getProducts(Report $eReport, \Collection $ccProduct) {

		if($ccProduct->count() < 2) {
			return '';
		}

		$h = '<h2>'.s("Produits").'</h2>';

		$h .= '<div class="util-overflow-xs">';

			$h .= '<div class="report-product-wrapper">';

			foreach($ccProduct as $cProduct) {

				$eProduct = $cProduct->first();
				$turnover = $cProduct->sum('turnover');

				$h .= '<div class="util-block report-product">';

					$h .= \selling\ProductUi::getVignette($eProduct['product'], '3rem');
					$h .= '<h4>';
						$h .= \selling\ProductUi::link($eProduct['product']);
					$h .= '</h4>';
					$h .= '<div>';
						if($eProduct['quantity'] > 0) {
							$h .= \main\UnitUi::getValue($eProduct['quantity'], $eProduct['unit']);
						} else {
							$h .= match($eProduct['unit']) {
								Product::UNIT => s("À la pièce"),
								Product::BUNCH => s("À la botte"),
								Product::KG => s("Au kg")
							};
						}
						if($eProduct['product']['size'] !== NULL) {
							$h .= ' | '.encode($eProduct['product']['size']);
						}
					$h .= '</div>';
					$h .= '<div class="report-product-turnover">'.\util\TextUi::money($turnover, precision: 0).'</div>';
					$h .= '<div class="color-muted">'.s("{value} %", round($turnover / $eReport['turnover'] * 100)).'</div>';

				$h .= '</div>';

			}

			$h .= '</div>';

		$h .= '</div>';

		$h .= '<br/>';

		return $h;

	}
	
	protected function getStats(Report|Cultivation $e, bool $quick = FALSE): string {

		$h = '';

		$h .= '<td class="text-end">';
			$value = ($e['area'] > 0) ? s("{value} m²", $e['area']) : '-';
			$h .= $quick ? $e->quick('area', $value) : $value;
		$h .= '</td>';

		$h .= '<td class="text-end">';
			$value = ($e['workingTime'] > 0) ? \series\TaskUi::convertTime($e['workingTime']) : '-';
			$h .= $quick ? $e->quick('workingTime', $value) : $value;
		$h .= '</td>';

		$h .= '<td class="text-end">';
			$value = ($e['turnover'] > 0) ? \util\TextUi::money($e['turnover'], precision: 0) : '-';
			$h .= $quick ? $e->quick('turnover', $value) : $value;
		$h .= '</td>';

		$h .= '<td class="text-end  report-item-ratio">';
			if($e['turnoverByArea'] !== NULL) {
				$h .= \util\TextUi::money($e['turnoverByArea'], precision: 1);
			}
		$h .= '</td>';

		$h .= '<td class="text-end">';
			$value = ($e['costs'] > 0) ? \util\TextUi::money($e['costs'], precision: 0) : '-';
			$h .= $quick ? $e->quick('costs', $value) : $value;
		$h .= '</td>';

		$h .= '<td class="text-end">';
			$h .= \util\TextUi::money($e['grossMargin'], precision: 0);
		$h .= '</td>';

		$h .= '<td class="text-end  report-item-ratio">';
			if($e['grossMarginByArea'] !== NULL) {
				$h .= \util\TextUi::money($e['grossMarginByArea'], precision: 1);
			}
		$h .= '</td>';

		$h .= '<td class="text-end  report-item-ratio">';
			if($e['grossMarginByWorkingTime'] !== NULL) {
				$h .= \util\TextUi::money($e['grossMarginByWorkingTime'], precision: 1);
			}
		$h .= '</td>';

		return $h;
		
	}

	public function create(Report $e, ?float $workingTimeNoSeries, \Collection $cProduct): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/analyze/report:doCreate', ['id' => 'report-create']);

			$h .= $form->hidden('from', $e['from']);
			$h .= $form->hidden('farm', $e['farm']['id']);

			$h .= $form->group(
				s("Ferme"),
				\farm\FarmUi::link($e['farm'], TRUE)
			);

			if(count($e['farm']->getSeasons()) === 1) {

				$h .= $form->hidden('season', $e['farm']['seasonFirst']);
				$h .= $form->group(
					s("Saison"),
					$e['farm']['seasonFirst']
				);

			} else {
				$h .= $form->dynamicGroup($e, 'season');
			}

			if($e['cPlant']->empty()) {

				$h .= $form->group(
					content: '<p class="util-warning">'.s("Il n'y a aucune série sur laquelle faire un rapport pour la saison {season}.", ['season' => $e['season']]).'</p>'
				);

			} else {
				$h .= $form->dynamicGroup($e, 'plant');
			}

			if($e['plant']->notEmpty()) {

				if($e['cCultivation']->empty()) {

					$h .= $form->group(
						content: '<p class="util-warning">'.s("Il n'y a aucune série sur laquelle faire un rapport pour cette espèce et pour la saison {season}.", ['season' => $e['season']]).'</p>'
					);

				} else {

					$h .= $form->dynamicGroup($e, 'name');
					$h .= $form->dynamicGroup($e, 'description');

					$h .= $form->group(
						p("Série à inclure au rapport", "Séries à inclure au rapport", $e['cCultivation']->count()),
						$form->dynamicField($e, 'cultivations')
					);

					if($workingTimeNoSeries > 0) {
						$h .= $form->group(
							content: '<p class="text-end">'.\Asset::icon('info-circle').' '.s("Il y a aussi eu {time} de travail hors-série sur cette plante en {year} (<link>voir le détail</link>)", ['time' => \series\TaskUi::convertTime($workingTimeNoSeries), 'year' => $e['season'], 'link' => '<a href="/series/analyze:tasks?id='.$e['farm']['id'].'&status='.\series\Task::DONE.'&plant='.$e['plant']['id'].'&year='.$e['season'].'&noSeries" target="_blank">']).'</p>'
						);
					}

					$sales = '<p class="util-info mt-1">';
						$sales .= s("Sélectionnez les ventes qui correspondent aux séries que vous avez indiquées. Cela vous permettra d'obtenir des informations intéressantes comme un chiffre d'affaires au mètre carré ou par heure travaillée pour ces séries.");
						if($e['farm']['selling']['hasVat']) {
							$sales .= ' '.s("Les ventes sont exprimées hors taxes.");
						}
					$sales .= '</p>';
					$sales .= '<div class="report-create-period">';
						$sales .= $form->dynamicField($e, 'firstSaleAt');
						$sales .= $form->dynamicField($e, 'lastSaleAt');
					$sales .= '</div>';
					$sales .= '<div id="report-create-products">';
						$sales .= (new \analyze\ReportUi())->getProductsField($cProduct, $e['from']);
					$sales .= '</div>';

					$h .= $form->group(
						s("Ventes à inclure au rapport"),
						$sales
					);

				}

			}
			if($e['plant']->notEmpty()) {

				$h .= '<div id="report-create-submit">';
					$h .= $form->group(content: $this->getSubmit($form, $e));
				$h .= '</div>';

			}

		$h .= $form->close();

		return new \Panel(
			id: 'panel-report-create',
			title: s("Créer un rapport"),
			body: $h
		);

	}

	public function getSeries(\util\FormUi $form, Report $eReport, Report $eReportFrom) {

		$eReport->expects(['cCultivation', 'cSeries']);

		$cCultivation = $eReport['cCultivation'];

		if($eReportFrom->empty()) {
			$cultivationsSelected = $eReport['cSeries']->getColumnCollection('cultivation')->getIds();
		} else {
			$cultivationsSelected = $eReportFrom['cCultivation']->getColumnCollection('cultivation')->getIds();
		}

		$form = new \util\FormUi();

		$h = '<p class="util-info mt-1">';
			$h .= s("Sélectionnez les séries que vous voulez inclure au rapport. Cela permettra d'importer automatiquement le temps de travail et les surfaces utilisées. Les coûts directs correspondent par exemple au prix des semences ou des plants.");
			if($eReport['farm']['selling']['hasVat']) {
				$h .= ' '.s("Les coûts directs sont exprimées hors taxes.");
			}
		$h .= '</p>';

		$h .= '<div class="report-create-series stick-xs">';

			$h .= '<div class="util-grid-header report-create-series-name">';
				if($cCultivation->count() > 2) {
					$h .= '<label class="report-create-checkbox" title="'.s("Tout cocher / Tout décocher").'">';
						$h .= '<input type="checkbox" '.($cCultivation->count() === count($cultivationsSelected) ? 'checked' : '').' '.attr('onclick', 'CheckboxField.all(this, \'[name^="cultivations[]"]\', node => Report.selectCheckbox(node))').'"/>';
					$h .= '</label>';
				}
				$h .= s("Série");
			$h .= '</div>';
			$h .= '<div class="report-create-series-area util-grid-header">'.s("Surface").'</div>';
			$h .= '<div class="report-create-series-working-time util-grid-header">'.s("Temps de travail").'</div>';
			$h .= '<div class="report-create-series-costs util-grid-header">'.s("Coûts directs").'</div>';

			foreach($cCultivation as $eCultivation) {

				$eCultivationFrom = $eReportFrom['cCultivation'][$eCultivation['id']] ?? new Cultivation();
				$checked = in_array($eCultivation['id'], $cultivationsSelected);

				$h .= '<div class="report-create-series-name '.($checked ? '' : 'report-create-disabled').'">';
					$h .= '<label class="report-create-checkbox">';
						$h .= $form->inputCheckbox('cultivations[]', $eCultivation['id'], [
							'checked' => $checked,
							'oninput' => 'Report.selectCheckbox(this)'
						]);
					$h .= '</label>';
					$h .= \series\SeriesUi::link($eCultivation['series'], TRUE);
				$h .= '</div>';

				$h .= '<div class="text-end">';
					$h .= '<div class="input-group">';
						$h .= $form->number('area['.$eCultivation['id'].']', $eCultivation['series']['area'], [
							'min' => 0,
							'oninput' => 'Report.refreshStats()'
						]);
						$h .= '<div class="input-group-addon">'.s("m²").'</div>';
					$h .= '</div>';
				$h .= '</div>';

				$h .= '<div class="text-end">';
					$h .= '<div class="input-group">';
						$h .= $form->number('workingTime['.$eCultivation['id'].']', round($eCultivation['workingTime'], 2), [
							'min' => 0,
							'step' => 0.01,
							'oninput' => 'Report.refreshStats()'
						]);
						$h .= '<div class="input-group-addon">'.s("h").'</div>';
					$h .= '</div>';
				$h .= '</div>';

				$h .= '<div class="text-end">';
					$h .= '<div class="input-group">';
						$h .= $form->number('costs['.$eCultivation['id'].']', $eCultivationFrom['costs'] ?? 0, attributes: [
							'min' => 0,
							'oninput' => 'Report.refreshStats()'
						]);
						$h .= '<div class="input-group-addon">'.s("€").'</div>';
					$h .= '</div>';
				$h .= '</div>';

			}

			$h .= '<div class="report-create-series-total text-end">';
				$h .= '<div>'.s("Total des coûts directs :").'</div>';
				$h .= '<label class="report-create-costs-user">';
					$h .= $form->inputCheckbox('costsUser', attributes: ['onclick' => 'Report.selectCosts(this)']).' '.s("J'indique directement le total");
				$h .= '</label>';
			$h .= '</div>';

			$h .= '<div>';
				$h .= $form->dynamicField($eReport, 'costs');
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getProductsField(\Collection $cProduct, Report $eReportFrom = new Report()) {

		if($cProduct->empty()) {
			return '<div class="util-info">'.s("Il n'y a aucun produit vendu pour cette espèce.").'</div>';
		}

		if($eReportFrom->empty()) {
			$productsSelected = $cProduct->getIds();
		} else {
			$productsSelected = $eReportFrom['cProduct']->getColumnCollection('product')->getIds();
		}

		$form = new \util\FormUi();

		$h = '<div class="report-create-series stick-xs tr-even" onrender="Report.refreshStats()">';

			$h .= '<div class="util-grid-header report-create-series-name">';
				if($cProduct->count() > 2) {
					$h .= '<label class="report-create-checkbox report-create-checkbox-all" title="'.s("Tout cocher / Tout décocher").'">';
						$h .= '<input type="checkbox" checked '.attr('onclick', 'CheckboxField.all(this, \'[name^="products[]"]\', node => Report.selectCheckbox(node))').'"/>';
					$h .= '</label>';
				}
				$h .= s("Produit");
			$h .= '</div>';
			$h .= '<div class="util-grid-header text-end">'.s("Volume").'</div>';
			$h .= '<div class="util-grid-header text-end">'.s("Ventes").'</div>';
			$h .= '<div class="util-grid-header text-end">'.s("Prix moyen").'</div>';

			foreach($cProduct as $eProduct) {

				$unit = \main\UnitUi::getSingular($eProduct['unit'], short: TRUE);

				$quantity = round($eProduct['sales']['quantity'] ?? 0);
				$turnover = round($eProduct['sales']['turnover'] ?? 0);

				$checked = in_array($eProduct['id'], $productsSelected);

				$h .= '<div class="report-create-series-name '.($checked ? '' : 'report-create-disabled').'">';
					$h .= '<label class="report-create-checkbox">';
						$h .= $form->inputCheckbox('products[]', $eProduct['id'], [
							'checked' => $checked,
							'oninput' => 'Report.selectCheckbox(this)'
						]);
					$h .= '</label>';
					$h .= \selling\ProductUi::link($eProduct, TRUE);
				$h .= '</div>';

				$h .= '<div class="text-end">';
					$h .= '<div class="input-group">';
						$h .= $form->number('quantity['.$eProduct['id'].']', $quantity, [
							'min' => 0,
							'oninput' => 'Report.refreshStats()',
							'data-product' => $eProduct['id']
						]);
						$h .= '<div class="input-group-addon">'.$unit.'</div>';
					$h .= '</div>';
				$h .= '</div>';

				$h .= '<div class="text-end">';
					$h .= '<div class="input-group">';
						$h .= $form->number('turnover['.$eProduct['id'].']', $turnover, [
							'min' => 0,
							'oninput' => 'Report.refreshStats()',
							'data-product' => $eProduct['id']
						]);
						$h .= '<div class="input-group-addon">'.s("€").'</div>';
					$h .= '</div>';
				$h .= '</div>';

				$h .= '<div class="text-end">';
					$h .= '<div>';
						$h .= '<span data-ref="report-price-'.$eProduct['id'].'">'.($quantity > 0 ? round($turnover / $quantity, 1) : '?').'</span>';
						$h .= ' '.s("€ / {unit}", ['unit' => $unit]);
					$h .= '</div>';
				$h .= '</div>';

			}

		$h .= '</div>';

		return $h;

	}

	public function getSubmit(\util\FormUi $form, Report $e): string {

		$h = '<h3>'.s("Synthèse du rapport").'</h3>';

		$h .= '<ul class="util-summarize">';
			$h .= '<li>';
				$h .= '<h5>'.s("Ventes").'</h5>';
				$h .= '<div>'.s("{value} €", '<span id="report-create-turnover"></span>').'</div>';
			$h .= '</li>';
			$h .= '<li>';
				$h .= '<h5>'.s("Coûts directs").'</h5>';
				$h .= '<div>'.s("{value} €", '<span id="report-create-costs"></span>').'</div>';
			$h .= '</li>';
			$h .= '<li>';
				$h .= '<h5>'.s("Surface").'</h5>';
				$h .= '<div>'.s("{value} m²", '<span id="report-create-area"></span>').'</div>';
			$h .= '</li>';
			$h .= '<li>';
				$h .= '<h5>'.s("Temps de travail").'</h5>';
				$h .= '<div>'.s("{value} h", '<span id="report-create-working-time"></span>').'</div>';
			$h .= '</li>';
		$h .= '</ul>';
		$h .= $form->submit(s("Créer le rapport"));

		return $h;

	}

	public function update(Report $eReport): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/analyze/report:doUpdate');

			$h .= $form->hidden('id', $eReport['id']);

			$h .= $form->dynamicGroup($eReport, 'name');
			$h .= $form->dynamicGroup($eReport, 'description');

			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Modifier un rapport"),
			body: $h
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Report::model()->describer($property, [
			'name' => s("Nom du rapport"),
			'description' => s("Commentaires"),
			'season' => s("Saison"),
			'plant' => s("Espèce"),
		]);

		switch($property) {

			case 'name' :
				$d->prepend = function(\util\FormUi $form, Report $e) {
					return $form->addon(encode($e['plant']['name']).' '.$e['season'].' -');
				};
				$d->after = \util\FormUi::info(s("Le nom de rapport est facultatif mais recommandé"));
				break;

			case 'season' :
				$d->field = 'select';
				$d->values = function(Report $e) {
					return array_combine($e['farm']->getSeasons(), $e['farm']->getSeasons());
				};
				$d->attributes['mandatory'] = TRUE;
				$d->attributes['onchange'] = 'Report.refreshCreateSeries()';
				break;

			case 'plant' :
				$d->autocompleteDispatch = '#report-create';
				$d->autocompleteBody = function(\util\FormUi $form, Report $e) {
					$e->expects(['farm', 'cPlant']);
					return [
						'farm' => $e['farm']['id'],
						'ids' => $e['cPlant']->getIds()
					];
				};
				(new \plant\PlantUi())->query($d);
				break;

			case 'cultivations' :
				$d->field = function(\util\FormUi $form, Report $e) {
					return (new ReportUi())->getSeries($form, $e, $e['from']);
				};
				break;

			case 'costs' :
				$d->name = 'costsTotal';
				$d->attributes = [
					'disabled',
					'oninput' => 'Report.refreshStats()'
				];
				$d->append = s("€");
				break;

			case 'firstSaleAt' :
				$d->prepend = s("Début des ventes");
				$d->attributes['onchange'] = 'Report.refreshCreateProducts()';
				break;

			case 'lastSaleAt' :
				$d->prepend = s("Fin des ventes");
				$d->attributes['onchange'] = 'Report.refreshCreateProducts()';
				break;

			case 'testAreaOperator' :
			case 'testWorkingTimeOperator' :
			case 'testCostsOperator' :
			case 'testTurnoverOperator' :
				$d->field = 'select';
				$d->values = [
					Report::ABSOLUTE => match($property) {
						'testAreaOperator' => s("m²"),
						'testWorkingTimeOperator' => s("h"),
						'testCostsOperator', 'testTurnoverOperator' => s("€")
					},
					Report::RELATIVE => s("%")
				];
				$d->attributes['mandatory'] = TRUE;
		}

		return $d;

	}

}
?>
