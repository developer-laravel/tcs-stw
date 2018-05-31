<?php

namespace IIAB\StudentTransferBundle\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SettingsSchoolListType extends AbstractType {
    /**
     * {@inheritdoc}
     */
    public function buildForm( FormBuilderInterface $builder , array $options ) {

        $builder->add( 'school' , 'choice' , [
            'choices' => $options['choices'] ,
            'required' => false,
            'horizontal_input_wrapper_class' => ['style'=>'display: inline-block; width: 50%; border: 1px solid green;'],
        ] );
        $builder->add( 'renewalOnly' , 'choice' , [
            'label' => 'Applications Accepted',
            'choices' => [
                'Allow Initial and Renewal' => 0,
                'Only Allow Renewals' => 1,
            ] ,
            'required' => false,
            'empty_data' => 0,
        ] );
    }

    public function configureOptions( \Symfony\Component\OptionsResolver\OptionsResolver $resolver)
    {
         $resolver->setRequired('choices');
    }
}