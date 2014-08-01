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

namespace Togu\MediaBundle\Handler;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\VisitorInterface;
use JMS\Serializer\Context;
use Doctrine\Bundle\PHPCRBundle\ManagerRegistry;

use Sonata\MediaBundle\Provider\Pool;
use Application\Sonata\MediaBundle\Entity\Media;

class RawImageTypeHandler implements SubscribingHandlerInterface {
	private $mediaPool;

	/**
	 *
	 * @param MediaManagerInterface $mediaManager
	 */
	public function __construct(Pool $pool)
	{
		$this->mediaPool = $pool;
	}

	public static function getSubscribingMethods()
	{
		return array(
			array(
				'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
				'format' => 'json',
				'type' => 'Application\\Sonata\\MediaBundle\\Entity\\Media',
				'method' => 'serializeImage',
			)
		);
	}

	public function serializeImage(VisitorInterface $visitor, Media $media, array $type, Context $context) {
		$formats = array('reference');
		$formats = array_merge($formats, array_keys($this->mediaPool->getFormatNamesByContext($media->getContext())));

		$provider = $this->mediaPool->getProvider($media->getProviderName());

		$properties = array();
		foreach ($formats as $format) {
			$properties[$format] =  $provider->getHelperProperties($media, $format);
		}
		$properties['id'] = $media->getId();

		return $properties;
	}
}