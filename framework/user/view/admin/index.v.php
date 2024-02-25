<?php
new AdaptativeView('index', function($data, MainTemplate $t) {

	$t->title = s("Gérer les utilisateurs");

	$uiAdmin = new \user\AdminUi();

	$t->header = '<div class="admin-navigation stick-xs">';
		$t->header .= (new \main\AdminUi())->getNavigation('user');
		$t->header .= $uiAdmin->getNavigation('user');
		$t->header .= $uiAdmin->getUsersForm($data->search, $data->nUser);
	$t->header .= '</div>';

	echo $uiAdmin->displayStats($data->cRole, $data->cUserDaily, $data->cUserActive);
	echo $uiAdmin->displayUsers($data->cUser, $data->nUser, $data->page, $data->search, $data->isExternalConnected);

});

new JsonView('query', function($data, AjaxTemplate $t) {
	$t->pushCollection('c', $data->c, ['id', 'firstName', 'lastName', 'visibility', 'email']);
});

new AdaptativeView('forgottenPassword', function($data, PanelTemplate $t) {
	return (new \user\AdminUi())->updateForgottenPassword($data->e, $data->eUserAuth, $data->expires);
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return (new \user\AdminUi())->updateUser($data->e);
});
?>
