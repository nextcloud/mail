<?php
/**
 * ownCloud - Mail app
 *
 * @author Thomas Müller
 * @copyright 2013-2014 Thomas Müller thomas.mueller@tmit.eu
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Controller;

use Horde_Imap_Client;
use OCA\Mail\Http\AttachmentDownloadResponse;
use OCA\Mail\Http\HtmlResponse;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\ContactsIntegration;
use OCA\Mail\Service\IMailBox;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;

class MessagesController extends Controller {

	/** @var AccountService */
	private $accountService;

	/**
	 * @var string
	 */
	private $currentUserId;

	/**
	 * @var ContactsIntegration
	 */
	private $contactsIntegration;

	/**
	 * @var \OCA\Mail\Service\Logger
	 */
	private $logger;

	/**
	 * @var \OCP\Files\Folder
	 */
	private $userFolder;

	/**
	 * @var IL10N
	 */
	private $l10n;

	/**
	 * @param string $appName
	 * @param \OCP\IRequest $request
	 * @param AccountService $accountService
	 * @param $currentUserId
	 * @param $userFolder
	 * @param $contactsIntegration
	 * @param $logger
	 * @param $l10n
	 */
	public function __construct($appName,
								$request,
								AccountService $accountService,
								$currentUserId,
								$userFolder,
								$contactsIntegration,
								$logger,
								$l10n) {
		parent::__construct($appName, $request);
		$this->accountService = $accountService;
		$this->currentUserId = $currentUserId;
		$this->userFolder = $userFolder;
		$this->contactsIntegration = $contactsIntegration;
		$this->logger = $logger;
		$this->l10n = $l10n;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $accountId
	 * @param string $folderId
	 * @param int $from
	 * @param int $to
	 * @param string $filter
	 * @return JSONResponse
	 */
	public function index($accountId, $folderId, $from=0, $to=20, $filter=null) {
		$mailBox = $this->getFolder($accountId, $folderId);

		$this->logger->debug("loading messages $from to $to of folder <$folderId>");

		$json = $mailBox->getMessages($from, $to-$from+1, $filter);

		$ci = $this->contactsIntegration;
		$json = array_map(function($j) use ($ci, $mailBox) {
			if ($mailBox->getSpecialRole() === 'trash') {
				$j['delete'] = (string)$this->l10n->t('Delete permanently');
			}

			if ($mailBox->getSpecialRole() === 'sent') {
				$j['fromEmail'] = $j['toEmail'];
				$j['from'] = $j['to'];
				if((count($j['toList']) > 1) || (count($j['ccList']) > 0)) {
					$j['from'] .= ' ' . $this->l10n->t('& others');
				}
			}

			$j['senderImage'] = $ci->getPhoto($j['fromEmail']);
			return $j;
		}, $json);

		return new JSONResponse($json);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $accountId
	 * @param string $folderId
	 * @param mixed $id
	 * @return JSONResponse
	 */
	public function show($accountId, $folderId, $id) {
		$mailBox = $this->getFolder($accountId, $folderId);
		$account = $this->getAccount($accountId);
		try {
			$m = $mailBox->getMessage($id);
		} catch (DoesNotExistException $ex) {
			return new JSONResponse([], 404);
		}
		$json = $m->getFullMessage($account->getEmail(), $mailBox->getSpecialRole());
		$json['senderImage'] = $this->contactsIntegration->getPhoto($m->getFromEmail());
		if (isset($json['hasHtmlBody'])){
			$json['htmlBodyUrl'] = $this->buildHtmlBodyUrl($accountId, $folderId, $id);
		}

		if (isset($json['attachment'])) {
			$json['attachment'] = $this->enrichDownloadUrl($accountId, $folderId, $id, $json['attachment']);
		}
		if (isset($json['attachments'])) {
			$json['attachments'] = array_map(function($a) use ($accountId, $folderId, $id) {
				return $this->enrichDownloadUrl($accountId, $folderId, $id, $a);
			}, $json['attachments']);
		}

		return new JSONResponse($json);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $accountId
	 * @param string $folderId
	 * @param string $messageId
	 * @return \OCA\Mail\Http\HtmlResponse
	 */
	public function getHtmlBody($accountId, $folderId, $messageId) {
		try {
			$mailBox = $this->getFolder($accountId, $folderId);

			$m = $mailBox->getMessage($messageId, true);
			$html = $m->getHtmlBody($accountId, $folderId, $messageId, function($cid) use ($m){
				$match = array_filter($m->attachments, function($a) use($cid){
					return $a['cid'] === $cid;
				});
				$match = array_shift($match);
				if (is_null($match)) {
					return null;
				}
				return $match['id'];
			});

			$htmlResponse = new HtmlResponse($html);

			// Harden the default security policy
			// FIXME: Remove once ownCloud 8.1 is a requirement for the mail app
			if(class_exists('\OCP\AppFramework\Http\ContentSecurityPolicy')) {
				$policy = new ContentSecurityPolicy();
				$policy->allowEvalScript(false);
				$policy->disallowScriptDomain('\'self\'');
				$policy->disallowConnectDomain('\'self\'');
				$policy->disallowFontDomain('\'self\'');
				$policy->disallowMediaDomain('\'self\'');
				$htmlResponse->setContentSecurityPolicy($policy);
			}

			// Enable caching
			$htmlResponse->cacheFor(60 * 60);
			$htmlResponse->addHeader('Pragma', 'cache');

			return $htmlResponse;
		} catch(\Exception $ex) {
			return new TemplateResponse($this->appName, 'error', ['message' => $ex->getMessage()], 'none');
		}
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $accountId
	 * @param string folderId
	 * @param string $messageId
	 * @param string $attachmentId
	 * @return AttachmentDownloadResponse
	 */
	public function downloadAttachment($accountId, $folderId, $messageId, $attachmentId) {
		$mailBox = $this->getFolder($accountId, $folderId);

		$attachment = $mailBox->getAttachment($messageId, $attachmentId);

		return new AttachmentDownloadResponse(
			$attachment->getContents(),
			$attachment->getName(),
			$attachment->getType());
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $accountId
	 * @param string $folderId
	 * @param string $messageId
	 * @param string $attachmentId
	 * @param string $targetPath
	 * @return JSONResponse
	 */
	public function saveAttachment($accountId, $folderId, $messageId, $attachmentId, $targetPath) {
		$mailBox = $this->getFolder($accountId, $folderId);

		$attachmentIds = [$attachmentId];
		if($attachmentId === 0) {
			$m = $mailBox->getMessage($messageId);
			$attachmentIds = array_map(function($a){
				return $a['id'];
			}, $m->attachments);
		}
		foreach($attachmentIds as $attachmentId) {
			$attachment = $mailBox->getAttachment($messageId, $attachmentId);

			$fileName = $attachment->getName();
			$fileParts = pathinfo($fileName);
			$fileName = $fileParts['filename'];
			$fileExtension = $fileParts['extension'];
			$fullPath = "$targetPath/$fileName.$fileExtension";
			$counter = 2;
			while($this->userFolder->nodeExists($fullPath)) {
				$fullPath = "$targetPath/$fileName ($counter).$fileExtension";
				$counter++;
			}

			$newFile = $this->userFolder->newFile($fullPath);
			$newFile->putContent($attachment->getContents());
		}

		return new JSONResponse();
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $accountId
	 * @param string $folderId
	 * @param string $messageId
	 * @param boolean $starred
	 * @return JSONResponse
	 */
	public function toggleStar($accountId, $folderId, $messageId, $starred) {
		$mailBox = $this->getFolder($accountId, $folderId);

		$mailBox->setMessageFlag($messageId, Horde_Imap_Client::FLAG_FLAGGED, !$starred);

		return new JSONResponse();
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $accountId
	 * @param string $folderId
	 * @param string $id
	 * @return JSONResponse
	 */
	public function destroy($accountId, $folderId, $id) {
		$this->logger->debug("deleting message <$id> of folder <$folderId>, account <$accountId>");
		try {
			$account = $this->getAccount($accountId);
			$account->deleteMessage(base64_decode($folderId), $id);
			return new JSONResponse();

		} catch (DoesNotExistException $e) {
			$this->logger->error("could not delete message <$id> of folder <$folderId>, "
				. "account <$accountId> because it does not exist");
			return new JSONResponse([], 404);
		}
	}

	/**
	 * @param int $accountId
	 * @return \OCA\Mail\Service\IAccount
	 */
	private function getAccount($accountId) {
		return $this->accountService->find($this->currentUserId, $accountId);
	}

	/**
	 * @param int $accountId
	 * @param string $folderId
	 * @return IMailBox
	 */
	private function getFolder($accountId, $folderId) {
		$account = $this->getAccount($accountId);
		return $account->getMailbox(base64_decode($folderId));
	}

	/**
	 * @param string $messageId
	 * @param $accountId
	 * @param $folderId
	 * @return callable
	 */
	private function enrichDownloadUrl($accountId, $folderId, $messageId, $attachment) {
		$downloadUrl = \OCP\Util::linkToRoute('mail.messages.downloadAttachment', [
			'accountId' => $accountId,
			'folderId' => $folderId,
			'messageId' => $messageId,
			'attachmentId' => $attachment['id'],
		]);
		$downloadUrl = \OC::$server->getURLGenerator()->getAbsoluteURL($downloadUrl);
		$attachment['downloadUrl'] = $downloadUrl;
		$attachment['mimeUrl'] = \OC_Helper::mimetypeIcon($attachment['mime']);
		return $attachment;
	}

	/**
	 * @param string $accountId
	 * @param string $folderId
	 * @param string $messageId
	 * @return string
	 */
	private function buildHtmlBodyUrl($accountId, $folderId, $messageId) {
		$htmlBodyUrl = \OC::$server->getURLGenerator()->linkToRoute('mail.messages.getHtmlBody', [
			'accountId' => $accountId,
			'folderId' => $folderId,
			'messageId' => $messageId,
		]);
		return \OC::$server->getURLGenerator()->getAbsoluteURL($htmlBodyUrl);
	}

}
