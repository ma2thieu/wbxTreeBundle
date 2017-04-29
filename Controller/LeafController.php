<?php

namespace wbx\TreeBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
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

    protected function getRoot() {
        return $this->getRepository()->getRoot();
    }


    protected function index() {
        $repo = $this->getRepository();

        $root = $this->getRoot();
        if (!$root) {
            throw $this->createNotFoundException('Unable to find root.');
        }

        $tree = $repo->getHtmlTree($root, "", array(), true);

        $url_add = $this->generateUrl($this->route_prefix . '_add');
        $url_edit = $this->generateUrl($this->route_prefix . '_edit', array('id' => "ID"));
        $url_delete = $this->generateUrl($this->route_prefix . '_delete');
        $url_move_left = $this->generateUrl($this->route_prefix . '_move_left', array('id' => "ID"));
        $url_move_right = $this->generateUrl($this->route_prefix . '_move_right', array('id' => "ID"));
        $url_move_up = $this->generateUrl($this->route_prefix . '_move_up', array('id' => "ID"));
        $url_move_down = $this->generateUrl($this->route_prefix . '_move_down', array('id' => "ID"));

        $delete_form = $this->get('form.factory')->createNamedBuilder('wbxtree_leaf_delete', 'form', array())->add('id', 'hidden')->getForm();

        return array(
            'tree' => $tree,
            'root'  =>  $root,
            'url_add'  =>  $url_add,
            'url_edit'  =>  $url_edit,
            'url_delete'  =>  $url_delete,
            'url_move_left'  =>  $url_move_left,
            'url_move_right'  =>  $url_move_right,
            'url_move_up'  =>  $url_move_up,
            'url_move_down'  =>  $url_move_down,
            'delete_form'  =>  $delete_form->createView(),
        );
    }


    protected function addCreateEntity($name, $parent) {
        $entity = $this->getNewEntity();
        $entity->setName($name);
        $entity->setParent($parent);

        return $entity;
    }

    protected function add() {
        $request = $this->getRequest();

        $parent_id = $request->request->get('id');
        $child_name = $request->request->get('name');

        $em = $this->getDoctrine()->getManager();

        $parent = $this->getRepository()->find($parent_id);

        if (!$parent) {
            $response = new Response(json_encode(array(
                'status'    => false,
                'message'   => "there is no node with id = " . $parent_id,
                'id'        => -1
            )));
        }
        else {
            $entity = $this->addCreateEntity($child_name, $parent);
            $em->persist($entity);
            $em->flush();

            $response = new Response(json_encode(
                array(
                    'status'    => true,
                    'message'   => "",
                    'id'   => $entity->getId(),
                    'params'    => $entity->getArrayParams()
                )
            ));
        }

        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }


    protected function doDeleteEntity($entity) {
        $em = $this->getDoctrine()->getManager();
        $em->remove($entity);
    }

    protected function delete() {
        $request = $this->getRequest();

        $delete_form = $this->get('form.factory')->createNamedBuilder('wbxtree_leaf_delete', 'form', array())->add('id', 'hidden')->getForm();
        $delete_form->submit($request);

        if ($delete_form->isValid()) {
            $values = $request->request->get('wbxtree_leaf_delete');
            $id = $values["id"];

            $em = $this->getDoctrine()->getManager();
            $repo = $this->getRepository();
            $entity = $repo->find($id);

            if (!$entity) {
                $this->get('session')->getFlashBag()->add('error', "entity id = " . $id . " not found");
            }
            else if ($entity->getSlug() == "root") {
                $this->get('session')->getFlashBag()->add('error', 'ROOT cannot be deleted');
            }
            else {
                $this->doDeleteEntity($entity);
                $em->flush();

                $this->get('session')->getFlashBag()->add('info', 'Tag successfully deleted');
            }
        }
        else {
            $this->get('session')->getFlashBag()->add('error', "form invalid");
        }
    }


    protected function moveLeft($id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $this->getRepository()->find($id);
        if (!$entity) {
            $this->get('session')->getFlashBag()->add('error', "entity id = " . $id . " not found");
        }
        else if ($entity->getSlug() == "root") {
            $this->get('session')->getFlashBag()->add('error', 'ROOT cannot be moved');
        }
        else {
            $parent = $entity->getParent();
            if (!$parent) {
                $this->get('session')->getFlashBag()->add('error', "parent not found");
            }
            else {
                $grandparent = $parent->getParent();
                if (!$grandparent) {
                    $this->get('session')->getFlashBag()->add('error', "grand parent not found");
                }
                else {
                    $entity->setParent($grandparent);
                    $em->persist($entity);
                    $em->flush();

                    $this->get('session')->getFlashBag()->add('info', 'Tag successfully moved to the left.');
                }
            }
        }
    }


    protected function moveRight($id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $this->getRepository()->find($id);
        if (!$entity) {
            $this->get('session')->getFlashBag()->add('error', "entity id = " . $id . " not found");
        }
        else if ($entity->getSlug() == "root") {
            $this->get('session')->getFlashBag()->add('error', 'ROOT cannot be moved');
        }
        else {
            $prev_siblings = $this->getRepository()->getPrevSiblings($entity);
            if (count($prev_siblings) < 1) {
                $this->get('session')->getFlashBag()->add('error', "previous sibling not found");
            }
            else {
                $prev_sibling = $prev_siblings[count($prev_siblings) - 1];
                $entity->setParent($prev_sibling);
                $em->persist($entity);
                $em->flush();

                $this->get('session')->getFlashBag()->add('info', 'Tag successfully moved to the right.');
            }
        }
    }


    protected function moveUp($id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $this->getRepository()->find($id);

        if (!$entity) {
            $this->get('session')->getFlashBag()->add('error', "entity id = " . $id . " not found");
        }
        else if ($entity->getSlug() == "root") {
            $this->get('session')->getFlashBag()->add('error', 'ROOT cannot be moved');
        }
        else {
            $this->getRepository()->moveUp($entity);
            $em->flush();

            $this->get('session')->getFlashBag()->add('info', 'Tag successfully moved up.');
        }
    }


    protected function moveDown($id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $this->getRepository()->find($id);

        if (!$entity) {
            $this->get('session')->getFlashBag()->add('error', "entity id = " . $id . " not found");
        }
        else if ($entity->getSlug() == "root") {
            $this->get('session')->getFlashBag()->add('error', 'ROOT cannot be moved');
        }
        else {
            $this->getRepository()->moveDown($entity);
            $em->flush();

            $this->get('session')->getFlashBag()->add('info', 'Tag successfully moved down.');
        }
    }


}
