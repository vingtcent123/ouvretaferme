<?php
namespace plant;

/**
 * Family basic functions
 */
class FamilyLib extends FamilyCrud {

	public static function getList(): \Collection {

		return Family::model()
			->select(Family::getSelection())
			->sort('name')
			->getCollection();

	}

	/**
	 * Get families for display
	 */
	public static function getListWithPlants(): \Collection {

		return Family::model()
			->select(Family::getSelection() + [
				'plants' => Plant::model()
					->select([
						'family',
						'number' => new \Sql('COUNT(*)', 'int'),
						'vignettes' => new \Sql('GROUP_CONCAT(vignette SEPARATOR \',\')')
					])
					->group('family')
					->delegateElement('family', function($value) {
						return [
							'number' => $value['number'],
							'vignettes' => $value['vignettes'] ? explode(',', $value['vignettes']) : []
						];
					})
			])
			->sort('name')
			->getCollection();

	}

	public static function delete(Family $e): void {

		$e->expects(['id']);

		if(Plant::model()
				->whereFamily($e)
				->exists()) {
			Family::fail('deleteUsed');
			return;
		}

		Family::model()->delete($e);

	}

}
?>
