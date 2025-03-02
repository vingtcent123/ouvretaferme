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

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('task.check', function(Task $eTask): bool {

				return Task::model()
					->select('farm', 'series', 'cultivation')
					->get($eTask);

			})
			->setCallback('user.check', function(\user\User $eUser): bool {

				return \user\User::model()->exists($eUser) and $eUser->isOnline();

			});

		parent::build($properties, $input, $p);

	}

}
?>