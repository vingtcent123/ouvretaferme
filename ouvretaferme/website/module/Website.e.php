<?php
namespace website;

class Website extends WebsiteElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'farm' => ['legalEmail'],
		];

	}

	public function canRead(): bool {

		$this->expects(['status', 'farm']);
		return ($this['status'] === Website::ACTIVE or $this->canWrite());

	}

	public function canWrite(): bool {

		$this->expects(['farm']);
		return $this['farm']->canCommunication();

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('internalDomain.prepare', function(string &$domain): bool {
				$domain = mb_strtolower($domain);
				return TRUE;
			})
			->setCallback('internalDomain.check', function(string $domain): bool {
				return preg_match('/^[a-z0-9\_-]+$/s', $domain) > 0;
			})
			->setCallback('domain.check', function(?string $domain): bool {

				return (
					$domain === NULL or (
						preg_match('/^[a-z0-9\-\.]+$/s', $domain) > 0 and
						str_starts_with($domain, '.') === FALSE and
						str_ends_with($domain, '.') === FALSE and
						str_contains($domain, '..') === FALSE and
						str_contains($domain, '.') === TRUE
					)
				);
			})
			->setCallback('domain.prefix', function(?string $domain): bool {

				return (
					$domain === NULL or
					preg_match('/^[a-z0-9\-]+\.([a-z0-9\-]+\.)+[a-z0-9]+$/s', $domain) > 0
				);
			})
			->setCallback('footer.prepare', function(string &$value): bool {
				$value = new \editor\XmlLib()->fromHtml($value, ['acceptFigure' => TRUE]);
				return TRUE;
			})
			->setCallback('customFont.check', function(string $customFont): bool {
				return DesignLib::isCustomFont($customFont, WebsiteSetting::CUSTOM_FONTS);
			})
			->setCallback('customTitleFont.check', function(string $customFont): bool {
				return DesignLib::isCustomFont($customFont, WebsiteSetting::CUSTOM_TITLE_FONTS);
			});

		parent::build($properties, $input, $p);

	}

}
?>
