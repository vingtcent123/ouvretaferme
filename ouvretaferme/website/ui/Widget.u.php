<?php
namespace website;

class WidgetUi {

	public function replace(Webpage $eWebpage, string $content): string {

		$eWebpage->expects(['widgets']);

		foreach($eWebpage['widgets'] as $original => $new) {
			if($new !== NULL) {
				$content = str_ireplace($original, $new(), $content);
			}
		}

		return $content;
	}

	public function getShop(\shop\Shop $eShop, \shop\Date $eDate) {

		\Asset::css('website', 'widget.css');

		$h = '<div class="website-widget-shop">';

			$h .= '<h3>';
				$h .= \shop\ShopUi::link($eShop);
			$h .= '</h3>';

			if($eDate->notEmpty()) {

				$h .= new \shop\ShopUi()->getDateHeader($eDate, cssPrefix: 'website-widget');

				if($eDate['isOrderable']) {

					$url = $eShop['embedOnly'] ? $eShop['embedUrl'] : \shop\ShopUi::url($eShop);

					$h .= '<div class="website-widget-shop-order">';
						$h .= '<a href="'.encode($url).'">&gt;  '.s("Commander en ligne").'  &lt;</a>';
					$h .= '</div>';

				}

			} else {

				$h .= '<p>';
					$h .= s("Cette boutique n'est pas encore ouverte.");
				$h .= '</p>';

			}

		$h .= '</div>';

		return $h;

	}

}
?>
