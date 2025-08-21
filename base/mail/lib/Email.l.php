<?php
namespace mail;

class EmailLib extends EmailCrud {

	public static function getByCustomer(\selling\Customer $eCustomer): \Collection {

		return Email::model()
			->select(Email::getSelection())
			->whereCustomer($eCustomer)
			->whereStatus('NOT IN', [Email::WAITING, Email::SENDING])
			->sort([
				'createdAt' => SORT_DESC
			])
			->getCollection(0, 100);

	}

	public static function getByCampaign(Campaign $eCampaign): \Collection {

		return Email::model()
			->select(Email::getSelection())
			->whereCampaign($eCampaign)
			->sort([
				'to' => SORT_ASC
			])
			->getCollection();

	}

	public static function clean(): void {

		\mail\Email::model()
			->whereStatus(\mail\Email::SENT)
			->where('sentAt < NOW() - INTERVAL 12 MONTH')
			->delete();

	}

}
?>
