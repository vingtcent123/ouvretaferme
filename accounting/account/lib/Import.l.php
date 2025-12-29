<?php
namespace account;

Class ImportLib extends ImportCrud {

	public static function getPropertiesCreate(): array {

		return ['financialYear', 'delimiter'];

	}

	public static function updateRuleValue(\farm\Farm $eFarm, Import $eImport, array $input): void {

		switch($input['type']) {
			case 'comptes':
			case 'comptesAux':
				$eAccount = AccountLib::getById($input['value']);

				if($eAccount->empty()) {
					return;
				}

				$eImport['rules'][$input['type']][$input['key']]['account'] = $eAccount->extracts(['id']);
				break;

			case 'journaux':
				$eJournalCode = \journal\JournalCodeLib::getById($input['value']);

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

		$update['status'] = $isImportable ? Import::IN_PROGRESS : Import::FEEDBACK_REQUESTED;

		$update['rules'] = $eImport['rules'];

		Import::model()->update($eImport, $update);

		return ($isImportable === FALSE);

	}

	public static function create(Import $e): void {

		if(isset($_FILES['fec']['tmp_name']) === FALSE) {
			throw new \NotExpectedAction('File content not found for FEC import.');
		}

		$e['content'] = file_get_contents($_FILES['fec']['tmp_name']);
		$e['filename'] = $_FILES['fec']['name'];

		// Check filename at least
		preg_match('/([\d]{9,9})FEC([\d]{8,8})/', $e['filename'], $matches);
		if(empty($matches)) {
			\Fail::log('Import::filename.incorrect', wrapper: 'fec');
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
		$lines = explode("\n", $e['content']);
		$header = first($lines);
		$cols = explode($e['delimiter'], $header);
		if(in_array(count($cols), [18, 21]) === FALSE) {
			\Fail::log('Import::header.missingCols', wrapper: 'fec');
			return;
		}

		$headerModel = FecLib::getHeader(TRUE);
		foreach($cols as $col) {
			if(in_array($col, $headerModel) === FALSE) {
			\Fail::log('Import::header.incorrectCol', wrapper: 'fec');
			return;
			}
		}

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

	public static function findAccount(\Collection $cAccount, string $account): Account {

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

	public static function findJournal(\Collection $cJournalCode, string $code): \journal\JournalCode {

		$cJournalCodeFiltered = $cJournalCode->find(fn($e) => $e['code'] === $code);

		if($cJournalCodeFiltered->empty()) {
			return new \journal\JournalCode();
		}

		return new \journal\JournalCode($cJournalCodeFiltered->first()->extracts(['id']));

	}

	public static function extractRules(\farm\Farm $eFarm, Import $eImport): array {

		$lines = array_slice(explode("\n", trim($eImport['content'])), 1);
		$cAccount = AccountLib::getAll();
		$cMethod = \payment\MethodLib::getByFarm($eFarm, FALSE, TRUE);
		$cJournalCode = \journal\JournalCodeLib::getAll();

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
				] = explode($eImport['delimiter'], $line);

			if($journalCode and $journalLib) {
				$journaux[$journalCode] = ['journalCode' => self::findJournal($cJournalCode, $journalCode), 'label' => $journalLib];
			}

			if($compteNum and $compteLib) {
				$eAccount = self::findAccount($cAccount, (string)$compteNum);
				$comptes[$compteNum] = ['account' => $eAccount, 'label' => $compteLib];
			}

			if($compAuxNum and $compAuxLib) {
				$eAccount = self::findAccount($cAccount, (string)$compAuxNum);
				$comptesAux[$compAuxNum] = ['account' => $eAccount, 'label' => $compAuxLib];
			}

			if($modeRglt) {
				$paiements[$modeRglt] = ['payment' => new \payment\Method(), 'label' => $modeRglt];
			}
		}

		$paiements = array_unique($paiements);

		return [
			'journaux' => $journaux,
			'comptes' => $comptes,
			'comptesAux' => $comptesAux,
			'paiements' => $paiements,
		];
	}

	public static function toDate(string $fecDate): string {

		if(mb_strlen($fecDate) !== 8) {
			throw new \Exception("Unknown date format.");
		}

		return mb_substr($fecDate, 0, 4).'-'.mb_substr($fecDate, 4, 2).'-'.mb_substr($fecDate, 6, 2);

	}

	public static function manageImports(\farm\Farm $eFarm): void {

		$eImport = ImportLib::currentOpenImport();

		if($eImport->notEmpty()) {
			self::treatImport($eFarm, $eImport);
		}

	}

	public static function treatImport(\farm\Farm $eFarm, Import $eImport): void {

		$update = ['updatedAt' => new \Sql('NOW()')];

		if($eImport['status'] === Import::CREATED) {

			$update['rules'] = self::extractRules($eFarm, $eImport);

			// Vérifie si on a toutes les infos
			$isImportable =
				(count(array_find($update['rules']['journaux'], fn($journal) => $journal['journalCode']->empty()) ?? []) === 0 and
				count(array_find($update['rules']['comptes'], fn($compte) => $compte['account']->empty()) ?? []) === 0 and
				count(array_find($update['rules']['comptesAux'], fn($compte) => $compte['account']->empty()) ?? []) === 0 and
				count(array_find($update['rules']['paiements'], fn($paiement) => $paiement['payment']->empty()) ?? []) === 0)
			;

			$update['status'] = $isImportable ? Import::WAITING : Import::FEEDBACK_REQUESTED;

		} else if($eImport['status'] === Import::IN_PROGRESS) {

			return;

		} else {

			$isImportable = TRUE;

		}

		Import::model()->update($eImport, $update);

		if($isImportable === FALSE) {
			return;
		}

		Import::model()->beginTransaction();

		$eImport = ImportLib::getById($eImport['id']);

		$cOperation = new \Collection();

		$lines = array_slice(explode("\n", trim($eImport['content'])), 1);

		$cAccount = AccountLib::getAll();
		$cMethod = \payment\MethodLib::getByFarm($eFarm, FALSE, TRUE);
		$cJournalCode = \journal\JournalCodeLib::getAll();

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
			] = explode($eImport['delimiter'], $line);

			$ecritureDate = self::toDate($ecritureDate);

			if(FinancialYearLib::isDateInFinancialYear($ecritureDate, $eImport['financialYear']) === FALSE) {
				$update['errors'] = Import::DATES;
			}

			if(
				isset($eImport['rules']['comptes'][$compteNum]['account']['id']) and
				$cAccount->offsetExists($eImport['rules']['comptes'][$compteNum]['account']['id'])
			) {

				$eAccount = $cAccount->find(fn($e) => $e['id'] === $eImport['rules']['comptes'][$compteNum]['account']['id'])->first();

			} else {

				$eAccount = new Account();

			}

			if(
				isset($eImport['rules']['journaux'][$journalCode]['journalCode']['id']) and
				$cJournalCode->offsetExists($eImport['rules']['journaux'][$journalCode]['journalCode']['id'])
			) {

				$eJournalCode = $cJournalCode->find(fn($e) => $e['id'] === $eImport['rules']['journaux'][$journalCode]['journalCode']['id'])->first();

			} else {

				$eJournalCode = new \journal\JournalCode();

			}

			if(
				isset($eImport['rules']['paiements'][$modeRglt]['payment']['id']) and
				$cMethod->offsetExists($eImport['rules']['paiements'][$modeRglt]['payment']['id'])
			) {

				$eMethod = $cMethod->offsetGet($eImport['rules']['paiements'][$modeRglt]['payment']['id']);

			} else {

				$eMethod = new \payment\Method();

			}

			$eOperation = new \journal\Operation([
				'number' => $ecritureNum,
				'financialYear' => $eImport['financialYear'],
				'journalCode' => $eJournalCode,
				'account' => $eAccount,
				'accountLabel' => $compteLib,
				'date' => $ecritureDate,
				'description' => $ecritureLib,
				'document' => $pieceRef,
				'documentDate' => self::toDate($pieceDate),
				'amount' => $debit > 0 ? $debit : $credit,
				'type' => $debit > 0 ? \journal\Operation::DEBIT : \journal\Operation::CREDIT,
				'paymentDate' => $dateRglt ? self::toDate($dateRglt) : NULL,
				'paymentMethod' => $eMethod,
			]);
			$cOperation->append($eOperation);

		}

		if(isset($update['errors']) === FALSE) {
			\journal\Operation::model()->insert($cOperation);
		}

		Import::model()->update($eImport, $update);

		Import::model()->commit();

	}

	public static function getImports(): \Collection {

		return Import::model()
			->select(Import::getSelection())
			->sort(['createdAt' => SORT_DESC])
			->getCollection();

	}

}
