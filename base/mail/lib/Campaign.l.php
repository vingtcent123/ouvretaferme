<?php
namespace mail;

class CampaignLib extends CampaignCrud {

	public static function getPropertiesCreate(): array {

		return ['scheduledAt', 'subject', 'content', 'to'];

	}

	public static function getPropertiesUpdate(): array {

		return ['scheduledAt', 'subject', 'content', 'to'];

	}

	public static function create(Campaign $e): void {

		$e['scheduled'] = count($e['to']);

		parent::create($e);

	}


	public static function countByFarm(\farm\Farm $eFarm): int {

		return Campaign::model()
			->whereFarm($eFarm)
			->count();

	}

	public static function getByFarm(\farm\Farm $eFarm, ?int $page = NULL): \Collection {

		$number = ($page === NULL) ? NULL : 100;
		$position = ($page === NULL) ? NULL : $page * $number;

		return Campaign::model()
			->select(Campaign::getSelection())
			->whereFarm($eFarm)
			->sort([
				'scheduledAt' => SORT_DESC,
				'id' => SORT_DESC
			])
			->getCollection($position, $number);

	}

	public static function getLastByFarm(\farm\Farm $eFarm, ?string $source): \Collection {

		return Campaign::model()
			->select(Campaign::getSelection())
			->whereFarm($eFarm)
			->whereSource($source)
			->sort([
				'scheduledAt' => SORT_DESC,
				'id' => SORT_DESC
			])
			->getCollection(0, 5);

	}

	public static function countScheduled(\farm\Farm $eFarm, string $date, Campaign $eCampaignExclude = new Campaign()): int {

		$week = toWeek($date);
		$dateStart = week_date_starts($week);
		$dateEnd = week_date_ends($week);

		return Campaign::model()
			->whereFarm($eFarm)
			->whereId('!=', $eCampaignExclude, if: $eCampaignExclude->notEmpty())
			->whereScheduledAt('BETWEEN', new \Sql(Campaign::model()->format($dateStart).' AND '.Campaign::model()->format($dateEnd)))
			->getValue(new \Sql('SUM(scheduled)', 'int')) ?? 0;

	}

	public static function sendConfirmed(): void {

		$cCampaign = \mail\Campaign::model()
			->select(Campaign::getSelection())
			->whereStatus(\mail\Campaign::CONFIRMED)
			->where('NOW() > scheduledAt')
			->getCollection();

		foreach($cCampaign as $eCampaign) {

			$affected = \mail\Campaign::model()
				->whereStatus(Campaign::CONFIRMED)
				->update($eCampaign, ['status' => \mail\Campaign::SENT]);

			if($affected === 0) {
				continue;
			}

			$eFarm = $eCampaign['farm'];

			$failed = 0;
			$consent = [];
			$limited = [];

			foreach($eCampaign['to'] as $to) {

				$eContact = ContactLib::get($eFarm, $to);

				// Pas de consentement
				if($eContact->canSend() === FALSE) {

					$failed++;
					$consent[] = $to;
					continue;

				}

				// Quota dépassé
				if(
					Email::model()
						->whereTo($to)
						->whereCampaign('!=', NULL)
						->whereSentAt('>', new \Sql('NOW() - INTERVAL 7 DAY'))
						->count() >= $eFarm->getContactLimit()
				) {

					$failed++;
					$limited[] = $to;
					continue;

				}

				self::sendOne($eFarm, $eCampaign, $to);

			}

			if($failed > 0) {

				Campaign::model()->update($eCampaign, [
					'failed' => new \Sql('failed + '.$failed),
					'limited' => $limited,
					'consent' => $consent
				]);

			}

		}

	}

	public static function sendOne(\farm\Farm $eFarm, Campaign $eCampaign, string $to, bool $test = FALSE): void {

		$subject = $eCampaign['subject'];

		if($test) {
			$subject = '[test] '.$subject;
		}

		$libSend = new \mail\SendLib()
			->setFarm($eFarm)
			->setReplyTo($eFarm['legalEmail'])
			->setFromName($eFarm['name'])
			->setTo($to)
			->setContent(...\mail\DesignUi::format($eFarm, $subject, new \editor\ReadorFormatterUi()->getFromXml($eCampaign['content'], ['isEmail' => TRUE]), footer: CampaignUi::unsubscribe($eFarm, $to)));

		if($test === FALSE) {
			$libSend->setCampaign($eCampaign);
		}

		$libSend->send();

	}

}
?>
