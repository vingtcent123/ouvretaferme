<?php
new Page(function($data) {

	\user\ConnectionLib::checkLogged();
	$data->eFarm->validate('canManage');

})
	->get('index', function($data) {
		\company\MindeeLib::getInvoiceData('/tmp/shared/test-invoice.pdf');
	})
	->post('revoke', function($data) {

		\account\DropboxLib::revoke();

		throw new ReloadAction('account', 'Partner::Dropbox.revoked');

	});

?>
