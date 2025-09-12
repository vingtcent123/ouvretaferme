<?php
namespace website;

class WidgetLib {

	public static function fill(Website $eWebsite, Webpage $eWebpage): void {

		if($eWebpage->empty()) {
			return;
		}

		$eFarm = $eWebpage['farm'];

		$eWebpage['widgets'] = [];

		if($eWebpage['content'] !== NULL) {

			$found = preg_match_all('/\@('.implode('|', self::getList()).')(=([0-9]+))?/s', $eWebpage['content'], $matches);

			for($i = 0; $i < $found; $i++) {

				$original = mb_strtolower($matches[0][$i]);

				if(isset($eWebpage['widgets'][$original])) {
					continue;
				}

				$app = $matches[1][$i];
				$value = $matches[3][$i] ?? NULL;

				$eWebpage['widgets'][$original] = match($app) {
					'contactForm' => self::getContactForm($eWebsite),
					'donationForm' => self::getDonationForm($eWebsite),
					'newsletterForm' => self::getNewsletterForm($eWebsite),
					'shop' => $value ? self::getShop($eFarm, (int)$value, 'limited') : fn() => '',
					'fullShop' => $value ? self::getShop($eFarm, (int)$value, 'full') : fn() => '',
					default => throw new \Exception('Missing widget '.$app)
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

	public static function getContactForm(Website $eWebsite): ?\Closure {

		return fn() => new ContactUi()->getForm($eWebsite);

	}

	public static function getNewsletterForm(Website $eWebsite): ?\Closure {

		return fn() => new NewsletterUi()->getForm($eWebsite);

	}

	public static function getDonationForm(Website $eWebsite): ?\Closure {

		return fn() => new \association\AssociationUi()->donationForm(eWebsite: $eWebsite);

	}

	public static function getList(): array {
		return ['shop', 'fullShop', 'contactForm', 'donationForm', 'newsletterForm'];
	}

}
?>
