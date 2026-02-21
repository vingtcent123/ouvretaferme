<?php
namespace pdp;

Class AddressLib extends AddressCrud {

	const FR_SCHEME_ID = '0225';
	const BE_VAT_SCHEME_ID = '9925';
	const BE_SCHEME_ID = '0208';

	public static function getPropertiesCreate(): array {
		return ['identifier'];
	}

	public static function create(Address $e): void {

		Address::model()->beginTransaction();

			$eCompany = CompanyLib::get();
			$e['company'] = $eCompany;
			$e['isReplyTo'] = FALSE;
			$e['createdAt'] = new \Sql('NOW()');

			parent::create($e);

			// On la crée sur super pdp
			$result = self::send($e);

			if($result === NULL) {
				Address::model()->rollBack();
				throw new \RedirectAction(\farm\FarmUi::urlConnected(\farm\Farm::getConnected()).'/pdp/?error=pdp\\Connection::lost');
			}

			if(($result['http_status_code'] ?? NULL) === 400) {
				\Fail::log('Address::identifier.check');
				Address::model()->rollBack();
				return;
			}

			if(($result['error'] ?? NULL) === NULL) {

				$e['id'] = $result['id'];
				$e['createdAt'] = date('Y-m-d H:i:s', strtotime($result['created_at']));
				$e['status'] = $result['status'];

				Address::model()->update($e, $e->extracts(['id', 'createdAt', 'status']));

			}

		Address::model()->commit();

	}

	public static function delete(Address $e): void {

		Address::model()->beginTransaction();

			parent::delete($e);

			self::sendDelete($e);

		Address::model()->commit();

	}

	public static function formatIdentifier(string $identifier): string {

		$eFarm = \farm\Farm::getConnected();

		if($eFarm->isFR()) {

			if(LIME_ENV === 'dev') {
				return '0225:315143296_103_'.uniqid();
			}

			return self::FR_SCHEME_ID.':'.$identifier;

		} else if($eFarm->isBE()) {

			if($eFarm->getConf('hasVat')) {
				return self::BE_VAT_SCHEME_ID.':'.$identifier;
			}
			return self::BE_SCHEME_ID.':'.$identifier;

		}

		throw new Exception('Unhandled farm for e-invoicing');

	}

	/* FONCTIONS LIÉES À SUPER PDP */

	public static function synchronize(Company $eCompany): void {

		$accessToken = ConnectionLib::getValidToken();

		if($accessToken === NULL) {
			return;
		}

		$dataDirectory = CurlLib::send($accessToken, PdpSetting::SUPER_PDP_API_URL.'directory_entries', [], 'GET');

		if(($dataDirectory['error'] ?? NULL) !== NULL) {
			return;
		}

		Address::model()->beginTransaction();

			$cAddress = new \Collection();
			foreach($dataDirectory['data'] ?? [] as $directory) {
				$cAddress->append(new Address([
					'id' => $directory['id'],
					'company' => $eCompany,
					'identifier' => $directory['identifier'],
					'isReplyTo' => $directory['is_replyto'] ?? FALSE,
					'createdAt' => date('Y-m-d H:i:s', strtotime($directory['created_at'])),
					'type' => $directory['directory'],
					'status' => $directory['status'],
					'statusMessage' => $directory['status_message'] ?? NULL,
				]));
			}

			if($cAddress->notEmpty()) {

				foreach($cAddress as $eAddress) {
					Address::model()->option('add-replace')->insert($eAddress);
				}

				Address::model()
					->whereId('NOT IN', $cAddress->getIds())
					->delete();

			} else {
				Address::model()->where(TRUE)->delete();
			}

		Address::model()->commit();

	}

	private static function send(Address $eAddress) {

		$accessToken = ConnectionLib::getValidToken();

		if($accessToken === NULL) {
			return NULL;
		}

		$url = PdpSetting::SUPER_PDP_API_URL.'directory_entries';
		$params = [
			'directory' => 'peppol',
			'identifier' => $eAddress['identifier'],
		];

		return CurlLib::send($accessToken, $url, json_encode($params), 'POST');

	}

	private static function sendDelete(Address $eAddress) {

		$accessToken = ConnectionLib::getValidToken();

		if($accessToken === NULL) {
			return NULL;
		}

		$url = PdpSetting::SUPER_PDP_API_URL.'directory_entries/'.$eAddress['id'];

		return CurlLib::send($accessToken, $url, [], 'DELETE');

	}
}
