<?php
namespace mail;

class Campaign extends CampaignElement {

	public function canRead(): bool {

		$this->expects(['farm']);
		return $this['farm']->canCommunication();

	}

	public function getMinScheduledAt(): string {

		if($this->exists()) {
			return date('Y-m-d H:00:00', strtotime($this['scheduledAt'].' + 2 HOUR'));
		} else {
			return date('Y-m-d H:00:00', time() + 7200);
		}

	}

}
?>