<?php
/**
 * Affichage d'une page de navigation
 */
class MarketTemplate extends BaseTemplate {

	public string $template = 'market';

	public ?string $selected = NULL;

	public ?\selling\Sale $eSaleSelected = NULL;

	public function __construct() {

		parent::__construct();

		\Asset::css('main', 'design.css');
		\Asset::css('selling', 'market.css');

		\Asset::css('media', 'media.css');

		$this->base = \Lime::getProtocol().'://'.SERVER('HTTP_HOST');

	}

	protected function buildHtml(string $main): string {

		$this->title = s("Vente du {date}", ['date' => \util\DateUi::textual($this->data->e['deliveredAt'])]);

		$h = '<!DOCTYPE html>';
		$h .= '<html lang="'.$this->lang.'">';

		$h .= '<head>';
			$h .= $this->getHead();
			$h .= Asset::importHtml();
		$h .= '</head>';

		$h .= '<body data-template="'.$this->getTemplate().'" data-touch="no">';

			$h .= '<nav id="main-nav"></nav>';
			$h .= '<header></header>';
			$h .= '<main>'.$this->getMain($main).'</main>';
			$h .= '<footer></footer>';

		$h .= '</body>';

		$h .= '</html>';

		return $h;

	}

	protected function getMain(string $main): string {

		$h = '<div class="market-top-wrapper">';
			$h .= '<div class="market-top">';
				$h .= $this->getTop();
			$h .= '</div>';
		$h .= '</div>';
		$h .= '<div class="market-content">';
			$h .= '<div class="market-sales">';

				if($this->data->ccSale->empty()) {

					if($this->data->e->isMarketSelling()) {

						$h .= '<div class="market-sales-list">';
							$h .= '<a data-ajax="/selling/market:doCreateSale" post-id="'.$this->data->e['id'].'" class="market-sales-item market-sales-item-new">';
								$h .= Asset::icon('plus-circle');
								$h .= '<div>'.s("Créer une première vente").'</div>';
							$h .= '</a>';
						$h .= '</div>';

					}

				} else {

					$cSaleDraft = $this->data->ccSale[\selling\Sale::DRAFT];
					$cSaleDelivered = $this->data->ccSale[\selling\Sale::DELIVERED];
					$cSaleCanceled = $this->data->ccSale[\selling\Sale::CANCELED];

					if($this->data->e->isMarketSelling()) {

						$h .= '<h3>'.s("Ventes en cours ({value})", $cSaleDraft->count()).'</h3>';
						$h .= '<div class="market-sales-list">';
							$h .= new \selling\MarketUi()->getList($this->data->e, $cSaleDraft, $this->eSaleSelected);
							$h .= '<a data-ajax="/selling/market:doCreateSale" post-id="'.$this->data->e['id'].'" class="market-sales-item market-sales-item-new">';
								$h .= '<div class="market-sales-svg">'.Asset::icon('plus-circle').'</div>';
								$h .= '<div>';
									$h .= s("Nouvelle vente");
								$h .= '</div>';
							$h .= '</a>';
						$h .= '</div>';

					}

					$h .= '<h3>'.s("Ventes payées ({value})", $cSaleDelivered->count()).'</h3>';

					if($cSaleDelivered->notEmpty()) {
						$h .= '<div class="market-sales-list">';
							$h .= new \selling\MarketUi()->getList($this->data->e, $cSaleDelivered, $this->eSaleSelected);
						$h .= '</div>';
					}
					if($cSaleCanceled->notEmpty()) {
						$h .= '<h3>'.s("Ventes annulées ({value})", $cSaleCanceled->count()).'</h3>';
						$h .= '<div class="market-sales-list">';
							$h .= new \selling\MarketUi()->getList($this->data->e, $cSaleCanceled, $this->eSaleSelected);
						$h .= '</div>';
					}

				}

			$h .= '</div>';
			$h .= '<div class="market-main">';
				$h .= '<div class="market-main-width">';
					$h .= $main;
				$h .= '</div>';
			$h .= '</div>';
		$h .= '</div>';


		return $h;

	}

	protected function getTop(): string {

		$eSale = $this->data->e;

		$h = '<a href="'.\selling\SaleUi::url($eSale).'" class="market-top-back">';
			$h .= Asset::icon('arrow-left');
		$h .= '</a>';

		$h .= '<div class="market-top-title">';
			$h .= '<h2>'.encode($eSale['customer']->getName()).'</h2>';
			$h .= '<div class="market-top-title-date">'.\util\DateUi::textual($eSale['deliveredAt']).'</div>';
		$h .= '</div>';

		$h .= '<a href="'.\selling\SaleUi::urlMarket($eSale).'/articles" class="market-top-stat '.($this->selected === 'items' ? 'selected' : '').'">';
			$h .= '<h4>'.s("Articles").'</h4>';
			$h .= '<div>'.$eSale['items'].'</div>';
		$h .= '</a>';

		$h .= '<a href="'.\selling\SaleUi::urlMarket($eSale).'/ventes" class="market-top-stat '.($this->selected === 'sales' ? 'selected' : '').'">';
			$h .= '<h4>'.s("Ventes").'</h4>';
			$h .= '<div>'.$eSale['marketSales'].'</div>';
		$h .= '</a>';

		if($this->data->e['preparationStatus'] === \selling\Sale::CLOSED) {

			$h .= '<span class="market-top-close market-top-close-disabled" title="'.s("Cette vente est clôturée").'">';
				$h .= Asset::icon('check');
			$h .= '</span>';

		} else if($this->data->ccSale->notEmpty() and $this->data->ccSale[\selling\Sale::DRAFT]->notEmpty()) {

			$h .= '<a class="market-top-close market-top-close-disabled" title="'.s("Clôturer cette vente").'" data-alert="'.s("La vente pourra être clôturée lorsqu'il n'y aura plus de vente en cours !").'">';
				$h .= Asset::icon('check-circle-fill');
			$h .= '</a>';

		} else {

			$h .= '<a data-ajax="/selling/market:doClose" post-id="'.$eSale['id'].'" class="market-top-close disabled" title="'.s("Clôturer cette vente").'" data-confirm="'.s("Voulez-vous vraiment clôturer cette vente ? Vous ne pourrez plus saisir de nouvelles commandes.").'">';
				$h .= Asset::icon('check-circle-fill');
			$h .= '</a>';

		}


		return $h;

	}

}
?>
