<?php
namespace media;

class ShopLogoLib extends MediaLib {

	public function buildElement(): \shop\Shop {

		$eShop = POST('id', 'shop\Shop');

		if(
			$eShop->empty() or
			\shop\Shop::model()
				->select(\shop\Shop::getSelection())
				->get($eShop) === FALSE
		) {
			throw new \NotExistsAction('Shop');
		}

		// L'utilisateur n'est pas le propriétaire de la ferme
		if($eShop->canWrite() === FALSE) {

			throw new \NotAllowedAction();

		}

		return $eShop;

	}

}
?>
