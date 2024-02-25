<?php
namespace website;

class Webpage extends WebpageElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'template' => ['fqn'],
		];

	}

	public function canRead(): bool {

		$this->expects(['status', 'farm']);

		return (
			$this['status'] === Webpage::ACTIVE or
			$this->canWrite()
		);

	}

	public function canWrite(): bool {

		$this->expects(['farm']);
		return $this['farm']->canManage();

	}

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		return parent::build($properties, $input, $callbacks + [

			'url.prepare' => function(string &$url): bool {
				$url = mb_strtolower($url);
				return TRUE;
			},

			'url.check' => function(string $url): bool {
				return $url !== NULL and preg_match('/^[a-z0-9\_-]*$/s', $url) > 0;
			},

			'content.prepare' => function(string &$value): bool {
				$value = (new \editor\XmlLib())->fromHtml($value, ['acceptFigure' => TRUE]);
				return TRUE;
			}

		]);

	}

}
?>