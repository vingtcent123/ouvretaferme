<?php
namespace selling;

class MerchantUi {

	public function __construct() {

		\Asset::css('selling', 'merchant.css');
		\Asset::js('selling', 'merchant.js');

	}


	public function get(Sale $eSale, Item $eItemParent, Item $eItemSale) {

		$eItemReference = $eItemSale->empty() ? $eItemParent : $eItemSale;

		$unitPrice = $eItemReference['unitPrice'];
		$number = $eItemSale->empty() ? NULL : $eItemSale['number'];
		$price = $eItemSale->empty() ? NULL : $eItemSale['price'];
		$locked = $eItemSale->empty() ? '' : $eItemSale['locked'];

		$actions = function() {

			$h = '<div class="merchant-lock hide">'.\Asset::icon('lock-fill').'</div>';
				$h .= '<div class="merchant-erase hide">';
				$h .= '<a '.attr('onclick', "Merchant.itemErase(this)").' title="'.s("Revenir à zéro").'">'.\Asset::icon('eraser-fill', ['style' => 'transform: scaleX(-1);']).'</a>';
			$h .= '</div>';

			return $h;

		};

		$h = '<div id="merchant-'.$eItemReference['id'].'" class="merchant hide" data-item="'.$eItemReference['id'].'">';
			$h .= '<div class="merchant-background" onclick="Merchant.hideEntry(this)"></div>';
			$h .= '<div class="merchant-content">';


				$form = new \util\FormUi();

				$h .= '<div class="merchant-item">';

					$h .= $form->openAjax('/selling/market:doUpdateSale', ['id' => 'market-sale-form']);

						$h .= $form->hidden('id', $eSale['id']);
						$h .= $form->hidden('type['.$eItemReference['id'].']', $eItemSale->empty() ? 'parent' : 'standalone');
						$h .= $form->hidden('locked['.$eItemReference['id'].']', $locked);

						$h .= '<div class="merchant-title">';
							$h .= '<h2>';
								$h .= encode($eItemReference['name']);
							$h .= '</h2>';
							$h .= '<a onclick="Merchant.hideEntry(this)" class="merchant-close">'.\Asset::icon('x-lg').'</a>';
						$h .= '</div>';

						$h .= '<div class="merchant-lines">';

							$h .= '<div class="merchant-label">'.s("Prix unitaire").'</div>';
							$h .= '<div class="merchant-actions" data-property="'.Item::UNIT_PRICE.'">';
								$h .= $actions();
							$h .= '</div>';
							$h .= '<a onclick="Merchant.keyboardToggle(this)" data-property="'.Item::UNIT_PRICE.'" class="merchant-field">';
								$h .= $form->hidden('unitPrice['.$eItemReference['id'].']', $unitPrice);
								$h .= '<div class="merchant-value" id="merchant-'.$eItemReference['id'].'-unit-price">'.\util\TextUi::number($unitPrice ?? 0.0, 2).'</div>';
							$h .= '</a>';
							$h .= '<div class="merchant-unit">';
								$h .= '€ &nbsp;/&nbsp;'.\main\UnitUi::getSingular($eItemReference['unit'], short: TRUE, by: TRUE);
							$h .= '</div>';

							$h .= '<div class="merchant-label">'.s("Vendu").'</div>';
							$h .= '<div class="merchant-actions" data-property="'.Item::NUMBER.'">';
								$h .= $actions();
							$h .= '</div>';
							$h .= '<a onclick="Merchant.keyboardToggle(this)" data-property="'.Item::NUMBER.'" class="merchant-field">';
								$h .= $form->hidden('number['.$eItemReference['id'].']', $number);
								$h .= '<div class="merchant-value" id="merchant-'.$eItemReference['id'].'-number" data-unit="'.$eItemReference['unit'].'">';
									$h .= \util\TextUi::number($number ?? 0.0, $eItemReference->isIntegerUnit() ? 0 : 2);
								$h .= '</div>';
							$h .= '</a>';
							$h .= '<div class="merchant-unit">';
								$h .= \main\UnitUi::getNeutral($eItemReference['unit'], short: TRUE);
							$h .= '</div>';

							$h .= '<div class="merchant-label">'.s("Montant").'</div>';
							$h .= '<div class="merchant-actions" data-property="'.Item::PRICE.'">';
								$h .= $actions();
							$h .= '</div>';
							$h .= '<a onclick="Merchant.keyboardToggle(this)" data-property="'.Item::PRICE.'" class="merchant-field">';
								$h .= $form->hidden('price['.$eItemReference['id'].']', $price);
								$h .= '<div class="merchant-value" id="merchant-'.$eItemReference['id'].'-price">'.\util\TextUi::number($price ?? 0.0, 2).'</div>';
							$h .= '</a>';
							$h .= '<div class="merchant-unit">';
								$h .= '€';
							$h .= '</div>';

							$h .= '<div></div>';
							$h .= '<div></div>';
							$h .= '<div class="merchant-submit">';
								$h .= $form->submit(s("Enregistrer"));
							$h .= '</div>';

						$h .= '</div>';

						if($eItemSale->notEmpty()) {
							$h .= '<a data-ajax="/selling/item:doDelete" post-id="'.$eItemSale['id'].'" class="merchant-delete" data-confirm="'.s("L'article sera retiré de cette vente. Continuer ?").'">'.s("Supprimer cet article").'</a>';
						}

					$h .= $form->close();

				$h .= '</div>';
				$h .= '<div class="merchant-keyboard disabled">';
					$h .= '<div class="merchant-digits">';
						for($digit = 1; $digit <= 9; $digit++) {
							$h .= '<a onclick="Merchant.keyboardDigit('.$digit.')" class="merchant-digit">'.$digit.'</a>';
						}
						$h .= '<a onclick="Merchant.keyboardDigit(0)" class="merchant-digit">0</a>';
						$h .= '<a onclick="Merchant.keyboardDigit(0); Merchant.keyboardDigit(0);" class="merchant-digit">00</a>';
						$h .= '<a onclick="Merchant.keyboardRemoveDigit();" class="merchant-digit">'.\Asset::icon('backspace').'</a>';
					$h .= '</div>';
				$h .= '</div>';

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

}
?>
