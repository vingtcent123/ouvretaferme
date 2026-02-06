<?php
namespace account;

class FinancialYearDocument extends FinancialYearDocumentElement {

	public function isProcessing(): bool {

		return $this->notEmpty() and
			in_array($this['generation'], [FinancialYearDocument::NOW, FinancialYearDocument::PROCESSING, FinancialYearDocument::WAITING]) and
			$this['createdAt'] > date('Y-m-d H:i', strtotime('10 minutes ago'))
			;

	}
}
?>
