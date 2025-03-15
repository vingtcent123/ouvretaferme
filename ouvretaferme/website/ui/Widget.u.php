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
			$h .= '<h4>';
				$h .= \shop\ShopUi::link($eShop);
			$h .= '</h4>';
			$h .= new \shop\ShopUi()->getDateHeader($eDate, cssPrefix: 'website-widget');
			if($eDate['isOrderable']) {
				$h .= '<div class="website-widget-shop-order">';
					$h .= '<a href="'.\shop\ShopUi::url($eShop).'">'.\Asset::icon('arrow-right-short').'  '.s("Commander en ligne").'  '.\Asset::icon('arrow-left-short').'</a>';
				$h .= '</div>';
			}
		$h .= '</div>';

		return $h;

	}

}
?>
