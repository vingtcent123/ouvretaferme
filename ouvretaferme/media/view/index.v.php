<?php
$put = function($data, AjaxTemplate $t) {

	$media = [
		'type' => $data->type,
		'hash' => $data->hash,
		'color' => $data->color,
		'width' => $data->width,
		'height' => $data->height,
		'takenAt' => $data->takenAt,
		'version' => $data->eElement->empty() ? time() : NULL,
		'urls' => []
	];

	$uiMedia = \media\MediaUi::getInstance($data->type);

	if($data->eElement->empty()) {
		$media['urls']['original'] = $uiMedia->getUrlByHash($data->hash, NULL, $media['version']);
	} else {
		$media['urls']['original'] = $uiMedia->getUrlByElement($data->eElement, NULL);
	}

	foreach(Setting::get('media\\'.$data->type)['imageFormat'] as $format => $size) {

		if($data->eElement->empty()) {
			$url = $uiMedia->getUrlByHash($data->hash, $format, $media['version']);
		} else {
			$url = $uiMedia->getUrlByElement($data->eElement, $format);
		}

		$media['urls'][$format] = $url;

	}

	$t->push('media', $media);

};

new JsonView('put', function($data, AjaxTemplate $t) use ($put) {

	$put($data, $t);

	$t->push('camera', \media\MediaUi::getInstance($data->type)->getCamera($data->eElement, '100%', '100%', '100%'));

});

new JsonView('delete', function($data, AjaxTemplate $t) {

	$t->push('camera', \media\MediaUi::getInstance($data->type)->getCamera($data->eElement, '100%', '100%', '100%'));

});

new JsonView('update', function($data, AjaxTemplate $t) use ($put) {

	$put($data, $t);

	$t->push('camera', \media\MediaUi::getInstance($data->type)->getCamera($data->eElement, '100%', '100%', '100%'));

});

new JsonView('getCrop', function($data, AjaxTemplate $t) {

	if($data->eElement->notEmpty()) {
		$url = \media\MediaUi::getInstance($data->type)->getUrlByElement($data->eElement, NULL);
	} else {
		$version = time();
		$t->push('version', $version);
		$url = \media\MediaUi::getInstance($data->type)->getUrlByHash($data->hash, NULL, $version);
	}

	$t->push('url', $url);
	$t->push('basename', $data->type.'/'.$data->hash.'.'.\media\MediaUi::getExtension($data->hash));
	$t->push('filetype', \media\MediaUi::getExtension($data->hash));
	$t->push('bounds', $data->metadata['crop'] ?? NULL);

	$t->pushPanel(\media\MediaUi::getInstance($data->type)->getCropPanel($data->metadata['width'], $data->metadata['height']));

});

new JsonView('getCropPanel', function($data, AjaxTemplate $t) {
	$t->pushPanel(\media\MediaUi::getInstance($data->type)->getCropPanel($data->width, $data->height));
});
?>
