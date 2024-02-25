<?php
namespace website;

class NewsUi {

	public function __construct() {

	}

	public function getAll(\Collection $cNews): string {

		$h = '';

		foreach($cNews as $eNews) {

			$h .= '<div class="website-news">';
				$h .= '<div class="website-news-anchor" id="news-'.$eNews['id'].'"></div>';
				$h .= '<h2 class="website-news-title">'.encode($eNews['title'] ).'</h2>';
				$h .= '<div class="website-news-date">'.\util\DateUi::textual($eNews['publishedAt'], \util\DateUi::DATE).'</div>';
				$h .= (new \editor\EditorUi())->value($eNews['content']);
			$h .= '</div>';

		}

		return $h;

	}

	public function getForMenu(Website $eWebsite, Webpage $eWebpageCurrent, Webpage $eWebpageNews, \Collection $cNews, int $limit): string {

		if($cNews->empty()) {
			return '';
		}

		$h = '<div class="website-menu-news-wrapper">';

			$h .= '<h4>';
				$class = ($eWebpageCurrent->notEmpty() and $eWebpageCurrent['id'] === $eWebpageNews['id']) ? 'selected' : '';
				$h .= '<a href="'.WebsiteUi::url($eWebsite, '/'.$eWebpageNews['url']).'" class="website-menu-item '.$class.'">';
					$h .= s("Actualités");
				$h .= '</a>';
			$h .= '</h4>';

			$position = 0;

			foreach($cNews as $eNews) {

				$h .= '<a href="'.WebsiteUi::path($eWebsite, '/'.$eWebpageNews['url']).'#news-'.$eNews['id'].'" class="website-menu-news">';
					$h .= encode($eNews['title']);
					$h .= ' <small>'.\util\DateUi::textual($eNews['publishedAt'], \util\DateUi::DATE).'</small>';
				$h .= '</a>';

				if(++$position === $limit) {
					break;
				}

			}

		$h .= '</div>';

		return $h;

	}

	public function create(Website $eWebsite): \Panel {

		$form = new \util\FormUi();

		$eNews = new News([
			'website' => $eWebsite
		]);

		$h = '';

		$formOpen = $form->openAjax('/website/news:doCreate', ['class' => 'panel-dialog container']);

			$h .= $form->hidden('website', $eWebsite['id']);

			$h .= $form->dynamicGroups($eNews, ['title', 'publishedAt', 'content']);

			$footer = $form->submit(s("Ajouter l'actualité"));

		$formClose = $form->close();

		return new \Panel(
			title: s("Ajouter une nouvelle actualité"),
			dialogOpen: $formOpen,
			dialogClose: $formClose,
			body: $h,
			footer: $footer,
			close: 'reload'
		);

	}

	public function update(News $eNews): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$formOpen = $form->openAjax('/website/news:doUpdate', ['class' => 'panel-dialog container']);

			$h .= $form->hidden('id', $eNews['id']);

			$h .= $form->dynamicGroups($eNews, ['title', 'publishedAt', 'content']);

			$footer = $form->submit(s("Modifier l'actualité"));

		$formClose = $form->close();

		return new \Panel(
			title: s("Modifier une actualité"),
			dialogOpen: $formOpen,
			dialogClose: $formClose,
			body: $h,
			footer: $footer,
			close: 'reload'
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = News::model()->describer($property, [
			'title' => s("Titre de l'actualité"),
			'content' => s("Contenu"),
			'publishedAt' => s("Date de publication"),
		]);


		switch($property) {

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
