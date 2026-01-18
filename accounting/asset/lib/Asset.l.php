<?php
namespace asset;

class AssetLib extends \asset\AssetCrud {
	public static function getPropertiesCreate(): array {
		return [
			'acquisitionDate',
			'account', 'accountLabel',
			'value', 'residualValue',
			'economicMode', 'fiscalMode',
			'startDate', // Doit venir après les modes (pour le check)
			'description',
			'economicDuration', 'fiscalDuration',
			'isExcess',
			'resumeDate', 'economicAmortization',
		];
	}
	public static function getPropertiesUpdate(): array {
		return [
			'acquisitionDate',
			'account', 'accountLabel',
			'value', 'residualValue',
			'economicMode', 'fiscalMode',
			'startDate', // Doit venir après les modes (pour le check)
			'description',
			'economicDuration', 'fiscalDuration',
			'isExcess',
			'economicAmortization',
		];
	}

	public static function hasAssets(): bool {

		return (Asset::model()->count() > 0);

	}

	public static function countOperationMissingAsset(\account\FinancialYear $eFinancialYear): int {

		return \journal\OperationLib::applyAssetCondition()
			->whereFinancialYear($eFinancialYear)
			->whereAccountLabel('NOT LIKE', \account\AccountSetting::IN_PROGRESS_ASSETS_CLASS.'%')
			->whereAccountLabel('NOT LIKE', \account\AccountSetting::IN_CONTRUCTION_ASSETS_CLASS.'%')
			->whereAsset(NULL)
			->where(new \Sql('SUBSTRING(hash, LENGTH(hash), 1) != "'.\journal\JournalSetting::HASH_LETTER_RETAINED.'"'))
			->count();

	}

	public static function hasAmortization(Asset $eAsset): bool {

		return (Amortization::model()->whereAsset($eAsset)->count() > 0);

	}

	private static function updateEndDate(Asset $e): ?string {

		if($e['economicDuration'] !== NULL and $e['economicMode'] !== Asset::WITHOUT) {
			return date('Y-m-d', strtotime($e['startDate'].' + '.$e['economicDuration'].' month'));
		} else {
			return NULL;
		}

	}

	// Amortissement économique uniquement si la durée d'amort. fiscale est plus rapide que la durée d'amort. éco.
	private static function isExcess(Asset $e): bool {

		if($e['isGrant']) {

			return FALSE;

		}

		return($e['economicDuration'] < $e['fiscalDuration']);

	}

	public static function update(Asset $e, array $properties): void {

		$e['accountLabel'] = \account\AccountLabelLib::pad($e['accountLabel']);

		$e['endDate'] = self::updateEndDate($e);
		$e['isGrant'] = \asset\AssetLib::isGrant($e['accountLabel']);

		$e['isExcess'] = self::isExcess($e);

		parent::update($e, $properties);

	}
	public static function create(Asset $e): void {

		Asset::model()->beginTransaction();

		$cOperation = \journal\OperationLib::getByIds(POST('operations', 'array'));

		if($cOperation->notEmpty() and $cOperation->find(fn($e) => $e['asset']->notEmpty())->count() > 0) {
			throw new \FailAction('asset\Asset::Operation.alreadyLinked');
		}

		$e['accountLabel'] = \account\AccountLabelLib::pad($e['accountLabel']);

		// Calculate endDate
		$e['endDate'] = self::updateEndDate($e);
		$e['isGrant'] = \asset\AssetLib::isGrant($e['accountLabel']);

		$e['isExcess'] = self::isExcess($e);

		if($cOperation->notEmpty()) {
			if($cOperation->count() === 1) {
				if($e['accountLabel'] !== $cOperation->first()['accountLabel']) {
					throw new \FailAction('Asset::accountLabel.check');
				}
				if($e['account']['id'] !== $cOperation->first()['account']['id']) {
					throw new \FailAction('Asset::account.check');
				}
			} else if($cOperation->count() > 1) {
				if($cOperation->find(fn($eOperation) => $eOperation['accountLabel'] !== $e['accountLabel'])->count() > 0) {
					throw new \FailAction('Asset::accountLabels.check');
				}
				if($cOperation->find(fn($eOperation) => $eOperation['account']['id'] !== $e['account']['id'])->count() > 0) {
					throw new \FailAction('Asset::accounts.check');
				}
			}

		}

		parent::create($e);

		// Reprend les cumuls antérieurs à l'entrée dans l'exercice comptable
		AmortizationLib::resume($e);

		if($cOperation->notEmpty()) {
			\journal\OperationLib::applyAssetCondition()
				->whereId('IN', $cOperation->getIds())
				->update(['asset' => $e]);
		}

		Asset::model()->commit();

	}

	public static function isTangibleAsset(string $account): bool {

		return \account\AccountLabelLib::isFromClass($account, \account\AccountSetting::TANGIBLE_ASSETS_CLASS) or \account\AccountLabelLib::isFromClass($account, \account\AccountSetting::TANGIBLE_LIVING_ASSETS_CLASS);

	}

	public static function isIntangibleAsset(string $account): bool {

		return \account\AccountLabelLib::isFromClass($account, \account\AccountSetting::INTANGIBLE_ASSETS_CLASS);

	}

	public static function isTangibleLivingAsset(string $account): bool {

		return \account\AccountLabelLib::isFromClass($account, \account\AccountSetting::TANGIBLE_LIVING_ASSETS_CLASS);

	}

	public static function getAcquisitions(\account\FinancialYear $eFinancialYear, string $type): \Collection {

		return Asset::model()
			->select(Asset::getSelection())
			->whereAcquisitionDate('>=', $eFinancialYear['startDate'])
			->whereAcquisitionDate('<=', $eFinancialYear['endDate'])
			->whereAccountLabel('LIKE', match($type) {
				'asset' => \account\AccountSetting::ASSET_GENERAL_CLASS.'%',
				'grant' => \account\AccountSetting::GRANT_ASSET_CLASS.'%',
			})
			->sort(['accountLabel' => SORT_ASC, 'startDate' => SORT_ASC])
			->getCollection();

	}

	private static function applyGrantConditions(\account\FinancialYear $eFinancialYear): AssetModel {

		return Asset::model()
      ->whereStartDate('<=', $eFinancialYear['endDate'])
      ->whereEndDate('>=', $eFinancialYear['startDate'])
			->whereAccountLabel('LIKE', \account\AccountSetting::GRANT_ASSET_CLASS.'%');

	}

	public static function countGrantsByFinancialYear(\account\FinancialYear $eFinancialYear): int {

		return self::applyGrantConditions($eFinancialYear)->count();

	}

	public static function getGrantsByFinancialYear(\account\FinancialYear $eFinancialYear): \Collection {

		return self::applyGrantConditions($eFinancialYear)
      ->select(Asset::getSelection())
      ->sort(['accountLabel' => SORT_ASC, 'startDate' => SORT_ASC])
      ->getCollection();
	}

	private static function applyAssetConditions(\account\FinancialYear $eFinancialYear): AssetModel {

		return Asset::model()
			->or(
				fn() => $this->where('economicMode = "linear" AND startDate <='.Asset::model()->format($eFinancialYear['endDate'])),
				fn() => $this->where('economicMode != "linear" AND acquisitionDate <='.Asset::model()->format($eFinancialYear['endDate'])),
			)
			->where('endDate IS NULL or endDate >='.Asset::model()->format($eFinancialYear['startDate']))
			->where('endedDate IS NULL or endedDate >= '.Asset::model()->format($eFinancialYear['startDate']))
			->whereAccountLabel('LIKE', \account\AccountSetting::ASSET_GENERAL_CLASS.'%');

	}

	public static function countAssetsByFinancialYear(\account\FinancialYear $eFinancialYear): int {

		return self::applyAssetConditions($eFinancialYear)->count();

	}

	public static function getAssetsByFinancialYear(\account\FinancialYear $eFinancialYear): \Collection {

		return self::applyAssetConditions($eFinancialYear)
			->select(
				Asset::getSelection()
				+ ['account' => \account\Account::getSelection()]
			)
			->sort(['accountLabel' => SORT_ASC, 'startDate' => SORT_ASC])
			->getCollection();

	}

	public static function delete(Asset $e): void {

		Asset::model()->beginTransaction();

		\journal\Operation::model()
			->whereAsset($e)
			->update(['asset' => NULL]);

		parent::delete($e);

		Asset::model()->commit();

	}

	public static function deleteByIds(array $ids): void {

		Asset::model()->beginTransaction();

		Asset::model()
			->whereId('IN', $ids)
			->delete();

		Amortization::model()
			->whereAsset('IN', $ids)
			->delete();

		Depreciation::model()
			->whereAsset('IN', $ids)
			->delete();

		Asset::model()->commit();

	}

	/**
	 * @throws \ModuleException
	 */
	public static function getWithDepreciationsById(int $id): Asset {

		$eAsset = new Asset();
		Asset::model()
			->select(
				Asset::getSelection()
				+ [
					'cAmortization' => Amortization::model()
						->select(['amount', 'date', 'type', 'financialYear' => \account\FinancialYear::getSelection()])
						->sort(['date' => SORT_ASC])
						->delegateCollection('asset'),
				]
			)
			->whereId($id)
			->get($eAsset);

		return $eAsset;

	}

	public static function amortizeAll(\account\FinancialYear $eFinancialYear): void {

		Asset::model()->beginTransaction();

		$eJournalCode = \journal\JournalCodeLib::getByCode(\journal\JournalSetting::JOURNAL_CODE_OD_BILAN);

		$cAsset = self::getAssetsByFinancialYear($eFinancialYear);

		foreach($cAsset as $eAsset) {

			if($eAsset->isAmortizable()) {
				AmortizationLib::amortize($eFinancialYear, $eAsset, endDate: NULL, eJournalCode: $eJournalCode, simulate: FALSE);
			}

		}

		$cAssetGrant = self::getGrantsByFinancialYear($eFinancialYear);

		foreach($cAssetGrant as $eAsset) {

			if($eAsset->isAmortizable()) {
				AmortizationLib::amortizeGrant($eFinancialYear, $eAsset, simulate: FALSE);
			}

		}

		Asset::model()->commit();
	}

	public static function getAll(\Search $search): \Collection {

		if($search->get('account')) {
			$eAccount = \account\AccountLib::getById($search->get('account'));
		} else {
			$eAccount = new \account\Account();
		}

		return Asset::model()
			->select(Asset::getSelection())
			->whereAccountLabel($search->get('accountLabel'), if: $search->get('accountLabel') and AssetLib::isAsset($search->get('accountLabel')))
			->whereAccount($search->get('account'), if: $eAccount->notEmpty() and AssetLib::isAsset($eAccount['class']))
			->where('description LIKE "%'.$search->get('query').'%" OR accountLabel LIKE "%'.$search->get('query').'%"', if: $search->get('query'))
			->sort(['createdAt' => SORT_DESC])
			->getCollection();
	}

	public static function isGrant(string $class): bool {

			return mb_substr($class, 0, mb_strlen(\account\AccountSetting::GRANT_ASSET_CLASS)) === \account\AccountSetting::GRANT_ASSET_CLASS;
	}

	public static function isAsset(string $class): bool {

		return str_starts_with($class, \account\AccountSetting::ASSET_GENERAL_CLASS) and str_starts_with($class, \account\AccountSetting::ASSET_AMORTIZATION_GENERAL_CLASS) === FALSE;

	}

	public static function attach(Asset $eAsset, \Collection $cOperation): void{

		\journal\OperationLib::applyAssetCondition()
			->whereId('IN', $cOperation->getIds())
			->update(['asset' => $eAsset]);

	}

	/**
	 * Mise au rebut ou vente d'une immo
	 */
	public static function dispose(Asset $eAsset, array $input): void {

		$fw = new \FailWatch();

		$eAsset->build(['status', 'endedDate'], $input);

		$fw->validate();

		$eFinancialYear = \account\FinancialYearLib::getOpenFinancialYearByDate($eAsset['endedDate']);
		if($eFinancialYear->empty()) {
			throw new \NotExpectedAction('Open FinancialYear has not been found according to date "'.$eAsset['endedDate'].'"');
		}

		$endDate = $eAsset['endedDate'];
		$newStatus = $eAsset['status'];

		Asset::model()->beginTransaction();

		if(AmortizationLib::isAmortizable($eAsset)) {

			// Étape 1 : On calcule la dotation aux amortissements
			AmortizationLib::amortize($eFinancialYear, $eAsset, endDate: $endDate, eJournalCode: new \journal\JournalCode(), simulate: FALSE);

			// Étape 2 : Écriture de la sortie du bien du patrimoine
			$hash = \journal\OperationLib::generateHash().\journal\JournalSetting::HASH_LETTER_ASSETS;

			$eAsset = AssetLib::getById($eAsset['id']); // On re-récupère tous les amortissements
			$accumulatedAmortizationsValue = $eAsset['economicAmortization'];

			// 2.a) amortissement au débit
			$values = AmortizationLib::getAmortizationOperationValues($eFinancialYear, $eAsset, $endDate, $accumulatedAmortizationsValue);
			$values['hash'] = $hash;
			$values['type'] = \journal\Operation::DEBIT;
			\journal\OperationLib::createFromValues($values);

			// 2.b) VNC au débit
			$netAccountingValue = round(self::getAmortizableBase($eAsset, 'economic') - $accumulatedAmortizationsValue, 2);
			$eAccountVNC = \account\AccountLib::getByClass(\account\AccountSetting::CHARGE_ASSET_NET_VALUE_CLASS);
			$values = [
				'account' => $eAccountVNC['id'],
				'accountLabel' => \account\AccountLabelLib::pad($eAccountVNC['class']),
				'date' => $endDate,
				'paymentDate' => $endDate,
				'description' => new AssetUi()->getTranslation(\account\AccountSetting::CHARGE_ASSET_NET_VALUE_CLASS).' '.$eAsset['description'],
				'amount' => $netAccountingValue,
				'type' => \journal\Operation::DEBIT,
				'asset' => $eAsset,
				'financialYear' => $eFinancialYear['id'],
				'hash' => $hash,
				'journalCode' => $eAccountVNC['journalCode'],
			];
			\journal\OperationLib::createFromValues($values);

			// 2.c) sortie de l'immo
			$values = [
				'account' => $eAsset['account']['id'],
				'accountLabel' => $eAsset['accountLabel'],
				'date' => $endDate,
				'paymentDate' => $endDate,
				'description' => new AssetUi()->getTranslation('asset').' '.$eAsset['description'],
				'amount' => self::getAmortizableBase($eAsset, 'economic'),
				'type' => \journal\OperationElement::CREDIT,
				'asset' => $eAsset,
				'financialYear' => $eFinancialYear['id'],
				'hash' => $hash,
				'journalCode' => $eAsset['account']['journalCode'],
			];
			\journal\OperationLib::createFromValues($values);

			// Si dérogatoire : faire une reprise des dotations
			if($eAsset['isExcess']) {
				AmortizationLib::recoverExcess($eFinancialYear, $eAsset, $endDate);
			}

			// Étape 3. Reprise des éventuelles dépréciations
			$cDepreciation = DepreciationLib::getByAsset($eAsset);

			if($cDepreciation->notEmpty()) {

				$depreciationExceptionalAmount = round($cDepreciation->find(fn($e) => $e['type'] === Depreciation::EXCEPTIONAL)->sum('amount'), 2);
				$depreciationNormalAmount = round($cDepreciation->find(fn($e) => $e['type'] === Depreciation::NORMAL)->sum('amount'), 2);
				$accountLabelDepreciation = \account\AccountLabelLib::geDepreciationClassFromClass($eAsset['accountLabel']);

				foreach([Depreciation::NORMAL => $depreciationNormalAmount, Depreciation::EXCEPTIONAL => $depreciationExceptionalAmount] as $type => $amount) {

					if($amount <= 0.0) {
						continue;
					}

					$hash = \journal\OperationLib::generateHash().\journal\JournalSetting::HASH_LETTER_ASSETS;

					$class = match($type) {
						Depreciation::NORMAL => \account\AccountSetting::RECOVERY_NORMAL_ON_ASSET_DEPRECIATION,
						Depreciation::EXCEPTIONAL => \account\AccountSetting::RECOVERY_EXCEPTIONAL_ON_ASSET_DEPRECIATION,
					};
					$eAccountRecovery = \account\AccountLib::getByClass($class);
					$eAccountAssetDepreciation = \account\AccountLib::getByClass(mb_substr($accountLabelDepreciation, 0, 3));

					// Débiter le compte 29
					$values = [
						'account' => $eAccountAssetDepreciation['id'],
						'accountLabel' => $accountLabelDepreciation,
						'date' => $endDate,
						'paymentDate' => $endDate,
						'description' => new AssetUi()->getTranslation('depreciation-asset').' '.$eAsset['description'],
						'amount' => $amount,
						'type' => \journal\OperationElement::DEBIT,
						'asset' => $eAsset,
						'financialYear' => $eFinancialYear['id'],
						'hash' => $hash,
						'journalCode' => $eAccountAssetDepreciation['journalCode'],
					];
					\journal\OperationLib::createFromValues($values);

					// Créditer le compte 7816 ou 7876
					$values = [
						'account' => $eAccountRecovery['id'],
						'accountLabel' => \account\AccountLabelLib::pad($eAccountRecovery['class']),
						'date' => $endDate,
						'paymentDate' => $endDate,
						'description' => new AssetUi()->getTranslation('recovery-depreciation-'.($type === Depreciation::EXCEPTIONAL ? 'exceptional' : 'asset')).' '.$eAsset['description'],
						'amount' => $amount,
						'type' => \journal\OperationElement::CREDIT,
						'asset' => $eAsset,
						'financialYear' => $eFinancialYear['id'],
						'hash' => $hash,
						'journalCode' => $eAccountRecovery['journalCode'],
					];
					\journal\OperationLib::createFromValues($values);

				}

				if($depreciationNormalAmount > 0 or $depreciationExceptionalAmount > 0) {

					Depreciation::model()
						->whereAsset($eAsset)
						->update(['recoverDate' => new \Sql('NOW()')]);

				}

			}

		}

		// Étape 4 : Mise à jour de l'immobilisation
		Asset::model()->update(
			$eAsset,
			[
				'status' => $newStatus,
				'endDate' => $endDate,
				'updatedAt' => new \Sql('NOW()'),
			]
		);
		Asset::model()->commit();

	}

	public static function getAmortizableBase(Asset $eAsset, string $type): float {

		if($type === 'economic') {
			return round($eAsset['value'] - $eAsset['residualValue'], 2);
		} else {
			return $eAsset['value'];
		}
	}

	public static function getNotAssigned(): \Collection {

		return Asset::model()
			->select(Asset::getSelection())
			->join(\journal\Operation::model(), 'm1.id = m2.asset', 'LEFT')
			->where('m2.id IS NULL')
			->getCollection();
	}
}
?>
