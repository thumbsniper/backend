<?php

/*
 * Copyright (C) 2015  Thomas Schulte <thomas@cupracer.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace ThumbSniper\api;

require_once('vendor/autoload.php');



use ThumbSniper\common\Logger;
use ThumbSniper\common\Settings;

class ApiMail
{
    private $forceDebug;

    /** @var Logger */
    protected $logger;



    function __construct($forceDebug)
    {
        $this->forceDebug = $forceDebug;
        $this->forceDebug = true;

        $this->logger = new Logger($this->forceDebug);
    }


    function sendMail($rcpt, $subject, $msg)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        if(!Settings::getMailSubjectPrefix())
        {
            $subject = "[" . Settings::getMailSubjectPrefix() . "] " . $subject;
        }

        $crlf = "\n";
        $html = "<div>" . $msg . "</div>";

        $headers = array(
            'From' => Settings::getMailFromName() . " <" . Settings::getMailFromAddress() . ">",
            'To' => $rcpt,
            'Subject' => $subject,
            'Date' => date('r')
        );

        $mime = new \Mail_mime(array('eol' => $crlf)); //based on pear doc
        $mime->setHTMLBody($html);

        $body = $mime->getMessageBody(); //based on pear doc above
        $headers = $mime->headers($headers);

        $smtp = \Mail::factory(
            "smtp", array(
                "host" => Settings::getMailSmtpHost(),
                "port" => Settings::getMailSmtpPort(),
                "auth" => true,
                "username" => Settings::getMailUser(),
                "password" => Settings::getMailPassword()
            )
        );

        $mail = $smtp->send($rcpt, $headers, $body);
    }
}
