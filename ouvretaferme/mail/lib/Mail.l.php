<?php
namespace mail;

class MailLib {

	protected \farm\Farm|null $eFarm = NULL;
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

	public function setFarm(\farm\Farm $eFarm): MailLib {
		$this->eFarm = $eFarm;
		return $this;
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
		$this->eFarm = NULL;
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
	public function send(): MailLib {

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
				'farm' => $this->eFarm ?? new \farm\Farm(),
				'html' => $this->bodyHtml,
				'text' => $this->bodyText,
				'subject' => $this->subject,
				'fromEmail' => $this->fromEmail ?? \Setting::get('mail\emailFrom'),
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
			->select('id', 'html', 'text', 'subject', 'fromEmail', 'fromName', 'to', 'bcc', 'replyTo', 'attachments')
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

		BrevoLib::send($eEmail);

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
			->where('sentAt < NOW() - INTERVAL 2 WEEK')
			->delete();

	}

}
?>
