<?php
namespace production;

class Sequence extends SequenceElement {

	public static function getSelection(?\farm\Farm $eFarm = NULL): array {

		return parent::getSelection() + [
			'farm' => ['name'],
			'cCrop' => Crop::model()
				->select(Crop::getSelection())
				->delegateCollection('sequence', 'id', fn($c) => $c->sort(CropLib::sortByPlant())),
		];

	}

	public function active(): bool {
		return ($this['status'] === Sequence::ACTIVE);
	}

	public function canRead(): bool {

		$this->expects(['farm']);

		return (
			$this['farm']->canWrite() or
			$this['visibility'] !== Sequence::PRIVATE
		);

	}

	public function canWrite(): bool {

		$this->expects(['farm']);
		return $this['farm']->canManage();

	}

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		return parent::build($properties, $input, $callbacks + [

			'bedWidth.prepare' => function(?int &$bedWidth): bool {

				try {
					$this->expects(['use']);
				} catch(\Exception) {
					return FALSE;
				}

				$this->expects(['use']);

				if($this['use'] === Sequence::BLOCK) {
					$bedWidth = NULL;
				}

				if($this['use'] === Sequence::BED and $bedWidth === NULL) {
					return FALSE;
				}

				return TRUE;

			},

			'alleyWidth.prepare' => function(?int &$alleyWidth): bool {

				try {
					$this->expects(['use']);
				} catch(\Exception) {
					return FALSE;
				}

				$this->expects(['use']);

				if($this['use'] === Sequence::BLOCK) {
					$alleyWidth = NULL;
				}

				return TRUE;

			},

			'alleyWidth.check' => function(?int &$alleyWidth): bool {

				try {
					$this->expects(['use']);
				} catch(\Exception) {
					return FALSE;
				}

				switch($this['use']) {

					case Sequence::BED :
						return Sequence::model()->check('alleyWidth', $alleyWidth);

					case Sequence::BLOCK :
						$alleyWidth = NULL;
						return TRUE;

				}

			},

			'plantsList.check' => function($plants): bool {

				$plants = (array)($plants ?? []);

				$cPlant = \plant\Plant::model()
					->select('id', 'farm')
					->whereId('IN', $plants)
					->getCollection();

				if($cPlant->empty()) {
					return FALSE;
				} else {

					foreach($cPlant as $ePlant) {
						if($ePlant->canRead() === FALSE) {
							return FALSE;
						}
					}

					$this['cPlant'] = $cPlant;
					return TRUE;
				}

			},

			'perennialLifetime.prepare' => function(?int &$lifetime): bool {

				$this->expects(['id', 'cycle']);

				if($this['cycle'] === Sequence::ANNUAL) {
					$lifetime = NULL;
				}

				return TRUE;

			},

		]);

	}

}
?>