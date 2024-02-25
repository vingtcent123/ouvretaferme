<?php
namespace website;

class WebpageUi {

	public function __construct() {

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
			title: s("Créer une nouvelle page"),
			body: $h,
			close: 'reload'
		);

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

			$h .= $form->group(
				content: $form->submit(s("Modifier la page"))
			);

		$h .= $form->close();

		return new \Panel(
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
					$form->dynamicField($eWebpage, 'url', function($d) use ($auto) {
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
				'customDesign' => ['maxWidth']
			]
		]);

		$form = new \util\FormUi();

		$h = '<h2>'.encode($eWebpage['title']).'</h2>';
		$h .= '<p class="util-info">'.s("L'éditeur de texte votre permet de rédiger le contenu de cette page en ajoutant des textes et des photos.").'</p>';

		$h .= $form->openAjax('/website/webpage:doUpdateContent');

			$h .= $form->hidden('id', $eWebpage['id']);

			$h .= $form->dynamicField($eWebpage, 'content', function($d) use ($eWebpage) {
				$d->attributes['style'] = 'max-width: '.$eWebpage['website']['customDesign']['maxWidth'];
			});

		$h .= '<div class="fixed-bottom">';
			$h .= '<div class="container">';
				$h .= $form->submit(s("Enregistrer le contenu"));
			$h .= '</div>';
		$h .= '</div>';

		$h .= $form->close();

		return $h;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Webpage::model()->describer($property, [
			'url' => s("Adresse de la page"),
			'title' => s("Titre de la page"),
			'description' => s("Description de la page"),
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

		}

		return $d;

	}

}
?>
