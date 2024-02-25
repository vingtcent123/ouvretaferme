<?php
namespace website;

class News extends NewsElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'isPublished' => new \Sql('NOW() >= publishedAt', 'bool')
		];

	}

	public function canRead(): bool {

		$this->expects(['status', 'farm']);

		return (
			$this['status'] === News::READY or
			$this->canWrite()
		);

	}

	public function canWrite(): bool {

		$this->expects(['farm']);
		return $this['farm']->canManage();

	}

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		return parent::build($properties, $input, $callbacks + [

			'content.prepare' => function(string &$value): bool {
				$value = (new \editor\XmlLib())->fromHtml($value, ['acceptFigure' => TRUE]);
				return TRUE;
			}

		]);

	}

}
?>