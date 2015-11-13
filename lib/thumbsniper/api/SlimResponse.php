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


class SlimResponse
{
	/** @var string */
	private $expires;

	/** @var string */
	private $cacheControl;

	/** @var string */
	private $pragma;

	/** @var string */
	private $lastModified;

	/** @var array */
	private $redirect;

	/** @var string */
	private $contentType;

	/** @var string */
	private $output;

	/** @var int */
	private $httpStatus;


	function __construct()
	{
	}

	/**
	 * @return string
	 */
	public function getExpires()
	{
		return $this->expires;
	}

	/**
	 * @param string $expires
	 */
	public function setExpires($expires)
	{
		$this->expires = $expires;
	}

	/**
	 * @return string
	 */
	public function getCacheControl()
	{
		return $this->cacheControl;
	}

	/**
	 * @param string $cacheControl
	 */
	public function setCacheControl($cacheControl)
	{
		$this->cacheControl = $cacheControl;
	}

	/**
	 * @return string
	 */
	public function getPragma()
	{
		return $this->pragma;
	}

	/**
	 * @param string $pragma
	 */
	public function setPragma($pragma)
	{
		$this->pragma = $pragma;
	}

	/**
	 * @return mixed
	 */
	public function getRedirect()
	{
		return $this->redirect;
	}

	/**
	 * @param mixed $redirect
	 */
	public function setRedirect($redirect)
	{
		$this->redirect = $redirect;
	}

	/**
	 * @return string
	 */
	public function getContentType()
	{
		return $this->contentType;
	}

	/**
	 * @param string $contentType
	 */
	public function setContentType($contentType)
	{
		$this->contentType = $contentType;
	}

	/**
	 * @return string
	 */
	public function getOutput()
	{
		return $this->output;
	}

	/**
	 * @param string $output
	 */
	public function setOutput($output)
	{
		$this->output = $output;
	}

	/**
	 * @return int
	 */
	public function getHttpStatus()
	{
		return $this->httpStatus;
	}

	/**
	 * @param int $httpStatus
	 */
	public function setHttpStatus($httpStatus)
	{
		$this->httpStatus = $httpStatus;
	}

	/**
	 * @return string
	 */
	public function getLastModified()
	{
		return $this->lastModified;
	}

	/**
	 * @param string $lastModified
	 */
	public function setLastModified($lastModified)
	{
		$this->lastModified = $lastModified;
	}
}
