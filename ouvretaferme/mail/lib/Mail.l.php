<?php
namespace mail;

require_once __DIR__.'/PHPMailer-6.5.1/src/PHPMailer.php';
require_once __DIR__.'/PHPMailer-6.5.1/src/SMTP.php';
require_once __DIR__.'/PHPMailer-6.5.1/src/Exception.php';

class MailLib {

	protected ?string $fromEmail = NULL;
	protected ?string $fromName = NULL;
	protected ?string $to = NULL;
	protected ?string $bcc = NULL;
	protected ?string $replyTo = NULL;
	protected ?string $bodyText = NULL;
	protected ?string $bodyHtml = NULL;
	protected ?string $subject = NULL;

	protected array $attachments = [];

	public function __construct() {
	}

	public function setFromEmail(?string $fromEmail): MailLib {
		$this->fromEmail = $fromEmail;
		return $this;
	}

	public function setFromName(?string $fromName): MailLib {
		$this->fromName = $fromName;
		return $this;
	}

	public function setReplyTo(?string $email): MailLib {
		$this->replyTo = $email;
		return $this;
	}

	public function setTemplate(string $template): MailLib {
		$this->template = $template;
		return $this;
	}

	public function addAttachment(string $content, string $name, string $type): MailLib {
		$this->attachments[] = [
			'content' => $content,
			'name' => $name,
			'type' => $type
		];
		return $this;
	}

	public function setTo(string $to): MailLib {
		$this->to = $to;
		return $this;
	}

	public function setBcc(string $email): MailLib {
		$this->bcc = $email;
		return $this;
	}

	public function setContent(string $subject, ?string $bodyText = NULL, ?string $bodyHtml = NULL): MailLib {

		$this->subject = $subject;
		$this->bodyText = $bodyText;
		$this->bodyHtml = $bodyHtml;

		return $this;

	}

	public function setBodyText(string $bodyText): MailLib {
		$this->bodyText = $bodyText;
		return $this;
	}

	public function setBodyHtml(string $bodyHtml): MailLib {
		$this->bodyHtml = $bodyHtml;
		return $this;
	}

	public function setSubject(string $subject): MailLib {
		$this->subject = $subject;
		return $this;
	}

	/**
	 * Reset mail parameters.
	 */
	public function reset() {
		$this->fromEmail = NULL;
		$this->fromName = NULL;
		$this->to = NULL;
		$this->bcc = NULL;
		$this->replyTo = NULL;
		$this->bodyText = '';
		$this->bodyHtml = '';
		$this->subject = '';
		$this->attachments = [];
	}

	/**
	 * Send the mail.
	 */
	public function send(string $server): MailLib {

		if(
			$this->to === NULL or
			(empty($this->bodyText) and empty($this->bodyHtml)) or
			empty($this->subject)
		) {

			throw new \Exception('Internal error: it needs at least to set: a to address ('.var_export($this->to, true).'), a subject ('.var_export($this->subject, true).'), a body (text or html) to send a mail.');

		} else {

			// /!\ Do not touch
			if(LIME_ENV === 'dev') {

				if(in_array($this->to, \Setting::get('mail\devSendOnly')) === FALSE) {
					return $this;
				}

				$this->subject = '['.LIME_ENV.'] '.$this->subject;

			}

			$eEmail = new \mail\Email([
				'html' => $this->bodyHtml,
				'text' => $this->bodyText,
				'subject' => $this->subject,
				'server' => $server,
				'fromEmail' => $this->fromEmail,
				'fromName' => $this->fromName ?? \Setting::get('mail\emailName'),
				'to' => $this->to,
				'bcc' => $this->bcc,
				'replyTo' => $this->replyTo,
				'attachments' => serialize($this->attachments)
			]);

			\mail\Email::model()->insert($eEmail);

			if(LIME_ENV === 'dev') {
				$this->doSend($eEmail);
			}

		}

		$this->reset();

		return $this;


	}

	/**
	 * Sends all waiting emails
	 *
	 */
	public static function sendWaiting(): void {

		$cEmail = \mail\Email::model()
			->select('id', 'html', 'text', 'subject', 'server', 'fromEmail', 'fromName', 'to', 'bcc', 'replyTo', 'attachments')
			->whereStatus(\mail\Email::WAITING)
			->getCollection();

		foreach($cEmail as $eEmail) {

			$affected = \mail\Email::model()
				->whereStatus(Email::WAITING)
				->update($eEmail, ['status' => \mail\Email::SENDING]);

			if($affected > 0) {
				self::doSend($eEmail);
			}

		}

	}

	private static function doSend(Email $eEmail): void {

		$server = \Setting::get('mail\smtpServers')[$eEmail['server']];

		$mail = new \PHPMailer\PHPMailer\PHPMailer();
		$mail->isSMTP();
		$mail->Host = $server['host'];
		$mail->Port = $server['port'];
		$mail->CharSet = 'UTF-8';
		$mail->SMTPAuth = TRUE;
		$mail->Username = $server['user'];
		$mail->Password = $server['password'];
		$mail->SMTPSecure = $server['secure'];

		$mail->setFrom($eEmail['fromEmail'] ?? $server['from'], $eEmail['fromName']);
		$mail->addAddress($eEmail['to']);

		if($eEmail['replyTo'] !== NULL) {
			$mail->addReplyTo($eEmail['replyTo']);
		}

		if($eEmail['bcc'] !== NULL) {
			$mail->addBCC($eEmail['bcc']);
		}

		$attachments = unserialize($eEmail['attachments']);

		foreach($attachments as $attachment) {
			$mail->addStringAttachment($attachment['content'], $attachment['name'], type: $attachment['type']);
		}

		//Content
		if($eEmail['html']) {
			$mail->isHTML(TRUE);
			$mail->Subject = $eEmail['subject'];
			$mail->Body = $eEmail['html'];
			$mail->AltBody = $eEmail['text'];
		} else {
			$mail->isHTML(FALSE);
			$mail->Subject = $eEmail['subject'];
			$mail->Body = $eEmail['text'];
		}

		$mail->send();

		$eEmail['sentAt'] = new \Sql('NOW()');

		\mail\Email::model()
			->update($eEmail, [
				'status' => Email::SUCCESS,
				'sentAt' => new \Sql('NOW()')
			]);

	}

	public static function clean(): void {

		\mail\Email::model()
			->whereStatus(\mail\Email::SUCCESS)
			->where('sentAt < NOW() - INTERVAL 1 WEEK')
			->delete();

	}

}
?>
