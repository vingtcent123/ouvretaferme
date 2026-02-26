<?php
/**
 * Synchronise les factures et les événements des factures des fermes qui ont activé la PA
 *
 */
new Page()
	->cron('index', function($data) {

		$cCompanyCron = \company\CompanyCron::model()
			->select(\company\CompanyCron::getSelection() + ['farm' => ['id', 'legalCountry']])
			->whereAction(\company\CompanyCronLib::SUPER_PDP_INITIALIZE)
			->whereStatus(\company\CompanyCron::WAITING)
			->getCollection();

		foreach($cCompanyCron as $eCompanyCron) {

			\farm\FarmLib::connectDatabase($eCompanyCron['farm']);

			$updated = \company\CompanyCron::model()->update($eCompanyCron, ['status' => \company\CompanyCron::PROCESSING]);

			if($updated === 0) {
				continue;
			}

			$token = \pdp\ConnectionLib::getValidToken();

			$affected = \account\Partner::model()
				->wherePartner(\account\PartnerSetting::SUPER_PDP)
				->update(['synchronization' => \account\Partner::IN_PROGRESS]);

			if($affected !== 1) {
				continue;
			}

			try {

				\pdp\InvoiceLib::synchronize($eCompanyCron['farm'], $token);
				\pdp\EventLib::synchronize($token);

				\account\Partner::model()
					->wherePartner(\account\PartnerSetting::SUPER_PDP)
					->update(['synchronization' => \account\Partner::DONE, 'synchronizedAt' => new Sql('NOW()')]);

				\company\CompanyCron::model()->delete($eCompanyCron);

			} catch (Exception $e) {

				\account\Partner::model()
					->wherePartner(\account\PartnerSetting::SUPER_PDP)
					->update(['synchronization' => \account\Partner::FAIL]);

				trigger_error('Error while synchronizing farm #'.$eCompanyCron['farm']['id'].' : '.$e->getMessage());

			}

		}

	}, interval: 'permanent@2');
?>
<?php
