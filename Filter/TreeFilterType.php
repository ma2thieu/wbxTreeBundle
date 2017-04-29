<?php
namespace wbx\TreeBundle\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Doctrine\ORM\QueryBuilder;
use Lexik\Bundle\FormFilterBundle\Filter\Query\QueryInterface;

class TreeFilterType extends AbstractType {

    protected $ident = "q";
    protected $leaves_name = "leaves";
    protected $leaves_string_name = "leaves_string";

    public function __construct($ident, $leaves_name, $leaves_string_name) {
        $this->ident = $ident;
        $this->leaves_name = $leaves_name;
        $this->leaves_string_name = $leaves_string_name;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add($this->leaves_name, 'filter_text', array(
            'apply_filter' => function (QueryInterface $fq, $field, $values) {
                if ($values['value'] != "") {

                    $a = explode(",", $values['value']);
                    foreach ($a as $k => $v) {
                        $ids = str_replace(":", ",|", $v);

                        $fq->getQueryBuilder()
                            ->andWhere('REGEXP(' . $this->ident . '.' . $this->leaves_string_name . ', :ids' . $k . ') = 1')
                            ->setParameter('ids' . $k, $ids)
                        ;
                    }
                }
            },
            'label' => $this->leaves_name
        ));
    }


    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'csrf_protection' => false
        ));
    }


    public function getName() {
        return 'wbxtree_filter';
    }

}

