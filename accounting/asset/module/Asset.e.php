<?php
namespace asset;

class Asset extends AssetElement {

	public function canManage(): bool {
		if($this->empty()) {
			return FALSE;
		}

		if($this['status'] !== AssetElement::ONGOING) {
			return FALSE;
		}

		return TRUE;
	}

	public function canView(): bool {

		if($this->empty()) {
			return FALSE;
		}

		return TRUE;
	}

}
?>
