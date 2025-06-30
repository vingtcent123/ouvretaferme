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

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('apiSecretKey.check', function(string $key): bool {
				return str_starts_with($key, 'rk_live_') and strlen($key) > 10;
			})
			->setCallback('apiSecretKey.end', function(): bool {

				try {
					StripeLib::getWebhooks($this);
					return TRUE;
				}
				catch(\Exception) {
					return FALSE;
				}

			})
			->setCallback('apiSecretKeyTest.check', function(?string $key): bool {
				return (
					$key === NULL or
					(str_starts_with($key, 'sk_test_') and strlen($key) > 10)
				);
			})
			->setCallback('webhookSecretKey.check', function(string $key): bool {
				return str_starts_with($key, 'whsec_') and strlen($key) > 10;
			});
		
		parent::build($properties, $input, $p);

	}

}
?>