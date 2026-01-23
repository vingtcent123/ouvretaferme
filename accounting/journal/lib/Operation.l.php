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

	public static function getForAssetAttach(array $ids): \Collection {

		return Operation::model()
			->select(Operation::getSelection())
			->whereId('IN', $ids)
			->whereAsset(NULL)
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

			$model = Operation::model()
				->whereDate('>=', fn() => $search->get('financialYear')['startDate'])
				->whereDate('<=', fn() => $search->get('financialYear')['endDate']);

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
			self::applyAssetCondition()
		    ->or(
					fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::ASSET_GENERAL_CLASS.'%'),
					fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::EQUIPMENT_GRANT_CLASS.'%'),
				)
				->whereAccountLabel('NOT LIKE', \account\AccountSetting::IN_PROGRESS_ASSETS_CLASS.'%')
				->whereAccountLabel('NOT LIKE', \account\AccountSetting::IN_CONTRUCTION_ASSETS_CLASS.'%')
				->where(new \Sql('SUBSTRING(hash, LENGTH(hash), 1) != "'.\journal\JournalSetting::HASH_LETTER_RETAINED.'"'))
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
			->whereAmount('>=', $search->get('amountMin'), if: $search->get('amountMin'))
			->whereAmount('<=', $search->get('amountMax'), if: $search->get('amountMax'))
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
		$defaultOrder = ($eFinancialYear !== NULL and $eFinancialYear->isCashAccounting()) ? ['date' => SORT_ASC, 'm1.id' => SORT_ASC] : ['date' => SORT_ASC, 'm1.id' => SORT_ASC];

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
			->where(new \Sql('SUBSTRING(hash, LENGTH(hash), 1) != "'.\journal\JournalSetting::HASH_LETTER_RETAINED.'"'))
			->getCollection()
			->getColumn('hash');

		return count(array_unique($hashes));

	}

	public static function getUnbalanced(\Search $search): array {

		$eFinancialYear = $search->get('financialYear');
		$defaultOrder = $eFinancialYear->isCashAccounting() ? ['date' => SORT_ASC, 'm1.id' => SORT_ASC] : ['date' => SORT_ASC, 'm1.id' => SORT_ASC];

		// Récupérer les hash des opérations déséquilibrées
		$hashes = self::applySearch(new \Search(['financialYear' => $eFinancialYear]))
			->select(['hash', 'balance' => new \Sql('SUM(IF(type = "'.Operation::CREDIT.'", amount, -amount))', 'float')])
			->where(new \Sql('SUBSTRING(hash, LENGTH(hash), 1) != "'.\journal\JournalSetting::HASH_LETTER_RETAINED.'"'))
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
			->where(new \Sql('SUBSTRING(hash, LENGTH(hash), 1) != "'.\journal\JournalSetting::HASH_LETTER_RETAINED.'"'))
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
		$defaultOrder = $eFinancialYear->isCashAccounting() ? ['date' => SORT_ASC, 'm1.id' => SORT_ASC] : ['date' => SORT_ASC, 'm1.id' => SORT_ASC];

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

	public static function getAllForVatJournal(string $type, \Search $search = new \Search(), bool $hasSort = FALSE,
		?array $index = ['accountLabel', 'month', NULL]): \Collection {

		// Si c'est le journal des achats il faut tout afficher en positif
		if($type === 'buy') {
			$amount = new \Sql('IF(type = "credit", -1 * amount, amount)');
		} else {
			$amount = new \Sql('IF(type = "debit", -1 * amount, amount)');
		}
		return self::applySearch($search)
			->select([
				'id', 'document', 'financialYear', 'description', 'thirdParty' => ['id', 'name'],
				'amount' => $amount,
				'operation' => ['id', 'asset', 'accountLabel', 'amount' => $amount, 'date', 'document', 'financialYear', 'description', 'thirdParty' => ['id', 'name'], 'vatRate'],
				'date', 'accountLabel', 'account' => ['id', 'class', 'description'],
				'month' => new \Sql('SUBSTRING(date, 1, 7)'),
			])
			->sort($hasSort === TRUE ? $search->buildSort() : ['accountLabel' => SORT_ASC, 'date' => SORT_ASC, 'm1.id' => SORT_ASC])
			->whereAccountLabel('LIKE', ($type === 'buy' ? \account\AccountSetting::VAT_BUY_CLASS_PREFIX : \account\AccountSetting::VAT_SELL_CLASS_PREFIX).'%')
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

		Operation::model()->beginTransaction();

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

		Operation::model()->commit();

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


	public static function prepareOperations(array $input, string $for = 'create', \bank\Cashflow $eCashflow = new \bank\Cashflow()): \Collection {

		$eFinancialYear = \account\FinancialYearLib::getById($input['financialYear'] ?? NULL);
		$hash = self::generateHash().($eCashflow->empty() ? JournalSetting::HASH_LETTER_WRITE : JournalSetting::HASH_LETTER_CASHFLOW);

		if($eFinancialYear->acceptUpdate() === FALSE) {

			\Fail::log('Operation::FinancialYear.notUpdatable');
			return new \Collection();
		}

		$isFromCashflow = $eCashflow->notEmpty();

		if($isFromCashflow) {

			$eOperationDefault = new Operation([
				'date' => $eCashflow['date'],
				'paymentDate' => $eCashflow['date'],
				'paymentMethod' => \payment\MethodLib::getById(POST('paymentMethod')),
			]);

			if($eOperationDefault['paymentMethod']->empty()) {
				\Fail::log('Operation::paymentMethod.empty');
				return new \Collection();
			}

		} else {

			$eOperationDefault = new Operation();

		}

		if($for === 'update') {

			$cOperationOriginByHash = OperationLib::getByHash($input['hash']);

			if($cOperationOriginByHash->notEmpty()) {

				foreach($cOperationOriginByHash as $eOperationOriginByHash) {
					$eOperationOriginByHash->validate('isNotLinkedToAsset');
				}

				// Si ce lot d'opérations était lié à une facture => Mise à jour du hash de l'invoice et de ses sales
				if($for === 'update') {

					\selling\Invoice::model()
			      ->whereAccountingHash($input['hash'])
			      ->update(['accountingHash' => $hash]);

					\selling\Sale::model()
			      ->whereAccountingHash($input['hash'])
			      ->update(['accountingHash' => $hash]);
				}

				// On supprime tout et on recommence !
				OperationCashflow::model()->whereHash($input['hash'])->delete();
				Operation::model()->whereHash($input['hash'])->delete();

			}

			$for = 'create';

		}

		$accounts = var_filter($input['account'] ?? [], 'array');
		$vatValues = var_filter($input['vatValue'] ?? [], 'array');
		$indexes = count($input['accountLabel']);

		$fw = new \FailWatch();

		$cAccount = \account\AccountLib::getByIdsWithVatAccount($accounts);

		$cOperation = new \Collection();
		$cOperationCashflow = new \Collection();
		$properties = [
			'account', 'accountLabel',
			'description', 'amount', 'type', 'document', 'vatRate',
			'asset',
			'journalCode', 'thirdParty',
		];
		if($eFinancialYear['hasVat']) {
			$properties[] = 'vat';
		}
		if($isFromCashflow === FALSE) {
			$properties = array_merge($properties, ['date', 'paymentDate', 'paymentMethod']);
		}

		$eOperationDefault['thirdParty'] = NULL;
			
		$eOperationDefault['hash'] = $hash;
		$eOperationDefault['financialYear'] = $eFinancialYear;

		for($index = 0; $index < $indexes; $index++) {

			$eOperation = clone $eOperationDefault;

			$eOperation['index'] = $index;
			$eOperation['financialYear'] = $eFinancialYear;

			$input['accountLabel'][$index] = \account\AccountLabelLib::pad($input['accountLabel'][$index]);

			$eOperation->buildIndex($properties, $input, $index);

			$fw->validate();


			// Date de la pièce justificative : date de l'écriture
			if($eOperation['document'] !== NULL) {
				$eOperation['documentDate'] = $eOperation['date'];
			} else {
				$eOperation['documentDate'] = NULL;
			}

			// Enregistre les termes du libellé de banque pour améliorer les prédictions
			if($isFromCashflow === TRUE) {

				$eThirdParty = \account\ThirdPartyLib::recalculateMemos($eCashflow, $eOperation['thirdParty']);
				\account\ThirdPartyLib::update($eThirdParty, ['memos']);

			}

			// Ce type d'écriture a un compte de TVA correspondant
			$eAccount = $eOperation['account'];
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

			// Doit passer après validate (account doit être setté)
			if($eOperation['journalCode']->empty()) {
					$eOperation['journalCode'] = $cAccount->find(fn($e) => $e['id'] === $eOperation['account']['id'])->first()['journalCode'];
			}

			// Données que l'on va recopier par défaut sur toutes les autres écritures du groupe
			foreach(['document', 'documentDate', 'thirdParty', 'journalCode'] + ($for === 'create' ? ['date', 'paymentMethod'] : []) as $property) {
				if(($eOperationDefault[$property] ?? NULL) === NULL) {
					$eOperationDefault[$property] = $eOperation[$property];
				}
			}

			\journal\Operation::model()->insert($eOperation);

			$cOperation->append($eOperation);

			if($isFromCashflow) {
				$cOperationCashflow->append(new OperationCashflow([
					'operation' => $eOperation,
					'cashflow' => $eCashflow,
					'hash' => $hash,
				]));
			}

			// Ajout de l'entrée de compte de TVA correspondante
			if($hasVatAccount === TRUE) {

				$defaultValues = $isFromCashflow === TRUE
					? [
						'date' => $eCashflow['date'],
						'description' => $eOperation['description'] ?? $eCashflow->getMemo(),
						'cashflow' => $eCashflow,
						'paymentMethod' => $eOperation['paymentMethod'],
						'hash' => $hash,
					]
					: $eOperation->getArrayCopy();

				// Cette fonction fait déjà l'ajout dans OperationCashflow
				$eOperationVat = \journal\OperationLib::createVatOperation(
					$eOperation,
					$eAccount,
					$input['vatValue'][$index],
					defaultValues: $defaultValues,
					eCashflow: $eCashflow,
				);
				$cOperation->append($eOperationVat);

				if($isFromCashflow) {

					$cOperationCashflow->append(new OperationCashflow([
						'operation' => $eOperationVat,
						'cashflow' => $eCashflow,
						'hash' => $hash,
					]));

				}

			}

			// Gestion des acomptes (409x et 419x) qui doivent être enregistrés TTC + une contrepartie 44581 pour la TVA
			if(
				\account\AccountLabelLib::isDeposit($eOperation['accountLabel'])  and
				(int)$eOperation['vatRate'] !== 0 and
				$eOperationVat->notEmpty()
			) {

				$eAccountVatRegul = \account\AccountLib::getByClass(\account\AccountSetting::VAT_TO_REGULATE_CLASS);
				$eOperation['amount'] += $eOperationVat['amount'] ?? 0;
				Operation::model()->update($eOperation, ['amount' => $eOperation['amount']]);

				// Créer l'écriture de TVA de régul
				$eOperationVatRegul = self::createVatRegulOperation($eOperationVat, $eAccountVatRegul, $eOperation);

				$cOperation->append($eOperationVatRegul);

				if($isFromCashflow) {
					$cOperationCashflow->append(new OperationCashflow([
						'operation' => $eOperationVatRegul,
						'cashflow' => $eCashflow,
						'hash' => $hash,
					]));
				}
			}

		}

		// Ajout de la transaction sur le numéro de compte bancaire 512 (seulement pour une création)
		if($isFromCashflow === TRUE) {

			// Si toutes les écritures sont sur le même document, on utilise aussi celui-ci pour l'opération bancaire;
			$documents = $cOperation->getColumn('document');
			$uniqueDocuments = array_unique($documents);
			if(count($uniqueDocuments) === 1 and count($documents) === $cOperation->count()) {
				$document = first($uniqueDocuments);
			} else {
				$document = NULL;
			}

			$eOperationDefault['hash'] = $hash;

			$eOperationBank = \journal\OperationLib::createBankOperationFromCashflow(
				$eCashflow,
				$eOperationDefault,
				$document,
			);
			$cOperation->append($eOperationBank);
			$cOperationCashflow->append(new OperationCashflow(['operation' => $eOperationBank, 'cashflow' => $eCashflow, 'hash' => $hash]));

			\bank\Cashflow::model()->update(
				$eCashflow,
				['status' => \bank\CashflowElement::ALLOCATED, 'updatedAt' => \bank\Cashflow::model()->now(), 'hash' => $hash]
			);

			if($cOperationCashflow->notEmpty()) {
				OperationCashflow::model()->insert($cOperationCashflow);
			}

		}

		if($fw->ko()) {
			return new \Collection();
		}

		return $cOperation;

	}

	public static function createVatRegulOperation(Operation $eOperationVat, \account\Account $eAccountVatRegul, Operation $eOperationParent): Operation {

		$eOperationVatRegul = new Operation($eOperationVat->getArrayCopy());
		unset($eOperationVatRegul['id']);
		$eOperationVatRegul['type'] = $eOperationParent['type'] === Operation::DEBIT ? Operation::CREDIT : Operation::DEBIT;
		$eOperationVatRegul['account'] = $eAccountVatRegul;
		$eOperationVatRegul['accountLabel'] = \account\AccountLabelLib::pad($eAccountVatRegul['class']);

		Operation::model()->insert($eOperationVatRegul);

		return $eOperationVatRegul;
	}

	public static function createVatOperation(Operation $eOperationLinked, \account\Account $eAccount, float $vatValue, array $defaultValues, \bank\Cashflow $eCashflow): Operation {

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
			'date' => $defaultValues['date'],
		];

		if($eCashflow->empty()) {
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
				'date',
			], $eCashflow->empty() ? ['paymentDate', 'paymentMethod',] : []),
			$values,
		);

		$eOperationVat['operation'] = $eOperationLinked;
		if($eCashflow->notEmpty()) {
			$eOperationVat['paymentDate'] = $eCashflow['date'];
			$eOperationVat['paymentMethod'] = \payment\MethodLib::getById(POST('paymentMethod'));
		}

		$fw->validate();

		Operation::model()->insert($eOperationVat);

		// On retourne l'opération complète
		return OperationLib::getById($eOperationVat['id']);

	}

	public static function deleteByHash(string $hash): void {

		// Suppression de toutes les opérations liées par le hash
		$cOperation = Operation::model()
			->select('id')
			->whereHash($hash)
			->getCollection();

		OperationCashflow::model()->whereOperation('IN', $cOperation->getIds())->delete();
		Operation::model()->whereId('IN', $cOperation->getIds())->delete();

		// Si l'opération est issue d'un import en compta => supprimer le lien dans la facture et ses ventes
		\selling\Invoice::model()
			->whereAccountingHash($hash)
			->update(['accountingHash' => NULL]);
		\selling\Sale::model()
			->whereAccountingHash($hash)
			->update(['accountingHash' => NULL]);

	}

	public static function delete(Operation $e): void {

		$e->expects(['id', 'asset']);

		if($e['asset']->notEmpty()) {
			throw new \Exception('Impossible to delete operation with Asset');
		}

		\journal\Operation::model()->beginTransaction();

		self::deleteByHash($e['hash']);

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

	public static function getForAttachQuery(\bank\Cashflow $eCashflow, \Search $search): \Collection {

		$selection = Operation::getSelection();
		if($search->get('thirdParty')->notEmpty()) {
			$selection['isThirdParty'] = new \Sql('IF(thirdParty = '.$search->get('thirdParty')['id'].', 1, 0)', 'bool');
			$sort = ['m1_isThirdParty' => SORT_DESC];
		} else {
			$selection['isThirdParty'] = new \Sql('0');
			$sort = ['m1_isThirdParty' => SORT_DESC];
		}

		if($search->get('query') !== '') {

			$query = trim(preg_replace('/[+\-><\(\)~*\"@]+/', ' ', $search->get('query')));
			$keywords = [];

			$words = array_filter(preg_split('/\s+/', $query));

			if(count($words) > 0) {

				foreach($words as $word) {
					$keywords[] = '*'.$word.'*';
				}

				$match = 'MATCH(accountLabel, description, document) AGAINST ('.Operation::model()->format(implode(' ', $keywords)).' IN BOOLEAN MODE)';

				Operation::model()->where($match.' > 0');
			}

		}

		$excludedOperationIds = array_filter($search->get('excludedOperationIds'), fn($entry) => $entry);

		$excludedPrefix = array_filter($search->get('excludedPrefix'), fn($val) => $val);
		if(count($excludedPrefix) > 0) {
			foreach($excludedPrefix as $prefix) {
				Operation::model()->where(new \Sql('m1.accountLabel NOT LIKE "'.$prefix.'%"'));
			}
		}

		$cFinancialYear = \account\FinancialYearLib::getOpenFinancialYears();
		$eFinancialYear = \account\FinancialYearLib::getFinancialYearForDate($eCashflow['date'], $cFinancialYear);

		$cOperationNotBalanced = Operation::model()
			->select([
				'hash',
				'totalBank' => new \Sql('SUM(IF(accountLabel LIKE "512%", IF(type = "credit", -amount, amount), 0))'),
				'totalOther' => new \Sql('SUM(IF(accountLabel NOT LIKE "512%", IF(type = "credit", -amount, amount), 0))'),
			])
			->whereFinancialYear($eFinancialYear)
			->group('hash')
			->having('totalBank != - totalOther')
			->getCollection();

		return Operation::model()
			->select($selection)
			->whereFinancialYear($eFinancialYear)
			->whereAccountLabel('NOT LIKE', \account\AccountSetting::VAT_CLASS.'%')
			->join(OperationCashflow::model(), 'm1.id = m2.operation', 'LEFT')
			->where('m2.cashflow IS NULL')
			->where('m1.id NOT IN ('.join(', ', $excludedOperationIds).')', if: count($excludedOperationIds) > 0)
			->where('m1.hash IN ("'.join('", "', $cOperationNotBalanced->getColumn('hash')).'")')
			->sort($sort + ['m1_date' => SORT_DESC])
			->getCollection(NULL, NULL, 'hash'); // Pour ne conserver que 1 opération par hash

	}

	public static function getForDeferral(string $query, \account\FinancialYear $eFinancialYear): \Collection {

		if($query !== '') {

			$query = trim(preg_replace('/[+\-><\(\)~*\"@]+/', ' ', $query));
			$keywords = [];

			$words = array_filter(preg_split('/\s+/', $query));

			if(count($words) > 0) {

				foreach($words as $word) {
					$keywords[] = '*'.$word.'*';
				}

				$match = 'MATCH(accountLabel, description, document) AGAINST ('.Operation::model()->format(implode(' ', $keywords)).' IN BOOLEAN MODE)';

				Operation::model()->where($match.' > 0');
			}

		} else {
			$match = '';
		}

		return Operation::model()
			->select(Operation::getSelection())
			->or(
				fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::CHARGE_ACCOUNT_CLASS.'%'),
				fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::PRODUCT_ACCOUNT_CLASS.'%'),
			)
			->join(Deferral::model(), 'm1.id = m2.operation', 'LEFT')
			->where('m2.id IS NULL')
			->where($match.' > 0', if: $match)
			->whereDate('>=', $eFinancialYear['startDate'])
			->whereDate('<=', $eFinancialYear['endDate'])
			->sort(['date' => SORT_DESC])
			->getCollection(NULL, NULL, 'hash'); // Pour ne conserver que 1 opération par hash

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

		$eOperationModel = $cOperation->first();

		$hash = $eOperationModel['hash'];
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
				'hash' => $hash,
			]));

		}

		// Create Bank line with the good third party
		$eOperationBank = OperationLib::createBankOperationFromCashflow($eCashflow, new Operation([
			'thirdParty' => $eThirdParty,
			'journalCode' => $eOperationModel['journalCode'],
			'documentDate' => $eOperationModel['documentDate'],
			'paymentMethod' => $eOperationModel['paymentMethod'],
			'financialYear' => $eOperationModel['financialYear'],
			'hash' => $hash,
			'accountLabel' => \account\AccountLabelLib::pad($eCashflow['account']['label'] ?? \account\AccountSetting::DEFAULT_BANK_ACCOUNT_LABEL),
		]), $eOperationModel['document']);

		$cOperationCashflow->append(new OperationCashflow(['operation' => $eOperationBank, 'cashflow' => $eCashflow, 'hash' => $hash]));

		OperationCashflow::model()->insert($cOperationCashflow);
	}

	public static function createBankOperationFromCashflow(\bank\Cashflow $eCashflow, Operation $eOperation, ?string $document = NULL): Operation {

		$eAccountBank = \account\AccountLib::getByClass(\account\AccountSetting::BANK_ACCOUNT_CLASS);

		$eThirdParty = $eOperation['thirdParty'] ?? new \account\ThirdParty();

		$label = $eCashflow['import']['account']['label'];

		$values = [
			'date' => $eCashflow['date'],
			'account' => $eAccountBank['id'] ?? NULL,
			'accountLabel' => $label,
			'description' => $eCashflow->getMemo(),
			'document' => $document,
			'documentDate' => $eOperation['documentDate'] ?? NULL,
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

		return $eOperationBank;

	}

	public static function createFromValues(array $values, \bank\Cashflow $eCashflow = new \bank\Cashflow()): Operation {

		$eOperation = new Operation();

		$fw = new \FailWatch();

		$eOperation->build(
			[
				'financialYear', 'date', 'paymentDate',
				'account', 'accountLabel',
				'journalCode', 'hash',
				'operation', 'asset', 'thirdParty',
				'description', 'type', 'amount',
				'document', 'documentDate',
				'vatRate', 'vatAccount',
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

		$hash = $eCashflow['hash'];

		$cOperation = Operation::model()
			->select(['id', 'hash', 'asset'])
			->whereHash($hash)
			->getCollection();

		if($action === 'delete') {
			// On vérifie qu'il n'y a pas d'immos impliqués
			$eOperation = new Operation(['cOperationHash' => $cOperation]);
			$eOperation->validate('isNotLinkedToAsset');
		}

		Operation::model()->beginTransaction();

		// Dissociation cashflow et operation
		OperationCashflow::model()
			->or(
				fn() => $this->whereCashflow($eCashflow),
				fn() => $this->whereOperation('IN', $cOperation->getIds(), if: count($cOperation->getIds()) > 0)
			)
     ->delete();

		// Suppression du lien écritures - factures / ventes (mais pas du rapprochement)
		\selling\Invoice::model()
      ->whereAccountingHash($eCashflow['hash'])
      ->update(['accountingHash' => NULL]);
		\selling\Sale::model()
      ->whereAccountingHash($eCashflow['hash'])
      ->update(['accountingHash' => NULL]);

		if($action === 'delete') {

			self::deleteByHash($hash);

		} else {

		// Suppression de l'écriture sur le compte 512 (banque) (qui est créée automatiquement)
		\journal\Operation::model()
      ->whereHash($hash)
      ->whereAccountLabel('LIKE', \account\AccountSetting::DEFAULT_BANK_ACCOUNT_LABEL.'%')
      ->delete();

			// On affecte 1 hash à tout le nouveau groupe d'opérations
			$hash = self::generateHash().JournalSetting::HASH_LETTER_WRITE;
			Operation::model()
				->whereHash($hash)
				->update(['hash' => $hash]);

		}

		// Mise à jour du cashflow
		$eCashflow['hash'] = NULL;
		$eCashflow['status'] = \bank\Cashflow::WAITING;
		\bank\Cashflow::model()->update($eCashflow, $eCashflow->extracts(['hash', 'status']));

		\account\LogLib::save('unlinkCashflow', 'Operation', ['id' => $eCashflow['id'], 'action' => $action]);

		Operation::model()->commit();
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

	/**
	 * Comptes de l'exploitant
	 */
	public static function getFarmersAccountValue(\account\FinancialYear $eFinancialYear): float {

		return Operation::model()
			->select([
				'total' => new \Sql('SUM(IF(type="debit", -amount, amount))', 'float'),
			])
			->whereFinancialYear($eFinancialYear)
			->whereAccountLabel('LIKE', \account\AccountSetting::FARMER_S_ACCOUNT_CLASS.'%')
			->get()['total'] ?? 0.0;

	}

	/**
	 * Comptes d'attente
	 */
	public static function getWaitingAccountValues(\account\FinancialYear $eFinancialYear): array {

		return Operation::model()
			->select([
				'total' => new \Sql('SUM(IF(type="debit", -amount, amount))', 'float'),
				'accountLabel' => new \Sql('SUBSTRING(accountLabel, 1, 3)')
			])
			->whereFinancialYear($eFinancialYear)
			->where(new \Sql('SUBSTRING(accountLabel, 1, 3) IN ('.join(',', \account\AccountSetting::WAITING_ACCOUNT_CLASSES).')'))
			->group('accountLabel')
			->getCollection(NULL, NULL, 'accountLabel')
			->getArrayCopy();

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
