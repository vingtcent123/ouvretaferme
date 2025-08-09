<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {
	return new \mail\CampaignUi()->create($data->e);
});
?>
