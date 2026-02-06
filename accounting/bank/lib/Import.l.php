<?php
namespace bank;

class ImportLib extends ImportCrud {

	public static function getPropertiesUpdate(): array {
		return ['account'];
	}

	public static function formatCurrentFinancialYearImports(\account\FinancialYear $eFinancialYear, \Collection $cImport): array {

		if($eFinancialYear->empty()) {

			if($cImport->empty()) {

				$eAccount = \account\Account::model()
					->select(['date' => new \Sql('MIN(DATE(createdAt))')])
					->get();

				$startDate = $eAccount['date'];

			} else {

				$startDate = date('Y-m-d', strtotime(min($cImport->getColumn('startDate'))));
			}

		} else {

			$startDate = $eFinancialYear['startDate'];

		}

		if($eFinancialYear->notEmpty() and $eFinancialYear['endDate'] !== NULL) {
			$endDate = $eFinancialYear['endDate'];
		} else {
			$endDate = date('Y-m-d');
		}

		$imports = [];

		$startPeriod = NULL;
		$endPeriod = NULL;
		$currentPeriod = new Import();

		for($currentDate = $startDate; $currentDate <= $endDate; $currentDate = date('Y-m-d', strtotime($currentDate.' +1 day'))) {

			if($startPeriod === NULL) {
				$startPeriod = $currentDate;
			}

			$eImport = self::extractImportFromDate($cImport, $currentDate);

			if($endPeriod !== NULL) {
				if($eImport->exists() === FALSE) { // No corresponding import
					if($currentPeriod->exists() === TRUE) { // Last import existed
						$imports[] = [
							'startPeriod' => $startPeriod,
							'endPeriod' => $endPeriod,
							'import' => $currentPeriod
						];
						$startPeriod = $currentDate;
					}
				} else {
					if($currentPeriod->exists() === FALSE) { // Last import did not exist
						$imports[] = [
							'startPeriod' => $startPeriod,
							'endPeriod' => $endPeriod,
							'import' => new Import()
						];
						$startPeriod = $currentDate;
					} else {
						if($currentPeriod['id'] !== $eImport['id']) { // Changed the import
							$imports[] = [
								'startPeriod' => $startPeriod,
								'endPeriod' => $endPeriod,
								'import' => $currentPeriod
							];
							$startPeriod = $currentDate;
						}
					}
				}
			}

			$currentPeriod = $eImport;
			$endPeriod = $currentDate;
		}

		// Add the last period
		$imports[] = [
			'startPeriod' => date('Y-m-d', strtotime($startPeriod)),
			'endPeriod' => date('Y-m-d', strtotime($endPeriod)),
			'import' => $currentPeriod
		];

		return $imports;

	}

	protected static function extractImportFromDate(\Collection $cImport, string $date): Import {

		$imports = [ImportElement::FULL => [], ImportElement::PARTIAL => [], ImportElement::ERROR => [], ImportElement::NONE => []];

		foreach($cImport as $eImport) {
			if(date('Y-m-d', strtotime($eImport['startDate'])) <= $date and date('Y-m-d', strtotime($eImport['endDate'])) >= $date) {
				$imports[$eImport['status']][] = $eImport;
			}
		}

		// On récupère l'import le plus pertinent
		foreach($imports as $importList) {
			if(count($importList) > 0) {
				return $importList[0];
			}
		}
		return new Import();
	}

	public static function getWaitingImports(): \Collection {

		$cImport = Import::model()
			->select(Import::getSelection())
			->whereAccount(NULL)
			->sort(['processedAt' => SORT_DESC])
			->getCollection();

		return $cImport->find(fn($e) => count($e['result']['imported']) > 0);

	}

	public static function getLastImport(): Import {

		return Import::model()
			->select(Import::getSelection())
			->sort(['processedAt' => SORT_DESC])
			->get();
	}

	public static function getAll(\account\FinancialYear $eFinancialYear, ?int $selectedBankAccount): \Collection {

		if($eFinancialYear->notEmpty()) {
			Import::model()
				->whereStartDate('<=', $eFinancialYear['endDate'].' 00:00:00')
				->whereEndDate('>=', $eFinancialYear['startDate'].' 23:59:59');
		}

		if($selectedBankAccount) {
			Import::model()->whereAccount($selectedBankAccount);
		}

		return Import::model()
			->select(Import::getSelection() + [
				'account' => BankAccount::getSelection(),
				'nCashflowAllocated' => Cashflow::model()
					->select('id', 'status')
					->whereStatus(Cashflow::ALLOCATED)
					->delegateCollection('import', callback: fn(\Collection $cCashflow) => $cCashflow->count())
			])
			->whereStatus('IN', [Import::PARTIAL, Import::FULL])
			->sort(['startDate' => SORT_ASC])
			->getCollection();
	}
	public static function getLonely(\account\FinancialYear $eFinancialYear): \Collection {

		if($eFinancialYear->notEmpty()) {
			Import::model()
				->whereStartDate('<=', $eFinancialYear['endDate'].' 00:00:00')
				->whereEndDate('>=', $eFinancialYear['startDate'].' 23:59:59');
		}
		return Import::model()
			->select(Import::getSelection())
			->whereAccount(NULL)
			->whereStatus('!=', Import::NONE)
			->sort(['startDate' => SORT_ASC])
			->getCollection();
	}

	public static function importBankStatement(\farm\Farm $eFarm): ?Import {

		if(isset($_FILES['ofx']) === FALSE) {
			return null;
		}

		$filepath = $_FILES['ofx']['tmp_name'];
		$filename = $_FILES['ofx']['name'];

		// Vérification de la taille (max 1 Mo)
		if(filesize($filepath) > 1024 * 1024) {
			\Fail::log('Import::ofxSize');
			return null;
		}

		copy($filepath, '/tmp/ofx/'.date('YmdHis').'-'.$eFarm['id'].'.ofx');

		Cashflow::model()->beginTransaction();

		if(\bank\OfxParserLib::isOfx($filepath) === FALSE) {

			\Fail::log('Import::ofxFormat');
			Cashflow::model()->rollBack();
			return new Import();

		}
		try {



			$ofx = \bank\OfxParserLib::extractFile($filepath);;

			$eBankAccount = \bank\OfxParserLib::extractAccount($ofx);

			$import = \bank\OfxParserLib::extractImport($ofx);

			$eImport = new Import([
				'filename' => $filename,
				'account' => $eBankAccount->exists() ? $eBankAccount : new BankAccount(), // il y a bankId et accountId dans tous les cas
				'startDate' => $import['startDate'],
				'endDate' => $import['endDate'],
				'result' => [],
				'status' => ImportElement::PROCESSING,
				'bankId' => $eBankAccount['bankId'],
				'accountId' => $eBankAccount['accountId'],
			]);

			Import::model()->insert($eImport);

			$cCashflow = \bank\OfxParserLib::extractOperations($ofx, $eBankAccount, $eImport);
			$result = \bank\CashflowLib::insertMultiple($cCashflow);

			if(count($result['imported']) === 0) {
				\Fail::log('Import::nothingImported');
				$status = ImportElement::NONE;
			} else if(count($result['alreadyImported']) > 0) {
				$status = ImportElement::PARTIAL;
			} else {
				$status = ImportElement::FULL;
			}

		} catch (\Exception $e) {

			\Fail::log('Import::ofxError');
			$status = ImportElement::ERROR;
			$result = [];

		}

		if(isset($eImport)) {

			$eImport['result'] = $result;
			$eImport['status'] = $status;
			$eImport['processedAt'] = new \Sql('NOW()');

			Import::model()->update($eImport, $eImport->extracts(['result', 'status', 'processedAt']));

		}

		Cashflow::model()->commit();

		return $eImport ?? new Import();

	}

	public static function update(Import $e, array $properties): void {

		if(!array_delete($properties, 'account') or count($properties) !== 0) {
			return;
		}

		Import::model()->beginTransaction();

			if($e['newAccount']->empty()) {

				// Créer le nouveau compte bancaire
				$bankId = uniqid();
				$accountId = uniqid();

				$e['account'] = BankAccountLib::createNew($bankId, $accountId);

			} else {

				$e['account'] = $e['newAccount'];

				BankAccount::model()->update($e['account'], ['bankId' => $e['bankId'], 'accountId' => $e['accountId']]);

			}

			parent::update($e, ['account', 'status']);

			Cashflow::model()
				->whereImport($e)
				->update(['account' => $e['account']]);

		Import::model()->commit();
	}

	/**
	 * Reprise de rapprochement en attente
	 */
	public static function checkForReconciliation(\farm\Farm $eFarm): void {

		// Le cron va passer
		if(\company\CompanyCron::model()
			->whereFarm($eFarm)
			->whereAction(\company\CompanyCronLib::RECONCILIATE)
			->count() > 0) {
			return;
		}

		// Est-ce qu'il y a des rapprochements en attente ?
		$nImportWaiting = Import::model()
			->whereReconciliation(Import::WAITING)
			->count();

		if($nImportWaiting === 0) {
			return;
		}

		// On lance les rapprochements
		\preaccounting\SuggestionLib::calculateSuggestionsByFarm($eFarm);

	}

	public static function delete(Import $e): void {

		Import::model()->beginTransaction();

			parent::delete($e);

			Cashflow::model()->whereImport($e)->delete();

		Import::model()->commit();

	}

}
?>
