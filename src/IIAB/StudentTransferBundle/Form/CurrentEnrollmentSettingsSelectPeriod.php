<?php
/**
 * Created by PhpStorm.
 * User: Derrick
 * Date: 4/27/15
 * Time: 10:17 AM
 */

namespace IIAB\StudentTransferBundle\Form;


use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\FormBuilderInterface;

class CurrentEnrollmentSettingsSelectPeriod {

    public function buildForm( FormBuilderInterface $builder , array $options ) {
        $builder
            ->add( 'enrollmentPeriod' , 'entity' , [
                'class' => 'IIABStudentTransferBundle:OpenEnrollment',
                'query_builder' => function( EntityRepository $er ) {
                    return $er->createQueryBuilder( 'u' )
                        ->orderBy( 'u.id' , 'ASC' );
                }
            ]
        );
    }

    public function getDefaultOptions( array $options ) {
        return array(
            'data_class' => 'IIABStudentTransferBundle:OpenEnrollment'
        );
    }

    public function getName() {
        return 'current_enrollment_settings_select_period';
    }
}