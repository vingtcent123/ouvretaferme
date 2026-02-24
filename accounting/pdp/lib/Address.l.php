<?php
namespace pdp;

class AddressLib extends AddressCrud {

	const FR_SCHEME_ID = '0225';
	const FR_PUBLIC_SCHEME_ID = '0224';
	const BE_VAT_SCHEME_ID = '9925';
	const BE_SCHEME_ID = '0208';

	public static function getPropertiesCreate(): array {
		return ['electronicAddress'];
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
				\Fail::log('Address::electronicAddress.check');
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

	public static function getSchemeIdentifier(\farm\Farm $eFarm = new \farm\Farm()): string {

		if($eFarm->empty()) {
			$eFarm = \farm\Farm::getConnected();
		}

		if($eFarm->isFR()) {

			return self::FR_SCHEME_ID;

		} else if($eFarm->isBE()) {

			if($eFarm->getConf('hasVat')) {
				return self::BE_VAT_SCHEME_ID;
			}
			return self::BE_SCHEME_ID;

		}

		throw new \Exception('Unhandled farm for e-invoicing');

	}

	public static function formatElectronicAddress(string $address): string {

		return self::getSchemeIdentifier().':'.$address;

	}

	public static function getSchemesByCountry(\user\Country $eCountry): array {

		if($eCountry->isFR()) {

			return [AddressLib::FR_SCHEME_ID, AddressLib::FR_PUBLIC_SCHEME_ID];

		}

		if($eCountry->isBE()) {

			return [AddressLib::BE_SCHEME_ID, AddressLib::BE_VAT_SCHEME_ID];

		}

		return [];
	}

	public static function get(): string {

		return Address::model()
			->select(Address::getSelection())
			->whereIsReplyTo(FALSE)
			->sort(['createdAt' => SORT_DESC])
			->get();

	}

	public static function countValidAddresses(): int {

		return Address::model()
			->whereIsReplyTo(FALSE)
			->count();

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
					'electronicAddress' => $directory['identifier'],
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
			'identifier' => $eAddress['electronicAddress'],
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
