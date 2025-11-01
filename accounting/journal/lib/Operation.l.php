<?php
namespace journal;

class OperationLib extends OperationCrud {

	const TMP_INVOICE_FOLDER = 'tmp-invoice';

	public static function getPropertiesCreate(): array {
		return ['account', 'accountLabel', 'date', 'description', 'document', 'amount', 'type', 'vatRate', 'thirdParty', 'asset'];
	}
	public static function getPropertiesUpdate(): array {
		return ['account', 'accountLabel', 'date', 'description', 'document', 'amount', 'type', 'thirdParty'];
	}

	public static function countByOldDatesButNotNewDate(\account\FinancialYear $eFinancialYear, string $newStartDate, string $newEndDate): int {

		return Operation::model()
			->whereDate('BETWEEN', new \Sql(\account\FinancialYear::model()->format($eFinancialYear['startDate']).' AND '.\account\FinancialYear::model()->format($eFinancialYear['endDate'])))
			->whereDate('NOT BETWEEN', new \Sql(\account\FinancialYear::model()->format($newStartDate).' AND '.\account\FinancialYear::model()->format($newEndDate)))
			->count();

	}

	public static function applySearch(\Search $search = new \Search()): OperationModel {

		if($search->has('financialYear')) {

			if($search->get('financialYear')['accountingType'] === \account\FinancialYear::ACCRUAL) {

				$model = Operation::model()
					->whereDate('>=', fn() => $search->get('financialYear')['startDate'], if: $search->has('financialYear'))
					->whereDate('<=', fn() => $search->get('financialYear')['endDate'], if: $search->has('financialYear'));

			} else {

				$model = Operation::model()
					->or(
						fn() => $this
							->wherePaymentDate('BETWEEN', new \Sql(\account\FinancialYear::model()->format($search->get('financialYear')['startDate']).' AND '.\account\FinancialYear::model()->format($search->get('financialYear')['endDate'])), if: $search->has('financialYear')),
						fn() => $this
							->wherePaymentDate(NULL)
							->whereDate('BETWEEN', new \Sql(\account\FinancialYear::model()->format($search->get('financialYear')['startDate']).' AND '.\account\FinancialYear::model()->format($search->get('financialYear')['endDate'])), if: $search->has('financialYear')),
					);

			}
		}

		if($search->get('cashflowFilter') === TRUE) {
			$model
				->join(OperationCashflow::model(), 'm1.id = m2.operation', 'LEFT')
				->where('m2.id IS NULL');
		}

		if($search->get('cashflow')) {
			$model
				->join(OperationCashflow::model(), 'm1.id = m2.operation')
				->where('m2.cashflow = '.$search->get('cashflow'));

		}

		return $model
			->whereJournalCode('=', $search->get('journalCode'), if: $search->has('journalCode') and $search->get('journalCode') !== NULL)
			->whereDate('LIKE', '%'.$search->get('date').'%', if: $search->get('date'))
			->whereDate('>=', $search->get('minDate'), if: $search->get('minDate'))
			->whereDate('<=', $search->get('maxDate'), if: $search->get('maxDate'))
			->wherePaymentDate('LIKE', '%'.$search->get('paymentDate').'%', if: $search->get('paymentDate'))
			->wherePaymentMethod($search->get('paymentMethod'), if: $search->get('paymentMethod'))
			->whereAccountLabel('LIKE', $search->get('accountLabel').'%', if: $search->get('accountLabel'))
			->where(fn() => 'accountLabel LIKE "'.join('%" OR accountLabel LIKE "', $search->get('accountLabels')).'%"', if: $search->get('accountLabels'))
			->whereDescription('LIKE', '%'.$search->get('description').'%', if: $search->get('description'))
			->whereDocument($search->get('document'), if: $search->get('document'))
			->whereType($search->get('type'), if: $search->get('type'))
			->whereAsset($search->get('asset'), if: $search->get('asset'))
			->whereThirdParty('=', $search->get('thirdParty'), if: $search->get('thirdParty'))
			->whereDocument(NULL, if: $search->has('hasDocument') and $search->get('hasDocument') === '1');

	}

	public static function getByThirdPartyAndOrderedByUsage(\account\ThirdParty $eThirdParty): \Collection {

		return \journal\Operation::model()
			->select(['account', 'count' => new \Sql('COUNT(*)')])
			->whereThirdParty($eThirdParty)
			->group('account')
			->sort(['count' => SORT_DESC])
			->getCollection(NULL, NULL, 'account');

	}

	public static function getAllForBook(\Search $search = new \Search()): \Collection {

		return self::applySearch($search)
			->select(
				Operation::getSelection()
				+ ['thirdParty' => ['name']]
				+ ['class' => new \Sql('SUBSTRING(IF(accountLabel IS NULL, m2.class, accountLabel), 1, 3)')]
				+ ['accountLabel' => new \Sql('IF(accountLabel IS NULL, RPAD(m2.class, 8, "0"), accountLabel)')]
				+ ['account' => ['description']]
			)
			->join(\account\Account::model(), 'm1.account = m2.id')
			->whereAccountLabel('LIKE', trim($search->get('accountLabel'), '0').'%', if: $search->get('accountLabel'))
			->sort(['m1_accountLabel' => SORT_ASC, 'date' => SORT_ASC])
			->getCollection();

	}

	public static function getAllForVatDeclaration(\Search $search = new \Search()): \Collection {

		$search->set('accountLabel', \account\AccountSetting::VAT_CLASS);

		return self::applySearch($search)
       ->select(
         Operation::getSelection()
         + ['account' => ['class', 'description']]
         + ['thirdParty' => ['id', 'name']]
	       + ['operation' => Operation::getSelection()]
       )
			->whereVatDeclaration(NULL, if: $search->has('vatDeclaration') === FALSE)
			->sort(['date' => SORT_ASC, 'id' => SORT_ASC])
			->getCollection();

	}

	public static function getAllForAccounting(\Search $search = new \Search(), bool $hasSort = FALSE): \Collection {

		return self::applySearch($search)
			->select(
				Operation::getSelection()
				+ ['account' => ['class', 'description']]
				+ ['thirdParty' => ['id', 'name']]
				+ ['cLetteringCredit' => LetteringLib::delegate('credit')]
				+ ['cLetteringDebit' => LetteringLib::delegate('debit')]
			)
			->sort($hasSort === TRUE ? $search->buildSort() : ['date' => SORT_ASC, 'id' => SORT_ASC])
			->getCollection();
	}

	public static function getAllForJournal(\Search $search = new \Search(), bool $hasSort = FALSE): \Collection {

		$eFinancialYear = $search->get('financialYear');
		$defaultOrder = $eFinancialYear->isCashAccounting() ? ['paymentDate' => SORT_ASC, 'date' => SORT_ASC, 'm1.id' => SORT_ASC] : ['date' => SORT_ASC, 'm1.id' => SORT_ASC];

		return self::applySearch($search)
			->select(
				Operation::getSelection()
				+ ['account' => ['class', 'description']]
				+ ['thirdParty' => ['id', 'name']]
			)
			->sort($hasSort === TRUE ? $search->buildSort() : $defaultOrder)
			->getCollection();

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

		return self::applySearch($search)
			->select(
				Operation::getSelection()
				+ ['operation' => [
					'id', 'account', 'accountLabel', 'document', 'type',
					'thirdParty' => ['id', 'name'],
					'description', 'amount', 'vatRate', 'date',
					'financialYear',
					'cOperationCashflow' => OperationCashflowLib::delegateByOperation()
				]]
				+ ['account' => ['class', 'description']]
				+ ['thirdParty' => ['id', 'name']]
				+ ['month' => new \Sql('SUBSTRING(date, 1, 7)')]
				+ ['amount' => new \Sql('IF(SUBSTRING(accountLabel, 1, 4) = "'.\account\AccountSetting::VAT_BUY_CLASS_PREFIX.'", IF(type = "credit", -1 * amount, amount), IF(type = "debit", -1 * amount, amount))')]
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

	public static function getNotLetteredOperationsByThirdParty(\account\ThirdParty $eThirdParty): \Collection {

		if($eThirdParty->empty()) {
			return new \Collection();
		}

		return Operation::model()
			->select(Operation::getSelection() +
				['cLetteringCredit' => LetteringLib::delegate('credit'), 'cLetteringDebit' => LetteringLib::delegate('debit')]
			)
			->whereLetteringStatus('!=', Operation::TOTAL)
			->sort(['date' => SORT_ASC])
			->getCollection();

	}

	public static function preparePayments(array $input): ?\Collection {

		$fw = new \FailWatch();
		$paymentType = POST('paymentType');
		$cOperation = new \Collection();

		if(in_array($paymentType, ['incoming-client', 'incoming-supplier', 'outgoing-client', 'outgoing-supplier']) === FALSE) {
			\Fail::log('Operation::payment.typeMissing');
			return NULL;
		}

		$eOperation = new Operation();
		$eOperation->build(['financialYear', 'thirdParty', 'amount', 'paymentDate', 'paymentMethod'], $input);

		$fw->validate();

		$eOperation['thirdParty'] = \account\ThirdPartyLib::getById($eOperation['thirdParty']['id']);

		if(mb_strpos($paymentType, 'client') !== FALSE) {

			$eAccount = \account\AccountLib::getByClass(\account\AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS);
			$thirdPartyType = 'client';

			if($eOperation['thirdParty']['clientAccountLabel'] === NULL) {

				$nextLabel = \account\ThirdPartyLib::getNextThirdPartyAccountLabel('clientAccountLabel', \account\AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS);
				$eOperation['thirdParty']['clientAccountLabel'] = $nextLabel;
				\account\ThirdPartyLib::update($eOperation['thirdParty'], ['clientAccountLabel']);
				$accountLabel = $nextLabel;

			} else {

				$accountLabel = $eOperation['thirdParty']['clientAccountLabel'];

			}

		} else if(mb_strpos($paymentType, 'supplier') !== FALSE) {

			$eAccount = \account\AccountLib::getByClass(\account\AccountSetting::THIRD_ACCOUNT_SUPPLIER_DEBT_CLASS);
			$thirdPartyType = 'supplier';

			if($eOperation['thirdParty']['supplierAccountLabel'] === NULL) {

				$nextLabel = \account\ThirdPartyLib::getNextThirdPartyAccountLabel('supplierAccountLabel', \account\AccountSetting::THIRD_ACCOUNT_SUPPLIER_DEBT_CLASS);
				$eOperation['thirdParty']['supplierAccountLabel'] = $nextLabel;
				\account\ThirdPartyLib::update($eOperation['thirdParty'], ['supplierAccountLabel']);
				$accountLabel = $nextLabel;

			} else {

				$accountLabel = $eOperation['thirdParty']['supplierAccountLabel'];

			}
		}

		if(mb_strpos($paymentType, 'incoming') !== FALSE) {

			$type = Operation::CREDIT;

		} else if(mb_strpos($paymentType, 'outgoing') !== FALSE) {

			$type = Operation::DEBIT;

		}

		$eOperation['account'] = $eAccount;
		$eOperation['accountLabel'] = $accountLabel;
		$eOperation['date'] = $eOperation['paymentDate'];
		$eOperation['type'] = $type;

		$eOperation['description'] = new \account\ThirdPartyUi()->getOperationDescription($eOperation['thirdParty'], $thirdPartyType);

		$cOperation->append($eOperation);
		Operation::model()->insert($eOperation);

		$eOperationBank = clone $eOperation;
		$eOperationBank->offsetUnset('id');
		$eOperationBank['type'] = $type === Operation::CREDIT ? Operation::DEBIT : Operation::CREDIT;
		$eBankAccount = \bank\BankAccountLib::getById($input['bankAccountLabel']);
		$eAccount = \account\AccountLib::getByClass(\account\AccountSetting::BANK_ACCOUNT_CLASS);
		$eOperationBank['accountLabel'] = $eBankAccount->empty() ? \account\ClassLib::pad($eBankAccount['class']) : $eBankAccount['label'];
		$eOperationBank['account'] = $eAccount;
		$eOperationBank['description'] = OperationUi::getDescriptionBank($paymentType);

		$cOperation->append($eOperationBank);
		Operation::model()->insert($eOperationBank);

		// Lettrage
		LetteringLib::letter($eOperation);

		return $cOperation;

	}

	public static function prepareOperations(\farm\Farm $eFarm, array $input, Operation $eOperationDefault, \bank\Cashflow $eCashflow = new \bank\Cashflow()): \Collection {

		$accounts = var_filter($input['account'] ?? [], 'array');
		$vatValues = var_filter($input['vatValue'] ?? [], 'array');
		$invoiceFile = var_filter($input['invoiceFile'] ?? NULL, 'string');
		$invoiceId = var_filter($input['invoice']['id'] ?? NULL, '?int');
		$ePaymentMethodInvoice = var_filter($input['invoice']['paymentMethod'] ?? NULL, 'payment\Method');
		$eFinancialYear = \account\FinancialYearLib::getById($input['financialYear'] ?? NULL);
		$isFromCashflow = $eCashflow->notEmpty();

		$isAccrual = $eFinancialYear->isAccrualAccounting();
		$isCash = $eFinancialYear->isCashAccounting();

		$fw = new \FailWatch();

		if($eFinancialYear->canUpdate() === FALSE) {
			\Fail::log('Operation::FinancialYear.notUpdatable');
			return new \Collection();
		}

		$cAccounts = \account\AccountLib::getByIdsWithVatAccount($accounts);

		$cOperation = new \Collection();
		$cOperationCashflow = new \Collection();
		$properties = [
			'account', 'accountLabel',
			'description', 'amount', 'type', 'document', 'vatRate', 'comment',
		];
		if($eFinancialYear['hasVat']) {
			$properties[] = 'vat';
		}


		$eOperationDefault['thirdParty'] = NULL;
		$eOperationDefault['financialYear'] = $eFinancialYear;

		if($isFromCashflow) {

			if($invoiceId !== NULL) {

				$input['invoice'] = $invoiceId;
				$properties[] = 'invoice';

			}

		} else {

			$properties = array_merge($properties, ['date', 'paymentDate', 'paymentMethod']);

		}

		foreach($accounts as $index => $account) {

			$eOperation = clone $eOperationDefault;
			$eOperation['index'] = $index;
			$eOperation['financialYear'] = $eFinancialYear;

			$input['invoiceFile'] = [$index => $invoiceFile];
			$eOperation->buildIndex($properties, $input, $index);

			if($isFromCashflow) {
				$eOperation->build(['paymentDate', 'paymentMethod'], $input);
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

				$eOperation['thirdParty'] = \account\ThirdPartyLib::getById($thirdParty);

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
					$memos = explode(' ', $eCashflow['memo']);
					if($eOperation['thirdParty']['memos'] === NULL) {
						$eOperation['thirdParty']['memos'] = [];
					}

					foreach($memos as $memo) {
						$loweredMemo = mb_strtolower($memo);
						if(
							mb_strlen($loweredMemo) <= 3
							or in_array($loweredMemo, ['paiement', 'carte', 'votre', 'inst', 'faveur', 'virement', 'emis', 'vers', 'facture', 'remise', 'cheque', 'especes', 'versement', 'prelevement'])
						) {
							continue;
						}

						$textToArray = str_split(str_replace(' ', '', $loweredMemo));
						$numbers = count(array_filter($textToArray, function ($item) { return is_numeric($item); }));

						// On ne garde pas les memo avec plus de 3 chiffres (comme des dates ou des numéros de référence)
						if($numbers >= 3) {
							continue;
						}

						if(isset($eOperation['thirdParty']['memos'][$loweredMemo]) === FALSE) {
							$eOperation['thirdParty']['memos'][$loweredMemo] = 0;
						}
						$eOperation['thirdParty']['memos'][$loweredMemo]++;
					}

				}

				\account\ThirdPartyLib::update($eOperation['thirdParty'], ['vatNumber', 'names', 'memos']);

			} else {

				$eOperation['thirdParty'] = new \account\ThirdParty();

			}

			foreach(['date', 'document', 'documentDate', 'thirdParty'] as $property) {
				if(($eOperationDefault[$property] ?? NULL) === NULL) {
					$eOperationDefault[$property] = $eOperation[$property];
				}
			}

			// Ce type d'écriture a un compte de TVA correspondant
			$eAccount = $cAccounts[$account] ?? new \account\Account();
			$vatValue = var_filter($vatValues[$index] ?? NULL, 'float', 0.0);
			$hasVatAccount = (
				$eFinancialYear['hasVat']
				and $eAccount->exists()
				and $eAccount['vatAccount']->exists()
				and (
					$vatValue !== 0.0
					// Cas où on enregistre quand même une entrée de TVA à 0% : Si c'est explicitement indiqué dans eAccount.
					or $eAccount['vatRate'] === 0.0
				)
			);
			if($hasVatAccount === TRUE) {
				$eOperation['vatAccount'] = $eAccount['vatAccount'];
			}

			$fw->validate();

			$eOperation['journalCode'] = \account\AccountLib::getJournalCodeByClass($eOperation['accountLabel']);

			// Immo : vérification et création
			$eAsset = \asset\AssetLib::prepareAsset($eOperation, $input['asset'][$index] ?? [], $index);

			$fw->validate();

			$eOperation['asset'] = $eAsset;

			\journal\Operation::model()->insert($eOperation);
			$cOperation->append($eOperation);
			if($isFromCashflow) {
				$cOperationCashflow->append(new OperationCashflow([
					'operation' => $eOperation,
					'cashflow' => $eCashflow,
					'amount' => min($eOperation['amount'], abs($eCashflow['amount']))
				]));
			}

			// Ajout de l'entrée de compte de TVA correspondante
			if($hasVatAccount === TRUE) {

				$eOperationVat = \journal\OperationLib::createVatOperation(
					$eOperation,
					$eAccount,
					$input['vatValue'][$index],
					$isFromCashflow === TRUE
						? [
							'date' => $eCashflow['date'],
							'description' => $eOperation['description'] ?? $eCashflow['memo'],
							'cashflow' => $eCashflow,
							'paymentMethod' => $eOperation['paymentMethod'],
							'journalCode' => $eOperation['journalCode'],
						]
						: $eOperation->getArrayCopy(),
					eCashflow: $eCashflow,
				);

				$cOperation->append($eOperationVat);
			}

			// En cas de comptabilité à l'engagement : création de l'entrée en 401 ou 411
			// Et vérification si un lettrage est possible
			if($isAccrual and $eOperation['thirdParty']->notEmpty()) {

				$amount = $eOperation['amount'] + ($hasVatAccount ? $eOperationVat['amount'] : 0);

				$eThirdParty = \account\ThirdPartyLib::getById($thirdParty);
				$isChargeOperation = mb_substr($eOperation['accountLabel'], 0, 1) === (string)\account\AccountSetting::CHARGE_ACCOUNT_CLASS;
				$isProductOperation = mb_substr($eOperation['accountLabel'], 0, 1) === (string)\account\AccountSetting::PRODUCT_ACCOUNT_CLASS;

				// Classe 6 => Fournisseur
				if($isChargeOperation) {

					$description = new \account\ThirdPartyUi()->getOperationDescription($eThirdParty, 'supplier');
					$eAccountThirdParty = \account\AccountLib::getByClass(\account\AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS);

					if($eThirdParty['supplierAccountLabel'] === NULL) {

						$accountLabel = \account\ThirdPartyLib::getNextThirdPartyAccountLabel('supplierAccountLabel', \account\AccountSetting::THIRD_ACCOUNT_SUPPLIER_DEBT_CLASS);
						$eThirdParty['supplierAccountLabel'] = $accountLabel;
						\account\ThirdPartyLib::update($eThirdParty, ['supplierAccountLabel']);

					} else {

						$accountLabel = $eThirdParty['supplierAccountLabel'];

					}

					// Classe 7 => Client
				} else if($isProductOperation) {

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

					throw new \Exception('Unable to register a 401 or 411 operation.');

				}

				// On en profite pour affecter le numéro de accountLabel aux tiers
				$eOperationThirdParty = new Operation(
					[
						'thirdParty' => $eThirdParty,
						'date' => $eOperation['date'],
						'document' => $eOperation['document'],
						'documentDate' => $eOperation['documentDate'],
						'amount' => $amount,
						'account' => $eAccountThirdParty,
						'type' => $eOperation['type'] === Operation::CREDIT ? Operation::DEBIT : Operation::CREDIT,
						'accountLabel' => $accountLabel,
						'description' => $description,
						'financialYear' => $eFinancialYear,
					]
				);

				\journal\Operation::model()->insert($eOperationThirdParty);

				// On tente de le lettrage
				LetteringLib::letter($eOperationThirdParty);

			}
		}

		if($isCash) {

			// Si toutes les écritures sont sur le même document, on utilise aussi celui-ci pour l'opération bancaire;
			$documents = $cOperation->getColumn('document');
			$uniqueDocuments = array_unique($documents);
			if(count($uniqueDocuments) === 1 and count($documents) === $cOperation->count()) {
				$document = first($uniqueDocuments);
			} else {
				$document = NULL;
			}

			// Ajout de la transaction sur la classe de compte bancaire 512
			if($isFromCashflow === TRUE) {

				// Crée automatiquement l'operationCashflow correspondante
				$eOperationBank = \journal\OperationLib::createBankOperationFromCashflow(
					$eCashflow,
					$eOperationDefault,
					$document,
				);
				$cOperation->append($eOperationBank);

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

	public static function createVatOperation(Operation $eOperationLinked, \account\Account $eAccount, float $vatValue, array $defaultValues, \bank\Cashflow $eCashflow): Operation {

		$values = [
			...$defaultValues,
			'account' => $eAccount['vatAccount']['id'] ?? NULL,
			'accountLabel' => \account\ClassLib::pad($eAccount['vatAccount']['class']),
			'document' => $eOperationLinked['document'],
			'thirdParty' => $eOperationLinked['thirdParty']['id'] ?? NULL,
			'type' => $eOperationLinked['type'],
			'paymentDate' => $eOperationLinked['paymentDate'],
			'paymentMethod' => $eOperationLinked['paymentMethod']['id'] ?? NULL,
			'amount' => abs($vatValue),
			'financialYear' => $eOperationLinked['financialYear']['id'],
		];

		$eOperationVat = new Operation();

		$fw = new \FailWatch();

		$eOperationVat->build(
			[
				'financialYear',
				'date', 'account', 'accountLabel', 'description', 'document', 'journalCode',
				'thirdParty', 'type', 'amount', 'operation',
				'paymentDate', 'paymentMethod',
			],
			$values,
			new \Properties('create'),
		);
		$eOperationVat['operation'] = $eOperationLinked;
		if($eOperationLinked['document'] !== NULL) {
			$eOperationVat['documentDate'] = new \Sql('NOW()');
		}

		$fw->validate();

		Operation::model()->insert($eOperationVat);

		if($eCashflow->notEmpty()) {

			$eOperationCashflow = new OperationCashflow([
				'operation' => $eOperationVat,
				'cashflow' => $eCashflow,
				'amount' => min($eOperationVat['amount'], abs($eCashflow['amount'])),
			]);

			OperationCashflow::model()->insert($eOperationCashflow);

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

		Operation::model()
			->whereOperation($e)
			->delete();

		parent::delete($e);

		\journal\Operation::model()->commit();

		OperationCashflow::model()
			->whereOperation($e)
			->delete();

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
	public static function getOperationsForAttach(\bank\Cashflow $eCashflow): \Collection {

		$amount = abs($eCashflow['amount']);

		$properties = Operation::getSelection()
			+ ['account' => ['class', 'description']]
			+ ['thirdParty' => \account\ThirdParty::getSelection()];

		$cOperation = self::addOpenFinancialYearCondition()
			->select($properties)
			->join(OperationCashflow::model(), 'm1.id = m2.operation', 'LEFT')
			->where('m2.id IS NULL')
			->where('m1.operation is null')
			->sort(['date' => SORT_ASC])
			->getCollection();

		$cOperationLinked = $cOperation->empty() === FALSE ? Operation::model()
			->select($properties)
			->join(OperationCashflow::model(), 'm1.id = m2.operation', 'LEFT')
			->where('m2.id IS NULL')
			->where('m1.operation IN ('.join(',', $cOperation->getIds()).')')
			->getCollection() : new \Collection();

		// Tri pour optimiser le montant
		foreach($cOperation as &$eOperation) {
			$eOperation['links'] = new \Collection();
			$sum = 0;
			foreach($cOperationLinked as $eOperationLinked) {
				if($eOperationLinked['operation']['id'] === $eOperation['id']) {
					$sum += $eOperationLinked['amount'];
					$eOperation['links']->append($eOperationLinked);
				}
			}
			$eOperation['totalVATIncludedAmount'] = $eOperation['amount'] + $sum;
			$eOperation['difference'] = abs($eOperation['totalVATIncludedAmount'] - $amount);
		}

		return $cOperation;

	}

	public static function countByCashflow(\bank\Cashflow $eCashflow): int {

		return OperationCashflow::model()
			->whereCashflow($eCashflow)
			->count();

	}

	public static function attachIdsToCashflow(\bank\Cashflow $eCashflow, array $operationIds, \account\ThirdParty $eThirdParty, \Collection $cPaymentMethod): int {

		// Get the operations AND linked Operations
		$cOperation = Operation::model()
			->select(Operation::getSelection())
			->or(
				fn() => $this->whereId('IN', $operationIds),
				fn() => $this->whereOperation('IN', $operationIds),
			)
			->getCollection();
		$properties = ['updatedAt', 'paymentDate'];
		$eOperation = new Operation([
			'updatedAt' => Operation::model()->now(),
			'paymentDate' => $eCashflow['date'],
		]);

		$updated = self::addOpenFinancialYearCondition()
			->select($properties)
			->whereId('IN', $cOperation->getIds())
			->update($eOperation);

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
		]));

		return $updated;
	}

	public static function createBankOperationFromCashflow(\bank\Cashflow $eCashflow, Operation $eOperation, ?string $document = NULL): Operation {

		$eAccountBank = \account\AccountLib::getByClass(\account\AccountSetting::BANK_ACCOUNT_CLASS);

		$eThirdParty = $eOperation['thirdParty'] ?? new \account\ThirdParty();

		if($eCashflow['import']['account']['label'] !== NULL) {
			$label = $eCashflow['import']['account']['label'];
		} else {
			$label = \account\ClassLib::pad(\account\AccountSetting::DEFAULT_BANK_ACCOUNT_LABEL);
		}

		$values = [
			'date' => $eCashflow['date'],
			'account' => $eAccountBank['id'] ?? NULL,
			'accountLabel' => $label,
			'description' => $eCashflow['memo'],
			'document' => $document,
			'thirdParty' => $eThirdParty['id'] ?? NULL,
			'type' => match($eCashflow['type']) {
				\bank\Cashflow::CREDIT => Operation::DEBIT,
				\bank\Cashflow::DEBIT => Operation::CREDIT,
			},
			'amount' => abs($eCashflow['amount']),
			'paymentDate' => $eCashflow['date'],
			'paymentMethod'=> $eOperation['paymentMethod']['id'] ?? NULL,
			'financialYear'=> $eOperation['financialYear']['id'],
			'journalCode' => \account\AccountLib::getJournalCodeByClass($label),
		];

		$eOperationBank = new Operation();

		$fw = new \FailWatch();

		$eOperationBank->build([
			'financialYear', 'date', 'account', 'accountLabel', 'description', 'document', 'thirdParty', 'type', 'amount',
			'operation', 'paymentDate', 'paymentMethod', 'journalCode',
		], $values, new \Properties('create'));

		if($document !== NULL) {
			$eOperationBank['documentDate'] = new \Sql('NOW()');
		}

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

	public static function createFromValues(array $values, \bank\Cashflow $eCashflow = new \bank\Cashflow()): void {

		$eOperation = new Operation();

		$fw = new \FailWatch();

		$eOperation->build(
			[
				'financialYear', 'date',
				'operation', 'asset', 'thirdParty',
				'account', 'accountLabel',
				'description', 'type', 'amount',
				'document', 'documentDate',
				'vatRate', 'vatAccount',
				'journalCode'
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
	}

	public static function unlinkCashflow(\bank\Cashflow $eCashflow, string $action): void {

		if($eCashflow->exists() === FALSE) {
			return;
		}

		Operation::model()->beginTransaction();

		$cOperationCashflow = OperationCashflow::model()
			->select(['operation' => ['asset']])
			->whereCashflow($eCashflow)
			->getCollection();

		// Suppression de  l'écriture sur le compte 512 (banque) (qui est créée automatiquement)
		\journal\Operation::model()
			->whereId('IN', $cOperationCashflow->getColumnCollection('operation')->getIds())
			->whereAccountLabel('LIKE', \account\AccountSetting::DEFAULT_BANK_ACCOUNT_LABEL.'%')
      ->delete();

		// Dissociation cashflow <-> operation
		OperationCashflow::model()
			->whereCashflow($eCashflow)
			->delete();

		if($action === 'delete') {

			// Suppression des immos
			$cAsset = $cOperationCashflow->getColumnCollection('operation')->getColumnCollection('asset');
			if($cAsset->empty() === FALSE) {
				\asset\AssetLib::deleteByIds($cAsset->getIds());
			}
			// Suppression des écritures
			\journal\Operation::model()
	      ->whereId('IN', $cOperationCashflow->getColumnCollection('operation')->getIds())
	      ->delete();

		}

		\account\LogLib::save('unlinkCashflow', 'Operation', ['id' => $eCashflow['id'], 'action' => $action]);

		Operation::model()->commit();

	}

	public static function countGroupByThirdParty(): \Collection {

		return Operation::model()
			->select(['count' => new \Sql('COUNT(*)', 'int'), 'thirdParty'])
			->group('thirdParty')
			->getCollection(NULL, NULL, 'thirdParty');

	}

	public static function getLabels(string $query, ?int $thirdParty, ?int $account): array {

		$labels = Operation::model()
			->select(['accountLabel' => new \Sql('DISTINCT(accountLabel)')])
			->whereThirdParty($thirdParty, if: $thirdParty !== NULL)
			->whereAccount($account, if: $account !== NULL)
			->where('accountLabel LIKE "%'.$query.'%"', if: $query !== '')
			->sort(['accountLabel' => SORT_ASC])
			->getCollection()
			->getColumn('accountLabel');

		return $labels;

	}

	public static function countByAccounts(\Collection $cAccount): \Collection {

		return Operation::model()
			->select([
				'count' => new \Sql('COUNT(*)', 'int'),
				'account'
			])
			->whereAccount('IN', $cAccount->getIds())
			->group('account')
			->getCollection(NULL, NULL, 'account');

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

	public static function getWaiting(\account\ThirdParty $eThirdParty): \Collection {

		$search = new \Search([
			'thirdParty' => $eThirdParty['id'],
			'accountLabels' => [\account\AccountSetting::THIRD_ACCOUNT_SUPPLIER_DEBT_CLASS, \account\AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS]
		]);

		return self::applySearch($search)
			->select(Operation::getSelection() + ['cLetteringCredit' => LetteringLib::delegate('credit'), 'cLetteringDebit' => LetteringLib::delegate('debit')])
			->or(
				fn() => $this->whereLetteringStatus(NULL),
				fn() => $this->whereLetteringStatus('!=', Operation::TOTAL),
			)
			->sort(['date' => SORT_ASC, 'm1.id' => SORT_ASC])
			->getCollection();

	}

	private static function getExtension(string $mimeType): ?string {

		return match($mimeType) {
			'image/jpeg' => 'jpeg',
			'image/png' => 'png',
			'image/gif' => 'gif',
			'application/pdf' => 'pdf',
			default => null,
		};

	}

	public static function readInvoice(\farm\Farm $eFarm, array $file): array {

		$hash = \media\UtilLib::generateHash();
		$extension = self::getExtension($file['type']);
		if($extension === NULL) {
			\Fail::log('Operation::invoice.unknownExtension');
			return [];
		}
		$localFilename = date('Y-m-d-').$hash.'.pdf';

		// Copie du fichier
		\storage\DriverLib::sendBinary(file_get_contents($file['tmp_name']), self::TMP_INVOICE_FOLDER.'/'.$localFilename);

		// Lecture sur Mindee
		$operation = \company\MindeeLib::getInvoiceData($eFarm, \storage\DriverLib::getFileName(self::TMP_INVOICE_FOLDER.'/'.$localFilename));

		// Récupération du tiers
		$operation['eThirdParty'] = \account\ThirdPartyLib::selectFromOcrData($operation['thirdParty']);

		if(count($operation['shipping']) > 0) {
			$operation['shipping']['account'] = \account\AccountLib::getByClass(\account\AccountSetting::SHIPPING_CHARGE_ACCOUNT_CLASS);
			if($operation['shipping']['vatRate'] !== NULL) {
				$operation['shipping']['account']['vatRate'] = $operation['shipping']['vatRate'];
			}
		}

		$operation['mimetype'] = $file['type'];
		$operation['filename'] = $localFilename;
		$operation['filepath'] = \storage\DriverLib::getFileName(self::TMP_INVOICE_FOLDER.'/'.$localFilename);

		return $operation;
	}

	public static function cleanInvoices(): void {

		$folder = \storage\DriverLib::getFileName(self::TMP_INVOICE_FOLDER);
		$command = 'ls '.$folder;

		exec($command, $files);

		foreach($files as $file) {
			if(mb_substr($file, 0, 11) !== date('Y-m-d')) {
				unlink($folder.'/'.$file);
			}
		}
	}

	public static function saveInvoiceToDropbox(?string $filename, \Collection $cOperation): void {

		if($cOperation->count() === 0) {
			return;
		}

		$eOperation = $cOperation->first();

		if($eOperation->empty()) {
			return;
		}

		if($eOperation['document'] === NULL) {
			return;
		}

		$filepath = \storage\DriverLib::getFileName(self::TMP_INVOICE_FOLDER.'/'.$filename);
		if($filename === NULL or is_file($filepath) === FALSE) {
			return;
		}

		$eFinancialYear = \account\FinancialYearLib::selectDefaultFinancialYear();

		if($eOperation['thirdParty']->notEmpty()) {
			$thirdPartyQName = '-'.toFqn($eOperation['thirdParty']['name']);
		} else {
			$thirdPartyQName = '';
		}

		$extension = mb_substr($filename, mb_stripos($filename, '.') + 1);
		$newFilename = '/'.mb_substr($eFinancialYear['startDate'], 0, 4)
			.'/'.mb_substr($eOperation['date'], 5, 2)
			.'/'.$eOperation['date'].'-'.$eOperation['document'].$thirdPartyQName.'.'.$extension;

		\account\DropboxLib::uploadFile($newFilename, $filepath);

		self::updateDocumentStorage($newFilename, $cOperation);

	}

	public static function updateDocumentStorage(string $storage, \Collection $cOperation): void {

		$eOperation = new Operation(['documentStorage' => $storage]);

		Operation::model()
			->select(['documentStorage'])
			->where('id IN ('.join(', ', $cOperation->getIds()).')')
			->update($eOperation);

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
			->where(new \Sql('SUBSTRING(accountLabel, 1, 3) NOT IN ("'.join('", "', [\account\AccountSetting::PREPAID_EXPENSE_CLASS, \account\AccountSetting::ACCRUED_EXPENSE_CLASS]).'")'))
			->sort(['accountLabel' => SORT_ASC])
			->group(['account', 'accountLabel'])
			->having(new \Sql('ABS(total) > 0.5'))
			->getCollection();

	}

	/**
	 * @param \Collection $cOperation Opérations sommées par compte de l'exercice précédent
	 * @param \account\FinancialYear $eFinancialYear Exercice sur lequel écrire les opérations d'ouverture
	 * @param \account\FinancialYear $eFinancialYearPrevious Exercice sur lequel sont basées les opérations $cOperation
	 */
	public static function createForOpening(\Collection $cOperation, \account\FinancialYear $eFinancialYear, \account\FinancialYear $eFinancialYearPrevious): void {

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
				'description' => new \account\FinancialYearUi()->getOpeningDescription($eFinancialYearPrevious['endDate']),
				'journalCode' => Operation::OD,
				'document' => 'OUV-'.str_pad($number, 4, '0', STR_PAD_LEFT),
				'documentDate' => $eFinancialYear['startDate'],
			];

			self::createFromValues($values);

			$number++;

		}

	}

	protected static function checkSelectedOperationsForBatch(\Collection $cOperation): bool {

		// On vérifie que toute les opérations liées sont aussi dans la liste
		$cOperationLinked = Operation::model()
			->select('id')
			->whereOperation('IN', $cOperation->getIds())
			->getCollection();

		if(array_diff($cOperationLinked->getIds(), $cOperation->getIds())) {
			\Fail::log('Operation::selectedOperationInconsistency');
			return FALSE;
		}

		return TRUE;
	}

	public static function updateJournalCodeCollection(\Collection $cOperation, ?string $journalCode): void {

		if(self::checkSelectedOperationsForBatch($cOperation) === FALSE) {
			return;
		}

		if(in_array($journalCode, Operation::model()->getPropertyEnum('journalCode')) === FALSE and $journalCode !== '') {
			\Fail::log('Operation::selectedJournalCodeInconsistency');
			return;
		}

		if($journalCode === "") {
			$journalCode = NULL;
		}

		Operation::model()
			->select(['journalCode'])
			->whereId('IN', $cOperation->getIds())
			->update(new Operation(['journalCode' => $journalCode]));
	}

	public static function updateCommentCollection(\Collection $cOperation, ?string $comment): void {

		if(self::checkSelectedOperationsForBatch($cOperation) === FALSE) {
			return;
		}

		if($comment === "") {
			$comment = NULL;
		}

		Operation::model()
			->select(['comment'])
			->whereId('IN', $cOperation->getIds())
			->update(new Operation(['comment' => $comment]));
	}

	public static function updateDocumentCollection(\Collection $cOperation, ?string $document): void {

		if(self::checkSelectedOperationsForBatch($cOperation) === FALSE) {
			return;
		}

		if($document === "") {
			$document = NULL;
		}

		Operation::model()
			->select(['document'])
			->whereId('IN', $cOperation->getIds())
			->update(new Operation(['document' => $document]));
	}

	public static function updatePaymentMethodCollection(\Collection $cOperation, \payment\Method $ePaymentMethod): void {

		if(self::checkSelectedOperationsForBatch($cOperation) === FALSE) {
			return;
		}

		Operation::model()
			->select(['paymentMethod'])
			->whereId('IN', $cOperation->getIds())
			->update(new Operation(['paymentMethod' => $ePaymentMethod]));

	}
}
?>
