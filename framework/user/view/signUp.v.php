<?php
new AdaptativeView('index', function($data, MainTemplate $t) {

	echo (new \user\UserUi())->signUp($data->eUserOnline, $data->eRole, REQUEST('redirect'));

});
?>
