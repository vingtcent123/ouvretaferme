<?php
new Page(function($data) {

	\user\ConnectionLib::checkLogged();
	$data->eFarm->validate('canManage');

})
	->post('revoke', function($data) {

		\account\DropboxLib::revoke();

		throw new ReloadAction('account', 'Partner::Dropbox.revoked');

	});

?>
