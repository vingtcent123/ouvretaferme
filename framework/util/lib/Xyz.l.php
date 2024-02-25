<?php
namespace util;

/**
 * To use directly the XYZ file system
 * File paths needs to be relative paths
 *
 * @author Émilie Guth
 */
class XyzLib {

	public static function getFileResource(string $path): \Imagick {

		return new \Imagick(self::getFileName($path));

	}

	public static function sendResource(\Imagick $resource, string $path): void {

		$absolutePath = self::getFileName($path);
		$directory = dirname($absolutePath);

		if(is_dir($directory) === FALSE) {
			mkdir($directory, 0777, TRUE);
		}

		$resource->writeImage($absolutePath);

	}

	public static function sendBinary(string $data, string $path): void {

		$absolutePath = self::getFileName($path);
		$directory = dirname($absolutePath);

		if(is_dir($directory) === FALSE) {
			mkdir($directory, 0777, TRUE);
		}

		file_put_contents($absolutePath, $data);

	}

	public static function saveMetadata(string $path, array $metadata): void {

		file_put_contents(self::getFileName($path).'.txt', json_encode($metadata));

	}

	public static function copy(string $fileFrom, string $fileTo): void {

		copy(self::getFileName($fileFrom), self::getFileName($fileTo));

	}

	public static function getMetadata(string $path): array {

		$content = file_get_contents(self::getFileName($path.'.txt'));

		if($content !== FALSE) {
			return json_decode($content, TRUE);
		} else {
			return [];
		}

	}

	public static function delete(string $filePath) {

		$absolutePath = self::getFileName($filePath);

		if(is_file($absolutePath) === FALSE) {
			return;
		}

		unlink($absolutePath);

	}

	public static function getFileName(string $file): string {
		return self::directory().'/'.$file;
	}

	public static function directory() {
		if(LIME_ENV === 'dev') {
			return '/var/www/storage';
		}
		return '/var/www/storage';
	}

}

?>