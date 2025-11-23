<?php
namespace association;

class HistoryLib extends HistoryCrud {

	public static function getPropertiesCreate(): array {
		return ['farm', 'type', 'membership', 'paidAt', 'amount'];
	}

	public static function countByYears(array $years): \Collection {

		return History::model()
			->select(['count' => new \Sql('COUNT(*)'), 'membership'])
			->whereMembership('IN', $years)
			->group(['membership'])
			->whereStatus(History::VALID)
			->getCollection(NULL, NULL, 'membership');

	}
	public static function getForDocument(int $id): History {

		return History::model()
			->select(
				History::getSelection()
				+ ['farm' => \farm\Farm::getSelection()]
				+ ['sale' => \selling\Sale::getSelection()]
				+ ['customer' => \selling\Customer::getSelection()]
			)
			->whereId($id)
			->get();
	}

	public static function hasDonate(\user\User $eUser, ?int $year): bool {

		$eCustomer = \selling\Customer::model()
			->select('id')
			->whereFarm(AssociationSetting::FARM)
			->whereUser($eUser)
			->get();

		if($eCustomer->empty()) {
			return FALSE;
		}

		return History::model()
			->whereCustomer($eCustomer)
			->whereCreatedAt('LIKE', $year.'-%', if: $year !== NULL)
			->whereStatus(History::VALID)
			->exists();

	}

	public static function getByFarm(\farm\Farm $eFarm): \Collection {

		return History::model()
			->select(History::getSelection())
			->whereFarm($eFarm)
			->sort(['updatedAt' => SORT_DESC])
			->getCollection();

	}

	public static function updateByPaymentIntentId(string $paymentIntentId, array $values): void {

		History::model()
			->wherePaymentIntentId($paymentIntentId)
			->update($values);
	}

	public static function associatePaymentIntentId(string $id): void {

		$eStripeFarm = MembershipLib::getAssociationStripeFarm();

		try {
			$checkout = \payment\StripeLib::getStripeCheckoutSessionFromPaymentIntent($eStripeFarm, $id);
		}
		catch(\Exception $e) {
			trigger_error("Stripe: ".$e->getMessage());
			return;
		}

		if($checkout['data'] === []) {
			return;
		}

		History::model()
			->whereCheckoutId($checkout['data'][0]['id'])
			->update([
				'paymentIntentId' => $id
			]);

	}

	public static function getByPaymentIntentId(string $id): History {

		$eHistory = new History();

		History::model()
			->select(History::getSelection())
			->wherePaymentIntentId($id)
			->get($eHistory);

		if($eHistory->notEmpty()) {
			return $eHistory;
		}

		self::associatePaymentIntentId($id);

		History::model()
			->select(History::getSelection())
			->wherePaymentIntentId($id)
			->get($eHistory);

		return $eHistory;

	}

	public static function generateDocument(History $eHistory): ?string {

		$eContent = new \pdf\Content();

		$hash = NULL;
		$content = self::generateDocumentContent($eHistory);

		new \media\AssociationDocumentLib()->send($eContent, $hash, $content, 'pdf');

		History::model()->update($eHistory, ['document' => $hash]);

		return self::getPdfContent($hash);

	}

	public static function getPdfContent(string $document): ?string {

		$path = \storage\DriverLib::directory().'/'.new \media\AssociationDocumentUi()->getBasenameByHash($document);
		return file_get_contents($path);

	}

	private static function generateDocumentContent(History $eHistory): string {

		$url = \Lime::getUrl().'/association/pdf?id='.$eHistory['id'].'&remoteKey='.\Lime::getRemoteKey('selling');

		$title = new AssociationUi()->getPdfTitle();

		return \Cache::redis()->lock(
			'pdf-'.$url, function () use ($title, $url) {

			$file = tempnam('/tmp', 'pdf-').'.pdf';

			$args = '"--url='.$url.'"';
			$args .= ' "--destination='.$file.'"';
			$args .= ' "--title='.rawurlencode($title).'"';

			exec('node '.LIME_DIRECTORY.'/ouvretaferme/main/nodejs/pdf.js '.$args.' 2>&1');

			$content = file_get_contents($file);

			unlink($file);

			return $content;

		}, fn() => throw new \FailAction('association\History::fileLocked'), 5);

	}
}
?>
