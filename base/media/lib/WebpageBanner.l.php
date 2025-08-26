<?php
namespace media;

class WebpageBannerLib extends MediaLib {

	public function buildElement(): \website\Webpage {

		$eWebpage = POST('id', 'website\Webpage');

		if(
			$eWebpage->empty() or
			\website\Webpage::model()
				->select('banner', 'farm')
				->get($eWebpage) === FALSE
		) {
			throw new \NotExistsAction('Webpage');
		}

		if($eWebpage->canWrite() === FALSE) {
			throw new \NotAllowedAction();
		}

		return $eWebpage;

	}

}
?>
