<?php
namespace preaccounting;

Class CashLib {

	public static function countForAccounting(\farm\Farm $eFarm, \Search $search, bool $forImport): int {

		return self::filterForAccounting($eFarm, $search, $forImport)->count();

	}

	public static function getForAccounting(\farm\Farm $eFarm, \Search $search, bool $forImport): \Collection {

		return self::filterForAccounting($eFarm, $search, $forImport)
			->select(\cash\Cash::getSelection() + ['payment' => \selling\Payment::getSelection(), 'cashflow' => ['id', 'account' => ['account']]])
			->sort(['date' => SORT_DESC])
			->option('count')
			->getCollection(NULL, NULL, 'id');

	}

	private static function filterForAccounting(\farm\Farm $eFarm, \Search $search, bool $forImport): \cash\CashModel {

		return \cash\Cash::model()
			->whereSource('!=', \cash\Cash::INITIAL)
			->whereStatus(\cash\Cash::VALID)
			->where('date BETWEEN '.\cash\Cash::model()->format($search->get('from')).' AND '.\cash\Cash::model()->format($search->get('to')))
			->where(fn() => 'register IN ('.join(', ', $search->get('cRegisterFilter')->getIds()).')', if: $search->has('cRegisterFilter') and $search->get('cRegisterFilter')->notEmpty())
			->whereCustomer($search->get('customer'), if: $search->has('customer') and $search->get('customer')->notEmpty())
			->whereRegister($search->get('register'), if: $search->has('register') and $search->get('register')->notEmpty())
			->whereAccountingHash(NULL, if: $forImport)
			->whereAccountingReady(TRUE, if: $forImport)
		;
	}

	public static function setAccountingReady(): void {

		$cCash = \cash\Cash::model()
			->select(\cash\Cash::getSelection() + [
				'sale' => \selling\Sale::getSelection(),
				'invoice' => \selling\Invoice::getSelection(),
				'cashflow' => \bank\Cashflow::getSelection() + ['account' => 'account']
			])
			->whereAccountingReady(FALSE)
			->whereStatus(\cash\Cash::VALID)
			->whereSource('!=', \cash\Cash::INITIAL)
			->whereAccountingHash(NULL)
			->getCollection();

		foreach($cCash as $eCash) {

			if($eCash['source'] === \cash\Cash::SELL_INVOICE) {
				$eCash['invoice']['cSale'] = SaleLib::getByInvoiceForFec($eCash['invoice']);
			} else if($eCash['source'] === \cash\Cash::SELL_SALE) {
				$eCash['sale']['cItem'] = ItemLib::getBySaleForFec($eCash['sale']);
			}

			if($eCash->acceptAccountingImport()) {
				\cash\Cash::model()->update($eCash, ['accountingReady' => TRUE]);
			}

		}

	}

}
