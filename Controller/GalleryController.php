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

use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\View\View;
use \JMS\Serializer\SerializationContext;
use \Doctrine\DBAL\DBALException;
use \JMS\Serializer\DeserializationContext;
use Togu\MediaBundle\Document\Gallery;
use Sonata\MediaBundle\Model\MediaManagerInterface;
use FOS\RestBundle\Controller\Annotations\Route;


/**
 * Class PageController
 * @package Togu\MediaBundle\Controller
 */
class GalleryController extends FOSRestController {

	/**
     * @param ParamFetcherInterface $paramFetcher
     *
     * @QueryParam(name="nodeId", default=null, description="NodeId to fetch")
     * @QueryParam(name="query", default=null, description="An optional query to override the default behaviour")
     *
     * @QueryParam(name="page", requirements="\d+", default="1", description="Page of the list.")
     * @QueryParam(name="start", requirements="\d+", default="0", description="Offset of the list")
     * @QueryParam(name="limit", requirements="\d+", default="250", description="Number of record per fetch.")
     * @QueryParam(name="sort", description="Sort result by field in URL encoded JSON format", default="[]")
     * @QueryParam(name="filter", description="Search filter in URL encoded JSON format", default="[]")
     * @QueryParam(name="group", description="The JMS Serializer group", default="")
     * @QueryParam(name="depth", description="The depth to use for serialization", default="1")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
	public function getQueryAction(ParamFetcherInterface $paramFetcher) {
		$manager = $this->get('doctrine_phpcr.odm.default_document_manager');
        $rawSorters = json_decode($paramFetcher->get("sort"), true);
        $sorters = array();
        foreach ($rawSorters as $s) {
            $sorters[$s['property']] = $s['direction'];
        }
        $rawFilters = json_decode($paramFetcher->get("filter"), true);
        $filters = array();
        foreach ($rawFilters as $f) {
            $filters[$f['property']] = $f['value'];
        }
        $start = 0;
        if ($paramFetcher->get("start") === "0") {
            if ($paramFetcher->get("page") > 1) {
                $start = ($paramFetcher->get("page")-1) * $paramFetcher->get("limit");
            }
        } else {
            $start = $paramFetcher->get("start");
        }

        $id = $paramFetcher->get("nodeId");
        $query = $paramFetcher->get("query");
        if($id !== null) {
        	$filters['leaf'] = true;
        	$filters['parentId'] = array('child' => $manager->find(null, $id)->getPath());
	        $queryType = 'id';
        } elseif($query !== null) {
        	$filters['text'] = array(
        		'like' => $query . '%'
        	);
        	$queryType = 'query';
        } else {
        	try {
        		throw new \Exception("An Id or a query are mandatory");
        	} catch (DBALException $e) {
        		return $this->handleView(
        				View::create(array('errors'=>array($e->getMessage())), 400)
        		);
        	}
        }
        $nodes = $manager->getRepository('ToguMediaBundle:Gallery')->findBy(
            $filters,
            $sorters,
            $paramFetcher->get("limit"),
            $start
        );
        $mediaManager = $this->get('sonata.media.manager.media');
       	$mediaPool = $this->get('sonata.media.pool');

       	$parentFolders = array();
       	$folders = array();
       	foreach($nodes as $node) {
       		if($node->isLeaf() === true) {
       			$parent = $node->getParentId();
       			$parentId = $parent->getId();
       			if(! isset($folders[$parentId])) {
       				$folders[$parentId] = array();
	       			$parentFolders[$parentId] = $parent;
       			}
       			$folders[$parentId][$node->getId()] = $node;
       		} else {
       			$nodeId = $node->getId();
       			foreach($node->getChildren() as $child) {
       				// Avoids empty folders
	       			if(! isset($folders[$nodeId])) {
	       				$folders[$nodeId] = array();
		       			$parentFolders[$nodeId] = $node;
	       			}
       				$folders[$nodeId][$child->getId()] = $child;
       			}
       		}
       	}

        $list = array();
       	foreach($folders as $id => $folder) {
       		$parent = $parentFolders[$id];
       		$folderName = "";
       		do {
       			$folderName = "/" . $parent->getText() . $folderName;
	       		$isRoot = $parent->isRoot();
       			$parent = $parent->getParentId();
       		} while(! $isRoot);

       		$images = array();
       		foreach($folder as $imageId => $image) {
       			$media = $image->getImageId();
       			if(! $media) {
       				continue;
       			}
       			$images[] = $media;
       		}

       		$entry = array(
       			'queryType' => $queryType,
       			'folderName' => $folderName,
       			'images' => $images
       		);
       		if($queryType == 'query') {
       			$entry['query'] = $query;
       		}

       		array_push($list, $entry);
       	}

        $context = $this->getSerializerContext(array('list'));
        $view = View::create($list, 200)->setSerializationContext($context);
        return $this->handleView($view);
	}


    /**
     * Get list of a Gallery record
     * @param ParamFetcherInterface $paramFetcher
     *
     * @Route(requirements={"id"=".+"})
     *
     * @QueryParam(name="page", requirements="\d+", default="1", description="Page of the list.")
     * @QueryParam(name="start", requirements="\d+", default="0", description="Offset of the list")
     * @QueryParam(name="limit", requirements="\d+", default="25", description="Number of record per fetch.")
     * @QueryParam(name="sort", description="Sort result by field in URL encoded JSON format", default="[]")
     * @QueryParam(name="filter", description="Search filter in URL encoded JSON format", default="[]")
     * @QueryParam(name="group", description="The JMS Serializer group", default="")
     * @QueryParam(name="depth", description="The depth to use for serialization", default="1")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getGalleryAction($id, ParamFetcherInterface $paramFetcher) {
        $manager = $this->get('doctrine_phpcr.odm.default_document_manager');
        $rawSorters = json_decode($paramFetcher->get("sort"), true);
        $sorters = array();
        foreach ($rawSorters as $s) {
            $sorters[$s['property']] = $s['direction'];
        }
        $rawFilters = json_decode($paramFetcher->get("filter"), true);
        $filters = array();
        foreach ($rawFilters as $f) {
            $filters[$f['property']] = $f['value'];
        }
        $start = 0;
        if ($paramFetcher->get("start") === "0") {
            if ($paramFetcher->get("page") > 1) {
                $start = ($paramFetcher->get("page")-1) * $paramFetcher->get("limit");
            }
        } else {
            $start = $paramFetcher->get("start");
        }
        $filters['parentId'] = array('child' => $manager->find(null, $id)->getPath());

        $list = $manager->getRepository('ToguMediaBundle:Gallery')->findBy(
            $filters,
            $sorters,
            $paramFetcher->get("limit"),
            $start
        );
        $list = array_values($list->toArray());
        $context = $this->getSerializerContext(array('list'));
        $view = View::create($list, 200)->setSerializationContext($context);
        return $this->handleView($view);
    }

    /**
     * Create a new Gallery record
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postGalleryAction() {
        $serializer = $this->get("tpg_extjs.phpcr_serializer");
        $entity = $serializer->deserialize(
            $this->getRequest()->getContent(),
            'Togu\MediaBundle\Document\Gallery',
            'json',
            DeserializationContext::create()->setGroups(array("Default", "post"))
        );
        $validator = $this->get('validator');
        $validations = $validator->validate($entity, array('Default', 'post'));
        if ($validations->count() === 0) {
            $manager = $this->get('doctrine_phpcr.odm.default_document_manager');
            $manager->persist($entity);
            try {
                $manager->flush();
            } catch (DBALException $e) {
                return $this->handleView(
                    View::create(array('errors'=>array($e->getMessage())), 400)
                );
            }
            return $this->handleView(
                View::create(array(
                	"success" => true,
                	"records" => array($entity)
                ), 200)->setSerializationContext($this->getSerializerContext())
            );
        } else {
            return $this->handleView(
                View::create(array('errors'=>$validations), 400)
            );
        }
    }

    /**
     * Update an existing Gallery record
     * @param $id
     *
     * @Route(requirements={"id"=".+"})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putGalleryAction($id) {
        $manager = $this->get('doctrine_phpcr.odm.default_document_manager');
        $entity = $manager->getRepository('ToguMediaBundle:Gallery')->find($id);
        if ($entity === null) {
            return $this->handleView(View::create('', 404));
        }
        $serializer = $this->get("tpg_extjs.phpcr_serializer");
        $entity = $serializer->deserialize(
            $this->getRequest()->getContent(),
            'Togu\\MediaBundle\\Document\\Gallery',
            'json',
            DeserializationContext::create()->setGroups(array("Default", "put"))
        );
        $validator = $this->get('validator', array('Default', 'put'));
        $validations = $validator->validate($entity);
        if ($validations->count() === 0) {
            try {
//                $manager->merge($entity);
                $manager->flush();
            } catch (DBALException $e) {
                return $this->handleView(
                    View::create(array('errors'=>array($e->getMessage())), 400)
                );
            }
            return $this->handleView(
                View::create(array(
                	"success" => true,
                	"records" => array($entity)
                ), 200)->setSerializationContext($this->getSerializerContext(array("get")))
            );
        } else {
            return $this->handleView(
                View::create(array('errors'=>$validations), 400)
            );
        }
    }

    /**
     * Patch an existing Gallery record
     * @param $id
     *
     * @Route(requirements={"id"=".+"})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function patchGalleryAction($id) {
        $manager = $this->get('doctrine_phpcr.odm.default_document_manager');
        $entity = $manager->getRepository('ToguMediaBundle:Gallery')->find($id);
        if ($entity === null) {
            return $this->handleView(View::create('', 404));
        }
        $content = json_decode($this->getRequest()->getContent(), true);
        $content['id'] = $id;
        $serializer = $this->get("tpg_extjs.phpcr_serializer");
        $dContext = DeserializationContext::create()->setGroups(array("Default", "patch"));
        $dContext->attributes->set('related_action', 'merge');
        $entity = $serializer->deserialize(
            json_encode($content),
            'Togu\MediaBundle\Document\Gallery',
            'json',
            $dContext
        );
        $validator = $this->get('validator');
        $validations = $validator->validate($entity, array('Default', 'patch'));
        if ($validations->count() === 0) {
            try {
                $manager->flush();
            } catch (DBALException $e) {
                return $this->handleView(
                    View::create(array('errors'=>array($e->getMessage())), 400)
                );
            }
            return $this->handleView(
                View::create(array(
                	"success" => true,
                	"records" => array($entity)
                ), 200)->setSerializationContext($this->getSerializerContext(array("get")))
            );
        } else {
            return $this->handleView(
                View::create(array('errors'=>$validations), 400)
            );
        }
    }

    /**
     * Delete an existing Gallery record
     * @param $id
     *
     * @Route(requirements={"id"=".+"})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteGalleryAction($id) {
        $manager = $this->get('doctrine_phpcr.odm.default_document_manager');
        $entity = $manager->getRepository('ToguMediaBundle:Gallery')->find($id);
        $manager->remove($entity);
        $manager->flush();
        return $this->handleView(View::create(null, 204));
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
