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

namespace Togu\MediaBundle\DataFixtures\PHPCR;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAware;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Yaml\Yaml;

use Togu\MediaBundle\Document\RootNode;
use Togu\MediaBundle\Document\Gallery;

class LoadGallery extends ContainerAware implements FixtureInterface, OrderedFixtureInterface
{
	protected $manager;
	protected $locator;
	protected $mediaManager;

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {

    	$parentDocument = $manager->find(null, '/media');

        $gallery = new RootNode();
    	$gallery->setParentId($parentDocument);
    	$gallery->setLeaf(false);
    	$gallery->setId('root');
    	$gallery->setText('Images');

        $manager->persist($gallery);

        $this->manager = $manager;
        $configDir = $this->container->getParameter('togu.generator.config.dir');
        $this->mediaManager = $this->container->get('sonata.media.manager.media');

        $this->locator = new FileLocator($configDir . '/fixtures/media');

        $fixtures = Yaml::parse(file_get_contents($this->locator->locate("media.yaml")));

        foreach ($fixtures as $node) {
        	$this->processNode($gallery, $node, $manager);
        }

        $manager->flush();
    }

    protected function processNode($parent, $node) {
    	$gallery = new Gallery();
    	$gallery->setLeaf($node['leaf']);
    	$gallery->setText($node['text']);
    	$gallery->setParentId($parent);

    	if($node['leaf'] === true) {
    		$gallery->setImageId($this->addMedia($node['media']));
	    	$this->manager->persist($gallery);
    		return;
    	}
	   	$this->manager->persist($gallery);

    	if(! isset($node['children'])) {
    		return;
    	}
    	foreach ($node['children'] as $childNode) {
    		$this->processNode($gallery, $childNode);
    	}
    }

    protected function addMedia($mediaInfo) {
    	$media = $this->mediaManager->create();
    	$media->setBinaryContent($this->locator->locate($mediaInfo['fileName']));
    	$media->setEnabled(true);

    	$context = $mediaInfo['context'];
    	$provider = $mediaInfo['provider'];

    	$this->mediaManager->save($media, $context, $provider);

    	return $media;
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
    	return 1;
    }
}
