<?php
new Page(function($data) {

	$data->eFarm->validate('canManage');

	if($data->eFarm->usesAccounting() === FALSE) {
		throw new RedirectAction('/comptabilite/parametrer?farm='.$data->eFarm['id']);
	}

	$data->cJournalCode = \journal\JournalCodeLib::getAll();

})
	->get('/journal/livre-journal', function($data) {

		\user\ConnectionLib::checkLogged();

		$data->eThirdParty = get_exists('thirdParty')
			? account\ThirdPartyLib::getById(GET('thirdParty', 'int'))
			: NULL;

		$search = new Search([
			'date' => GET('date'),
			'accountLabel' => GET('accountLabel'),
			'description' => GET('description'),
			'type' => GET('type'),
			'document' => GET('document'),
			'thirdParty' => GET('thirdParty'),
			'asset' => GET('asset'),
			'paymentMethod' => GET('paymentMethod'),
			'hasDocument' => GET('hasDocument', '?int'),
			'needsAsset' => GET('needsAsset', '?int'),
			'periodStart' => GET('periodStart'),
			'periodEnd' => GET('periodEnd'),
			'minDate' => GET('periodStart'),
			'maxDate' => GET('periodEnd'),
			'hash' => GET('hash'),
		], GET('sort'));

		$search->set('cashflowFilter', GET('cashflowFilter', 'bool'));

		$hasSort = get_exists('sort') === TRUE;
		$data->search = clone $search;

		$data->eOperationRequested = new \journal\Operation();

		if(GET('operation')) {

			$data->eOperationRequested = \journal\OperationLib::getById(GET('operation'));

			if($data->eOperationRequested->notEmpty()) {
				$data->eFarm['eFinancialYear'] = $data->eOperationRequested['financialYear'];
			}

		}

		if(get_exists('financialYear') and GET('financialYear', 'int') === 0) { // On demande explicitement : pas de filtre sur l'exercice
		} else {
			// Ne pas ouvrir le bloc de recherche
			$search->set('financialYear', $data->eFarm['eFinancialYear']);
		}

		$data->eCashflow = \bank\CashflowLib::getById(GET('cashflow'));
		if($data->eCashflow->exists() === TRUE) {
			$search->set('cashflow', GET('cashflow'));
		}

		if($data->eOperationRequested->notEmpty() and !GET('code')) {

			$journalCode = NULL;
			$code = NULL;

		} else {

			$journalCode = GET('journalCode');

			if(in_array($journalCode, $data->cJournalCode->getIds()) === FALSE and $journalCode !== '-1') {
				$journalCode = NULL;
			}

			$code = GET('journalCode');
			if(in_array($code, ['vat-buy', 'vat-sell', \journal\JournalSetting::JOURNAL_CODE_BANK]) === FALSE) {
				$code = NULL;
			}

		}

		$search->set('journalCode', $journalCode);
		if($journalCode === '-1') {
			$data->search->set('journalCode', $journalCode);
		}
		$data->cOperation = new Collection();
		$data->operationsVat = [];
		$data->page = GET('page', 'int');

		if($code === \journal\JournalSetting::JOURNAL_CODE_BANK) {

			[$data->cOperation, $data->nOperationSearch, $data->nPage] = \journal\OperationLib::getAllForBankJournal(search: $search, page: $data->page, hasSort: $hasSort);

		} else if(in_array($journalCode, $data->cJournalCode->getIds()) or $code === NULL or $journalCode === -1) {

			[$data->cOperation, $data->nOperationSearch, $data->nPage] = \journal\OperationLib::getAllForJournal(search: $search, page: $data->page, hasSort: $hasSort);

		} else if(mb_substr($code, 0, 3) === 'vat') {

			// Journaux de TVA
			if($data->eFarm['eFinancialYear']['hasVat']) {

				$data->operationsVat = [
					'buy' => \journal\OperationLib::getAllForVatJournal('buy', $search, $hasSort),
					'sell' => \journal\OperationLib::getAllForVatJournal('sell', $search, $hasSort),
				];

			}

		}

		$data->cPaymentMethod = \payment\MethodLib::getByFarm($data->eFarm, NULL, NULL, NULL);
		$data->cJournalCode = \journal\JournalCodeLib::getAll();
		$data->cAccount = \account\AccountLib::getAll();

		$data->counts = \preaccounting\PreaccountingLib::countImports($data->eFarm, $data->eFarm['eFinancialYear']['startDate'], $data->eFarm['eFinancialYear']['endDate'], $data->search);

		throw new ViewAction($data);

	})
	->get('pdf', function($data) {

		$content = pdf\PdfLib::generate($data->eFarm, $data->eFarm['eFinancialYear'], \pdf\PdfElement::JOURNAL_INDEX);

		if($content === NULL) {
			throw new NotExistsAction();
		}

		$filename = journal\PdfUi::filenameJournal($data->eFarm).'.pdf';

		throw new PdfAction($content, $filename);
	});
?>
