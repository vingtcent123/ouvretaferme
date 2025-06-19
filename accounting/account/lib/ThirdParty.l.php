<?php
namespace account;

class ThirdPartyLib extends ThirdPartyCrud {

	public static function getPropertiesCreate(): array {
		return ['name'];
	}

	public static function getAll(\Search $search): \Collection {

		$search->validateSort(['id', 'name']);

		return ThirdParty::model()
      ->select(ThirdParty::getSelection())
      ->whereName('LIKE', '%'.$search->get('name').'%', if: $search->get('name'))
      ->sort($search->buildSort())
      ->getCollection();

	}

	public static function getByName(string $name): ThirdParty|\Element {

		return ThirdParty::model()
	    ->select(ThirdParty::getSelection())
	    ->whereName('=', $name)
	    ->get();

	}

	public static function filterByCashflow(\Collection $cThirdParty, \bank\Cashflow $eCashflow): \Collection {

		$memoItems = explode(' ', $eCashflow['memo']);

		foreach($cThirdParty as &$eThirdParty) {
			$eThirdParty['weight'] = 0;

			foreach($memoItems as $memoItem) {
				if(mb_strlen($memoItem) < 3) {
					continue;
				}
				if(strtolower($eThirdParty['name']) === strtolower($memoItem)) {
					$eThirdParty['weight'] += 50;
				} else if(mb_strlen($memoItem) > 3 and mb_strpos(strtolower($eThirdParty['name']), strtolower($memoItem)) !== FALSE) {
					$eThirdParty['weight'] += levenshtein(strtolower($eThirdParty['name']), strtolower($memoItem));
				}
			}
		}

		return $cThirdParty->sort(['weight' => SORT_DESC, 'name' => SORT_ASC]);

	}

	public static function getNextThirdPartyAccountLabel(string $field, string $prefix): string {

		$eThirdParty = ThirdParty::model()
      ->select($field)
      ->where($field, 'LIKE', $prefix.'%')
      ->sort([$field => SORT_DESC])
      ->get();

		if($eThirdParty->empty()) {
			return $prefix.'001';
		}

		return (int)$eThirdParty[$field] + 1;

	}

}
?>
