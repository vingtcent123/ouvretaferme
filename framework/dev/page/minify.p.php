<?php

/**
 * Gets minified files
 *
 * @author Émilie Guth
 */
(new Page())
	->get('/minify/{version}/{filename}', function($data) {

		$isCrawler = (\util\DeviceLib::get() === 'crawler');

		$version = GET('version');
		$filename = GET('filename');

		if(str_contains($filename, '.') === FALSE) {

			if($isCrawler === FALSE) { // Crawlers may have a weird behavior
				throw new NotExpectedAction('Invalid filename');
			} else {
				throw new StatusAction(410);
			}


		}

		[, $type] = explode('.', $filename, 2);

		$contentType = match($type) {
			'css' => 'text/css',
			'js' => 'application/javascript',
			default => throw new NotExpectedAction('Wrong type asked')
		};

		$files = explode(',', GET('m'));

		// Check filename consistency
		$filenameCheck = \dev\MinifyLib::buildFilename($files, $type);

		if($filenameCheck !== $filename) {
			throw new StatusAction(410);
		}

		// Check version consistency
		$versionCheck = \Asset::getVersion();

		if($versionCheck !== $version) {

			if(LIME_ENV === 'prod') {
				throw new RedirectAction('/minify/'.$versionCheck.'/'.$filename.'?m='.urlencode(GET('m')));
			} else {
				throw new Exception('Version mismatch ('.$versionCheck.' expected, '.$version.' got)');
			}

		}

		$realDirectory = Setting::get('dev\minifyDirectory').'/'.LIME_APP.'/'.$version;
		$realFilename = $realDirectory.'/'.$filename;

		if(file_exists($realFilename) === FALSE) {

			$content = '';

			foreach($files as $file) {

				if(
					str_contains($file, '..') or // Security check
					str_contains($file, ':') === FALSE
				) { // : needed to separate app/pkg from css|lib|js/path/to/file
					throw new NotExpectedAction('Invalid file path ('.$file.')');
				}

				if(preg_match("/([a-z]+):([a-zA-Z0-9\/\.\-\_]+)/", $file, $matches) === FALSE) {
					throw new NotExpectedAction('Invalid file path');
				}

				[, $package, $mediaFile] = $matches;
				$app = \Package::getApp($package);

				$filepath = LIME_DIRECTORY.'/'.$app.'/'.$package.'/asset/'.$mediaFile.'.'.$type;

				if(is_file($filepath)) {
					$content .= file_get_contents($filepath)."\n";
				} else {

					if($isCrawler === FALSE) {
						$eUserOnline = \user\ConnectionLib::getOnline();
						if($eUserOnline->notEmpty() and $eUserOnline['role']['fqn'] === 'admin') {
							throw new Exception('File \''.$file.'\' does not exist');
						} else {
							throw new NotExpectedAction('File \''.$file.'\' does not exist');
						}
					} else {
						// Just ignore the file for crawlers
					}

				}
			}

			// Some replacements
			$content = str_replace('"use strict";', '', $content); // Not compatible with ES6

			// we do not generate the file if it is being generated already by another user
			if(Cache::redis()->add('minify-'.$realFilename, 'lock', 60) !== FALSE) {

				if(is_dir($realDirectory) === FALSE) {
					@mkdir($realDirectory, 0777, TRUE);
				}

				file_put_contents($realFilename, $content);

				Cache::redis()->delete('minify-'.$realFilename);

			}

		} else {

			$content = file_get_contents($realFilename);

		}

		throw new DataAction($content, $contentType);

	});
?>
