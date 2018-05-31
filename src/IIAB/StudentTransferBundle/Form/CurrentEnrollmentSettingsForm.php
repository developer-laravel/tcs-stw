<?php
/**
 * Created by PhpStorm.
 * User: DerrickWales
 * Date: 4/24/15
 * Time: 10:01 AM
 */

namespace IIAB\StudentTransferBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class CurrentEnrollmentSettingsForm
 * @package IIAB\StudentTransferBundle\Form
 */
class CurrentEnrollmentSettingsForm extends AbstractType {

	public function buildForm( FormBuilderInterface $builder , array $options ) {

		$builder
			->add( 'maxCapacity' , null , [
                'label' => 'Max Capacity' ,
                'attr' => [
                    'style' => 'max-width:100px;' ,
                    'min' => 0
                ],
            ] )
			->add( 'black' , 'integer' , [ 'label' => 'Black' , 'attr' => [ 'style' => 'max-width:100px;' , 'min' => 0 ] ] )
			->add( 'white' , 'integer' , [ 'label' => 'White' , 'attr' => [ 'style' => 'max-width:100px;' , 'min' => 0 ] ] )
			->add( 'other' , 'integer' , [ 'label' => 'Other' , 'attr' => [ 'style' => 'max-width:100px;' , 'min' => 0 ] ] )
		;
	}

    public function configureOptions(\Symfony\Component\OptionsResolver\OptionsResolver $resolver){
        $resolver->setDefaults( [
            'data_class' => 'IIAB\StudentTransferBundle\Entity\CurrentEnrollmentSettings'
        ] );
    }

	public function getName() {
		return 'current_enrollment_settings';
	}
}