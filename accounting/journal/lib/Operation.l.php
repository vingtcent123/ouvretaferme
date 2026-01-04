<?php
namespace journal;

class OperationLib extends OperationCrud {

	const MAX_BY_PAGE = 500;

	public static function getPropertiesCreate(): array {
		return ['account', 'accountLabel', 'date', 'description', 'document', 'documentDate', 'amount', 'type', 'vatRate', 'thirdParty', 'asset', 'hash'];
	}
	public static function getPropertiesUpdate(): array {
		return ['account', 'accountLabel', 'date', 'description', 'document', 'documentDate', 'amount', 'type', 'thirdParty', 'journalCode', 'vatRate'];
	}

	public static function countByFinancialYear(\account\FinancialYear $eFinancialYear): int {

		return Operation::model()
			->whereFinancialYear($eFinancialYear)
			->count();

	}

	public static function countByFinancialYears(\Collection $cFinancialYear): array {

		return Operation::model()
			->select(['financialYear', 'count' => new \Sql('COUNT(*)', 'int')])
			->whereFinancialYear('IN', $cFinancialYear->getIds())
			->group('financialYear')
			->getCollection(NULL, NULL, 'financialYear')
			->getArrayCopy();

	}
	public static function applyAssetCondition(): OperationModel {

		return Operation::model()
			->or(
				fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::ASSET_GENERAL_CLASS.'%'),
				fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::GRANT_ASSET_CLASS.'%'),
			)
			->whereAccountLabel('NOT LIKE', \account\AccountSetting::ASSET_AMORTIZATION_GENERAL_CLASS.'%')
			->whereAccountLabel('NOT LIKE', \account\AccountSetting::INVESTMENT_GRANT_AMORTIZATION_CLASS.'%');

	}

	public static function getByAsset(\asset\Asset $eAsset): \Collection {

		return Operation::model()
			->select(Operation::getSelection())
			->sort(['date' => SORT_DESC])
			->whereAsset($eAsset)
			->getCollection();

	}

	public static function getByIdsForAsset(array $ids): \Collection {

		return self::applyAssetCondition()
			->select(Operation::getSelection())
			->whereId('IN', $ids)
			->getCollection();

	}

	public static function countByOldDatesButNotNewDate(\account\FinancialYear $eFinancialYear, string $newStartDate, string $newEndDate): int {

		return Operation::model()
			->whereDate('BETWEEN', new \Sql(\account\FinancialYear::model()->format($eFinancialYear['startDate']).' AND '.\account\FinancialYear::model()->format($eFinancialYear['endDate'])))
			->whereDate('NOT BETWEEN', new \Sql(\account\FinancialYear::model()->format($newStartDate).' AND '.\account\FinancialYear::model()->format($newEndDate)))
			->count();

	}

	public static function applySearch(\Search $search = new \Search()): OperationModel {

		if($search->get('financialYear')->notEmpty()) {

			if($search->get('financialYear')['accountingType'] === \account\FinancialYear::ACCRUAL) {

				$model = Operation::model()
					->whereDate('>=', fn() => $search->get('financialYear')['startDate'], if: $search->get('financialYear')->notEmpty())
					->whereDate('<=', fn() => $search->get('financialYear')['endDate'], if: $search->get('financialYear')->notEmpty());

			} else {

				$model = Operation::model()
					->or(
						fn() => $this
							->wherePaymentDate('BETWEEN', new \Sql(\account\FinancialYear::model()->format($search->get('financialYear')['startDate']).' AND '.\account\FinancialYear::model()->format($search->get('financialYear')['endDate'])), if: $search->get('financialYear')->notEmpty()),
						fn() => $this
							->wherePaymentDate(NULL)
							->whereDate('BETWEEN', new \Sql(\account\FinancialYear::model()->format($search->get('financialYear')['startDate']).' AND '.\account\FinancialYear::model()->format($search->get('financialYear')['endDate'])), if: $search->get('financialYear')->notEmpty()),
					);

			}
		} else {

			$model = Operation::model();

		}

		if($search->get('cashflowFilter') === TRUE) {
			$model
				->join(OperationCashflow::model(), 'm1.id = m2.operation', 'LEFT')
				->where('m2.id IS NOT NULL');
		} else if($search->get('cashflowFilter') === FALSE) {
			$model
				->join(OperationCashflow::model(), 'm1.id = m2.operation', 'LEFT')
				->where('m2.id IS NULL');
		}

		if($search->has('cashflow') and $search->get('cashflow')->notEmpty()) {
			$model
				->join(OperationCashflow::model(), 'm1.id = m2.operation')
				->where('m2.cashflow = '.$search->get('cashflow')['id']);

		}

		if($search->get('journalCode')) {
			if($search->get('journalCode') === '-1') {
				$model->whereJournalCode(NULL);
			} else {
				$model->whereJournalCode('=', $search->get('journalCode'));
			}
		}

		if($search->get('hasDocument') !== NULL) {
			if($search->get('hasDocument') === 1) {
				$model->whereDocument('!=', NULL);
			} else {
				$model->whereDocument(NULL);
			}
		}

		if($search->get('needsAsset') !== NULL) {
			self::applyAssetCondition()->or(
				fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::ASSET_GENERAL_CLASS.'%'),
				fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::EQUIPMENT_GRANT_CLASS.'%'),
			)
				->whereAccountLabel('NOT LIKE', \account\AccountSetting::ASSET_AMORTIZATION_GENERAL_CLASS);
			if($search->get('needsAsset') === 0) {
				$model->whereAsset('!=', NULL);
			} else {
				$model->whereAsset(NULL);
			}
		}

		return $model
			->whereId('=', $search->get('id'), if: $search->get('id'))
			->whereHash('=', $search->get('hash'), if: $search->get('hash'))
			->whereDate('LIKE', '%'.$search->get('date').'%', if: $search->get('date'))
			->whereDate('>=', $search->get('minDate'), if: $search->get('minDate'))
			->whereDate('<=', $search->get('maxDate'), if: $search->get('maxDate'))
			->wherePaymentDate('LIKE', '%'.$search->get('paymentDate').'%', if: $search->get('paymentDate'))
			->wherePaymentMethod($search->get('paymentMethod'), if: $search->has('paymentMethod') and $search->get('paymentMethod')->notEmpty())
			->whereAccountLabel('LIKE', $search->get('accountLabel').'%', if: $search->get('accountLabel'))
			->where(fn() => 'accountLabel LIKE "'.join('%" OR accountLabel LIKE "', $search->get('accountLabels')).'"', if: $search->get('accountLabels'))
			->whereDescription('LIKE', '%'.$search->get('description').'%', if: $search->get('description'))
			->whereDocument('LIKE', '%'.$search->get('document').'%', if: $search->get('document'))
			->whereType($search->get('type'), if: $search->get('type'))
			->whereAsset($search->get('asset'), if: $search->has('asset') and $search->get('asset')->notEmpty())
			->whereThirdParty('=', $search->get('thirdParty'), if: $search->has('thirdParty') and $search->get('thirdParty')->notEmpty());

	}

	public static function getByThirdPartyAndOrderedByUsage(\account\ThirdParty $eThirdParty): \Collection {

		return \journal\Operation::model()
			->select(['account', 'count' => new \Sql('COUNT(*)')])
			->whereThirdParty($eThirdParty)
			->group('account')
			->sort(['count' => SORT_DESC])
			->getCollection(NULL, NULL, 'account');

	}
	public static function getOrderedByUsage(): \Collection {

		return \journal\Operation::model()
			->select(['account', 'count' => new \Sql('COUNT(*)')])
			->group('account')
			->sort(['count' => SORT_DESC])
			->getCollection(NULL, NULL, 'account');

	}

	public static function getByHashes(array $hashes): \Collection {

		return Operation::model()
			->select(Operation::getSelection() + [
				'cOperationCashflow' => OperationCashflowLib::delegateByOperation(),
				'asset' => \asset\Asset::getSelection(),
			])
			->whereHash('IN', $hashes)
			->sort(['id' => SORT_ASC])
			->getCollection(NULL, NULL, 'id');
	}

	public static function getByHash(string $hash): \Collection {

		return Operation::model()
			->select(Operation::getSelection() + [
				'cOperationCashflow' => OperationCashflowLib::delegateByOperation(),
				'asset' => \asset\Asset::getSelection(),
			])
			->whereHash($hash)
			->sort(['id' => SORT_ASC])
			->getCollection(NULL, NULL, 'id');
	}

	public static function getAllForBook(\Search $search = new \Search()): \Collection {

		return self::applySearch($search)
			->select(
				['id', 'date', 'document', 'description', 'type', 'amount']
				+ ['thirdParty' => ['name']]
				+ ['class' => new \Sql('SUBSTRING(IF(accountLabel IS NULL, m2.class, accountLabel), 1, 3)')]
				+ ['accountLabel' => new \Sql('IF(accountLabel IS NULL, RPAD(m2.class, 8, "0"), accountLabel)')]
				+ ['account' => ['description']]
			)
			->join(\account\Account::model(), 'm1.account = m2.id')
			->sort(['m1_accountLabel' => SORT_ASC, 'date' => SORT_ASC])
			->getCollection();

	}

	public static function getByThirdParty(\account\FinancialYear $eFinancialYear, string $type): \Collection {

		$search = new \Search(['financialYear' => $eFinancialYear]);
		if($type === 'customer') {
			$search->set('accountLabel', \account\AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS);
		} else {
			$search->set('accountLabel', \account\AccountSetting::THIRD_ACCOUNT_SUPPLIER_DEBT_CLASS);
		}

		return self::applySearch($search)
			->select([
				'accountLabel',
				'debit' => new \Sql('SUM(IF(type = "'.Operation::DEBIT.'", amount, 0))'),
				'credit' => new \Sql('SUM(IF(type = "'.Operation::CREDIT.'", amount, 0))'),
				'thirdParty' => ['id', 'name'],
			])
			->whereThirdParty('!=', NULL)
			->group(['accountLabel', 'thirdParty'])
			->getCollection();

	}

	public static function getAllForJournal(?int $page, \Search $search = new \Search(), bool $hasSort = FALSE): array {

		$eFinancialYear = $search->get('financialYear');
		$defaultOrder = ($eFinancialYear !== NULL and $eFinancialYear->isCashAccounting()) ? ['paymentDate' => SORT_ASC, 'date' => SORT_ASC, 'm1.id' => SORT_ASC] : ['date' => SORT_ASC, 'm1.id' => SORT_ASC];

		$selection = array_merge(Operation::getSelection(),
			['account' => ['class', 'description']],
			['thirdParty' => ['id', 'name']]
		);

		self::applySearch($search)
			->select($selection)
			->option('count')
			->sort($hasSort === TRUE ? $search->buildSort() : $defaultOrder);

		if($page === NULL) {
			$cOperation = Operation::model()->getCollection();
		} else {
			$cOperation = Operation::model()->getCollection($page * self::MAX_BY_PAGE, self::MAX_BY_PAGE);
		}

		$nOperation = Operation::model()->found();
		$nPage = ceil($nOperation / self::MAX_BY_PAGE);

		return [$cOperation, $nOperation, $nPage];

	}

	public static function countUnbalanced(\account\FinancialYear $eFinancialYear): int {

		$hashes = self::applySearch(new \Search(['financialYear' => $eFinancialYear]))
			->select(['hash', 'balance' => new \Sql('SUM(IF(type = "'.Operation::CREDIT.'", amount, -amount))', 'float')])
			->group('hash')
			->having('balance != 0.0')
			->getCollection()
			->getColumn('hash');

		return count(array_unique($hashes));

	}

	public static function getUnbalanced(\Search $search): array {

		$eFinancialYear = $search->get('financialYear');
		$defaultOrder = $eFinancialYear->isCashAccounting() ? ['paymentDate' => SORT_ASC, 'date' => SORT_ASC, 'm1.id' => SORT_ASC] : ['date' => SORT_ASC, 'm1.id' => SORT_ASC];

		// Récupérer les hash des opérations déséquilibrées
		$hashes = self::applySearch(new \Search(['financialYear' => $eFinancialYear]))
			->select(['hash', 'balance' => new \Sql('SUM(IF(type = "'.Operation::CREDIT.'", amount, -amount))', 'float')])
			->group('hash')
			->having('balance != 0.0')
			->getCollection()
			->getColumn('hash');

		$cOperation = Operation::model()
			->select(
			 Operation::getSelection()
			 + ['account' => ['class', 'description']]
			 + ['thirdParty' => ['id', 'name']]
			)
			->sort( $defaultOrder)
			->option('count')
			->whereHash('IN', $hashes)
			->getCollection();

		$nOperation = Operation::model()->found();

		return [$cOperation, $nOperation];

	}

	/**
	 * Le journal de banque doit ressortir toutes les contreparties au compte AccountSetting\BANK_ACCOUNT_CLASS
	 */
	public static function getAllForBankJournal(?int $page, \Search $search = new \Search(), bool $hasSort = FALSE): array {

		$eFinancialYear = $search->get('financialYear');
		$defaultOrder = $eFinancialYear->isCashAccounting() ? ['paymentDate' => SORT_ASC, 'date' => SORT_ASC, 'm1.id' => SORT_ASC] : ['date' => SORT_ASC, 'm1.id' => SORT_ASC];

		$searchFiltered = new \Search($search->getFiltered(['journalCode']));

		$hashes = self::applySearch($searchFiltered)
			->select([
				'hash' => new \Sql('DISTINCT(hash)'),
			])
			->whereAccountLabel('LIKE', \account\AccountSetting::BANK_ACCOUNT_CLASS.'%')
			->getCollection()
			->getColumn('hash');

		self::applySearch($searchFiltered)
			->select(
			 Operation::getSelection()
			 + ['account' => ['class', 'description']]
			 + ['thirdParty' => ['id', 'name']]
			)
			->sort($hasSort === TRUE ? $search->buildSort() : $defaultOrder)
			->option('count')
			->whereHash('IN', $hashes)
			->whereAccountLabel('NOT LIKE', \account\AccountSetting::BANK_ACCOUNT_CLASS.'%');

		if($page === NULL) {
			$cOperation = Operation::model()->getCollection();
		} else {
			$cOperation = Operation::model()->getCollection($page * self::MAX_BY_PAGE, self::MAX_BY_PAGE);
		}

		$nOperation = Operation::model()->found();
		$nPage = ceil($nOperation / self::MAX_BY_PAGE);

		return [$cOperation, $nOperation, $nPage];

	}

	public static function getAllChargesForClosing(\Search $search): \Collection {

		return self::applySearch($search)
			->select(
				Operation::getSelection()
				+ ['operation' => [
					'id', 'account', 'accountLabel', 'document', 'type',
					'thirdParty' => ['id', 'name'],
					'description', 'amount', 'vatRate', 'date',
					'financialYear',
				]]
				+ ['account' => ['class', 'description']]
				+ ['thirdParty' => ['id', 'name']]
				+ ['month' => new \Sql('SUBSTRING(date, 1, 7)')]
			)
			->sort(['accountLabel' => SORT_ASC, 'date' => SORT_ASC, 'm1.id' => SORT_ASC])
			->or(
				fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::CHARGE_ACCOUNT_CLASS.'%'),
				fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::PRODUCT_ACCOUNT_CLASS.'%'),
			)
			->getCollection();
	}

	public static function getAllForVatJournal(string $type, \Search $search = new \Search(), bool $hasSort = FALSE,
		?array $index = ['accountLabel', 'month', NULL]): \Collection {

		// Si c'est le journal des achats il faut tout afficher en positif
		if($type === 'buy') {
			$amount = new \Sql('IF(type = "credit", -1 * amount, amount)');
		} else {
			$amount = new \Sql('IF(type = "debit", -1 * amount, amount)');
		}

		return self::applySearch($search)
			->select(
				array_merge(Operation::getSelection(),
					['operation' => [
						'id', 'account', 'accountLabel', 'document', 'type',
						'thirdParty' => ['id', 'name'],
						'description', 'vatRate', 'date',
						'financialYear',
						'cOperationCashflow' => OperationCashflowLib::delegateByOperation(),
						'amount' => $amount,
					]],
					['account' => ['class', 'description']],
					['thirdParty' => ['id', 'name']],
					['month' => new \Sql('SUBSTRING(date, 1, 7)')],
					['amount' => $amount]
				),
			)
			->sort($hasSort === TRUE ? $search->buildSort() : ['accountLabel' => SORT_ASC, 'date' => SORT_ASC, 'm1.id' => SORT_ASC])
			->whereAccountLabel('LIKE', ($type === 'buy' ? \account\AccountSetting::VAT_BUY_CLASS_PREFIX : \account\AccountSetting::VAT_SELL_CLASS_PREFIX).'%')
			->where(new \Sql('operation IS NOT NULL'))
			->getCollection(NULL, NULL, $index);

	}

	public static function updateAccountLabels(\bank\BankAccount $eBankAccount): bool {

		$eOperation = ['accountLabel' => $eBankAccount['label'], 'updatedAt' => new \Sql('NOW()')];
		$eFinancialYear = \account\FinancialYearLib::selectDefaultFinancialYear();

		if($eFinancialYear['status'] === \account\FinancialYear::CLOSE) {
			return TRUE;
		}

		$cOperation = OperationCashflow::model()
			->select('operation')
			->join(\bank\Cashflow::model(), 'm1.cashflow = m2.id')
			->where('m2.account = '.$eBankAccount['id'])
			->getColumn('operation');

		if($cOperation->empty()) {
			return TRUE;
		}

		Operation::model()
			->select(['accountLabel', 'updatedAt'])
			// Liée aux cashflow de ce compte bancaire
			->where('m1.id IN ('.join(', ', $cOperation->getIds()).')')
			// Type banque
			->join(\account\Account::model(), 'm1.account = m2.id')
			->where('m2.class = '.\account\AccountSetting::BANK_ACCOUNT_CLASS)
			// De l'exercice comptable courant
			->where('m1.date >= "'.$eFinancialYear['startDate'].'"')
			->where('m1.date <= "'.$eFinancialYear['endDate'].'"')
			->update($eOperation);

		return TRUE;
	}

	public static function update(Operation $e, array $properties): void {

		$e['updatedAt'] = new \Sql('NOW()');
		$properties[] = 'updatedAt';
		if(in_array('document', $properties) === TRUE) {
			$properties[] = 'documentDate';
			$e['documentDate'] =  new \Sql('NOW()');
		}
		parent::update($e, $properties);

		// Quick document update
		if(in_array('document', $properties) === TRUE) {
			// On rattache cette pièce comptable aux cashflows + aux opérations liées
			if($e['cOperationCashflow']->notEmpty()) {
				$cCashflow = $e['cOperationCashflow']->getColumnCollection('cashflow');
				\bank\Cashflow::model()
					->select('document')
					->whereId('IN', $cCashflow->getIds())
					->update(['document' => $e['document']]);
				Operation::model()
					->select('document', 'documentDate')
					->whereId('IN', $e['cOperationCashflow']->getColumnCollection('operation')->getIds())
					->update(['document' => $e['document'], 'documentDate' => new \Sql('NOW()')]);
			}
		}

		// à répercuter sur les opérations liées
		if(
			in_array('journalCode', $properties) or
			in_array('paymentMethod', $properties) or
			in_array('document', $properties)
		) {
			Operation::model()
				->whereOperation($e)
				->update(['journalCode' => $e['journalCode'], 'paymentMethod' => $e['paymentMethod'], 'document' => $e['document']]);
		}

		\account\LogLib::save('update', 'Operation', ['id' => $e['id'], 'properties' => $properties]);

	}

	/**
	 * Generates a 19-characters hash
	 *
	 * @param string $end Optional end (from 0 to 6 characters)
	 * @return string
	 */
	public static function generateHash(string $end = ''): string {

		$endLength = strlen($end);

		if($endLength > 6) {
			throw new \Exception('Parameter \'end\' is too long (max length expected: 6, received: '.$end.')');
		}

		$hash = uniqid(); /* 13 caracters */

		$crcLength = 6 - $endLength;

		if($crcLength > 0) {
			$crc = sprintf('%0'.$crcLength.'x', mt_rand(0, 16 ** $crcLength - 1)); /* 6 caracters */
		} else {
			$crc = '';
		}

		return $hash.$crc.$end; /* 19 caracters */
	}


	public static function prepareOperations(\farm\Farm $eFarm, array $input, Operation $eOperationDefault, string $for = 'create', \bank\Cashflow $eCashflow = new \bank\Cashflow()): \Collection {

		$accounts = var_filter($input['account'] ?? [], 'array');
		$vatValues = var_filter($input['vatValue'] ?? [], 'array');
		$invoiceFile = var_filter($input['invoiceFile'] ?? NULL);
		$ePaymentMethodInvoice = var_filter($input['invoice']['paymentMethod'] ?? NULL, 'payment\Method');
		$eFinancialYear = \account\FinancialYearLib::getById($input['financialYear'] ?? NULL);
		$isFromCashflow = $eCashflow->notEmpty();
		$eThirdParty = new \account\ThirdParty();
		$thirdPartys = [];

		$totalAmount = 0; // Par défaut : débit - crédit

		$fw = new \FailWatch();

		if($eFinancialYear->acceptUpdate() === FALSE) {
			\Fail::log('Operation::FinancialYear.notUpdatable');
			return new \Collection();
		}

		$cAccounts = \account\AccountLib::getByIdsWithVatAccount($accounts);

		$cOperation = new \Collection();
		$cOperationCashflow = new \Collection();
		$properties = [
			'account', 'accountLabel',
			'description', 'amount', 'type', 'document', 'vatRate',
			'asset',
			'journalCode',
		];
		if($eFinancialYear['hasVat']) {
			$properties[] = 'vat';
		}

		$eOperationDefault['thirdParty'] = NULL;
		$eOperationDefault['financialYear'] = $eFinancialYear;

		if($for === 'create') {

			$properties = array_merge($properties, ['date']);
			if($isFromCashflow === FALSE) {
				$properties = array_merge($properties, ['paymentDate', 'paymentMethod']);
			}

		}

		if($for === 'update') {
			if(isset($input['id']) === FALSE) {
				throw new \NotExpectedAction('no ids for the update');
			}
			$cOperationOriginByIds = self::getByIds($input['id']);
			if ($cOperationOriginByIds->empty()) {
				throw new \NotExpectedAction('no ids for the update');
			}
		} else {
			$cOperationOriginByIds = new \Collection();
		}

		if($for === 'update' and ($input['hash'] ?? NULL) !== NULL) {

			$cOperationOrigin = OperationLib::getByHash($input['hash']);

		}

		$hash = self::generateHash().($eCashflow->empty() ? 'w' : 'c');

		if($for === 'create') {
			$eOperationDefault['hash'] = $hash;
		} else {
			$eOperationDefault['hash'] = $cOperationOrigin->first()['hash'];
		}

		foreach($accounts as $index => $account) {

			// Si on a déjà l'opération de départ, on part de celle-ci et on la modifie.
			if(isset($input['id'][$index]) and $cOperationOriginByIds->find(fn($e) => $e['id'] === (int)$input['id'][$index])->notEmpty()) {

				$eOperation = $cOperationOriginByIds->find(fn($e) => $e['id'] === (int)$input['id'][$index])->first();
				foreach(array_keys($eOperationDefault->getArrayCopy()) as $field) {
					$eOperation[$field] = $eOperationDefault[$field];
				}

			} else {

				$eOperation = clone $eOperationDefault;

			}

			$eOperation['index'] = $index;
			$eOperation['financialYear'] = $eFinancialYear;

			$input['invoiceFile'] = [$index => $invoiceFile];
			$input['accountLabel'][$index] = \account\AccountLabelLib::pad($input['accountLabel'][$index]);

			$eOperation->buildIndex($properties, $input, $index);

			if($isFromCashflow and $for === 'create') {
				$eOperation->build(['paymentDate', 'paymentMethod'], $input);
			}

			if($for === 'update') {

				$eOperationOriginal = $cOperationOrigin->offsetGet($input['id'][$index]);
				$eOperation['date'] = $eOperationOriginal['date'];
			}

			$fw->validate();

			$eOperation['amount'] = abs($eOperation['amount']);

			// Date de la pièce justificative : date de l'écriture
			if($eOperation['document'] !== NULL) {
				$eOperation['documentDate'] = $eOperation['date'];
			} else {
				$eOperation['documentDate'] = NULL;
			}

			$thirdParty = $input['thirdParty'][$index] ?? null;

			if($thirdParty !== null) {

				if($eThirdParty->empty() or $eThirdParty['id'] !== (int)$thirdParty) {
					$eThirdParty = \account\ThirdPartyLib::getById($thirdParty);
				}

				$eOperation['thirdParty'] = $eThirdParty;
				$thirdPartys[] = $eThirdParty['id'];

				// Vérifier si on doit enregistrer des données supplémentaires (issues de la facture pdf)
				if($eOperation['thirdParty']['vatNumber'] === NULL and ($input['thirdPartyVatNumber'][$index] ?? NULL) !== NULL) {
					$eOperation['thirdParty']['vatNumber'] = rtrim(trim($input['thirdPartyVatNumber'][$index]));
				}

				if(($input['thirdPartyName'][$index] ?? NULL) !== NULL and ($eOperation['thirdParty']['names'] === NULL or mb_strpos($eOperation['thirdParty']['names'], $input['thirdPartyName'][$index]) !== FALSE)) {
					if($eOperation['thirdParty']['names'] === NULL) {
						$eOperation['thirdParty']['names'] = rtrim(trim($input['thirdPartyName'][$index]));
					} else {
						$eOperation['thirdParty']['names'] .= '|'.rtrim(trim($input['thirdPartyName'][$index]));
					}
				}

				// Enregistre les termes du libellé de banque pour améliorer les prédictions
				if($isFromCashflow === TRUE) {

					$eOperation['thirdParty'] = \account\ThirdPartyLib::recalculateMemos($eCashflow, $eOperation['thirdParty']);

				}

				\account\ThirdPartyLib::update($eOperation['thirdParty'], ['vatNumber', 'names', 'memos']);

			} else {

				$eOperation['thirdParty'] = new \account\ThirdParty();

			}

			// Ce type d'écriture a un compte de TVA correspondant
			$eAccount = $cAccounts[$account] ?? new \account\Account();
			$vatValue = var_filter($vatValues[$index] ?? NULL, 'float', 0.0);
			$hasVatAccount = (
				$eFinancialYear['hasVat'] and
				$eAccount->exists() and
				$eAccount['vatAccount']->exists() and
				(
					$vatValue !== 0.0 or
					// Cas où on enregistre quand même une entrée de TVA à 0% : Si c'est explicitement indiqué dans eAccount.
					$eAccount['vatRate'] === 0.0
				)
			);

			if($hasVatAccount === TRUE) {
				$eOperation['vatAccount'] = $eAccount['vatAccount'];
			}

			$fw->validate();

			if($eOperation['journalCode']->empty()) {
					$eOperation['journalCode'] = $cAccounts->find(fn($e) => $e['id'] === $eOperation['account']['id'])->first()['journalCode'];
			}

			foreach(['document', 'documentDate', 'thirdParty', 'journalCode'] + ($for === 'create' ? ['date', 'paymentMethod'] : []) as $property) {
				if(($eOperationDefault[$property] ?? NULL) === NULL) {
					$eOperationDefault[$property] = $eOperation[$property];
				}
			}

			if($for === 'create') {

				\journal\Operation::model()->insert($eOperation);

				$totalAmount += $eOperation['type'] === Operation::DEBIT ? $eOperation['amount'] : -1 * $eOperation['amount'];

			} else {

				$fields = array_intersect(OperationLib::getPropertiesUpdate(), array_keys($eOperation->getArrayCopy()));

				Operation::model()
					->select($fields)
					->update($eOperation);

			}

			$cOperation->append($eOperation);

			if($for === 'create' and $isFromCashflow) {
				$cOperationCashflow->append(new OperationCashflow([
					'operation' => $eOperation,
					'cashflow' => $eCashflow,
					'hash' => $hash,
					'amount' => min($eOperation['amount'], abs($eCashflow['amount']))
				]));
			}

			// Ajout de l'entrée de compte de TVA correspondante
			if($hasVatAccount === TRUE) {

				$forVat = $for;

				if($for === 'update') {

					$eOperationVatOrigin = $cOperationOrigin->find(fn($e) => $e['id'] === (int)(POST('vatOperation', 'array')[$index] ?? -1))->first();

					// L'écriture n'existe pas !
					if($eOperationVatOrigin === NULL or $eOperationVatOrigin->empty()) {

						$defaultValues = $eOperation->getArrayCopy();
						$forVat = 'create';

					} else {

						$defaultValues = $cOperationOrigin->find(fn($e) => $e['id'] === (int)(POST('vatOperation', 'array')[$index]))->first()->getArrayCopy();

					}

					// Certains champs doivent être automatiquement recopiés de l'originale à l'écriture de TVA
					if(isset($defaultValues['operation']['id'])) {

						$eOperationExcludingVat = $cOperation->find(fn($e) => $e['id'] === $defaultValues['operation']['id'])->first();

						foreach(['description'] as $fieldCopy) {
							$defaultValues[$fieldCopy] = $eOperationExcludingVat[$fieldCopy];
						}

					}

				} else {

					$defaultValues = $isFromCashflow === TRUE
						? [
							'date' => $eCashflow['date'],
							'description' => $eOperation['description'] ?? $eCashflow['memo'],
							'cashflow' => $eCashflow,
							'paymentMethod' => $eOperation['paymentMethod'],
							'hash' => $hash,
						]
						: $eOperation->getArrayCopy();

				}

				// Fait déjà l'ajout dans OperationCashflow
				$eOperationVat = \journal\OperationLib::createVatOperation(
					$eOperation,
					$eAccount,
					$input['vatValue'][$index],
					defaultValues: $defaultValues,
					eCashflow: $eCashflow,
					for: $forVat,
				);

				$cOperation->append($eOperationVat);

				$totalAmount += $eOperationVat['type'] === Operation::DEBIT ? $eOperationVat['amount'] : -1 * $eOperationVat['amount'];

			} elseif($eOperation->exists()) {

				// S'il y avait une opération de TVA => il faut la supprimer
				Operation::model()
					->whereAccountLabel('LIKE', \account\AccountSetting::VAT_CLASS.'%')
					->whereOperation($eOperation)
					->delete();

			}

			// Création de l'opération d'origine
			// Gestion des acomptes (409x et 419x) qui doivent être enregistrés TTC + une contrepartie 44581 pour la TVA

			if(
				\account\AccountLabelLib::isFromClass($eOperation['accountLabel'], \account\AccountSetting::THIRD_ACCOUNT_SUPPLIER_DEPOSIT_CLASS) or
				\account\AccountLabelLib::isFromClass($eOperation['accountLabel'], \account\AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEPOSIT_CLASS)
			) {

				$eOperation['amount'] += $eOperationVat['amount'];

				Operation::model()->update($eOperation, ['amount' => $eOperation['amount']]);

				$eAccountVatRegul = \account\AccountLib::getByClass(\account\AccountSetting::VAT_DEPOSIT);

				// Créer l'écriture de TVA de régul
				$eOperationVatRegul = new Operation($eOperationVat->getArrayCopy());
				unset($eOperationVatRegul['id']);
				$eOperationVatRegul['type'] = $eOperation['type'] === Operation::DEBIT ? Operation::CREDIT : Operation::DEBIT;
				$eOperationVatRegul['account'] = $eAccountVatRegul;
				$eOperationVatRegul['accountLabel'] = \account\AccountLabelLib::pad($eAccountVatRegul['class']);

				Operation::model()->insert($eOperationVatRegul);
				$cOperation->append($eOperationVatRegul);

				if($for === 'create' and $isFromCashflow) {
					$cOperationCashflow->append(new OperationCashflow([
						'operation' => $eOperationVatRegul,
						'cashflow' => $eCashflow,
						'hash' => $hash,
						'amount' => min($eOperationVatRegul['amount'], abs($eCashflow['amount']))
					]));
				}
			}

		}

		// Liste des opérations AVANT d'ajouter l'opération du compte de tiers
		$cOperationWithoutThirdParties = new \Collection();
		foreach($cOperation as $eOperation) {
			$cOperationWithoutThirdParties->append(clone $eOperation);
		}

		$thirdPartys = array_unique($thirdPartys);

		if(count($thirdPartys) === 1 and $eThirdParty->notEmpty()) {

			// En cas de comptabilité à l'engagement : création de l'entrée en 401 ou 411 correspondante
			$eOperationThirdParty = self::createThirdPartyOperation($eFinancialYear, $totalAmount, $hash, $cOperation, $eThirdParty);

			if($eOperationThirdParty->notEmpty()) {

				if($for === 'update') {

					$eOperationThirdPartyDb = $cOperation->find(fn($e) => $e['account']['id'] === $eOperationThirdParty['account']['id']);
					$eOperationThirdParty['id'] = $eOperationThirdPartyDb['id'];

					Operation::model()
						->select(array_intersect(OperationLib::getPropertiesUpdate(), array_keys($eOperation->getArrayCopy())))
						->update($eOperationVat);

				}

				if($for === 'create' and $isFromCashflow) {
					$cOperationCashflow->append(new OperationCashflow([
						'operation' => $eOperationThirdParty,
						'cashflow' => $eCashflow,
						'hash' => $hash,
						'amount' => min($eOperationThirdParty['amount'], abs($eCashflow['amount']))
					]));
				}

				$cOperation->append($eOperationThirdParty);

			}

		}

		// Ajout de la transaction sur le numéro de compte bancaire 512 (seulement pour une création)
		if($for === 'create' and $isFromCashflow === TRUE) {

			// Si toutes les écritures sont sur le même document, on utilise aussi celui-ci pour l'opération bancaire;
			$documents = $cOperation->getColumn('document');
			$uniqueDocuments = array_unique($documents);
			if(count($uniqueDocuments) === 1 and count($documents) === $cOperation->count()) {
				$document = first($uniqueDocuments);
			} else {
				$document = NULL;
			}

			$eOperationDefault['hash'] = $hash;

			// Crée automatiquement l'operationCashflow correspondante
			$eOperationBank = \journal\OperationLib::createBankOperationFromCashflow(
				$eCashflow,
				$eOperationDefault,
				$document,
			);
			$cOperation->append($eOperationBank);

			// En cas de comptabilité à l'engagement : création de l'entrée en 401 ou 411 correspondante
			if(count($thirdPartys) === 1 and $eThirdParty->notEmpty()) {

				$eOperationThirdParty = self::createThirdPartyOperation($eFinancialYear, $eCashflow['amount'], $hash, $cOperationWithoutThirdParties, $eThirdParty);

				if($eOperationThirdParty->notEmpty()) {
					$cOperation->append($eOperationThirdParty);
				}

			}

		}

		if(($eOperationDefault['invoice'] ?? NULL) !== NULL and $eOperationDefault['invoice']->exists() and $ePaymentMethodInvoice->exists()) {

			$ePaymentMethod = \payment\MethodLib::getById($ePaymentMethodInvoice['id']);

			if($ePaymentMethod['use']->value(\payment\Method::SELLING) and ($ePaymentMethod['farm']->exists() === FALSE or $ePaymentMethod['farm']->is($eFarm))) {

				\selling\Invoice::model()->update($eOperationDefault['invoice'], ['paymentStatus' => \selling\Invoice::PAID, 'paymentMethod' => $ePaymentMethod]);

			}

		}

		if($cOperationCashflow->notEmpty()) {
			OperationCashflow::model()->insert($cOperationCashflow);
		}

		if($fw->ko()) {
			return new \Collection();
		}

		return $cOperation;

	}

	private static function createThirdPartyOperation(\account\FinancialYear $eFinancialYear, float $amount, string $hash, \Collection $cOperation, \account\ThirdParty $eThirdParty): Operation {

		if($amount === 0.0 or $eFinancialYear->isCashAccounting()) {
			return new Operation();
		}

		// On ne doit avoir qu'un seul type (charge ou produit) d'opération, pas les 2 en même temps
		$hasCharge = FALSE;
		$hasProduit = FALSE;
		$hasThirdPartyOperation = FALSE;
		foreach($cOperation as $eOperation) {

			if(\account\AccountLabelLib::isChargeClass($eOperation['accountLabel'])) {
				$hasCharge = TRUE;
			} else if(\account\AccountLabelLib::isProductClass($eOperation['accountLabel'])) {
				$hasProduit = TRUE;
			}

			if(
				\account\AccountLabelLib::isFromClass(\account\AccountSetting::THIRD_ACCOUNT_SUPPLIER_DEBT_CLASS, $eOperation['accountLabel']) or
				\account\AccountLabelLib::isFromClass(\account\AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS, $eOperation['accountLabel'])
			) {
				$hasThirdPartyOperation = TRUE;
			}
		}

		// Le mix engagement / trésorerie n'est effectué que pour les opérations avec les clients
		if($hasCharge) {
			return new Operation();
		}

		if($hasCharge and $hasProduit) {
			\Fail::log('Operation::typeProduitCharge.inconsistent');
			return new Operation();
		}

		// Si dans le lot il y a déjà une opération en 401 ou 411 => On ne crée pas d'opération Third Party
		if($hasThirdPartyOperation) {
			return new Operation();
		}

		// Opération avec un fournisseur
		if($hasCharge) {

			$description = new \account\ThirdPartyUi()->getOperationDescription($eThirdParty, 'supplier');
			$eAccountThirdParty = \account\AccountLib::getByClass(\account\AccountSetting::THIRD_ACCOUNT_SUPPLIER_DEBT_CLASS);

			if($eThirdParty['supplierAccountLabel'] === NULL) {

				$accountLabel = \account\ThirdPartyLib::getNextThirdPartyAccountLabel('supplierAccountLabel', \account\AccountSetting::THIRD_ACCOUNT_SUPPLIER_DEBT_CLASS);
				$eThirdParty['supplierAccountLabel'] = $accountLabel;
				\account\ThirdPartyLib::update($eThirdParty, ['supplierAccountLabel']);

			} else {

				$accountLabel = $eThirdParty['supplierAccountLabel'];

			}

		} else if($hasProduit) {

			$description = new \account\ThirdPartyUi()->getOperationDescription($eThirdParty, 'client');
			$eAccountThirdParty = \account\AccountLib::getByClass(\account\AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS);

			if($eThirdParty['clientAccountLabel'] === NULL) {

				$accountLabel = \account\ThirdPartyLib::getNextThirdPartyAccountLabel('clientAccountLabel', \account\AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS);
				$eThirdParty['clientAccountLabel'] = $accountLabel;
				\account\ThirdPartyLib::update($eThirdParty, ['clientAccountLabel']);

			} else {

				$accountLabel = $eThirdParty['clientAccountLabel'];

			}
		} else {
			return new Operation();
		}

		$eOperationThirdParty = new Operation(
			[
				'thirdParty' => $eThirdParty,
				'date' => $eOperation['date'],
				'document' => $eOperation['document'],
				'documentDate' => $eOperation['documentDate'],
				'amount' => abs($amount),
				'account' => $eAccountThirdParty,
				'type' => $amount > 0 ? Operation::CREDIT : Operation::DEBIT,
				'accountLabel' => $accountLabel,
				'description' => $description,
				'financialYear' => $eFinancialYear,
				'hash' => $hash,
				'journalCode' => $eOperation['journalCode'],
				'paymentDate' => $eOperation['paymentDate'],
			]
		);

		\journal\Operation::model()->insert($eOperationThirdParty);

		return $eOperationThirdParty;

	}

	public static function createVatOperation(Operation $eOperationLinked, \account\Account $eAccount, float $vatValue, array $defaultValues, \bank\Cashflow $eCashflow, string $for = 'create'): Operation {

		$values = [
			...$defaultValues,
			'account' => $eAccount['vatAccount']['id'] ?? NULL,
			'accountLabel' => \account\AccountLabelLib::pad($eAccount['vatAccount']['class']),
			'document' => $eOperationLinked['document'],
			'documentDate' => $eOperationLinked['documentDate'],
			'thirdParty' => $eOperationLinked['thirdParty']['id'] ?? NULL,
			'type' => $eOperationLinked['type'],
			'amount' => abs($vatValue),
			'financialYear' => $eOperationLinked['financialYear']['id'],
			'hash' => $eOperationLinked['hash'],
			'journalCode' => $eOperationLinked['journalCode']['id'] ?? NULL,
		];

		if($for === 'create') {
			$values['date'] = $eOperationLinked['date'];
			$values['paymentDate'] = $eOperationLinked['paymentDate'];
			$values['paymentMethod'] = $eOperationLinked['paymentMethod']['id'] ?? NULL;
		}

		$eOperationVat = new Operation();

		$fw = new \FailWatch();

		$eOperationVat->build(
			array_merge([
				'financialYear',
				'account', 'accountLabel', 'description', 'document', 'documentDate',
				'thirdParty', 'type', 'amount', 'operation',
				'hash', 'journalCode', // On prend le journalCode de l'opération d'origine
			], ($for === 'create' ? ['date', 'paymentDate', 'paymentMethod'] : [])),
			$values,
			new \Properties($for),
		);

		$eOperationVat['operation'] = $eOperationLinked;

		$fw->validate();

		if($for === 'create') {

			Operation::model()->insert($eOperationVat);

		} else if($for === 'update' and isset($defaultValues['id'])) {

			Operation::model()
				->select(array_intersect(OperationLib::getPropertiesUpdate(), array_keys($eOperationVat->getArrayCopy())))
				->whereId($defaultValues['id'])
				->update($eOperationVat);
			$eOperationVat = Operation::model()
				->select(Operation::getSelection())
				->whereId($defaultValues['id'])
				->get();

		}

		if($eCashflow->notEmpty()) {

			if($for === 'create') {

				$eOperationCashflow = new OperationCashflow([
					'operation' => $eOperationVat,
					'cashflow' => $eCashflow,
					'amount' => min($eOperationVat['amount'], abs($eCashflow['amount'])),
				]);

				OperationCashflow::model()->insert($eOperationCashflow);

			}

		}

		return $eOperationVat;

	}

	public static function delete(Operation $e): void {

		\journal\Operation::model()->beginTransaction();

		$e->expects(['id', 'asset']);

		// Deletes related operations (like assets... or VAT)
		if($e['asset']->exists() === TRUE) {
			\asset\AssetLib::deleteByIds([$e['asset']['id']]);
		}

		// Opérations liées à celle-ci
		Operation::model()
			->whereOperation($e)
			->delete();

		parent::delete($e);

		// Suppression de toutes les opérations liées par le hash
		$cOperation = Operation::model()
			->select('id')
			->whereHash($e['hash'])
			->getCollection();

		Operation::model()->whereHash($e['hash'])->delete();

		// Si l'opération est issue d'un import en compta => supprimer le lien
		if($e->isFromImport()) {

			switch($e->importType()) {

				case JournalSetting::HASH_LETTER_IMPORT_MARKET:
				case JournalSetting::HASH_LETTER_IMPORT_SALE:
					\selling\Sale::model()->whereAccountingHash($e['hash'])->update(['accountingHash' => NULL]);
					break;

				case JournalSetting::HASH_LETTER_IMPORT_INVOICE:

					\selling\Sale::model()->whereAccountingHash($e['hash'])->update(['accountingHash' => NULL]);

					$eInvoice = \selling\Invoice::model()->select(\selling\Invoice::getSelection())->whereAccountingHash($e['hash'])->get();

					if($eInvoice->notEmpty()) {

						$eInvoice['cashflow'] = new \bank\Cashflow();
						$eInvoice['accountingHash'] = NULL;
						$eInvoice['readyForAccounting'] = FALSE;
						$eInvoice['accountingDifference'] = NULL;

						\selling\Invoice::model()->whereAccountingHash($e['hash'])->update($eInvoice->extracts(['cashflow', 'accountingHash', 'readyForAccounting']));

						\preaccounting\InvoiceLib::recalculateReadyForAccounting($eInvoice, new \bank\Cashflow());
					}

					break;
			}

		}

		OperationCashflow::model()
			->whereOperation('IN', $cOperation->getIds())
			->delete();

		\journal\Operation::model()->commit();

		\account\LogLib::save('delete', 'Operation', ['id' => $e['id']]);

	}

	private static function addOpenFinancialYearCondition(): OperationModel {

		$cFinancialYear = \account\FinancialYearLib::getOpenFinancialYears();
		$dateConditions = [];
		foreach($cFinancialYear as $eFinancialYear) {
			$dateConditions[] = 'date BETWEEN "'.$eFinancialYear['startDate'].'" AND "'.$eFinancialYear['endDate'].'"';
		}

		return Operation::model()->where(join(' OR ', $dateConditions), if: empty($dateConditions) === FALSE);

	}

	public static function countByCashflow(\bank\Cashflow $eCashflow): int {

		return OperationCashflow::model()
			->whereCashflow($eCashflow)
			->count();

	}

	public static function getForAttachQuery(string $query, \account\ThirdParty $eThirdParty, array $excludedOperationIds, array $excludedPrefix): \Collection {

		$selection = Operation::getSelection(FALSE);
		if($eThirdParty->notEmpty()) {
			$selection['isThirdParty'] = new \Sql('IF(thirdParty = '.$eThirdParty['id'].', 1, 0)', 'bool');
			$sort = ['m1_isThirdParty' => SORT_DESC];
		} else {
			$selection['isThirdParty'] = new \Sql('0');
			$sort = ['m1_isThirdParty' => SORT_DESC];
		}

		if($query !== '') {

			$keywords = [];

			$query = trim(preg_replace('/[+\-><\(\)~*\"@]+/', ' ', $query));

			foreach(preg_split('/\s+/', $query) as $word) {
				$keywords[] = '*'.$word.'*';
			}

			$match = 'MATCH(accountLabel, description, document) AGAINST ('.Operation::model()->format(implode(' ', $keywords)).' IN BOOLEAN MODE)';

			Operation::model()->where($match.' > 0');

		}

		if(count($excludedOperationIds) > 0) {

			$hashToExclude = Operation::model()
				->select('hash')
				->whereId('IN', $excludedOperationIds)
				->getCollection()
				->getColumn('hash');

		} else {

			$hashToExclude = [];

		}

		$excludedPrefix = array_filter($excludedPrefix, fn($val) => $val);
		if(count($excludedPrefix) > 0) {
			foreach($excludedPrefix as $prefix) {
				Operation::model()->where(new \Sql('m1.accountLabel NOT LIKE "'.$prefix.'%"'));
			}
		}

		$cOperation = Operation::model()
			->select(['hash' => new \Sql('DISTINCT(hash)')])
			->join(OperationCashflow::model(), 'm1.id = m2.operation', 'LEFT')
			->where('m1.hash NOT IN ("'.join('", "', $hashToExclude).'")', if: count($hashToExclude) > 0)
			->getCollection();

		// On va déterminer toutes les opérations déjà équilibrées pour les exclure
		$hashes = array_unique($cOperation->getColumn('hash'));

		$cOperationNotBalanced = Operation::model()
			->select([
				'hash',
				'totalBank' => new \Sql('SUM(IF(accountLabel LIKE "512%", IF(type = "credit", -amount, amount), 0))'),
				'totalOther' => new \Sql('SUM(IF(accountLabel NOT LIKE "512%", IF(type = "credit", -amount, amount), 0))'),
			])
			->whereHash('IN', $hashes)
			->group('hash')
			->having('totalBank != - totalOther')
			->getCollection();

		$cOperation = Operation::model()
			->select($selection)
			->join(OperationCashflow::model(), 'm1.id = m2.operation', 'LEFT')
			->whereHash('IN', $cOperationNotBalanced->getColumn('hash'))
			->sort($sort + ['m1_date' => SORT_DESC])
			->getCollection(NULL, NULL, 'hash'); // Pour ne conserver que 1 opération par hash

		return self::setHashOperations($cOperation);

	}

	public static function setHashOperations(\Collection $cOperation): \Collection {

		$cOperationLinked = Operation::model()
			->select('id', 'hash', 'amount', 'type', 'operation', 'accountLabel')
			->whereHash('IN', $cOperation->getColumn('hash'))
			->getCollection();

		foreach($cOperation as &$eOperation) {

			if(isset($eOperation['cOperationHash']) === FALSE) {
				$eOperation['cOperationHash'] = new \Collection();
			}

			$eOperation['cOperationHash']->mergeCollection($cOperationLinked->find(fn($e) => $e['hash'] === $eOperation['hash']));

		}

		return $cOperation;

	}

	/* Récupère la liste des opérations à rattacher à une opération bancaire.
	 * - toutes les opérations (via le hash)
	 * Il n'y a pas de filtre sur le fait que les opérations soient déjà rattachées à une opération bancaire
	 * (exemple d'une écriture réglée en plusieurs paiements différents)
	 */
	public static function getOperationsForAttach(array $operationIds): \Collection {

		$hashes = Operation::model()
			->select('hash')
			->whereId('IN', $operationIds)
			->getCollection()
			->getColumn('hash');

		return self::getByHashes($hashes);

	}

	public static function attachOperationsToCashflow(\bank\Cashflow $eCashflow, \Collection $cOperation, \account\ThirdParty $eThirdParty): void {

		$hash = $cOperation->first()['hash'];
		$update = [
			'updatedAt' => Operation::model()->now(),
			'paymentDate' => $eCashflow['date'],
			'hash' => $hash
		];

		self::addOpenFinancialYearCondition()
			->select(array_keys($update))
			->whereId('IN', $cOperation->getIds())
			->update($update);

		// Create OperationCashflow entries
		$cOperationCashflow = new \Collection();

		foreach($cOperation as $eOperation) {
			$cOperationCashflow->append(new OperationCashflow([
				'operation' => $eOperation,
				'cashflow' => $eCashflow,
				'amount' => min($eOperation['amount'], abs($eCashflow['amount'])),
			]));
		}
		OperationCashflow::model()->insert($cOperationCashflow);

		// Create Bank line with the good third party
		OperationLib::createBankOperationFromCashflow($eCashflow, new Operation([
			'thirdParty' => $eThirdParty,
			'paymentMethod' => $eOperation['paymentMethod'],
			'financialYear' => $eOperation['financialYear'],
			'hash' => $hash,
			'accountLabel' => \account\AccountLabelLib::pad($eCashflow['account']['label'] ?? \account\AccountSetting::DEFAULT_BANK_ACCOUNT_LABEL),
		]));

	}

	public static function createBankOperationFromCashflow(\bank\Cashflow $eCashflow, Operation $eOperation, ?string $document = NULL): Operation {

		$eAccountBank = \account\AccountLib::getByClass(\account\AccountSetting::BANK_ACCOUNT_CLASS);

		$eThirdParty = $eOperation['thirdParty'] ?? new \account\ThirdParty();

		if($eCashflow['import']['account']['label'] !== NULL) {
			$label = $eCashflow['import']['account']['label'];
		} else {
			$label = \account\AccountLabelLib::pad(\account\AccountSetting::DEFAULT_BANK_ACCOUNT_LABEL);
		}

		$values = [
			'date' => $eCashflow['date'],
			'account' => $eAccountBank['id'] ?? NULL,
			'accountLabel' => $label,
			'description' => $eCashflow['memo'],
			'document' => $document,
			'documentDate' => $eOperation['documentDate'],
			'thirdParty' => $eThirdParty['id'] ?? NULL,
			'type' => ($eCashflow['amount'] > 0 ? Operation::DEBIT : Operation::CREDIT),
			'amount' => abs($eCashflow['amount']),
			'paymentDate' => $eCashflow['date'],
			'paymentMethod'=> $eOperation['paymentMethod']['id'] ?? NULL,
			'financialYear'=> $eOperation['financialYear']['id'],
			'journalCode'=> $eOperation['journalCode']['id'] ?? NULL,
			'hash'=> $eOperation['hash'],
		];

		$eOperationBank = new Operation();

		$fw = new \FailWatch();

		$eOperationBank->build([
			'financialYear', 'date', 'account', 'accountLabel', 'description', 'document', 'documentDate', 'thirdParty', 'type', 'amount',
			'operation', 'paymentDate', 'paymentMethod', 'hash', 'journalCode',
		], $values, new \Properties('create'));

		$fw->validate();

		\journal\Operation::model()->insert($eOperationBank);

		$eOperationCashflow = new OperationCashflow([
			'operation' => $eOperationBank,
			'cashflow' => $eCashflow,
			'amount' => min($eOperationBank['amount'], abs($eCashflow['amount'])),
		]);

		OperationCashflow::model()->insert($eOperationCashflow);

		return $eOperationBank;

	}

	public static function createFromValues(array $values, \bank\Cashflow $eCashflow = new \bank\Cashflow()): Operation {

		$eOperation = new Operation();

		$fw = new \FailWatch();

		$eOperation->build(
			[
				'financialYear', 'date', 'paymentDate',
				'operation', 'asset', 'thirdParty',
				'account', 'accountLabel',
				'description', 'type', 'amount',
				'document', 'documentDate',
				'vatRate', 'vatAccount',
				'journalCode', 'hash',
			],
			$values,
			new \Properties('create')
		);

		if(($values['asset'] ?? NULL) !== NULL) {
			$eOperation['asset'] = $values['asset'];
		}

		$fw->validate();

		Operation::model()->insert($eOperation);

		if($eCashflow->notEmpty()) {

			$eOperationCashflow = new OperationCashflow([
				'operation' => $eOperation,
				'cashflow' => $eCashflow,
				'amount' => min($eOperation['amount'], abs($eCashflow['amount'])),
			]);

			OperationCashflow::model()->insert($eOperationCashflow);

		}

		return $eOperation;

	}

	public static function unlinkCashflow(\bank\Cashflow $eCashflow, string $action): void {

		$cCashflow = \bank\Cashflow::model()
			->select(\bank\Cashflow::getSelection())
			->whereHash(($eCashflow['hash']))
			->getCollection();

		foreach($cCashflow as $eCashflow) {

			Operation::model()->beginTransaction();

			// Mise à jour du cashflow
			$hash = $eCashflow['hash'];
			$eCashflow['hash'] = NULL;
			$eCashflow['status'] = \bank\Cashflow::WAITING;
			\bank\Cashflow::model()->update($eCashflow, $eCashflow->extracts(['hash', 'status']));

			if($eCashflow['invoice']->notEmpty()) {
				$eInvoice = \selling\InvoiceLib::getById($eCashflow['invoice']['id']);
				\preaccounting\InvoiceLib::recalculateReadyForAccounting($eInvoice, $eCashflow);
			}

			$cOperation = OperationLib::getByHash($hash);

			// Suppression de l'écriture sur le compte 512 (banque) (qui est créée automatiquement)
			\journal\Operation::model()
        ->whereId('IN', $cOperation->getIds())
        ->whereAccountLabel('LIKE', \account\AccountSetting::DEFAULT_BANK_ACCOUNT_LABEL.'%')
        ->delete();

			// Dissociation cashflow <-> operation
			OperationCashflow::model()
       ->whereCashflow($eCashflow)
       ->delete();

			// Dissociation cashflow <-> operation
			OperationCashflow::model()
       ->whereOperation('IN', $cOperation->getIds(), if: count($cOperation->getIds()) > 0)
       ->delete();

			if($action === 'delete') {

				// Suppression des immos
				$cAsset = $cOperation->getColumnCollection('asset');
				if($cAsset->empty() === FALSE) {
					\asset\AssetLib::deleteByIds($cAsset->getIds());
				}

				// Suppression des liens vers les factures
				\selling\Invoice::model()
          ->whereAccountingHash($eCashflow['hash'])
          ->update(['accountingHash' => NULL]);

				// Suppression des écritures
				Operation::model()
         ->whereId('IN', $cOperation->getIds())
         ->delete();

			} else {

				// Réinitialiser les hash des opérations
				foreach($cOperation as $eOperation) {

					if($eOperation['operation']->notEmpty()) {
						continue; // Sera traitée avec le parent
					}

					$hash = self::generateHash().JournalSetting::HASH_LETTER_WRITE;

					// On affecte un nouveau hash aux opérations et leurs copines.
					Operation::model()
	         ->where('id = '.$eOperation['id'].' OR operation = '.$eOperation['id'])
	         ->update(['hash' => $hash]);

				}

			}

			\account\LogLib::save('unlinkCashflow', 'Operation', ['id' => $eCashflow['id'], 'action' => $action]);

			Operation::model()->commit();
		}
	}

	public static function countByThirdParty(): \Collection {

		return Operation::model()
			->select(['count' => new \Sql('COUNT(*)', 'int'), 'thirdParty'])
			->group(['thirdParty'])
			->getCollection(NULL, NULL, ['thirdParty']);

	}

	public static function countGroupByThirdParty(array $financialYearIds): \Collection {

		return Operation::model()
			->select(['financialYear', 'count' => new \Sql('COUNT(*)', 'int'), 'thirdParty'])
			->whereFinancialYear('IN', $financialYearIds)
			->group(['thirdParty', 'financialYear'])
			->getCollection(NULL, NULL, ['thirdParty', 'financialYear']);

	}

	public static function getDescriptions(string $accountLabel, \account\ThirdParty $eThirdParty): array {

		return Operation::model()
			->select(['description', 'count' => new \Sql('COUNT(*)')])
			->whereThirdParty($eThirdParty)
			->whereAccountLabel($accountLabel)
			// Exclusion banque et TVA qui sont générés automatiquement
			->where('accountLabel NOT LIKE "'.\account\AccountSetting::BANK_ACCOUNT_CLASS.'%"')
			->where('accountLabel NOT LIKE "'.\account\AccountSetting::VAT_CLASS.'%"')
			->group('description')
			->sort(['count' => SORT_DESC])
			->having('count > 2')
			->getCollection()
			->getColumn('description');

	}

	public static function getLabels(string $query, \account\ThirdParty $eThirdParty, \account\Account $eAccount): array {

		$labels = Operation::model()
			->select(['accountLabel' => new \Sql('DISTINCT(accountLabel)')])
			->whereThirdParty($eThirdParty, if: $eThirdParty->notEmpty())
			->whereAccount($eAccount, if: $eAccount->notEmpty())
			->where('accountLabel LIKE "'.$query.'%"', if: $query !== '')
			->sort(['accountLabel' => SORT_ASC])
			->getCollection()
			->getColumn('accountLabel');

		return $labels;

	}

	public static function countByAccounts(\Collection $cAccount, array $financialYearIds): \Collection {

		return Operation::model()
			->select([
				'count' => new \Sql('COUNT(*)', 'int'),
				'account', 'financialYear'
			])
			->whereFinancialYear('IN', $financialYearIds)
			->whereAccount('IN', $cAccount->getIds())
			->group(['account', 'financialYear'])
			->getCollection(NULL, NULL, ['account', 'financialYear']);

	}

	public static function countByAccount(\account\Account $eAccount): int {

		$eAccount->expects(['id']);

		return Operation::model()
			->whereAccount($eAccount)
			->count();

	}

	public static function setNumbers(\account\FinancialYear $eFinancialYear): void {

		$search = new \Search(['financialYear' => $eFinancialYear]);

		$cOperation = self::applySearch($search)
			->select('id')
			->sort(['date' => SORT_ASC, 'm1.id' => SORT_ASC])
			->getCollection();

		$number = 0;
		foreach($cOperation as $eOperation) {

			$eOperation['number'] = ++$number;
			Operation::model()->select('number')->update($eOperation);

		}

	}

	public static function getForOpening(\account\FinancialYear $eFinancialYear): \Collection {

		return Operation::model()
			->select([
				'total' => new \Sql('SUM(IF(type="debit", -amount, amount))', 'float'),
				'account' => \account\Account::getSelection(),
				'accountLabel'
			])
			->whereFinancialYear($eFinancialYear)
			->where(new \Sql('SUBSTRING(accountLabel, 1, 1) NOT IN ("'.join('", "', [\account\AccountSetting::CHARGE_ACCOUNT_CLASS, \account\AccountSetting::PRODUCT_ACCOUNT_CLASS]).'")'))
			->sort(['accountLabel' => SORT_ASC])
			->group(['account', 'accountLabel'])
			->having(new \Sql('ABS(total) > 0.0'))
			->getCollection();

	}

	/**
	 * @param \Collection $cOperation Opérations sommées par compte de l'exercice précédent
	 * @param \account\FinancialYear $eFinancialYear Exercice sur lequel écrire les opérations d'ouverture
	 * @param \account\FinancialYear $eFinancialYearPrevious Exercice sur lequel sont basées les opérations $cOperation
	 */
	public static function createForOpening(\Collection $cOperation, \account\FinancialYear $eFinancialYear, \account\FinancialYear $eFinancialYearPrevious): void {

		$eJournalCodeOD = JournalCodeLib::getByCode(JournalSetting::JOURNAL_CODE_OD);
		$number = 1;

		foreach($cOperation as $eOperation) {

			$values = [
				'financialYear' => $eFinancialYear['id'],
				'amount' => abs($eOperation['total']),
				'type' => ($eOperation['total'] > 0 ? Operation::CREDIT : Operation::DEBIT),
				'account' => $eOperation['account']['id'],
				'accountLabel' => $eOperation['accountLabel'],
				'date' => $eFinancialYear['startDate'],
				'paymentDate' => $eFinancialYear['startDate'],
				'description' => new \account\FinancialYearUi()->getOpeningDescription($eFinancialYearPrevious),
				'journalCode' => $eJournalCodeOD,
				'document' => 'OUV-'.str_pad($number, 4, '0', STR_PAD_LEFT),
				'documentDate' => $eFinancialYear['startDate'],
			];

			self::createFromValues($values);

			$number++;

		}

	}

	public static function updateJournalCodeCollection(\Collection $cOperation, JournalCode $eJournalCode): void {

		$cJournalCode = JournalCodeLib::getAll();

		if($eJournalCode->notEmpty() and in_array($eJournalCode['id'], $cJournalCode->getIds()) === FALSE) {
			\Fail::log('Operation::selectedJournalCodeInconsistency');
			return;
		}

		Operation::model()
			->select(['journalCode'])
			->where('id IN ('.join(', ', $cOperation->getIds()).') OR operation IN ('.join(', ', $cOperation->getIds()).')')
			->update(['journalCode' => $eJournalCode]);
	}

	public static function updateDocumentCollection(\Collection $cOperation, ?string $document): void {

		if($document === "") {
			$document = NULL;
		}

		Operation::model()
			->select(['document'])
			->where('id IN ('.join(', ', $cOperation->getIds()).') OR operation IN ('.join(', ', $cOperation->getIds()).')')
			->update(new Operation(['document' => $document]));
	}

	public static function updatePaymentMethodCollection(\Collection $cOperation, \payment\Method $ePaymentMethod): void {

		Operation::model()
			->select(['paymentMethod'])
			->where('id IN ('.join(', ', $cOperation->getIds()).') OR operation IN ('.join(', ', $cOperation->getIds()).')')
			->update(new Operation(['paymentMethod' => $ePaymentMethod]));

	}

}
?>
