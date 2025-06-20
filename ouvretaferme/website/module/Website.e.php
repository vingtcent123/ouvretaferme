<?php
namespace website;

class Website extends WebsiteElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'customDesign' => ['maxWidth'],
			'farm' => ['legalEmail'],
		];

	}

	public function canRead(): bool {

		$this->expects(['status', 'farm']);
		return ($this['status'] === Website::ACTIVE or $this->canWrite());

	}

	public function canWrite(): bool {

		$this->expects(['farm']);
		return $this['farm']->canManage();

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
			->setCallback('customFont.check', function(string $customFont): bool {
				return DesignLib::isCustomFont($customFont, 'customFonts');
			})
			->setCallback('customTitleFont.check', function(string $customFont): bool {
				return DesignLib::isCustomFont($customFont, 'customTitleFonts');
			});

		parent::build($properties, $input, $p);

	}

}
?>
