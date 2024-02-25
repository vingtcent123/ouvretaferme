<?php
namespace website;

class MenuLib extends MenuCrud {

	public static function getPropertiesCreate(): array {
		return ['url', 'webpage', 'label'];
	}

	public static function getPropertiesUpdate(): \Closure {
		return function($e) {
			if($e['webpage']->notEmpty()) {
				return ['url', 'label'];
			} else {
				return ['label'];
			}
		};
	}

	public static function getByWebsite(Website $eWebsite, bool $onlyActive = TRUE): \Collection {

		if($onlyActive) {
			Menu::model()->whereStatus(Menu::ACTIVE);
		}

		return Menu::model()
			->select(Menu::getSelection())
			->whereWebsite($eWebsite)
			->sort(new \Sql('IF(position IS NOT NULL, position, id) ASC'))
			->getCollection();

	}

	public static function create(Menu $e): void {

		$e->expects([
			'website' => ['farm'],
			'webpage',
			'url'
		]);

		// Soit une page, soit une URL, mais pas les deux
		if(
			($e['webpage']->empty() and $e['url'] === NULL) or
			($e['webpage']->notEmpty() and $e['url'] !== NULL)
		) {
			Menu::fail('webpage.check');
			Menu::fail('url.check');
			return;
		}

		try {

			$e['farm'] = $e['website']['farm'];

			if($e['webpage']->notEmpty()) {
				$e['webpage']->expects(['status']);
				$e['status'] = $e['webpage']['status'];
			} else {
				$e['status'] = Menu::ACTIVE;
			}

			Menu::model()->insert($e);

		} catch(\DuplicateException $e) {

			switch($e->getInfo()['duplicate']) {

				case ['webpage'] :
					Menu::fail('webpage.duplicate');
					break;

			}

		}

	}

	public static function updatePositions(Website $e, array $positions): void {

		foreach($positions as $position => $id) {

			Menu::model()
				->whereWebsite($e)
				->whereId($id)
				->update([
					'position' => (int)$position + 1
				]);

		}

	}

}
?>
