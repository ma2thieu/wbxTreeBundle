<?php

namespace wbx\TreeBundle\Entity;

use Gedmo\Tree\Entity\Repository\NestedTreeRepository;


class LeafRepository extends NestedTreeRepository {


    public function getRootHtmlTree($link_pattern = "", $path_ids = array(), $expand_all = false) {
        $root = $this->getRoot();
        return $root ? $this->getHtmlTree($root, $link_pattern, $path_ids, $expand_all) : "";
    }


    public function getNotRootHtmlTree($link_pattern = "", $path_ids = array(), $expand_all = false) {
        $tree = "";

        $root = $this->getRoot();
        if (!$root) {
            return $tree;
        }

        foreach ($this->children($root, true) as $child) {
            $tree .= $this->getHtmlTree($child, $link_pattern, $path_ids, $expand_all);
        }

        return $tree;
    }


    public function getNodeParams() {
        return array(
            'id'    => "getId",
            'name'  => "getName",
            'slug'  => "getSlug",
            'lvl'   => "getLevel",
        );
    }


    public function getHtmlTree($node, $link_pattern = "", $path_ids = array(), $expand_all = false) {
        $a = array();
        foreach ($this->getNodeParams() as $k => $v) {
            $a[$k] = $node->$v();
        }

        $a['__children'] = $this->childrenHierarchy($node, false);
        $tree = array($a);

        return $this->_makeTree($tree, $link_pattern, $path_ids, $expand_all);
    }

    private function _makeTree($nodes, $link_pattern = "", $path_ids = array(), $expand_all = false) {
        $result = '<ul>';

        foreach ($nodes as $node) {
            $selected = in_array($node["id"], $path_ids);

            $type = (key_exists("__children", $node) && is_array($node["__children"]) && count($node["__children"]) > 0) ? "folder" : "file";

            if ($type == "folder") {
                $status = ($expand_all || (!$expand_all && $selected)) ? "opened" : "closed";
            }
            else {
                $status = "none";
            }

            $class = $selected ? 'selected ' : '';

            if ($status == "opened") {
                $prefix = '<span class="prefix ' . $class . '">' . (!$expand_all ? '-' : '') . '</span><span class="space">&nbsp;</span>';
            }
            else if ($status == "closed") {
                $prefix = '<span class="prefix ' . $class . '">' . (!$expand_all ? '+' : '') . '</span><span class="space">&nbsp;</span>';
            }
            else if ($status == "none") {
                $prefix = '<span class="prefix">&nbsp;</span><span class="space">&nbsp;</span>';
            }
            else {
                $prefix = "";
            }

            $title = $node["name"];

            $display = $prefix . '<span class="name">' . $title . '</span>';

            $link = $link_pattern == "" ? 'javascript:' : str_replace("SUB_FOLDER_SLUG", ($node["lvl"] == 0 ? "0" : $node["slug"]), $link_pattern);

            $result .= '<li id="node_' . $node["id"] . '"';
            foreach ($this->getNodeParams() as $k => $v) {
                $node_val = $node[$k];
                if ($node[$k] instanceOf \Datetime) {
                    $node_val = $node[$k]->format('Y-m-d');
                }
                $result .= ' data_' . $k . '="' . $node_val . '"';
            }
            $result .= '>';

            $result .= '<a class="' . $class . '" href="' . $link . '">' . $display . '</a>';

            if ($type == "folder" && $status == "opened") {
                $result .= $this->_makeTree($node["__children"], $link_pattern, $path_ids, $expand_all);
            }

            $result .= '</li>';
        }

        $result .= '</ul>';

        return $result;
    }


    public function getHtmlSelect($nodes) {
        $select = array();
        $result = '<select>';

        foreach ($nodes as $node) {
            $select[] = array(
                'id'        => $node->getId(),
                'name'      => $node->getName(),
                'lvl'       => $node->getLevel(),
                '__children'  => $this->childrenHierarchy($node, false)
            );
        }

        $result .= $this->_makeSelect($select);
        $result .= '</select>';

        return $result;
    }

    private function _makeSelect($nodes) {
        $result = "";

        foreach ($nodes as $node) {
            $lvl = (int) $node["lvl"];
            $title = str_repeat("--", $lvl) . ' ' . $node["name"];
            $result .= '<option value="' . $node["id"] . '">' . $title . '</option>';

            if (is_array($node["children"]) && count($node["children"] > 0)) {
                $result .= $this->_makeSelect($node["children"]);
            }
        }

        return $result;
    }


    public function getPathIds($id) {
        $ids = array();

        $node = $this->find($id);
        if ($node) {
            $path = $this->getPath($node);
            foreach ($path as $p) {
                $ids[] = $p->getId();
            }
        }

        return $ids;
    }


    public function getChildrenIds($id, $with_parent = true) {
        $ids = array();

        if ($with_parent) {
            $ids[] = $id;
        }

        $node = $this->find($id);
        if ($node) {
            $children = $this->children($node);
            foreach ($children as $c) {
                $ids[] = $c->getId();
            }
        }

        return $ids;
    }


    public function getRoot() {
        $roots = $this->getRootNodes();
        return count($roots) > 0 ? $roots[0] : null;
    }


}
