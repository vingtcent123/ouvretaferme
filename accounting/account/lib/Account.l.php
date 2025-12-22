<?php
namespace account;

class AccountLib extends AccountCrud {

	public static function getByClass(string $class): Account {

		$eAccount = new Account();

		Account::model()
			->select(Account::getSelection())
			->whereClass($class)
			->get($eAccount);

		return $eAccount;

	}

	public static function countByClass(string $class): int {

		return Account::model()
			->whereClass($class)
			->count();

	}

	public static function getByClasses(array $classes, string $index = 'id'): \Collection {

		return Account::model()
			->select(
				['name' => new \Sql('CONCAT(class, ". ", description)')]
				+ Account::getSelection()
				+ ['vatAccount' => ['class', 'vatRate', 'description']]
			)
			->whereClass('IN', $classes)
			->getCollection(NULL, NULL, $index);

	}

	public static function getByIdsWithVatAccount(array $ids): \Collection {

		return Account::model()
			->select(
				['name' => new \Sql('CONCAT(class, ". ", description)')]
				+ Account::getSelection()
				+ ['vatAccount' => ['class', 'vatRate', 'description']]
			)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, 'id');

	}

	public static function getByPrefixWithVatAccount(string $prefix): Account {

		$eAccount = new Account();
		Account::model()
			->select(
				['name' => new \Sql('CONCAT(class, ". ", description)')]
				+ Account::getSelection()
				+ ['vatAccount' => ['class', 'vatRate', 'description']]
			)
			->whereClass('LIKE', $prefix.'%')
			->get($eAccount);

		return $eAccount;

	}

	public static function getByIdWithVatAccount(int $id): Account {

		$eAccount = new Account();
		Account::model()
			->select(
				['name' => new \Sql('CONCAT(class, ". ", description)')]
				+ Account::getSelection()
				+ ['vatAccount' => ['class', 'vatRate', 'description']]
			)
			->whereId('=', $id)
			->get($eAccount);

		return $eAccount;
	}

	public static function getAll(?\Search $search = new \Search(), string $query = ''): \Collection {

		if($search->get('classPrefixes')) {
			Account::model()->where(fn() => 'class LIKE "'.join('%" OR class LIKE "', $search->get('classPrefixes')).'%"', if: $search->get('classPrefixes'));
		}

		if($search->has('stock')) {
			Account::model()->where('class LIKE "%'.$search->has('stock').'%"');
		}

		if($query !== '') {

			$query = first(explode(' ', $query));

			// Recherche par numéro de compte
			if($query === (string)(int)($query)) {

				Account::model()->whereClass('LIKE', $query.'%');

			// Recherche textuelle
			} else {

				Account::model()->where('class LIKE "%'.$query.'%" OR description LIKE "%'.$query.'%"', if: $query !== '');

				$keywords = [];

				$query = trim(preg_replace('/[+\-><\(\)~*\"@]+/', ' ', $query));

				foreach(preg_split('/\s+/', $query) as $word) {
					$keywords[] = '*'.$word.'*';
				}

				$match = 'MATCH(description) AGAINST ('.Account::model()->format(implode(' ', $keywords)).' IN BOOLEAN MODE)';

				Account::model()->where($match.' > 0');
			}
		}
		return Account::model()
			->select(
        ['name' => new \Sql('CONCAT(class, ". ", description)')]
        + Account::getSelection()
				+ ['journalCode' => ['id', 'code', 'name']],
      )
			->sort(['class' => SORT_ASC])
			->where('class LIKE "'.$search->get('classPrefix').'%"', if: $search->get('classPrefix'))
			->whereClass('IN', fn() => $search->get('class'), if: $search->has('class') and is_array($search->get('class')))
			->whereDescription('LIKE', '%'.$search->get('description').'%', if: $search->get('description'))
			->whereCustom(TRUE, if: $search->get('customFilter') === TRUE)
			->whereVisible(TRUE, if: $search->get('visible') === TRUE)
			->where('vatAccount IS NOT NULL', if: $search->get('vatFilter') === TRUE)
			->getCollection(NULL, NULL, 'id');
	}

	/**
	 * @param \Collection $cAccount Tous les comptes (potentiellement filtrés par la recherche)
	 * @param int|null $thirdParty Filtre par tiers
	 * @param array $accountsAlreadyUsed Accounts déjà utilisés dans le formulaire de création
	 * @return \Collection Tous les comptes retriés
	 */
	public static function orderAccounts(\Collection $cAccount, ?int $thirdParty, array $accountsAlreadyUsed): \Collection {

		$cOperationByUsage = \journal\OperationLib::getOrderedByUsage();

		$cAccountByThirdParty = new \Collection();
		$cAccountOthers = new \Collection();

		// Comptes liés au tiers en priorité :
		// - triés par nombre d'usages décroissants
		// - en mettant classes 4 (comptes de tiers : TVA) et classe 5 (comptes financiers) et classes déjà utilisées
		// Ensuite, comptes utilisés dans l'ordre décroissant d'usage (groupés par classe principale)
		$cAccountClassMatch = new \Collection();
		$cAccountClassThird = new \Collection();
		$cAccountClassUsed = new \Collection();
		$cAccountClassAfter = new \Collection();

		if($thirdParty !== NULL) {

			$eThirdParty = ThirdPartyLib::getById($thirdParty);
			$cOperationThirdParty = \journal\OperationLib::getByThirdPartyAndOrderedByUsage($eThirdParty);

			foreach($cOperationThirdParty as $eOperation) {

				if($cAccount->findById($eOperation['account'])->empty()) {
					continue;
				}

				$eAccount = $cAccount->offsetGet($eOperation['account']['id']);
				$eAccount['thirdParty'] = TRUE;
				$eAccount['used'] = FALSE;

				if(in_array($eAccount['id'], $accountsAlreadyUsed) === TRUE) {

					$cAccountClassAfter->append($eAccount);

				} else {

					switch((int)mb_substr($eAccount['class'], 0, 1)) {

						case AccountSetting::THIRD_PARTY_GENERAL_CLASS:
							$cAccountClassThird->append($eAccount);
							break;

						case AccountSetting::FINANCIAL_GENERAL_CLASS:
							$cAccountClassAfter->append($eAccount);
							break;

						default:
							$cAccountByThirdParty->append($eAccount);
					}

				}

			}
		}

		$cAccountByThirdParty->mergeCollection($cAccountClassThird);
		$cAccountByThirdParty->mergeCollection($cAccountClassAfter);

		$cAccountClassUsedGrouped = [];

		foreach([AccountSetting::CAPITAL_GENERAL_CLASS, AccountSetting::ASSET_GENERAL_CLASS, AccountSetting::STOCK_GENERAL_CLASS, AccountSetting::THIRD_PARTY_GENERAL_CLASS, AccountSetting::FINANCIAL_GENERAL_CLASS, AccountSetting::CHARGE_ACCOUNT_CLASS, AccountSetting::PRODUCT_ACCOUNT_CLASS] as $class) {
			$cAccountClassUsedGrouped[$class] = new \Collection();
		}

		if($thirdParty !== NULL) {

			foreach($cOperationByUsage as $eOperation) {

				if(
					$cAccount->findById($eOperation['account'])->empty() or
					$cAccountByThirdParty->findById($eOperation['account'])->notEmpty()
				) {
					continue;
				}

				$eAccount = $cAccount->offsetGet($eOperation['account']['id']);
				$eAccount['thirdParty'] = FALSE;
				$eAccount['used'] = TRUE;
				$class = (int)mb_substr($eAccount['class'], 0, 1);
				$cAccountClassUsedGrouped[$class]->append($eAccount);

			}

		}

		foreach($cAccountClassUsedGrouped as $cAccountClassUsedUngrouped) {
			$cAccountClassUsed->mergeCollection($cAccountClassUsedUngrouped);
		}

		// On empile tous les autres comptes
		foreach($cAccount as $eAccount) {

			if(
				$cAccountByThirdParty->findById($eAccount)->notEmpty() or
				$cAccountClassUsed->findById($eAccount)->notEmpty()
			) {
				continue;
			}

			$eAccount['thirdParty'] = FALSE;
			$eAccount['used'] = FALSE;

			if(POST('query') and ThirdPartyLib::scoreNameMatch($eAccount['description'], POST('query')) > 100) {
				$cAccountClassMatch->append($eAccount);
			} else {
				$cAccountOthers->append($eAccount);
			}

		}

		return $cAccountByThirdParty->mergeCollection($cAccountClassMatch)->mergeCollection($cAccountClassUsed)->mergeCollection($cAccountOthers);

	}

	public static function createCustomClass(array $input): void {

		$fw = new \FailWatch();

		$eAccount = new Account();
		$eAccount->build(['class', 'description', 'vatAccount', 'vatRate'], $input);

		$fw->validate();

		$eAccount['custom'] = TRUE;

		Account::model()->insert($eAccount);

	}

	public static function delete(Account $e): void {

		if($e['custom'] === FALSE) {
			throw new \NotExpectedAction();
		}

		if(\journal\OperationLib::countByAccount($e) > 0) {
			throw new \NotExpectedAction();
		}

		Account::model()->delete($e);

		LogLib::save('delete', 'Account', ['id' => $e['id'], 'class' => $e['class']]);

	}

	public static function update(Account $e, array $properties): void {

		if(in_array('vatRate', $properties)) {

			$e['vatRate'] = \selling\SellingSetting::getVatRates($e['eFarm'])[$e['vatRate']] ?? NULL;

		}

		parent::update($e, $properties);

	}
}
?>
