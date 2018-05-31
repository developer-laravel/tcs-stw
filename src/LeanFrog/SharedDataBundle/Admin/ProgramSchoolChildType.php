<?php

namespace LeanFrog\SharedDataBundle\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvents;

class ProgramSchoolChildType extends AbstractType {
    /**
     * {@inheritdoc}
     */
    public function buildForm( FormBuilderInterface $builder , array $options ) {

        $choices = [
            97 => 'PreK 97',
            98 => 'PreK 98',
            99 => 'PreK',
            0 => 'K',
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
            5 => 5,
            6 => 6,
            7 => 7,
            8 => 8,
            9 => 9,
            10 => 10,
            11 => 11,
            12 => 12,
        ];

        $builder
            ->add( 'gradeLevel' , 'choice' , [
                'label' => 'Grade Level',
                'required' => true,
                'choices' => array_flip( $choices ),
                'placeholder' => 'choose an option',
            ] )

            ->add( 'school' , 'choice' , [
                'choices' => $options['linked_entities'] ,
                'multiple' => true,
                'required' => false,
                'horizontal_input_wrapper_class' => ['style'=>'display: inline-block; width: 50%; border: 1px solid green;'],
            ] );

    }

    public function configureOptions( \Symfony\Component\OptionsResolver\OptionsResolver $resolver){
         $resolver->setRequired('linked_entities');
    }
}