<?php
namespace media;

class ToolVignetteLib extends MediaLib {

	public function buildElement(): \farm\Tool {

		$eTool = POST('id', 'farm\Tool');

		if(
			$eTool->empty() or
			\farm\Tool::model()
				->select(['vignette', 'farm'])
				->get($eTool) === FALSE
		) {
			throw new \NotExistsAction('Tool');
		}

		if($eTool->canWrite() === FALSE) {
			throw new \NotAllowedAction();
		}

		return $eTool;

	}

}
?>
