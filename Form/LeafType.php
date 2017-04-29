<?php

namespace wbx\TreeBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class LeafType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('name', 'text', array(
            	'label' => "wbxtree.form.name"
            ))
        ;
    }

    public function getName() {
        return 'wbxtree_leaf';
    }
}
