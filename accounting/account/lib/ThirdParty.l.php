<?php
namespace account;

class ThirdPartyLib extends ThirdPartyCrud {

	public static function getPropertiesCreate(): array {
		return ['name', 'customer'];
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

	public static function getByCustomer(\selling\Customer $eCustomer): ThirdParty|\Element {

		return ThirdParty::model()
	    ->select(ThirdParty::getSelection())
	    ->whereCustomer($eCustomer)
	    ->get();

	}

	public static function getByVatNumber(string $vatNumber): ThirdParty|\Element {

		return ThirdParty::model()
	    ->select(ThirdParty::getSelection())
	    ->whereVatNumber('=', $vatNumber)
	    ->get();

	}

	public static function getByNames(string $name): ThirdParty|\Element {

		return ThirdParty::model()
	    ->select(ThirdParty::getSelection())
	    ->whereNames('LIKE', '%'.$name.'%')
	    ->get();

	}

	public static function extractWeightByCashflow(ThirdParty $eThirdParty, \bank\Cashflow $eCashflow): int {

		$memoItems = explode(' ', $eCashflow['memo']);

		$weight = 0;

		foreach($memoItems as $memoItem) {
			if(mb_strlen($memoItem) < 3) {
				continue;
			}

			$memoItem = mb_strtolower($memoItem);

			if(strtolower($eThirdParty['name']) === strtolower($memoItem)) {

				$weight += 50;

				// On a déjà vu ce terme au moins 2 fois dans des allocations précédentes
			} else if(isset($eThirdParty['memos'][$memoItem]) and $eThirdParty['memos'][$memoItem] >= 2) {

				$weight += 10 * $eThirdParty['memos'][$memoItem];

			} else if(mb_strlen($memoItem) > 3 and mb_strpos(strtolower($eThirdParty['name']), strtolower($memoItem)) !== FALSE) {

				// Plus il faut modifier, moins y'a de chances que ça soit le bon tiers.
				$weight -= levenshtein(strtolower($eThirdParty['name']), strtolower($memoItem));

			}
		}

		return $weight;

	}

	public static function filterByCashflow(\Collection $cThirdParty, \bank\Cashflow $eCashflow): \Collection {

		foreach($cThirdParty as &$eThirdParty) {

			$eThirdParty['weight'] = self::extractWeightByCashflow($eThirdParty, $eCashflow);

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

	public static function selectFromOcrData(array $data): ThirdParty {

		$eThirdParty = new \account\ThirdParty();

		if($data['vatNumber']) {

			$eThirdParty = self::getByVatNumber($data['vatNumber']);

		}

		if($eThirdParty->notEmpty()) {
			return $eThirdParty;
		}

		$eThirdParty = self::getByNames($data['name']);

		return $eThirdParty;

	}

}
?>
