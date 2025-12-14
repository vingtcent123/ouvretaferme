<?php
namespace selling;

class Pdf extends PdfElement {

	public function canSend(): bool {

		$this->expects(['type', 'emailedAt', 'createdAt']);

		return match($this['type']) {

			Pdf::DELIVERY_NOTE, Pdf::ORDER_FORM => (
				$this['emailedAt'] === NULL and
				in_array(substr($this['createdAt'], 0, 10), [currentDate(), date('Y-m-d', strtotime('today - 1 day'))])
			),
			default => FALSE

		};

	}

	public function canDelete(): bool {
		return $this['farm']->canManage();
	}

}
?>
