<?php
namespace farm;

class Action extends ActionElement {

	public function canRead(): bool {
		return $this->canWrite();
	}

	public function canWrite(): bool {

		$this->expects(['farm']);

		return (
			\sequence\SequenceSetting::getPrivilege('admin') or
			(
				$this['farm']->empty() === FALSE and
				$this['farm']->canManage()
			)
		);

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('categories.check', function(array &$categories): bool {

				$this->expects(['farm']);

				array_walk($categories, fn(&$value) => $value = (int)$value);

				return (
					$categories !== [] and
					Category::model()
						->whereFarm($this['farm'])
						->whereId('IN', $categories)
						->count() === count($categories)
				);

			})
			->setCallback('color.prepare', function(?string &$color): bool {

				if($color === '') {
					$color = new ActionModel()->getDefaultValue('color');
				}

				return TRUE;

			});

		parent::build($properties, $input, $p);

	}

}
?>
