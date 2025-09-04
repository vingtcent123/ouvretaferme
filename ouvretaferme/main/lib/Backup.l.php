<?php
namespace main;

class BackupLib {

	const LOCAL_BACKUP_DIR = '/var/www/';
	const LOCAL_DATABASE_BACKUP_FILE = 'backup.sql';
	const LOCAL_FARMS_BACKUP_FILE = 'farms-mysql.tar.gz';
	const LOCAL_STORAGE_BACKUP_FILE = 'storage.tar.gz';
	const SERVER_BACKUP_DIR = '/home/ubuntu/otf-backup/';
	const DATABASE_FOLDER = 'mysql';
	const STORAGE_FOLDER = 'storage';

	const DURATION_BACKUP_IN_DAYS = 15;
	const DURATION_BACKUP_MAX_IN_DAYS = 365 * 7;

	public static function backupFarms(): void {

		$day = date('Y-m-d');
		[$serverUser, $serverHostname] = self::getHostData();

		$command = 'scp '.self::LOCAL_BACKUP_DIR.self::LOCAL_FARMS_BACKUP_FILE.' '.$serverUser.'@'.$serverHostname.':'.self::SERVER_BACKUP_DIR.self::DATABASE_FOLDER.'/'.$day.'-farm.tar.gz';
		self::exec($command);

	}

	public static function backupDatabase(): void {

		$day = date('Y-m-d');
		[$serverUser, $serverHostname] = self::getHostData();

		$command = 'scp '.self::LOCAL_BACKUP_DIR.self::LOCAL_DATABASE_BACKUP_FILE.' '.$serverUser.'@'.$serverHostname.':'.self::SERVER_BACKUP_DIR.self::DATABASE_FOLDER.'/'.$day.'.sql';
		self::exec($command);

	}

	public static function backupStorage(): void {

		[$serverUser, $serverHostname] = self::getHostData();
		$day = date('Y-m-d');

		$command = 'scp '.self::LOCAL_BACKUP_DIR.self::LOCAL_STORAGE_BACKUP_FILE.' '.$serverUser.'@'.$serverHostname.':'.self::SERVER_BACKUP_DIR.self::STORAGE_FOLDER.'/'.$day.'.tar.gz';
		self::exec($command);

	}

	public static function cleanDatabase(): void {

		self::doClean(self::SERVER_BACKUP_DIR.self::DATABASE_FOLDER);

	}

	public static function cleanStorage(): void {

		self::doClean(self::SERVER_BACKUP_DIR.self::STORAGE_FOLDER);

	}

	private static function getHostData(): array {

		$serverUser = MainSetting::$backupServer['user'];
		$serverHostname = MainSetting::$backupServer['hostname'];

		if(strlen($serverUser) === 0 or strlen($serverHostname) === 0) {
			throw new \Exception('backupServer must be configured.');
		}

		return [$serverUser, $serverHostname];

	}

	private static function exec(string $command): mixed {

		if(LIME_ENV === 'prod') {

			exec($command, $result);
			return $result;

		} else {

			echo $command."\n";
			return [];

		}

	}

	private static function doClean(string $folder): void {

		[$serverUser, $serverHostname] = self::getHostData();

		$today = date('Y-m-d');

		$command = 'ssh '.$serverUser.'@'.$serverHostname.' "ls '.$folder.'"';

		$files = self::exec($command);

		foreach($files as $file) {

			$fileDate = substr($file, 0, 10);
			$fileDay = substr($fileDate, -2);

			$interval = \util\DateLib::interval($today, $fileDate);
			$days = (int)($interval / 60 / 60 / 24);

			if(
				// On ne supprime pas les backups de moins de DURATION_BACKUP_IN_DAYS jours
				$days <= self::DURATION_BACKUP_IN_DAYS
				or (
					// On ne supprime pas les backups de moins de DURATION_BACKUP_MAX_IN_DAYS jours ET du 1er ou du 15
					$days <= self::DURATION_BACKUP_MAX_IN_DAYS
					and in_array($fileDay, ['01, 15'])
				)
			) {
				continue;
			}

			// Dans tous les cas on supprime les backups trop vieilles
			$command = 'ssh '.$serverUser.'@'.$serverHostname.' "rm '.$folder.'/'.$file.'"';
			self::exec($command);

		}

	}

}
?>
