<?php
namespace website;

class WebpageLib extends WebpageCrud {

	public static function getPropertiesCreate(): array {
		return ['url', 'title', 'description'];
	}

	public static function getPropertiesUpdate(): array|\Closure {
		return function($e) {
			if($e['template']['fqn'] === 'homepage') {
				return ['title', 'description'];
			} else {
				return ['url', 'title', 'description'];
			}
		};
	}

	public static function getByUrl(Website $eWebsite, string $url): Webpage {

		return Webpage::model()
			->select(Webpage::getSelection())
			->whereWebsite($eWebsite)
			->whereUrl($url)
			->get();

	}

	public static function getByWebsite(Website $eWebsite): \Collection {

		return Webpage::model()
			->select(Webpage::getSelection())
			->whereWebsite($eWebsite)
			->sort(['url' => SORT_ASC])
			->getCollection(NULL, NULL, 'id');

	}

	public static function getNewsByWebsite(Website $eWebsite): Webpage {

		$eTemplate = TemplateLib::getByFqn('news');

		return Webpage::model()
			->select(['id', 'url', 'status'])
			->whereWebsite($eWebsite)
			->whereTemplate($eTemplate)
			->get();

	}

	public static function getByWebsiteForMenu(Website $eWebsite): \Collection {

		$ids = Menu::model()
			->whereWebsite($eWebsite)
			->whereWebpage('!=', NULL)
			->getColumn('webpage');

		Webpage::model()->whereId('NOT IN', $ids);

		return self::getByWebsite($eWebsite);

	}

	public static function create(Webpage $e): void {

		$e->expects([
			'website' => ['farm']
		]);

		try {

			$e['farm'] = $e['website']['farm'];

			Webpage::model()->insert($e);

		} catch(\DuplicateException $e) {

			switch($e->getInfo()['duplicate']) {

				case ['website', 'url'] :
					Webpage::fail('url.duplicate');
					break;

			}

		}

	}
	
	public static function update(Webpage $e, array $properties): void {

		$e->expects([
			'template' => ['fqn']
		]);

		// On ne désactive pas la page d'accueil !
		if($e['template']['fqn'] === 'homepage') {
			$properties = array_diff($properties, ['status', 'url']);
		}

		Webpage::model()->beginTransaction();

		parent::update($e, $properties);

		if(in_array('status', $properties)) {

			Menu::model()
				->whereWebpage($e)
				->update([
					'status' => $e['status']
				]);

		}

		Webpage::model()->commit();

	}

	public static function delete(Webpage $e): void {

		$e->expects([
			'id', 'website',
			'template' => ['fqn']
		]);

		if($e['template']['fqn'] !== 'open') {
			return;
		}

		Webpage::model()->beginTransaction();

		Menu::model()
			->whereWebsite($e['website'])
			->whereWebpage($e)
			->delete();

		Webpage::model()->delete($e);

		Webpage::model()->commit();

	}

}
?>
