<?php
namespace asset;

class AssetLib extends \asset\AssetCrud {

	public static function getPropertiesCreate(): array {
		return ['account', 'accountLabel', 'value', 'type', 'description', 'acquisitionDate', 'startDate', 'duration', 'grant', 'asset'];
	}

	public static function isTangibleAsset(string $account): bool {

		foreach(\Setting::get('account\tangibleAssetsClasses') as $tangibleAssetsClass) {
			if(\account\ClassLib::isFromClass($account, $tangibleAssetsClass) === TRUE) {
				return TRUE;
			};
		}

		return FALSE;

	}

	public static function isIntangibleAsset(string $account): bool {

		return \account\ClassLib::isFromClass($account, \Setting::get('account\intangibleAssetsClass'));

	}

	public static function isGrantAsset(string $account): bool {

		return \account\ClassLib::isFromClass($account, \Setting::get('account\grantAssetClass'));

	}

	public static function depreciationClassByAssetClass(string $class): string {

		if(self::isGrantAsset($class) === TRUE) {
			return \Setting::get('account\grantDepreciationClass');
		}

		return mb_substr($class, 0, 1).'8'.mb_substr($class, 1);

	}

	public static function isDepreciationClass(string $class): bool {

		return (mb_substr($class, 1, 1) === '8');

	}

	public static function getAllGrants(): \Collection {

		return Asset::model()
			->select(Asset::getSelection())
			->whereIsGrant(TRUE)
			->whereAsset(NULL)
			->whereAccountLabel('LIKE', \Setting::get('account\grantAssetClass').'%')
			->getCollection();
	}

	public static function getAllAssetsToLinkToGrant(): \Collection {

		return Asset::model()
			->select(Asset::getSelection())
			->whereType('IN', [Asset::LINEAR, Asset::DEGRESSIVE])
			->whereIsGrant(FALSE)
			->whereGrant(NULL)
			->whereAccountLabel('LIKE', \Setting::get('account\assetClass').'%')
			->getCollection();
	}

	public static function getAcquisitions(\account\FinancialYear $eFinancialYear, string $type): \Collection {

		return Asset::model()
			->select(Asset::getSelection())
			->whereAcquisitionDate('>=', $eFinancialYear['startDate'])
			->whereAcquisitionDate('<=', $eFinancialYear['endDate'])
			->whereAccountLabel('LIKE', match($type) {
				'asset' => \Setting::get('account\assetClass').'%',
				'subvention' => \Setting::get('account\grantAssetClass').'%',
			})
			->sort(['accountLabel' => SORT_ASC, 'startDate' => SORT_ASC])
			->getCollection();

	}

	public static function getGrantsByFinancialYear(\account\FinancialYear $eFinancialYear): \Collection {

		return Asset::model()
      ->select(Asset::getSelection())
      ->whereStartDate('<=', $eFinancialYear['endDate'])
      ->whereEndDate('>=', $eFinancialYear['startDate'])
			->whereAccountLabel('LIKE', \Setting::get('account\grantAssetClass').'%')
			->whereIsGrant(TRUE)
			->whereStatus(Asset::ONGOING)
      ->sort(['accountLabel' => SORT_ASC, 'startDate' => SORT_ASC])
      ->getCollection();
	}

	public static function getAssetsByFinancialYear(\account\FinancialYear $eFinancialYear, ?string $status = Asset::ONGOING): \Collection {

		return Asset::model()
			->select(
				Asset::getSelection()
				+ ['account' => \account\Account::getSelection()]
			)
			->whereStartDate('<=', $eFinancialYear['endDate'])
			->whereEndDate('>=', $eFinancialYear['startDate'])
			->whereAccountLabel('LIKE', \Setting::get('account\assetClass').'%')
			->whereIsGrant(FALSE)
			->whereStatus($status, if: $status !== NULL)
			->sort(['accountLabel' => SORT_ASC, 'startDate' => SORT_ASC])
			->getCollection();

	}

	public static function prepareAsset(\journal\Operation $eOperation, array $assetData, int $index): ?Asset {

		$eOperation->expects(['accountLabel']);

		// Ni une immo, ni une sub
		if(
			\account\ClassLib::isFromClass($eOperation['accountLabel'], \Setting::get('account\assetClass')) === FALSE
			and	\account\ClassLib::isFromClass($eOperation['accountLabel'], \Setting::get('account\grantAssetClass')) === FALSE
		) {
			return NULL;
		}

		$fw = new \FailWatch();

		$properties = new \Properties('create');
		$properties->setWrapper(function(string $property) use($index) {
			return 'asset['.$index.']['.$property.']';
		});
		$assetData['account'] = $eOperation['account']['id'];
		$assetData['accountLabel'] = $eOperation['accountLabel'];
		$assetData['description'] = $eOperation['description'];

		$isGrant = self::isGrantAsset($eOperation['accountLabel']);
		$assetData['isGrant'] = $isGrant;

		$eAsset = new Asset();
		$eAsset->build(['isGrant', 'accountLabel', 'account', 'description', 'value', 'type', 'acquisitionDate', 'startDate', 'duration', 'grant', 'asset'], $assetData);

		if($fw->ko() === TRUE) {
			return NULL;
		}

		// Pour les subventions date d'acquisition = date de mise en service
		if($isGrant) {
			$eAsset['startDate'] = $eAsset['acquisitionDate'];
		}

		$eAsset['endDate'] = date('Y-m-d', strtotime($eAsset['startDate'].' + '.$eAsset['duration'].' year - 1 day'));

		Asset::model()->insert($eAsset);

		// On ajoute le lien vers l'immo dans la subvention concernée
		if($eAsset['grant']->notEmpty()) {

			Asset::model()->update($eAsset['grant'], ['asset' => $eAsset]);

		}

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

		$eAccountGrantsInIncomeStatement = \account\AccountLib::getByClass(\Setting::get('account\grantsInIncomeStatement'));
		$eAccountDepreciation = \account\AccountLib::getByClass(\Setting::get('account\grantDepreciationClass'));

		foreach($grantsToRecognize as $grantId) {

			$cAsset = $cAssetGrant->find(fn($e) => $e['id'] === (int)$grantId);
			if($cAsset->empty()) {
				continue;
			}

			$eAsset = $cAsset->first();

			$alreadyRecognized = RecognitionLib::sumByGrant($eAsset);
			$depreciationValue = $eAsset['value'] - $alreadyRecognized;
			$prorataDays = 0;

			self::recognize($eFinancialYear, $eAsset, $alreadyRecognized, $depreciationValue, $eAccountGrantsInIncomeStatement, $eAccountDepreciation, $prorataDays, new AssetUi()->getFinalRecognitionTranslation());

		}

		Asset::model()->commit();
	}

	public static function recognizeGrants(\account\FinancialYear $eFinancialYear): void {

		Asset::model()->beginTransaction();

		$cAsset = self::getGrantsByFinancialYear($eFinancialYear);

		$eAccountGrantsInIncomeStatement = \account\AccountLib::getByClass(\Setting::get('account\grantsInIncomeStatement'));
		$eAccountDepreciation = \account\AccountLib::getByClass(\Setting::get('account\grantDepreciationClass'));

		foreach($cAsset as $eAsset) {

			$depreciationData = DepreciationLib::calculateGrantDepreciation($eFinancialYear['startDate'], $eFinancialYear['endDate'], $eAsset);
			$prorataDays = $depreciationData['prorataDays'];

			// Valeur théorique
			$depreciationValue = $depreciationData['value'];

			// Valeur restante (déjà virée au compte de résultat)
			$alreadyRecognized = RecognitionLib::sumByGrant($eAsset);

			self::recognize($eFinancialYear, $eAsset, $alreadyRecognized, $depreciationValue, $eAccountGrantsInIncomeStatement, $eAccountDepreciation, $prorataDays, NULL);

		}

		Asset::model()->commit();
	}

	private static function recognize(
		\account\FinancialYear $eFinancialYear,
		Asset $eAsset,
		float $alreadyRecognized,
		float $depreciationValue,
		\account\Account $eAccountGrantsInIncomeStatement,
		\account\Account $eAccountDepreciation,
		float $prorataDays,
		?string $comment,
	): void {

		$value = min($eAsset['value'] - $alreadyRecognized, $depreciationValue);

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

	public static function depreciateAll(\account\FinancialYear $eFinancialYear): void {

		Asset::model()->beginTransaction();

		$cAsset = self::getAssetsByFinancialYear($eFinancialYear);

		foreach($cAsset as $eAsset) {

			self::depreciate($eFinancialYear, $eAsset, NULL);

		}

		Asset::model()->commit();
	}

	/**
	 * Amortit l'immobilisation sur l'exercice comptable dépendant de sa date d'acquisition / date de fin d'amortissement
	 * Crée une entrée "Dotation aux amortissements" (classe 6) au débit et une entrée "Amortissement" (classe 2) au crédit
	 *
	 * @param Asset $eAsset
	 * @return void
	 */
	public static function depreciate(\account\FinancialYear $eFinancialYear, Asset $eAsset, ?string $endDate): void {

		// Cas où on sort l'immo manuellement (cassé, mise au rebus etc.)
		if($endDate === NULL) {
			$endDate = $eFinancialYear['endDate'];
		}

		$depreciationValue = DepreciationLib::calculateDepreciation($eFinancialYear['startDate'], $endDate, $eAsset);

		// Dotation aux amortissements
		if(self::isIntangibleAsset($eAsset['accountLabel'])) {
			$depreciationChargeClass = \Setting::get('account\intangibleAssetsDepreciationChargeClass');
		} else {
			$depreciationChargeClass = \Setting::get('account\tangibleAssetsDepreciationChargeClass');
		}

		$eAccountDepreciationCharge = \account\AccountLib::getByClass($depreciationChargeClass);
		$values = [
			'account' => $eAccountDepreciationCharge['id'],
			'accountLabel' => \account\ClassLib::pad($eAccountDepreciationCharge['class']),
			'date' => $endDate,
			'description' => $eAccountDepreciationCharge['description'],
			'amount' => $depreciationValue,
			'type' => \journal\OperationElement::DEBIT,
			'asset' => $eAsset,
			'financialYear' => $eFinancialYear['id'],
		];
		\journal\OperationLib::createFromValues($values);

		// Amortissement
		$values = self::getDepreciationOperationValues($eFinancialYear, $eAsset, $endDate, $depreciationValue);

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

		// Si l'immobilisation a été entièrement amortie ou n'est plus valide
		$depreciatedValue = Depreciation::model()
			->whereAsset($eAsset)
			->getValue(new \Sql('SUM(amount)', 'float'));

		if($eAsset['endDate'] <= $eFinancialYear['endDate'] or $depreciatedValue >= $eAsset['value']) {
			Asset::model()->update($eAsset, ['status' => Asset::ENDED, 'updatedAt' => new \Sql('NOW()')]);
		}

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
	private static function getDepreciationOperationValues(\account\FinancialYear $eFinancialYear, Asset $eAsset, string $date, float $amount): array {

		$depreciationClass = self::depreciationClassByAssetClass(substr($eAsset['accountLabel'], 0, 3));
		$eAccountDepreciation = \account\AccountLib::getByClass(trim($depreciationClass, '0'));

		return [
			'account' => $eAccountDepreciation['id'],
			'accountLabel' => \account\ClassLib::pad($eAccountDepreciation['class']),
			'date' => $date,
			'description' => $eAccountDepreciation['description'],
			'amount' => $amount,
			'type' => \journal\OperationElement::CREDIT,
			'asset' => $eAsset,
			'financialYear' => $eFinancialYear['id'],
		];

	}

	public static function isDepreciable(Asset $eAsset): bool {

		return substr($eAsset['accountLabel'], 0, mb_strlen(\Setting::get('account\nonDepreciableAssetClass'))) !== \Setting::get('account\nonDepreciableAssetClass');

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
			->where('m1.type = "'.Asset::GRANT_RECOVERY.'"')
			->where('m1.asset IS NOT NULL')
			->where('m1.isGrant = 1')
			->getCollection();

	}

	/**
	 * TODO : vérifier la mise au rebut, vente etc.
	 */
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

		$eFinancialYear = \account\FinancialYearLib::getOpenFinancialYearByDate($date);
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
							->select(['amount', 'date', 'type', 'financialYear' => \account\FinancialYear::getSelection()])
							->sort(['date' => SORT_ASC])
							->delegateCollection('asset'),
						'account' => \account\Account::getSelection(),
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
			'accountLabel' => \account\ClassLib::pad($eAsset['accountLabel']),
			'date' => $date,
			'description' => $eAsset['description'],
			'amount' => $eAsset['value'],
			'type' => \journal\OperationElement::CREDIT,
			'asset' => $eAsset,
		];
		\journal\OperationLib::createFromValues($values);

		// Sortir l'actif (amort. : 28x) en annulant l'amortissement cumulé
		if(AssetLib::isDepreciable($eAsset) === TRUE) {

			$values = self::getDepreciationOperationValues($eFinancialYear, $eAsset, $date, $accumulatedDepreciationsValue);
			$values['type'] = \journal\OperationElement::DEBIT;
			\journal\OperationLib::createFromValues($values);

		}

		// Sortir l'actif (charge exc. de la VNC 675) : perte de l'actif
		$eAccountDisposal = \account\AccountLib::getByClass(\Setting::get('account\disposalAssetValueClass'));
		$values = [
			'account' => $eAccountDisposal['id'],
			'accountLabel' => \account\ClassLib::pad($eAccountDisposal['class']),
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
			$eAccountProduct = \account\AccountLib::getByClass(\Setting::get('account\productAssetValueClass'));
			$values = [
				'account' => $eAccountProduct['id'],
				'accountLabel' => \account\ClassLib::pad($eAccountProduct['class']),
				'date' => $date,
				'description' => $eAccountProduct['description'],
				'amount' => $amount,
				'type' => \journal\OperationElement::CREDIT,
				'asset' => $eAsset,
			];
			\journal\OperationLib::createFromValues($values);

			// c. Créer l'écriture débit compte banque (512) OU le débit créance sur cession (462)
			if($createReceivable === TRUE) {

				$receivablesOnAssetDisposalClass = \Setting::get('account\receivablesOnAssetDisposalClass');
				$debitAccountLabel = \account\ClassLib::pad($receivablesOnAssetDisposalClass);
				$eAccountDebit = \account\AccountLib::getByClass($receivablesOnAssetDisposalClass);

			} else {

				$bankClass = \Setting::get('account\bankAccountClass');
				$debitAccountLabel = \account\ClassLib::pad($bankClass);
				$eAccountDebit = \account\AccountLib::getByClass($bankClass);

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
