<?php
namespace bank;

class CashflowLib extends CashflowCrud {

	public static function getImportData(Cashflow $eCashflow): void {

		$eCashflow['import'] = \bank\Import::model()
			->select(\bank\Import::getSelection() + ['account' => \bank\BankAccount::getSelection()])
			->whereId($eCashflow['import']['id'])
			->get();

	}

	public static function countSuggestionWaitingByImport(Import $eImport): int {

		return Cashflow::model()
			->whereIsSuggestionCalculated(FALSE)
			->whereImport($eImport)
			->count();

	}
	public static function applySearch(\Search $search): CashflowModel {

		if(($search->has('amountMin') and $search->has('amountMax'))) {
			Cashflow::model()
				->or(
					fn() => $this->where('amount BETWEEN '.$search->get('amountMin').' AND '.$search->get('amountMax'), if: ($search->has('amountMin') and $search->has('amountMax'))),
					fn() => $this->where('amount BETWEEN '.(-1 * $search->get('amountMax')).' AND '.(-1 * $search->get('amountMin')), if: ($search->has('amountMin') and $search->has('amountMax'))),
				);
		}

		if($search->get('financialYear') !== NULL and $search->get('financialYear')->notEmpty()) {
			Cashflow::model()
				->whereDate('>=', fn() => $search->get('financialYear')['startDate'], if: $search->get('financialYear')->notEmpty())
				->whereDate('<=', fn() => $search->get('financialYear')['endDate'], if: $search->get('financialYear')->notEmpty());
		}

		return Cashflow::model()
			->whereId('=', $search->get('id'), if: $search->get('id'))
			->whereImport('=', $search->get('import'), if: $search->has('import') and $search->get('import')->notEmpty())
			->whereDate('LIKE', '%'.$search->get('date').'%', if: $search->get('date'))
			->whereDate('>=', $search->get('from'), if: $search->get('from'))
			->whereDate('<=', $search->get('to'), if: $search->get('to'))
			->whereFitid('LIKE', '%'.$search->get('fitid').'%', if: $search->get('fitid'))
			->whereMemo('LIKE', '%'.mb_strtolower($search->get('memo') ?? '').'%', if: $search->get('memo'))
			->whereCreatedAt('<=', $search->get('createdAt'), if: $search->get('createdAt'))
			->whereIsReconciliated('=', $search->get('isReconciliated'), if: $search->get('isReconciliated') !== NULL)
			->whereType($search->get('type'), if: $search->has('type') and $search->get('type'))
			->whereStatus('=', $search->get('status'), if: $search->get('status'))
			->whereStatus('!=', Cashflow::DELETED, if: empty($search->get('status')))
			->whereAccount('=', fn() => $search->get('bankAccount')['id'], if: $search->get('bankAccount') and $search->get('bankAccount')->notEmpty())
		;

	}

	public static function getAll(\Search $search, ?int $page, bool $hasSort): array {

		$maxByPage = 500;
		self::applySearch($search)
			->select(Cashflow::getSelection() + [
				'cOperationHash' => \journal\Operation::model()
					->select('id', 'hash', 'accountLabel', 'financialYear', 'asset')
					->delegateCollection('hash', propertyParent: 'hash'),
				'invoice' => ['id', 'name', 'document', 'customer' => ['id', 'name']],
				'sale' => ['id', 'document', 'customer' => ['id', 'name']],
			])
			->option('count')
			->sort($hasSort === TRUE ? $search->buildSort() : ['date' => SORT_DESC, 'fitid' => SORT_DESC]);

		if($page === NULL) {
			$cCashflow = Cashflow::model()->getCollection();
		} else {
			$cCashflow = Cashflow::model()->getCollection($page * $maxByPage, $maxByPage);
		}

		$nCashflow = Cashflow::model()->found();
		$nPage = ceil($nCashflow / $maxByPage);

		return [
			$cCashflow,
			$nCashflow,
			$nPage,
		];
	}

	public static function insertMultiple(\Collection $cCashflow): array {

		$cCashflowAlreadyImported = Cashflow::model()
			->select('fitid')
			->whereFitid('IN', $cCashflow->getColumn('fitid'))
			->getCollection(NULL, NULL, 'fitid');

		$cCashflowFiltered = $cCashflow->find(fn($e) => $cCashflowAlreadyImported->offsetExists($e['fitid']) === FALSE);

		Cashflow::model()->option('add-ignore')->insert($cCashflowFiltered);

		$imported = $cCashflowFiltered->getColumn('fitid');

		return ['alreadyImported' => $cCashflowAlreadyImported->getColumn('fitid'), 'imported' => $imported];

	}


	public static function attach(Cashflow $eCashflow, \Collection $cOperation, \account\ThirdParty $eThirdParty): void {

		if($eCashflow['status'] !== Cashflow::WAITING or \journal\OperationLib::countByCashflow($eCashflow) > 0) {
			throw new \NotExpectedAction('Cashflow #'.$eCashflow['id'].' already attached');
		}

		Cashflow::model()->beginTransaction();

		\journal\OperationLib::attachOperationsToCashflow($eCashflow, $cOperation, $eThirdParty);

		$properties = ['status', 'updatedAt'];
		$eCashflow['status'] = Cashflow::ALLOCATED;
		$eCashflow['updatedAt'] = Cashflow::model()->now();
		$eCashflow['hash'] = $cOperation->first()['hash'];

		Cashflow::model()
			->select($properties)
			->whereId($eCashflow['id'])
			->update($eCashflow->extracts(['status', 'updatedAt', 'hash']));

		Cashflow::model()->commit();

		\account\LogLib::save('attach', 'Cashflow', ['id' => $eCashflow['id'], 'operations' => $cOperation->getIds()]);
	}

	public static function deleteCasfhlow(Cashflow $eCashflow): void {

		$eCashflow->expects(['id']);

		Cashflow::model()
			->whereStatus(Cashflow::WAITING)
			->whereId($eCashflow['id'])
			->update(['status' => Cashflow::DELETED]);
	}

	public static function undeleteCashflow(Cashflow $eCashflow): void {

		$eCashflow->expects(['id']);

		Cashflow::model()
			->whereStatus(Cashflow::DELETED)
			->whereId($eCashflow['id'])
			->update(['status' => Cashflow::WAITING]);
	}

	public static function getForAttachQuery(string $query, \Collection $cOperation): \Collection {

		$totalAmount = array_reduce($cOperation->getArrayCopy(), function ($sum, $element) {
			// Il faut inverser car on va chercher dans le relevé bancaire.
			if($element['type'] === \journal\Operation::CREDIT) {
				$sum += $element['amount'];
			} else {
				$sum -= $element['amount'];
			}
				return $sum;
			});

		if($query !== '') {

			$keywords = [];

			$query = trim(preg_replace('/[+\-><\(\)~*\"@]+/', ' ', $query));

			foreach(preg_split('/\s+/', $query) as $word) {
				$keywords[] = '*'.$word.'*';
			}

			$match = 'MATCH(memo, name) AGAINST ('.Cashflow::model()->format(implode(' ', $keywords)).' IN BOOLEAN MODE)';

			Cashflow::model()->where($match.' > 0');

		}

		return Cashflow::model()
			->select(Cashflow::getSelection())
			->whereAmount('>=', ($totalAmount - 1))
			->whereAmount('<=', ($totalAmount + 1))
			->whereHash('=', NULL)
			->getCollection();

	}

	public static function searchSimilarAffectedCashflows(\Collection $cCashflow): void {

		foreach($cCashflow as &$eCashflow) {
			if($eCashflow['hash'] === NULL) {
				$eCashflow['similar'] = self::countSimilarAffectedCashflows($eCashflow);
			}
		}

	}

	public static function applySimilarCashflowSearch(Cashflow $eCashflow): CashflowModel {

			$query = trim(preg_replace('/[+\-><\(\)~*\"@]+/', ' ', $eCashflow['memo'])).' '.
				trim(preg_replace('/[+\-><\(\)~*\"@]+/', ' ', $eCashflow['name']))
			;

			$keywords = [];
			foreach(preg_split('/\s+/', $query) as $word) {
				$keywords[] = '*'.$word.'*';
			}

			$match = 'MATCH(memo, name, document) AGAINST ('.Cashflow::model()->format(implode(' ', $keywords)).' IN BOOLEAN MODE)';

		return Cashflow::model()
      ->where($match.' > 0')
			->whereAmount($eCashflow['amount'])
			->whereType($eCashflow['type'])
			->whereId('!=', $eCashflow['id'])
			->whereHash('!=', NULL);

	}

	public static function countSimilarAffectedCashflows(Cashflow $eCashflow): int {

		return self::applySimilarCashflowSearch($eCashflow)->count();

	}

	public static function getSimilarAffectedCashflows(Cashflow $eCashflow, ?int $id): array {

		$operationSelection = \journal\Operation::getSelection();
		unset($operationSelection['cashflow']);
		unset($operationSelection['cOperationCashflow']);
		$cCashflow = self::applySimilarCashflowSearch($eCashflow)
			->select(array_merge(Cashflow::getSelection(), [
				'cOperationCashflow' => \journal\OperationCashflow::model()
					->select([
						'id', 'cashflow' => ['id', 'hash', 'amount', 'hash', 'invoice', 'type', 'date', 'memo', 'account'],
						'operation' => $operationSelection])
					->delegateCollection('cashflow', 'id')
			]))
			->whereId($id, if: $id !== NULL)
			->getCollection();

		$schemas = [];

		foreach($cCashflow as $eCashflow) {

			$cOperation = new \Collection();

			foreach($eCashflow['cOperationCashflow'] as $eOperationCashflow) {
				$eOperationCashflow['operation']['cashflow'] = $eOperationCashflow['cashflow'];
				$cOperation->offsetSet($eOperationCashflow['operation']['accountLabel'], $eOperationCashflow['operation']);
			}

			$accountLabels = $cOperation->getColumn('accountLabel');
			sort($accountLabels);

			$key = join('-', $accountLabels);

			$amounts = $cOperation->getColumn('amount');
			sort($amounts);

			$key .= '|'.join('-', $amounts);

			if(isset($schemas[$key]) === FALSE) {
				$schemas[$key] = $cOperation;
			}

		}

		return $schemas;
	}

	public static function createSimilarOperations(\account\FinancialYear $eFinancialYear, Cashflow $eCashflow, Cashflow $eCashflowOrigin, string $key): void {

		$similar = self::getSimilarAffectedCashflows($eCashflow, $eCashflowOrigin['id']);

		if(isset($similar[$key]) === FALSE) {
			return;
		}

		// On ne copie pas l'écriture de banque qui sera copiée dans le attach
		$cOperationCopy = clone $similar[$key]->find(fn($e) => \account\AccountLabelLib::isFromClass($e['accountLabel'], \account\AccountSetting::BANK_ACCOUNT_CLASS) === FALSE);

		$hash = \journal\OperationLib::generateHash().\journal\JournalSetting::HASH_LETTER_WRITE;

		foreach($cOperationCopy as &$eOperation) {
			unset($eOperation['id']);
			$eOperation['date'] = $eCashflow['date'];
			$eOperation['paymentDate'] = $eCashflow['date'];
			$eOperation['hash'] = $hash;
			$eOperation['financialYear'] = $eFinancialYear;
			$eOperation['number'] = NULL;
			$eOperation['createdAt'] = new \Sql('NOW()');
			$eOperation['updatedAt'] = new \Sql('NOW()');
			$eOperation['createdBy'] = \user\ConnectionLib::getOnline();
		}

		if($cOperationCopy->notEmpty()) {

			\journal\Operation::model()->beginTransaction();

				\journal\Operation::model()->insert($cOperationCopy);

				// On re-récupère les opérations avec leurs IDs
				$cOperation = \journal\OperationLib::getByHash($hash);

				// On rattache le cashflow aux opérations
				self::attach($eCashflow, $cOperation, $cOperation->first()['thirdParty']);

				// On doit raccrocher les opérations liées
				$cLinkedOperation = $similar[$key]->find(fn($e) => $e['operation']->notEmpty());

				$cOperationMothers = \journal\OperationLib::getByIds($cLinkedOperation->getColumnCollection('operation')->getIds());

				foreach($cLinkedOperation as $eOperation) {
					$eOperationMother = $cOperationMothers->find(fn($e) => $e['id'] === $eOperation['operation']['id'])->first();

					// On cherche les correspondances dans les copies
					$cOperationMotherCopy = $cOperation->find(function($e) use($eOperationMother) {
						return ($e['amount'] === $eOperationMother['amount'] and
							$e['type'] === $eOperationMother['type'] and
							$e['accountLabel'] === $eOperationMother['accountLabel']);
					});

					$cOperationBabyCopy = $cOperation->find(function($e) use($eOperation) {
						return ($e['amount'] === $eOperation['amount'] and
							$e['type'] === $eOperation['type'] and
							$e['accountLabel'] === $eOperation['accountLabel']);
					});

					if($cOperationMotherCopy->notEmpty() and $cOperationBabyCopy->notEmpty()) {
						\journal\Operation::model()->update($cOperationBabyCopy->first(), ['operation' => $cOperationMotherCopy->first()]);
					}
				}

			\journal\Operation::model()->commit();
		}
	}
}
?>
