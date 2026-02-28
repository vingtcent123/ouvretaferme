<?php
namespace farm;

class Farm extends FarmElement {

	const DEMO = 1;

	protected static array $conf = [];

	private static Farm $eFarmConnected;

	public function __construct(array $array = []) {

		parent::__construct($array);

	}

	public static function setConnected(Farm $eFarm): void {
		self::$eFarmConnected = $eFarm;
	}

	public static function getConnected(): Farm {
		return self::$eFarmConnected;
	}

	public function getFarmer(): Farmer {
		return FarmerLib::getOnline()[$this['id']] ?? new Farmer();
	}

	public static function getSelection(): array {

		return parent::getSelection() + [
			'calendarMonths' => new \Sql('IF(calendarMonthStart IS NULL, 0, 12 - calendarMonthStart + 1) + 12 + IF(calendarMonthStop IS NULL, 0, calendarMonthStop)', 'int'),
			'cFinancialYear?' => fn(Farm $eFarm) => function() use ($eFarm) {
				if($eFarm->hasAccounting()) {
					FarmLib::connectDatabase($eFarm);
					return \account\FinancialYearLib::getAll();
				} else {
					return new \Collection();
				}
			},
		];

	}

	public function isSeasonValid(int $season): bool {

		$this->expects(['seasonFirst', 'seasonLast']);

		return (
			$season >= $this->getFirstValidSeason() and
			$season <= $this->getLastValidSeason()
		);

	}

	public function isProducer(): bool {
		return $this['type'] === Farm::PRODUCER;
	}

	public function isCommunity(): bool {
		return $this['type'] === Farm::COMMUNITY;
	}

	public function getFirstValidSeason(): int {

		$this->expects(['seasonFirst']);

		return $this['seasonFirst'] - FarmSetting::CALENDAR_LIMIT;

	}

	public function getLastValidSeason(): int {

		$this->expects(['seasonLast']);

		return $this['seasonLast'] + FarmSetting::CALENDAR_LIMIT;

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

	public function getCalendarStartDay(int $season): string {
		return $this['calendarMonthStart'] ? ($season - 1).'-'.sprintf('%02d', $this['calendarMonthStart']).'-01' : $season.'-01-01';
	}

	public function getCalendarStopDay(int $season): string {

		if($this['calendarMonthStop']) {

			$date = new \DateTime(($season + 1).'-'.$this['calendarMonthStop'].'-01');
			$date->modify('last day of this month');

			return $date->format('Y-m-d');

		} else {
			return $season.'-12-31';
		}

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

	// Est-ce que la ferme a ses bases de données de comptabilité ?
	public function hasAccounting(): bool {
		return ($this['hasAccounting']);
	}
	// Est-ce que la ferme utilise le logiciel comptable d'OTF ?
	public function usesAccounting(): bool {

		return (
			$this->empty() === FALSE and
			$this->hasAccounting() and
			$this['hasFinancialYears']
		);

	}

	public function siren(): string {

		$this->expects(['siret']);

		return mb_substr($this['siret'], 0, 9);

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

	public function canRead(): bool {
		return (
			$this->canProduction() or
			$this->canCommercialisation() or
			$this->canAccounting()
		);
	}

	public function canProduction(): bool {

		if($this->hasFeatureProduction() === FALSE) {
			return FALSE;
		}

		return (
			$this->canPlanning() or
			$this->canAnalyze() or
			$this->canManage()
		);
	}

	public function hasFeatureProduction(): bool {
		return (
			$this->hasFeatureCultivation()
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
			($this->canAccountEntry() or
			$this->canManage())
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

	public function isMembership(): bool {

		return ($this['membership'] === TRUE);

	}

	public function isVatAccountingConfigured(): bool {

		return (
			$this->getConf('hasVatAccounting') !== NULL and
			($this->getConf('hasVatAccounting') === TRUE ? ($this->getConf('vatChargeability') !== NULL and $this->getConf('vatFrequency') !== NULL) : TRUE)
		);

	}

	public function isLegal(): bool {

		return (
			($this->isFR() === FALSE or $this['siret'] !== NULL) and
			$this['legalName'] !== NULL and
			$this['legalCity'] !== NULL
		);

	}

	public function validateLegal(): self {
		return $this->isLegal() ? $this : throw new \FailAction('farm\Farm::notLegal', ['farm' => $this]);
	}

	public function isVerified(): bool {

		return (
			$this->exists() and
			$this['verified']
		);

	}

	public function validateVerified(): self {

		$this->validate();

		return $this->isVerified() ? $this : throw new \FailAction('farm\Farm::notVerified', ['farm' => $this]);

	}

	public function acceptElectronicInvoicing(): bool {

		if(\pdp\PdpLib::isActive($this) === FALSE or $this->isFR() === FALSE) {
			return TRUE;
		}

		return (
			$this->hasLegalAddress() and
			$this['siret'] !== NULL and
			$this->getConf('electronicScheme') !== NULL and
			$this->getConf('electronicAddress') !== NULL and
			$this->getConf('vatNumber') !== NULL
		);
	}
	public function hasInvoicingMentions(): bool {

		return $this->getConf('invoiceMandatoryTexts') and
			$this->getConf('invoiceCollection') !== NULL and
			$this->getConf('invoiceLateFees') !== NULL and
			$this->getConf('invoiceDiscount') !== NULL;

	}

	public function hasVatCountry(): bool {
		return TRUE;
	}

	public function isFR(): bool {

		return ($this['legalCountry']['id'] === \user\UserSetting::FR);

	}

	public function isBE(): bool {

		return ($this['legalCountry']['id'] === \user\UserSetting::BE);

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

	public function conf(): \farm\Configuration {

		if(array_key_exists($this['id'], self::$conf) === FALSE) {
			self::$conf[$this['id']] = \farm\ConfigurationLib::getByFarm($this);
			self::$conf[$this['id']]['farm'] = $this;
		}

		return self::$conf[$this['id']];

	}

	public function getConf(string $name): mixed {
		return $this->conf()[$name];
	}

	public function getView(string $name): mixed {
		$eFarmer = $this->getFarmer();

		if($eFarmer->notEmpty()) {
			return $eFarmer[$name];
		} else {
			return new FarmerModel()->getDefaultValue($name);
		}
	}

	public function saveFeaturesAsSettings(): void {

		foreach(['featureTime'] as $feature) {
			FarmSetting::${$feature} = $this[$feature];
		}

	}

	public function hasFeatureCultivation(): bool {

		return $this->isProducer();

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

		if($this['legalCountry']->notEmpty()) {
			$address .= "\n".\user\Country::ask($this['legalCountry'])['name'];
		}

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
		return \company\CompanyUi::urlJournal($this).'/livre-journal';
	}

	public function getGameUrl(): string {
		return FarmUi::url($this).'/plateau';
	}

	public function getCampaignLimit(): int {
		return $this->isMembership() ? self::getCampaignMemberLimit() : 50;
	}

	public static function getCampaignMemberLimit(): int {
		return 1000;
	}

	public function getContactLimit(): int {
		if(LIME_ENV === 'dev') {
			return 100;
		} else {
			return $this->isMembership() ? self::getContactMemberLimit() : 1;
		}
	}

	public static function getContactMemberLimit(): int {
		return 3;
	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('legalCountry.check', function($eCountry): bool {

				if($this->isVerified()) {
					return FALSE;
				}

				return \user\Country::model()->exists($eCountry);

			})
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
			->setCallback('legalEmail.empty', function(?string $email) {
				return ($email !== NULL);
			})
			->setCallback('legalName.empty', function(?string $legalName) use($p) {
				return ($legalName !== NULL);
			})
			->setCallback('siret.empty', function(?string &$siret) use($p) {

				if($this->isFR()) {
					return ($siret !== NULL);
				} else {
					$siret = NULL;
					return TRUE;
				}

			})
			->setCallback('siret.check', fn(?string &$siret) => Farm::checkSiret($siret))
			->setCallback('legalCity.empty', function(?string $legalCity) use($p) {

				$fw = new \FailWatch();

				if($this['legalStreet1'] === NULL) {
					Farm::fail('legalStreet1.empty');
				}

				if($this['legalPostcode'] === NULL) {
					Farm::fail('legalPostcode.empty');
				}

				if($legalCity === NULL) {
					Farm::fail('legalCity.empty');
				}

				return $fw->ok();

			})
			->setCallback('cultivationLngLat.check', function(?array &$cultivationLngLat) {

				$this->expects(['cultivationPlace']);

				if($this['cultivationPlace'] !== NULL) {

					if(
						$cultivationLngLat === NULL or
						Farm::model()->check('cultivationLngLat', $cultivationLngLat) === FALSE
					) {
						Farm::fail('cultivationPlace.check');
					}

				} else {
					$cultivationLngLat = NULL;
				}

				return TRUE;

			})
			->setCallback('defaultBedWidth.size', function(?int $defaultBedWidth) {

				if($defaultBedWidth === NULL) {
					return TRUE;
				}

				return ($defaultBedWidth >= 5);

			})
		;
		
		parent::build($properties, $input, $p);

	}

	public static function checkSiret(?string &$siret): bool {

		if($siret === NULL) {
			return TRUE;
		} else {
			$siret = preg_replace('/\s+/i', '', $siret);
			return preg_match('/^[0-9]{14}$/', $siret) > 0;
		}

	}

	public static function checkVatNumber(string $element, Farm|\selling\Customer $e, ?string &$vat, bool $isNullable): bool {

		if($e->isFR()) {

			if($vat === NULL) {
				if($isNullable) {
					return TRUE;
				} else {
					\Fail::log($element.'::vatNumber.check', ['FR']);
					return TRUE;
				}
			}
			
			$vat = preg_replace('/\s+/i', '', $vat ?? '');
			$vat = strtoupper($vat);

			if(preg_match('/^FR[0-9]{11}$/', $vat) === 0) {
				\Fail::log($element.'::vatNumber.check', ['FR']);
			}

			return TRUE;

		} else if($e->isBE()) {

			if($vat === NULL) {
				if($isNullable) {
					return TRUE;
				} else {
					\Fail::log($element.'::vatNumber.check', ['BE']);
					return TRUE;
				}
			}

			$vat = preg_replace('/\s+/i', '', $vat);
			$vat = strtoupper($vat);

			if(preg_match('/^BE[01]{1}[0-9]{9}$/', $vat) === 0) {
				\Fail::log($element.'::vatNumber.check', ['BE']);
			}

		} else {

			if($vat !== NULL) {

				if($e->hasVatCountry()) {
					\Fail::log($element.'::vatNumber.country');
				} else {
					\Fail::log($element.'::vatNumber.noCountry');
				}

			}

		}

		return TRUE;

	}

}
?>
