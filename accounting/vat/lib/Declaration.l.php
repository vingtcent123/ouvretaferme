<?php
namespace vat;

Class DeclarationLib extends DeclarationCrud {

	/**
	 * @return \Collection<Declaration>
	 */
	public static function getAll(array $froms): \Collection {

		if(empty($froms)) {
			return new \Collection();
		}

		return Declaration::model()
			->select(Declaration::getSelection())
			->whereFrom('IN', $froms)
			->getCollection(index: 'from');

	}

	/**
	 * @return \Collection<Declaration>
	 */
	public static function getHistory(\account\FinancialYear $eFinancialYear): \Collection {

		return Declaration::model()
			->select(Declaration::getSelection())
			->whereFinancialYear($eFinancialYear)
			->sort(['updatedAt' => SORT_DESC])
			->getCollection();

	}

	public static function getByDates(string $from, string $to): Declaration {

		return Declaration::model()
			->select(Declaration::getSelection())
			->whereFrom($from)
			->whereTo($to)
			->get();

	}

	public static function getPrevious(Declaration $eDeclaration): Declaration {

		return Declaration::model()
			->select(Declaration::getSelection())
			->whereTo(date('Y-m-d', strtotime($eDeclaration['from'].' - 1 day')))
			->get();

	}

	public static function getCerfaFromFrequency(string $frequency): string {

		if($frequency === \farm\Configuration::ANNUALLY) {
			return Declaration::CA12;
		}

		return Declaration::CA3;

	}

	public static function saveCerfa(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, string $from, string $to, array $data, string $limit): void {

		$eDeclaration = new Declaration([
			'from' => $from,
			'to' => $to,
			'associates' => $eFinancialYear['associates'],
			'limit' => $limit, // Sauvegardé à titre historique
			'cerfa' => self::getCerfaFromFrequency($eFarm->getConf('vatFrequency')),
			'data' => $data,
			'status' => Declaration::DRAFT,
			'financialYear' => $eFinancialYear,
			'updatedAt' => new \Sql('NOW()'),
			'declaredAt' => NULL,
			'updatedBy' => \user\ConnectionLib::getOnline(),
		]);

		Declaration::model()->option('add-replace')->insert($eDeclaration);

	}

	public static function update(Declaration $e, array $properties): void {

		Declaration::model()->beginTransaction();

			if(in_array('status', $properties)) {

				switch($e['status']) {

					case Declaration::DECLARED:
						$e['declaredAt'] = new \Sql('NOW()');
						$e['declaredBy'] = \user\ConnectionLib::getOnline();
						$properties[] = 'declaredAt';
						$properties[] = 'declaredBy';
						break;

					case Declaration::ACCOUNTED:
						$e['accountedAt'] = new \Sql('NOW()');
						$e['accountedBy'] = \user\ConnectionLib::getOnline();
						$properties[] = 'accountedAt';
						$properties[] = 'accountedBy';
						break;

					case Declaration::PAID:
						$e['paidAt'] = new \Sql('NOW()');
						$e['paidBy'] = \user\ConnectionLib::getOnline();
						$properties[] = 'paidAt';
						$properties[] = 'paidBy';
						break;

				}

			}

			$e['updatedAt'] = new \Sql('NOW()');
			$e['updatedBy'] = \user\ConnectionLib::getOnline();
			$properties[] = 'updatedAt';
			$properties[] = 'updatedBy';


			parent::update($e, $properties);

		Declaration::model()->commit();

	}

}
