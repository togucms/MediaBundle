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
use Togu\AnnotationBundle\Annotation as TOGU;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @PHPCR\Document(referenceable=true)
 */
class Gallery extends Tree {
	/**
	 * @var int
	 * @PHPCR\Int(nullable=true)
	 * @JMS\Type("image")
	 * @TOGU\Type(type="image")
	 */
    protected $imageId;

    /**
     * ImageId getter
     * @return int
     */
    public function getImageId() {
    	return $this->imageId;
    }

    /**
     * ImageId setter
     * @param int $imageId
     */
    public function setImageId($imageId) {
    	$this->imageId = $imageId;
    }
}
