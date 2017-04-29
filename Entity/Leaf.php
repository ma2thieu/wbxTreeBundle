<?php

namespace wbx\TreeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;
use Gedmo\Sluggable\Sluggable;

/**
 * @Orm\MappedSuperclass(repositoryClass="wbx\TreeBundle\Entity\LeafRepository")
 * @Gedmo\Tree(type="nested")
 */
class Leaf {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", unique=true, length=255)
     * @Gedmo\Slug(fields={"name"})
     */
    protected $slug;

    /**
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotBlank()
     */
    protected $name;


    /* NESTED TREE */

    /**
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer")
     */
    protected $lft;

    /**
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer")
     */
    protected $lvl;

    /**
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer")
     */
    protected $rgt;

    /**
     * @Gedmo\TreeRoot
     * @ORM\Column(name="root", type="integer", nullable=true)
     */
    protected $root;


    /**
     *  Constructor
     */
    public function __construct() {
        $this->children = new ArrayCollection();
    }


    public function getId() {
        return $this->id;
    }

    public function getSlug() {
        return $this->slug;
    }

    public function __toString() {
        return $this->getName();
    }


    public function getArrayParams() {
        return array(
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
        );
    }


    public function setName($name) {
        $this->name = $name;
    }

    public function getName() {
        return $this->name;
    }


    public function getLvl() {
        return $this->lvl;
    }

    public function getLevel() {
        return $this->lvl;
    }

    public function isRoot() {
        return is_null($this->parent) ? true : false;
    }


    public function setParent($parent) {
        $this->parent = $parent;
    }

    public function getParent() {
        return $this->parent;
    }


    public function getChildren() {
        return $this->children;
    }

}