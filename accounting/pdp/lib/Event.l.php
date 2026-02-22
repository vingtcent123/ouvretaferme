<?php
namespace pdp;

class EventLib {

	public static function synchronize(string $accessToken) {

		$eEventLast = \invoicing\Event::model()
			->select('id')
			->sort(['createdAt' => SORT_DESC])
			->get();

		$hasAfter = TRUE;

		$body = [];

		if($eEventLast->notEmpty()) {
			$lastId = $eEventLast['id'];
		} else {
			$lastId = NULL;
		}

		while($hasAfter === TRUE) {

			if($lastId !== NULL) {
				$body['starting_after_id'] = $lastId;
			}

			$data = CurlLib::send($accessToken, PdpSetting::SUPER_PDP_API_URL.'invoice_events', http_build_query($body), 'GET');
			$hasAfter = ($data['has_after'] ?? FALSE);
			if($data === NULL) {
				return;
			}

			\invoicing\Event::model()->beginTransaction();

			foreach($data['data'] as $event) {

				$eEvent = new \invoicing\Event([
					'id' => $event['id'],
					'invoice' => new \invoicing\Invoice(['id' => $event['invoice_id']]),
					'createdAt' => date('Y-m-d H:i:s', strtotime($event['created_at'])),
					'statusCode' => $event['status_code'],
					'statusText' => $event['status_text'],
				]);

				\invoicing\Event::model()->insert($eEvent);

			}

			\invoicing\Invoice::model()->commit();
		}

	}
}
