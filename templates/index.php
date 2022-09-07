<?php
/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author colm <mail@colm.be>
 * @author Damien <dcosset@hotmail.fr>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Thomas Imbreckx <zinks@iozero.be>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
script('mail', 'mail');
?>

<input type="hidden" id="attachment-size-limit" value="<?php p($_['attachment-size-limit']); ?>">
<input type="hidden" id="config-installed-version" value="<?php p($_['app-version']); ?>">
<input type="hidden" id="external-avatars" value="<?php p($_['external-avatars']); ?>">
<input type="hidden" id="collect-data" value="<?php p($_['collect-data']); ?>">
<input type="hidden" id="start-mailbox-id" value="<?php p($_['start-mailbox-id']); ?>">
<input type="hidden" id="tag-classified-messages" value="<?php p($_['tag-classified-messages']); ?>">
