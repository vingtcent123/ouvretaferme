<?php
namespace website;

class NewsLib extends NewsCrud {

	public static function getPropertiesCreate(): array {
		return self::getPropertiesWrite();
	}

	public static function getPropertiesUpdate(): array {
		return self::getPropertiesWrite();
	}

	public static function getPropertiesWrite(): array {
		return ['title', 'content', 'publishedAt'];
	}

	public static function getByWebsite(Website $eWebsite, ?int $limit = NULL, bool $onlyPublished = TRUE): \Collection {

		if($onlyPublished) {

			News::model()
				->whereStatus(News::READY)
				->where(new \Sql('NOW() >= publishedAt'));

		}

		return News::model()
			->select(News::getSelection())
			->whereWebsite($eWebsite)
			->sort(['publishedAt' => SORT_DESC])
			->getCollection(0, $limit);

	}

	public static function getLastForBlog(): \Collection {

		return News::model()
			->select(News::getSelection())
			->whereStatus(News::READY)
			->where('NOW() >= publishedAt')
			->whereFarm(\Setting::get('blogFarm'))
			->sort(['publishedAt' => SORT_DESC])
			->getCollection(0, 5);

	}

	public static function create(News $e): void {

		$e->expects([
			'website' => ['farm']
		]);

		$e['farm'] = $e['website']['farm'];

		News::model()->insert($e);

	}

}
?>
