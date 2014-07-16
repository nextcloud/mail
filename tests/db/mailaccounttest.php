<?php
/**
* ownCloud - Mail
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
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/

namespace OCA\Mail\Db;


class TestMailAccount extends \PHPUnit_Framework_TestCase {

	private $row = array(
		'ocuserid' => 1,
		'mailaccountid' => 1,
		'mailaccountname' => 'Account 1',
		'email' => 'octest@octest.org',
		'inboundhost' => 'octest.org',
		'inboundhostport' => '993',
		'inboundsslmode' => '',
		'inbounduser' => 'test',
		'inboundpassword' => 'test',
		'inboundservice' => '',
		'outboundhost' => 'octest.org',
		'outboundhostport' => '143',
		'outboundsslmode' => '',
		'outbounduser' => 'test',
		'outboundpassword' => 'test',
		'outboundservice' => ''
	);
	
	private $setVars = array(
		'ocuserid' => 1,
		'mailaccountid' => 1,
		'mailaccountname' => 'Account 2',
		'email' => 'octest@octest.org',
		'inboundhost' => 'octest.org',
		'inboundhostport' => '993',
		'inboundsslmode' => '',
		'inbounduser' => 'test',
		'inboundpassword' => 'test',
		'inboundservice' => '',
		'outboundhost' => 'octest.org',
		'outboundhostport' => '143',
		'outboundsslmode' => '',
		'outbounduser' => 'test',
		'outboundpassword' => 'test',
		'outboundservice' => ''
	);

	/**
	 * @var \OCA\Mail\Db\MailAccount
	 */
	private $mailAccount;
	
	/**
	 * Initialize MailAccount Object
	 */
	protected function setup(){
		$this->mailAccount = new MailAccount($this->row);
	}
	
	/**
	 * Test if all Getter Methods of MailAccount returning the correct values
	 */
    public function testGetOcUserId(){
		$expectedOcUserId = $this->row['ocuserid'];
		$ocUserId = $this->mailAccount->getOcUserId();
		
		$this->assertSame($expectedOcUserId, $ocUserId);
    }
	
	public function testGetMailAccountId(){
		$expectedMailAccountId = $this->row['mailaccountid'];
		$mailAccountId = $this->mailAccount->getMailAccountId();
		
		$this->assertSame($expectedMailAccountId, $mailAccountId);
	}
	
	public function testGetMailAccountName(){
		$expectedMailAccountName = $this->row['mailaccountname'];
		$mailAccountName = $this->mailAccount->getMailAccountName();
		
		$this->assertSame($expectedMailAccountName, $mailAccountName);
	}
	
	public function testGetEmail(){
		$expectedEmail = $this->row['email'];
		$email = $this->mailAccount->getEmail();
		
		$this->assertSame($expectedEmail, $email);
	}
	
	public function testGetInboundHost(){
		$expectedInboundHost = $this->row['inboundhost'];
		$inboundHost = $this->mailAccount->getInboundHost();
		
		$this->assertSame($expectedInboundHost, $inboundHost);
	}
	
	public function testGetInboundHostPort(){
		$expectedInboundHostPort = $this->row['inboundhostport'];
		$inboundHostPort = $this->mailAccount->getInboundHostPort();
		
		$this->assertSame($expectedInboundHostPort, $inboundHostPort);
	}
	
	public function testGetInboundSslMode(){
		$expectedInboundSslMode = $this->row['inboundsslmode'];
		$inboundSslMode = $this->mailAccount->getInboundSslMode();
		
		$this->assertSame($expectedInboundSslMode, $inboundSslMode);
	}
	
	public function testGetInboundUser(){
		$expectedInboundUser = $this->row['inbounduser'];
		$inboundUser = $this->mailAccount->getInboundUser();
		
		$this->assertSame($expectedInboundUser, $inboundUser);
	}
	
	public function testGetInboundPassword(){
		$expectedInboundPassword = $this->row['inboundpassword'];
		$inboundPassword = $this->mailAccount->getInboundPassword();
		
		$this->assertSame($expectedInboundPassword, $inboundPassword);
	}
	
	public function testGetInboundService(){
		$expectedInboundService = $this->row['inboundservice'];
		$inboundService = $this->mailAccount->getInboundService();
		
		$this->assertSame($expectedInboundService, $inboundService);
	}
	
	public function testGetOutboundHost(){
		$expectedOutboundHost = $this->row['outboundhost'];
		$outboundHost = $this->mailAccount->getOutboundHost();
		
		$this->assertSame($expectedOutboundHost, $outboundHost);
	}
	
	public function testGetOutboundHostPort(){
		$expectedOutboundHostPort = $this->row['outboundhostport'];
		$outboundHostPort = $this->mailAccount->getOutboundHostPort();
		
		$this->assertSame($expectedOutboundHostPort, $outboundHostPort);
	}
	
	public function testGetOutboundSslMode(){
		$expectedOutboundSslMode = $this->row['outboundsslmode'];
		$outboundSslMode = $this->mailAccount->getOutboundSslMode();
		
		$this->assertSame($expectedOutboundSslMode, $outboundSslMode);
	}
	
	public function testGetOutboundUser(){
		$expectedOutboundUser = $this->row['outbounduser'];
		$outboundUser = $this->mailAccount->getOutboundUser();
		
		$this->assertSame($expectedOutboundUser, $outboundUser);
	}
	
	public function testGetOutboundPassword(){
		$expectedOutboundPassword = $this->row['outboundpassword'];
		$outboundPassword = $this->mailAccount->getOutboundPassword();
		
		$this->assertSame($expectedOutboundPassword, $outboundPassword);
	}
	
	public function testGetOutboundService(){
		$expectedOutboundService = $this->row['outboundservice'];
		$outboundService = $this->mailAccount->getOutboundService();
		
		$this->assertSame($expectedOutboundService, $outboundService);
	}
	
	/**
	 * Test all Setter Methods of MailAccount
	 */
	public function testSetOcUserId(){
		$expectedOcUserId = $this->setVars['ocuserid'];
		$this->mailAccount->setOcUserId($expectedOcUserId);
		$ocUserId = $this->mailAccount->getOcUserId();
		
		$this->assertSame($expectedOcUserId, $ocUserId);
	}
	
	public function testSetMailAccountId(){
		$expectedMailAccountId = $this->setVars['mailaccountid'];
		$this->mailAccount->setMailAccountId($expectedMailAccountId);
		$mailAccountId = $this->mailAccount->getMailAccountId();
		
		$this->assertSame($expectedMailAccountId, $mailAccountId);
	}
	
	public function testSetMailAccountName(){
		$expectedMailAccountName = $this->setVars['mailaccountname'];
		$this->mailAccount->setMailAccountName($expectedMailAccountName);
		$mailAccountName = $this->mailAccount->getMailAccountName();
		
		$this->assertSame($expectedMailAccountName, $mailAccountName);
	}
	
	public function testSetEmail(){
		$expectedEmail = $this->setVars['email'];
		$this->mailAccount->setEmail($expectedEmail);
		$email = $this->mailAccount->getEmail();
		
		$this->assertSame($expectedEmail, $email);
	}
	
	public function testSetInboundHost(){
		$expectedInboundHost = $this->setVars['inboundhost'];
		$this->mailAccount->setInboundHost($expectedInboundHost);
		$inboundHost = $this->mailAccount->getInboundHost();
		
		$this->assertSame($expectedInboundHost, $inboundHost);
	}
	
	public function testSetInboundHostPort(){
		$expectedInboundHostPort = $this->setVars['inboundhostport'];
		$this->mailAccount->setInboundHost($expectedInboundHostPort);
		$inboundHostPort = $this->mailAccount->getInboundHostPort();
		
		$this->assertSame($expectedInboundHostPort, $inboundHostPort);
	}
	
	public function testSetInboundSslMode(){
		$expectedInboundSslMode = $this->setVars['inboundsslmode'];
		$this->mailAccount->setInboundSslMode($expectedInboundSslMode);
		$inboundSslMode = $this->mailAccount->getInboundSslMode();
		
		$this->assertSame($expectedInboundSslMode, $inboundSslMode);
	}
	
	public function testSetInboundUser(){
		$expectedInboundUser = $this->setVars['inbounduser'];
		$this->mailAccount->setInboundUser($expectedInboundUser);
		$inboundUser = $this->mailAccount->getInboundUser();
		
		$this->assertSame($expectedInboundUser, $inboundUser);
	}
	
	public function testSetInboundPassword(){
		$expectedInboundPassword = $this->setVars['inboundpassword'];
		$this->mailAccount->setInboundPassword($expectedInboundPassword);
		$inboundPassword = $this->mailAccount->getInboundPassword();
		
		$this->assertSame($expectedInboundPassword, $inboundPassword);
	}
	
	public function testSetInboundService(){
		$expectedInboundService = $this->setVars['inboundservice'];
		$this->mailAccount->setInboundService($expectedInboundService);
		$inboundService = $this->mailAccount->getInboundService();
		
		$this->assertSame($expectedInboundService, $inboundService);
	}
	
	//outboundOutboundoutbound
	public function testSetOutboundHost(){
		$expectedOutboundHost = $this->setVars['outboundhost'];
		$this->mailAccount->setOutboundHost($expectedOutboundHost);
		$outboundHost = $this->mailAccount->getOutboundHost();
		
		$this->assertSame($expectedOutboundHost, $outboundHost);
	}
	
	public function testSetOutboundHostPort(){
		$expectedOutboundHostPort = $this->setVars['outboundhostport'];
		$this->mailAccount->setOutboundHost($expectedOutboundHostPort);
		$outboundHostPort = $this->mailAccount->getOutboundHostPort();
		
		$this->assertSame($expectedOutboundHostPort, $outboundHostPort);
	}
	
	public function testSetOutboundSslMode(){
		$expectedOutboundSslMode = $this->setVars['outboundsslmode'];
		$this->mailAccount->setOutboundSslMode($expectedOutboundSslMode);
		$outboundSslMode = $this->mailAccount->getOutboundSslMode();
		
		$this->assertSame($expectedOutboundSslMode, $outboundSslMode);
	}
	
	public function testSetOutboundUser(){
		$expectedOutboundUser = $this->setVars['outbounduser'];
		$this->mailAccount->setOutboundUser($expectedOutboundUser);
		$outboundUser = $this->mailAccount->getOutboundUser();
		
		$this->assertSame($expectedOutboundUser, $outboundUser);
	}
	
	public function testSetOutboundPassword(){
		$expectedOutboundPassword = $this->setVars['outboundpassword'];
		$this->mailAccount->setOutboundPassword($expectedOutboundPassword);
		$outboundPassword = $this->mailAccount->getOutboundPassword();
		
		$this->assertSame($expectedOutboundPassword, $outboundPassword);
	}
	
	public function testSetOutboundService(){
		$expectedOutboundService = $this->setVars['outboundservice'];
		$this->mailAccount->setOutboundService($expectedOutboundService);
		$outboundService = $this->mailAccount->getOutboundService();
		
		$this->assertSame($expectedOutboundService, $outboundService);
	}
	
}
?>
