<?php
namespace series;

class CommentUi {

	public function __construct() {
		\Asset::css('series', 'comment.css');
	}

	public function createCollection(\Collection $cTask): \Panel {

		$eComment = new Comment();

		$form = new \util\FormUi();

		$h = $form->openAjax('/series/comment:doCreateCollection');

			$h .= (new TaskUi())->getTasksField($form, $cTask);

			$h .= $form->dynamicGroup($eComment, 'text');
			$h .= $form->group(content: $form->submit(s("Envoyer")));

		$h .= $form->close();

		return new \Panel(
			title: s("Commenter"),
			body: $h,
			close: 'reloadOnHistory'
		);

	}

	public function getList(\Collection $cComment, bool $update = FALSE, bool $link = FALSE): string {

		$h = '';

		if($cComment->empty()) {

			$h .= '<div class="util-info">';
				$h .= s("Il n'y a pas de commentaire sur cette intervention.");
			$h .= '</div>';

		} else {

			$h .= '<div class="comment-item-wrapper '.($update ? 'comment-item-update-wrapper' : '').'">';

			foreach($cComment as $eComment) {

					$text = \util\TextUi::tiny(nl2br(encode($eComment['text'])), $link);

					$h .= '<div class="comment-item-date">';
						$h .= \util\DateUi::numeric($eComment['createdAt'], \util\DateUi::DAY_MONTH);
					$h .= '</div>';
					$h .= '<div class="comment-item-value">';
						$h .= \user\UserUi::getVignette($eComment['user'], '1.25rem');
						$h .= ' '.$text;
					$h .= '</div>';

					if($update) {

						$h .= '<div class="comment-item-update">';
							if($eComment->canWrite()) {
								$h .= '<a href="/series/comment:update?id='.$eComment['id'].'" class="btn btn-sm btn-outline-primary" title="'.s("Modifier le commentaire").'">'.\Asset::icon('pencil-fill').'</a>&nbsp;&nbsp;';
								$h .= '<a data-ajax="/series/comment:doDelete" post-id="'.$eComment['id'].'" class="btn btn-sm btn-outline-danger" title="'.s("Supprimer le commentaire").'" data-confirm="'.s("Supprimer ce commentaire ?").'">'.\Asset::icon('trash').'</a>';
							}
						$h .= '</div>';

					}
			}

			$h .= '</div>';

		}

		return $h;

	}

	public function update(\series\Comment $eComment): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/series/comment:doUpdate');

			$h .= $form->hidden('id', $eComment['id']);
			$h .= $form->dynamicGroup($eComment, 'text');

			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Modifier un utilisateur de la ferme"),
			body: $h,
			close: 'reloadOnHistory'
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Comment::model()->describer($property, [
			'user' => s("Utilisateur"),
			'text' => s("Commentaire"),
		]);

		switch($property) {

			case 'text' :
				$d->field = 'textarea';
				$d->attributes = [
					'data-limit' => Comment::model()->getPropertyRange('text')[1],
					'onrender' => 'this.focus();'
				];
				break;

		}

		return $d;

	}

}
