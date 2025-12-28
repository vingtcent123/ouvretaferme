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
		return self::getPropertiesCreate();
	}

	public static function countOperationMissingAsset(\account\FinancialYear $eFinancialYear): int {

		return \journal\Operation::model()
			->whereFinancialYear($eFinancialYear)
			->or(
				fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::ASSET_GENERAL_CLASS.'%'),
				fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::EQUIPMENT_GRANT_CLASS.'%'),
			)
			->whereAccountLabel('NOT LIKE', \account\AccountSetting::ASSET_AMORTIZATION_GENERAL_CLASS.'%')
			->whereAsset(NULL)
			->count();

	}

	public static function isOutOfDurationRange(Asset $eAsset, string $type): bool {

		$cAmortizationDuration = \company\AmortizationDurationLib::getAll();

		if($cAmortizationDuration->offsetExists((int)mb_substr($eAsset['accountLabel'], 0, 4))) {
			$eAmortizationDuration = $cAmortizationDuration->offsetGet((int)mb_substr($eAsset['accountLabel'], 0, 4));
		} else if($cAmortizationDuration->offsetExists((int)mb_substr($eAsset['accountLabel'], 0, 3))) {
			$eAmortizationDuration = $cAmortizationDuration->offsetGet((int)mb_substr($eAsset['accountLabel'], 0, 3));
		} else {
			return FALSE;
		}

		return (
			$eAsset[$type.'Duration'] < $eAmortizationDuration['durationMin'] * 12 * (1 - AssetSetting::AMORTIZATION_DURATION_TOLERANCE) or
			$eAsset[$type.'Duration'] > $eAmortizationDuration['durationMax'] * 12 * (1 + AssetSetting::AMORTIZATION_DURATION_TOLERANCE)
		);
	}

	public static function hasAmortization(Asset $eAsset): bool {

		return (Amortization::model()->whereAsset($eAsset)->count() > 0);

	}

	public static function create(Asset $e): void {

		Asset::model()->beginTransaction();

		$cOperation = \journal\OperationLib::getByIds(POST('operations', 'array'));

		if($cOperation->notEmpty() and $cOperation->find(fn($e) => $e['asset']->notEmpty())->count() > 0) {
			throw new \FailAction('asset\Asset::Operation.alreadyLinked');
		}

		$e['accountLabel'] = \account\AccountLabelLib::pad($e['accountLabel']);

		// Calculate endDate
		$e['endDate'] = date('Y-m-d', strtotime($e['startDate'].' + '.$e['economicDuration'].' month'));
		$e['isGrant'] = \asset\AssetLib::isGrant($e['accountLabel']);

		// Amortissement économique uniqueemnt si la durée d'amort. fiscale est plus rapide que la durée d'amort. éco.
		if($e['isGrant']) {

			$isExcess = FALSE;

		} else {

			if($e['economicDuration'] > $e['fiscalDuration']) {
				$isExcess = TRUE;
			} else {
				$isExcess = FALSE;
			}

		}

		$e['isExcess'] = $isExcess;

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
				'subvention' => \account\AccountSetting::GRANT_ASSET_CLASS.'%',
			})
			->sort(['accountLabel' => SORT_ASC, 'startDate' => SORT_ASC])
			->getCollection();

	}

	public static function getGrantsByFinancialYear(\account\FinancialYear $eFinancialYear): \Collection {

		return Asset::model()
      ->select(Asset::getSelection())
      ->whereStartDate('<=', $eFinancialYear['endDate'])
      ->whereEndDate('>=', $eFinancialYear['startDate'])
			->whereAccountLabel('LIKE', \account\AccountSetting::GRANT_ASSET_CLASS.'%')
      ->sort(['accountLabel' => SORT_ASC, 'startDate' => SORT_ASC])
      ->getCollection();
	}

	public static function getAssetsByFinancialYear(\account\FinancialYear $eFinancialYear): \Collection {

		return Asset::model()
			->select(
				Asset::getSelection()
				+ ['account' => \account\Account::getSelection()]
			)
			->whereStartDate('<=', $eFinancialYear['endDate'])
			->whereEndDate('>=', $eFinancialYear['startDate'])
			->where('endedDate IS NULL or endedDate >= '.Asset::model()->format($eFinancialYear['startDate']))
			->whereAccountLabel('LIKE', \account\AccountSetting::ASSET_GENERAL_CLASS.'%')
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

		$cAsset = self::getAssetsByFinancialYear($eFinancialYear);

		foreach($cAsset as $eAsset) {

			AmortizationLib::amortize($eFinancialYear, $eAsset, NULL);

		}

		$cAssetGrant = self::getGrantsByFinancialYear($eFinancialYear);

		foreach($cAssetGrant as $eAsset) {

			AmortizationLib::amortizeGrant($eFinancialYear, $eAsset);

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
			AmortizationLib::amortize($eFinancialYear, $eAsset, $endDate);

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
}
?>
