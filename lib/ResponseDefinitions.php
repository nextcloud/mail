<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail;

/**
 * @psalm-type MailIMAPFullMessage = array{
 *     uid: int<0, max>,
 *     messageId: string,
 *     from: list<array{label: string, email: string}>,
 *     to: list<array{label: string, email: string}>,
 *     replyTo: array<empty>|list<array{label: string, email: string}>,
 *     cc: array<empty>|list<array{label: string, email: string}>,
 *     bcc: array<empty>|list<array{label: string, email: string}>,
 *     subject: string,
 *     dateInt: int<0, max>,
 *     flags: array{seen: bool, flagged: bool, answered: bool, deleted: bool, draft: bool, forwarded: bool, hasAttachments: bool, mdnsent: bool, important: bool},
 *     hasHtmlBody?: bool,
 *     body?: string,
 *     dispositionNotificationTo: string,
 *     hasDkimSignature: bool,
 *     phishingDetails: array{checks: list<array{type: string, isPhishing: bool, message: string, additionalData: array}>, warning: bool},
 *     unsubscribeUrl: ?string,
 *     isOneClickUnsubscribe: bool,
 *     unsubscribeMailTo: ?string,
 *     scheduling: array<empty>|list<array{id: ?string, messageId: string, method: string, contents: string}>,
 *     attachments: array<empty>|list<array{id: int<1, max>, messageId: int<1, max>, filename: string, mime: string, size: int<0, max>, cid: ?string, disposition: string, downloadUrl?: string}>
 * }
 *
 * @psalm-type MessageApiResponse = MailIMAPFullMessage&array{
 *     signature: ?string,
 *     itineraries?: array{},
 *     id: int<1, max>,
 *     isSenderTrusted: bool,
 *     smime: array{ isSigned: bool, signatureIsValid: ?bool, isEncrypted: bool},
 *     dkimValid?: bool,
 *     rawUrl: string
 * }
 *
 * @psalm-type MessageApiAttachment = array{ name: string, mime: string, size: int<0, max>, content: string}
 */
class ResponseDefinitions {
}
