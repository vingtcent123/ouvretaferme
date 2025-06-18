<?php
namespace asset;

class AssetLib extends \asset\AssetCrud {

	public static function getPropertiesCreate(): array {
		return ['value', 'type', 'description', 'mode', 'acquisitionDate', 'startDate', 'duration'];
	}
	public static function getPropertiesUpdate(): array {
		return ['value', 'type', 'description', 'mode', 'acquisitionDate', 'startDate', 'duration', 'status'];
	}

	public static function isTangibleAsset(string $account): bool {

		foreach(\Setting::get('accounting\tangibleAssetsClasses') as $tangibleAssetsClass) {
			if(\accounting\ClassLib::isFromClass($account, $tangibleAssetsClass) === TRUE) {
				return TRUE;
			};
		}

		return FALSE;

	}

	public static function isIntangibleAsset(string $account): bool {

		return \accounting\ClassLib::isFromClass($account, \Setting::get('accounting\intangibleAssetsClass'));

	}

	public static function isSubventionAsset(string $account): bool {

		return \accounting\ClassLib::isFromClass($account, \Setting::get('accounting\subventionAssetClass'));

	}

	public static function depreciationClassByAssetClass(string $class): string {

		if(self::isSubventionAsset($class) === TRUE) {
			return \Setting::get('accounting\subventionDepreciationAssetClass');
		}

		return mb_substr($class, 0, 1).'8'.mb_substr($class, 1);

	}

	public static function isDepreciationClass(string $class): bool {

		return (mb_substr($class, 1, 1) === '8');

	}

	public static function getAcquisitions(\accounting\FinancialYear $eFinancialYear, string $type): \Collection {

		return Asset::model()
			->select(Asset::getSelection())
			->whereAcquisitionDate('>=', $eFinancialYear['startDate'])
			->whereAcquisitionDate('<=', $eFinancialYear['endDate'])
			->whereAccountLabel('LIKE', match($type) {
				'asset' => \Setting::get('accounting\assetClass').'%',
				'subvention' => \Setting::get('accounting\subventionAssetClass').'%',
			})
			->sort(['accountLabel' => SORT_ASC, 'startDate' => SORT_ASC])
			->getCollection();

	}

	public static function getSubventionsByFinancialYear(\accounting\FinancialYear $eFinancialYear): \Collection {

		return Asset::model()
      ->select(
        Asset::getSelection()
        + ['account' => \accounting\Account::getSelection()]
      )
      ->whereStartDate('<=', $eFinancialYear['endDate'])
			->whereAccountLabel('LIKE', \Setting::get('accounting\subventionAssetClass').'%')
      ->sort(['accountLabel' => SORT_ASC, 'startDate' => SORT_ASC])
      ->getCollection();
	}

	public static function getAssetsByFinancialYear(\accounting\FinancialYear $eFinancialYear): \Collection {

		return Asset::model()
			->select(
				Asset::getSelection()
				+ ['account' => \accounting\Account::getSelection()]
			)
			->whereStartDate('<=', $eFinancialYear['endDate'])
			->whereEndDate('>=', $eFinancialYear['startDate'])
			->whereAccountLabel('LIKE', \Setting::get('accounting\assetClass').'%')
			->sort(['accountLabel' => SORT_ASC, 'startDate' => SORT_ASC])
			->getCollection();

	}

	public static function prepareAsset(\journal\Operation $eOperation, array $assetData, int $index): ?Asset {

		$eOperation->expects(['accountLabel']);

		if(
			(int)mb_substr($eOperation['accountLabel'], 0, 1) !== \Setting::get('accounting\assetClass')
			and
			(int)mb_substr($eOperation['accountLabel'], 0, 2) !== \Setting::get('accounting\subventionAssetClass')
		) {
			return NULL;
		}

		$eAsset = new Asset();
		$fw = new \FailWatch();

		$properties = new \Properties('create');
		$properties->setWrapper(function(string $property) use($index) {
			return 'asset['.$index.']['.$property.']';
		});
		$eAsset->build(['value', 'type', 'acquisitionDate', 'startDate', 'duration'], $assetData, $properties);
		if($fw->ko() === TRUE) {
			return NULL;
		}

		$eAsset['account'] = $eOperation['account'];
		$eAsset['accountLabel'] = $eOperation['accountLabel'];
		$eAsset['description'] = $eOperation['description'];
		$eAsset['endDate'] = date('Y-m-d', strtotime($eAsset['startDate'].' + '.$eAsset['duration'].' year - 1 day'));

		Asset::model()->insert($eAsset);

		return $eAsset;

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
					'cDepreciation' => Depreciation::model()
						->select(['amount', 'date', 'type', 'financialYear' => \accounting\FinancialYear::getSelection()])
						->sort(['date' => SORT_ASC])
						->delegateCollection('asset'),
				]
			)
			->whereId($id)
			->get($eAsset);

		return $eAsset;

	}

	public static function subventionReversal(\accounting\FinancialYear $eFinancialYear): void {

		\journal\Operation::model()->beginTransaction();

		$cAsset = self::getSubventionsByFinancialYear($eFinancialYear);

		$eAccountSubventionAssetDepreciation = \accounting\AccountLib::getByClass(\Setting::get('accounting\subventionAssetsDepreciationChargeClass'));

		foreach($cAsset as $eAsset) {

			if(
				$eAsset !== AssetElement::ONGOING
				or $eAsset['endDate'] > $eFinancialYear['endDate']
				or $eAsset['endDate'] < $eFinancialYear['startDate']
			) {
				continue;
			}

			// Crée l'opération 13x au débit
			$eOperationSubvention = new \journal\Operation([
				'type' => \journal\OperationElement::DEBIT,
				'amount' => $eAsset['value'],
				'account' => $eAsset['account'],
				'accountLabel' => $eAsset['accountLabel'],
				'description' => $eAsset['description'],
				'document' => new AssetUi()->getAssetShortTranslation(),
				'documentDate' => new \Sql('NOW()'),
				'asset' => $eAsset,
			]);

			\journal\Operation::model()->insert($eOperationSubvention);

			// Crée l'opération de reprise au crédit
			$eOperationReversal = new \journal\Operation([
				'type' => \journal\OperationElement::CREDIT,
				'amount' => $eAsset['value'],
				'account' => $eAccountSubventionAssetDepreciation,
				'accountLabel' => \accounting\ClassLib::pad($eAccountSubventionAssetDepreciation['class']),
				'description' => $eAsset['description'],
				'document' => new AssetUi()->getAssetShortTranslation(),
				'documentDate' => new \Sql('NOW()'),
				'asset' => $eAsset,
			]);

			\journal\Operation::model()->insert($eOperationReversal);

			// Solde la subvention
			$eAsset['status'] = AssetElement::ENDED;
			$eAsset['updatedAt'] = new \Sql('NOW()');
			Asset::model()
				->select(['status', 'updatedAt'])
				->update($eAsset);

		}

		\journal\Operation::model()->commit();

	}

	public static function depreciateAll(\accounting\FinancialYear $eFinancialYear): void {

		Asset::model()->beginTransaction();

		$cAsset = self::getAssetsByFinancialYear($eFinancialYear)->mergeCollection(self::getSubventionsByFinancialYear($eFinancialYear));

		foreach($cAsset as $eAsset) {

			self::depreciate($eFinancialYear, $eAsset, NULL);

		}

		Asset::model()->commit();
	}

	/**
	 * Amortit l'immobilisation sur l'exercice comptable dépendant de sa date d'acquisition / date de fin d'amortissement
	 * Crée une entrée "Dotation aux amortissements" (classe 6) et une entrée "Amortissement" (classe 2)
	 *
	 * @param Asset $eAsset
	 * @return void
	 */
	public static function depreciate(\accounting\FinancialYear $eFinancialYear, Asset $eAsset, ?string $endDate): void {

		if($endDate === NULL) {
			$endDate = $eFinancialYear['endDate'];
		}

		$depreciationValue = DepreciationLib::calculateDepreciation($eFinancialYear['startDate'], $endDate, $eAsset);

		// Dotation aux amortissements
		if(self::isSubventionAsset($eAsset['accountLabel'])) {
			$depreciationChargeClass = \Setting::get('accounting\subventionAssetsDepreciationChargeClass');
		} else if(self::isIntangibleAsset($eAsset['accountLabel'])) {
			$depreciationChargeClass = \Setting::get('accounting\intangibleAssetsDepreciationChargeClass');
		} else {
			$depreciationChargeClass = \Setting::get('accounting\tangibleAssetsDepreciationChargeClass');
		}

		$eAccountDepreciationCharge = \accounting\AccountLib::getByClass($depreciationChargeClass);
		$values = [
			'account' => $eAccountDepreciationCharge['id'],
			'accountLabel' => \accounting\ClassLib::pad($eAccountDepreciationCharge['class']),
			'date' => $endDate,
			'description' => $eAccountDepreciationCharge['description'],
			'amount' => $depreciationValue,
			'type' => \journal\OperationElement::DEBIT,
			'asset' => $eAsset,
		];
		\journal\OperationLib::createFromValues($values);

		// Amortissement
		$values = self::getDepreciationOperationValues($eAsset, $endDate, $depreciationValue);

		if($depreciationValue !== 0.0) {
			\journal\OperationLib::createFromValues($values);
		}

		// Créer une entrée dans la table Depreciation
		$eDepreciation = new Depreciation([
			'asset' => $eAsset,
			'amount' => $depreciationValue,
			'type' => DepreciationElement::ECONOMIC,
			'date' => $endDate,
			'financialYear' => $eFinancialYear,
		]);

		Depreciation::model()->insert($eDepreciation);

	}

	/**
	 * Renvoie les valeurs d'une opération d'amortissement pour l'immobilisation et le montant donnés
	 *
	 * @param Asset $eAsset
	 * @param string $date
	 * @param float $value
	 *
	 * @return array
	 */
	private static function getDepreciationOperationValues(Asset $eAsset, string $date, float $amount): array {

		$depreciationClass = self::depreciationClassByAssetClass(substr($eAsset['accountLabel'], 0, 3));
		$eAccountDepreciation = \accounting\AccountLib::getByClass(trim($depreciationClass, '0'));

		return [
			'account' => $eAccountDepreciation['id'],
			'accountLabel' => \accounting\ClassLib::pad($eAccountDepreciation['class']),
			'date' => $date,
			'description' => $eAccountDepreciation['description'],
			'amount' => $amount,
			'type' => \journal\OperationElement::CREDIT,
			'asset' => $eAsset,
		];

	}

	public static function isDepreciable(Asset $eAsset): bool {

		return substr($eAsset['accountLabel'], 0, mb_strlen(\Setting::get('accounting\nonDepreciableAssetClass'))) !== \Setting::get('accounting\nonDepreciableAssetClass');

	}

	public static function dispose(Asset $eAsset, array $input): void {

		$fw = new \FailWatch();

		$eAsset->build(['status'], $input);

		if($eAsset['status'] === AssetElement::SOLD) {

			if(($input['amount'] ?? NULL) === NULL or strlen($input['amount']) === 0) {
				Asset::fail('amount.check');
			}

			$amount = cast($input['amount'], 'float');

			$createReceivable = cast($input['createReceivable'] ?? FALSE, 'bool');

		} else {

			$amount = 0;

		}

		$date = $input['date'] ?? NULL;
		if(strlen($date) === 0 or \util\DateLib::isValid($date) === FALSE) {
			Asset::fail('date.check');
		}

		$eFinancialYear = \accounting\FinancialYearLib::getOpenFinancialYearByDate($date);
		if($eFinancialYear->exists() === FALSE) {
			throw new \NotExpectedAction('Open FinancialYear has not been found according to date "'.$date.'"');
		}

		$fw->validate();

		Asset::model()->beginTransaction();

		$eAsset['updatedAt'] = new \Sql('NOW()');
		Asset::model()
			->select(['status', 'updatedAt'])
			->update($eAsset);

		// Constater l'amortissement du début de l'exercice comptable jusqu'à la date de cession
		if(AssetLib::isDepreciable($eAsset)) {

			AssetLib::depreciate($eFinancialYear, $eAsset, $date);

			// Re-récupérer l'actif pour sommer les amortissements cumulés
			Asset::model()
				->select(Asset::getSelection() + [
						'cDepreciation' => Depreciation::model()
							->select(['amount', 'date', 'type', 'financialYear' => \accounting\FinancialYear::getSelection()])
							->sort(['date' => SORT_ASC])
							->delegateCollection('asset'),
						'account' => \accounting\Account::getSelection(),
					])
				->whereId($eAsset['id'])
				->get($eAsset);

		}

		// Calcul de la VNC. Attention, pour certaines immos on retient la valeur vénale et non la valeur net pour le calcul des plus values. TODO
		// Valeur d'entrée
		$initialValue = $eAsset['value'];

		// Amortissements
		$accumulatedDepreciationsValue = $eAsset['cDepreciation']->sum('amount');
		$netAccountingValue = $initialValue - $accumulatedDepreciationsValue;

		// Sortir l'actif (immo : 2x)
		$values = [
			'account' => $eAsset['account']['id'],
			'accountLabel' => \accounting\ClassLib::pad($eAsset['accountLabel']),
			'date' => $date,
			'description' => $eAsset['description'],
			'amount' => $eAsset['value'],
			'type' => \journal\OperationElement::CREDIT,
			'asset' => $eAsset,
		];
		\journal\OperationLib::createFromValues($values);

		// Sortir l'actif (amort. : 28x) en annulant l'amortissement cumulé
		if(AssetLib::isDepreciable($eAsset) === TRUE) {

			$values = self::getDepreciationOperationValues($eAsset, $date, $accumulatedDepreciationsValue);
			$values['type'] = \journal\OperationElement::DEBIT;
			\journal\OperationLib::createFromValues($values);

		}

		// Sortir l'actif (charge exc. de la VNC 675) : perte de l'actif
		$eAccountDisposal = \accounting\AccountLib::getByClass(\Setting::get('accounting\disposalAssetValueClass'));
		$values = [
			'account' => $eAccountDisposal['id'],
			'accountLabel' => \accounting\ClassLib::pad($eAccountDisposal['class']),
			'date' => $date,
			'description' => $eAccountDisposal['description'],
			'amount' => $netAccountingValue,
			'type' => \journal\OperationElement::DEBIT,
			'asset' => $eAsset,
		];
		\journal\OperationLib::createFromValues($values);

		// 1/ Cas d'une vente :
		if($eAsset['status'] === AssetElement::SOLD) {

			// b. Création de l'écriture de la vente 775
			$eAccountProduct = \accounting\AccountLib::getByClass(\Setting::get('accounting\productAssetValueClass'));
			$values = [
				'account' => $eAccountProduct['id'],
				'accountLabel' => \accounting\ClassLib::pad($eAccountProduct['class']),
				'date' => $date,
				'description' => $eAccountProduct['description'],
				'amount' => $amount,
				'type' => \journal\OperationElement::CREDIT,
				'asset' => $eAsset,
			];
			\journal\OperationLib::createFromValues($values);

			// c. Créer l'écriture débit compte banque (512) OU le débit créance sur cession (462)
			if($createReceivable === TRUE) {

				$receivablesOnAssetDisposalClass = \Setting::get('accounting\receivablesOnAssetDisposalClass');
				$debitAccountLabel = \accounting\ClassLib::pad($receivablesOnAssetDisposalClass);
				$eAccountDebit = \accounting\AccountLib::getByClass($receivablesOnAssetDisposalClass);

			} else {

				$bankClass = \Setting::get('accounting\bankAccountClass');
				$debitAccountLabel = \accounting\ClassLib::pad($bankClass);
				$eAccountDebit = \accounting\AccountLib::getByClass($bankClass);

			}

			$values = [
				'date' => $date,
				'account' => $eAccountDebit['id'],
				'accountLabel' => $debitAccountLabel,
				'description' => $eAsset['description'],
				'type' => \journal\OperationElement::DEBIT,
				'amount' => $amount,
			];
			\journal\OperationLib::createFromValues($values);

		}

		// 2/ Cas d'une mise au rebut : rien de plus à faire.

		Asset::model()->commit();

	}

}
?>
