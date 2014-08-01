<?php

/*
 * Copyright (c) 2012-2014 Alessandro Siragusa <alessandro@togu.io>
 *
 * This file is part of the Togu CMS.
 *
 * Togu is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Togu is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Togu.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Togu\MediaBundle\Document;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

use Tpg\ExtjsBundle\Annotation as Extjs;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @PHPCR\Document(referenceable=false)
 */
class RootNode {
    /**
     * @var string
     * @PHPCR\Id()
     * @JMS\Exclude()
     */
    protected $path;

    /**
     * @PHPCR\ParentDocument()
     * @JMS\Type("phpcr_parentdocument")
     */
    protected $parentId;

    /**
     * @var string
     * @PHPCR\String
     * @JMS\Type("string")
     */
    protected $text;

    /**
     * @var string
     * @PHPCR\Nodename
     */
    protected $id;

    /**
     * @PHPCR\Children
     * @JMS\Exclude
     */
    protected $children;

    /**
     * @PHPCR\Boolean
     */
    protected $leaf;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

   	/**
   	 *
   	 * @param string $id
   	 */
    public function setId($id) {
    	$this->id = $id;
    }

    /**
     * Get path
     *
     * @return integer
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     *
     * @param unknown $parent
     */
    public function setParentId($parent) {
    	$this->parentId = $parent;
    }

    /**
     *
     */
    public function getParentId() {
    	return $this->parentId;
    }

	/**
	 * @return string
	 */
    public function getText() {
    	return $this->text;
    }

    /**
     *
     * @param string $text
     */
    public function setText($text) {
    	$this->text = $text;
    }

    /**
     *
     */
    public function getChildren() {
    	return $this->children;
    }

    /**
     * @return boolean
     */
    public function isLeaf() {
    	return $this->leaf;
    }

    /**
     *
     * @param boolean $leaf
     */
    public function setLeaf($leaf) {
    	$this->leaf = $leaf;
    }

    /**
     *
     * @return boolean
     */
    public function isRoot() {
    	return true;
    }
}
