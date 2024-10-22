<?php
/**
 * Affichage d'une page de documentation
 */
class DocTemplate extends MainTemplate {

	public string $template = 'doc';
	public ?string $header = '';
	public ?string $footer = '';

	public bool $mainContainer = FALSE;

	protected function getMain(string $stream):string {

		$h = '<div class="doc-wrapper">';
			$h .= '<div class="doc-menu">';
				/* Pas de menu pour l'instant */
			$h .= '</div>';
			$h .= '<div class="doc-header">';
				$h .= '<h1>'.$this->title.'</h1>';
			$h .= '</div>';
			$h .= '<div class="doc-content">';
				$h .= '<div class="container">';
					$h .= parent::getMain($stream);
				$h .= '</div>';
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

}
?>
