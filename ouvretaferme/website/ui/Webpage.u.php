<?php
namespace website;

class WebpageUi {

	public function __construct() {

	}

	public static function getBanner(Webpage $eWebpage, string $width): string {

		$eWebpage->expects(['id', 'banner']);

		$ui = new \media\WebpageBannerUi();

		$class = 'media-banner-view'.' ';
		$style = '';

		if($eWebpage['banner'] !== NULL) {
			$style .= 'background-image: url('.$ui->getUrlByElement($eWebpage, 's').');';
		}

		return '<div class="'.$class.'" style="width: '.$width.'; max-width: 100%; height: auto; aspect-ratio: 3; '.$style.'"></div>';

	}

	public function create(Website $eWebsite): \Panel {

		$form = new \util\FormUi();

		$eWebpage = new Webpage([
			'website' => $eWebsite
		]);

		$h = '';

		$h .= $form->openAjax('/website/webpage:doCreate', ['id' => 'webpage-create']);

			$h .= $form->hidden('website', $eWebsite['id']);

			$h .= $this->getUrlField($form, $eWebpage, TRUE);
			$h .= $form->dynamicGroups($eWebpage, ['title', 'description']);

			$h .= $form->group(
				content: $form->submit(s("Créer la page"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-webpage-create',
			title: s("Créer une nouvelle page"),
			body: $h,
			close: 'reload'
		);

	}

	public function updateTitle(Webpage $eWebpage): string {

		$h = '<h1>';
			$h .= '<a href="/website/manage?id='.$eWebpage['farm']['id'].'"  class="h-back hide-lateral-down">'.\Asset::icon('arrow-left').'</a>';
			$h .= s("Éditer le contenu d'une page");
		$h .= '</h1>';

		return $h;

	}

	public function update(Webpage $eWebpage): \Panel {

		$eWebpage->expects([
			'template' => ['fqn']
		]);

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/website/webpage:doUpdate');

			$h .= $form->hidden('id', $eWebpage['id']);

			if($eWebpage['template']['fqn'] !== 'homepage') {
				$h .= $this->getUrlField($form, $eWebpage, FALSE);
			}

			$h .= $form->dynamicGroups($eWebpage, ['title', 'description']);

			if($eWebpage['template']['fqn'] !== 'homepage') {
				$h .= $form->dynamicGroup($eWebpage, 'public');
			}

			$h .= $form->group(
				content: $form->submit(s("Modifier la page"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-webpage-update',
			title: s("Paramétrer la page"),
			body: $h,
			close: 'reload'
		);

	}

	protected function getUrlField(\util\FormUi $form, Webpage $eWebpage, bool $displayAuto): string {

		\Asset::css('website', 'manage.css');
		\Asset::js('website', 'manage.js');

		$auto = $displayAuto ? '<label title="Garder un nom choisi automatiquement en fonction du titre de la page ?">'.$form->inputCheckbox('urlAuto', 1, [
			'checked' => TRUE,
			'onclick' => 'WebsiteManage.changeUrlAuto(this)',
		]).'&nbsp;'.s("Automatique ?").'</label>' : '';

		return $form->group(
			self::p('url')->label,
			'<div class="webpage-write-url">'.
				$form->inputGroup(
					'<div class="input-group-addon">'.WebsiteUi::url($eWebpage['website']).'</div>'.
					$form->dynamicField($eWebpage, 'url', function($d) use($auto) {
						if($auto) {
							$d->attributes['class'] = 'disabled';
						}
					})
				).
				$auto.
			'</div>'.
			$form->info(s("Uniquement des chiffres, des lettres ou des tirets"))
		);

	}

	public function updateContent(Webpage $eWebpage): string {

		$eWebpage->expects([
			'website' => [
				'customWidth'
			]
		]);

		$form = new \util\FormUi();

		$h = \website\DesignUi::getStyles($eWebpage['website'], '#webpage-update-content');
		$h .= $form->openAjax('/website/webpage:doUpdateContent', ['id' => 'webpage-update-content']);

			$h .= '<div class="util-title mb-2">';
				$h .= '<h1 style="font-family: var(--customTitleFont);">'.encode($eWebpage['title']).'</h1>';
				$h .= '<a href="'.WebsiteUi::url($eWebpage['website'], '/'.$eWebpage['url']).'" class="btn btn-secondary" target="_blank">'.s("Consulter la page").'</a>';
			$h .= '</div>';

			if($eWebpage['content'] === NULL) {
				$h .= '<p class="util-info">'.s("L'éditeur de texte vous permet de rédiger le contenu de cette page en ajoutant des textes et des photos.").'</p>';
			}

			$h .= $form->hidden('id', $eWebpage['id']);

			$h .= '<div style="font-family: var(--customFont); max-width: '.$eWebpage['website']['customWidth'].'px">';
				$h .= $form->dynamicField($eWebpage, 'content');
			$h .= '</div>';

			$h .= '<br/>';

			$h .= $form->submit(s("Enregistrer le contenu"));

		$h .= $form->close();

		return $h;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Webpage::model()->describer($property, [
			'url' => s("Adresse de la page"),
			'title' => s("Titre de la page"),
			'description' => s("Description de la page"),
			'public' => s("Autoriser les moteurs de recherche à référencer cette page"),
		]);

		switch($property) {

			case 'url' :
				$d->attributes = ['style' => 'min-width: 15rem'];
				break;

			case 'description' :
				$d->field = 'textarea';
				$d->attributes = ['data-limit' => Webpage::model()->getPropertyRange('description')[1]];
				$d->label .= \util\FormUi::info(s("Utilisée pour les moteurs de recherche. Si vous laissez ce champ vide, alors la description du site sera utilisée."));
				break;

			case 'content' :
				$d->options = [
					'acceptFigure' => TRUE
				];
				break;

			case 'public' :
				$d->field = 'yesNo';
				$d->labelAfter = \util\FormUi::info(s("Cela ne garantit pas que les moteurs de recherche référenceront réellement cette page."));
				break;

		}

		return $d;

	}

}
?>
