<?php
namespace website;

class DomainLib {

	public static function buildRewrites(): void {

		$cWebsite = Website::model()
			->select('id', 'domain', 'domainStatus')
			->whereDomainStatus('IN', [Website::PENDING, Website::CERTIFICATE_CREATED])
			->getCollection();

		foreach($cWebsite as $eWebsite) {

			$domain = self::getDomain($eWebsite);

			$file = self::getRewriteFile($eWebsite);

			if(is_file($file)) {
				unlink($file);
			}

			$content = self::getRewriteContent($domain, match($eWebsite['domainStatus']) {
				Website::PENDING => FALSE,
				Website::CERTIFICATE_CREATED => TRUE
			});

			file_put_contents($file, $content);

			if(is_file($file)) {

				Website::model()->update($eWebsite, [
					'domainStatus' => match($eWebsite['domainStatus']) {
						Website::PENDING => Website::CONFIGURED_UNSECURED,
						Website::CERTIFICATE_CREATED => Website::CONFIGURED_SECURED
					}
				]);

			}

		}

		if($cWebsite->notEmpty()) {
			exec('sudo /usr/sbin/service nginx reload');  // /etc/sudoers
		}

	}

	/*
	 * Supprimer les fichiers de rewrite obsolètes
	 */
	public static function cleanRewrites(): void {

		$files = glob(self::getRewriteDirectory().'/*');

		foreach($files as $file) {

			$id = (int)pathinfo($file, PATHINFO_FILENAME);

			if(Website::model()
				->whereId($id)
				->whereDomain('!=', NULL)
				->exists() === FALSE) {
				unlink($file);
			}

		}

	}

	protected static function getRewriteDirectory(): string {
		return '/var/www/otf-rewrite';
	}

	protected static function getRewriteFile(Website $eWebsite): string {
		return self::getRewriteDirectory().'/'.$eWebsite['id'].'.cfg';
	}

	protected static function getRewriteContent(string $url, bool $ssl): string {

		$listen = ($ssl ? 'listen 443 ssl' : 'listen 80');

		$certificate = $ssl ? 'ssl_certificate /etc/letsencrypt/live/'.$url.'/fullchain.pem;
	ssl_certificate_key /etc/letsencrypt/live/'.$url.'/privkey.pem;
	include /etc/letsencrypt/options-ssl-nginx.conf;
	ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;' : '';

		$rewrite =

'server {

	'.$listen.';
	server_name '.$url.';
	root /var/www/otf;

	'.$certificate.'

	include /var/www/otf/framework/dev/conf/rewrite/bot.cfg;
	include /var/www/otf/framework/dev/conf/rewrite/asset.cfg;

	location @minify {
		rewrite ^(.*)$ /_lime?$args&limeEnv=prod&limeApp=ouvretaferme&limeName=$1? last;
	}

	location / {
		rewrite ^(\/[a-zA-Z0-9\:\_\-\.]*)$ /_lime?$args&origin=external&limeEnv=prod&limeApp=ouvretaferme&limeName=/public/'.$url.'$1? last;
		rewrite ^(.*)$ /_lime?$args&origin=external&limeEnv=prod&limeApp=ouvretaferme&limeName=/public/'.$url.'/400? last;
	}
	
	include /var/www/otf/framework/dev/conf/rewrite/lime.cfg;
	
}
';

		if($ssl) {

			$rewrite .=

'server {

	listen 80;
	server_name '.$url.';

	return 301 https://'.$url.'$request_uri;

}

';

		}

		return $rewrite;

	}

	public static function buildPingUnsecure(): void {

		$cWebsite = Website::model()
			->select('id', 'domain')
			->whereDomainStatus('IN', [Website::CONFIGURED_UNSECURED, Website::FAILURE_UNSECURED])
			->getCollection();

		if($cWebsite->notEmpty()) {
			exec('sudo /usr/bin/systemd-resolve --flush-caches');  // /etc/sudoers
		}

		foreach($cWebsite as $eWebsite) {

			$domain = self::getDomain($eWebsite);

			$ping = @file_get_contents('http://'.$domain.'/:test');

			if(str_starts_with($ping, 'OK')) {

				Website::model()->update($eWebsite, [
					'domainStatus' => Website::PINGED_UNSECURED
				]);

			} else {

				Website::model()->update($eWebsite, [
					'domainStatus' => Website::FAILURE_UNSECURED
				]);

			}

		}

	}

	public static function buildCertificate(): void {

		$cWebsite = Website::model()
			->select('id', 'domain')
			->whereDomainStatus('IN',  [Website::PINGED_UNSECURED, Website::FAILURE_CERTIFICATE_CREATED])
			->getCollection();

		foreach($cWebsite as $eWebsite) {

			$domain = self::getDomain($eWebsite);

			// Création du certificat
			exec('sudo /usr/bin/certbot certonly --authenticator standalone -d '.$domain.' --pre-hook "service nginx stop" --post-hook "service nginx start" -n');

			if(is_file('/etc/letsencrypt/renewal/'.$domain.'.conf')) {

				Website::model()->update($eWebsite, [
					'domainStatus' => Website::CERTIFICATE_CREATED
				]);

			} else {

				Website::model()->update($eWebsite, [
					'domainStatus' => Website::FAILURE_CERTIFICATE_CREATED
				]);

			}

		}

		if($cWebsite->notEmpty()) {
			exec('sudo /usr/sbin/service nginx reload');  // /etc/sudoers
		}

	}

	public static function buildPingSecure(): void {

		$cWebsite = Website::model()
			->select('id', 'domain')
			->whereDomainStatus('IN', [Website::CONFIGURED_SECURED, Website::FAILURE_SECURED])
			->getCollection();

		foreach($cWebsite as $eWebsite) {

			$domain = self::getDomain($eWebsite);

			$ping = @file_get_contents('https://'.$domain.'/:test');

			if(str_starts_with($ping, 'OK')) {

				Website::model()->update($eWebsite, [
					'domainStatus' => Website::PINGED_SECURED
				]);

			} else {

				Website::model()->update($eWebsite, [
					'domainStatus' => Website::FAILURE_SECURED
				]);

			}

		}

	}

	private static function getDomain(Website $eWebsite): string {
		return match(LIME_ENV) {
			'dev' => 'dev-'.$eWebsite['domain'],
			'prod' => $eWebsite['domain']
		};
	}

}
?>
