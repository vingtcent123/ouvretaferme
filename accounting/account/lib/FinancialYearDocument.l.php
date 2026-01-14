<?php
namespace account;

Class FinancialYearDocumentLib extends FinancialYearDocumentCrud {

	const BALANCE = 'balance';
	const OPENING = 'opening';
	const OPENING_DETAILED = 'opening-detailed';
	const CLOSING = 'closing';
	const CLOSING_DETAILED = 'closing-detailed';

	const SIG = 'sig';
	const INCOME_STATEMENT = 'income-statement';
	const INCOME_STATEMENT_DETAILED = 'income-statement-detailed';

	public static function getTypes(): array {

		return [
			self::BALANCE,
			self::OPENING, self::OPENING_DETAILED,
			self::CLOSING, self::CLOSING_DETAILED,
			self::SIG,
			self::INCOME_STATEMENT, self::INCOME_STATEMENT_DETAILED,
		];

	}

	public static function hasDocument(FinancialYear $eFinancialYear, string $type): bool {

		if(in_array($type, self::getTypes()) === FALSE) {
			return FALSE;
		}

		if($eFinancialYear['cDocument']->empty()) {
			return FALSE;
		}

		$cFinancialYearDocument = $eFinancialYear['cDocument']->find(fn($eFinancialYearDocument) => $eFinancialYearDocument['type'] === $type);
		if($cFinancialYearDocument->empty()) {
			return FALSE;
		}

		$eFinancialYearDocument = $cFinancialYearDocument->first();
		return $eFinancialYearDocument['content']->notEmpty();

	}

	public static function canGenerate(FinancialYear $eFinancialYear, string $type): bool {

		if(in_array($type, self::getTypes())) {
			return FALSE;
		}

		if($eFinancialYear['cDocument']->empty()) {
			return TRUE;
		}

		$cFinancialYearDocument = $eFinancialYear['cDocument']->find(fn($eFinancialYearDocument) => $eFinancialYearDocument['type'] === $type);
		if($cFinancialYearDocument->empty()) {
			return TRUE;
		}

		$eFinancialYearDocument = $cFinancialYearDocument->first();
		return in_array($eFinancialYearDocument['generation'], [FinancialYearDocument::FAIL, FinancialYearDocument::SUCCESS]);

	}

	public static function clean(): void {

		$cPdfContent = PdfContent::model()
			->select('id', 'hash')
			->join(Pdf::model(), 'm1.id = m2.content', 'LEFT')
			->where(new \Sql('m2.id IS NULL'))
			->getCollection();

		foreach($cPdfContent as $ePdfContent) {

			$path = 'pdf-content/'.$ePdfContent['hash'].'.'.\media\MediaUi::getExtension($ePdfContent['hash']);
			\media\MediaSetting::$mediaDriver->delete($path);

			PdfContent::model()->delete($ePdfContent);

		}
	}

	public static function getContent(FinancialYear $eFinancialYear, string $type): ?string {

		$eFinancialYearDocument = FinancialYearDocument::model()
			->select(['content' => PdfContent::getSelection()])
			->whereFinancialYear($eFinancialYear)
			->whereType($type)
			->get();

		if($eFinancialYearDocument->empty()) {
			return NULL;
		}

		return \overview\PdfLib::getContentByPdf($eFinancialYearDocument['content']);

	}

	public static function generateAll(\farm\Farm $eFarm): void {

		$cFinancialYearDocumentWaiting = FinancialYearDocument::model()
			->select(FinancialYearDocument::getSelection() + ['financialYear' => FinancialYear::getSelection()])
			->whereGeneration('IN', [FinancialYearDocument::WAITING, FinancialYearDocument::NOW])
			->getCollection();

		foreach($cFinancialYearDocumentWaiting as $eFinancialYearDocumentWaiting) {

			FinancialYearLib::generate($eFarm, $eFinancialYearDocumentWaiting['financialYear'], $eFinancialYearDocumentWaiting['type']);

		}

	}

	public static function updateDocument(FinancialYear $eFinancialYear, string $type, Pdf $ePdf): bool {

		return (FinancialYearDocument::model()
			->whereFinancialYear($eFinancialYear)
			->whereType($type)
			->whereGeneration('IN', FinancialYearDocument::PROCESSING)
			->update(['generation' => FinancialYearDocument::SUCCESS, 'content' => $ePdf['content']]) > 0);

	}

	public static function generate(FinancialYear $eFinancialYear, string $type): bool {

		return (FinancialYearDocument::model()
			->whereFinancialYear($eFinancialYear)
			->whereType($type)
			->whereGeneration('IN', [FinancialYearDocument::NOW, FinancialYearDocument::WAITING])
			->update(['generation' => FinancialYearDocument::PROCESSING, 'generationAt' => new \Sql('NOW()')]) > 0);

	}

	public static function countWaiting(FinancialYear $eFinancialYear): int {

		return FinancialYearDocument::model()
			->whereFinancialYear($eFinancialYear)
			->whereGeneration('IN', [FinancialYearDocument::PROCESSING, FinancialYearDocument::NOW, FinancialYearDocument::WAITING])
			->count();

	}

	public static function regenerate(FinancialYear $eFinancialYear, string $type): bool {

		$eFinancialYearDocument = FinancialYearDocument::model()
			->select(FinancialYearDocument::getSelection())
			->whereFinancialYear($eFinancialYear)
			->whereType($type)
			->get();

		if($eFinancialYearDocument->empty()) {

			$eFinancialYearDocument = new FinancialYearDocument([
				'financialYear' => $eFinancialYear,
				'type' => $type,
				'generation' => FinancialYearDocument::NOW,
			]);
			FinancialYearDocument::model()->insert($eFinancialYearDocument);

			return TRUE;

		}

		$updated = FinancialYearDocument::model()
			->whereFinancialYear($eFinancialYear)
			->whereType($type)
			->whereGeneration('IN', [FinancialYearDocument::SUCCESS, FinancialYearDocument::FAIL])
			->update($eFinancialYearDocument, ['generation' => FinancialYearDocument::NOW]);

		return ($updated === 1);

	}

}
