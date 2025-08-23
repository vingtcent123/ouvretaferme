<?php
namespace farm;

class Farm extends FarmElement {

	const DEMO = 1;

	protected static array $selling = [];

	public function __construct(array $array = []) {

		parent::__construct($array);

	}

	public function getFarmer(): Farmer {
		return FarmerLib::getOnline()[$this['id']] ?? new Farmer();
	}

	public static function getSelection(): array {

		return parent::getSelection() + [
			'calendarMonths' => new \Sql('IF(calendarMonthStart IS NULL, 0, 12 - calendarMonthStart + 1) + 12 + IF(calendarMonthStop IS NULL, 0, calendarMonthStop)', 'int'),
			'company' => \company\Company::model()
				->select(\company\Company::getSelection())
				->delegateElement('farm'),
		];

	}

	public function isSeasonValid(int $season): bool {

		$this->expects(['seasonFirst', 'seasonLast']);

		return (
			$season >= $this->getFirstValidSeason() and
			$season <= $this->getLastValidSeason()
		);

	}

	public function getFirstValidSeason(): int {

		$this->expects(['seasonFirst']);

		return $this['seasonFirst'] - \Setting::get('farm\calendarLimit');

	}

	public function getLastValidSeason(): int {

		$this->expects(['seasonLast']);

		return $this['seasonLast'] + \Setting::get('farm\calendarLimit');

	}

	public function validateSeason(mixed $season): self {

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

	public function checkSeason(mixed $season): bool {

		$this->expects(['seasonFirst', 'seasonLast']);

		return ($season >= $this['seasonFirst'] and $season <= $this['seasonLast']);

	}

	public function active(): bool {
		return ($this['status'] === Farm::ACTIVE);
	}

	public function hasAccounting(): bool {
		return (
			!OTF_DEMO and
			(FEATURE_ACCOUNTING or ($this->exists() and in_array($this['id'], \Setting::get('company\accountingBetaTesterFarms'))))
		); // Jardins de Tallende
	}

	public function canSection(string $section): bool {

		switch($section) {

			case 'production' :
				return $this->canProduction();

			case 'commercialisation' :
				return $this->canCommercialisation();

			case 'accounting' :
				return $this->canAccounting();

		}

	}

	public function canProduction(): bool {
		return (
			$this->canPlanning() or
			$this->canAnalyze() or
			$this->canManage()
		);
	}

	public function canCommercialisation(): bool {
		return (
			$this->canSelling() or
			$this->canAnalyze() or
			$this->canManage()
		);
	}

	public function canAccounting(): bool {
		return (
			$this->canAccountEntry() or
			$this->canManage()
		);
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

	// Peut accéder en lecture aux pages liées à la communication
	public function canCommunication(): bool {
		return $this->canManage();
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

	public function canAccountEntry(): bool {
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

	public function isEmail(): bool {

		return (
			$this['legalEmail'] !== NULL
		);

	}

	public function isLegal(): bool {

		return (
			$this['legalName'] !== NULL and
			$this['legalEmail'] !== NULL
		);

	}

	public function isLegalComplete(): bool {

		return (
			$this->isLegal() and
			$this['legalCity'] !== NULL
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

	public function canShop(): bool {

		$this->expects(['legalEmail', 'legalName']);

		return (
			$this['legalEmail'] !== NULL and
			$this['legalName'] !== NULL
		);

	}

	public function selling(): \selling\Configuration {

		if(array_key_exists($this['id'], self::$selling) === FALSE) {
			self::$selling[$this['id']] = \selling\ConfigurationLib::getByFarm($this);
		}

		return self::$selling[$this['id']];

	}

	public function getSelling(string $name): mixed {
		return $this->selling()[$name];
	}

	public function getView(string $name): mixed {
		$eFarmer = $this->getFarmer();

		if($eFarmer->notEmpty()) {
			return $eFarmer[$name];
		} else {
			return new FarmerModel()->getDefaultValue($name);
		}
	}

	public function validateEmailComplete(): void {

		$this->isEmail() ?: throw new \FailAction('farm\Farm::notEmail', ['farm' => $this]);

	}

	public function validateLegalComplete(): void {

		$this->isLegal() ?: throw new \FailAction('farm\Farm::notLegal', ['farm' => $this]);

	}

	public function validateSellingComplete(): void {

		$this->isLegalComplete() ?: throw new \FailAction('farm\Farm::notSelling', ['farm' => $this]);

	}

	public function saveFeaturesAsSettings(): void {

		foreach(['featureTime'] as $feature) {
			\Setting::set('farm\\'.$feature, $this[$feature]);
		}

	}

	public function hasFeatureTime(): bool {

		$this->expects(['featureTime']);

		return $this['featureTime'];

	}

	public function getLegalAddress(string $type = 'text'): ?string {

		if($this->hasLegalAddress() === FALSE) {
			return NULL;
		}

		$address = $this['legalStreet1']."\n";
		if($this['legalStreet2'] !== NULL) {
			$address .= $this['legalStreet2']."\n";
		}
		$address .= $this['legalPostcode'].' '.$this['legalCity'];

		return ($type === 'text') ? $address : nl2br(encode($address));

	}

	public function hasLegalAddress(): bool {
		return ($this['legalCity'] !== NULL);
	}

	public function getUrl(?string $section): string {

		return match($section) {
			'accounting' => $this->getAccountingUrl(),
			'commercialisation' => $this->getCommercialisationUrl(),
			default => $this->getProductionUrl()
		};

	}

	public function getProductionUrl(): ?string {

		if($this->canPlanning() or $this->canManage()) {
			return FarmUi::urlPlanningWeekly($this);
		} else {
			return FarmUi::urlCultivationSeries($this);
		}

	}

	public function getCommercialisationUrl(): ?string {

		if($this->canSelling() or $this->canManage()) {
			return FarmUi::urlSellingSales($this);
		} else if($this->canAnalyze()) {
			return FarmUi::urlAnalyzeCommercialisation($this);
		} else {
			return NULL;
		}

	}

	public function getAccountingUrl(): string {
		return \company\CompanyUi::urlJournal($this).'/operations';
	}

	public function getCampaignLimit(): int {
		return $this['id'] === 7 ? $this->getCampaignMemberLimit() : 100;
	}

	public function getCampaignMemberLimit(): int {
		return 1000;
	}

	public function getContactLimit(): int {
		if(LIME_ENV === 'dev') {
			return 999;
		} else {
			return $this['id'] === 7 ? $this->getContactMemberLimit() : 1;
		}
	}

	public function getContactMemberLimit(): int {
		return 3;
	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('rotationExclude.prepare', function(mixed &$plants): bool {

				$this->expects(['id']);

				$plants = (array)($plants ?? []);

				$plants = \plant\Plant::model()
					->select('id')
					->whereId('IN', $plants)
					->whereFarm($this)
					->getColumn('id');

				return TRUE;

			})
			->setCallback('place.required', function(?string $place) use($input) {

				$required = $input['placeRequired'] ?? FALSE;

				if($required) {
					return ($place !== NULL);
				} else {
					return TRUE;
				}

			})
			->setCallback('legalEmail.empty', function(?string $email) {
				return ($email !== NULL);
			})
			->setCallback('placeLngLat.check', function(?array &$placeLngLat) {

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

			})
			->setCallback('defaultBedWidth.size', function(?int $defaultBedWidth) {

				if($defaultBedWidth === NULL) {
					return TRUE;
				}

				return ($defaultBedWidth >= 5);

			});
		
		parent::build($properties, $input, $p);

	}

}
?>
