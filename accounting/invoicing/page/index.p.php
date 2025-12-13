<?php
new Page(
	function($data) {
		\user\ConnectionLib::checkLogged();

		$data->eFarm->validate('canManage');

})
->get('/facturation-electronique', function($data) {

	throw new ViewAction($data);

});
?>
