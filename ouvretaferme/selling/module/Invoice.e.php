<?php
namespace selling;

class Invoice extends InvoiceElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'customer' => CustomerElement::getSelection(),
			'expiresAt' => new \Sql('IF(content IS NULL, NULL, createdAt + INTERVAL '.\Setting::get('selling\documentExpires').' MONTH)')
		];

	}

	public function canRead(): bool {

		$this->expects(['farm']);
		return $this['farm']->canSelling();

	}

	public function canWrite(): bool {
		$this->expects(['farm']);
		return $this['farm']->canManage();
	}

	public function canRemote(): bool {
		return $this->canRead() or GET('key') === \Setting::get('selling\remoteKey');
	}

	public function acceptSend(): bool {

		$this->expects(['emailedAt', 'createdAt', 'generation']);

		return (
			$this['generation'] === Invoice::SUCCESS and
			$this['emailedAt'] === NULL
		);

	}

	public function acceptRegenerate(): bool {
		return in_array($this['generation'], [Invoice::FAIL, Invoice::SUCCESS]);
	}

	public function isCreditNote(): bool {
		return ($this['priceExcludingVat'] < 0.0);
	}

	public function getInvoice(): string {
		$this->expects(['document']);
		return $this->isCreditNote() ? 'AV'.$this['document'] : 'FA'.$this['document'];
	}

	public function getTaxes(): string {

		$this->expects(['hasVat', 'taxes']);

		if($this['hasVat']) {
			return SaleUi::p('taxes')->values[$this['taxes']];
		} else {
			return '';
		}

	}

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		return parent::build($properties, $input, $callbacks + [

			'sales.prepare' => function(array &$sales): bool {

				$this->expects(['customer']);

				$this['cSale'] = SaleLib::getForInvoice($this['customer'], $sales);

				if($this['cSale']->empty()) {
					return FALSE;
				}

				return TRUE;

			},

			'sales.check' => function(array &$sales): bool {

				return ($this['cSale']->count() === count($sales));

			},

			'sales.taxes' => function(): bool {

				$this->expects(['cSale']);

				$taxes = $this['cSale']->getColumn('taxes');

				return (count(array_unique($taxes)) === 1);

			},

			'sales.hasVat' => function(): bool {

				$this->expects(['cSale']);

				$hasVat = $this['cSale']->getColumn('hasVat');

				return (count(array_unique($hasVat)) === 1);

			},

		]);

	}

}
?>