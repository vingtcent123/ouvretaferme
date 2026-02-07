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
					->select(['id', 'hash', 'accountLabel', 'asset', 'type', 'amount', 'number', 'financialYear' => ['id', 'status', 'closeDate']])
					->delegateCollection('hash', propertyParent: 'hash'),
				'invoice' => ['id', 'number', 'document', 'customer' => ['id', 'name']],
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

	public static function getForCash(\payment\Method $eMethod, string $dateAfter): \Collection {

		switch($eMethod['fqn']) {

			case \payment\MethodLib::CASH :

				Cashflow::model()->or(
					fn() => $this->whereMemo('LIKE', 'vrst %'),
					fn() => $this->whereMemo('LIKE', 'versement %'),
					fn() => $this->whereMemo('LIKE', 'vers%espece%'),
					fn() => $this->whereMemo('LIKE', 'ret%espece%'),
					fn() => $this->whereMemo('LIKE', 'ret%dab%'),
					fn() => $this->whereMemo('LIKE', 'ret%distrib%'),
				);

				break;

			case \payment\MethodLib::CHECK :

				Cashflow::model()->or(
					fn() => $this->whereMemo('LIKE', 'rem%cheq'),
					fn() => $this->whereMemo('LIKE', 'rem%chq'),
				);

				break;

			default :
				return new \Collection();

		}

		return Cashflow::model()
			->select([
				'id',
				'date',
				'source' => fn() => \cash\Cash::BANK,
				'type' => fn($e) => match($e['type']) {
					Cashflow::CREDIT => \cash\Cash::DEBIT,
					Cashflow::DEBIT => \cash\Cash::CREDIT,
				},
				'amountIncludingVat' => new \Sql('amount'),
				'description' => new \Sql('memo')
			])
			->whereStatus('!=', Cashflow::DELETED)
			->whereStatusCash(Cashflow::WAITING)
			->whereDate('>', $dateAfter)
			->getCollection();

	}

	public static function deleteCasfhlow(Cashflow $eCashflow): void {

		$eCashflow->expects(['id']);

		Cashflow::model()->beginTransaction();

			Cashflow::model()
				->whereStatus(Cashflow::WAITING)
				->whereId($eCashflow['id'])
				->update(['status' => Cashflow::DELETED]);

			\preaccounting\Suggestion::model()->whereCashflow($eCashflow)->delete();

		Cashflow::model()->commit();
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

			$words = array_filter(preg_split('/\s+/', $query));

			if(count($words) > 0) {

				foreach($words as $word) {
					$keywords[] = '*'.$word.'*';
				}

				$match = 'MATCH(memo, name) AGAINST ('.Cashflow::model()->format(implode(' ', $keywords)).' IN BOOLEAN MODE)';

				Cashflow::model()->where($match.' > 0');
			}

		}

		return Cashflow::model()
			->select(Cashflow::getSelection())
			->whereAmount('>=', ($totalAmount - 1))
			->whereAmount('<=', ($totalAmount + 1))
			->whereHash('=', NULL)
			->getCollection();

	}

	public static function applySimilarCashflowSearch(Cashflow $eCashflow): CashflowModel {

		$query = trim(preg_replace('/[+\-><\(\)~*\"@]+/', ' ', $eCashflow['memo'])).' '.
			trim(preg_replace('/[+\-><\(\)~*\"@]+/', ' ', $eCashflow['name']))
		;

		$keywords = [];

		$words = array_filter(preg_split('/\s+/', $query));

		if(count($words) > 0) {

			foreach($words as $word) {
				$keywords[] = '*'.$word.'*';
			}

			$match = 'MATCH(memo, name) AGAINST ('.Cashflow::model()->format(implode(' ', $keywords)).' IN BOOLEAN MODE)';

			Cashflow::model()->where($match.' > 0');
		}

		return Cashflow::model()
      ->where($match.' > 0')
			->whereAmount($eCashflow['amount'])
			->whereType($eCashflow['type'])
			->whereId('!=', $eCashflow['id'])
			->whereHash('!=', NULL);

	}

	public static function getSimilarAffectedCashflows(Cashflow $eCashflow): \Collection {

		$operationSelection = \journal\Operation::getSelection();
		unset($operationSelection['cashflow']);
		unset($operationSelection['cOperationCashflow']);
		$cCashflow = self::applySimilarCashflowSearch($eCashflow)
			->select(array_merge(Cashflow::getSelection(), [
				'cOperationCashflow' => \journal\OperationCashflow::model()
					->select([
						'cashflow' => ['id', 'hash', 'amount', 'hash', 'invoice', 'type', 'date', 'memo', 'account'],
						'operation' => $operationSelection])
					->delegateCollection('cashflow')
			]))
			->getCollection();

		$cCashflowFormatted = new \Collection();

		// Les structures trouvées avec une clé qui regroupe n° compte + montants
		$schemas = [];

		foreach($cCashflow as $eCashflow) {
			$cOperation = new \Collection();

			foreach($eCashflow['cOperationCashflow'] as $eOperationCashflow) {
				$eOperationCashflow['operation']['cashflow'] = $eOperationCashflow['cashflow'];
				$cOperation->append($eOperationCashflow['operation']);
			}

			$accountLabels = $cOperation->getColumn('accountLabel');
			sort($accountLabels);

			$key = join('-', $accountLabels);

			$amounts = $cOperation->getColumn('amount');
			sort($amounts);

			$key .= '|'.join('-', $amounts);

			if(in_array($key, $schemas) === FALSE) {

				$schemas[] = $key;

				$cOperation->sort('id');
				$eCashflow['cOperation'] = $cOperation;
				$cCashflowFormatted->append($eCashflow);

			}

		}

		return $cCashflowFormatted;
	}

}
?>
