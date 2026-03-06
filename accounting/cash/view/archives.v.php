<?php
new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->title = s("Archivage des journaux de caisse");
	$t->nav = 'cash';

	$h = '<div class="util-action">';
		$h .= '<h1>';
			$h .= '<a href="'.\farm\FarmUi::urlCash().'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
			$h .= $t->title;
		$h .= '</h1>';
		$h .= '<a href="'.\farm\FarmUi::urlConnected().'/cash/archives:create" class="btn btn-primary">'.Asset::icon('plus-circle').' '.s("Nouvelle archive").'</a>';
	$h .= '</div>';

	$t->mainTitle = $h;

	echo new \cash\ArchiveUi()->getList($data->cArchive);

});

new AdaptativeView('create', function($data, PanelTemplate $t) {
	return new \cash\ArchiveUi()->create($data->e);
});