<?php
namespace cash;

class ArchiveLib extends ArchiveCrud {

	public static function getPropertiesCreate(): array {
		return ['from', 'to'];
	}

	public static function create(Archive $e): void {

		$output = self::getCsv($e);
		$csv = \util\CsvLib::toCsv($output);
		$csv = trim($csv);

		$e['csv'] = $csv;
		$e['sha256'] = hash('sha256', $csv);

		parent::create($e);

	}

	public static function getCsv(Archive $e): array {

		$cCash = Cash::model()
			->select(CashElement::getSelection() + [
				'register' => [
					'paymentMethod' => ['name']
				],
				'sale' => ['document'],
				'invoice' => ['number']
			])
			->whereDate('BETWEEN', new \Sql(Cash::model()->format($e['from']).' AND '.Cash::model()->format($e['to'])))
			->whereStatus(Cash::VALID)
			->sort([
				'register' => SORT_ASC,
				'position' => SORT_ASC
			])
			->getCollection();

		$output = [
			new ArchiveUi()->getCsvHeader()
		];

		foreach($cCash as $eCash) {

			$output[] = [
				$eCash['register']['id'],
				$eCash['position'],
				$eCash['register']['paymentMethod']['name'],
				CashUi::getText($eCash['source'], $eCash['type']),
				match($eCash['type']) {
					Cash::CREDIT => s("Crédit"),
					Cash::DEBIT => s("Débit"),
				},
				\util\TextUi::csvNumber($eCash['amountExcludingVat']),
				\util\TextUi::csvNumber($eCash['amountIncludingVat']),
				\util\TextUi::csvNumber($eCash['vat']),
				\util\TextUi::csvNumber($eCash['vatRate']),
				ArchiveUi::getReference($eCash),
				\util\TextUi::csvNumber($eCash['balance']),
			];

		}

		return $output;

	}

	public static function getList(): \Collection {

		return Archive::model()
			->select(Archive::getSelection())
			->sort([
				'id' => SORT_DESC
			])
			->getCollection();

	}

}
?>
