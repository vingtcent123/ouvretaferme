<?php
namespace website;

class DesignLib extends DesignCrud {

	public static function getAll(): \Collection {

		return Design::model()
			->select('id', 'name')
			->sort(['name' => SORT_ASC])
			->getCollection();

	}

	public static function isCustomFont(string $customFont, array $customFonts): bool {

		return count(array_filter(
			$customFonts, fn($font) => $font['value'] === $customFont
		)) > 0;

	}

}
?>
