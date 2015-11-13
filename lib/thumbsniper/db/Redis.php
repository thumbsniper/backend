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

namespace ThumbSniper\db;

require_once('vendor/autoload.php');



use ThumbSniper\common\Logger;
use Predis;



class Redis
{
    private $logger;

    private $scheme;
    private $host;
    private $port;
    private $db;

    /** @var  Predis\Client */
    private $client;


    function __construct(Logger $logger, $scheme, $host, $port, $db)
    {
        $this->logger = $logger;
        $this->scheme = $scheme;
        $this->host = $host;
        $this->port = $port;
        $this->db = $db;

        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);
    }


    function __destruct()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $this->close();
    }


    public function getConnection()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        if ($this->client == NULL) {
            try {
                $client = new Predis\Client(array(
                    'scheme' => $this->scheme,
                    'host' => $this->host,
                    'port' => $this->port,
                    'database' => $this->db,
                    'read_write_timeout' => 0
                ));

                // explicitly call Predis\Client::connect()
                $client->connect();

                // assign (hopefully) working connection to class var
                $this->client = $client;

                $this->logger->log(__METHOD__, "created new Redis connection", LOG_DEBUG);
            } catch (\Exception $e) {
                $this->logger->log(__METHOD__, "Error while connecting to Redis: " . $e->getMessage(), LOG_ERR);
                echo $e->getMessage();
            }
        }

        return $this->client;
    }


    public function close()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        if($this->client) {
            $this->client->quit();
        }
        $this->client = NULL;
    }
}
