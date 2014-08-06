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


use Test\AppFramework\Db\MapperTestUtility;

class MailAccountMapperTest extends MapperTestUtility {

	/**
	 * @var MailAccountMapper
	 */
	private $mapper;

	/**
	 * @var MailAccount
	 */
	private $mailAccount;
	
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

	/**
	 * Initialize Mapper
	 */
	public function setup(){
		parent::setUp();
		$this->mapper = new MailAccountMapper($this->db);
		
		$this->mailAccount = new MailAccount($this->row);
	}
	
	public function testTableName(){
		$this->assertSame('*PREFIX*mail_accounts', $this->mapper->getTableName());
	}
	
	public function testFind(){
		$userId = $this->mailAccount->getOcUserId();
		$mailAccountId = $this->mailAccount->getMailAccountId();
		
		$sql = 'SELECT * FROM `' . $this->mapper->getTableName() . '` WHERE ocuserid = ? and mailaccountid = ?';
		
		$this->setMapperResult($sql, array($userId, $mailAccountId), array($this->row));
		
		$result = $this->mapper->find($userId, $mailAccountId);
		$this->assertEquals($this->mailAccount, $result);
	}

}
