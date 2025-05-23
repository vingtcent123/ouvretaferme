<?php
namespace farm;

class ArchiveLib {

	public static function getArchive(Farm $eFarm, int $year): array {

		$eArchive = Archive::model()
			->select(Archive::getSelection())
			->whereFarm($eFarm)
			->whereSeason($year)
			->sort(['createdAt' => SORT_DESC])
			->get();

		if(
			$eArchive->empty() or
			strlen($eArchive['hash'] ?? '') === 0 or
			$year >= (int)date('Y') // En cours de saison => On génère un nouveau
		) {

			$export = \selling\AnalyzeLib::getExportSales($eFarm, $year);
			array_unshift($export, new \selling\AnalyzeUi()->getExportSalesHeader($eFarm));

			$eArchive = new Archive(['farm' => $eFarm, 'season' => $year]);
			Archive::model()->insert($eArchive);
			self::putContent($eArchive, $export);

			$fileContent = trim(file_get_contents(self::getTemporaryPath($eArchive)));
			$eArchive['hash'] = self::computeHash($fileContent);

			Archive::model()
       ->select('hash')
       ->update($eArchive);

			rename(self::getTemporaryPath($eArchive), self::getPath($eArchive));

			return $export;
		}

		return self::getContent($eArchive);

	}

	public static function getDirectory(): string {

		return \storage\DriverLib::directory().'/archive/';

	}
	public static function getPath(Archive $eArchive): string {

		return self::getDirectory().$eArchive['hash'].'.csv';

	}
	public static function getTemporaryPath(Archive $eArchive): string {

		return self::getDirectory().$eArchive['id'].'.csv';

	}

	public static function getContent(Archive $eArchive): ?array {

		$path = self::getPath($eArchive);
		$handle = fopen($path, 'r');

		if($handle === FALSE) {
			return NULL;
		}

		$export = [];
		$nextLine = fgets($handle, 1000000);
		while($nextLine !== FALSE) {
			$export[] = str_getcsv(trim($nextLine), ';', escape: '');
			$nextLine = fgets($handle, 1000000);
		}
		fclose($handle);

		return $export;

	}

	public static function putContent(Archive $eArchive, array $export): void {

		$path = self::getTemporaryPath($eArchive);

		if(is_dir(self::getDirectory()) === FALSE) {
			@mkdir(self::getDirectory(), 0777, TRUE);
		}

		$fp = fopen($path, 'w+');

		foreach($export as $line) {
			fputcsv($fp, $line, separator: ';', escape: '');
		}

		fclose($fp);
	}

	/**
	 * Create a hash based on the archive content
	 */
	public static function computeHash(string $content): string {

		return hash('sha256', \Setting::get('farm\archiveSalt').$content);

	}

}
