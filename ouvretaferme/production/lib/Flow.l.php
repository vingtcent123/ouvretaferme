<?php
namespace production;

class FlowLib extends FlowCrud {

	public static function getPropertiesCreate(): array {
		return ['action', 'crop', 'description', 'fertilizer', 'seasonOnly', 'seasonStart', 'seasonStop', 'weekOnly', 'yearOnly', 'weekStart', 'yearStart', 'weekStop', 'yearStop', 'frequency', 'toolsList'];
	}

	public static function getPropertiesUpdate(): array {
		return self::getPropertiesCreate();
	}

	public static function getTools(Flow $e): \Collection {

		return Requirement::model()
			->select([
				'tool' => \farm\Tool::getSelection()
			])
			->whereFlow($e)
			->getColumn('tool')
			->sort('name', natural: TRUE);

	}

	public static function getBySequence(Sequence $eSequence, ?int $season = NULL, array $properties = []): \Collection {

		if($season !== NULL) {

			Flow::model()->where('
				seasonOnly = '.$season.' OR
				'.$season.' BETWEEN seasonStart AND seasonStop OR
				('.$season.' >= seasonStart AND seasonStop IS NULL)
			');

		}

		return Flow::model()
			->select(Flow::getSelection() + [
				'cRequirement' => Requirement::model()
					->select([
						'tool' => ['name', 'vignette']
					])
					->delegateCollection('flow')
			])
			->select($properties)
			->sort([
				new \Sql('IF(weekOnly IS NOT NULL, CAST(weekOnly AS SIGNED) + CAST(yearOnly AS SIGNED) * 100, CAST(weekStart AS SIGNED) + CAST(yearStart AS SIGNED) * 100)'),
				'id' => SORT_ASC
			])
			->whereSequence($eSequence)
			->getCollection();

	}

	public static function getByCrops(\Collection $cCrop, ?int $season, array $properties = []): \Collection {

		if($season !== NULL) {

			Flow::model()->where('
				seasonOnly = '.$season.' OR
				'.$season.' BETWEEN seasonStart AND seasonStop OR
				('.$season.' >= seasonStart AND seasonStop IS NULL)
			');

		}

		return Flow::model()
			->select(Flow::getSelection() + [
				'cRequirement' => Requirement::model()
					->select([
						'farm', 'tool'
					])
					->delegateCollection('flow')
			])
			->select($properties)
			->whereCrop('IN', $cCrop)
			->sort([
				'weekStart' => SORT_ASC,
				'id' => SORT_ASC
			])
			->getCollection();

	}

	/**
	 * Pas nécessaire de recalculer les semaines de démarrage étant donné que ces champs sont dupliqués tel quel
	 */
	public static function duplicateFromSequence(Sequence $eSequence, Sequence $eSequenceNew, \Collection $cCrop): \Collection {

		$cFlow = Flow::model()
			->select(Flow::model()->getProperties())
			->whereSequence($eSequence)
			->getCollection(NULL, NULL, 'id');

		// Copie des tâches
		foreach($cFlow as $eFlow) {

			$eFlow['id'] = NULL;
			$eFlow['sequence'] = $eSequenceNew;

			if($eFlow['crop']->notEmpty()) {
				$eFlow['crop'] = $cCrop[$eFlow['crop']['id']];
			}

			Flow::model()->insert($eFlow);

		}

		return $cFlow;

	}

	public static function changeWeekStart(\Collection $cFlow, int $startWeek): void {

		if($cFlow->empty()) {
			return;
		}

		$weekDifference = ($cFlow->first()['weekOnly'] ?? $cFlow->first()['weekStart']) - $startWeek;

		foreach($cFlow as $eFlow) {

			if($eFlow['weekOnly']) {

				$eFlow['weekOnly'] -= $weekDifference;
				self::moveWeek($eFlow, 'weekOnly', 'yearOnly');

			} else {

				$eFlow['weekStart'] -= $weekDifference;
				self::moveWeek($eFlow, 'weekStart', 'yearStart');

				$eFlow['weekStop'] -= $weekDifference;
				self::moveWeek($eFlow, 'weekStop', 'yearStop');

			}

		}

	}

	private static function moveWeek(Flow $eFlow, string $weekProperty, string $yearProperty): void {

		if($eFlow[$weekProperty] < 1) {
			$eFlow[$weekProperty] += 52;
			$eFlow[$yearProperty]--;
		} else if($eFlow[$weekProperty] > 52) {
			$eFlow[$weekProperty] -= 52;
			$eFlow[$yearProperty]++;
		}

	}

	public static function reorder(Sequence $eSequence, \Collection $cFlow): array {

		$eSequence->expects(['cycle']);

		if($cFlow->empty()) {
			return [];
		}

		$events = [];

		foreach($cFlow as $eFlow) {

			if($eSequence['cycle'] === Sequence::PERENNIAL) {

				if($eFlow['seasonOnly'] !== NULL) {
					$seasons = [$eFlow['seasonOnly']];
				} else {
					$seasons = [];
					$lastSeason = $eFlow['seasonStop'] ?? \Setting::get('maxSeasonStop');
					for($season = $eFlow['seasonStart']; $season <= $lastSeason; $season++) {
						$seasons[] = $season;
					}
				}

			} else {
				$seasons = [0];
			}

			$endless = ($eFlow['seasonStart'] !== NULL and $eFlow['seasonStop'] === NULL);

			foreach($seasons as $season) {

				if($eSequence['cycle'] === Sequence::PERENNIAL) {
					$year = 0;
				} else {
					$year = NULL; // Sera écrasé par la bonne valeur
				}

				if($eFlow['weekOnly'] !== NULL) {

					$events[$season][$year ?? $eFlow['yearOnly']][$eFlow['weekOnly']][] = [
						'position' => $eFlow['positionOnly'],
						'field' => 'only',
						'flow' => $eFlow,
						'endless' => $endless
					];

				} else {

					$events[$season][$year ?? $eFlow['yearStart']][$eFlow['weekStart']][] = [
						'position' => $eFlow['positionStart'],
						'field' => 'start',
						'flow' => $eFlow,
						'endless' => $endless
					];

					$events[$season][$year ?? $eFlow['yearStop']][$eFlow['weekStop']][] = [
						'position' => $eFlow['positionStop'],
						'field' => 'stop',
						'flow' => $eFlow,
						'endless' => $endless
					];

				}

			}

		}


		foreach($events as $season => $years) {

			foreach($years as $year => $weeks) {

				foreach($weeks as $week => $flows) {

					uasort($events[$season][$year][$week], function($a, $b) {

						if($b['position'] === NULL) {
							return -1;
						} else if($a['position'] === NULL) {
							return 1;
						} else {
							return ($a['position'] < $b['position']) ? -1 : 1;
						}

					});

				}

				ksort($events[$season][$year]);

			}

			ksort($events[$season]);

		}

		ksort($events);

		return $events;

	}

	public static function create(Flow $e): void {

		$e->expects([
			'crop',
			'sequence' => ['farm', 'cycle']
		]);

		Flow::model()->beginTransaction();

		$e['farm'] = $e['sequence']['farm'];

		if($e['crop']->notEmpty()) {

			$e['crop']->expects(['plant']);
			$e['plant'] = $e['crop']['plant'];

		}

		Flow::model()->insert($e);

		self::updateTools($e);

		SequenceLib::recalculate($e['farm'], $e['sequence']);

		Flow::model()->commit();

	}

	public static function updateTools(Flow $e): void {

		$e->expects(['crop', 'sequence', 'farm', 'cTool']);

		Requirement::model()
			->whereFlow($e)
			->delete();

		$cRequirement = new \Collection();

		foreach($e['cTool'] as $eTool) {
			$cRequirement[] = new Requirement([
				'tool' => $eTool,
				'flow' => $e,
				'crop' => $e['crop'],
				'sequence' => $e['sequence'],
				'farm' => $e['farm'],
			]);
		}

		Requirement::model()->insert($cRequirement);

	}

	public static function update(Flow $e, array $properties): void {

		Flow::model()->beginTransaction();

		if(array_delete($properties, 'toolsList')) {
			self::updateTools($e);
		}

		if(in_array('crop', $properties)) {

			if($e['crop']->notEmpty()) {

				$e['crop']->expects(['plant']);
				$e['plant'] = $e['crop']['plant'];

			} else {
				$e['plant'] = new \plant\Plant();
			}

			$properties[] = 'plant';

		}

		parent::update($e, $properties);

		if(array_intersect(['weekOnly', 'weekStart', 'weekStop'], $properties)) {
			SequenceLib::recalculate($e['farm'], $e['sequence']);
		}

		Flow::model()->commit();

	}

	public static function updatePosition(Sequence $eSequence, array $positions): void {

		foreach($positions as $position => [$flow, $field]) {

			$update = [
				'only' => 'positionOnly',
				'start' => 'positionStart',
				'stop' => 'positionStop',
			][$field];

			Flow::model()
				->whereId($flow)
				->whereSequence($eSequence)
				->update([
					$update => $position
				]);

		}

	}
	public static function incrementWeekCollection(\Collection $c, int $increment): void {

		// Traitement en PHP, impossible à faire en une seule requête
		foreach($c as $e) {
			self::incrementWeek($e, $increment);
		}

	}


	public static function incrementWeek(Flow $e, int $increment): bool {

		if($increment < -26 or $increment > 26) {
			return FALSE;
		}

		$e->expects([
			'sequence' => ['cycle'],
			'weekOnly', 'weekStart', 'weekStop',
			'yearOnly', 'yearStart', 'yearStop',
		]);

		foreach(['Only', 'Start', 'Stop'] as $property) {

			$propertyWeek = 'week'.$property;
			$propertyYear = 'year'.$property;

			if($e[$propertyWeek] !== NULL) {

				$e[$propertyWeek] += $increment;

				if($e[$propertyWeek] < 1) {

					if($e['sequence']['cycle'] === Sequence::PERENNIAL) {
						Flow::fail('weekTooSoonPerennial');
						return FALSE;
					} else {

						$e[$propertyWeek] += 52;
						$e[$propertyYear]--;

					}


				} else if($e[$propertyWeek] > 52) {

					if($e['sequence']['cycle'] === Sequence::PERENNIAL) {
						Flow::fail('weekTooLatePerennial');
						return FALSE;
					} else {

						$e[$propertyWeek] -= 52;
						$e[$propertyYear]++;

					}

				}

				if($e['sequence']['cycle'] === Sequence::ANNUAL) {

					if(
						$e[$propertyYear] < -1 or
						($e[$propertyYear] === -1 and $e[$propertyWeek] < \Setting::get('minWeekN-1'))
					) {
						Flow::fail('weekTooSoonAnnual');
						return FALSE;
					}

					if(
						$e[$propertyYear] > 1 or
						($e[$propertyYear] === 1 and $e[$propertyWeek] > \Setting::get('maxWeekN+1'))
					) {
						Flow::fail('weekTooLateAnnual');
						return FALSE;
					}

				}

			}

		}

		Flow::model()
			->select([
				'weekOnly', 'weekStart', 'weekStop',
				'yearOnly', 'yearStart', 'yearStop'
			])
			->update($e);

		SequenceLib::recalculate($e['farm'], $e['sequence']);

		return TRUE;

	}

	/**
	 * Conçu pour supprimer des interventions issus du même itinéraire technique
	 */
	public static function deleteCollection(\Collection $c): void {

		Flow::model()->beginTransaction();

		Requirement::model()
			->whereFlow('IN', $c)
			->delete();

		Flow::model()->delete($c);

		SequenceLib::recalculate($c->first()['farm'], $c->first()['sequence']);

		Flow::model()->commit();

	}

	public static function delete(Flow $e): void {

		$e->expects(['id', 'sequence', 'farm']);

		Flow::model()->beginTransaction();

		Requirement::model()
			->whereFlow($e)
			->delete();

		Flow::model()->delete($e);

		SequenceLib::recalculate($e['farm'], $e['sequence']);

		Flow::model()->commit();

	}

}
?>
