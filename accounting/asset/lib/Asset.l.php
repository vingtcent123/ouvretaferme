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

		$eOperation = \journal\OperationLib::getById(POST('operation'));

		if($eOperation->notEmpty() and $eOperation['asset']->notEmpty()) {
			throw new \FailAction('asset\Asset::Operation.alreadyLinked');
		}

		$e['accountLabel'] = \account\AccountLabelLib::pad($e['accountLabel']);

		// Calculate endDate
		$e['endDate'] = date('Y-m-d', strtotime($e['startDate'].' + '.$e['economicDuration'].' month'));
		$e['isGrant'] = \asset\AssetLib::isGrant($e['accountLabel']);

		// Set isExcess value
		// On vérifie :
		// - si une valeur résiduelle est indiquée
		// - si economicMode != fiscalMode
		// - sinon, si la durée d'amortissement économique est hors de la tranche des durées recommandées
		if($e['isGrant']) {

			$isExcess = FALSE;

		} else {

			if($e['economicMode'] === Asset::WITHOUT and $e['fiscalMode'] === Asset::WITHOUT) {
				$isExcess = FALSE;
			} else if($e['economicMode'] !== $e['fiscalMode']) {
				$isExcess = TRUE;
			} else if($e['residualValue'] > 0) {
				$isExcess = TRUE;
			} else if($e['economicDuration'] >= $e['fiscalDuration']) {
				$isExcess = FALSE;
			} else {
				$isExcess = self::isOutOfDurationRange($e, 'economic');
			}

		}

		$e['isExcess'] = $isExcess;

		if($eOperation->notEmpty()) {
			if($e['accountLabel'] !== $eOperation['accountLabel']) {
				throw new \FailAction('Asset::accountLabel.check');
			}
			if($e['account']['id'] !== $eOperation['account']['id']) {
				throw new \FailAction('Asset::account.check');
			}

		}

		parent::create($e);

		// Reprend les cumuls antérieurs à l'entrée dans l'exercice comptable
		AmortizationLib::resume($e);

		if($eOperation->notEmpty()) {
			\journal\Operation::model()->update($eOperation, ['asset' => $e]);
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

	public static function deleteByIds(array $ids): void {

		Asset::model()
			->whereId('IN', $ids)
			->delete();

		Amortization::model()
			->whereAsset('IN', $ids)
			->delete();

		Depreciation::model()
			->whereAsset('IN', $ids)
			->delete();
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

		return mb_substr($class, 0, mb_strlen(\account\AccountSetting::ASSET_GENERAL_CLASS)) === (string) \account\AccountSetting::ASSET_GENERAL_CLASS;

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
