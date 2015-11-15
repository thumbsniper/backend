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

namespace ThumbSniper\account;

require_once('vendor/autoload.php');

use ThumbSniper\common\Settings;
use ThumbSniper\common\Logger;
use ThumbSniper\common\Helpers;
use MongoDB;
use MongoTimestamp;
use MongoRegex;
use MongoCollection;
use MongoCursor;
use Exception;



class AccountModel
{
    /** @var Logger */
    private $logger;

    /** @var MongoDB */
    protected $mongoDB;



    public function __construct(MongoDB $mongoDB, Logger $logger)
    {
        $this->mongoDB = $mongoDB;
        $this->logger = $logger;
    }



    private static function load($data)
    {
        $account = new Account();

        if (!is_array($data)) {
            return false;
        }

        $account->setId(isset($data[Settings::getMongoKeyAccountAttrId()]) ? $data[Settings::getMongoKeyAccountAttrId()] : null);
        $account->setFirstName(isset($data[Settings::getMongoKeyAccountAttrFirstName()]) ? $data[Settings::getMongoKeyAccountAttrFirstName()] : null);
        $account->setLastName(isset($data[Settings::getMongoKeyAccountAttrLastName()]) ? $data[Settings::getMongoKeyAccountAttrLastName()] : null);
        $account->setEmail(isset($data[Settings::getMongoKeyAccountAttrEmail()]) ? $data[Settings::getMongoKeyAccountAttrEmail()] : null);
        $account->setEmailVerified(isset($data[Settings::getMongoKeyAccountAttrEmailVerified()]) ? $data[Settings::getMongoKeyAccountAttrEmailVerified()] : null);

        $account->setActive(isset($data[Settings::getMongoKeyAccountAttrActive()]) ? $data[Settings::getMongoKeyAccountAttrActive()] : null);

        if(Settings::getFrontendAdminEmail() && $account->getEmail() == Settings::getFrontendAdminEmail()) {
            $account->setAdmin(true);
        }

        $account->setApiKey(isset($data[Settings::getMongoKeyAccountAttrApiKey()]) ? $data[Settings::getMongoKeyAccountAttrApiKey()] : null);
        $account->setApiKeyType(isset($data[Settings::getMongoKeyAccountAttrApiKeyType()]) ? $data[Settings::getMongoKeyAccountAttrApiKeyType()] : null);
        $account->setApiKeyActive(isset($data[Settings::getMongoKeyAccountAttrApiKeyActive()]) ? $data[Settings::getMongoKeyAccountAttrApiKeyActive()] : null);

        $account->setDomainVerificationKey(isset($data[Settings::getMongoKeyAccountAttrDomainVerificationKey()]) ? $data[Settings::getMongoKeyAccountAttrDomainVerificationKey()] : null);
        $account->setWhitelistActive(isset($data[Settings::getMongoKeyAccountAttrWhitelistActive()]) ? $data[Settings::getMongoKeyAccountAttrWhitelistActive()] : null);

        $tsAdded = null;
        if(isset($data[Settings::getMongoKeyAccountAttrTsAdded()]))
        {
            if($data[Settings::getMongoKeyAccountAttrTsAdded()] instanceof MongoTimestamp) {
                /** @var MongoTimestamp $mongoTs */
                $mongoTs = $data[Settings::getMongoKeyAccountAttrTsAdded()];
                $tsAdded = $mongoTs->sec;
            }
        }
        $account->setTsAdded($tsAdded);

        $apiKeyTsAdded = null;
        if(isset($data[Settings::getMongoKeyAccountAttrApiKeyTsAdded()]))
        {
            if($data[Settings::getMongoKeyAccountAttrApiKeyTsAdded()] instanceof MongoTimestamp) {
                /** @var MongoTimestamp $mongoTs */
                $mongoTs = $data[Settings::getMongoKeyAccountAttrApiKeyTsAdded()];
                $apiKeyTsAdded = $mongoTs->sec;
            }
        }
        $account->setApiKeyTsAdded($apiKeyTsAdded);

        $apiKeyTsExpire = null;
        if(isset($data[Settings::getMongoKeyAccountAttrApiKeyTsExpiry()]))
        {
            if($data[Settings::getMongoKeyAccountAttrApiKeyTsExpiry()] instanceof MongoTimestamp) {
                /** @var MongoTimestamp $mongoTs */
                $mongoTs = $data[Settings::getMongoKeyAccountAttrApiKeyTsExpiry()];
                $apiKeyTsExpire = $mongoTs->sec;
            }
        }
        $account->setApiKeyTsExpire($apiKeyTsExpire);

        $account->setMaxDailyRequests(Settings::getAccountMaxDailyRequests($account->getApiKeyType()));


        return $account;
    }



    public function getById($id)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $account = NULL;

        try {
            $accountCollection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionAccounts());
            $accountData = $accountCollection->findOne(array('_id' => $id));

            if(is_array($accountData)) {
                $account = AccountModel::load($accountData);
                $account->setRequestStats($this->getRequestStatsToday($account->getId()));

                $this->logger->log(__METHOD__, "found account " . $account->getId() . " in MongoDB", LOG_DEBUG);
            }
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception: " . $e->getMessage(), LOG_ERR);
            die();
        }

        return $account;
    }


    public function getAllAccounts()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $accounts = array();

        try {
            $accountCollection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionAccounts());

            $accountFields = array(
                Settings::getMongoKeyAccountAttrId() => true
            );

            $accountsData = $accountCollection->find(array(), $accountFields);

            foreach($accountsData as $accountData)
            {
                $a = $this->getById($accountData[Settings::getMongoKeyAccountAttrId()]);

                if($a instanceof Account) {
                    $accounts[] = $a;
                }
            }
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception: " . $e->getMessage(), LOG_ERR);
            die();
        }

        return $accounts;
    }


    public function getNumAccounts($where = NULL)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $numAccounts = NULL;

        try {
            $accountCollection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionAccounts());

            if($where)
            {
                $accountsQuery = array(
                    '$or' => array(
                        array(
                            Settings::getMongoKeyAccountAttrFirstName() => new MongoRegex("/" . $where . "/i")
                        ),
                        array(
                            Settings::getMongoKeyAccountAttrLastName() => new MongoRegex("/" . $where . "/i")
                        )
                    )
                );
            }else {
                $accountsQuery = array();
            }

            $numAccounts = $accountCollection->count($accountsQuery);
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception: " . $e->getMessage(), LOG_DEBUG);
            die();
        }

        return $numAccounts;
    }



    public function getAccounts($orderby = '_id', $orderDirection = 'asc', $limit = PHP_INT_MAX, $offset = 0, $where = NULL)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $accounts = array();

        try {
            $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionAccounts());

            $query = array(
                '$query' => array()
            );

            if ($orderDirection == "asc") {
                $query['$orderby'] = array(
                    $orderby => 1
                );
            } else {
                $query['$orderby'] = array(
                    $orderby => -1
                );
            }

            if ($where) {
                $query['$query']['$or'] = array(
                    array(
                        Settings::getMongoKeyAccountAttrId() => array(
                            '$regex' => $where,
                            '$options' => 'i'
                        )
                    ),
                    array(
                        Settings::getMongoKeyAccountAttrFirstName() => array(
                            '$regex' => $where,
                            '$options' => 'i'
                        )
                    ),
                    array(
                        Settings::getMongoKeyAccountAttrLastName() => array(
                            '$regex' => $where,
                            '$options' => 'i'
                        )
                    ),
                    array(
                        Settings::getMongoKeyAccountAttrEmail() => array(
                            '$regex' => $where,
                            '$options' => 'i'
                        )
                    )
                );
            }

            $fields = array(
                Settings::getMongoKeyAccountAttrId() => true
            );

            //$this->logger->log(__METHOD__, "HIER: " . print_r($query, true), LOG_DEBUG);

            /** @var MongoCursor $referrerCursor */
            $cursor = $collection->find($query, $fields);
            $cursor->skip($offset);
            $cursor->limit($limit);

            foreach ($cursor as $doc) {
                $a = $this->getById($doc[Settings::getMongoKeyAccountAttrId()]);
                //$this->logger->log(__METHOD__, "HIER: " . print_r($r, true), LOG_DEBUG);

                if ($a instanceof Account) {
                    $accounts[] = $a;
                }
            }
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception while searching for accounts: " . $e->getMessage(), LOG_ERR);
        }

        return $accounts;
    }



    private function getRequestStatsToday($accountId)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $numRequests = 0;
        $date = date("Y-m-d", time());

        try {
            $collection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionAccounts());

            $query = array(
                Settings::getMongoKeyAccountAttrId() => $accountId,
                Settings::getMongoKeyAccountAttrNumRequestsDaily() => array(
                    '$exists' => true
                )
            );

            $fields = array(
                Settings::getMongoKeyAccountAttrId() => false,
                Settings::getMongoKeyAccountAttrNumRequestsDaily() => true
            );

            $doc = $collection->findOne($query, $fields);

            //$this->logger->log(__METHOD__, "HIERTODAY " . $accountId . ": " . print_r($doc, true), LOG_ERR);

            if (isset($doc[Settings::getMongoKeyAccountAttrNumRequestsDaily()][$date])) {
                $numRequests = $doc[Settings::getMongoKeyAccountAttrNumRequestsDaily()][$date];
            }

        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while getting current account daily request stats for " . $accountId . ": " . $e->getMessage(), LOG_ERR);
        }

        return $numRequests;
    }



    public function getNumRequests($accountId, $days)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $stats = array();

        $now = time();

        try {
            $collection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionAccounts());

            $query = array(
                Settings::getMongoKeyAccountAttrId() => $accountId,
                Settings::getMongoKeyAccountAttrNumRequestsDaily() => array(
                    '$exists' => true
                )
            );

            $fields = array(
                Settings::getMongoKeyAccountAttrId() => false,
                Settings::getMongoKeyAccountAttrNumRequestsDaily() => true
            );

            $doc = $collection->findOne($query, $fields);

            //$this->logger->log(__METHOD__, "HIER " . $accountId . ": " . print_r($doc, true), LOG_ERR);

            for($i = 0; $i < $days; $i++) {
                $day = date("Y-m-d", $now - (86400 * $i));
                //$this->logger->log(__METHOD__, "check day: " . $day);
                if (isset($doc[Settings::getMongoKeyAccountAttrNumRequestsDaily()][$day])) {
                    $stats[$day] = $doc[Settings::getMongoKeyAccountAttrNumRequestsDaily()][$day];
                }else {
                    $stats[$day] = 0;
                }
            }

        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while getting account daily request stats for " . $accountId . ": " . $e->getMessage(), LOG_ERR);
        }

        return $stats;
    }


    public function isEmailExists($email)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

	    try {
		    $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionAccounts());

		    $query = array(
			    Settings::getMongoKeyAccountAttrEmail() => $email
		    );

		    $numEmails = $collection->count($query);
	    } catch (\Exception $e) {
		    $this->logger->log(__METHOD__, "exception: " . $e->getMessage() . " (Code: " . $e->getCode() . ")", LOG_ERR);
		    //TODO: don't die, but do something useful
		    die();
	    }

	    return $numEmails > 0;
    }


	public function isUserNameExists($userName)
	{
		$this->logger->log(__METHOD__, NULL, LOG_DEBUG);

		try {
			$collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionAccounts());

			$query = array(
				Settings::getMongoKeyAccountAttrId() => $userName
			);

			$numUserNames = $collection->count($query);
		} catch (\Exception $e) {
			$this->logger->log(__METHOD__, "exception: " . $e->getMessage() . " (Code: " . $e->getCode() . ")", LOG_ERR);
			//TODO: don't die, but do something useful
			die();
		}

		return $numUserNames > 0;
	}



    private function isAccountToken($oauthId, $provider)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        try {
            $stmt = $this->db->prepare("SELECT COUNT(oauthId) AS tokencount FROM accounts_auth
				WHERE oauthId = ? AND provider = ?");

            if ($stmt->execute(array($oauthId, $provider))) {
                if ($c = $stmt->fetch()) {
                    return $c['tokencount'] > 0;
                }
            }
        } catch (\PDOException $e) {
            $this->logger->log(__METHOD__, "exception: " . $e->getMessage(), LOG_ERR);
            die();
        }

        //TODO: might be wrong?
        return false;
    }


    private function isApiKey($accountId)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        try {
            $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionAccounts());

            $query = array(
                Settings::getMongoKeyAccountAttrId() => $accountId,
                Settings::getMongoKeyAccountAttrApiKey() => array(
                    '$exists' => true
                )
            );

            $fields = array(
                Settings::getMongoKeyAccountAttrId() => true
            );

            $accountData = $collection->findOne($query, $fields);

            //$this->logger->log(__METHOD__, "HIER: " . print_r($accountData, true), LOG_DEBUG);

            $numApiKeys = sizeof($accountData);
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception: " . $e->getMessage() . " (Code: " . $e->getCode() . ")", LOG_ERR);
            die();
        }

        return $numApiKeys > 0;
    }


    public function saveAccount(Account $account)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $accountId = null;

        try {
            $accountCollection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionAccounts());

            $query = array(
                Settings::getMongoKeyAccountAttrId() => $account->getId()
            );

            $data = array(
                Settings::getMongoKeyAccountAttrFirstName() => $account->getFirstName(),
                Settings::getMongoKeyAccountAttrLastName() => $account->getLastName(),
                Settings::getMongoKeyAccountAttrEmail() => $account->getEmail(),
                Settings::getMongoKeyAccountAttrDomainVerificationKey() => Helpers::genRandomString(32),
                Settings::getMongoKeyAccountAttrTsAdded() => new MongoTimestamp()
            );

            $update = array(
                '$setOnInsert' => $data
            );

            $options = array(
                'upsert' => true
            );

            $result = $accountCollection->update($query, $update, $options);

            if (is_array($result)) {
                if ($result['n'] == true) {
                    $this->logger->log(__METHOD__, "new account created: " . $account->getId(), LOG_ERR);
                } elseif ($result['updatedExisting']) {
                    $this->logger->log(__METHOD__, "updated account " . $account->getId() . " instead of creating a new one. Works fine. :-)", LOG_ERR);
                }

                $accountId = $account->getId();
            }
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception while creating account " . $account->getId() . ": " . $e->getMessage() . "(Code: " . $e->getCode() . ")", LOG_ERR);
        }

        return $accountId;
    }





    public function createApiKey($accountId, $type = NULL)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

//        if ($this->isApiKey($accountId)) {
//            return false;
//        }

        if ($type == NULL) {
            $type = Settings::getApiKeyDefaultType();
        }

        $private = Helpers::genRandomString(32);
        $public = md5($private);

        try {
            $accountsCollection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionAccounts());

            $accountData = array(
                Settings::getMongoKeyAccountAttrApiKey() => $public,
                Settings::getMongoKeyAccountAttrApiKeyType() => $type,
                Settings::getMongoKeyAccountAttrApiKeyTsExpiry() => new MongoTimestamp(time() + Settings::getApiKeyExpire()),
                Settings::getMongoKeyAccountAttrApiKeyTsAdded() => new MongoTimestamp(),
                Settings::getMongoKeyAccountAttrApiKeyActive() => true
            );

            $accountQuery = array(
                Settings::getMongoKeyAccountAttrId() => $accountId
            );

            $accountUpdate = array(
                '$set' => $accountData
            );

            $accountsCollection->update($accountQuery, $accountUpdate);
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception: " . $e->getMessage() . " (Code: " . $e->getCode() . ")", LOG_ERR);
            die();
        }

        return $private;
    }



    // enable / disable whitelist for an account
    public function updateWhitelistUsage($accountId, $active)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        if (!$this->isApiKey($accountId)) {
            return false;
        }

        try {
            $accountsCollection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionAccounts());

            $accountData = array(
                Settings::getMongoKeyAccountAttrWhitelistActive() => $active
            );

            $accountQuery = array(
                Settings::getMongoKeyAccountAttrId() => $accountId
            );

            $accountUpdate = array(
                '$set' => $accountData
            );

            $accountsCollection->update($accountQuery, $accountUpdate);
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception: " . $e->getMessage() . " (Code: " . $e->getCode() . ")", LOG_ERR);
            die();
        }

        return true;
    }


    public function getByApiKey($private)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $account = NULL;
        $public = md5($private);

        try {
            $accountCollection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionAccounts());

            $accountQuery = array(
                Settings::getMongoKeyAccountAttrApiKey() => $public
            );

            $accountFields = array(
                Settings::getMongoKeyAccountAttrId() => true
            );

            $accountData = $accountCollection->findOne($accountQuery, $accountFields);

            if($accountData)
            {
                $account = $this->getById($accountData[Settings::getMongoKeyAccountAttrId()]);
            }
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception: " . $e->getMessage(), LOG_ERR);
            die();
        }

        return $account;
    }


    /**
     * @param int $accountId
     * @return boolean
     */
    public function incrementDailyRequests($accountId)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $now = time();
        $today = date("Y-m-d", $now);

        try {
            $accountCollection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionAccounts());

            $dailyRequestsQuery = array(
                Settings::getMongoKeyAccountAttrId() => $accountId
            );

            $dailyRequestsUpdate = array(
                '$inc' => array(
                    Settings::getMongoKeyAccountAttrNumRequestsDaily() . '.' . $today => 1
                ),
            );

            if($accountCollection->update($dailyRequestsQuery, $dailyRequestsUpdate)) {
                $this->logger->log(__METHOD__, "incremented account daily request stats for " . $accountId, LOG_INFO);
                return true;
            }
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception while incrementing account daily request stats for " . $accountId . ": " . $e->getMessage(), LOG_ERR);
        }

        return false;
    }


    public function addToActiveAccountsDailyStats($accountId)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        //Statt eine weitere Liste zu führen, müsste man das auch über Queries lösen können, oder?

        $now = time();
        $today = date("Y-m-d", $now);

        try {
            $statsCollection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionStatistics());

            $statsQuery = array(
                '_id' => 'accounts'
            );

            $statsUpdate = array(
                '$addToSet' => array(
                    'active.' . $today => $accountId
                )
            );

            $statsOptions = array(
                'upsert' => true
            );

            if($statsCollection->update($statsQuery, $statsUpdate, $statsOptions)) {
                $this->logger->log(__METHOD__, "added account " . $accountId . " to daily active stats", LOG_DEBUG);
            }

        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception while adding account " . $accountId . " to daily active stats: " . $e->getMessage(), LOG_ERR);
        }
    }
}
