<?php
new AdaptativeView('display', function($data, FarmTemplate $t) {

	$t->title = $data->e['name'];
	$t->tab = 'cultivation';

	$t->subNav = (new \farm\FarmUi())->getCultivationSubNav($data->eFarm);

	echo (new \production\SequenceUi())->getHeader($data->e);

	echo (new \production\CropUi())->displayBySequence($data->e, $data->harvests, $data->cActionMain);

	if($data->e->canWrite()) {
		echo (new \production\SequenceUi())->getComment($data->e);
	}

	echo '<div class="h-line">';
		echo '<h3>'.s("Interventions").'</h3>';
		echo (new \production\FlowUi())->planTask($data->e);
	echo '</div>';

	echo (new \production\FlowUi())->getTimeline($data->e, $data->events, $data->e->canWrite());
	echo (new \production\SequenceUi())->getSeries($data->e, $data->ccSeries);
	echo (new \production\SequenceUi())->getPhotos($data->e, $data->cPhoto);

});

new AdaptativeView('create', function($data, PanelTemplate $t) {
	return (new \production\SequenceUi())->create($data->e);
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return (new \production\SequenceUi())->update($data->e);
});

new JsonView('updateComment', function($data, AjaxTemplate $t) {

	$t->qs('#sequence-comment')
		->outerHtml(((new \production\SequenceUi())->getCommentField($data->e)))
		->scrollTo(behavior: 'smooth');

	$t->qs('[name="comment"]')->focus();

});

new JsonView('getComment', function($data, AjaxTemplate $t) {
	$t->qs('#sequence-comment')->outerHtml(((new \production\SequenceUi())->getComment($data->e)));
});

new JsonView('query', function($data, AjaxTemplate $t) {

	$t->push('results', \production\SequenceUi::getAutocomplete($data->cSequence, $data->cActionMain));

});
?>
