<?php
namespace farm;

Class ConfigurationHistoryLib extends ConfigurationHistoryCrud {

	public static function getForDate(Farm $eFarm, string $date): ConfigurationHistory {

		return ConfigurationHistory::model()
			->select('value')
			->whereFarm($eFarm)
			->whereEffectiveAt('<=', $date)
			->sort(['effectiveAt' => SORT_DESC])
			->get();

	}

}
