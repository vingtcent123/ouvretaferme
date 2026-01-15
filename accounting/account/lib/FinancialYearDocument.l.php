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

	//---- BALANCES : comptable, synthétique
	const BALANCE = 'balance';

	//---- JOURNAUX : TVA achat / vente, par journal
	//---- TVA : Cerfa, Dde de remboursement
	//---- GRAND LIVRE

	public static function getTypes(): array {

		return [
			self::BALANCE_SHEET,
			self::OPENING, self::OPENING_DETAILED,
			self::CLOSING, self::CLOSING_DETAILED,
			self::SIG,
			self::INCOME_STATEMENT, self::INCOME_STATEMENT_DETAILED,
			self::ASSET_AMORTIZATION, self:: ASSET_ACQUISITION,
			self::BALANCE,
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

	/**
	 * Appelé au moment de la clôture d'un exercice
	 * => On regénère tous les documents provisoires
	 */
	public static function regenerateAll(\farm\Farm $eFarm, FinancialYear $eFinancialYear): void {

		$cFinancialYearDocument = FinancialYearDocument::model()
			->select(FinancialYearDocument::getSelection())
			->whereFinancialYear($eFinancialYear)
			->whereType('IN', [
					self::BALANCE_SHEET,
					self::SIG,
					self::INCOME_STATEMENT, self::INCOME_STATEMENT_DETAILED,
					self::ASSET_AMORTIZATION, self:: ASSET_ACQUISITION,])
			->getCollection(NULL, NULL, 'type');

		foreach($cFinancialYearDocument as $eFinancialYearDocument) {

			self::regenerate($eFinancialYear, $eFinancialYearDocument['type']);

		}

		if($cFinancialYearDocument->notEmpty()) {

			\company\CompanyCronLib::addConfiguration(
				$eFarm,
				\company\CompanyCronLib::FINANCIAL_YEAR_GENERATE_DOCUMENT, \company\CompanyCron::WAITING, $eFinancialYear['id']
			);

		}

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
