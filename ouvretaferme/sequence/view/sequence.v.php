<?php
new AdaptativeView('display', function($data, FarmTemplate $t) {

	$t->title = $data->e['name'];
	$t->tab = 'cultivation';

	$t->subNav = new \farm\FarmUi()->getCultivationSubNav($data->eFarm);

	$t->mainTitle = new \sequence\SequenceUi()->getHeader($data->eFarm, $data->e, $data->cFlow);
	echo new \sequence\SequenceUi()->getDetails($data->e);

	echo new \sequence\CropUi()->displayBySequence($data->e, $data->harvests, $data->cActionMain);

	if($data->e->canWrite()) {
		echo new \sequence\SequenceUi()->getComment($data->e);
	}

	echo '<div class="util-title">';
		echo '<h3>'.s("Interventions").'</h3>';
		echo new \sequence\FlowUi()->planTask($data->e);
	echo '</div>';

	echo new \sequence\FlowUi()->getTimeline($data->e, $data->events, $data->e->canWrite());
	echo new \sequence\SequenceUi()->getSeries($data->e, $data->ccSeries);
	echo new \sequence\SequenceUi()->getPhotos($data->e, $data->cPhoto);

});

new AdaptativeView('create', function($data, PanelTemplate $t) {
	return new \sequence\SequenceUi()->create($data->e);
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return new \sequence\SequenceUi()->update($data->e);
});

new JsonView('updateComment', function($data, AjaxTemplate $t) {

	$t->qs('#sequence-comment')
		->outerHtml(new \sequence\SequenceUi()->getCommentField($data->e))
		->scrollTo(behavior: 'smooth');

	$t->qs('[name="comment"]')->focus();

});

new JsonView('getComment', function($data, AjaxTemplate $t) {
	$t->qs('#sequence-comment')->outerHtml(new \sequence\SequenceUi()->getComment($data->e));
});

new JsonView('query', function($data, AjaxTemplate $t) {

	$t->push('results', \sequence\SequenceUi::getAutocomplete($data->cSequence, $data->cActionMain));

});
?>
