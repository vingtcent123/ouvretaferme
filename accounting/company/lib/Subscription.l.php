<?php
namespace company;

class SubscriptionLib extends SubscriptionCrud {

	public static function getPropertiesCreate(): array {
		return ['company', 'type', 'startsAt', 'endsAt'];
	}
	public static function getPropertiesUpdate(): array {
		return ['type', 'endsAt'];
	}

	public static function deleteExpiredSubscriptions(): void {

		$cSubscription = Subscription::model()
			->select(Subscription::getSelection())
      ->where('endsAt < NOW()')
			->getCollection();

		foreach($cSubscription as $eSubscription) {

			Company::model()
				->whereId($eSubscription['company']['id'])
				->update(['subscriptionType' => new \Sql('subscriptionType & ~ '.SubscriptionUi::getCompanySubscriptionTypeBySubscriptionType($eSubscription['type']))]);

			Subscription::model()
				->whereCompany($eSubscription['company'])
				->whereType($eSubscription['type'])
        ->delete();
		}

	}

	private static function addHistory(Company $eCompany, string $subscriptionType, string $from, string $to, bool $isPack, bool $isBio): void {

		$eSubscriptionHistory = new SubscriptionHistory([
			'company' => $eCompany,
			'type' => $subscriptionType,
			'isPack' => $isPack,
			'isBio' => $isBio,
			'startsAt' => $from,
			'endsAt' => $to,
		]);

		SubscriptionHistory::model()->insert($eSubscriptionHistory);

	}

	public static function getByCompanyAndType(Company $eCompany, string $type): Subscription {

		$eSubscription = new Subscription();

		Subscription::model()
			->select(Subscription::getSelection())
			->whereCompany($eCompany)
			->whereType($type)
			->get($eSubscription);

		return $eSubscription;

	}

	public static function subscribe(Company $eCompany, int $type, bool $isPack = FALSE, bool $isBio = FALSE): string {

		Subscription::model()->beginTransaction();

		$subscriptionType = SubscriptionUi::getSubscriptionTypeByCompanySubscriptionType($type);

		$startsAt = date('Y-m-d');
		$endsAt = date('Y-m-d', strtotime($startsAt.' + 1 year - 1 day'));
		$eSubscription = new Subscription([
			'company' => $eCompany,
			'type' => $subscriptionType,
			'startsAt' => $startsAt,
			'endsAt' => $endsAt,
		]);

		$message = 'activated';

		try {

			Subscription::model()->insert($eSubscription);

			$from = $startsAt;
			$to = $endsAt;

		} catch (\DuplicateException $e) {

			$eSubscriptionOld = self::getByCompanyAndType($eCompany, $subscriptionType);

			// Prolongation
			if($eSubscriptionOld['endsAt'] >= date('Y-m-d')) {

				$endsAt = date('Y-m-d', strtotime($eSubscriptionOld['endsAt'].' + 1 year'));
				$from = date('Y-m-d', strtotime($eSubscriptionOld['endsAt'].' + 1 day'));

				$message = 'prolongated';

			// Comme un premier abonnement
			} else {

				$startsAt = date('Y-m-d', strtotime($eSubscriptionOld['endsAt'].' + 1 day'));
				$endsAt = date('Y-m-d', strtotime($startsAt.' + 1 year - 1 day'));

				$from = $startsAt;

				$eSubscriptionOld['startsAt'] = $startsAt;

			}

			$to = $endsAt;

			$eSubscriptionOld['endsAt'] = $endsAt;

			Subscription::model()
				->select(['endsAt', 'startsAt'])
				->whereCompany($eSubscriptionOld['company'])
				->whereType($eSubscriptionOld['type'])
        ->update($eSubscriptionOld);

			$eSubscription = $eSubscriptionOld;

		}

		if($eCompany['subscriptionType'] === NULL) {
			$eCompany['subscriptionType'] = new \Set();
		}
		$eCompany['subscriptionType']->value($type, TRUE);
		CompanyLib::update($eCompany, ['subscriptionType']);

		self::addHistory($eCompany, $subscriptionType, $from, $to, $isPack, $isBio);

		Subscription::model()->commit();

		return $message;

	}

	public static function subscribePack(Company $eCompany): void {

		foreach([CompanyElement::ACCOUNTING, CompanyElement::PRODUCTION, CompanyElement::SALES] as $companySubscriptionType) {
			self::subscribe($eCompany, $companySubscriptionType, TRUE);
		}

	}

	public static function getHistory(Company $eCompany): \Collection {

		return SubscriptionHistory::model()
			->select(SubscriptionHistory::getSelection())
			->sort(['createdAt' => SORT_DESC])
			->whereCompany($eCompany)
			->getCollection();

	}
}
?>
