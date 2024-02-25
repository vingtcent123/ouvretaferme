<?php
namespace series;

class Comment extends CommentElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'user' => ['firstName', 'lastName', 'visibility', 'vignette'],
		];

	}

	public function canRead(): bool {

		$this->expects(['farm']);
		return $this['farm']->canWrite();

	}

	public function canWrite(): bool {

		$this->expects(['farm']);
		return $this['farm']->canWork();

	}

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		return parent::build($properties, $input, $callbacks + [

			'task.check' => function(Task $eTask): bool {

				return Task::model()
					->select('farm', 'series', 'cultivation')
					->get($eTask);

			},

			'user.check' => function(\user\User $eUser): bool {

				return \user\User::model()->exists($eUser) and $eUser->isOnline();

			}

		]);

	}

}
?>