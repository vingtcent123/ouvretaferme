<?php
/**
 * Affichage d'une page de documentation
 */
class DocTemplate extends MainTemplate {

	public string $template = 'doc';
	public ?string $header = '';
	public ?string $footer = '';

	public ?string $subTitle = NULL;

	public bool $mainContainer = FALSE;

	public ?string $menuSelected = NULL;

	protected function getMain(string $stream):string {

		Asset::css('main', 'font-itim.css');
		Asset::css('main', 'doc.css');
		Asset::js('main', 'doc.js');

		$h = '<a id="doc-menu-open" onclick="Doc.toggleMenu()">'.Asset::icon('list').' '.s("Menu").'</a>';
		$h .= '<div class="doc-wrapper">';
			$h .= '<div id="doc-menu">';
				$h .= '<div class="doc-menu-title">'.s("Généralités").'</div>';
				$h .= '<a href="/doc/" '.$this->menuSelected('mainUse').'>'.s("Introduction").'</a>';
				$h .= '<a href="/doc/main:help" '.$this->menuSelected('mainHelp').'>'.s("Obtenir de l'aide").'</a>';
				$h .= '<a href="/doc/main:design" '.$this->menuSelected('mainDesign').'>'.s("Principes ergonomiques").'</a>';
				$h .= '<div class="doc-menu-title">'.s("Commercialisation").'</div>';
				$h .= '<a href="/doc/selling:pricing" '.$this->menuSelected('sellingPricing').'>'.s("La gestion des prix").'</a>';
				$h .= '<a href="/doc/selling:market" '.$this->menuSelected('sellingMarket').'>'.s("Le logiciel de caisse").'</a>';
				$h .= '<a href="/doc/selling:product" '.$this->menuSelected('sellingProduct').'>'.s("Photos libres de droits pour vos produits").'</a>';
				$h .= '<div class="doc-menu-title">'.s("Boutiques en ligne").'</div>';
				$h .= '<a href="/doc/shop:shared" '.$this->menuSelected('shopShared').'>'.s("Les boutiques collectives").'</a>';
				$h .= '<div class="doc-menu-title">'.s("Importer des données").'</div>';
				$h .= '<a href="/doc/import:series" '.$this->menuSelected('importSeries').'>'.s("Importer un plan de culture").'</a>';
				$h .= '<a href="/doc/import:products" '.$this->menuSelected('importProducts').'>'.s("Importer des produits").'</a>';
				$h .= '<a href="/doc/import:customers" '.$this->menuSelected('importCustomers').'>'.s("Importer des clients").'</a>';
				if(LIME_ENV === 'dev')
				$h .= '<a href="/doc/import:prices" '.$this->menuSelected('importPrices').'>'.s("Importer des prix").'</a>';
				$h .= '<div class="doc-menu-title">'.s("Comptabilité").'</div>';
				$h .= '<a href="/doc/accounting" '.$this->menuSelected('accounting').'>'.s("Prendre en main le logiciel").'</a>';
				$h .= '<a href="/doc/accounting:bank" '.$this->menuSelected('accounting:bank').'>'.s("Les opérations bancaires").'</a>';
				$h .= '<a href="/doc/accounting:start" '.$this->menuSelected('accounting:start').'>'.s("Démarrer la comptabilité").'</a>';
				$h .= '<a href="/doc/accounting:import" '.$this->menuSelected('accounting:import').'>'.s("Importer les factures").'</a>';
				$h .= '<a href="/doc/accounting:asset" '.$this->menuSelected('accounting:asset').'>'.s("Importer les immobilisations").'</a>';
				$h .= '<div class="doc-menu-title">'.s("Divers").'</div>';
				$h .= '<a href="/doc/editor" '.$this->menuSelected('editor').'>'.s("Utiliser l'éditeur de texte").'</a>';
			$h .= '</div>';
			$h .= '<div class="doc-header">';
				$h .= '<h1>'.$this->title.'</h1>';
				if($this->subTitle !== NULL) {
					$h .= '<h4>'.$this->subTitle.'</h4>';
				}
			$h .= '</div>';
			$h .= '<div class="doc-content">';
				$h .= parent::getMain($stream);
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	protected function menuSelected(string $page): string {
		return ($page === $this->menuSelected) ? 'class="selected"' : '';
	}

}
?>
