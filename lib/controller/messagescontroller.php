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

use OCA\Mail\AttachmentDownloadResponse;
use OCA\Mail\HtmlResponse;
use OCA\Mail\Service\ContactsIntegration;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http\JSONResponse;

class MessagesController extends Controller
{
	/**
	 * @var \OCA\Mail\Db\MailAccountMapper
	 */
	private $mapper;

	/**
	 * @var string
	 */
	private $currentUserId;

	/**
	 * @var ContactsIntegration
	 */
	private $contactsIntegration;

	/**
	 * @var \OCP\Files\Folder
	 */
	private $userFolder;

	public function __construct($appName, $request, $mapper, $currentUserId, $userFolder, $contactsIntegration){
		parent::__construct($appName, $request);
		$this->mapper = $mapper;
		$this->currentUserId = $currentUserId;
		$this->userFolder = $userFolder;
		$this->contactsIntegration = $contactsIntegration;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $from
	 * @param int $to
	 * @return JSONResponse
	 */
	public function index($from=0, $to=40)
	{
		$mailBox = $this->getFolder();
		$json = $mailBox->getMessages($from, $to-$from);

		$json = array_map(function($j) {
			$j['senderImage'] = $this->contactsIntegration->getPhoto($j['fromEmail']);
			return $j;
		}, $json);

		return new JSONResponse($json);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $id
	 * @return JSONResponse
	 */
	public function show($id)
	{
		$accountId = $this->params('accountId');
		$folderId = $this->params('folderId');
		$mailBox = $this->getFolder();

		$m = $mailBox->getMessage($id);
		$json = $m->as_array();
		$json['senderImage'] = App::getPhoto($m->getFromEmail());
		if ($json['hasHtmlBody']){
			$json['htmlBodyUrl'] = $this->buildHtmlBodyUrl($accountId, $folderId, $id);
		}

		if (isset($json['attachment'])) {
			$json['attachment'] = $this->enrichDownloadUrl($accountId, $folderId, $id, $json['attachment']);
		}
		if (isset($json['attachments'])) {
			$json['attachments'] = array_map(function($a) use($accountId, $folderId, $id) {
				return $this->enrichDownloadUrl($accountId, $folderId, $id, $a);
			}, $json['attachments']);
		}

		return new JSONResponse($json);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $messageId
	 * @return JSONResponse
	 */
	public function getHtmlBody($messageId) {
		$mailBox = $this->getFolder();

		$m = $mailBox->getMessage($messageId, true);
		$html = $m->getHtmlBody();

		return new HtmlResponse($html);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $messageId
	 * @param int $attachmentId
	 * @return AttachmentDownloadResponse
	 */
	public function downloadAttachment($messageId, $attachmentId)
	{
		$mailBox = $this->getFolder();

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
	 * @param int $messageId
	 * @param int $attachmentId
	 * @param string $targetPath
	 * @return JSONResponse
	 */
	public function saveAttachment($messageId, $attachmentId, $targetPath) {
		$mailBox = $this->getFolder();

		$attachmentIds = array($attachmentId);
		if($attachmentId === 0) {
			$m = $mailBox->getMessage($messageId);
			$attachmentIds = array_map(function($a){
				return $a['id'];
			}, $m->attachments);
		}
		foreach($attachmentIds as $attachmentId) {
			$attachment = $mailBox->getAttachment($messageId, $attachmentId);

			$fileName = $attachment->getName();
			$fullPath = "$targetPath/$fileName";
			$counter = 0;
			while($this->userFolder->nodeExists($fullPath)) {
				$fullPath = "$targetPath/$counter-$fileName";
				$counter++;
			}

			$newFile = $this->userFolder->newFile($fullPath);
			$newFile->putContent($attachment->getContents());
		}

		return new JSONResponse();
	}
	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 *
	 * @param int $id
	 * @return JSONResponse
	 */
	public function destroy($id)
	{
		try {
			$mailBox = $this->getFolder();
			//
			// TODO: let's see how we implement delete
			//
			$mailBox->deleteMessage($id);

			return new JSONResponse();
		} catch (DoesNotExistException $e) {
			return new JSONResponse();
		}
	}

	/**
	 * TODO: private functions below have to be removed from controller -> imap service to be build
	 */

	private function getAccount()
	{
		$accountId = $this->params('accountId');
		return $this->mapper->find($this->currentUserId, $accountId);
	}

	/**
	 * @return \OCA\Mail\Mailbox
	 */
	private function getFolder()
	{
		$account = $this->getAccount();
		$m = new \OCA\Mail\Account($account);
		$folderId = $this->params('folderId');
		return $m->getMailbox($folderId);
	}

	/**
	 * @param $id
	 * @param $accountId
	 * @param $folderId
	 * @return callable
	 */
	private function enrichDownloadUrl($accountId, $folderId, $id, $attachment) {
		$downloadUrl = \OCP\Util::linkToRoute('mail.messages.downloadAttachment', array(
			'accountId' => $accountId,
			'folderId' => $folderId,
			'messageId' => $id,
			'attachmentId' => $attachment['id'],
		));
		$downloadUrl = \OC_Helper::makeURLAbsolute($downloadUrl);
		$attachment['downloadUrl'] = $downloadUrl;
		$attachment['mimeUrl'] = \OC_Helper::mimetypeIcon($attachment['mime']);
		return $attachment;
	}

	private function buildHtmlBodyUrl($accountId, $folderId, $id) {
		$htmlBodyUrl = \OCP\Util::linkToRoute('mail.messages.getHtmlBody', array(
			'accountId' => $accountId,
			'folderId' => $folderId,
			'messageId' => $id,
			'requesttoken' => \OC_Util::callRegister(),
		));
		return \OC_Helper::makeURLAbsolute($htmlBodyUrl);
	}

}
