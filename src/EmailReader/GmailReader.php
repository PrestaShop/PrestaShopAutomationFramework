<?php

namespace PrestaShop\PSTAF\EmailReader;

use PrestaShop\PSTAF\Helper\Spinner;

class GmailReader
{
	private $email;
	private $password;

	public function __construct($email, $password)
	{
		$this->email = $email;
		$this->password = $password;
	}

	public function readEmails($sentTo = null)
	{
		$host = '{imap.gmail.com:993/imap/ssl}INBOX';

		$spinner = new Spinner('Could not connect to Imap server.', 60, 10000);

		$inbox = $spinner->assertBecomesTrue(function () use ($host) {
			return @imap_open($host, $this->email, $this->password);
		});

		$emails = imap_search($inbox, 'TO '.($sentTo ? $sentTo : $this->email));

		if ($emails) {
			$messages = [];
			foreach ($emails as $n)
			{
				$hinfo = imap_headerinfo($inbox, $n);

				$subject = $hinfo->subject;

				$message = [
					'subject' => $subject,
					'body' => imap_fetchbody($inbox, $n, 2)
				];

				$messages[] = $message;
			}
			return $messages;
		} else {
			return [];
		}
	}
}