<?php
namespace series;

class Task extends TaskElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'plant' => ['name', 'fqn', 'vignette'],
			'farm' => ['name', 'vignette', 'featureTime'],
			'cultivation' => ['startWeek', 'startAction', 'mainUnit', 'density', 'bunchWeight', 'unitWeight'],
			'variety' => ['name'],
			'category' => ['fqn', 'name'],
			'series' => ['name', 'cycle', 'mode', 'season', 'area', 'areaTarget', 'length', 'lengthTarget', 'bedWidth', 'use'],
			'action' => ['fqn', 'name', 'short', 'categories', 'color', 'pace', 'series'],
			'repeat' => ['frequency', 'current', 'stop', 'completed'],
			'delayed' => function(Task $e): bool {

				$week = currentWeek();

				return (
					$e['status'] === Task::TODO and
					$e['plannedWeek'] !== NULL and
					strcmp($e['plannedWeek'], $week) < 0
				);

			},
			'harvestQuality' => ['name'],
			'createdBy' => ['firstName', 'lastName', 'visibility'],
		];

	}

	public static function validateBatch(\Collection $cTask): void {

		if($cTask->empty()) {
			throw new \FailAction('series\Task::tasks.check');
		} else {

			$eFarm = $cTask->first()['farm'];

			foreach($cTask as $eTask) {

				if($eTask['farm']['id'] !== $eFarm['id']) {
					throw new \NotExpectedAction('Different farms');
				}

			}
		}

	}

	public static function validateSameAction(\Collection $cTask, ?\farm\Action $eAction = NULL): void {

		if($cTask->empty()) {
			return;
		}

		$eAction ??= $cTask->first()['action'];

		foreach($cTask as $eTask) {

			if($eTask['action']['id'] !== $eAction['id']) {
				throw new \NotExpectedAction($eAction ? 'Unexpected actions' : 'Different actions');
			}

		}

	}

	public function canRead(): bool {

		$this->expects(['farm']);
		return $this['farm']->canWrite();

	}

	public function canWrite(): bool {

		$this->expects(['farm']);
		return $this['farm']->canTask();

	}

	public function notInSeries(): bool {
		return $this['series']->empty();
	}

	public function isTodo(): bool {
		return ($this['status'] === Task::TODO);
	}

	public function neverDone(): bool {
		return ($this['doneWeek'] === NULL);
	}

	public function isDone(): bool {
		return ($this['status'] === Task::DONE);
	}

	public function canPostpone(): bool {
		return ($this->isTodo() and $this['plannedWeek'] !== NULL);
	}

	public function canSoil(): bool {

		$this->expects([
			'series',
			'category' => ['fqn']
		]);

		return (
			$this['series']->empty() and
			in_array($this['category']['fqn'], ['culture', 'production'])
		);

	}

	public function format(string $property, array $options = []): ?string {

		switch($property) {

			case 'harvest' :
				if($this[$property] > 0) {
					return \production\CropUi::getYield($this, $property, 'harvestUnit', $options);
				} else {
					return NULL;
				}

			case 'fertilizer' :
				if($this[$property] !== NULL) {

					[$major, $minor] = (new \production\FlowUi())->getFertilizer($this);

					$h = implode('&nbsp;&nbsp;', $major);
					if($major and $minor) {
						$h .= ' + ';
					}
					$h .= implode('&nbsp;&nbsp;', $minor);

					return $h;

				} else {
					return NULL;
				}

			default :
				return parent::format($property, $options);

		}

	}

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		$fw = new \FailWatch();

		return parent::build($properties, $input, $callbacks + [

			'planned.check' => function(?array $planned): bool {

				$this->expects(['status']);

				if($planned !== NULL and isset($planned['date'])) {

					if(\Filter::check('date', $planned['date'])) {
						$this['plannedDate'] = $planned['date'];
						$this['plannedWeek'] = toWeek($planned['date']);
					} else {
						return FALSE;
					}

				} else if($planned !== NULL and isset($planned['week'])) {

					if(\Filter::check('week', $planned['week'])) {
						$this['plannedDate'] = NULL;
						$this['plannedWeek'] = $planned['week'];
					} else {
						return FALSE;
					}


				} else {

					$this['plannedDate'] = NULL;
					$this['plannedWeek'] = NULL;

				}

				return TRUE;

			},

			'done.check' => function(array $done): bool {

				$this->expects(['status']);

				if(isset($done['date'])) {

					if(\Filter::check('date', $done['date'])) {
						$this['doneDate'] = $done['date'];
						$this['doneWeek'] = toWeek($done['date']);
					} else {
						return FALSE;
					}

				} else if(isset($done['week'])) {

					if(\Filter::check('week', $done['week'])) {
						$this['doneDate'] = NULL;
						$this['doneWeek'] = $done['week'];
					} else {
						return FALSE;
					}


				}

				return TRUE;

			},

			'action.check' => function(\farm\Action $eAction): bool {

				$this->expects(['farm', 'category']);

				return (
					\farm\ActionLib::canUse($eAction, $this['farm']) and
					in_array($this['category']['id'], $eAction['categories'])
				);

			},

			'action.harvest' => function(\farm\Action $eAction) use ($for): bool {

				if($for === 'update') {

					$this->expects([
						'action' => ['fqn'],
					]);

					return (
						$this['action']['fqn'] !== ACTION_RECOLTE and
						$eAction['fqn'] !== ACTION_RECOLTE
					);

				} else {
					return TRUE;
				}

			},

			'repeatMaster.check' => function() use ($input) {

				// Pas de fréquence, pas de répétition
				$frequency = var_filter($input['frequency'] ?? NULL, '?string');

				if($frequency === NULL) {
					$this['repeatMaster'] = new Repeat();
					return TRUE;
				}

				$eRepeat = new Repeat([
					'task' => $this
				]);

				if($eRepeat->build(['frequency', 'stop'], $input)) {
					$this['repeatMaster'] = $eRepeat;
					return TRUE;
				} else {
					return FALSE;
				}

			},

			'harvest.check' => function(?float &$harvest) use ($fw): bool {

				if($fw->has('Task::action.check')) { // L'action génère déjà une erreur
					return TRUE;
				}

				$this->expects([
					'action' => ['fqn'],
					'status',
				]);

				if($this['action']['fqn'] !== ACTION_RECOLTE) {
					$harvest = NULL;
					return TRUE;
				}

				if($this['action']['fqn'] === ACTION_RECOLTE and $this['status'] === Task::DONE and $harvest === NULL) {
					return FALSE;
				} else {
					return TRUE;
				}

			},

			'harvestQuality.check' => function(\plant\Quality &$eQuality) use ($fw): bool {

				if($fw->has('Task::action.check')) { // L'action génère déjà une erreur
					return TRUE;
				}


				$this->expects([
					'action' => ['fqn'],
				]);

				if($this['action']['fqn'] !== ACTION_RECOLTE) {
					$eQuality = new \plant\Quality();
					return TRUE;
				}

				return (
					$eQuality->empty() or
					\plant\Quality::model()->exists($eQuality)
				);

			},

			'harvestMore.cast' => function(string &$harvest): bool {

				$harvest = (float)$harvest;
				return TRUE;

			},

			'harvestMore.negative' => function(?float $harvest): bool {

				$this->expects([
					'harvest',
				]);

				if(
					$this['action']['fqn'] === ACTION_RECOLTE and
					$this['harvest'] + $harvest >= 0
				) {
					$this['harvestMore'] = $harvest;
					return TRUE;
				} else {
					return FALSE;
				}

			},

			'fertilizer.check' => function(?array &$fertilizer) use ($fw): bool {

				if($fw->has('Task::action.check')) { // L'action génère déjà une erreur
					return TRUE;
				}

				$this->expects([
					'action' => ['fqn'],
				]);

				if($this['action']['fqn'] !== ACTION_FERTILISATION) {
					$fertilizer = NULL;
					return TRUE;
				}

				if($fertilizer !== NULL) {
					$fertilizer = \farm\RoutineLib::checkFertilizer($fertilizer);
				}

				return TRUE;

			},

			'cultivation.check' => function(Cultivation $eCultivation) use ($for): bool {

				$this->expects([
					'category' => ['fqn']
				]);

				if($this['category']['fqn'] !== CATEGORIE_CULTURE) {
					return FALSE;
				}

				switch($for) {

					case 'create':

						$this->expects(['series']);

						return (
							$eCultivation->empty() or
							Cultivation::model()
								->select(['mainUnit'])
								->whereSeries($this['series'])
								->get($eCultivation)
						);

					case 'update':

						$this->expects(['plant', 'action', 'season', 'cultivation', 'series']);

						if($this['series']->empty()) {
							return FALSE;
						}

						// On peut changer de cultivation dans deux cas

						// Cas 1
						// Sur la même espèce et sur la même saison...

						if(
							$eCultivation->notEmpty() and
							Cultivation::model()
								->select(['series', 'plant', 'mainUnit', 'bunchWeight', 'unitWeight'])
								->wherePlant($this['plant']) // On peut changer de Cultivation, mais sur la même espèce...
								->whereSeason($this['season']) // ... et sur la même saison
								->get($eCultivation)
						) {
							$this['oldCultivation'] = $this['cultivation'];
							return TRUE;
						}

						// Cas 2
						// Dans la même série et pas sur les récoltes
						if(
							$this['action']['fqn'] !== ACTION_RECOLTE and
							(
								$eCultivation->empty() or
								Cultivation::model()
									->select([
										'series' => ['season'],
										'plant'
									])
									->whereSeries($this['series'])
									->get($eCultivation)
							)
						) {
							$this['oldCultivation'] = $this['cultivation'];
							return TRUE;
						}

						return FALSE;

				}

			},

			'variety.check' => function(\plant\Variety $eVariety): bool {

				$this->expects(['cultivation']);

				return $eVariety->empty() or Slice::model()
					->whereCultivation($this['cultivation'])
					->whereVariety($eVariety)
					->exists();

			},

			'quality.check' => function(\plant\Quality $eQuality): bool {

				$this->expects(['plant']);

				return $eQuality->empty() or \plant\Quality::model()
					->wherePlant($this['plant'])
					->exists($eQuality);

			},

			'toolsList.check' => function(mixed $tools): bool {

				try {
					$this->expects(['action']);
				} catch(\Exception) {
					return FALSE;
				}

				if($this['action']->empty()) {
					$this['cTool'] = new \Collection();
					return TRUE;
				}

				$tools = (array)($tools ?? []);

				$cTool = \farm\Tool::model()
					->select('id', 'farm')
					->whereId('IN', $tools)
					->where('action IS NULL or action = '.$this['action']['id'])
					->getCollection()
					->filter(fn($eTool) => $eTool->canRead());

				$this['cTool'] = $cTool;
				return TRUE;

			},

			'plant.check' => function(\plant\Plant $ePlant) use ($fw): bool {

				if($fw->has('Task::action.check')) { // L'action génère déjà une erreur
					return TRUE;
				}

				$this->expects([
					'category' => ['fqn'],
					'action' => ['fqn']
				]);

				// Plante obligatoire pour les récoltes facultative sinon
				if($ePlant->empty()) {

					if(
						$this['category']['fqn'] === CATEGORIE_CULTURE and
						$this['action']['fqn'] === ACTION_RECOLTE
					) {
						return FALSE;
					} else {
						return TRUE;
					}

				}

				return (
					$ePlant->empty() or
					\plant\Plant::model()
						->select('name') // Requis pour traitement futur
						->get($ePlant)
				);
			},

			'status.check' => function(string $status): bool {

				$this->expects(['status']);

				if(empty($this['id'])) {
					return in_array($status, [Task::DONE, Task::TODO]);
				} else {

					switch($status) {

						case Task::DONE :
							return FALSE;

						case Task::TODO :
							return ($this['status'] === Task::DONE);

						default :
							return FALSE;

					}

				}

			},

		]);

	}

}
?>