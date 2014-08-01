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

namespace Togu\MediaBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;

use Sonata\MediaBundle\Model\MediaManagerInterface;

use Togu\AnnotationBundle\Data\AnnotationProcessor;

class ImageReferenceSubscriber implements EventSubscriber
{
	private $mediaManager;
	private $typeProcessor;

	public function __construct(MediaManagerInterface $mediaManager, AnnotationProcessor $typeProcessor)
	{
		$this->mediaManager = $mediaManager;
		$this->typeProcessor = $typeProcessor;
	}

	public function getSubscribedEvents()
	{
		return array(
				'postLoad',
				'prePersist',
				'preUpdate',
				'postPersist',
				'postUpdate',
		);
	}

	protected function switchToObject(LifecycleEventArgs $args) {
		$entity = $args->getEntity();

		$imageFields = $this->typeProcessor->getFieldsOfType($entity, 'image');
		foreach ($imageFields as $field) {
			$imageId = $field->getValue($entity);
			if($imageId !== null) {
				$field->setValue($entity, $this->mediaManager->find($imageId));
			}
		}
	}

	protected function switchToId(LifecycleEventArgs $args) {
		$entity = $args->getEntity();

		$imageFields = $this->typeProcessor->getFieldsOfType($entity, 'image');
		foreach ($imageFields as $field) {
			$imageId = $field->getValue($entity);
			if($imageId && $imageId instanceof \Application\Sonata\MediaBundle\Entity\Media) {
				$imageId = $imageId->getId();
			}
			$field->setValue($entity, $imageId);
		}
	}
	public function postLoad(LifecycleEventArgs $args) {
		$this->switchToObject($args);
	}

	public function postPersist(LifecycleEventArgs $args) {
		$this->switchToObject($args);
	}

	public function postUpdate(LifecycleEventArgs $args) {
		$this->switchToObject($args);
	}

	public function prePersist(LifecycleEventArgs $args) {
		$this->switchToId($args);
	}

	public function preUpdate(LifecycleEventArgs $args) {
		$this->switchToId($args);
	}
}