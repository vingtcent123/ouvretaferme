<?php
namespace farm;

class Survey extends SurveyElement {

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('achatRevente.prepare', function(string &$value): bool {
				$value = new \editor\XmlLib()->fromHtml($value, ['acceptFigure' => TRUE, 'figureOnlyImage' => TRUE]);
				return TRUE;
			})
			->setCallback('depotVente.prepare', function(string &$value): bool {
				$value = new \editor\XmlLib()->fromHtml($value, ['acceptFigure' => TRUE, 'figureOnlyImage' => TRUE]);
				return TRUE;
			})
			->setCallback('autofacturation.prepare', function(string &$value): bool {
				$value = new \editor\XmlLib()->fromHtml($value, ['acceptFigure' => TRUE, 'figureOnlyImage' => TRUE]);
				return TRUE;
			})
			->setCallback('caisse.prepare', function(string &$value): bool {
				$value = new \editor\XmlLib()->fromHtml($value, ['acceptFigure' => TRUE, 'figureOnlyImage' => TRUE]);
				return TRUE;
			});

		parent::build($properties, $input, $p);

	}

}
?>