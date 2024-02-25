<?php
namespace plant;

class ForecastUi {

	public function __construct() {

		\Asset::css('plant', 'forecast.css');
		\Asset::js('plant', 'forecast.js');

	}

	public function create(Forecast $eForecast): \Panel {

		$form = new \util\FormUi();

		$eForecast['unit'] = Forecast::KG;

		$attributes = [
			'data-kg' => \main\UnitUi::getSingular(Forecast::KG, short: TRUE),
			'data-bunch' => \main\UnitUi::getSingular(Forecast::BUNCH, short: TRUE),
			'data-unit' => \main\UnitUi::getSingular(Forecast::UNIT, short: TRUE)
		];

		$h = $form->openAjax('/plant/forecast:doCreate', $attributes);

			$h .= $form->hidden('farm', $eForecast['farm']['id']);
			$h .= $form->hidden('season', $eForecast['season']);

			$h .= $form->dynamicGroups($eForecast, ['plant*', 'unit*']);
			$h .= $this->write($form, $eForecast);

			$h .= $form->group(
				content: $form->submit(s("Ajouter"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Ajouter une espèce au prévisionnel"),
			body: $h
		);

	}

	public function update(Forecast $eForecast): \Panel {

		$eForecast->expects(['nCultivation']);

		$form = new \util\FormUi();

		$h = $form->openAjax('/plant/forecast:doUpdate');

			$h .= $form->hidden('id', $eForecast['id']);

			$h .= $form->group(
				s("Espèce"),
				PlantUi::link($eForecast['plant'])
			);

			$h .= $this->write($form, $eForecast);

			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();


		$footer = '<div class="text-end">';
			if($eForecast['nCultivation'] === 0) {
				$footer .= '<a data-ajax="/plant/forecast:doDelete" post-id="'.$eForecast['id'].'" class="btn btn-danger" data-confirm="'.s("Voulez-vous vraiment supprimer cette espèce du prévisionnel ?").'">'.s("Supprimer du prévisionnel").'</a>';
			} else {
				$footer .= '<a class="btn btn-danger" disabled title="'.AlertUi::getError('Forecast::deleteUsed').'">'.s("Supprimer du prévisionnel").'</a>';
			}
		$footer .= '</div>';


		return new \Panel(
			title: s("Modifier une espèce du prévisionnel"),
			body: $h,
			footer: $footer
		);

	}

	protected function write(\util\FormUi $form, Forecast $eForecast): string {

		$h = $form->dynamicGroups($eForecast, ['harvestObjective', 'privatePrice', 'proPrice']);
		$h .= $form->group(
			s("Répartition des ventes"),
			'<div class="forecast-create-part">'.
				$form->inputGroup(
					$form->dynamicField($eForecast, 'privatePart').$form->addon(s("% aux particuliers"))
				).
				$form->inputGroup(
					$form->dynamicField($eForecast, 'proPart').$form->addon(s("% aux professionnels"))
				).
			'</div>',
			['wrapper' => 'privatePart proPart']
		);

		return $h;

	}

	/**
	 * Describe properties
	 */
	public static function p(string $property): \PropertyDescriber {

		$d = Forecast::model()->describer($property, [
			'harvestObjective' => s("Objectif de volume"),
			'privatePrice' => s("Prix moyen pour les particuliers"),
			'proPrice' => s("Prix moyen pour les professionnels"),
			'plant' => s("Espèce cultivée"),
			'unit' => s("Unité de vente"),
		]);

		switch($property) {

			case 'plant' :
				$d->autocompleteBody = function(\util\FormUi $form, Forecast $e) {
					$e->expects(['farm']);
					return [
						'farm' => $e['farm']['id']
					];
				};
				(new \plant\PlantUi())->query($d);
				break;

			case 'harvestObjective' :
				$d->append = fn(\util\FormUi $form, Forecast $e) => $form->addon('<span data-ref="forecast-unit">'.\main\UnitUi::getSingular($e['unit'], short: TRUE).'</span>');
				break;


			case 'unit' :
				$d->values = [
					Forecast::UNIT => s("À la pièce"),
					Forecast::BUNCH => s("À la botte"),
					Forecast::KG => s("Au kg")
				];
				$d->attributes += [
					'callbackRadioAttributes' => function() {
						return ['oninput' => 'Forecast.changeUnit(this, "forecast-unit")'];
					}
				];
				break;

			case 'proPrice' :
			case 'privatePrice' :
				$d->append = fn(\util\FormUi $form, Forecast $e) => $form->addon(' € / <span data-ref="forecast-unit">'.\main\UnitUi::getSingular($e['unit'], short: TRUE).'</span>');

			case 'proPart' :
			case 'privatePart' :
				$d->attributes += [
					'oninput' => 'Forecast.changePart(this)'
				];
				break;

		}

		return $d;

	}

}
?>
