<?php
namespace mail;

class Campaign extends CampaignElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'sourceShop' => \shop\ShopElement::getSelection(),
			'sourceGroup' => \selling\GroupElement::getSelection(),
		];

	}

	public function canRead(): bool {

		$this->expects(['farm']);
		return $this['farm']->canCommunication();

	}

	public function acceptDelete(): bool {

		return $this['status'] === Campaign::CONFIRMED;

	}

	public function getMinScheduledAt(string $mode = 'display'): string {

		$delay = match($mode) {
			'display' => 3,
			'check' => 2
		};

		if($this->exists()) {
			return date('Y-m-d H:00:00', strtotime($this['scheduledAt'].' + '.$delay.' HOUR'));
		} else {
			return date('Y-m-d H:00:00', time() + $delay * 3600);
		}

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('scheduledAt.prepare', function(string &$scheduledAt): bool {
				// HTML retourne la date dans un obscur format ISO
				$scheduledAt = date('Y-m-d H:i:00', strtotime($scheduledAt));
				return TRUE;
			})
			->setCallback('scheduledAt.past', function(string $scheduledAt): bool {
				return $scheduledAt >= $this->getMinScheduledAt('check');
			})
			->setCallback('html.prepare', function(string &$value): bool {
				$value = new \editor\XmlLib()->fromHtml($value);
				return TRUE;
			})
			->setCallback('to.empty', function(array $to): bool {

				return count($to) > 0;

			})
			->setCallback('to.check', function(array $to): bool {

				return Contact::model()
					->whereFarm($this['farm'])
					->whereEmail('IN', $to)
					->count() === count($to);

			});

		parent::build($properties, $input, $p);

	}

}
?>