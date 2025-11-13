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

		$amortizationValue = AmortizationLib::calculateAmortization($eFinancialYear['startDate'], $endDate, $eAsset);

		// Dotation aux amortissements
		if(self::isIntangibleAsset($eAsset['accountLabel'])) {
			$amortizationChargeClass = \account\AccountSetting::INTANGIBLE_ASSETS_DEPRECIATION_CHARGE_CLASS;
		} else {
			$amortizationChargeClass = \account\AccountSetting::TANGIBLE_ASSETS_DEPRECIATION_CHARGE_CLASS;
		}

		$eAccountDepreciationCharge = \account\AccountLib::getByClass($amortizationChargeClass);
		$values = [
			'account' => $eAccountDepreciationCharge['id'],
			'accountLabel' => \account\ClassLib::pad($eAccountDepreciationCharge['class']),
			'date' => $endDate,
			'description' => $eAccountDepreciationCharge['description'],
			'amount' => $amortizationValue,
			'type' => \journal\OperationElement::DEBIT,
			'asset' => $eAsset,
			'financialYear' => $eFinancialYear['id'],
		];
		\journal\OperationLib::createFromValues($values);

		// Amortissement
		$values = self::getDepreciationOperationValues($eFinancialYear, $eAsset, $endDate, $amortizationValue);

		if($amortizationValue !== 0.0) {
			\journal\OperationLib::createFromValues($values);
		}

		// Créer une entrée dans la table Amortization
		$eDepreciation = new Amortization([
			'asset' => $eAsset,
			'amount' => $amortizationValue,
			'type' => Amortization::ECONOMIC,
			'date' => $endDate,
			'financialYear' => $eFinancialYear,
		]);

		Amortization::model()->insert($eDepreciation);

		// Si l'immobilisation a été entièrement amortie ou n'est plus valide
		$depreciatedValue = Amortization::model()
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

		$amortizationClass = self::depreciationClassByAssetClass(substr($eAsset['accountLabel'], 0, 3));
		$eAccountDepreciation = \account\AccountLib::getByClass(trim($amortizationClass, '0'));

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

	public static function getAll(\Search $search): \Collection {

		return Asset::model()
			->select(Asset::getSelection())
			->whereAccountLabel($search->get('accountLabel'), if: $search->get('accountLabel'))
			->whereAccount($search->get('account'), if: $search->get('account') and $search->get('account')->notEmpty())
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

	public static function isDepreciable(Asset $eAsset): bool {

		return substr($eAsset['accountLabel'], 0, mb_strlen(\account\AccountSetting::NON_DEPRECIABLE_ASSET_CLASS)) !== \account\AccountSetting::NON_DEPRECIABLE_ASSET_CLASS;

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
						'cAmortization' => Amortization::model()
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
		$accumulatedDepreciationsValue = $eAsset['cAmortization']->sum('amount');
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
		$eAccountDisposal = \account\AccountLib::getByClass(\account\AccountSetting::DISPOSAL_ASSET_VALUE_CLASS);
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
			$eAccountProduct = \account\AccountSetting::PRODUCT_ASSET_VALUE_CLASS;
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

				$receivablesOnAssetDisposalClass = \account\AccountSetting::RECEIVABLES_ON_ASSET_DISPOSAL_CLASS;
				$debitAccountLabel = \account\ClassLib::pad($receivablesOnAssetDisposalClass);
				$eAccountDebit = \account\AccountLib::getByClass($receivablesOnAssetDisposalClass);

			} else {

				$bankClass = \account\AccountSetting::BANK_ACCOUNT_CLASS;
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

	public static function computeTable(Asset $eAsset): array {

		if($eAsset['economicMode'] === Asset::LINEAR) {

			return self::computeLinearTable($eAsset);

		} else if($eAsset['economicMode'] === Asset::DEGRESSIVE) {

			return self::computeDegressiveTable($eAsset);

		}

	}

	/**
	 * Calcul du prorata
	 * - si linéaire => date de mise en service
	 * - si dégressif => 1er jour du mois de la date d'acquisition
	 *
	 * @param string $firstDateOfFinancialYear
	 * @param Asset $eAsset
	 * @return void
	 */
	private static function computeProrataTemporis(\account\FinancialYear $eFinancialYear, Asset $eAsset): float {

		if($eAsset['economicMode'] === Asset::LINEAR) {

			$referenceDate = $eAsset['startDate'];
			$daysFirstMonth = 30 - (int)mb_substr($referenceDate, -2);

		} else {

			$referenceDate = mb_substr($eAsset['acquisitionDate'], 0, 8).'01';
			$daysFirstMonth = 0;

		}

		$datetime1 = new \DateTime($referenceDate);
		$datetime2 = new \DateTime($eFinancialYear['endDate']);
		$interval = $datetime1->diff($datetime2);
		$months = (int)$interval->format('%m');

		$days = $daysFirstMonth + $months * 30;

		// Nombre de mois dans cet exercice comptable
		$datetime1 = new \DateTime($eFinancialYear['startDate']);
		$datetime2 = new \DateTime($eFinancialYear['endDate']);
		$interval = $datetime1->diff($datetime2);
		$monthsInFinancialYear = (int)$interval->format('%m') + 1;

		return round(min(1, $days / (30 * $monthsInFinancialYear)), 2);
	}

	private static function computeLinearTable(Asset $eAsset): array {

		$durationInYears = ($eAsset['economicDuration'] / 12);
		$rate = round(1 / $durationInYears * 100, 2);

		$cFinancialYearAll = \account\FinancialYearLib::getAll();

		$table = [];
		$amortizationCumulated = 0;
		$currentDate = $eAsset['startDate'];
		$eFinancialYear = $cFinancialYearAll->find(fn($e) => $eAsset['startDate'] <= $currentDate and $e['endDate'] >= $currentDate)->first();

		for($i = 0; $i <= $durationInYears; $i++) {

			$eFinancialYearCurrent = $cFinancialYearAll->find(fn($e) => $e['startDate'] <= $currentDate and $e['endDate'] >= $currentDate)->first();

			if($eFinancialYearCurrent === NULL) {
				$eFinancialYear = new \account\FinancialYear([
					'startDate' => date('Y-m-d', strtotime($eFinancialYear['startDate']. ' + 1 YEAR')),
					'endDate' => date('Y-m-d', strtotime($eFinancialYear['endDate'].' + 1 YEAR')),
				]);
			} else {
				$eFinancialYear = $eFinancialYearCurrent;
			}

			$eDepreciation = $eAsset['cAmortization'][$i] ?? new Amortization();

			if($eDepreciation->empty()) {

				switch($i) {
					case 0:
						$amortization = round($eAsset['amortizableBase'] * $rate * self::computeProrataTemporis($eFinancialYear, $eAsset) / 100, 2);
						break;
					case $durationInYears:
						$amortization = round($eAsset['amortizableBase'] - $amortizationCumulated, 2);
						break;
					default:
						$amortization = round($eAsset['amortizableBase'] * $rate / 100, 2);
				}

			} else {

				$amortization = $eDepreciation['amount'];

			}

			$amortizationCumulated += $amortization;

			$table[] = [
				'financialYear' => $eFinancialYear,
				'base' => $eAsset['amortizableBase'],
				'rate' => $rate,
				'amortizationValue' => $amortization,
				'amortizationValueCumulated' => round($amortizationCumulated, 2),
				'endValue' => round($eAsset['amortizableBase'] - $amortizationCumulated),
				'amortization' => $eDepreciation,
			];

			$currentDate = date('Y-m-d', strtotime($currentDate.' + 1 YEAR'));

			// On n'amortit pas + que la valeur initiale
			if($amortizationCumulated >= $eAsset['amortizableBase']) {
				break;
			}

		}

		return $table;

	}

	private static function computeDegressiveTable(Asset $eAsset): array {

		$baseLinearRate = round(1 / $eAsset['economicDuration'] * 100, 2);

		if($eAsset['economicDuration'] === 3 or $eAsset['economicDuration'] === 4) {

			$degressiveCoefficient = 1.25;

		} else if($eAsset['economicDuration'] === 5 or $eAsset['economicDuration'] === 6) {

			$degressiveCoefficient = 1.75;

		} else {

			$degressiveCoefficient = 2.25;

		}

		$cFinancialYearAll = \account\FinancialYearLib::getAll();

		$table = [];
		$amortizationCumulated = 0;
		$currentDate = $eAsset['startDate'];
		$eFinancialYear = $cFinancialYearAll->find(fn($e) => $eAsset['startDate'] <= $currentDate and $e['endDate'] >= $currentDate)->first();

		for($i = 0; $i <= $eAsset['economicDuration'] / 12; $i++) {

			$eFinancialYearCurrent = $cFinancialYearAll->find(fn($e) => $e['startDate'] <= $currentDate and $e['endDate'] >= $currentDate)->first();
			$linearRate = round(1 / ($eAsset['economicDuration'] + 1 - $i) * 100, 2);
			$degressiveRate = round($baseLinearRate * $degressiveCoefficient, 2);

			$rate = round(max($linearRate, $degressiveRate), 2);

			if($eFinancialYearCurrent === NULL) {
				$eFinancialYear = new \account\FinancialYear([
					'startDate' => date('Y-m-d', strtotime($eFinancialYear['startDate']. ' + 1 YEAR')),
					'endDate' => date('Y-m-d', strtotime($eFinancialYear['endDate'].' + 1 YEAR')),
				]);
			} else {
				$eFinancialYear = $eFinancialYearCurrent;
			}

			$eDepreciation = $eAsset['cAmortization'][$i] ?? new Amortization();

			if($eDepreciation->empty()) {

				switch($i) {
					case 0:
						$amortization = round(($eAsset['amortizableBase'] - $amortizationCumulated) * $rate * self::computeProrataTemporis($eFinancialYear, $eAsset) / 100, 2);
						break;
					case $eAsset['economicDuration']:
						$amortization = round(($eAsset['amortizableBase'] - $amortizationCumulated), 2);
						break;
					default:
						$amortization = round(($eAsset['amortizableBase'] - $amortizationCumulated) * $rate / 100, 2);
				}

			} else {

				$amortization = $eDepreciation['amount'];

			}

			$base = round($eAsset['amortizableBase'] - $amortizationCumulated, 2);
			$amortizationCumulated += $amortization;

			$table[] = [
				'financialYear' => $eFinancialYear,
				'base' => $base,
				'linearRate' => $linearRate,
				'degressiveRate' => $degressiveRate,
				'rate' => $rate,
				'amortizationValue' => $amortization,
				'amortizationValueCumulated' => round($amortizationCumulated, 2),
				'endValue' => round($eAsset['amortizableBase'] - $amortizationCumulated),
				'amortization' => $eDepreciation,
			];

			$currentDate = date('Y-m-d', strtotime($currentDate.' + 1 YEAR'));

		}

		return $table;

	}

}
?>
