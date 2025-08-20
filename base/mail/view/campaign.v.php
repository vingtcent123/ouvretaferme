<?php
new AdaptativeView('createSelect', function($data, PanelTemplate $t) {
	return new \mail\CampaignUi()->createSelect($data->e, $data->cGroup, $data->ccShop);
});

new AdaptativeView('create', function($data, FarmTemplate $t) {

	$t->nav = 'communications';
	$t->subNav = 'mailing';

	$t->title = s("Programmer une campagne pour {farm}", $data->eFarm['name']);
	$t->canonical = \farm\FarmUi::urlCommunicationsCampaign($data->eFarm);

	$h = '<div class="util-action">';
		$h .= '<h1>';
			$h .= '<a href="'.\farm\FarmUi::urlCommunicationsCampaign($data->eFarm).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
			$h .= s("Nouvelle campagne");
		$h .= '</h1>';
	$h .= '</div>';

	$t->mainTitle = $h;


	echo new \mail\CampaignUi()->create($data->e);
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return new \mail\CampaignUi()->update($data->e);
});

new AdaptativeView('getEmailFields', function($data, AjaxTemplate $t) {
	$t->qs('#campaign-write-email')->outerHtml(new \mail\CampaignUi()->getEmailFields(new \util\FormUi(), $data->e));
});
?>
