<?php
namespace selling;

class MerchantUi {

	public function __construct() {

		\Asset::css('selling', 'merchant.css');
		\Asset::js('selling', 'merchant.js');

	}


	public function get(string $url, Sale $eSale, Item $eItem, bool $showDelete = TRUE) {

		$eItem->expects(['id', 'name', 'unit', 'unitPrice', 'number', 'price', 'locked']);

		$actions = function(string $type) {

			$h = '<div class="merchant-lock hide">'.\Asset::icon('lock-fill').'</div>';
			$h .= '<div class="merchant-erase hide">';
				$h .= '<a '.attr('onclick', "Merchant.keyboardDelete('".$type."')").' title="'.s("Revenir à zéro").'">'.\Asset::icon('eraser-fill', ['style' => 'transform: scaleX(-1);']).'</a>';
			$h .= '</div>';

			return $h;

		};

		$format = fn($property, $value, $defaultPrecision = 2) => match($defaultPrecision) {
			0 => ($value or $property === Item::UNIT_PRICE) ? $value : '-',
			2 => ($value or $property === Item::UNIT_PRICE) ? \util\TextUi::number($value ?: 0, 2) : '-,--'
		};

		$h = '<div id="merchant-'.$eItem['id'].'" class="merchant hide" data-unit-integer="'.($eItem['unit']->isInteger() ? '1' : '0').'" data-item="'.$eItem['id'].'">';
			$h .= '<div class="merchant-background" onclick="Merchant.hide()"></div>';
			$h .= '<div class="merchant-content">';

				$form = new \util\FormUi();

				$h .= '<div class="merchant-item">';

					$h .= $form->openAjax($url);

						$h .= $form->hidden('id', $eSale['id']);
						$h .= $form->hidden('type['.$eItem['id'].']', ($eSale->isMarketSale() === FALSE or $eItem['number'] !== NULL) ? 'standalone' : 'parent');
						$h .= $form->hidden('locked['.$eItem['id'].']', $eItem['locked']);

						$h .= '<div class="merchant-title">';
							$h .= '<h2>';
								$h .= encode($eItem['name']);
							$h .= '</h2>';
							$h .= '<a onclick="Merchant.hide()" class="merchant-close">'.\Asset::icon('x-lg').'</a>';
						$h .= '</div>';

						$h .= '<div class="merchant-lines">';

							$h .= '<div class="merchant-label form-control-label" data-wrapper="number['.$eItem['id'].']">'.s("Vendu").'</div>';
							$h .= '<div class="merchant-actions" data-property="'.Item::NUMBER.'">';
								$h .= $actions(Item::NUMBER);
							$h .= '</div>';
							$h .= '<a onclick="Merchant.keyboardToggle(this)" data-property="'.Item::NUMBER.'" class="merchant-field">';
								$h .= $form->text('number['.$eItem['id'].']', $eItem['number']);
								$h .= '<div class="merchant-value" id="merchant-'.$eItem['id'].'-number">';
									$h .= $format(Item::NUMBER, $eItem['number'], ($eItem['packaging'] !== NULL or $eItem['unit']->isInteger()) ? 0 : 2);
								$h .= '</div>';
							$h .= '</a>';
							$h .= '<div class="merchant-unit">';
								$unit = \selling\UnitUi::getSingular($eItem['unit'], short: TRUE);
								if($eSale->isPro()) {
									$h .= '<span class="merchant-unit-packaging merchant-packaging '.($eItem['packaging'] !== NULL ? '' : 'hide').'">'.s("colis").'</span>';
									$h .= '<span class="merchant-unit-default merchant-packaging '.($eItem['packaging'] !== NULL ? 'hide' : '').'">'.$unit.'</span>';
								} else {
									$h .= $unit;
								}
							$h .= '</div>';
							$h .= '<div></div>';

							if($eSale->isPro()) {

								$h .= '<div class="merchant-label form-control-label" data-wrapper="packaging['.$eItem['id'].']">'.s("Colisage").'</div>';
								$h .= '<div class="merchant-actions">';
									$h .= '<div class="merchant-lock merchant-packaging '.($eItem['packaging'] !== NULL ? 'hide' : '').'">';
										$h .= '<a '.attr('onclick', "Merchant.packagingToggle()").' title="'.s("Définir un colisage").'">'.\Asset::icon('plus-circle').'</a>';
									$h .= '</div>';
									$h .= '<div class="merchant-erase merchant-packaging '.($eItem['packaging'] !== NULL ? '' : 'hide').'">';
										$h .= '<a '.attr('onclick', "Merchant.packagingToggle()").' data-confirm="'.s("Supprimer les colisages et saisir directement une quantité ?").'" title="'.s("Supprimer le colisage").'">'.\Asset::icon('trash-fill').'</a>';
									$h .= '</div>';
								$h .= '</div>';
								$h .= '<a onclick="Merchant.keyboardToggle(this)" data-property="packaging" class="merchant-field merchant-packaging '.($eItem['packaging'] !== NULL ? '' : 'hide').'">';
									$h .= $form->text('packaging['.$eItem['id'].']', $eItem['packaging']);
									$h .= '<div class="merchant-value" id="merchant-'.$eItem['id'].'-packaging">';
										$h .= $format('packaging', $eItem['packaging'], $eItem['unit']->isInteger() ? 0 : 2);
									$h .= '</div>';
								$h .= '</a>';
								$h .= '<div class="merchant-unit merchant-packaging '.($eItem['packaging'] !== NULL ? '' : 'hide').'">';
									$h .= \selling\UnitUi::getSingular($eItem['unit'], short: TRUE);
								$h .= '</div>';
								$h .= '<div class="merchant-placeholder merchant-packaging '.($eItem['packaging'] !== NULL ? 'hide' : '').'">';
								$h .= '</div>';
								$h .= '<div></div>';

							}

							if($eItem['unitPriceInitial'] !== NULL) {
								$unitPriceDiscountClass = '';
							} else {
								$unitPriceDiscountClass = ' hide';
							}

							$h .= '<div class="merchant-label form-control-label" data-wrapper="unitPrice['.$eItem['id'].']">'.s("Prix unitaire").'</div>';
							$h .= '<div class="merchant-actions" data-property="'.Item::UNIT_PRICE.'">';
								$h .= $actions(Item::UNIT_PRICE);
							$h .= '</div>';
							$h .= '<a onclick="Merchant.keyboardToggle(this)" data-property="'.Item::UNIT_PRICE.'" class="merchant-field">';
								$h .= $form->text('unitPrice['.$eItem['id'].']', $eItem['unitPriceInitial'] !== NULL ? $eItem['unitPriceInitial'] : $eItem['unitPrice']);
								$h .= '<div class="merchant-value" id="merchant-'.$eItem['id'].'-unit-price">'.$format(Item::UNIT_PRICE, $eItem['unitPrice']).'</div>';
							$h .= '</a>';
							$h .= '<div class="merchant-unit">';
								$h .= '€ '.\selling\UnitUi::getBy($eItem['unit'], short: TRUE);
							$h .= '</div>';
							$h .= '<div class="merchant-toggle-unit-price-initial" data-property="'.Item::UNIT_PRICE.'">';
								$h .= '<div class="merchant-tag">';
									$h .= '<span onclick="Merchant.toggleUnitPriceDiscountField('.$eItem['id'].');">';
										$h .= \Asset::icon('tag', ['data-item' => $eItem['id'], 'data-unit-price-discount-visible' => 0, 'class' => $unitPriceDiscountClass === '' ? 'hide' : '']);
										$h .= \Asset::icon('tag-fill', ['data-item' => $eItem['id'], 'data-unit-price-discount-visible' => 1, 'class' => $unitPriceDiscountClass]);
									$h .= '</a>';
								$h .= '</div>';
							$h .= '</div>';

							$h .= '<div class="merchant-label form-control-label'.$unitPriceDiscountClass.'" data-wrapper="unitPriceDiscount['.$eItem['id'].']" data-property="unit-price-discount" data-item="'.$eItem['id'].'">'.s("Prix remisé").'</div>';
							$h .= '<div class="merchant-actions'.$unitPriceDiscountClass.'" data-property="unit-price-discount" data-item="'.$eItem['id'].'">';
								$h .= '<div class="merchant-tag">';
									$h .= '<span>'.\Asset::icon('tag').'</a>';
								$h .= '</div>';
							$h .= '</div>';
							$h .= '<a onclick="Merchant.keyboardToggle(this)" data-property="unit-price-discount" class="merchant-field'.$unitPriceDiscountClass.'" data-item="'.$eItem['id'].'">';
								$h .= $form->text('unitPriceDiscount['.$eItem['id'].']', $eItem['unitPriceInitial'] !== NULL ? $eItem['unitPrice'] : NULL);
								$h .= '<div class="merchant-value" id="merchant-'.$eItem['id'].'-unit-price-discount">'.($eItem['unitPriceInitial'] !== NULL ? $format(Item::UNIT_PRICE, $eItem['unitPrice']) : '').'</div>';
							$h .= '</a>';
							$h .= '<div class="merchant-unit'.$unitPriceDiscountClass.'" data-property="unit-price-discount" data-item="'.$eItem['id'].'">';
								$h .= '€ '.\selling\UnitUi::getBy($eItem['unit'], short: TRUE);
							$h .= '</div>';
							$h .= '<div class="'.$unitPriceDiscountClass.'" data-property="unit-price-discount" data-item="'.$eItem['id'].'"></div>';

							$h .= '<div class="merchant-label form-control-label" data-wrapper="price['.$eItem['id'].']">'.s("Montant").'</div>';
							$h .= '<div class="merchant-actions" data-property="'.Item::PRICE.'">';
								$h .= $actions(Item::PRICE);
							$h .= '</div>';
							$h .= '<a onclick="Merchant.keyboardToggle(this)" data-property="'.Item::PRICE.'" class="merchant-field">';
								$h .= $form->text('price['.$eItem['id'].']', $eItem['price']);
								$h .= '<div class="merchant-value" id="merchant-'.$eItem['id'].'-price">'.$format(Item::PRICE, $eItem['price']).'</div>';
							$h .= '</a>';
							$h .= '<div class="merchant-unit">';
								$h .= '€';
							$h .= '</div>';
							$h .= '<div></div>';

							$h .= '<div></div>';
							$h .= '<div></div>';
							$h .= '<div class="merchant-submit">';
								$h .= $form->submit(s("Enregistrer"));
							$h .= '</div>';

						$h .= '</div>';

						if(
							$showDelete and
							$eItem['number'] !== NULL
						) {
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
