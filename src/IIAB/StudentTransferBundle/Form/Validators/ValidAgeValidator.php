<?php

namespace IIAB\StudentTransferBundle\Form\Validators;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Doctrine\ORM\EntityManager;

class ValidAgeValidator extends ConstraintValidator {

    /** @var EntityManager */
    private $emLookup;

    /**
     * Setting up all the defaults needed for the Class.
     *
     * @param EntityManager      $emLookup
     */
    function __construct( EntityManager $emLookup ) {
        $this->emLookup = $emLookup;
    }

    public function validate($value, Constraint $constraint) {

        // Get submitted form data
        $data = $this->context->getRoot()->getData();

        $openEnrollment = $this->emLookup->getRepository( 'IIABStudentTransferBundle:OpenEnrollment' )->findByDate( new \DateTime );
        $openEnrollment = ($openEnrollment ) ? $openEnrollment[0] : $this->emLookup->getRepository('IIABStudentTransferBundle:OpenEnrollment')->findOneBy( [] , ['endingDate' => 'DESC'] );

        switch( $data['currentGrade'] ){
            // case '99':
            //     $grade = 'pre-k';
            //     $cutOff = $placement->getPreKDateCutOff();
            //     break;
            case '99':
                $grade = 'kindergarten';
                $cutOff = $openEnrollment->getKindergartenDateCutOff();
                break;
            case '00':
                $grade = '1st grade';
                $cutOff = $openEnrollment->getfirstGradeDateCutOff();
                break;
            default :
                $cutOff = null;
        }

        if( isset($cutOff) && $data['dob'] > $cutOff ){

            $message = $constraint->message;
            $message = str_replace('%first_name%', $data['studentFirstName'], $message );
            $message = str_replace('%last_name%', $data['studentLastName'],  $message );
            $message = str_replace('%grade%', $grade,  $message );
            $message = str_replace('%date%', $cutOff->format('F d, Y'),  $message );

            $this->context->buildViolation( $constraint->message )
                ->setParameter( '%string%' , $message )
                ->addViolation();
        }
    }
}