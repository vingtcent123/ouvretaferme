<?php
namespace website;

class MenuUi {

	public function __construct() {

	}

	public function create(Website $eWebsite, \Collection $cWebpage, string $for): \Panel {

		$form = new \util\FormUi();

		$eMenu = new Menu([
			'website' => $eWebsite
		]);

		$h = '';

		if(
			$for === 'webpage' and
			$cWebpage->empty()
		) {
			$h .= '<div class="util-info">';
				$h .= s("Vous avez déjà ajouté toutes les pages de votre site au menu.");
			$h .= '</div>';
		} else {

			$h .= $form->openAjax('/website/menu:doCreate');

				$h .= $form->hidden('website', $eWebsite['id']);

				$h .= match($for) {
					'webpage' => $form->dynamicGroup($eMenu, 'webpage*', function($d) use ($cWebpage) {
						$d->values = $cWebpage;
					}),
					'url' => $form->dynamicGroup($eMenu, 'url*')
				};

				$h .= $form->dynamicGroup($eMenu, 'label*');

				$h .= $form->group(
					content: $form->submit(s("Ajouter au menu"))
				);

			$h .= $form->close();

		}

		return new \Panel(
			title: s("Ajouter une page au menu"),
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

			if($eWebpage['template']['fqn'] === 'homepage') {
				$h .= $form->dynamicGroups($eWebpage, ['title', 'description']);
			} else {
				$h .= $form->dynamicGroups($eWebpage, ['url', 'title', 'description']);
			}

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

	public function updateContent(Webpage $eWebpage): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$formOpen = $form->openAjax('/website/webpage:doUpdateContent', ['class' => 'panel-dialog container']);

			$h .= $form->hidden('id', $eWebpage['id']);

			$h .= $form->dynamicField($eWebpage, 'content');
			$h .= '<br/>';

			$footer = $form->submit(s("Enregistrer le contenu"));

		$formClose = $form->close();

		return new \Panel(
			title: encode($eWebpage['title']),
			dialogOpen: $formOpen,
			dialogClose: $formClose,
			body: $h,
			subTitle: WebsiteUi::url($eWebpage['website'], '/'.$eWebpage['url']),
			footer: $footer,
			close: 'reload',
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Menu::model()->describer($property, [
			'url' => s("Adresse de destination"),
			'webpage' => s("Page de destination"),
			'label' => s("Texte"),
		]);

		switch($property) {

			case 'webpage' :
				$d->field = 'select';
				break;

		}

		return $d;

	}

}
?>
