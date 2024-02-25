<?php
namespace website;

class DesignLib extends DesignCrud {

	public static function getAll(): \Collection {

		return Design::model()
			->select('id', 'name')
			->getCollection();

	}

	public static function isCustomFont(string $customFont, string $type): bool {

		return count(array_filter(
			\Setting::get('website\\'.$type), fn($font) => $font['value'] === $customFont
		)) > 0;

	}

}
?>