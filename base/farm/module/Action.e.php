<?php
namespace farm;

class Action extends ActionElement {

	public function isProtected(): bool {

		$this->expects(['fqn']);

		return (
			$this['fqn'] !== NULL and
			ctype_digit($this['fqn']) === FALSE
		);
	}

	public function isFree(): bool {
		return $this->isProtected() === FALSE;
	}

	public function canRead(): bool {
		return $this->canWrite();
	}

	public function canWrite(): bool {

		$this->expects(['farm']);

		return (
			\user\ConnectionLib::getOnline()->isAdmin() or
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

				$this['cCategory'] = Category::model()
					->select('id', 'fqn')
					->whereFarm($this['farm'])
					->whereId('IN', $categories)
					->getCollection();

				return (
					$categories !== [] and
					$this['cCategory']->count() === count($categories)
				);

			})
			->setCallback('color.prepare', function(?string &$color): bool {

				if($color === '') {
					$color = new ActionModel()->getDefaultValue('color');
				}

				$color = strtoupper($color);

				return TRUE;

			})
			->setCallback('pace.prepare', function(?string &$pace) use ($p): bool {

				if(
					$p->isInvalid('categories') or
					$this['cCategory']->contains(fn($eCategory) => $eCategory['fqn'] === CATEGORIE_CULTURE) === FALSE
				) {
					$pace = NULL;
				}

				return TRUE;

			})
			->setCallback('soil.prepare', function(?bool &$soil) use ($p): bool {

				if(
					$p->isBuilt('categories') === FALSE or
					$this['cCategory']->contains(fn($eCategory) => $eCategory['fqn'] === CATEGORIE_CULTURE) === FALSE
				) {
					$soil = FALSE;
				}

				if($p->for === 'update') {
					$this->expects(['soil']);
					$this['oldSoil'] = $this['soil'];
				}

				return TRUE;

			});

		parent::build($properties, $input, $p);

	}

}
?>
