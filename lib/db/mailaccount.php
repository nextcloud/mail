<?php
/**
 * ownCloud - Mail app
 *
 * @author Sebastian Schmid
 * @copyright 2013 Sebastian Schmid mail@sebastian-schmid.de
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Db;

class MailAccount {

	private $ocUserId;
	private $mailAccountId;
	private $mailAccountName;
	private $email;
	private $inboundHost;
	private $inboundHostPort;
	private $inboundSslMode;
	private $inboundUser;
	private $inboundPassword;
	private $inboundService;
	private $outboundHost;
	private $outboundHostPort;
	private $outboundSslMode;
	private $outboundUser;
	private $outboundPassword;
	private $outboundService;
	
	public function __construct($fromRow=null){
		if($fromRow){
			$this->fromRow($fromRow);
		}
	}
	
	/**
	 * @return integer
	 */
	public function getOcUserId(){
		return $this->ocUserId;
	}
	
	public function setOcUserId($ocUserId){
		$this->ocUserId = $ocUserId;
	}

	/**
	 * @return int
	 */
	public function getMailAccountId(){
		return $this->mailAccountId;
	}
	
	public function setMailAccountId($mailAccountId){
		$this->mailAccountId = $mailAccountId;
	}
	
	public function getMailAccountName(){
		return $this->mailAccountName;
	}
	
	public function setMailAccountName($mailAccountName){
		$this->mailAccountName = $mailAccountName;
	}
	
	public function getEmail(){
		return $this->email;
	}
	
	public function setEmail($email){
		$this->email = $email;
	}
	
	public function getInboundHost(){
		return $this->inboundHost;
	}
	
	public function setInboundHost($inboundHost){
		$this->inboundHost = $inboundHost;
	}
	
	public function getInboundHostPort(){
		return $this->inboundHostPort;
	}
	
	public function setInboundHostPort($inboundHostPort){
		$this->inboundHostPort = $inboundHostPort;
	}
	
	public function getInboundSslMode(){
		return $this->inboundSslMode;
	}
	
	public function setInboundSslMode($inboundSslMode){
		$this->inboundSslMode = $inboundSslMode;
	}
	
	public function getInboundUser(){
		return $this->inboundUser;
	}
	
	public function setInboundUser($inboundUser){
		$this->inboundUser = $inboundUser;
	}
	
	public function getInboundPassword(){
		//return $this->decryptPassword($this->inboundPassword);
		return $this->inboundPassword;
	}
	
	public function setInboundPassword($inboundPassword){
		//$this->inboundPassword = $this->encryptPassword($inboundPassword);
		$this->inboundPassword = $inboundPassword;
	}
	
	public function getInboundService(){
		return $this->inboundService;
	}
	
	public function setInboundService($inboundService){
		$this->inboundService = $inboundService;
	}
	
	public function getOutboundHost(){
		return $this->outboundHost;
	}
	
	public function setOutboundHost($outboundHost){
		$this->outboundHost = $outboundHost;
	}
	
	public function getOutboundHostPort(){
		return $this->outboundHostPort;
	}
	
	public function setOutboundHostPort($outboundHostPort){
		$this->outboundHostPort = $outboundHostPort;
	}
	
	public function getOutboundSslMode(){
		return $this->outboundSslMode;
	}
	
	public function setOutboundSslMode($outboundSslMode){
		$this->outboundSslMode = $outboundSslMode;
	}
	
	public function getOutboundUser(){
		return $this->outboundUser;
	}
	
	public function setOutboundUser($outboundUser){
		$this->outboundUser = $outboundUser;
	}
	
	public function getOutboundPassword(){
		//return $this->decryptPassword($this->outboundPassword);
		return $this->outboundPassword;
	}
	
	public function setOutboundPassword($outboundPassword){
		//$this->outboundPassword = $this->encryptPassword($outboundPassword);
		$this->outboundPassword = $outboundPassword;
	}
	
	public function getOutboundService(){
		return $this->outboundService;
	}
	
	public function setOutboundService($outboundService){
		$this->outboundService = $outboundService;
	}

	/**
	 * @return array
	 */
	public function toJson() {
		$result = array(
			'accountId' => $this->getMailAccountId(),
			'name' => $this->getMailAccountName(),
			'emailAddress' => $this->getEmail(),
			'imapHost' => $this->getInboundHost(),
			'imapPort' => $this->getInboundHostPort(),
			'imapUser' => $this->getInboundUser(),
			'imapSslMode' => $this->getInboundSslMode(),
		);

		if (!is_null($this->getOutboundHost())) {
			$result['smtpHost'] = $this->getOutboundHost();
			$result['smtpPort'] = $this->getOutboundHostPort();
			$result['smtpUser'] = $this->getOutboundUser();
			$result['smtpSslMode'] = $this->getOutboundSslMode();
		}

		return $result;
	}

	/**
	 * private functions
	 */
	private function fromRow($row){
		$this->ocUserId = $row['ocuserid'];
		$this->mailAccountId = $row['mailaccountid'];
		$this->mailAccountName = $row['mailaccountname'];
		$this->email = $row['email'];
		$this->inboundHost = $row['inboundhost'];
		$this->inboundHostPort = $row['inboundhostport'];
		$this->inboundSslMode = $row['inboundsslmode'];
		$this->inboundUser = $row['inbounduser'];
		$this->inboundPassword = $row['inboundpassword'];
		$this->inboundService = $row['inboundservice'];
		$this->outboundHost = $row['outboundhost'];
		$this->outboundHostPort = $row['outboundhostport'];
		$this->outboundSslMode = $row['outboundsslmode'];
		$this->outboundUser = $row['outbounduser'];
		$this->outboundPassword = $row['outboundpassword'];
		$this->outboundService = $row['outboundservice'];
	}
}
