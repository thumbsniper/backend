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

namespace ThumbSniper\common;


class Logger
{
    private $id;
    private $forceDebug;
    private $messages;


    function __construct($forceDebug = false)
    {
        $this->id = Helpers::genRandomString(12);

        $this->forceDebug = $forceDebug;

        if ($forceDebug) {
            $this->log(__METHOD__, "forced debug logging");
        }

        $this->messages = array();
    }


    function __destruct()
    {
    }


    public function logEcho($method, $msg, $severity = NULL)
    {
        $this->log($method, $msg, $severity);
        return $msg;
    }

    public function log($method, $msg, $severity = NULL)
    {
        /*
        LOG_EMERG	system is unusable
        LOG_ALERT	action must be taken immediately
        LOG_CRIT	critical conditions
        LOG_ERR		error conditions
        LOG_WARNING	warning conditions
        LOG_NOTICE	normal, but significant, condition
        LOG_INFO	informational message
        LOG_DEBUG	debug-level message
        */

        if ($this->forceDebug) {
            //FIXME: disabled logToArray: PHP Fatal error:  Allowed memory size of 134217728 bytes exhausted (tried to allocate 80 bytes)
            //$this->logToArray($method, $msg, $severity);
        }

        if ($this->forceDebug == false && $severity != NULL && $severity > Settings::getLogSeverity()) {
            return true;
        }

        if (Settings::getLogToApache()) {
            $this->logToErrorLog($method, $msg);
        }

        if (Settings::getLogToFile()) {
            $this->logToFile($method, $msg);
        }

        //FIXME: return correctly
        return true;
    }


    private function logToArray($method, $msg, $severity)
    {
        $log = array();

        $log['method'] = $method;
        $log['msg'] = $msg;
        $log['severity'] = $severity;

        $this->messages[] = $log;
    }


    private function logToErrorLog($method, $msg)
    {
        return error_log($this->id . " - " . $method . ": " . $msg, 0);
    }


    private function logToFile($method, $msg)
    {
        $now = microtime();
        list($ms, $timestamp) = explode(" ", $now);
        $ms = substr($ms, 1, 5);

        //FIXME: re-implement logging for different classes
//        if (constant('thumbsniper\\common\\Config::LOG_' . $src[0])) {
            if ($msg != NULL) {
                return error_log(date("Y-m-d H:i:s", $timestamp) . $ms . " - ID" . $this->id . " - " . $method . " - " . $msg . "\r\n", 3, Settings::getLogFile());
            } else {
                return error_log(date("Y-m-d H:i:s", $timestamp) . $ms . " - ID" . $this->id . " - " . $method . "\r\n", 3, Settings::getLogFile());
            }

  //      } else {
  //          return error_log("log state unknown - " . date("Y-m-d H:i:s") . " - ID" . $this->id . " - " . $method . " - " . $msg . "\r\n", 3, "/tmp/apiV2.log");
  //      }
    }


    public function publishMessagesAsHeaders()
    {
        $counter = 1;

        foreach ($this->messages as $message) {
            $output = $message['method'];

            if ($message['msg'] != NULL) {
                $output .= ": " . rtrim($message['msg']);
            }

            /*
            if($message['severity'] != NULL)
            {
                $output.= ": " . $message['severity'];
            }
            */

            header("X-ThumbSniper-" . str_pad($counter, strlen(sizeof($this->messages)), "0", STR_PAD_LEFT) . ": " . $output);
            $counter++;
        }
    }

    /**
     * @param boolean $forceDebug
     */
    public function setForceDebug($forceDebug)
    {
        $this->forceDebug = $forceDebug;
    }
}
