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

namespace Togu\MediaBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\View\View;
use \JMS\Serializer\SerializationContext;
use \Doctrine\DBAL\DBALException;
use \JMS\Serializer\DeserializationContext;
use FOS\RestBundle\Controller\Annotations\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class EntitiesController
 */
class MediaController extends FOSRestController {
    /**
     * Get detail of a media
     * @param              $id
     *
     * @Route(requirements={"id"="\d+"})
     *
     * @QueryParam(name="entity", description="EntityName", default="")
     * @QueryParam(name="group", description="The JMS Serializer group", default="")
     * @QueryParam(name="depth", description="The depth to use for serialization", default="1")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getMediaAction(ParamFetcherInterface $paramFetcher, $id) {
        $manager = $this->get("sonata.media.manager.media");
        $media = $manager->find($id);
        $view = View::create($this->formatMedia($media), 200)->setSerializationContext($this->getSerializerContext(array("get")));;
        return $this->handleView($view);
    }


    /**
     * Create a new media
     *
     * @Route(requirements={"provider"="[A-Za-z0-9.]*"})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postMediaAction($provider, Request $request) {
    	$pool = $this->get("sonata.media.pool");
        $manager = $this->get("sonata.media.manager.media");
    	$formFactory = $this->get('form.factory');

    	try {
    		$mediaProvider = $pool->getProvider($provider);
    	} catch (\RuntimeException $ex) {
    		throw new NotFoundHttpException($ex->getMessage(), $ex);
    	}
    	$medium = $manager->create();
    	$medium->setProviderName($provider);
    	$form = $formFactory->createNamed(null, 'sonata_media_api_form_media', $medium, array(
    			'provider_name'   => $mediaProvider->getName(),
//    			'csrf_protection' => false
    	));

    	$form->bind($request);

    	if ($form->isValid()) {
            try {
	    		$medium = $form->getData();
	    		$manager->save($medium);
            } catch (DBALException $e) {
                return $this->handleView(
                    View::create(array('errors'=>array($e->getMessage())), 400)
                );
            }
            return $this->handleView(
                View::create($this->formatMedia($medium), 200, array('Location'=>$this->generateUrl(
                    "_togu_media_rest_get_media",
                    array('id'=>$medium->getId()),
                    true
                )))->setSerializationContext($this->getSerializerContext(array('sonata_api_read')))
            );
    	} else {
            return $this->handleView(
                View::create(array('errors'=>$form->getErrors()), 400)
            );
        }
    }

    protected function formatMedia($media) {
    	return array(
        	"success" => $media !== null,
        	"total" => $media !== null ? 1 : 0,
        	"records" => array($media)
        );
    }

    protected function getSerializerContext($groups = array(), $version = null) {
        $serializeContext = SerializationContext::create();
        $serializeContext->enableMaxDepthChecks();
        $serializeContext->setGroups(array_merge(
            array(\JMS\Serializer\Exclusion\GroupsExclusionStrategy::DEFAULT_GROUP),
            $groups
        ));
        if ($version !== null) $serializeContext->setVersion($version);
        return $serializeContext;
    }
}
