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

namespace ThumbSniper\objective;

use \JsonSerializable;


class CachedImage implements JsonSerializable
{
    private $id;
    private $targetId;
    private $imageData;

    private $weapon;
    private $tsCaptured;
    private $snipeDuration;
    private $fileType;
	private $ttl;


    function __construct(array $objData = NULL)
    {
        if ($objData) {
            $this->id = $objData['id'];
            $this->targetId = $objData['targetId'];
            $this->imageData = $objData['imageData'];
            $this->weapon = $objData['weapon'];
            $this->tsCaptured = $objData['tsCaptured'];
            $this->snipeDuration = $objData['snipeDuration'];
            $this->fileType = $objData['fileType'];
	        $this->ttl = $objData['ttl'];
        }
    }


    public function getId()
    {
        return $this->id;
    }


    public function setId($id)
    {
        $this->id = $id;
    }


    public function getTargetId()
    {
        return $this->targetId;
    }


    public function setTargetId($targetId)
    {
        $this->targetId = $targetId;
    }


    public function getImageData()
    {
        return $this->imageData;
    }


    public function setImageData($imageData)
    {
        $this->imageData = $imageData;
    }


    public function getWeapon()
    {
        return $this->weapon;
    }


    public function setWeapon($weapon)
    {
        $this->weapon = $weapon;
    }


    public function getTsCaptured()
    {
        return $this->tsCaptured;
    }


    public function setTsCaptured($tsCaptured)
    {
        $this->tsCaptured = $tsCaptured;
    }


    public function getSnipeDuration()
    {
        return $this->snipeDuration;
    }


    public function setSnipeDuration($snipeDuration)
    {
        $this->snipeDuration = $snipeDuration;
    }

    /**
     * @return mixed
     */
    public function getFileType()
    {
        return $this->fileType;
    }

    /**
     * @param mixed $fileType
     */
    public function setFileType($fileType)
    {
        $this->fileType = $fileType;
    }

	/**
	 * @return mixed
	 */
	public function getTtl()
	{
		return $this->ttl;
	}

	/**
	 * @param mixed $ttl
	 */
	public function setTtl($ttl)
	{
		$this->ttl = $ttl;
	}

    public function jsonSerialize()
    {
        $objData = array();

        $objData['id'] = $this->id;
        $objData['targetId'] = $this->targetId;
        $objData['imageData'] = $this->imageData;
        $objData['weapon'] = $this->weapon;
        $objData['tsCaptured'] = $this->tsCaptured;
        $objData['snipeDuration'] = $this->snipeDuration;
        $objData['fileType'] = $this->fileType;
	    $objData['ttl'] = $this->ttl;

        return $objData;
    }
}

?>
