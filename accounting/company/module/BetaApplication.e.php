<?php
namespace company;

class BetaApplication extends BetaApplicationElement {

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('accountingHelped.check', function(): bool {
				return post_exists('accountingHelped');
			})
			->setCallback('hasSoftware.check', function(): bool {
				return post_exists('hasSoftware');
			})
			->setCallback('hasVat.check', function(): bool {
				return post_exists('hasVat');
			})
			->setCallback('hasStocks.check', function(): bool {
				return post_exists('hasStocks');
			})
		;

		parent::build($properties, $input, $p);

	}

}
?>
