<?php
namespace payment;

class StripeFarm extends StripeFarmElement {

	public function canRead(): bool {
		return $this->canWrite();
	}

	public function canWrite(): bool {

		$this->expects(['farm']);
		return $this['farm']->canManage();

	}

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		return parent::build($properties, $input, $callbacks + [

			'apiSecretKey.check' => function(string $key): bool {
				return str_starts_with($key, 'rk_live_') and strlen($key) > 10;
			},

			'apiSecretKeyTest.check' => function(?string $key): bool {
				return (
					$key === NULL or
					(str_starts_with($key, 'sk_test_') and strlen($key) > 10)
				);
			},

			'webhookSecretKey.check' => function(string $key): bool {
				return str_starts_with($key, 'whsec_') and strlen($key) > 10;
			},

		]);

	}

}
?>