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
use MongoDB;
use MongoTimestamp;


class OauthModel
{
    /** @var MongoDB */
    protected $mongoDB;

    /** @var Logger */
    private $logger;



    public function __construct(MongoDB $mongoDB, Logger $logger)
    {
        $this->mongoDB = $mongoDB;
        $this->logger = $logger;
    }



    private static function load($data) {
        $oauth = new Oauth();

        if(!is_array($data))
        {
            return false;
        }

        $oauth->setId(isset($data[Settings::getMongoKeyOauthAttrId()]) ? $data[Settings::getMongoKeyOauthAttrId()] : null);
        $oauth->setProvider(isset($data[Settings::getMongoKeyOauthAttrProvider()]) ? $data[Settings::getMongoKeyOauthAttrProvider()] : null);
        $oauth->setAccessToken(isset($data[Settings::getMongoKeyOauthAttrAccessToken()]) ? $data[Settings::getMongoKeyOauthAttrAccessToken()] : null);
        $oauth->setAccessTokenSecret(isset($data[Settings::getMongoKeyOauthAttrAccessTokenSecret()]) ? $data[Settings::getMongoKeyOauthAttrAccessTokenSecret()] : null);
        $oauth->setAccessTokenType(isset($data[Settings::getMongoKeyOauthAttrAccessTokenType()]) ? $data[Settings::getMongoKeyOauthAttrAccessTokenType()] : null);
        $oauth->setAccessTokenExpiry(isset($data[Settings::getMongoKeyOauthAttrAccessTokenExpiry()]) ? $data[Settings::getMongoKeyOauthAttrAccessTokenExpiry()] : null);
        $oauth->setRefreshToken(isset($data[Settings::getMongoKeyOauthAttrRefreshToken()]) ? $data[Settings::getMongoKeyOauthAttrRefreshToken()] : null);
	    $oauth->setPassword(isset($data[Settings::getMongoKeyOauthAttrPassword()]) ? $data[Settings::getMongoKeyOauthAttrPassword()] : null);
        $oauth->setScreenName(isset($data[Settings::getMongoKeyOauthAttrScreenName()]) ? $data[Settings::getMongoKeyOauthAttrScreenName()] : null);
        $oauth->setTsLastUpdated(isset($data[Settings::getMongoKeyOauthAttrTsLastUpdated()]) ? $data[Settings::getMongoKeyOauthAttrTsLastUpdated()] : null);

        $tsAdded = null;
        if(isset($data[Settings::getMongoKeyOauthAttrTsAdded()]))
        {
            if($data[Settings::getMongoKeyOauthAttrTsAdded()] instanceof MongoTimestamp) {
                /** @var MongoTimestamp $mongoTs */
                $mongoTs = $data[Settings::getMongoKeyOauthAttrTsAdded()];
                $tsAdded = $mongoTs->sec;
            }
        }
        $oauth->setTsAdded($tsAdded);

        $tsLastUpdated = null;
        if(isset($data[Settings::getMongoKeyOauthAttrTsLastUpdated()]))
        {
            if($data[Settings::getMongoKeyOauthAttrTsLastUpdated()] instanceof MongoTimestamp) {
                /** @var MongoTimestamp $mongoTs */
                $mongoTs = $data[Settings::getMongoKeyOauthAttrTsLastUpdated()];
                $tsLastUpdated = $mongoTs->sec;
            }
        }
        $oauth->setTsLastUpdated($tsLastUpdated);


        return $oauth;
    }



    public function getById($id)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        if(!$id)
        {
            $this->logger->log(__METHOD__, "invalid id: " . $id, LOG_ERR);
            return false;
        }

        $oauth = NULL;

        try {
            $accountCollection = new \MongoCollection($this->mongoDB, "accounts");

            $query = array(
                'oauth.' . Settings::getMongoKeyOauthAttrId() => $id
            );

            $fields = array(
                Settings::getMongoKeyAccountAttrId() => true,
                'oauth.$' => true
            );

            $accountData = $accountCollection->findOne($query, $fields);

            if(is_array($accountData['oauth'])) {
                //print_r($accountData);
                $oauthData = $accountData['oauth'][0];
                $oauth = OauthModel::load($oauthData);

                if($oauth instanceof Oauth) {
                    $this->logger->log(__METHOD__, "found oauth " . $oauth->getId() . " in MongoDB", LOG_DEBUG);
                }
            }
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "died (" . $e->getMessage() . ")", LOG_ERR);
            die($e->getMessage());
        }

        return $oauth;
    }


    public function getAccountIdByOauthIdAndProvider($oauthId, $provider)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $id = NULL;

        try {
            $accountCollection = new \MongoCollection($this->mongoDB, "accounts");

            $accountQuery = array(
                'oauth.' . Settings::getMongoKeyOauthAttrId() => $oauthId,
                'oauth.' . Settings::getMongoKeyOauthAttrProvider() => $provider
            );

            $accountFields = array(
                Settings::getMongoKeyAccountAttrId() => true
            );

            $accountData = $accountCollection->findOne($accountQuery, $accountFields);

            if(is_array($accountData)) {
                $id = $accountData[Settings::getMongoKeyAccountAttrId()];
            }
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception while searching for oauth id " . $oauthId . " and provider " . $provider . ": " . $e->getMessage(), LOG_ERR);
        }

        return $id;
    }


    public function saveOauth($accountId, $oauthData)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $oauth = new Oauth();
        $oauth->setId($oauthData['id']);
        $oauth->setProvider($oauthData['provider']);

        switch($oauthData['provider'])
        {
	        case "local":
				//FIXME: encrypt password
		        $oauth->setPassword($oauthData['password']);

		        $this->saveOauthLocal($accountId, $oauth);
		        break;

            case "google":
	            $oauth->setAccessToken($oauthData['accessToken']);
                $oauth->setAccessTokenType($oauthData['accessTokenType']);
                $oauth->setAccessTokenExpiry($oauthData['accessTokenExpiry']);
                $oauth->setRefreshToken($oauthData['refreshToken']);

                $this->saveOauthGoogle($accountId, $oauth);
                break;

            case "twitter":
	            $oauth->setAccessToken($oauthData['accessToken']);
                $oauth->setAccessTokenSecret($oauthData['accessTokenSecret']);
                $oauth->setScreenName($oauthData['screenName']);

                $this->saveOauthTwitter($accountId, $oauth);
                break;
        }
    }


    private function saveOauthGoogle($accountId, Oauth $oauth)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        try {
            $accountCollection = new \MongoCollection($this->mongoDB, "accounts");

            $updateQuery = array(
                Settings::getMongoKeyAccountAttrId() => $accountId,
                'oauth.' . Settings::getMongoKeyOauthAttrId() => $oauth->getId(),
                'oauth.' . Settings::getMongoKeyOauthAttrProvider() => $oauth->getProvider()
            );

            $oauthData = array(
                'oauth.$.' . Settings::getMongoKeyOauthAttrTsLastUpdated() => new MongoTimestamp(),
                'oauth.$.' . Settings::getMongoKeyOauthAttrAccessToken() => $oauth->getAccessToken(),
                'oauth.$.' . Settings::getMongoKeyOauthAttrAccessTokenType() => $oauth->getAccessTokenType(),
                'oauth.$.' . Settings::getMongoKeyOauthAttrAccessTokenExpiry() => $oauth->getAccessTokenExpiry(),
            );

            if ($oauth->getRefreshToken() != NULL) {
                $oauthData['oauth.$.' . Settings::getMongoKeyOauthAttrRefreshToken()] = $oauth->getRefreshToken();
            }

            $updateData = array(
                '$set' => $oauthData
            );

            $updateResult = $accountCollection->update($updateQuery, $updateData);

            //$this->logger->log(__METHOD__, "HIER update: " . print_r($updateResult, true), LOG_DEBUG);

            if(!$updateResult['updatedExisting'])
            {
                $insertQuery = array(
                    Settings::getMongoKeyAccountAttrId() => $accountId,
                    'oauth.' . Settings::getMongoKeyOauthAttrId() => array(
                        '$ne' => $oauth->getId()
                    ),
                    'oauth.' . Settings::getMongoKeyOauthAttrProvider() => array(
                        '$ne' => $oauth->getProvider()
                    )
                );

                $insertData = array(
                    '$push' => array(
                        'oauth' => array(
                            Settings::getMongoKeyOauthAttrId() => $oauth->getId(),
                            Settings::getMongoKeyOauthAttrProvider() => $oauth->getProvider(),
                            Settings::getMongoKeyOauthAttrAccessToken() => $oauth->getAccessToken(),
                            Settings::getMongoKeyOauthAttrAccessTokenType() => $oauth->getAccessTokenType(),
                            Settings::getMongoKeyOauthAttrAccessTokenExpiry() => $oauth->getAccessTokenExpiry(),
                            Settings::getMongoKeyOauthAttrRefreshToken() => $oauth->getRefreshToken(),
                            Settings::getMongoKeyOauthAttrTsAdded() => new MongoTimestamp()
                        )
                    )
                );

                $insertResult = $accountCollection->update($insertQuery, $insertData);

                //$this->logger->log(__METHOD__, "HIER insert: " . print_r($insertResult, true), LOG_DEBUG);
            }
            //TODO: return code auswerten
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception on update: " . $e->getMessage(), LOG_ERR);
            return false;
        }

        return $oauth->getId();
    }



    private function saveOauthTwitter($accountId, Oauth $oauth)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        try {
            $accountCollection = new \MongoCollection($this->mongoDB, "accounts");

            $updateQuery = array(
                Settings::getMongoKeyAccountAttrId() => $accountId,
                'oauth.' . Settings::getMongoKeyOauthAttrId() => $oauth->getId(),
                'oauth.' . Settings::getMongoKeyOauthAttrProvider() => $oauth->getProvider()
            );

            $oauthData = array(
                'oauth.$.' . Settings::getMongoKeyOauthAttrTsLastUpdated() => new MongoTimestamp()
            );

            $updateData = array(
                '$set' => $oauthData
            );

            $updateResult = $accountCollection->update($updateQuery, $updateData);

            //$this->logger->log(__METHOD__, "HIER update: " . print_r($updateResult, true), LOG_DEBUG);

            if(!$updateResult['updatedExisting'])
            {
                $insertQuery = array(
                    Settings::getMongoKeyAccountAttrId() => $accountId,
                    'oauth.' . Settings::getMongoKeyOauthAttrId() => array(
                        '$ne' => $oauth->getId()
                    ),
                    'oauth.' . Settings::getMongoKeyOauthAttrProvider() => array(
                        '$ne' => $oauth->getProvider()
                    )
                );

                $insertData = array(
                    '$push' => array(
                        'oauth' => array(
                            Settings::getMongoKeyOauthAttrId() => $oauth->getId(),
                            Settings::getMongoKeyOauthAttrProvider() => $oauth->getProvider(),
                            Settings::getMongoKeyOauthAttrScreenName() => $oauth->getScreenName(),
                            Settings::getMongoKeyOauthAttrAccessToken() => $oauth->getAccessToken(),
                            Settings::getMongoKeyOauthAttrAccessTokenSecret() => $oauth->getAccessTokenSecret(),
                            Settings::getMongoKeyOauthAttrTsAdded() => new MongoTimestamp()
                        )
                    )
                );

                $insertResult = $accountCollection->update($insertQuery, $insertData);

                //$this->logger->log(__METHOD__, "HIER insert: " . print_r($insertResult, true), LOG_DEBUG);
            }
            //TODO: return code auswerten
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception on update: " . $e->getMessage(), LOG_ERR);
            return false;
        }

        return $oauth->getId();
    }



	private function saveOauthLocal($accountId, Oauth $oauth)
	{
		$this->logger->log(__METHOD__, NULL, LOG_DEBUG);

		try {
			$accountCollection = new \MongoCollection($this->mongoDB, "accounts");

			$updateQuery = array(
				Settings::getMongoKeyAccountAttrId() => $accountId,
				'oauth.' . Settings::getMongoKeyOauthAttrId() => $oauth->getId(),
				'oauth.' . Settings::getMongoKeyOauthAttrProvider() => $oauth->getProvider()
			);

			$oauthData = array(
				'oauth.$.' . Settings::getMongoKeyOauthAttrTsLastUpdated() => new MongoTimestamp()
			);

			$updateData = array(
				'$set' => $oauthData
			);

			$updateResult = $accountCollection->update($updateQuery, $updateData);

			//$this->logger->log(__METHOD__, "HIER update: " . print_r($updateResult, true), LOG_DEBUG);

			if(!$updateResult['updatedExisting'])
			{
				$insertQuery = array(
					Settings::getMongoKeyAccountAttrId() => $accountId,
					'oauth.' . Settings::getMongoKeyOauthAttrId() => array(
						'$ne' => $oauth->getId()
					),
					'oauth.' . Settings::getMongoKeyOauthAttrProvider() => array(
						'$ne' => $oauth->getProvider()
					)
				);

				$insertData = array(
					'$push' => array(
						'oauth' => array(
							Settings::getMongoKeyOauthAttrId() => $oauth->getId(),
							Settings::getMongoKeyOauthAttrProvider() => $oauth->getProvider(),
							Settings::getMongoKeyOauthAttrPassword() => $oauth->getPassword(),
							Settings::getMongoKeyOauthAttrTsAdded() => new MongoTimestamp()
						)
					)
				);

				$insertResult = $accountCollection->update($insertQuery, $insertData);

				//$this->logger->log(__METHOD__, "HIER insert: " . print_r($insertResult, true), LOG_DEBUG);
			}
			//TODO: return code auswerten
		} catch (\Exception $e) {
			$this->logger->log(__METHOD__, "exception on update: " . $e->getMessage(), LOG_ERR);
			return false;
		}

		return $oauth->getId();
	}



    public function deleteOauthTwitter(Oauth $oauth)
    {
        //FIXME: implement deleteOauthTwitter for MongoDB
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        try {
            $sql = "DELETE FROM accounts_auth WHERE id = ?";
            $stmt = $this->db->prepare($sql);

            $stmt->execute(array($oauth->getId()));

            $this->logger->log(__METHOD__, "deleted twitter credentials for oauth " . $oauth->getOauthId(), LOG_INFO);
            return true;
        } catch (\PDOException $e) {
            $this->logger->log(__METHOD__, "exception: " . $e->getMessage(), LOG_ERR);
            return false;
        }
    }



    public function getAllProfiles($accountId)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $profiles = NULL;

        try {
            $accountCollection = new \MongoCollection($this->mongoDB, "accounts");

            $query = array(
                Settings::getMongoKeyAccountAttrId() => $accountId
            );

            $fields = array(
                Settings::getMongoKeyAccountAttrId() => true,
                'oauth.' . Settings::getMongoKeyOauthAttrId() => true
            );

            $accountResults = $accountCollection->findOne($query, $fields);

            if(is_array($accountResults['oauth'])) {
                $profiles = array();

                foreach($accountResults['oauth'] as $oauthData) {
                    $oauth = $this->getById($oauthData[Settings::getMongoKeyOauthAttrId()]);

                    if ($oauth instanceof Oauth) {
                        $profiles[] = $oauth;
                        $this->logger->log(__METHOD__, "found oauth " . $oauth->getId() . " in MongoDB", LOG_INFO);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "died (" . $e->getMessage() . ")", LOG_ERR);
            die($e->getMessage());
        }

        return $profiles;
    }



    public function getByProvider($accountId, $provider)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $profile = NULL;

        try {
            $accountCollection = new \MongoCollection($this->mongoDB, "accounts");

            $query = array(
                Settings::getMongoKeyAccountAttrId() => $accountId,
                'oauth.' . Settings::getMongoKeyOauthAttrProvider() => $provider
            );

            $fields = array(
                Settings::getMongoKeyAccountAttrId() => true,
                'oauth.$' => true
            );

            $accountData = $accountCollection->findOne($query, $fields);

            if(is_array($accountData['oauth'])) {
                //print_r($accountData);
                $oauthData = $accountData['oauth'][0];
                $oauth = OauthModel::load($oauthData);

                if($oauth instanceof Oauth) {
                    $profile = $oauth;
                    $this->logger->log(__METHOD__, "found oauth " . $oauth->getId() . " in MongoDB", LOG_INFO);
                }
            }
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "died (" . $e->getMessage() . ")", LOG_ERR);
            die($e->getMessage());
        }


        return $profile;
    }
}
