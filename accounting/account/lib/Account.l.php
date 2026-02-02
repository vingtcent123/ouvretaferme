<?php
namespace account;

class AccountLib extends AccountCrud {

	public static function getPropertiesCreate(): array {
		return self::getPropertiesUpdate();
	}

	public static function getPropertiesUpdate(): array {
		return ['class', 'description', 'vatAccount', 'vatRate'];
	}

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
			->select(Account::getSelection())
			->whereClass('IN', $classes)
			->getCollection(NULL, NULL, $index);

	}

	public static function getByIdsWithVatAccount(array $ids): \Collection {

		return Account::model()
			->select(Account::getSelection())
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, 'id');

	}

	public static function getByPrefixWithVatAccount(string $prefix): Account {

		$eAccount = new Account();
		Account::model()
			->select(Account::getSelection())
			->whereClass('LIKE', $prefix.'%')
			->get($eAccount);

		return $eAccount;

	}

	public static function getByIdWithVatAccount(int $id): Account {

		$eAccount = new Account();
		Account::model()
			->select(Account::getSelection())
			->whereId('=', $id)
			->get($eAccount);

		return $eAccount;
	}

	private static function applySearch(\Search $search, string $query = ''): AccountModel {

		if($query !== '') {

			$query = first(explode(' ', $query));

			// Recherche par numéro de compte
			if($query === (string)(int)($query)) {

				Account::model()->whereClass('LIKE', $query.'%');

			// Recherche textuelle
			} else {

				Account::model()->or(
					fn() => $this->whereClass('LIKE', '%'.$query.'%'),
					fn() => $this->whereDescription('LIKE', '%'.$query.'%')
				);

				$keywords = [];

				$query = trim(preg_replace('/[+\-><\(\)~*\"@]+/', ' ', $query));

				$words = array_filter(preg_split('/\s+/', $query));
				if(count($words) > 0) {

					foreach($words as $word) {
						$keywords[] = '*'.$word.'*';
					}

					$match = 'MATCH(description) AGAINST ('.Account::model()->format(implode(' ', $keywords)).' IN BOOLEAN MODE)';

					Account::model()->where($match.' > 0');
				}
			}
		}

		return Account::model()
			->where(fn() => 'class LIKE "'.join('%" OR class LIKE "', $search->get('classPrefixes')).'%"', if: $search->has('classPrefixes') and $search->get('classPrefixes'))
			->where('class LIKE "%'.$search->get('stock').'%"', if: $search->has('stock') and $search->get('stock')->notEmpty())
			->whereClass('LIKE', $search->get('classPrefix').'%', if: $search->get('classPrefix'))
			->whereClass('IN', fn() => $search->get('class'), if: $search->has('class') and is_array($search->get('class')))
			->whereDescription('LIKE', '%'.$search->get('description').'%', if: $search->get('description'))
			->whereCustom(TRUE, if: $search->get('customFilter') === TRUE)
			->whereVisible(TRUE, if: $search->get('visible') === TRUE)
			->where('vatAccount IS NOT NULL', if: $search->get('vatFilter') === TRUE)
		;

	}

	public static function getForList(?\Search $search = new \Search(), string $query = ''): \Collection {

		return self::applySearch($search, $query)
			->select(Account::getSelection() + [
				'nOperation' => \journal\Operation::model()
					->select('id')
					->delegateCollection('account', callback: fn(\Collection $cOperation) => $cOperation->count()),
				'vatAccount' => ['id', 'class', 'vatRate', 'description'],
				'journalCode' => fn($e) => \journal\JournalCodeLib::ask($e['journalCode'], \farm\Farm::getConnected())
			])
			->sort(['class' => SORT_ASC])
			->getCollection(NULL, NULL, 'id');

	}

	public static function getAll(?\Search $search = new \Search(), string $query = ''): \Collection {

		return self::applySearch($search, $query)
			->select(
				Account::getSelection() +
				($search->get('withVat') ? ['vatAccount' => ['id', 'class', 'vatRate', 'description']] : []) +
				($search->get('withJournal') ? ['journalCode' => fn($e) => \journal\JournalCodeLib::ask($e['journalCode'], \farm\Farm::getConnected())] : [])
			)
			->sort(['class' => SORT_ASC])
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

	public static function create(Account $e): void {

		if(($e['vatRate'] ?? NULL) != NULL) {

			$vatRates = \selling\SellingSetting::getVatRates($e['eFarm']);

			if(array_key_exists($e['vatRate'], $vatRates)) {
				$e['vatRate'] = $vatRates[$e['vatRate']];
			} else {
				$e['vatRate'] = NULL;
			}

		}

		$e['custom'] = TRUE;

		Account::model()->insert($e);

	}

	public static function delete(Account $e): void {

		Account::model()->beginTransaction();

			Account::model()->delete($e);

			$eFarm = \farm\Farm::getConnected();

			\selling\Product::model()
				->where('privateAccount = '.$e['id'].' OR proAccount = '.$e['id'])
				->whereFarm($eFarm)
				->update([
					'privateAccount' => new \Sql('IF(privateAccount = '.$e['id'].', NULL, privateAccount)'),
					'proAccount' => new \Sql('IF(proAccount = '.$e['id'].', NULL, proAccount)'),
				]);

			\selling\Item::model()
				->whereFarm($eFarm)
				->whereAccount($e)
				->update(['account' => NULL]);

		Account::model()->commit();

		LogLib::save('delete', 'Account', ['id' => $e['id'], 'class' => $e['class']]);

	}

	public static function update(Account $e, array $properties): void {

		Account::model()->beginTransaction();

			if(in_array('vatRate', $properties)) {

				$e['vatRate'] = \selling\SellingSetting::getVatRates($e['eFarm'])[$e['vatRate']] ?? NULL;

			}

			parent::update($e, $properties);

			if(in_array('class', $properties)) {

				$newClass = AccountLabelLib::pad($e['class']);

				\journal\Operation::model()
					->whereAccount($e)
					->update(['accountLabel' => $newClass]);

			}

		Account::model()->commit();

	}
}
?>
