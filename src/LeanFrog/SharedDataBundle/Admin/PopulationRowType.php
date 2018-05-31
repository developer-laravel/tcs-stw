<?php

namespace LeanFrog\SharedDataBundle\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvents;

class PopulationRowType extends AbstractType {
    /**
     * {@inheritdoc}
     */
    public function buildForm( FormBuilderInterface $builder , array $options ) {

        $builder

            ->add( 'academicYear', 'text', [
                'label' => 'Academic Year',
                'attr' => ['readonly'=>'readonly']
            ] )

            ->add( 'updateDateTime' , 'datetime' , [
                'label' => 'Date Time',
                'required' => false,
                'attr' => ['readonly'=>'readonly'],
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd hh:mm',
            ] )

            ->add( 'Black' , 'integer' , [
                'label' => 'Black',
                'required' => true,
                'attr' => ['class'=>'sonata-inline'],
            ] )

            ->add( 'White' , 'integer' , [
                'label' => 'White',
                'required' => true,
                'attr' => ['class'=>'sonata-inline'],
            ] )

            ->add( 'Other' , 'integer' , [
                'label' => 'Other',
                'required' => true,
                'attr' => ['class'=>'sonata-inline'],
            ] )
            ->add( 'None' , 'integer' , [
                'label' => 'Not Specified',
                'required' => true,
                'attr' => ['class'=>'sonata-inline'],
            ] );

    }

}