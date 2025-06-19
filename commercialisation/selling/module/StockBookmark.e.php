<?php
namespace selling;

class StockBookmark extends StockBookmarkElement {

	public function canRead(): bool {

		$this->expects(['farm']);
		return $this['farm']->canSelling();

	}

	public function canWrite(): bool {

		$this->expects(['farm']);
		return $this['farm']->canManage();

	}

}
?>