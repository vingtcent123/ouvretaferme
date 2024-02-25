<?php
namespace website;

class WebsiteLib extends WebsiteCrud {

	public static function getPropertiesCreate(): array {
		return self::getPropertiesWrite();
	}

	public static function getPropertiesUpdate(): array {
		return self::getPropertiesWrite();
	}

	public static function getPropertiesWrite(): array {
		return ['internalDomain', 'domain', 'name', 'description'];
	}

	public static function getByFarm(\farm\Farm $eFarm): Website {

		return Website::model()
			->select(Website::getSelection())
			->whereFarm($eFarm)
			->get();

	}

	public static function getByInternalDomain(string $domain): Website {

		return Website::model()
			->select(Website::getSelection())
			->whereInternalDomain($domain)
			->get();

	}

	public static function getByDomain(string $domain): Website {

		return Website::model()
			->select(Website::getSelection())
			->whereDomain($domain)
			->get();

	}

	public static function create(Website $e): void {

		$e->expects(['farm', 'domain']);

		Website::model()->beginTransaction();

		try {

			if($e['domain'] !== NULL) {
				$e['domainStatus'] = Website::PENDING;
			}

			Website::model()->insert($e);

			$cTemplate = TemplateLib::getAutocreate();

			foreach($cTemplate as $eTemplate) {

				$eWebpage = new Webpage([
					'website' => $e,
					'farm' => $e['farm'],
					'template' => $eTemplate,
					'url' => $eTemplate['defaultUrl'],
					'title' => $eTemplate['defaultTitle'],
					'description' => $eTemplate['defaultDescription'],
					'content' => $eTemplate['defaultContent'],
					'status' => Webpage::ACTIVE
				]);

				Webpage::model()->insert($eWebpage);

				$eMenu = new Menu([
					'website' => $e,
					'farm' => $e['farm'],
					'webpage' => $eWebpage,
					'label' => $eTemplate['defaultLabel'],
					'status' => Menu::ACTIVE
				]);

				Menu::model()->insert($eMenu);

			}

			Website::model()->commit();

		} catch(\DuplicateException $e) {

			Website::model()->rollBack();

			switch($e->getInfo()['duplicate']) {

				case ['farm'] :
					Website::fail('farm.duplicate');
					break;

				case ['internalDomain'] :
					Website::fail('internalDomain.duplicate');
					break;

				case ['domain'] :
					Website::fail('domain.duplicate');
					break;

			}

		}

	}

	public static function update(Website $e, array $properties): void {

		if(in_array('domain', $properties)) {

			// Le domaine a changé
			if($e['domain'] !== NULL) {

				if(
					Website::model()
						->whereDomain($e['domain'])
						->exists($e) === FALSE
				) {
					$properties[] = 'domainStatus';
					$e['domainStatus'] = Website::PENDING;
				}

			} else {
				$properties[] = 'domainStatus';
				$e['domainStatus'] = NULL;
			}

		}

		parent::update($e, $properties);

	}

	public static function delete(Website $e): void {

		$e->expects(['id']);

		Website::model()->beginTransaction();

		Menu::model()
			->whereWebsite($e)
			->delete();

		Webpage::model()
			->whereWebsite($e)
			->delete();

		News::model()
			->whereWebsite($e)
			->delete();

		Website::model()->delete($e);

		Website::model()->commit();

	}

}
?>
