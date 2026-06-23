<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\AiIntegrations;

/**
 * Default prompt templates for the AI integrations.
 *
 * Templates use sprintf placeholders (%s); see the referenced argument order
 * in the doc block of each constant.
 */
final class DefaultPrompts {

	/**
	 * Preamble prepended to the thread contents when generating event data.
	 */
	public const EVENT_DATA_PREAMBLE = <<<PROMPT
		I am scheduling an event based on an email thread and need an event title and agenda. Provide the result as JSON with keys for "title" and "agenda". For example ```{ "title": "Project kick-off meeting", "agenda": "* Introduction\\n* Project goals\\n* Next steps" }```.

		The email contents are:

		PROMPT;

	/**
	 * Arguments (in order): language code, message body.
	 */
	public const SUMMARIZE_MESSAGE = "You are tasked with formulating a helpful summary of a email message. \r\nThe summary should be in the language of this language code %s. \r\nThe summary should be less than 160 characters. \r\nOutput *ONLY* the summary itself, leave out any introduction. \r\nHere is the ***E-MAIL*** for which you must generate a helpful summary: \r\n***START_OF_E-MAIL***\r\n%s\r\n***END_OF_E-MAIL***\r\n";

	/**
	 * Arguments (in order): message body.
	 */
	public const SMART_REPLY = <<<PROMPT
		You are tasked with formulating helpful replies or reply templates to e-mails provided that have been sent to me. If you don't know some relevant information for answering the e-mails (like my schedule) leave blanks in the text that can later be filled by me. You must write the replies from my point of view as replies to the original sender of the provided e-mail!

		Formulate two extremely succinct reply suggestions to the provided ***E-MAIL***. Please, do not invent any context for the replies but, rather, leave blanks for me to fill in with relevant information where necessary. Provide the output formatted as valid JSON with the keys 'reply1' and 'reply2' for the reply suggestions.

		Each suggestion must be of 25 characters or less.

		Here is the ***E-MAIL*** for which you must suggest the replies to:

		***START_OF_E-MAIL***%s

		***END_OF_E-MAIL***

		Please, output *ONLY* a valid JSON string with the keys 'reply1' and 'reply2' for the reply suggestions. Leave out any other text besides the JSON! Be extremely succinct and write the replies from my point of view.
		PROMPT;

	/**
	 * Arguments (in order): message body.
	 */
	public const REQUIRES_FOLLOW_UP = <<<PROMPT
		Consider the following TypeScript function prototype:
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

		emailText: "%s"
		The JSON output should be in the form: {"expectsReply": true}
		Never return null or undefined.
		PROMPT;

	/**
	 * Arguments (in order): message body, language code.
	 */
	public const REQUIRES_TRANSLATION = <<<PROMPT
		Consider the following TypeScript function prototype:
		---
		/**
		 * This function takes in an email text and returns a boolean indicating whether the email needs translation from a specific language.
		 *
		 * @param emailText - string with the email text
		 * @param language - the language code to check against (e.g., 'en', 'de', etc.)
		 * @returns boolean true if the email is written in a different language than the one specified and needs translation, false if it is written in the specified language.
		 * only return true if whole sentences are written in a different language, not just a word or two.
		 */
		declare function isEmailWrittenInLanguage(emailText: string, language: string): Promise<boolean>;
		---
		Tell me what the function outputs for the following parameters.

		emailText: "%s"
		language: "%s"
		The JSON output should be in the form: {"needsTranslation": true}
		Never return null or undefined.
		PROMPT;
}
