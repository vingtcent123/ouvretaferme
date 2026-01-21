<?php
namespace account;

Class FinancialYearDocumentLib extends FinancialYearDocumentCrud {

	//---- BILANS
	const BALANCE_SHEET = 'balance-sheet';
	const OPENING = 'opening';
	const OPENING_DETAILED = 'opening-detailed';
	const CLOSING = 'closing';
	const CLOSING_DETAILED = 'closing-detailed';

	//---- DOC COMPTABLES
	const SIG = 'sig';
	const INCOME_STATEMENT = 'income-statement';
	const INCOME_STATEMENT_DETAILED = 'income-statement-detailed';

	//---- IMMOS
	const ASSET_AMORTIZATION = 'asset-amortization';
	const ASSET_ACQUISITION = 'asset-acquisition';

	//---- BALANCES
	const BALANCE = 'balance';
	const BALANCE_DETAILED = 'balance-detailed';

	//---- JOURNAUX : TVA achat / vente, par journal
	//---- GRAND LIVRE
	//---- TVA : Cerfa, Dde de remboursement

	public static function getTypes(): array {

		return [
			self::BALANCE_SHEET,
			self::OPENING, self::OPENING_DETAILED,
			self::CLOSING, self::CLOSING_DETAILED,
			self::SIG,
			self::INCOME_STATEMENT, self::INCOME_STATEMENT_DETAILED,
			self::ASSET_AMORTIZATION, self:: ASSET_ACQUISITION,
			self::BALANCE, self::BALANCE_DETAILED,
		];

	}

	public static function hasDocument(FinancialYear $eFinancialYear, string $type): bool {

		$eFinancialYearDocument = self::getDocument($eFinancialYear, $type);

		return $eFinancialYearDocument->notEmpty() and $eFinancialYearDocument['generation'] === FinancialYearDocument::SUCCESS and $eFinancialYearDocument['content']->notEmpty();

	}

	public static function getDocument(FinancialYear $eFinancialYear, string $type): FinancialYearDocument {

		if(in_array($type, self::getTypes()) === FALSE) {
			return new FinancialYearDocument();
		}

		if($eFinancialYear['cDocument']->empty()) {
			return new FinancialYearDocument();
		}

		$cFinancialYearDocument = $eFinancialYear['cDocument']->find(fn($eFinancialYearDocument) => $eFinancialYearDocument['type'] === $type);
		if($cFinancialYearDocument->empty()) {
			return new FinancialYearDocument();
		}

		return $cFinancialYearDocument->first();

	}

	public static function canGenerate(FinancialYear $eFinancialYear, string $type): bool {

		if(in_array($type, self::getTypes()) === FALSE) {
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

	public static function countWaiting(FinancialYear $eFinancialYear): int {

		return FinancialYearDocument::model()
			->whereFinancialYear($eFinancialYear)
			->whereGeneration('IN', [FinancialYearDocument::PROCESSING, FinancialYearDocument::NOW, FinancialYearDocument::WAITING])
			->count();

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

	public static function updateDocument(FinancialYear $eFinancialYear, string $type, Pdf $ePdf): bool {

		return (FinancialYearDocument::model()
			->whereFinancialYear($eFinancialYear)
			->whereType($type)
			->whereGeneration('IN', FinancialYearDocument::PROCESSING)
			->update(['generation' => FinancialYearDocument::SUCCESS, 'generationAt' => new \Sql('NOW()'), 'content' => $ePdf['content']]) > 0);

	}

	public static function generateWaiting(\farm\Farm $eFarm): void {

		FinancialYearDocument::model()->beginTransaction();

		$cFinancialYearDocument = FinancialYearDocument::model()
			->select(FinancialYearDocument::getSelection() + ['financialYear' => FinancialYear::getSelection()])
			->whereGeneration(FinancialYearDocument::WAITING)
			->getCollection();

		foreach($cFinancialYearDocument as $eFinancialYearDocument) {

			$updated = FinancialYearDocument::model()
				->whereFinancialYear($eFinancialYearDocument['financialYear'])
				->whereGeneration(FinancialYearDocument::WAITING)
				->whereType($eFinancialYearDocument['type'])
        ->update(['generation' => FinancialYearDocument::PROCESSING]);

			if($updated === 0) {
				continue;
			}

			$ePdf = \overview\PdfLib::generate($eFarm, $eFinancialYearDocument['financialYear'], $eFinancialYearDocument['type']);

			self::updateDocument($eFinancialYearDocument['financialYear'], $eFinancialYearDocument['type'], $ePdf);

			FinancialYearDocument::model()->commit();
		}

	}

	public static function generate(\farm\Farm $eFarm, FinancialYear $eFinancialYear, string $type): ?string {

		FinancialYearDocument::model()->beginTransaction();

		$eFinancialYearDocument = FinancialYearDocument::model()
			->select(FinancialYearDocument::getSelection())
			->whereFinancialYear($eFinancialYear)
			->whereType($type)
			->get();

		if($eFinancialYearDocument->empty()) {

			$eFinancialYearDocument = new FinancialYearDocument([
				'financialYear' => $eFinancialYear,
				'type' => $type,
				'generation' => FinancialYearDocument::PROCESSING,
			]);
			FinancialYearDocument::model()->insert($eFinancialYearDocument);

			$generate = TRUE;

		} else {

			$updated = FinancialYearDocument::model()
				->whereFinancialYear($eFinancialYear)
				->whereType($type)
				->whereGeneration('IN', [FinancialYearDocument::SUCCESS, FinancialYearDocument::FAIL])
				->update($eFinancialYearDocument, ['generation' => FinancialYearDocument::PROCESSING]);

			$generate = ($updated === 1);

		}

		if($generate === FALSE) {
			return NULL;
		}

		$ePdf = \overview\PdfLib::generate($eFarm, $eFinancialYear, $type);

		self::updateDocument($eFinancialYear, $type, $ePdf);

		FinancialYearDocument::model()->commit();

		return \overview\PdfLib::getContentByPdf($ePdf['content']);

	}

	/**
	 * Appelé au moment de la clôture d'un exercice
	 * => On regénère tous les documents
	 */
	public static function regenerateAll(\farm\Farm $eFarm, FinancialYear $eFinancialYear, array $types = []): void {

		if(empty($types)) {
			$types = [
				self::BALANCE_SHEET,
				self::SIG,
				self::INCOME_STATEMENT, self::INCOME_STATEMENT_DETAILED,
				self::ASSET_AMORTIZATION, self:: ASSET_ACQUISITION,
				self::BALANCE, self:: BALANCE_DETAILED,
				self::CLOSING, self::CLOSING_DETAILED
			];
		}

		foreach($types as $type) {

			$eFinancialYearDocument = new FinancialYearDocument([
				'type' => $type,
				'financialYear' => $eFinancialYear,
				'generation' => FinancialYearDocument::WAITING
			]);

			FinancialYearDocument::model()
				->option('add-replace')
				->insert($eFinancialYearDocument);

		}

		if(count($types) > 0) {
			\company\CompanyCronLib::addConfiguration($eFarm, \company\CompanyCronLib::FINANCIAL_YEAR_GENERATE_DOCUMENT, \company\CompanyCron::WAITING);
		}

	}


}
