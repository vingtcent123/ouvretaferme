<?php
namespace gallery;

class Photo extends PhotoElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'farm' => ['vignette', 'name', 'place'],
			'sequence' => ['name', 'mode', 'status', 'farm'],
			'series' => ['name', 'mode'],
			'author' => ['firstName', 'lastName', 'visibility', 'email', 'vignette']
		];

	}

	public function canRead(): bool {

		$this->expects([
			'farm',
			'sequence'
		]);

		return (
			$this['farm']->canWrite() or
			($this['sequence']->empty() === FALSE and $this['sequence']['farm']->canWrite())
		);

	}

	// Géré dans le build()
	public function canCreate(): bool {
		return TRUE;
	}

	public function canWrite(): bool {

		$this->expects([
			'farm'
		]);

		$eUserOnline = \user\ConnectionLib::getOnline();

		if($eUserOnline->empty()) {
			return FALSE;
		}

		return (
			$this['author']['id'] === $eUserOnline['id'] or // L'utilisateur est l'auteur de la photo
			$this['farm']->canManage()
		);

	}

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		return parent::build($properties, $input, $callbacks + [

			'farm.check' => function(\farm\Farm $eFarm): bool {

				if($eFarm->empty()) {
					return TRUE;
				}

				$eFarm = \farm\FarmLib::getById($eFarm);

				return $eFarm->canWrite();

			},

			'sequence.check' => function(\production\Sequence $eSequence): bool {

				if($eSequence->empty()) {
					return TRUE;
				}

				if(\production\Sequence::model()
					->select('id', 'farm')
					->get($eSequence) === FALSE) {
					return FALSE;
				}

				return $eSequence->canWrite();

			},

			'series.check' => function(\series\Series $eSeries): bool {

				if($eSeries->empty()) {
					return TRUE;
				}

				if(\series\Series::model()
					->select('id', 'farm')
					->get($eSeries) === FALSE) {
					return FALSE;
				}

				return $eSeries->canWrite();

			},

			'task.check' => function(\series\Task $eTask): bool {

				if($eTask->empty()) {
					return TRUE;
				}

				$this->expects(['series']);

				if(
					\series\Task::model()
						->select('id', 'farm', 'series')
						->get($eTask) === FALSE
				) {
					return FALSE;
				}

				return $eTask->canWrite();

			}

		]);

	}

}
?>