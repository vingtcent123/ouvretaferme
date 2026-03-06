<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {
	return new \selling\ArchiveUi()->create($data->e);
});