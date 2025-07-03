<?php
namespace mail;

class SendLib {

	protected \farm\Farm|null $eFarm = NULL;
	protected ?string $fromEmail = NULL;
	protected ?string $fromName = NULL;
	protected ?string $to = NULL;
	protected ?string $bcc = NULL;
	protected ?string $replyTo = NULL;
	protected ?string $template = NULL;
	protected ?string $bodyText = NULL;
	protected ?string $bodyHtml = NULL;
	protected ?string $subject = NULL;

	protected array $attachments = [];

	public function __construct() {
	}

	public function setFarm(\farm\Farm $eFarm): SendLib {
		$this->eFarm = $eFarm;
		return $this;
	}

	public function setFromEmail(?string $fromEmail): SendLib {
		$this->fromEmail = $fromEmail;
		return $this;
	}

	public function setFromName(?string $fromName): SendLib {
		$this->fromName = $fromName;
		return $this;
	}

	public function setReplyTo(?string $email): SendLib {
		$this->replyTo = $email;
		return $this;
	}

	public function setTemplate(string $template): SendLib {
		$this->template = $template;
		return $this;
	}

	public function addAttachment(string $content, string $name, string $type): SendLib {
		$this->attachments[] = [
			'content' => $content,
			'name' => $name,
			'type' => $type
		];
		return $this;
	}

	public function setTo(string $to): SendLib {
		$this->to = $to;
		return $this;
	}

	public function setBcc(string $email): SendLib {
		$this->bcc = $email;
		return $this;
	}

	public function setContent(string $subject, ?string $bodyText = NULL, ?string $bodyHtml = NULL): SendLib {

		$this->subject = $subject;
		$this->bodyText = $bodyText;
		$this->bodyHtml = $bodyHtml;

		return $this;

	}

	public function setBodyText(string $bodyText): SendLib {
		$this->bodyText = $bodyText;
		return $this;
	}

	public function setBodyHtml(string $bodyHtml): SendLib {
		$this->bodyHtml = $bodyHtml;
		return $this;
	}

	public function setSubject(string $subject): SendLib {
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
	public function send(): SendLib {

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

			if($eEmail['farm']->notEmpty()) {
				$eContact = ContactLib::getByEmail($eEmail, autoCreate: TRUE);
			} else {
				$eContact = new Contact();
			}

			$eEmail['contact'] = $eContact;

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
			->select(Email::getSelection())
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

		try {

			BrevoLib::send($eEmail);

			$eEmail['sentAt'] = new \Sql('NOW()');
			$eEmail['status'] = Email::SENT;

			\mail\Email::model()
				->select('sentAt', 'status')
				->update($eEmail);

		}
		catch(\Exception $e) {

			trigger_error("Brevo error with mail #".$eEmail['id'].' ('.$e->getMessage().')');

			$eEmail['status'] = Email::ERROR_PROVIDER;

			\mail\Email::model()
				->select('status')
				->update($eEmail);

		}

		ContactLib::updateEmailStatus($eEmail);

	}

	public static function clean(): void {

		\mail\Email::model()
			->whereStatus(\mail\Email::SENT)
			->where('sentAt < NOW() - INTERVAL 1 MONTH')
			->delete();

	}

}
?>
