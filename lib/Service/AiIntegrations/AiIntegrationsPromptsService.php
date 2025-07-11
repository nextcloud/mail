<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Mail\Service\AiIntegrations;

use OCP\AppFramework\Services\IAppConfig;

class AiIntegrationsPromptsService {
	public function __construct(
		private IAppConfig $appConfig,
	) {
	}

	private const EVENT_DATA_PROMPT_PREAMBLE = <<<PROMPT
I am scheduling an event based on an email thread and need an event title and agenda. Provide the result as JSON with keys for "title" and "agenda". For example ```{ "title": "Project kick-off meeting", "agenda": "* Introduction\\n* Project goals\\n* Next steps" }```.

The email contents are:

PROMPT;

	private const SUMMARIZE_EMAIL_PROMPT = "You are tasked with formulating a helpful summary of a email message. \r\n"
		. "The summary should be less than 160 characters. \r\n"
		. "Output *ONLY* the summary itself, leave out any introduction. \r\n"
		. "Here is the ***E-MAIL*** for which you must generate a helpful summary: \r\n";

	private const SMART_REPLY_PROMPT_PREAMPBLE = "You are tasked with formulating helpful replies or reply templates to e-mails provided that have been sent to me. If you don't know some relevant information for answering the e-mails (like my schedule) leave blanks in the text that can later be filled by me. You must write the replies from my point of view as replies to the original sender of the provided e-mail!

			Formulate two extremely succinct reply suggestions to the provided ***E-MAIL***. Please, do not invent any context for the replies but, rather, leave blanks for me to fill in with relevant information where necessary. Provide the output formatted as valid JSON with the keys 'reply1' and 'reply2' for the reply suggestions.

			Each suggestion must be of 25 characters or less.

			Here is the ***E-MAIL*** for which you must suggest the replies to: ***START_OF_E-MAIL***";
	private const SMART_REPLY_PROMPT_POSTAMBLE = "***END_OF_E-MAIL***

			Please, output *ONLY* a valid JSON string with the keys 'reply1' and 'reply2' for the reply suggestions. Leave out any other text besides the JSON! Be extremely succinct and write the replies from my point of view.";


	private const REQUIRES_FOLLOWUP_PROMPT_PREAMPBLE = "Consider the following TypeScript function prototype:
---
/**
 * This function takes in an email text and returns a boolean indicating whether the email author expects a response.
 *
 * @param emailText - string with the email text
 * @returns boolean true if the email expects a reply, false if not
 */
declare function doesEmailExpectReply(emailText: string): Promise<boolean>;
---
Tell me what the function outputs for the following parameters.

emailText:\r\n";

	private const REQUIRES_FOLLOWUP_PROMPT_POSTAMBLE = "\r\nThe JSON output should be in the form: {\"expectsReply\": true}
Never return null or undefined.";

	public function getEventDataPromptPreamble(): string {
		return $this->appConfig->getValueString('mail', 'event_data_prompt_preamble', self::EVENT_DATA_PROMPT_PREAMBLE);
	}
	public function getSummarizeEmailPrompt(): string {
		return $this->appConfig->getValueString('mail', 'summarize_email_prompt', self::SUMMARIZE_EMAIL_PROMPT);
	}
	public function getSmartReplyPrompt(string $message): string {
		return $this->appConfig->getValueString('mail', 'smart_reply_prompt_preamble', self::SMART_REPLY_PROMPT_PREAMPBLE) . $message . $this->appConfig->getAppValue('mail', 'smart_reply_prompt_postamble', self::SMART_REPLY_PROMPT_POSTAMBLE);
	}
	public function getSmartReplyPromptPreamble(): string {
		return $this->appConfig->getValueString('mail', 'smart_reply_prompt_preamble', self::SMART_REPLY_PROMPT_PREAMPBLE);
	}
	public function getSmartReplyPromptPostamble(): string {
		return $this->appConfig->getValueString('mail', 'smart_reply_prompt_postamble', self::SMART_REPLY_PROMPT_POSTAMBLE);
	}

	public function getRequiresFollowupPrompt(string $message): string {
		return $this->appConfig->getValueString('mail', 'requires_followup_prompt_preamble', self::REQUIRES_FOLLOWUP_PROMPT_PREAMPBLE) . $message . $this->appConfig->getAppValue('mail', 'requires_followup_prompt_postamble', self::REQUIRES_FOLLOWUP_PROMPT_POSTAMBLE);
	}
	public function getRequiresFollowupPromptPreamble(): string {
		return $this->appConfig->getValueString('mail', 'requires_followup_prompt_preamble', self::REQUIRES_FOLLOWUP_PROMPT_PREAMPBLE);
	}
	public function getRequiresFollowupPromptPostamble(): string {
		return $this->appConfig->getValueString('mail', 'requires_followup_prompt_postamble', self::REQUIRES_FOLLOWUP_PROMPT_POSTAMBLE);
	}

	public function setEventDataPromptPreamble(string $preamble): void {
		$this->appConfig->setValueString('mail', 'event_data_prompt_preamble', $preamble);
	}
	public function setSummarizeEmailPrompt(string $prompt): void {
		$this->appConfig->setValueString('mail', 'summarize_email_prompt', $prompt);
	}
	public function setSmartReplyPreamble(string $prompt): void {
		$this->appConfig->setValueString('mail', 'smart_reply_prompt_preamble', $prompt);
	}

	public function setSmartReplyPostamble(string $postamble): void {
		$this->appConfig->setValueString('mail', 'smart_reply_prompt_postamble', $postamble);
	}
	public function setRequiresFollowupPreamble(string $preamble): void {
		$this->appConfig->setValueString('mail', 'requires_followup_prompt_preamble', $preamble);
	}
	public function setRequiresFollowupPostamble(string $postamble): void {
		$this->appConfig->setValueString('mail', 'requires_followup_prompt_postamble', $postamble);
	}


}
