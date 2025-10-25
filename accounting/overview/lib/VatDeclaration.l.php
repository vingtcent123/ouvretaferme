<?php
namespace overview;

Class VatDeclarationLib extends VatDeclarationCrud {

	const DELAY_UPDATABLE_AFTER_LIMIT_IN_DAYS = 5;
	const DELAY_OPEN_BEFORE_LIMIT_IN_DAYS = 15;

	public static function declare(VatDeclaration $eVatDeclaration): void {

		VatDeclaration::model()
			->update(
				$eVatDeclaration,
				['status' => VatDeclaration::DECLARED, 'declaredAt' => new \Sql('NOW()'), 'declaredBy' => \user\ConnectionLib::getOnline()],
			);

	}
	public static function getHistory(\account\FinancialYear $eFinancialYear): \Collection {

		return VatDeclaration::model()
			->select(VatDeclaration::getSelection())
			->whereFinancialYear($eFinancialYear)
			->whereStatus('IN', [VatDeclaration::DRAFT, VatDeclaration::DECLARED])
			->sort(['updatedAt' => SORT_DESC])
			->getCollection();

	}

	public static function getByDates(string $from, string $to): VatDeclaration {

		return VatDeclaration::model()
			->select(VatDeclaration::getSelection())
			->whereStatus('IN', [VatDeclaration::DRAFT, VatDeclaration::DECLARED])
			->whereFrom($from)
			->whereTo($to)
			->get();

	}
	public static function saveCerfa(\account\FinancialYear $eFinancialYear, string $from, string $to, array $data, string $limit): void {

		if($eFinancialYear['vatFrequency'] === \account\FinancialYear::ANNUALLY) {
			$type = VatDeclaration::CA12;
		} else {
			$type = VatDeclaration::CA3;
		}

		$eVatDeclaration = new VatDeclaration([
			'from' => $from,
			'to' => $to,
			'associates' => $eFinancialYear['associates'],
			'limit' => $limit, // Sauvegardé à titre historique
			'cerfa' => $type,
			'data' => $data,
			'financialYear' => $eFinancialYear,
			'updatedAt' => new \Sql('NOW()'),
			'updatedBy' => \user\ConnectionLib::getOnline(),
		]);

		$affected = VatDeclaration::model()
			->select('cerfa', 'data', 'updatedAt', 'updatedBy')
			->whereStatus(VatDeclaration::DRAFT)
			->whereFrom($eVatDeclaration['from'])
			->whereTo($eVatDeclaration['to'])
			->update($eVatDeclaration);

		if($affected === 0) {
			VatDeclaration::model()->insert($eVatDeclaration);
		}

	}

}
