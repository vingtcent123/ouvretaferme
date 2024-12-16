<?php
namespace selling;

class MerchantUi {

	public function __construct() {

		\Asset::css('selling', 'merchant.css');
		\Asset::js('selling', 'merchant.js');

	}


	public function get(Sale $eSale, Item $eItem) {

		$eItem->expects(['id', 'name', 'unit', 'unitPrice', 'number', 'price', 'locked']);

		$actions = function(string $type) {

			$h = '<div class="merchant-lock hide">'.\Asset::icon('lock-fill').'</div>';
				$h .= '<div class="merchant-erase hide">';
				$h .= '<a '.attr('onclick', "Merchant.keyboardDelete('".$type."')").' title="'.s("Revenir à zéro").'">'.\Asset::icon('eraser-fill', ['style' => 'transform: scaleX(-1);']).'</a>';
			$h .= '</div>';

			return $h;

		};

		$format = fn($property, $value, $precision = 2) => ($value or $property === Item::UNIT_PRICE) ?  \util\TextUi::number($value ?: 0, $precision) : ($precision === 2 ? '-,--' : '-');

		$h = '<div id="merchant-'.$eItem['id'].'" class="merchant hide"  data-unit="'.$eItem['unit'].'" data-item="'.$eItem['id'].'">';
			$h .= '<div class="merchant-background" onclick="Merchant.hide()"></div>';
			$h .= '<div class="merchant-content">';


				$form = new \util\FormUi();

				$h .= '<div class="merchant-item">';

					$h .= $form->openAjax('/selling/market:doUpdateSale');

						$h .= $form->hidden('id', $eSale['id']);
						$h .= $form->hidden('type['.$eItem['id'].']', ($eItem['number'] !== NULL) ? 'standalone' : 'parent');
						$h .= $form->hidden('locked['.$eItem['id'].']', $eItem['locked']);

						$h .= '<div class="merchant-title">';
							$h .= '<h2>';
								$h .= encode($eItem['name']);
							$h .= '</h2>';
							$h .= '<a onclick="Merchant.hide()" class="merchant-close">'.\Asset::icon('x-lg').'</a>';
						$h .= '</div>';

						$h .= '<div class="merchant-lines">';

							$h .= '<div class="merchant-label">'.s("Prix unitaire").'</div>';
							$h .= '<div class="merchant-actions" data-property="'.Item::UNIT_PRICE.'">';
								$h .= $actions(Item::UNIT_PRICE);
							$h .= '</div>';
							$h .= '<a onclick="Merchant.keyboardToggle(this)" data-property="'.Item::UNIT_PRICE.'" class="merchant-field">';
								$h .= $form->hidden('unitPrice['.$eItem['id'].']', $eItem['unitPrice']);
								$h .= '<div class="merchant-value" id="merchant-'.$eItem['id'].'-unit-price">'.$format(Item::UNIT_PRICE, $eItem['unitPrice']).'</div>';
							$h .= '</a>';
							$h .= '<div class="merchant-unit">';
								$h .= '€ &nbsp;/&nbsp;'.\main\UnitUi::getSingular($eItem['unit'], short: TRUE, by: TRUE);
							$h .= '</div>';

							$h .= '<div class="merchant-label">'.s("Vendu").'</div>';
							$h .= '<div class="merchant-actions" data-property="'.Item::NUMBER.'">';
								$h .= $actions(Item::NUMBER);
							$h .= '</div>';
							$h .= '<a onclick="Merchant.keyboardToggle(this)" data-property="'.Item::NUMBER.'" class="merchant-field">';
								$h .= $form->hidden('number['.$eItem['id'].']', $eItem['number']);
								$h .= '<div class="merchant-value" id="merchant-'.$eItem['id'].'-number">';
									$h .= $format(Item::NUMBER, $eItem['number'], $eItem->isUnitInteger() ? 0 : 2);
								$h .= '</div>';
							$h .= '</a>';
							$h .= '<div class="merchant-unit">';
								$h .= \main\UnitUi::getNeutral($eItem['unit'], short: TRUE);
							$h .= '</div>';

							$h .= '<div class="merchant-label">'.s("Montant").'</div>';
							$h .= '<div class="merchant-actions" data-property="'.Item::PRICE.'">';
								$h .= $actions(Item::PRICE);
							$h .= '</div>';
							$h .= '<a onclick="Merchant.keyboardToggle(this)" data-property="'.Item::PRICE.'" class="merchant-field">';
								$h .= $form->hidden('price['.$eItem['id'].']', $eItem['price']);
								$h .= '<div class="merchant-value" id="merchant-'.$eItem['id'].'-price">'.$format(Item::PRICE, $eItem['price']).'</div>';
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

						if($eItem['number'] !== NULL) {
							$h .= '<a data-ajax="/selling/item:doDelete" post-id="'.$eItem['id'].'" class="merchant-delete" data-confirm="'.s("L'article sera retiré de cette vente. Continuer ?").'">'.s("Supprimer cet article").'</a>';
						}

					$h .= $form->close();

				$h .= '</div>';
				$h .= '<div class="merchant-keyboard disabled"></div>';

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

}
?>
