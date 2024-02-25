<?php
namespace selling;

class Product extends ProductElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'farm' => ['name', 'vignette'],
			'plant' => ['name', 'latinName', 'fqn', 'vignette'],
			'quality' => ['name', 'shortName', 'logo'],
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

	public function getName(string $mode = 'text'): string {

		$this->expects(['name', 'variety']);

		if($mode === 'text') {

			$h = $this['name'];
			if($this['variety'] !== NULL) {
				$h .= ' / '.$this['variety'];
			}

		} else {

			$h = encode($this['name']);
			if($this['variety'] !== NULL) {
				$h .= '<small title="'.s("Variété").'"> / '.encode($this['variety']).'</small>';
			}

		}

		return $h;

	}

	public function calcProVat(): float {

		$this->expects(['vat', 'proPrice']);

		return vat_from_excluding($this['proPrice'], \Setting::get('selling\vatRates')[$this['vat']]);

	}

	public function calcPrivateVat(): float {

		$this->expects(['vat', 'privatePrice']);

		return vat_from_including($this['privatePrice'], \Setting::get('selling\vatRates')[$this['vat']]);

	}

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		return parent::build($properties, $input, $callbacks + [

			'plant.check' => function(\plant\Plant $ePlant): bool {

				return (
					$ePlant->empty() or (
						\plant\Plant::model()
							->select('farm')
							->get($ePlant) and
						$ePlant->canRead()
					)
				);

			},

			'vat.check' => function(int $vat): bool {
				return array_key_exists($vat, SaleLib::getVatRates($this['farm']));
			},

		]);

	}

}
?>