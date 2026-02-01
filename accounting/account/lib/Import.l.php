<?php
namespace account;

Class ImportLib extends ImportCrud {

	public static function getPropertiesCreate(): array {

		return ['financialYear', 'financialYearStatus'];

	}

	public static function getAll(): \Collection {

		return Import::model()
			->select(Import::getSelection())
			->sort(['createdAt' => SORT_DESC])
			->getCollection(NULL, NULL, 'financialYear');

	}

	public static function cancelAll(FinancialYear $eFinancialYear): void {

		Import::model()
			->whereFinancialYear($eFinancialYear)
			->whereStatus('IN', [Import::WAITING, Import::FEEDBACK_REQUESTED, Import::FEEDBACK_TO_TREAT, Import::CREATED, Import::IN_PROGRESS])
			->update(['status' => Import::CANCELLED]);

	}

	public static function getByFinancialYear(FinancialYear $eFinancialYear): \Collection {

		return Import::model()
			->select(Import::getSelection())
			->whereFinancialYear($eFinancialYear)
			->sort(['createdAt' => SORT_DESC])
			->getCollection(NULL, NULL, 'financialYear');

	}

	public static function updateRuleValue(Import $eImport, array $input): void {

		switch($input['type']) {
			case 'comptes':
			case 'comptesAux':
				$eAccount = AccountLib::getById($input['value']);

				if($eAccount->empty() or isset($eImport['rules'][$input['type']][$input['key']]) === FALSE) {
					return;
				}

				if(AccountLabelLib::isFromClass($input['key'], $eAccount['class']) === FALSE) {
					\Fail::log('Import::accountNotCorresponding');
					return;
				}

				$eImport['rules'][$input['type']][$input['key']]['account'] = $eAccount->extracts(['id']);
				break;

			case 'journaux':
				$eJournalCode = \journal\JournalCodeLib::ask((int)$input['value']);

				if($eJournalCode->empty()) {
					return;
				}

				$eImport['rules'][$input['type']][$input['key']]['journalCode'] = $eJournalCode->extracts(['id']);
				break;

			case 'paiements':
				$eMethod = \payment\MethodLib::getById($input['value']);

				if($eMethod->empty()) {
					return;
				}

				$eImport['rules'][$input['type']][$input['key']]['payment'] = $eMethod->extracts(['id']);

				break;
		}

		Import::model()->update($eImport, ['rules' => $eImport['rules']]);

	}

	public static function validateRules(Import $eImport): bool {

		$update['updatedAt'] = new \Sql('NOW()');

		$isImportable =
			(array_find($eImport['rules']['journaux'], fn($journal) => count($journal['journalCode']) === 0) === NULL and
			array_find($eImport['rules']['comptes'], fn($compte) => count($compte['account']) === 0) === NULL and
			array_find($eImport['rules']['comptesAux'], fn($compte) => count($compte['account']) === 0) === NULL and
			array_find($eImport['rules']['paiements'], fn($paiement) => count($paiement['payment']) === 0) === NULL)
		;

		$update['status'] = $isImportable ? Import::FEEDBACK_TO_TREAT : Import::FEEDBACK_REQUESTED;

		$update['rules'] = $eImport['rules'];

		Import::model()->update($eImport, $update);

		return ($isImportable === FALSE);

	}

	public static function create(Import $e): void {

		if(isset($_FILES['fec']['tmp_name']) === FALSE) {
			throw new \NotExpectedAction('File content not found for FEC import.');
		}

		$e['content'] = trim(file_get_contents($_FILES['fec']['tmp_name']));
		$e['filename'] = $_FILES['fec']['name'];

		// Check filename at least
		preg_match('/([\d]{9,9})FEC([\d]{8,8})/', $e['filename'], $matches);
		if(empty($matches)) {
			\Fail::log('Import::filename.incorrect', wrapper: 'fec');
			return;
		}

		$eFinancialYear = FinancialYearLib::getById($e['financialYear']['id']);
		$importDate = date('Y-m-d', strtotime($matches[2]));
		if(
			!!\util\DateLib::isValid(date('Y-m-d', strtotime($importDate))) === FALSE or
			$importDate > $eFinancialYear['endDate'] or
			$importDate < $eFinancialYear['startDate']
		) {
			\Fail::log('Import::dates.incorrect', wrapper: 'fec');
			return;
		}

		// Check headers
		// Trouver le délimiteur
		preg_match('/JournalCode(.*)JournalLib/', $e['content'], $matches);
		if(isset($matches[1]) === FALSE or mb_strlen($matches[1]) !== 1) {
			\Fail::log('Import::header.incorrect', wrapper: 'fec');
			return;
		}

		$e['delimiter'] = $matches[1];

		// Vérifier le nombre de colonnes
		$lines = explode("\n", trim($e['content']));
		$header = trim(first($lines));
		$cols = explode($e['delimiter'], $header);
		if(in_array(count($cols), [18, 21]) === FALSE) {
			\Fail::log('Import::header.missingCols', wrapper: 'fec');
			return;
		}

		$headerModel = array_map(fn($head) => mb_strtolower($head), FecLib::getHeader(TRUE));
		foreach($cols as $col) {
			if(in_array(mb_strtolower($col), $headerModel) === FALSE) {
			\Fail::log('Import::header.incorrectCol', wrapper: 'fec');
			return;
			}
		}

		$e->validate();

		Import::model()->beginTransaction();

		parent::create($e);

		Import::model()->commit();

	}

	public static function currentOpenImport(): Import {

		return Import::model()
			->select(Import::getSelection())
			->whereStatus('NOT IN', [Import::DONE, Import::CANCELLED])
			->get();

	}

	public static function findAccount(\Collection $cAccount, string $account, string $accountLib, \Collection $cImport): Account {

		// On regarde dans les imports précédents
		if($cImport->notEmpty()) {

			foreach($cImport as $eImport) {

				if(
					isset($eImport['rules']['comptes'][$account]) === FALSE and
					isset($eImport['rules']['comptesAux'][$account]) === FALSE
				) {
					continue;
				}

				if(
					$eImport['rules']['comptes'][$account]['label'] === $accountLib and
					isset($eImport['rules']['comptes'][$account]['account']['id'])
				) {

					return $cAccount->find(fn($e) => $e['id'] === $eImport['rules']['comptes'][$account]['account']['id'])->first();

				} else if(
					$eImport['rules']['comptesAux'][$account]['label'] === $accountLib and
					isset($eImport['rules']['comptesAux'][$account]['account']['id'])
				) {

					return $cAccount->find(fn($e) => $e['id'] === $eImport['rules']['comptesAux'][$account]['account']['id'])->first();

				}
			}
		}

		$length = mb_strlen($account);
		for($i = 1; $i < $length ; $i++) {

			// On n'a pas trouvé juste en enlevant les leading zeros
			if((int)mb_substr($account, $length - $i) !== 0) {
				break;
			}

			$currentAccount = mb_substr($account, 0, $length - $i);
			$foundAccount = $cAccount->find(fn($e) => $e['class'] === $currentAccount);
			if($foundAccount->notEmpty()) {
				return new Account($foundAccount->first()->extracts(['id']));
			}

		}

		return new Account();

	}

	public static function findJournal(\Collection $cJournalCode, string $code, string $journalLib, \Collection $cImport): \journal\JournalCode {

		if($cImport->notEmpty()) {

			foreach($cImport as $eImport) {

				if(isset($eImport['rules']['journaux'][$code]) === FALSE) {
					continue;
				}

				if(
					$eImport['rules']['journaux'][$code]['label'] === $journalLib and
					isset($eImport['rules']['journaux'][$code]['journalCode']['id'])
				) {

					return \journal\JournalCodeLib::ask($eImport['rules']['journaux'][$code]['journalCode']['id']);

				}
			}
		}

		$cJournalCodeFiltered = $cJournalCode->find(fn($e) => $e['code'] === $code);

		if($cJournalCodeFiltered->empty()) {
			return new \journal\JournalCode();
		}

		return new \journal\JournalCode($cJournalCodeFiltered->first()->extracts(['id']));

	}

	protected static function extractLineElements(Import $eImport, string $line): array {

			$lineElements = explode($eImport['delimiter'], $line);

			if(count($lineElements) === 18) {

				$lineElements[] = NULL;
				$lineElements[] = NULL;
				$lineElements[] = NULL;

				[
					$journalCode, $journalLib,
					$ecritureNum, $ecritureDate,
					$compteNum, $compteLib,
					$compAuxNum, $compAuxLib,
					$pieceRef, $pieceDate,
					$ecritureLib,
					$debit, $credit,
					$ecritureLet, $dateLet, $validDate,
					$montantDevise, $IDDevise,
				] = $lineElements;

			}

			return $lineElements;
	}

	public static function extractRules(\farm\Farm $eFarm, Import $eImport): array {

		// Va se baser sur d'autres imports s'il en existe
		$cImport = Import::model()
			->select('rules')
			->whereId('!=', $eImport['id'])
			->whereStatus(Import::DONE)
			->getCollection();

		$lines = array_slice(explode("\n", trim($eImport['content'])), 1);
		$cAccount = AccountLib::getAll();
		$cJournalCode = \journal\JournalCodeLib::deferred();

		$journaux = [];
		$comptes = [];
		$comptesAux = [];
		$paiements = [];

		foreach($lines as $line) {

			[
				$journalCode, $journalLib,
				$ecritureNum, $ecritureDate,
				$compteNum, $compteLib,
				$compAuxNum, $compAuxLib,
				$pieceRef, $pieceDate,
				$ecritureLib,
				$debit, $credit,
				$ecritureLet, $dateLet, $validDate,
				$montantDevise, $IDDevise,
				$dateRglt, $modeRglt, $natOp
			] = self::extractLineElements($eImport, $line);

			if($journalCode and $journalLib) {
				$journaux[$journalCode] = ['journalCode' => self::findJournal($cJournalCode, $journalCode, $journalLib, $cImport), 'label' => $journalLib];
			}

			if($compteNum and $compteLib) {
				$eAccount = self::findAccount($cAccount, (string)$compteNum, $compteLib, $cImport);
				$comptes[$compteNum] = ['account' => $eAccount, 'label' => $compteLib];
			}

			if($compAuxNum and $compAuxLib) {
				$eAccount = self::findAccount($cAccount, (string)$compAuxNum, $compAuxLib, $cImport);
				$comptesAux[$compAuxNum] = ['account' => $eAccount, 'label' => $compAuxLib];
			}

			if($modeRglt) {
				$paiements[$modeRglt] = ['payment' => new \payment\Method(), 'label' => $modeRglt];
			}
		}

		return [
			'journaux' => $journaux,
			'comptes' => $comptes,
			'comptesAux' => $comptesAux,
			'paiements' => $paiements,
		];
	}

	public static function treatImport(\farm\Farm $eFarm, Import $eImport): bool {

		if($eImport->empty()) {
			return TRUE;
		}

		// Traitement en cours ou alors en attente de correction de l'utilisateur
		if(in_array($eImport['status'], [Import::IN_PROGRESS, Import::FEEDBACK_REQUESTED])) {
			return FALSE;
		}

		// On bloque les accès concurrents à cet import
		$oldStatus = $eImport['status'];
		$eImport['status'] = Import::IN_PROGRESS;
		Import::model()->update($eImport, $eImport->extracts(['status']));

		// On commence le traitement
		$eImport['updatedAt'] = new \Sql('NOW()');
		$properties = ['updatedAt'];

		if($oldStatus === Import::CREATED) {

			$eImport['rules'] = self::extractRules($eFarm, $eImport);
			$properties[] = 'rules';

			// Vérifie si on a toutes les infos
			$isImportable =
				(count(array_find($eImport['rules']['journaux'], fn($journal) => $journal['journalCode']->empty()) ?? []) === 0 and
				count(array_find($eImport['rules']['comptes'], fn($compte) => $compte['account']->empty()) ?? []) === 0 and
				count(array_find($eImport['rules']['comptesAux'], fn($compte) => $compte['account']->empty()) ?? []) === 0 and
				count(array_find($eImport['rules']['paiements'], fn($paiement) => $paiement['payment']->empty()) ?? []) === 0)
			;

			$eImport['status'] = $isImportable ? Import::WAITING : Import::FEEDBACK_REQUESTED;
			$properties[] = 'status';
			$properties[] = 'rules';

		} else { // On revérifie les règles.

			$missingJournals = array_find($eImport['rules']['journaux'], fn($journal) => isset($journal['journalCode']['id']) === FALSE);
			$missingAccounts = array_find($eImport['rules']['comptes'], fn($compte) => isset($compte['account']['id']) === FALSE);
			$missingAuxAccounts = array_find($eImport['rules']['comptesAux'], fn($compte) => isset($compte['account']['id']) === FALSE);
			$missingPayments = $eImport['rules']['paiements'] ? NULL : array_find($eImport['rules']['paiements'], fn($paiement) => isset($paiement['payment']['id']) === FALSE);

			$isImportable = (
				($missingJournals === NULL or count($missingJournals) > 0) and
				($missingAccounts === NULL or count($missingAccounts) > 0) and
				($missingAuxAccounts === NULL or count($missingAuxAccounts) > 0) and
				($missingPayments === NULL or count($missingPayments) > 0)
			);

			$eImport['status'] = $isImportable ? Import::WAITING : Import::FEEDBACK_REQUESTED;
			$properties[] = 'status';

		}

		Import::model()->update($eImport, $eImport->extracts($properties));

		if($isImportable === FALSE) {
			return FALSE;
		}

		// Import créé et importable ou alors import dont les règles ont été validées par l'utilisateur
		// => On procède à l'import

		$cOperation = new \Collection();

		$lines = array_slice(explode("\n", trim($eImport['content'])), 1);

		$cAccount = AccountLib::getAll();
		$cMethod = \payment\MethodLib::getByFarm($eFarm, FALSE);

		$update = ['status' => Import::DONE, 'updatedAt' => new \Sql('NOW()')];

		foreach($lines as $line) {
			[
				$journalCode, $journalLib,
				$ecritureNum, $ecritureDate,
				$compteNum, $compteLib,
				$compAuxNum, $compAuxLib,
				$pieceRef, $pieceDate,
				$ecritureLib,
				$debit, $credit,
				$ecritureLet, $dateLet, $validDate,
				$montantDevise, $IDDevise,
				$dateRglt, $modeRglt, $natOp
			] = self::extractLineElements($eImport, $line);

			$ecritureDate = new \preaccounting\SaleUi()->toDate($ecritureDate);

			if(FinancialYearLib::isDateInFinancialYear($ecritureDate, $eImport['financialYear']) === FALSE) {
				continue;
			}

			if(
				isset($eImport['rules']['comptes'][$compteNum]['account']['id']) and
				$cAccount->offsetExists($eImport['rules']['comptes'][$compteNum]['account']['id'])
			) {

				$eAccount = $cAccount->find(fn($e) => $e['id'] === $eImport['rules']['comptes'][$compteNum]['account']['id'])->first();
				if(AccountLabelLib::isFromClass($compteNum, $eAccount['class']) === FALSE) {
					throw new \Exception('Consistency issue between FEC account and account class for import #'.$eImport['id'].' and farm '.$eFarm['id']);
				}

			} else {

				$eAccount = new Account();

			}

			$eJournalCode = \journal\JournalCodeLib::ask($eImport['rules']['journaux'][$journalCode]['journalCode']['id']);

			if(
				isset($eImport['rules']['paiements'][$modeRglt]['payment']['id']) and
				$cMethod->offsetExists($eImport['rules']['paiements'][$modeRglt]['payment']['id'])
			) {

				$eMethod = $cMethod->offsetGet($eImport['rules']['paiements'][$modeRglt]['payment']['id']);

			} else {

				$eMethod = new \payment\Method();

			}

			$debit = (float)str_replace(',', '.', $debit);
			$credit = (float)str_replace(',', '.', $credit);

			$eOperation = new \journal\Operation([
				'hash' => \journal\OperationLib::generateHash().\journal\JournalSetting::HASH_LETTER_FEC_IMPORT,
				'number' => $ecritureNum,
				'financialYear' => $eImport['financialYear'],
				'journalCode' => $eJournalCode,
				'account' => $eAccount,
				'accountLabel' => $compteNum,
				'date' => $ecritureDate,
				'description' => $ecritureLib,
				'document' => $pieceRef,
				'documentDate' => new \preaccounting\SaleUi()->toDate($pieceDate),
				'amount' => $debit > 0 ? $debit : $credit,
				'type' => $debit > 0 ? \journal\Operation::DEBIT : \journal\Operation::CREDIT,
				'paymentDate' => $dateRglt ? new \preaccounting\SaleUi()->toDate($dateRglt) : NULL,
				'paymentMethod' => $eMethod,
			]);
			$cOperation->append($eOperation);

		}

		Import::model()->beginTransaction();

			Import::model()->update($eImport, $update);

			\journal\Operation::model()->insert($cOperation);

			LogLib::save('open', 'FinancialYear', ['id' => $eImport['financialYear']['id']]);

			$update = [
				'openDate' => new \Sql('NOW()'),
				'status' => FinancialYear::OPEN,
			];
			if($eImport['financialYearStatus'] === Import::CLOSED) {
				$update['status'] = FinancialYear::CLOSE;
				$update['closeDate'] = new \Sql('NOW()');
			}
			FinancialYear::model()->update($eImport['financialYear'], $update);

			// On va chercher les autoconsommations et tenter de les regrouper
			\journal\OperationLib::checkForSelfConsumption($cOperation, TRUE);

		Import::model()->commit();

		return TRUE;

	}

	public static function getImports(): \Collection {

		return Import::model()
			->select(Import::getSelection())
			->sort(['createdAt' => SORT_DESC])
			->getCollection();

	}

}
