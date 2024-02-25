<?php
namespace farm;

class Farm extends FarmElement {

	const DEMO = 1;

	public static function getSelection(): array {

		return parent::getSelection() + [
			'calendarMonths' => new \Sql('IF(calendarMonthStart IS NULL, 0, 12 - calendarMonthStart + 1) + 12 + IF(calendarMonthStop IS NULL, 0, calendarMonthStop)', 'int'),
		];

	}

	public function validateSeason(int $season): self {

		if($this->checkSeason($season) === FALSE) {
			throw new \NotExpectedAction($this);
		}

		return $this;

	}

	public function getSeasons(): array {
		$seasons = [];
		for($season = $this['seasonLast']; $season >= $this['seasonFirst']; $season--) {
			$seasons[] = $season;
		}
		return $seasons;
	}

	public function getRotationSeasons(int $lastSeason): array {

		$this->expects(['rotationYears', 'seasonFirst']);

		$seasons = [];
		for($season = $lastSeason; $season >= $this['seasonFirst'] and count($seasons) < $this['rotationYears']; $season--) {
			$seasons[] = $season;
		}

		return $seasons;

	}

	public function checkSeason(int $season): bool {
		return ($season >= $this['seasonFirst'] and $season <= $this['seasonLast']);
	}

	public function active(): bool {
		return ($this['status'] === Farm::ACTIVE);
	}

	// Peut accéder aux pages d'analyse des données
	public function canAnalyze(): bool {
		return (
			$this->canManage() or
			$this->isRole(Farmer::OBSERVER)
		);
	}

	// Peut voir le planning
	public function canPlanning(): bool {
		return (
			$this->canManage() or
			$this->isRole(Farmer::OBSERVER) === FALSE
		);
	}

	// Peut voir les données personnelles des clients et la page de gestion d'équipe
	public function canPersonalData(): bool {
		return $this->canManage();
	}

	// Peut accéder en lecture aux pages de commercialisation et en écriture aux pages de ventes
	public function canSelling(): bool {
		return (
			$this->canManage() or
			$this->isRole(Farmer::PERMANENT)
		);
	}

	// Peut créer ou modifier des interventions
	public function canTask(): bool {
		return (
			$this->canManage() or
			$this->isRole(Farmer::PERMANENT)
		);
	}

	// Peut gérer son temps de travail et commenter les interventions
	public function canWork(): bool {
		return (
			$this->canManage() or
			$this->isRole(Farmer::SEASONAL) or
			$this->isRole(Farmer::PERMANENT)
		);
	}

	// Peut gérer la ferme
	public function canManage(): bool {
		return $this->isRole(Farmer::OWNER);

	}

	public function isRole(string $role): bool {

		if($this->empty()) {
			return FALSE;
		}

		$eFarmer = $this->getFarmer();

		return (
			$eFarmer->notEmpty() and
			$eFarmer['role'] === $role
		);

	}

	public function canCreate(): bool {
		return (\user\ConnectionLib::getOnline()->isRole('customer') === FALSE);
	}

	public function canWrite(): bool {

		if($this->empty()) {
			return FALSE;
		}

		return $this->getFarmer()->notEmpty();

	}

	public function canRemote(): bool {
		return $this->canRead() or GET('key') === \Setting::get('selling\remoteKey');
	}

	public function canShop(): bool {

		$this->expects(['legalEmail', 'legalName']);

		return (
			$this['legalEmail'] !== NULL and
			$this['legalName'] !== NULL
		);

	}

	public function saveFeaturesAsSettings() {

		foreach(['featureTime', 'featureDocument'] as $feature) {
			\Setting::set('farm\\'.$feature, $this[$feature]);
		}

	}

	public function hasFeatureTime(): bool {

		$this->expects(['featureTime']);

		return $this['featureTime'];

	}

	public function hasFeatureDocument(string $type): bool {

		$this->expects(['featureDocument']);

		return match($type) {
			\selling\Sale::PRO => $this->hasFeatureDocumentPro(),
			\selling\Sale::PRIVATE => $this->hasFeatureDocumentPrivate(),
		};

	}

	public function hasFeatureDocumentPro(): bool {

		$this->expects(['featureDocument']);

		return in_array($this['featureDocument'], [Farm::ALL, Farm::PRO]);

	}

	public function hasFeatureDocumentPrivate(): bool {

		$this->expects(['featureDocument']);

		return in_array($this['featureDocument'], [Farm::ALL, Farm::PRIVATE]);

	}

	public function getFarmer(): Farmer {

		$this->expects(['id']);

		return FarmerLib::getOnline()[$this['id']] ?? new Farmer();

	}

	public function getHomeUrl(): string {

		if($this->canPlanning()) {
			return FarmUi::urlPlanningWeekly($this);
		} else {
			return FarmUi::urlCultivation($this, Farmer::SERIES, Farmer::AREA);
		}

	}

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		return parent::build($properties, $input, $callbacks + [

			'rotationExclude.prepare' => function(mixed &$plants): bool {

				$this->expects(['id']);

				$plants = (array)($plants ?? []);

				$plants = \plant\Plant::model()
					->select('id')
					->whereId('IN', $plants)
					->whereFarm($this)
					->getColumn('id');

				return TRUE;

			},

			'place.required' => function(?string $place) use ($input) {

				$required = $input['placeRequired'] ?? FALSE;

				if($required) {
					return ($place !== NULL);
				} else {
					return TRUE;
				}

			},

			'placeLngLat.check' => function(?array &$placeLngLat) {

				$this->expects(['place']);

				if($this['place'] !== NULL) {

					if(
						$placeLngLat === NULL or
						Farm::model()->check('placeLngLat', $placeLngLat) === FALSE
					) {
						Farm::fail('place.check');
					}

				} else {
					$placeLngLat = NULL;
				}

				return TRUE;

			},

			'defaultBedWidth.size' => function(?int $defaultBedWidth) {

				if($defaultBedWidth === NULL) {
					return TRUE;
				}

				return ($defaultBedWidth >= 5);

			}

		]);

	}

}
?>