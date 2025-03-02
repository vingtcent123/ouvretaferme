<?php
namespace website;

class Menu extends MenuElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'webpage' => ['url']
		];

	}

	public function canWrite(): bool {

		$this->expects(['farm']);

		return $this['farm']->canWrite();

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('webpage.check', function(Webpage $eWebpage): bool {

				if($eWebpage->empty()) {
					return TRUE;
				}

				return (
					Webpage::model()
						->select('farm', 'status')
						->get($eWebpage) and
					$eWebpage->canWrite()
				);

			});

		parent::build($properties, $input, $p);

	}

}
?>