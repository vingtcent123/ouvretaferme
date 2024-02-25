<?php
namespace map;

class GreenhouseLib extends GreenhouseCrud {

	public static function getPropertiesCreate(): array {
		return ['name', 'length', 'width', 'seasonFirst'];
	}

	public static function getPropertiesUpdate(): array {
		return ['name', 'length', 'width', 'seasonFirst', 'seasonLast'];
	}

	public static function getByFarm(\farm\Farm $eFarm): \Collection {

		return Greenhouse::model()
			->select(Greenhouse::getSelection())
			->whereFarm($eFarm)
			->sort('name')
			->getCollection();

	}

	public static function getByPlot(Plot $ePlot): \Collection {

		if($ePlot['zoneFill']) {
			Greenhouse::model()->whereZone($ePlot['zone']);
		} else {
			Greenhouse::model()->wherePlot($ePlot);
		}

		return Greenhouse::model()
			->select(Greenhouse::getSelection())
			->sort('name')
			->getCollection(NULL, NULL, 'id');

	}

	public static function putFromZone(\Collection|Zone &$value): void {

		Zone::model()
			->select([
				'cGreenhouse' => Greenhouse::model()
					->select(Greenhouse::getSelection())
					->whereZoneFill(TRUE)
					->sort('name')
					->delegateCollection('zone')
			])
			->get($value);

	}

	public static function create(Greenhouse $e): void {

		$e->expects([
			'plot' => ['zone', 'zoneFill']
		]);

		$e['zone'] = $e['plot']['zone'];
		$e['zoneFill'] = $e['plot']['zoneFill'];

		// Serre sur Zone
		if($e['plot']['zoneFill']) {
			parent::create($e);
		// Serre sur Plot
		} else {

			Greenhouse::model()->beginTransaction();

			Plot::model()->update($e['plot'], [
				'mode' => Plot::GREENHOUSE
			]);

			parent::create($e);

			Bed::model()
				->wherePlot($e['plot'])
				->wherePlotFill(FALSE)
				->update([
					'greenhouse' => $e
				]);

			Greenhouse::model()->commit();

		}


	}

	public static function createForPlot(Plot $ePlot): void {

		$ePlot->expects([
			'greenhouse' => ['length', 'width'],
			'farm', 'seasonFirst',
		]);

		$eGreenhouse = $ePlot['greenhouse'];
		$eGreenhouse['farm'] = $ePlot['farm'];
		$eGreenhouse['plot'] = $ePlot;
		$eGreenhouse['name'] = GreenhouseUi::defaultName();
		$eGreenhouse['seasonFirst'] = $ePlot['seasonFirst'];

		self::create($eGreenhouse);

	}

	public static function update(Greenhouse $e, array $properties): void {

		$e['area'] = new \Sql('length * width');
		$properties[] = 'area';

		Greenhouse::model()->beginTransaction();

		// Changement d'emplacement de la serre
		if(in_array('plot', $properties)) {

			$e['plot']->expects(['zone', 'zoneFill']);

			// Remets la serre à zéro si elle a été changée de bloc ou parcelle
			Bed::model()
				->whereGreenhouse($e)
				->wherePlot('!=', $e['plot'])
				->update([
					'greenhouse' => new Greenhouse()
				]);

			$e['zone'] = $e['plot']['zone'];
			$e['zoneFill'] = $e['plot']['zoneFill'];
			$properties[] = 'zone';
			$properties[] = 'zoneFill';

		}


		Greenhouse::model()
			->select($properties)
			->update($e);

		Greenhouse::model()->commit();

	}

	public static function delete(Greenhouse $e): void {

		$e->expects(['id', 'plot' => ['zoneFill']]);

		Greenhouse::model()->beginTransaction();

			// Serre sur Plot, alors on passe le Plot en plein champ
			if($e['plot']['zoneFill'] === FALSE) {

				Plot::model()->update($e['plot'], [
					'mode' => Plot::OUTDOOR
				]);

			}

			Bed::model()
				->whereGreenhouse($e)
				->update([
					'greenhouse' => new Greenhouse()
				]);

			Greenhouse::model()->delete($e);

		Greenhouse::model()->commit();

	}

}
?>
