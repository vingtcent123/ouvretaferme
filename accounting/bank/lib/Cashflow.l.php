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
			->whereImport('=', $search->get('import'), if: $search->has('import'))
			->whereDate('LIKE', '%'.$search->get('date').'%', if: $search->get('date'))
			->whereDate('>=', $search->get('from'), if: $search->get('from'))
			->whereDate('<=', $search->get('to'), if: $search->get('to'))
			->whereFitid('LIKE', '%'.$search->get('fitid').'%', if: $search->get('fitid'))
			->whereMemo('LIKE', '%'.mb_strtolower($search->get('memo') ?? '').'%', if: $search->get('memo'))
			->whereCreatedAt('<=', $search->get('createdAt'), if: $search->get('createdAt'))
			->whereIsReconciliated('=', $search->get('isReconciliated'), if: $search->get('isReconciliated') !== NULL)
			->where('amount < 0', if: $search->get('direction') and $search->get('direction') === 'debit')
			->where('amount >= 0', if: $search->get('direction') and $search->get('direction') === 'credit')
			->whereStatus('=', $search->get('status'), if: $search->get('status'))
			->whereStatus('!=', Cashflow::DELETED, if: empty($search->get('status')))
			->whereAccount('=', $search->get('bankAccount')['id'], if: $search->get('bankAccount') and $search->get('bankAccount')->notEmpty())
		;

	}

	public static function getAll(\Search $search, ?int $page, bool $hasSort): array {

		$maxByPage = 500;
		self::applySearch($search)
			->select(Cashflow::getSelection() + [
				'cOperationHash' => \journal\Operation::model()
					->select('id', 'financialYear')
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

	public static function getMinMaxDate(): array {

		$eCashflow = Cashflow::model()
			->select([
				'min' => new \Sql('MIN(date)'),
				'max' => new \Sql('MAX(date)'),
			])
			->where(TRUE)
			->get();

		if($eCashflow->empty() or $eCashflow['min'] === NULL) {
			return ['', ''];
		}

		return array_values($eCashflow->getArrayCopy());

	}

	public static function insertMultiple(array $cashflows): array {

		$alreadyImported = [];
		$imported = [];
		$invalidDate = [];

		foreach($cashflows as $cashflow) {

			$isAlreadyImportedTransaction = (Cashflow::model()->whereFitid($cashflow['fitid'])->count() > 0);

			if($isAlreadyImportedTransaction === TRUE) {
				$alreadyImported[] = $cashflow['fitid'];
				continue;
			}

			$type = match($cashflow['type']) {
				'DEBIT' => CashflowElement::DEBIT,
				'CREDIT' => CashflowElement::CREDIT,
				default => $cashflow['amount'] > 0 ? CashflowElement::CREDIT : CashflowElement::DEBIT,
			};
			$date = substr($cashflow['date'], 0, 4).'-'.substr($cashflow['date'], 4, 2).'-'.substr($cashflow['date'], 6, 2);

			if(\util\DateLib::isValid($date) === FALSE) {
				$invalidDate[] = $cashflow['fitid'];
				continue;
			}

			$eCashflow = new Cashflow(
				array_merge(
					$cashflow,
					[
						'type' => $type,
						'date' => $date
					]
				)
			);

			Cashflow::model()->insert($eCashflow);
			$imported[] = $cashflow['fitid'];

		}

		return ['alreadyImported' => $alreadyImported, 'invalidDate' => $invalidDate, 'imported' => $imported];

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
}
?>
