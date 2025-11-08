<?php
namespace selling;

class Relation extends RelationElement {

	public function canRead(): bool {

		$this->expects(['farm']);
		return $this['farm']->canSelling();

	}

	public function canWrite(): bool {
		$this->expects(['farm']);
		return $this['farm']->canManage();
	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('child.check', function(Product $eProduct): bool {

				if($eProduct->notEmpty()) {

					$this->expects(['farm']);

					return (
						Product::model()
							->select(ProductElement::getSelection())
							->get($eProduct) and
						$eProduct->validateProperty('farm', $this['farm']) and
						$eProduct->acceptRelation() and
						Relation::model()
							->whereParent($this['parent'])
							->whereChild($eProduct)
							->exists() === FALSE
					);

				} else {
					return FALSE;
				}


			});

		parent::build($properties, $input, $p);

	}

}
?>