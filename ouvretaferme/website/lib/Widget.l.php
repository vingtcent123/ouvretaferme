<?php
namespace website;

class WidgetLib {

	public static function fill(Webpage $eWebpage): void {

		if($eWebpage->empty()) {
			return;
		}

		$eFarm = $eWebpage['farm'];

		$eWebpage['widgets'] = [];

		if($eWebpage['content'] !== NULL) {

			$found = preg_match_all('/\@('.implode('|', self::getList()).')=([0-9]+)/si', $eWebpage['content'], $matches);

			for($i = 0; $i < $found; $i++) {

				$original = mb_strtolower($matches[0][$i]);

				if(isset($eWebpage['widgets'][$original])) {
					continue;
				}

				$app = $matches[1][$i];
				$value = $matches[2][$i];

				$eWebpage['widgets'][$original] = match($app) {
					'shop' => self::getShop($eFarm, (int)$value, 'limited'),
					'fullShop' => self::getShop($eFarm, (int)$value, 'full'),
				} ?? $original;

			}

		}

	}

	public static function getShop(\farm\Farm $eFarm, int $id, string $mode): ?\Closure {

		$eShop = \shop\ShopLib::getById($id);

		if($eShop->empty()) {
			return fn() => '';
		}

		// Pas les accès en écriture sur la boutique
		if($eShop['farm']['id'] !== $eFarm['id']) {
			return fn() => '';
		}

		return fn() => new \shop\ShopUi()->getEmbedScript($eShop, $mode);

	}

	public static function getList(): array {
		return ['shop', 'fullShop'];
	}

}
?>
