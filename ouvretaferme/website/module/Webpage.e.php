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
		return $this['farm']->canCommunication();

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('url.prepare', function(string &$url): bool {
				$url = mb_strtolower($url);
				return TRUE;
			})
			->setCallback('url.check', function(string $url): bool {
				return $url !== NULL and preg_match('/^[a-z0-9\_-]*$/s', $url) > 0;
			})
			->setCallback('content.prepare', function(string &$value): bool {
				$value = new \editor\XmlLib()->fromHtml($value, ['acceptFigure' => TRUE]);
				return TRUE;
			});
		
		parent::build($properties, $input, $p);

	}

}
?>