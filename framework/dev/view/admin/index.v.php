<?php
new AdaptativeView('index', function($data, MainTemplate $t) {

	$t->title = s("Les erreurs");

	$t->header = '<div class="admin-navigation stick-xs">';
		$t->header .= (new \main\AdminUi())->getNavigation('dev');
		$t->header .= (new dev\ErrorMonitoringUi())->getSearchForm($data->nError, $data->search);
	$t->header .= '</div>';

	echo (new dev\ErrorMonitoringUi())->getCloseForm();

	if($data->cError->count() === 0)  {
		echo '<div class="util-info">'.s("Génial, il n'y a pas d'erreur en ce moment !").'</div>';
	} else {

		echo (new \dev\ErrorMonitoringUi())->getErrors($data->cError);

		echo \util\TextUi::pagination($data->page, $data->nError / $data->number);

	}

});

new JsonView('doStatus', function($data, AjaxTemplate $t) {

	$t->qs('#error-status-'.$data->eError['id'])->innerHtml((new \dev\ErrorMonitoringUi())->getErrorStatus($data->eError));

});
?>
