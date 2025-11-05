<?php
namespace selling;

class Grid extends GridElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'product' => ProductElement::getSelection() + [
				'unit' => \selling\Unit::getSelection()
			],
			'customer' => ['name', 'type', 'destination'],
			'group' => ['name', 'type', 'farm', 'color'],
		];

	}

	public function getType(): ?string {

		$this->expects(['customer', 'group']);

		if($this['customer']->notEmpty()) {
			return $this['customer']['type'];
		} else if($this['group']->notEmpty()) {
			return $this['group']['type'];
		} else {
			return NULL;
		}

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('customer.check', function(Customer $eCustomer): bool {

				return $eCustomer->empty() or Customer::model()
					->select(Customer::getSelection())
					->whereStatus(Customer::ACTIVE)
					->whereFarm($this['farm'])
					->get($eCustomer);

			})
			->setCallback('group.check', function(CustomerGroup $eCustomerGroup): bool {

				return $eCustomerGroup->empty() or CustomerGroup::model()
					->select(CustomerGroup::getSelection())
					->whereFarm($this['farm'])
					->get($eCustomerGroup);

			})
			->setCallback('group.orCustomer', function(CustomerGroup $eCustomerGroup) use ($p): bool {

				if($p->isNew('customer') === FALSE) {
					throw new \Exception('Missing customer');
				}

				if($p->isBuilt('customer') === FALSE) {
					return TRUE;
				}

				return (
					$this['customer']->notEmpty() or
					$eCustomerGroup->notEmpty()
				);

			})
			->setCallback('product.check', function(Product $eProduct): bool {

				if($eProduct->notEmpty()) {

					return (
						Product::model()
							->select(ProductElement::getSelection())
							->whereStatus(Product::ACTIVE)
							->get($eProduct) and
						$eProduct->validateProperty('farm', $this['farm'])
					);

				} else {
					return FALSE;
				}


			})
			->setCallback('priceDiscount.prepare', function (?string &$priceDiscount): bool {

				if($priceDiscount === '') {
					$priceDiscount = NULL;
				} else {
					$priceDiscount = (float)$priceDiscount;
				}

				return TRUE;

			})
			->setCallback('priceDiscount.value', function (?float $priceDiscount) use($p): bool {

				// Si pas quick
				if($p->isBuilt('price') === FALSE) {
					return TRUE;
				}

				if($priceDiscount === NULL) {
					return TRUE;
				}
				return $this['price'] > $priceDiscount;

			})
			->setCallback('priceDiscount.setValue', function (?float $priceDiscount) use($p): bool {

				if($p->isBuilt('price') === FALSE) {
					throw new \PropertySkip();
				}

				// price a déjà été setté
				if($priceDiscount === NULL) {

					$this['priceInitial'] = NULL;
					$p->addBuilt('priceInitial');
					throw new \PropertySkip();

				}

				$this['priceInitial'] = $this['price'];
				$this['price'] = $priceDiscount;
				$p->addBuilt('priceInitial');
				$p->addBuilt('price');

				throw new \PropertySkip();

			});

		parent::build($properties, $input, $p);
	}

}
?>
