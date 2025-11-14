<?php
namespace asset;

class AssetLib extends \asset\AssetCrud {
	public static function getPropertiesCreate(): array {
		return [
			'acquisitionDate', 'startDate',
			'account', 'accountLabel',
			'value', 'amortizableBase',
			'economicMode', 'fiscalMode',
			'description',
			'duration', 'economicDuration', 'fiscalDuration',
			'grant', 'asset',
		];
	}

	public static function create(Asset $e): void {

		// Calculate endDate
		$e['endDate'] = date('Y-m-d', strtotime($e['startDate'].' + '.$e['economicDuration'].' month'));

		Asset::model()->insert($e);

	}
	public static function isTangibleAsset(string $account): bool {

		return \account\ClassLib::isFromClass($account, \account\AccountSetting::TANGIBLE_ASSETS_CLASS);

	}

	public static function isIntangibleAsset(string $account): bool {

		return \account\ClassLib::isFromClass($account, \account\AccountSetting::INTANGIBLE_ASSETS_CLASS);

	}

	public static function isGrantAsset(string $account): bool {

		return \account\ClassLib::isFromClass($account, \account\AccountSetting::GRANT_ASSET_CLASS);

	}

	public static function depreciationClassByAssetClass(string $class): string {

		if(self::isGrantAsset($class) === TRUE) {
			return \account\AccountSetting::GRANT_DEPRECIATION_CLASS;
		}

		return mb_substr($class, 0, 1).'8'.mb_substr($class, 1);

	}

	public static function getAllGrants(): \Collection {

		return Asset::model()
			->select(Asset::getSelection())
			->whereIsGrant(TRUE)
			->whereAsset(NULL)
			->whereAccountLabel('LIKE', \account\AccountSetting::GRANT_ASSET_CLASS.'%')
			->getCollection();
	}

	public static function getAllAssetsToLinkToGrant(): \Collection {

		return Asset::model()
			->select(Asset::getSelection())
			->whereEconomicMode('IN', [Asset::LINEAR, Asset::DEGRESSIVE])
			->whereIsGrant(FALSE)
			->whereGrant(NULL)
			->whereAccountLabel('LIKE', \account\AccountSetting::ASSET_GENERAL_CLASS.'%')
			->getCollection();
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
			->whereIsGrant(TRUE)
			->whereStatus(Asset::ONGOING)
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
			->whereAccountLabel('LIKE', \account\AccountSetting::ASSET_GENERAL_CLASS.'%')
			->whereIsGrant(FALSE)
			->sort(['accountLabel' => SORT_ASC, 'startDate' => SORT_ASC])
			->getCollection();

	}

	public static function deleteByIds(array $ids): void {

		Asset::model()
			->whereId('IN', $ids)
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

	public static function finallyRecognizeGrants(\account\FinancialYear $eFinancialYear, array $grantsToRecognize): void {

		Asset::model()->beginTransaction();

		// Toutes les subventions possibles
		$cAssetGrant = \asset\AssetLib::getGrantsWithAmortizedAssets();

		$eAccountGrantsInIncomeStatement = \account\AccountSetting::GRANT_ASSET_AMORTIZATION_CLASS;
		$eAccountDepreciation = \account\AccountLib::getByClass(\account\AccountSetting::GRANT_DEPRECIATION_CLASS);

		foreach($grantsToRecognize as $grantId) {

			$cAsset = $cAssetGrant->find(fn($e) => $e['id'] === (int)$grantId);
			if($cAsset->empty()) {
				continue;
			}

			$eAsset = $cAsset->first();

			$alreadyRecognized = RecognitionLib::sumByGrant($eAsset);
			$amortizationValue = $eAsset['value'] - $alreadyRecognized;
			$prorataDays = 0;

			self::recognize($eFinancialYear, $eAsset, $alreadyRecognized, $amortizationValue, $eAccountGrantsInIncomeStatement, $eAccountDepreciation, $prorataDays, new AssetUi()->getFinalRecognitionTranslation());

		}

		Asset::model()->commit();
	}

	public static function recognizeGrants(\account\FinancialYear $eFinancialYear): void {

		Asset::model()->beginTransaction();

		$cAsset = self::getGrantsByFinancialYear($eFinancialYear);

		$eAccountGrantsInIncomeStatement = \account\AccountSetting::GRANT_ASSET_AMORTIZATION_CLASS;
		$eAccountDepreciation = \account\AccountLib::getByClass(\account\AccountSetting::GRANT_DEPRECIATION_CLASS);

		foreach($cAsset as $eAsset) {

			$amortizationData = AmortizationLib::calculateGrantAmortization($eFinancialYear['startDate'], $eFinancialYear['endDate'], $eAsset);
			$prorataDays = $amortizationData['prorataDays'];

			// Valeur théorique
			$amortizationValue = $amortizationData['value'];

			// Valeur restante (déjà virée au compte de résultat)
			$alreadyRecognized = RecognitionLib::sumByGrant($eAsset);

			self::recognize($eFinancialYear, $eAsset, $alreadyRecognized, $amortizationValue, $eAccountGrantsInIncomeStatement, $eAccountDepreciation, $prorataDays, NULL);

		}

		Asset::model()->commit();
	}

	private static function recognize(
		\account\FinancialYear $eFinancialYear,
		Asset $eAsset,
		float $alreadyRecognized,
		float $amortizationValue,
		\account\Account $eAccountGrantsInIncomeStatement,
		\account\Account $eAccountDepreciation,
		float $prorataDays,
		?string $comment,
	): void {

		$value = min($eAsset['value'] - $alreadyRecognized, $amortizationValue);

		// Crée l'opération 139 au débit
		$eOperationSubvention = new \journal\Operation([
			'type' => \journal\OperationElement::DEBIT,
			'amount' => $value,
			'account' => $eAccountGrantsInIncomeStatement,
			'accountLabel' => \account\ClassLib::pad($eAccountGrantsInIncomeStatement['class']),
			'description' => $eAsset['description'],
			'thirdParty' => $eOperation['thirdParty'] ?? new \account\ThirdParty(),
			'document' => new AssetUi()->getAssetShortTranslation(),
			'documentDate' => new \Sql('NOW()'),
			'asset' => $eAsset,
			'financialYear' => $eFinancialYear,
			'date' => $eFinancialYear['endDate'],
			'paymentDate' => $eFinancialYear['endDate'],
		]);

		\journal\Operation::model()->insert($eOperationSubvention);

		// Crée l'opération de reprise au crédit du compte 7777
		$eOperationRecognition = new \journal\Operation([
			'type' => \journal\OperationElement::CREDIT,
			'amount' => $value,
			'account' => $eAccountDepreciation,
			'accountLabel' => \account\ClassLib::pad($eAccountDepreciation['class']),
			'description' => $eAsset['description'],
			'thirdParty' => $eOperation['thirdParty'] ?? new \account\ThirdParty(),
			'document' => new AssetUi()->getAssetShortTranslation(),
			'documentDate' => new \Sql('NOW()'),
			'asset' => $eAsset,
			'financialYear' => $eFinancialYear,
			'date' => $eFinancialYear['endDate'],
			'paymentDate' => $eFinancialYear['endDate'],
		]);

		\journal\Operation::model()->insert($eOperationRecognition);

		// Enregistre la quote part virée au compte de résultat
		$recognitionValues = [
			'grant' => $eAsset,
			'financialYear' => $eFinancialYear,
			'date' => $eFinancialYear['endDate'] > $eAsset['endDate'] ? $eAsset['endDate'] : $eFinancialYear['endDate'],
			'amount' => $value,
			'operation' => $eOperationRecognition,
			'debitAccountLabel' => $eOperationSubvention['accountLabel'],
			'creditAccountLabel' => $eOperationRecognition['accountLabel'],
			'prorataDays' => $prorataDays,
			'comment' => $comment,
		];
		RecognitionLib::saveByValues($recognitionValues);

		// Solde la subvention si elle est terminée
		if($eAsset['endDate'] <= $eFinancialYear['endDate'] or $alreadyRecognized + $value >= $eAsset['value']) {
			Asset::model()->update($eAsset, ['status' => Asset::ENDED, 'updatedAt' => new \Sql('NOW()')]);
		}

	}

	public static function amortizeAll(\account\FinancialYear $eFinancialYear): void {

		Asset::model()->beginTransaction();

		$cAsset = self::getAssetsByFinancialYear($eFinancialYear);

		foreach($cAsset as $eAsset) {

			AmortizationLib::amortize($eFinancialYear, $eAsset, NULL);

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

	public static function isAsset(string $class): bool {

		return (
			mb_substr($class, 0, mb_strlen(\account\AccountSetting::GRANT_ASSET_CLASS)) === \account\AccountSetting::GRANT_ASSET_CLASS or
			mb_substr($class, 0, mb_strlen(\account\AccountSetting::ASSET_GENERAL_CLASS)) === (string)\account\AccountSetting::ASSET_GENERAL_CLASS
		);

	}

	/**
	 * Recupère toutes les subventions courantes reliées à une immobilisation amortie ou terminée.
	 */
	public static function getGrantsWithAmortizedAssets(): \Collection {

		$assetModel = clone Asset::model();
		return Asset::model()
			->select(
				Asset::getSelection()
				+ [
					'asset' => Asset::getSelection(),
					'alreadyRecognized' => Recognition::model()
             ->delegateProperty('grant', new \Sql('SUM(amount)', 'float'))
				]
			)
			->join($assetModel, 'm1.asset = m2.id')
			->where('m2.status != "'.Asset::ONGOING.'"')
			->where('m1.status = "'.Asset::ONGOING.'"')
			->where('m1.asset IS NOT NULL')
			->where('m1.isGrant = 1')
			->getCollection();

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

		if(\account\ClassLib::isFromClass($eAsset['accountLabel'], \account\AccountSetting::NON_AMORTIZABLE_ASSET_CLASS) === FALSE) {

			// Étape 1 : On calcule la dotation aux amortissements
			AmortizationLib::amortize($eFinancialYear, $eAsset, $endDate);

			// Étape 2 : Écriture de la sortie du bien du patrimoine
			$hash = \journal\OperationLib::generateHash().'i';

			$eAsset = AssetLib::getById($eAsset['id']); // On re-récupère tous les amortissements
			$accumulatedAmortizationsValue = round($eAsset['cAmortization']->sum('amount'), 2);

			// 2.a) amortissement au débit
			$values = AmortizationLib::getAmortizationOperationValues($eFinancialYear, $eAsset, $endDate, $accumulatedAmortizationsValue);
			$values['hash'] = $hash;
			$values['type'] = \journal\Operation::DEBIT;
			\journal\OperationLib::createFromValues($values);

			// 2.b) VNC au débit
			$netAccountingValue = round($eAsset['amortizableBase'] - $accumulatedAmortizationsValue, 2);
			$eAccountVNC = \account\AccountLib::getByClass(\account\AccountSetting::CHARGE_ASSET_NET_VALUE_CLASS);
			$values = [
				'account' => $eAccountVNC['id'],
				'accountLabel' => \account\ClassLib::pad($eAccountVNC['class']),
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
				'amount' => $eAsset['amortizableBase'],
				'type' => \journal\OperationElement::CREDIT,
				'asset' => $eAsset,
				'financialYear' => $eFinancialYear['id'],
				'hash' => $hash,
				'journalCode' => $eAsset['account']['journalCode'],
			];
			\journal\OperationLib::createFromValues($values);

			// Étape 3. Reprise des éventuelles dépréciations
			$cDepreciation = DepreciationLib::getByAsset($eAsset);
			$depreciationExceptionalAmount = round($cDepreciation->find(fn($e) => $e['type'] === Depreciation::EXCEPTIONAL)->sum('amount'), 2);
			$depreciationNormalAmount = round($cDepreciation->find(fn($e) => $e['type'] === Depreciation::NORMAL)->sum('amount'), 2);
			$accountLabelDepreciation = \account\ClassLib::geDepreciationClassFromClass($eAsset['accountLabel']);

			foreach([Depreciation::NORMAL => $depreciationNormalAmount, Depreciation::EXCEPTIONAL => $depreciationExceptionalAmount] as $type => $amount) {

				if($amount <= 0.0) {
					continue;
				}

				$hash = \journal\OperationLib::generateHash().'i';

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
					'accountLabel' => \account\ClassLib::pad($eAccountRecovery['class']),
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
		Asset::model()->update($eAsset, ['status' => $newStatus, 'endedDate' => $endDate, 'updatedAt' => new \Sql('NOW()')]);

		Asset::model()->commit();

	}

}
?>
