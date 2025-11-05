<?php
new AdaptativeView('createSelect', function($data, PanelTemplate $t) {
	return new \mail\CampaignUi()->createSelect($data->e, $data->cCustomerGroup, $data->ccShop);
});

new AdaptativeView('getLimits', function($data, AjaxTemplate $t) {

	$remaining = $data->e['limit'] - $data->e['alreadyScheduled'];

	$t->qs('#campaign-limit')->innerHtml($remaining);
	$t->qs('#campaign-limit-alert')->innerHtml(new \mail\CampaignUi()->getAlert($data->date, $remaining, $data->e['limit']));

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

new AdaptativeView('get', function($data, FarmTemplate $t) {

	$t->nav = 'communications';
	$t->subNav = 'mailing';

	$t->title = s("Campagne du {date}", ['date' => \util\DateUi::numeric($data->e['scheduledAt'], \util\DateUi::DATE_HOUR_MINUTE)]);

	$h = '<div class="util-action">';
		$h .= '<h1>';
			$h .= '<a href="'.\farm\FarmUi::urlCommunicationsCampaign($data->eFarm).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
			$h .= $t->title;
		$h .= '</h1>';
		$h .= new \mail\CampaignUi()->getMenu($data->e, 'btn-primary');
	$h .= '</div>';

	$t->mainTitle = $h;

	echo new \mail\CampaignUi()->get($data->e, $data->cEmail);
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return new \mail\CampaignUi()->update($data->e);
});

new AdaptativeView('getEmailFields', function($data, AjaxTemplate $t) {
	$t->qs('#campaign-write-email')->outerHtml(new \mail\CampaignUi()->getEmailFields(new \util\FormUi(), $data->e));
});
?>
