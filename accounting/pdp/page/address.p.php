<?php
new \pdp\AddressPage()
	->create(function($data) {

		$data->eCompany = \pdp\CompanyLib::get();

		throw new \ViewAction($data);

	})
	->doCreate(fn() => throw new ReloadAction('pdp', 'Address::created'))
	->doDelete(fn() => throw new ReloadAction('pdp', 'Address::deleted'))
;
