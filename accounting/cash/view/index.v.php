<?php
new AdaptativeView('/journal-de-caisse', function($data, FarmTemplate $t) {

	$t->title = s("Journaux de caisse de {value}", $data->eFarm['name']);
	$t->canonical = \farm\FarmUi::urlConnected($data->eFarm).'/journal-de-caisse';

	$t->nav = 'cash';

	$h = '<div class="util-action">';
		$h .= '<h1>'.s("Journaux de caisse").'</h1>';
		$h .= '<a href="'.\farm\FarmUi::urlConnected().'/cash/register:create" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Nouveau journal").'</a>';
	$h .= '</div>';

	$t->mainTitle = $h;

	if($data->ccRegister->empty()) {

		echo '<h3>'.s("Configurer mon journal de caisse").'</h3>';

		echo new \cash\RegisterUi()->create($data->eRegisterCreate, start: TRUE)->body;

	} else {

		echo new \cash\RegisterUi()->getList($data->ccRegister);

		echo '<div class="util-block-side">';
			echo '<h3>'.Asset::icon('archive').'  '.s("Archivage des données").'</h3>';
			echo '<p>'.s("la fonction d'archivage vise à assurer la conformité fiscale vis-à-vis de l'article 286 du code général des impôts.").'</p>';
			echo '<a href="'.\farm\FarmUi::urlConnected().'/cash/archives" class="btn btn-primary">'.s("Accéder à l'archivage").'</a>';
		echo '</div>';

	}

});

new AdaptativeView('get', function($data, FarmTemplate $t) {

	$t->title = strip_tags(\cash\RegisterUi::getName($data->eRegisterCurrent));
	$t->canonical = \farm\FarmUi::urlConnected($data->eFarm).'/journal-de-caisse';

	$t->nav = 'cash';

	$t->mainTitle = new \cash\RegisterUi()->getHeader($data->eRegisterCurrent, $data->ccRegister);

	if($data->eRegisterCurrent['operations'] > 0) {

		echo new \cash\CashUi()->getChoice($data->eRegisterCurrent, $data->cCashflow, $data->cInvoice, $data->cSale);
		echo new \cash\CashUi()->getSearch($data->eRegisterCurrent, $data->search);
		echo new \cash\CashUi()->getList($data->eRegisterCurrent, $data->ccCash, $data->search, $data->page);

	} else {

		echo new \cash\CashUi()->start($data->eRegisterCurrent);

	}

});