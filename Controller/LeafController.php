<?php

namespace wbx\TreeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use wbx\TreeBundle\Entity\Leaf;
use wbx\TreeBundle\Form\LeafType;
use wbx\TreeBundle\Entity\LeafRepository;


class LeafController extends Controller {

    protected $route_prefix = "tree";

    protected function getRepository() {
        return $this->getDoctrine()->getManager()->getRepository("wbxTreeBundle:Leaf");
    }

    protected function getNewEntity() {
        return new Leaf();
    }

    protected function getExtraUrls() {
        return array();
    }

    protected function getRoot() {
        return $this->getRepository()->getRoot();
    }

    protected function getTree($repo) {
        $tree_id = "node";
        $tree = $repo->getRootJsHtmlTree($tree_id, true, array(
            'decorateVal' => function($node) {
                return $node["name"];
            },
            'decorateLi' => function($node) use($tree_id) {
                $result = '<li id="' . $tree_id . '_' . $node["id"] . '"';

                $params = array(
                    'id'    => "getId",
                    'name'  => "getName",
                    'slug'  => "getSlug",
                    'lvl'   => "getLevel",
                );

                foreach ($params as $k => $v) {
                    $result .= ' data_' . $k . '="' . $node[$k] . '"';
                }

                if ($node["lvl"] == 0) {
                    $result .= ' data_type="root"';
                }

                return $result .= '>';
            }
        ));

        return $tree;
    }


    protected function index() {
        $repo = $this->getRepository();

        $tree = $this->getTree($repo);

        $url_add = $this->generateUrl($this->route_prefix . '_add', array('parent_id' => "PARENT_ID", 'position' => "POSITION"));
        $url_rename = $this->generateUrl($this->route_prefix . '_rename', array('id' => "ID"));
        $url_delete = $this->generateUrl($this->route_prefix . '_delete', array('id' => "ID"));
        $url_move = $this->generateUrl($this->route_prefix . '_move', array('id' => "ID", 'parent_id' => "PARENT_ID", 'position' => "POSITION"));

        $delete_form = $this->get('form.factory')->createNamedBuilder('wbxtree_leaf_delete', 'form', array())->add('id', 'hidden')->getForm();

        return array(
            'tree' => $tree,
            'url_add'  =>  $url_add,
            'url_rename'  =>  $url_rename,
            'url_delete'  =>  $url_delete,
            'url_move'  =>  $url_move,
            'extra_urls'  =>  $this->getExtraUrls(),
            'delete_form'  =>  $delete_form->createView(),
        );
    }


    protected function addCreateEntity($name, $parent) {
        $entity = $this->getNewEntity();
        $entity->setName($name);
        $entity->setParent($parent);

        return $entity;
    }

    protected function add($parent_id, $position) {
        $em = $this->getDoctrine()->getManager();
        $request = $this->getRequest();

        $parent = $this->getRepository()->find($parent_id);
        if (!$parent) {
            return new JsonResponse(array('message' => "parent id = " . $parent_id . " not found"), 404);
        }
        else {
            $entity = $this->addCreateEntity($request->request->get('name', 'New node'), $parent);

            $form = $this->createForm(new LeafType(), $entity);
            $form->submit($request);

            if ($form->isValid()) {
                $em->persist($entity);
                $em->flush();

                $this->setPosition($entity, $position);

                $em->persist($entity);
                $em->flush();

                return new JsonResponse(array(
                    'id' => $entity->getId(),
                    'message' => "New node successfully created"
                ), 200);
            }
            else {
                return new JsonResponse(array('message' => "form invalid"), 404);
            }
        }
    }


    protected function rename($id) {
        $em = $this->getDoctrine()->getManager();
        $request = $this->getRequest();

        $entity = $this->getRepository()->find($id);
        if (!$entity) {
            return new JsonResponse(array('message' => "entity id = " . $id . " not found"), 404);
        }
        else {
            $form = $this->createForm(new LeafType(), $entity);
            $form->submit($request);

            if ($form->isValid()) {
                $em->persist($entity);
                $em->flush();

                return new JsonResponse(array(
                    'message' => "Node successfully renamed"
                ), 200);
            }
            else {
                return new JsonResponse(array('message' => "form invalid"), 404);
            }
        }
    }


    protected function doDeleteEntity($entity) {
        $em = $this->getDoctrine()->getManager();
        $em->remove($entity);
    }

    protected function delete($id) {
        $em = $this->getDoctrine()->getManager();
        $request = $this->getRequest();

        $entity = $this->getRepository()->find($id);
        if (!$entity) {
            return new JsonResponse(array('message' => "entity id = " . $id . " not found"), 404);
        }

        if ($entity->isRoot()) {
            return new JsonResponse(array('message' => "ROOT cannot be deleted"), 404);
        }

        $this->doDeleteEntity($entity);
        $em->flush();

        return new JsonResponse(array(
            'message' => "Node successfully deleted"
        ), 200);
    }


    protected function move($id, $parent_id, $position) {
        $em = $this->getDoctrine()->getManager();

        $entity = $this->getRepository()->find($id);
        if (!$entity) {
            return new JsonResponse(array('message' => "entity id = " . $entity_id . " not found"), 404);
        }
        else if ($entity->getSlug() == "root") {
            return new JsonResponse(array('message' => 'ROOT cannot be moved'), 404);
        }
        else {
            $children_ids = $this->getRepository()->getChildrenIds($id, false);

            $parent = $this->getRepository()->find($parent_id);
            if (!$parent) {
                return new JsonResponse(array('message' => "parent id = " . $id . " not found"), 404);
            }
            else if (in_array($parent->getId(), $children_ids)) {
                return new JsonResponse(array('message' => 'A node cannot be moved to one of its children'), 404);
            }
            else {
                $entity->setParent($parent);
                $em->persist($entity);
                $em->flush();

                $this->setPosition($entity, $position);

                $em->persist($entity);
                $em->flush();

                return new JsonResponse(array('message' => "Node successfully moved"), 200);
            }
        }
    }


    protected function setPosition(&$entity, $position) {
        $prev_siblings = $this->getRepository()->getPrevSiblings($entity);

        $d = $position - count($prev_siblings);
        if ($d > 0) {
            $this->getRepository()->moveDown($entity, $d);
        }
        else if ($d < 0) {
            $this->getRepository()->moveUp($entity, abs($d));
        }
    }

}
