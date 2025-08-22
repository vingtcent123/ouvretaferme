<?php
namespace mail;

class Campaign extends CampaignElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'farm' => \farm\FarmElement::getSelection(),
			'sourceShop' => \shop\ShopElement::getSelection(),
			'sourceGroup' => \selling\GroupElement::getSelection(),
		];

	}

	public function canRead(): bool {

		$this->expects(['farm']);
		return $this['farm']->canCommunication();

	}

	public function acceptUpdate(): bool {

		return $this['status'] === Campaign::CONFIRMED;

	}

	public function acceptDelete(): bool {

		return $this['status'] === Campaign::CONFIRMED;

	}

	public function getMinScheduledAt(string $mode = 'display'): string {

		$delay = match($mode) {
			'display' => 2,
			'check' => 1
		};

		if($this->exists()) {
			return date('Y-m-d H:00:00', strtotime($this['createdAt'].' + '.$delay.' HOUR'));
		} else {
			return date('Y-m-d H:00:00', time() + $delay * 3600);
		}

	}


	public function getMinFavoriteScheduledAt(\farm\Farm $eFarm): string {

		$eFarm->expects(['emailDefaultTime']);

		$min = $this->getMinScheduledAt('display');

		if($eFarm['emailDefaultTime'] !== NULL) {

			$minDate = substr($min, 0, 10);
			$newMin = $minDate.' '.$eFarm['emailDefaultTime'];

			if($newMin < $min) {
				return date('Y-m-d', strtotime($minDate.' + 1 DAY')).' '.$eFarm['emailDefaultTime'];
			} else {
				return $newMin;
			}

		} else {
			return $min;
		}

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('scheduledAt.prepare', function(string &$scheduledAt): bool {
				// HTML retourne la date dans un obscur format ISO
				$scheduledAt = date('Y-m-d H:i:00', strtotime($scheduledAt));
				return TRUE;
			})
			->setCallback('scheduledAt.soon', function(string $scheduledAt): bool {
				return $scheduledAt >= $this->getMinScheduledAt('check');
			})
			->setCallback('scheduledAt.past', function(string $scheduledAt): bool {
				return $scheduledAt >= currentDatetime();
			})
			->setCallback('html.prepare', function(string &$value): bool {
				$value = new \editor\XmlLib()->fromHtml($value);
				return TRUE;
			})
			->setCallback('to.empty', function(array $to): bool {

				return count($to) > 0;

			})
			->setCallback('to.check', function(array $to): bool {

				ContactLib::applyExport();

				return Contact::model()
					->whereFarm($this['farm'])
					->whereEmail('IN', $to)
					->count() === count($to);

			})
			->setCallback('to.limitExceeded', function(array $to) use ($p): bool {

				if($p->isInvalid('to')) {
					return TRUE;
				}

				$this['toLimit'] = $this['farm']->getCampaignLimit();
				$this['toAttempt'] = CampaignLib::countScheduled($this['farm'], $this['scheduledAt'], $this) + count($to);

				return ($this['toAttempt'] <= $this['toLimit']);

			});

		parent::build($properties, $input, $p);

	}

}
?>