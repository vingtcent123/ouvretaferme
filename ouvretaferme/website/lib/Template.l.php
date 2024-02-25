<?php
namespace website;

class TemplateLib extends TemplateCrud {

	public static function getAutocreate(): \Collection {

		return Template::model()
			->select(Template::getSelection())
			->whereAutocreate(TRUE)
			->getCollection();

	}

}
?>
