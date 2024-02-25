<?php
namespace website;

class Website extends WebsiteElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'customDesign' => ['maxWidth']
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

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		return parent::build($properties, $input, $callbacks + [

			'internalDomain.prepare' => function(string &$domain): bool {
				$domain = mb_strtolower($domain);
				return TRUE;
			},

			'internalDomain.check' => function(string $domain): bool {
				return preg_match('/^[a-z0-9\_-]+$/s', $domain) > 0;
			},

			'domain.check' => function(?string $domain): bool {

				return (
					$domain === NULL or (
						preg_match('/^[a-z0-9\-\.]+$/s', $domain) > 0 and
						str_starts_with($domain, '.') === FALSE and
						str_ends_with($domain, '.') === FALSE and
						str_contains($domain, '..') === FALSE
					)
				);
			},

			'customFont.check' => function(string $customFont): bool {
				return DesignLib::isCustomFont($customFont, 'customFonts');
			},

			'customTitleFont.check' => function(string $customFont): bool {
				return DesignLib::isCustomFont($customFont, 'customTitleFonts');
			},

		]);

	}

}
?>