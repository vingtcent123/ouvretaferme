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

		$h = '<div class="doc-wrapper">';
			$h .= '<div class="doc-menu">';
				$h .= '<div class="doc-menu-title">'.s("Commercialisation").'</div>';
				$h .= '<a href="/doc/selling:pricing" '.$this->menuSelected('sellingPricing').'>'.s("La gestion des prix").'</a>';
				$h .= '<div class="doc-menu-title">'.s("Boutiques en ligne").'</div>';
				$h .= '<a href="/doc/shop:shared" '.$this->menuSelected('shopShared').'>'.s("Les boutiques collectives").'</a>';
				$h .= '<div class="doc-menu-title">'.s("Importer des données").'</div>';
				$h .= '<a href="/doc/import" '.$this->menuSelected('import').'>'.s("Importer un plan de culture").'</a>';
				if(FEATURE_PRE_ACCOUNTING) {
					$h .= '<div class="doc-menu-title">'.s("Comptabilité").'</div>';
					$h .= '<a href="/doc/accounting" '.$this->menuSelected('accounting').'>'.s("Préparer les données de vente pour la comptabilité").'</a>';
				}
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
				$h .= '<div class="container">';
					$h .= parent::getMain($stream);
				$h .= '</div>';
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	protected function menuSelected(string $page): string {
		return ($page === $this->menuSelected) ? 'class="selected"' : '';
	}

}
?>
