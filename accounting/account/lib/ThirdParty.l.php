<?php
namespace account;

class ThirdPartyLib extends ThirdPartyCrud {

	const EXCLUDED_WORDS = ['vir', 'sas', 'web', 'mlle', 'les', 'sasu', 'mme', 'paiement', 'carte', 'votre', 'inst', 'faveur', 'virement', 'emis', 'vers', 'facture', 'remise', 'cheque', 'especes', 'versement', 'prelevement', 'devis', 'earl'];

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

		$memoItems = self::normalizeName($eCashflow['name']);

		$weight = 0;

		// Recherche par memo
		foreach($memoItems as $memoItem) {
			if(mb_strlen($memoItem) < 3) {
				continue;
			}

			$memoItem = mb_strtolower($memoItem);

			if(strtolower($eThirdParty['name']) === strtolower($memoItem)) {

				$weight += 50;

				// On a déjà vu ce terme au moins 2 fois dans des allocations précédentes
			} else if(isset($eThirdParty['memos'][$memoItem])) {

				$weight += 10 * $eThirdParty['memos'][$memoItem];

			} else if(mb_strlen($memoItem) > 3 and mb_strpos(strtolower($eThirdParty['name']), strtolower($memoItem)) !== FALSE) {

				// Plus il faut modifier, moins y'a de chances que ça soit le bon tiers. - pas forcément très fiable...
				//$weight -= levenshtein(strtolower($eThirdParty['name']), strtolower($memoItem));

			}
		}

		// Recherche par name
		$weight += self::scoreNameMatch($eThirdParty['name'], $eCashflow['name']);

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
			return $prefix.str_pad('001', 5, '0', STR_PAD_LEFT);
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

	public static function recalculateMemos(\bank\Cashflow $eCashflow, ThirdParty $eThirdParty): ThirdParty {

		$memos = explode(' ', $eCashflow['name']);
		if($eThirdParty['memos'] === NULL) {
			$eThirdParty['memos'] = [];
		}

		foreach($memos as $memo) {
			$loweredMemo = mb_strtolower($memo);
			if(
				mb_strlen($loweredMemo) <= 3
				or in_array($loweredMemo, ['paiement', 'carte', 'votre', 'inst', 'faveur', 'virement', 'emis', 'vers', 'facture', 'remise', 'cheque', 'especes', 'versement', 'prelevement', 'sepa', 'interne'])
			) {
				continue;
			}

			$textToArray = str_split(str_replace(' ', '', $loweredMemo));
			$numbers = count(array_filter($textToArray, function ($item) { return is_numeric($item); }));

			// On ne garde pas les memo avec plus de 3 chiffres (comme des dates ou des numéros de référence)
			if($numbers >= 3) {
				continue;
			}

			if(isset($eThirdParty['memos'][$loweredMemo]) === FALSE) {
				$eThirdParty['memos'][$loweredMemo] = 0;
			}
			$eThirdParty['memos'][$loweredMemo]++;
		}

		return $eThirdParty;
	}

	public static function normalizeName(string $name): array {
		// minuscules
		$name = mb_strtolower($name, 'UTF-8');

		// accents → sans accents
		$name = iconv('UTF-8', 'ASCII//TRANSLIT', $name);

		// suppression chiffres et symboles
		$name = preg_replace('/[^a-z\s]/', ' ', $name);

		// collapse espaces
		$name = preg_replace('/\s+/', ' ', $name);

		return array_values(array_filter(explode(' ', trim($name)), fn($val) => mb_strlen($val) >= 3 and in_array($val, self::EXCLUDED_WORDS) === FALSE));
	}

	// Chat GPT a écrit cette fonction
	// https://fr.wikipedia.org/wiki/Distance_de_Jaro-Winkler
	public static function jaroWinkler(string $s1, string $s2): float {
		// implémentation adaptée de Wikipédia, robuste et courte
		$m = 0;
		$t = 0;
		$l = 0;

		$s1_len = strlen($s1);
		$s2_len = strlen($s2);

		if ($s1_len === 0 && $s2_len === 0) return 1.0;

		$match_distance = (int) floor(max($s1_len, $s2_len) / 2) - 1;

		$s1_matches = array_fill(0, $s1_len, false);
		$s2_matches = array_fill(0, $s2_len, false);

		// Count matches
		for ($i = 0; $i < $s1_len; $i++) {
			$start = max(0, $i - $match_distance);
			$end   = min($i + $match_distance + 1, $s2_len);

			for ($j = $start; $j < $end; $j++) {
				if ($s2_matches[$j]) continue;
				if ($s1[$i] !== $s2[$j]) continue;
				$s1_matches[$i] = $s2_matches[$j] = true;
				$m++;
				break;
			}
		}

		if ($m === 0) return 0.0;

		// Count transpositions
		$k = 0;
		for ($i = 0; $i < $s1_len; $i++) {
			if (!$s1_matches[$i]) continue;
			while (!$s2_matches[$k]) $k++;
			if ($s1[$i] !== $s2[$k]) $t++;
			$k++;
		}

		$jaro = (1/3) * ($m / $s1_len + $m / $s2_len + ($m - $t/2) / $m);

		// Jaro-Winkler prefix
		for ($i = 0; $i < min(4, $s1_len, $s2_len); $i++) {
			if ($s1[$i] === $s2[$i]) $l++;
			else break;
		}

		return $jaro + $l * 0.1 * (1 - $jaro);
	}

	public static function scoreNameMatch(string $thirdPartyName, string $comparisonString): float {

		$comparisonNormalized = self::normalizeName($comparisonString);
		$thirdPartyNormalized = self::normalizeName($thirdPartyName);

		$score = 0;
		$matches = 0;

		foreach($thirdPartyNormalized as $thirdPartyWord) {
			foreach($comparisonNormalized as $comparedWord) {
				if($thirdPartyWord === $comparedWord) {
					$score += 100;
					$matches++;
					continue 2;
				}

				$sim = self::jaroWinkler($thirdPartyWord, $comparedWord);

				if ($sim >= 0.95) {
					// quasi identique
					$score += 100;
					$matches++;
					continue 2;
				}

				if ($sim >= 0.90) {
					// variante légère acceptable
					$score += 60;
					$matches++;
					continue 2;
				}
			}
		}

		// ratio : il faut au moins 30% des mots du tiers présents
		if(count($thirdPartyNormalized) > 0 && ($matches / count($thirdPartyNormalized)) < 0.30) {
			return 0; // rejet direct
		}

		return $score;
	}

	public static function create(ThirdParty $e): void {

		ThirdParty::model()->beginTransaction();

		$e['normalizedName'] = self::normalizeName($e['name']);

		parent::create($e);

		ThirdParty::model()->commit();

	}


}
?>
