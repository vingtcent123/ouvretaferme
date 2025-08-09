<?php
namespace mail;

class Campaign extends CampaignElement {

	public function canRead(): bool {

		$this->expects(['farm']);
		return $this['farm']->canCommunication();

	}

}
?>