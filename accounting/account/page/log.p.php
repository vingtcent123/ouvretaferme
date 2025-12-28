<?php
new Page()
->get('index', function($data) {

	$data->page = REQUEST('page', 'int', 0);

	[$data->cLog, $data->nLog] = \account\LogLib::get($data->page);

	throw new ViewAction($data);

});

?>
