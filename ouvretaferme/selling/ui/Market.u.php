<?php
namespace selling;

class MarketUi {

	public function __construct() {

		\Asset::css('selling', 'market.css');
		\Asset::js('selling', 'market.js');

	}

	public function getList(Sale $eSaleParent, \Collection $cSale, ?Sale $eSaleSelected = NULL): string {

		$h = '';

		foreach($cSale as $eSale) {

			$h .= '<a href="'.\selling\SaleUi::urlMarket($eSaleParent).'/vente/'.$eSale['id'].'" class="market-sales-item market-sales-item-'.$eSale['preparationStatus'].' '.(($eSaleSelected and $eSaleSelected['id'] === $eSale['id']) ? 'selected' : '').'">';

				$h .= $this->getCircle($eSale);

				$h .= '<div>';
					if($eSale['customer']->empty()) {
						$h .= s("Anonyme à {time}", ['time' => \util\DateUi::numeric($eSale['createdAt'], \util\DateUi::TIME)]);
					} else {
						$h .= s("{user} à {time}", ['user' => encode($eSale['customer']['name']), 'time' => \util\DateUi::numeric($eSale['createdAt'], \util\DateUi::TIME)]);
					}
					$h .= '<br/><small id="market-sale-'.$eSale['id'].'-price">'.\util\TextUi::money($eSale['priceIncludingVat'] ?? 0).'</small>';
				$h .= '</div>';
				$h .= '<div class="market-sales-owner" title="'.s("Vente créée par {value}", \user\UserUi::name($eSale['createdBy'])).'">';
					$h .= \user\UserUi::getVignette($eSale['createdBy'], '1.5rem');
				$h .= '</div>';

			$h .= '</a>';

		}

		return $h;

	}

	public function displayItems(Sale $eSale, \Collection $cItemMarket): string {

		$form = new \util\FormUi();

		$h = $form->openAjax('/selling/market:doUpdatePrices', ['id' => 'market-item-form']);

			$h .= $form->hidden('id', $eSale['id']);

			$h .= '<div class="util-action">';
				$h .= '<h2>'.s("Articles proposés à la vente").'</h2>';
				$h .= '<div>';
					$h .= '<a href="/selling/item:add?id='.$eSale['id'].'" class="btn btn-outline-primary">'.\Asset::icon('plus-circle').' '.s("Ajouter des articles").'</a>';
				$h .= '</div>';
			$h .= '</div>';

			if($cItemMarket->empty()) {

				$h .= '<div class="util-info">';
					$h .= s("Vous ne proposez pas encore d'article à la vente dans votre marché.");
				$h .= '</div>';

			} else {

				$h .= '<div id="market-item-list" class="market-item-wrapper">';

					foreach($cItemMarket as $eItemMarket) {
						$h .= $this->getItemMarket($eItemMarket, $form);
					}

				$h .= '</div>';

				$h .= $this->getItemBanner($form);

			}

		$h .= '</form>';

		return $h;

	}

	public function getItemMarket(Item $eItem, \util\FormUi $form): string {

		$h = '<div class="market-item">';

			$h .= $this->getItemProduct($eItem);

			$h .= '<div class="market-item-text">';

					$h .= '<div style="margin-bottom: 0.25rem">'.s("Prix unitaire").'</div>';
					$h .= '<div>';
						$h .= $form->inputGroup(
							$form->number('unitPrice['.$eItem['id'].']', attributes: ['placeholder' => $eItem['unitPrice'], 'step' => 0.01, 'class' => 'text-end']).
							$form->addon('<small>€ &nbsp;/&nbsp;'.\main\UnitUi::getSingular($eItem['unit'], short: TRUE, by: TRUE).'</small>')
						);
				$h .= '</div>';

				$h .= '<div class="market-item-fields">';
						$h .= '<div>'.s("Vendu").'</div>';
						$h .= '<div>';
							$h .= \main\UnitUi::getValue($eItem['number'], $eItem['unit'], short: TRUE);
						$h .= '</div>';
						$h .= '<div>'.s("Montant").'</div>';
						$h .= '<div>';
							$h .= \util\TextUi::money($eItem['price']);
						$h .= '</div>';
				$h .= '</div>';

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getItemBanner(\util\FormUi $form): string {

		$h = '<div class="market-banner hide" id="market-item-banner" onrender="Market.itemListUpdate()">';
			$h .= '<div>';
				$h .= '<div class="market-banner-icon">'.\Asset::icon('pencil').'</div>';
				$h .= '<span id="market-item-banner-one">'.s("1 prix modifié").'</span>';
				$h .= '<span id="market-item-banner-more">'.s("{value} prix modifiés", '<span id="market-item-banner-items"></span>').'</span>';
			$h .= '</div>';
			$h .= '<div style="display: flex;">';
				$h .= $form->submit(s("Enregistrer"), ['class' => 'btn btn-transparent']);
				$h .= '&nbsp;';
				$h .= '<a onclick="Market.itemEmpty()" class="btn btn-danger">';
					$h .= s("Annuler");
				$h .= '</a>';
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public function displaySale(Sale $eSale, \Collection $cItemSale, Sale $eSaleMarket, \Collection $cItemMarket): string {

		$h = '<div class="market-customer">';
			$h .= '<div class="util-action">';

				$h .= '<h2>'.s("Vente de {date}", ['date' => \util\DateUi::numeric($eSale['createdAt'], \util\DateUi::TIME)]).'</h2>';

				if($eSaleMarket['preparationStatus'] !== \selling\Sale::DELIVERED) {

					switch($eSale['preparationStatus']) {

						case Sale::DRAFT :

							if($cItemSale->empty()) {
								$h .= '<div>';
									$h .= '<a data-ajax="/selling/market:doDelete" post-id="'.$eSale['id'].'" class="btn btn-danger" data-confirm="'.s("Voulez-vous réellement supprimer cette vente ?").'">'.s("Supprimer la vente").'</a>';
								$h .= '</div>';
							} else {
								$h .= '<div>';
									$h .= '<a data-ajax="/selling/sale:doUpdatePreparationStatus" post-id="'.$eSale['id'].'" post-preparation-status="'.Sale::DELIVERED.'" post-id="'.$eSale['id'].'" class="btn btn-success" data-confirm="'.s("Voulez-vous réellement terminer cette vente ?").'">'.s("Terminer la vente").'</a> ';
									$h .= '<a data-ajax="/selling/sale:doUpdatePreparationStatus" post-id="'.$eSale['id'].'" post-preparation-status="'.Sale::CANCELED.'" class="btn btn-muted" data-confirm="'.s("Voulez-vous réellement annuler cette vente ?").'">'.s("Annuler la vente").'</a>';
								$h .= '</div>';
							}

							break;

						case Sale::CANCELED :
						case Sale::DELIVERED :

							$h .= '<a data-ajax="/selling/sale:doUpdatePreparationStatus" post-id="'.$eSale['id'].'" post-preparation-status="'.Sale::DRAFT.'" post-id="'.$eSale['id'].'" class="btn btn-outline-primary" data-confirm="'.s("Voulez-vous réellement remettre cette vente en cours ?").'">'.s("Repasser en cours").'</a> ';

							break;

					}

				}

			$h .= '</div>';

			$h .= '<div class="util-block stick-xs">';
				$h .= '<dl class="util-presentation util-presentation-2">';
					$h .= '<dt>'.s("Client").'</dt>';
					$h .= '<dd>';
						if($eSale['customer']->empty()) {
							$h .= '<a href="/selling/sale:updateCustomer?id='.$eSale['id'].'">'.CustomerUi::name($eSale['customer']).'</a>';
						} else {
							$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle">'.CustomerUi::name($eSale['customer']).'</a>';
							$h .= '<div class="dropdown-list">';
								$h .= '<a href="'.CustomerUi::url($eSale['customer']).'" class="dropdown-item">'.s("Voir le client").'</a>';
								$h .= '<a href="/selling/sale:updateCustomer?id='.$eSale['id'].'" class="dropdown-item">'.s("Changer de client").'</a>';
							$h .= '</div>';
						}
					$h .= '</dd>';
					$h .= '<dt>'.s("Créée par").'</dt>';
					$h .= '<dd>'.\user\UserUi::getVignette($eSale['createdBy'], '1.5rem').' '.\user\UserUi::name($eSale['createdBy']).'</dd>';
					$h .= '<dt>'.s("État").'</dt>';
					$h .= '<dd>'.$this->getCircle($eSale).' '.$this->getStatus($eSale).'</dd>';
					$h .= '<dt>'.s("Moyen de paiement").'</dt>';
					$h .= '<dd>';
						$h .= '<a data-dropdown="bottom-start" class="dropdown-toggle">';
							if($eSale['paymentMethod']) {
								$h .= SaleUi::p('paymentMethod')->values[$eSale['paymentMethod']];
							} else {
								$h .= '<span style="font-weight: normal">...</span>';
							}
						$h .= '</a>';
						$h .= '<div class="dropdown-list bg-secondary">';
							$h .= '<div class="dropdown-title">'.s("Moyen de paiement").'</div>';
							foreach([Sale::CASH, Sale::CARD, Sale::CHECK, Sale::TRANSFER] as $paymentMethod) {
								$h .= '<a data-ajax="/selling/sale:doUpdatePaymentMethod" post-id="'.$eSale['id'].'" post-payment-method="'.$paymentMethod.'" class="dropdown-item">'.SaleUi::p('paymentMethod')->values[$paymentMethod].'</a>';
							}
						$h .= '</div>';
					$h .= '</dd>';
				$h .= '</dl>';
			$h .= '</div>';

		$h .= '</div>';

		if($eSale['items'] > 0) {
			$h .= SaleUi::getSummary($eSale, onlyIncludingVat: TRUE);
		}

		$h .= $this->displaySaleItems($eSale, $cItemSale, $eSaleMarket, $cItemMarket);

		return $h;

	}

	protected function getSaleEntry(Sale $eSaleMarket, Sale $eSale, Item $eItemMarket, Item $eItemSale) {

		$extract = fn($property) => $eItemSale->empty() ? $eItemMarket[$property] : $eItemSale[$property];

		$unitPrice = $extract('unitPrice');
		$number = $eItemSale->empty() ? NULL : $eItemSale['number'];
		$price = $eItemSale->empty() ? NULL : $eItemSale['price'];
		$locked = $eItemSale->empty() ? '' : $eItemSale['locked'];

		$actions = function() {

			$h = '<div class="market-entry-lock hide">'.\Asset::icon('lock-fill').'</div>';
				$h .= '<div class="market-entry-erase hide">';
				$h .= '<a '.attr('onclick', "Market.itemErase(this)").' title="'.s("Revenir à zéro").'">'.\Asset::icon('eraser-fill', ['style' => 'transform: scaleX(-1);']).'</a>';
			$h .= '</div>';

			return $h;

		};

		$h = '<div id="market-entry-'.$eItemMarket['id'].'" class="market-entry hide" data-item="'.$eItemMarket['id'].'">';
			$h .= '<div class="market-entry-background" onclick="Market.hideEntry(this)"></div>';
			$h .= '<div class="market-entry-content">';


				$form = new \util\FormUi();

				$h .= '<div class="market-entry-item">';

					$h .= $form->openAjax('/selling/market:doUpdateSale', ['id' => 'market-sale-form']);

						$h .= $form->hidden('id', $eSaleMarket['id']);
						$h .= $form->hidden('subId', $eSale['id']);
						$h .= $form->hidden('locked['.$eItemMarket['id'].']', $locked);

						$h .= '<div class="market-entry-title">';
							$h .= '<h2>';
								$h .= encode($eItemMarket['name']);
							$h .= '</h2>';
							$h .= '<a onclick="Market.hideEntry(this)" class="market-entry-close">'.\Asset::icon('x-lg').'</a>';
						$h .= '</div>';

						$h .= '<div class="market-entry-lines">';

							$h .= '<div class="market-entry-label">'.s("Prix unitaire").'</div>';
							$h .= '<div class="market-entry-actions" data-property="'.Item::UNIT_PRICE.'">';
								$h .= $actions();
							$h .= '</div>';
							$h .= '<a onclick="Market.keyboardToggle(this)" data-property="'.Item::UNIT_PRICE.'" class="market-entry-field">';
								$h .= $form->hidden('unitPrice['.$eItemMarket['id'].']', $unitPrice);
								$h .= '<div class="market-entry-value" id="market-entry-'.$eItemMarket['id'].'-unit-price">'.\util\TextUi::number($unitPrice ?? 0.0, 2).'</div>';
							$h .= '</a>';
							$h .= '<div class="market-entry-unit">';
								$h .= '€ &nbsp;/&nbsp;'.\main\UnitUi::getSingular($eItemMarket['unit'], short: TRUE, by: TRUE);
							$h .= '</div>';

							$h .= '<div class="market-entry-label">'.s("Vendu").'</div>';
							$h .= '<div class="market-entry-actions" data-property="'.Item::NUMBER.'">';
								$h .= $actions();
							$h .= '</div>';
							$h .= '<a onclick="Market.keyboardToggle(this)" data-property="'.Item::NUMBER.'" class="market-entry-field">';
								$h .= $form->hidden('number['.$eItemMarket['id'].']', $number);
								$h .= '<div class="market-entry-value" id="market-entry-'.$eItemMarket['id'].'-number" data-unit="'.$eItemMarket['unit'].'">';
									$h .= \util\TextUi::number($number ?? 0.0, $eItemMarket->isIntegerUnit() ? 0 : 2);
								$h .= '</div>';
							$h .= '</a>';
							$h .= '<div class="market-entry-unit">';
								$h .= \main\UnitUi::getNeutral($eItemMarket['unit'], short: TRUE);
							$h .= '</div>';

							$h .= '<div class="market-entry-label">'.s("Montant").'</div>';
							$h .= '<div class="market-entry-actions" data-property="'.Item::PRICE.'">';
								$h .= $actions();
							$h .= '</div>';
							$h .= '<a onclick="Market.keyboardToggle(this)" data-property="'.Item::PRICE.'" class="market-entry-field">';
								$h .= $form->hidden('price['.$eItemMarket['id'].']', $price);
								$h .= '<div class="market-entry-value" id="market-entry-'.$eItemMarket['id'].'-price">'.\util\TextUi::number($price ?? 0.0, 2).'</div>';
							$h .= '</a>';
							$h .= '<div class="market-entry-unit">';
								$h .= '€';
							$h .= '</div>';

							$h .= '<div></div>';
							$h .= '<div></div>';
							$h .= '<div class="market-entry-submit">';
								$h .= $form->submit(s("Enregistrer"), ['class' => ' btn btn-secondary']);
							$h .= '</div>';

						$h .= '</div>';

						if(
							$unitPrice and
							$number and
							$price
						) {
							$h .= '<a onclick="Market.deleteItem(this)" class="market-entry-delete" data-confirm="'.s("L'article sera retiré de cette vente. Continuer ?").'">'.s("Supprimer cet article").'</a>';
						}

					$h .= $form->close();

				$h .= '</div>';
				$h .= '<div class="market-entry-keyboard disabled">';
					$h .= '<div class="market-entry-digits">';
						for($digit = 1; $digit <= 9; $digit++) {
							$h .= '<a onclick="Market.keyboardDigit('.$digit.')" class="market-entry-digit">'.$digit.'</a>';
						}
						$h .= '<a onclick="Market.keyboardDigit(0)" class="market-entry-digit">0</a>';
						$h .= '<a onclick="Market.keyboardDigit(0); Market.keyboardDigit(0);" class="market-entry-digit">00</a>';
						$h .= '<a onclick="Market.keyboardRemoveDigit();" class="market-entry-digit">'.\Asset::icon('backspace').'</a>';
					$h .= '</div>';
				$h .= '</div>';

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	protected function displaySaleItems(Sale $eSale, \Collection $cItemSale, Sale $eSaleMarket, \Collection $cItemMarket): string {

		$h = '<div id="market-item-sale" class="market-item-wrapper market-item-'.$eSale['preparationStatus'].'">';

			foreach($cItemMarket as $eItemMarket) {

				if($eItemMarket['product']->notEmpty()) {
					$eItemSale = $cItemSale[$eItemMarket['product']['id']] ?? new Item();
				} else {
					$eItemSale = $cItemSale->find(fn($eItemTry) => $eItemTry['name'] === $eItemMarket['name'], limit: 1, default: new Item());
				}

				$h .= $this->getSaleItem($eSale, $eItemMarket, $eItemSale);
				$h .= $this->getSaleEntry($eSaleMarket, $eSale, $eItemMarket, $eItemSale);

			}

		$h .= '</div>';

		return $h;

	}

	public function getSaleItem(Sale $eSale, Item $eItemMarket, Item $eItemSale): string {

		$locked = $eItemSale->empty() ? '' : $eItemSale['locked'];
		$tag = ($eSale['preparationStatus'] === Sale::DRAFT) ? 'a' : 'div';
		$onclick = ($eSale['preparationStatus'] === Sale::DRAFT) ? 'onclick="Market.showEntry(this)"' : 'div';

		$h = '<'.$tag.' class="market-item '.($eItemSale->empty() ? '' : 'market-item-highlight').'" '.$onclick.' data-locked="'.$locked.'" data-item="'.$eItemMarket['id'].'">';

			$unitPrice = $eItemSale->empty() ? $eItemMarket['unitPrice'] : $eItemSale['unitPrice'];
			$more = \util\TextUi::money($unitPrice).' <span class="util-annotation"> / '.\main\UnitUi::getSingular($eItemMarket['unit'], short: TRUE, by: TRUE).'</span>';

			$h .= $this->getItemProduct($eItemMarket, $more);

			$h .= '<div class="market-item-text '.($eItemSale->empty() ? 'market-item-text-empty' : '').'">';

				$h .= '<div class="market-item-fields">';
					$h .= '<div>'.s("Vendu").'</div>';
					$h .= '<div>';
						if($eItemSale->notEmpty()) {
							$h .= \main\UnitUi::getValue($eItemSale['number'], $eItemMarket['unit'], TRUE);
						} else {
							$h .= '/';
						}
					$h .= '</div>';
					$h .= '<div>'.s("Montant").'</div>';
					$h .= '<div>';
						if($eItemSale->notEmpty()) {
							$h .= \util\TextUi::money($eItemSale['price']);
						} else {
							$h .= '/';
						}
					$h .= '</div>';
				$h .= '</div>';

			$h .= '</div>';

		$h .= '</'.$tag.'>';

		return $h;

	}

	protected function getItemProduct(Item $eItem, string $more = ''): string {

		$eProduct = $eItem['product'];

		$h = '<div class="market-item-product">';

			$h .= '<div>';

				$h .= '<h4>';

					$h .= encode($eItem['name']);

				$h .= '</h4>';

				$h .= $more;

			$h .= '</div>';

			if($eProduct->notEmpty() and $eProduct['vignette'] !== NULL) {
				$h .= ProductUi::getVignette($eProduct, '3rem');
			}

		$h .= '</div>';

		return $h;

	}
	
	protected function getCircle(Sale $eSale): string {
		
		return match($eSale['preparationStatus']) {
			\selling\Sale::DRAFT => \Asset::icon('circle-fill', ['class' => 'color-todo']),
			\selling\Sale::DELIVERED => \Asset::icon('circle-fill', ['class' => 'color-success']),
			\selling\Sale::CANCELED => \Asset::icon('circle-fill', ['class' => 'color-muted'])
		};
		
	}

	protected function getStatus(Sale $eSale): string {

		return match($eSale['preparationStatus']) {
			\selling\Sale::DRAFT => s("En cours"),
			\selling\Sale::DELIVERED => s("Terminée"),
			\selling\Sale::CANCELED => s("Annulée")
		};

	}

}
?>
