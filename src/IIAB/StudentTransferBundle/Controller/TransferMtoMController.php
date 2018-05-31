<?php

namespace IIAB\StudentTransferBundle\Controller;

use IIAB\StudentTransferBundle\Command\CheckMinorityCommand;
use IIAB\StudentTransferBundle\Command\CheckAddressCommand;
use IIAB\StudentTransferBundle\Command\GetAvailableSchoolCommand;
use IIAB\StudentTransferBundle\Command\GetZonedSchoolsCommand;
use IIAB\StudentTransferBundle\Entity\Student;
use IIAB\StudentTransferBundle\Entity\Submission;
use IIAB\StudentTransferBundle\Entity\SubmissionData;
use IIAB\StudentTransferBundle\Entity\SubmissionStatus;
use IIAB\StudentTransferBundle\Entity\Audit;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use IIAB\StudentTransferBundle\Lottery\Lottery;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Validator\Constraints\NotBlank;
use IIAB\StudentTransferBundle\Form\Constraints\ValidAge;
use IIAB\StudentTransferBundle\Form\Constraints\ValidAddress;
use IIAB\StudentTransferBundle\Controller\Traits\TransferControllerTraits;

class TransferMtoMController extends Controller {
    use TransferControllerTraits;

    /**
     * Handles the M to M Transfers
     *
     * @param Request $request
     * @param int     $step
     *
     * @Route( "/m-to-m/step/{step}" , name="stw_m2m" , requirements={ "step" = "\d+" } )
     * @return \Symfony\Component\HttpFoundation\Response;
     */
    public function mTomTransferAction( Request $request , $step ) {

        $em = $this->getDoctrine()->getManager();
        $textFields = '';

        // Custom error messages for non-HTML5 validation.
        $emptyStudent = new NotBlank();
        $emptyStudent->message = 'Please provide a valid State ID';
        $cannotBeBlank = new NotBlank();
        $cannotBeBlank->message = 'Cannot be blank';

        $lastFormData = $request->getSession()->has( 'stw-formData' );
        if( $lastFormData !== false )
            $lastFormData = unserialize( base64_decode( $request->getSession()->get( 'stw-formData' ) ) );

        //Someone is trying to jump ahead!! Redirect back to step 1. If both step is greater than 1 and session data is empty, send back to first step.
        if( $step > 1 && $lastFormData === false ) {
            $request->getSession()->remove( 'stw-formData' );
            $request->getSession()->remove( 'stw-formData-zoned' );
            return $this->redirect( $this->generateUrl( $request->get('_route') , array( 'step' => 1 ) ) );
        }

        $currentSchoolYearText = $em->getRepository('IIABStudentTransferBundle:Settings')->findOneBy( array(
            'settingName' => 'current school year'
        ) );
        $nextSchoolYearText = $em->getRepository('IIABStudentTransferBundle:Settings')->findOneBy( array(
            'settingName' => 'next school year'
        ) );

        $openEnrollment = !empty( $lastFormData['enrollment'] ) ? $lastFormData['enrollment'] : $this->setOpenEnrollmentPeriodForForm( $request );
        if( !empty( $openEnrollment ) && ( !is_object( $openEnrollment ) || get_class( $openEnrollment ) != 'IIAB\StudentTransferBundle\Entity\OpenEnrollment' ) ){
            $openEnrollment = $em->getRepository('IIABStudentTransferBundle:OpenEnrollment')->find( $openEnrollment );
        }

        switch( $step ) {
            case 1:
            default:
                $enrollment = $this->setOpenEnrollmentPeriodForForm( $request );
                if( $enrollment == null ) {
                    if( isset( $lastFormData['enrollment'] ) && !empty( $lastFormData['enrollment'] ) ) {
                        $enrollment = $em->getRepository('IIABStudentTransferBundle:OpenEnrollment')->find( $lastFormData['enrollment'] );
                    } else {
                        return $this->redirect( $this->generateUrl( 'stw_index' ) );
                    }
                }

                /** Form isn't active, break out */
                if( ! $enrollment->isFormAvailable( $this->getRouteFormId() ) ) {
                    return $this->redirect( $this->generateUrl( 'stw_index' ) );
                }

                $form = $this->createFormBuilder()
                    ->setAction( $this->generateUrl( $request->get('_route') , array( 'step' => $step , 'enrollment' => $enrollment->getId() ) ) )
                    ->add( 'last-step' , 'hidden' , array(
                        'attr' => array( 'value' => $step )
                    ) )
                    ->add( 'enrollment' , 'hidden' , array(
                        'attr' => array( 'value' => $enrollment->getId() )
                    ) )
                    ->add( 'studentStatus' , 'choice' , array(
                        'label' => $this->get('translator')->trans( 'forms.status' , array() , 'IIABStudentTransferBundle' ),
                        'choices' => array (
                            'currentStudent'                => $this->get('translator')->trans( 'forms.hsvStudent' , array() , 'IIABStudentTransferBundle' ),
                            'currentKindergartenStudent'    => $this->get('translator')->trans( 'forms.new' , array() , 'IIABStudentTransferBundle' ) )
                    ) )
                    ->add( 'submitAndNext' , 'submit' , array(
                        'label' => $this->get('translator')->trans( 'forms.submitNext' , array() , 'IIABStudentTransferBundle' )
                    ) )
                    ->getForm();
                $form->handleRequest( $request );

                if( $form->isValid() ) {

                    $formData = $form->getData();

                    $request->getSession()->set( 'stw-formData' , base64_encode( serialize( $formData ) ) );

                    $step++;
                    return $this->redirect( $this->generateUrl( $request->get('_route') , array( 'step' => $step ) ) );
                }
                break;
            case 2:

                /**
                 * No Session Form data, means they just landed on the website without submitting a form.
                 * Redirect back to step 1 of the form.
                 */
                if( $lastFormData === false ) {
                    $request->getSession()->getFlashBag()->add( 'error' , 'You cannot skip to another page. Please start over and submit the form again.' );
                    return $this->redirect( $this->generateUrl( 'stw_index' , array( 'step' => 1 ) ) );
                }

                if( $lastFormData['studentStatus'] == 'currentStudent' ) {

                    $form = $this->createFormBuilder()
                        ->setAction( $this->generateUrl( $request->get('_route') , array( 'step' => $step ) ) )
                        ->add( 'last-step' , 'hidden' , array(
                            'attr' => array( 'value' => $step )
                        ) )
                        ->add( 'enrollment' , 'hidden' , array(
                            'data' => array( 'value' => $lastFormData['enrollment'] )
                        ) )
                        ->add( 'studentStatus' , 'hidden' , array(
                            'attr' => array( 'value' => $lastFormData['studentStatus'] )
                        ) )
                        ->add( 'studentID' , 'text' , array(
                            'label' => $this->get( 'translator' )->trans( 'forms.studentID' , array() , 'IIABStudentTransferBundle' ),
                            'constraints' => $cannotBeBlank
                        ) )
                        ->add( 'dob' , 'birthday' , array(
                            'label' => $this->get( 'translator' )->trans( 'forms.dob' , array() , 'IIABStudentTransferBundle' ),
                            'years' => range( date( 'Y' , strtotime( '-20 years' ) ) , date( 'Y' ) ),
                            'constraints' => $cannotBeBlank
                        ) )
                        ->add( 'primTelephone' , 'text' , array (
                            'label' => $this->get('translator')->trans( 'forms.primTelephone' , array() , 'IIABStudentTransferBundle' ),
                            'data' => isset( $lastFormData['primTelephone'] ) ? $lastFormData['primTelephone'] : '',
                            'constraints' => $cannotBeBlank
                        ) )

                        ->add( 'secTelephone' , 'text' , array(
                            'label' => $this->get( 'translator' )->trans( 'forms.secTelephone' , array() , 'IIABStudentTransferBundle' ),
                            'data' => isset( $lastFormData['secTelephone'] ) ? $lastFormData['secTelephone'] : '',
                            'required' => false
                        ) )

                        ->add( 'email', 'repeated', array(
                            'label' => $this->get( 'translator' )->trans( 'forms.email' , array() , 'IIABStudentTransferBundle' ),
                            'type' => 'text',
                            'required' => false,
                            'invalid_message' => 'The email addresses must match',
                            'first_options' => array ( 'label' => $this->get( 'translator' )->trans( 'forms.email' , array() , 'IIABStudentTransferBundle' ) ),
                            'second_options' => array ( 'label' => $this->get( 'translator' )->trans( 'forms.emailRepeat' , array() , 'IIABStudentTransferBundle' ) )
                        ) )
                        ->add( 'submitAndNext' , 'submit' , array(
                            'label' => $this->get( 'translator' )->trans( 'forms.submitNext' , array() , 'IIABStudentTransferBundle' ),
                            'attr' => array(
                                'class' => 'button button-action'
                            )
                        ) )
                        ->getForm();
                } else {

                    $grades = array( '99' => 'Pre-K' , '00' => 'K');
                    foreach( range( 1 , 12 ) as $grade ) {
                        $newGrade = sprintf( '%1$02d' , $grade );
                        $grades[$newGrade] = $newGrade;
                    }


                    $form = $this->createFormBuilder()
                        ->setAction( $this->generateUrl( $request->get('_route') , array( 'step' => $step ) ) )
                        ->add( 'last-step' , 'hidden' , array(
                            'attr' => array( 'value' => $step )
                        ) )
                        ->add( 'studentStatus' , 'hidden' , array(
                            'attr' => array( 'value' => $lastFormData['studentStatus'] )
                        ) )
                        ->add( 'enrollment' , 'hidden' , array(
                            'attr' => array( 'value' => $lastFormData['enrollment'] )
                        ) )
                        ->add( 'studentFirstName' , 'text' , array(
                            'label' => $this->get('translator')->trans( 'forms.studentFirstName' , array() , 'IIABStudentTransferBundle' ) ,
                            'data' => isset( $lastFormData['studentFirstName'] ) ? $lastFormData['studentFirstName'] : '',
                            'constraints' => $cannotBeBlank
                        ) )
                        ->add( 'studentLastName' , 'text' , array(
                            'label' => $this->get('translator')->trans( 'forms.studentLastName' , array() , 'IIABStudentTransferBundle' ) ,
                            'data' => isset( $lastFormData['studentLastName'] ) ? $lastFormData['studentLastName'] : '',
                            'constraints' => $cannotBeBlank
                        ) )
                        ->add( 'address' , 'text' , array(
                            'label' => $this->get('translator')->trans( 'forms.address' , array() , 'IIABStudentTransferBundle' ) ,
                            'data' => isset( $lastFormData['address'] ) ? $lastFormData['address'] : '',
                            'constraints' => array(
                                new NotBlank(),
                                new ValidAddress()
                            )
                        ) )
                        ->add( 'city' , 'text' , array(
                            'label' => $this->get('translator')->trans( 'forms.city' , array() , 'IIABStudentTransferBundle' ) ,
                            'data' => isset( $lastFormData['city'] ) ? $lastFormData['city'] : '',
                            'constraints' => $cannotBeBlank
                        ) )
                        ->add( 'state' , 'text' , array(
                            'label' => $this->get('translator')->trans( 'forms.state' , array() , 'IIABStudentTransferBundle' ) ,
                            'data' => isset( $lastFormData['state'] ) ? $lastFormData['state'] : '',
                            'constraints' => $cannotBeBlank
                        ) )
                        ->add( 'zip' , 'text' , array(
                            'label' => $this->get('translator')->trans( 'forms.zip' , array() , 'IIABStudentTransferBundle' ) ,
                            'data' => isset( $lastFormData['zip'] ) ? $lastFormData['zip'] : '',
                            'constraints' => $cannotBeBlank
                        ) )

                        ->add( 'primTelephone' , 'text' , array (
                            'label' => $this->get('translator')->trans( 'forms.primTelephone' , array() , 'IIABStudentTransferBundle' ),
                            'data' => isset( $lastFormData['primTelephone'] ) ? $lastFormData['primTelephone'] : '',
                            'constraints' => $cannotBeBlank
                        ) )

                        ->add( 'secTelephone' , 'text' , array(
                            'label' => $this->get( 'translator' )->trans( 'forms.secTelephone' , array() , 'IIABStudentTransferBundle' ),
                            'data' => isset( $lastFormData['secTelephone'] ) ? $lastFormData['secTelephone'] : '',
                            'required' => false
                        ) )

                        ->add( 'email', 'repeated', array(
                            'label' => $this->get( 'translator' )->trans( 'forms.email' , array() , 'IIABStudentTransferBundle' ),
                            'type' => 'text',
                            'required' => false,
                            'invalid_message' => 'The email addresses must match',
                            'first_options' => array ( 'label' => $this->get( 'translator' )->trans( 'forms.email' , array() , 'IIABStudentTransferBundle' ) ),
                            'second_options' => array ( 'label' => $this->get( 'translator' )->trans( 'forms.emailRepeat' , array() , 'IIABStudentTransferBundle' ) )
                        ) )

                        ->add( 'dob' , 'birthday' , array(
                            'label' => $this->get('translator')->trans( 'forms.dob' , array() , 'IIABStudentTransferBundle' ) ,
                            'data' => isset( $lastFormData['dob'] ) ? new \DateTime( $lastFormData['dob']->date ) : null,
                            'years' => range( date( 'Y' , strtotime( '-20 years' ) ) , date( 'Y' ) ),
                            'constraints' => array(
                                new ValidAge()
                            )
                        ) )
                        ->add( 'race' , 'choice' , array(
                            'label' => $this->get('translator')->trans( 'forms.race' , array() , 'IIABStudentTransferBundle' ) ,
                            'choices' => $this->getRaceOptions() ,
                            'data' => isset( $lastFormData['race'] ) ? $lastFormData['race'] : '',
                            'constraints' => $cannotBeBlank
                        ) )

                        ->add( 'currentSchool' , 'text' , array(
                            'label' => $this->get('translator')->trans( 'forms.currentSchool' , array() , 'IIABStudentTransferBundle' ) ,
                            'data' => isset( $lastFormData['currentSchool'] ) ? $lastFormData['currentSchool'] : ''
                        ) )

                        ->add( 'currentGrade' , 'choice' , array(
                            'label' => $this->get('translator')->trans( 'forms.currentGrade' , array() , 'IIABStudentTransferBundle' ) ,
                            'choices' => $grades,
                            'data' => isset( $lastFormData['currentGrade'] ) ? $lastFormData['currentGrade'] : '',
                        ) )

                        ->add( 'submitAndNext' , 'submit' , array(
                            'label' => $this->get( 'translator' )->trans( 'forms.submitNext' , array() , 'IIABStudentTransferBundle' ),
                            'attr' => array(
                                'class' => 'button button-action'
                            )
                        ) )
                        ->getForm()
                    ;
                }

                $form->handleRequest( $request );

                if( $form->isValid() ) {
                    $formData = $form->getData();

                    if( isset( $formData['studentID'] ) ) {
                        //Force Upper Case on the studentID
                        $formData['studentID'] = strtoupper( $formData['studentID'] );
                        $student = $em->getRepository( 'IIABStudentTransferBundle:Student' )->findOneBy( array( 'studentID' => $formData['studentID'] , 'dob' => $formData['dob']->format( 'n/j/Y' ) ) );
                        if( $student != NULL ) {
                            $formData['primTelephone'] = preg_replace( '/\D+/' , '' , $formData['primTelephone'] );
                            $student->setPrimTelephone( $formData['primTelephone'] );
                            //var_dump($student);die;
                            if( isset ( $formData['secTelephone'] ) ) {
                                $formData['secTelephone'] = preg_replace( '/\D+/' , '' , $formData['secTelephone'] );
                                $student->setSecTelephone( $formData['secTelephone'] );
                            }
                            if( isset ( $formData['email'] ) ) {
                                $student->setEmail( $formData['email'] );
                            }
                        }
                    } else {
                        //Create a new student record because the personnel form entering in is not found.
                        $student = new Student();
                        $student->setAddress( $formData['address'] );
                        $student->setCity( $formData['city'] );
                        $student->setDob( $formData['dob']->format( 'n/j/Y' ) );
                        $student->setFirstName( $formData['studentFirstName'] );
                        $student->setLastName( $formData['studentLastName'] );
                        $student->setGrade( $formData['currentGrade'] );
                        if ( $formData['currentSchool'] != NULL) {
                            $student->setSchool( $formData['currentSchool'] );
                        }
                        $student->setRace( $formData['race'] );

                        // Temporary ID is required to traverse steps without losing session data.
                        $student->setStudentID( 'TS' . strtotime( 'now' ) );
                        $student->setZip( $formData['zip'] );

                        $formData['primTelephone'] = preg_replace( '/\D+/' , '' , $formData['primTelephone'] );
                        $student->setPrimTelephone( $formData['primTelephone'] );
                        if ( isset ( $formData['secTelephone'] ) ) {
                            $formData['secTelephone'] = preg_replace( '/\D+/' , '' , $formData['secTelephone'] );
                            $student->setSecTelephone( $formData['secTelephone'] );
                        }
                        if ( isset ( $formData['email'] ) )
                            $student->setEmail( $formData['email'] );
                        $em->persist( $student );
                        $em->flush();
                        $formData['studentID'] = $student->getStudentID();
                    }


                    if( $student == NULL ) {
                        $request->getSession()->getFlashBag()->add( 'error' , $this->get('translator')->trans( 'errors.noStudentFound' , array() , 'IIABStudentTransferBundle' ) );
                    } elseif ( $student->getGrade() != 99 && $student->getSchool() == NULL ) {
                        // If the student is not in position to enter Kindergarten, require that a current school be set.
                        $request->getSession()->getFlashBag()->add( 'error' , $this->get( 'translator' )->trans( 'errors.noCurrentSchool' , array() , 'IIABStudentTransferBundle' ) );
                    } else {
                        $request->getSession()->set( 'stw-formData' , base64_encode( serialize( $formData ) ) );
                        $step++;
                    }


                    return $this->redirect( $this->generateUrl( $request->get('_route') , array( 'step' => $step ) ) );
                }
                break;

            case 3:
                $student = $em->getRepository( 'IIABStudentTransferBundle:Student' )->findOneBy( array( 'studentID' => $lastFormData['studentID'] , 'dob' => $lastFormData['dob']->format( 'n/j/Y' ) ) );

                // Searches for if student has multiple records in iNow. If so, change racial status to multi-race.
                $duplicate = $em->getRepository( 'IIABStudentTransferBundle:Student' )->findBy( array( 'studentID' => $lastFormData['studentID'] ) );
                //if ( count ( $duplicate ) > 1 ) { $student->setRace( 'Multi-Race - Two or More Races' ); }

                $student->setPrimTelephone( $lastFormData['primTelephone'] );
                if ( isset ( $lastFormData['secTelephone'] ) )
                    $student->setSecTelephone( $lastFormData['secTelephone'] );
                if ( isset ( $lastFormData['email'] ) )
                    $student->setEmail( $lastFormData['email'] );

                $form = $this->createFormBuilder()
                    ->setAction( $this->generateUrl( $request->get('_route') , array( 'step' => $step ) ) )
                    ->add( 'last-step' , 'hidden' , array(
                        'attr' => array( 'value' => $step )
                    ) )
                    ->add( 'enrollment' , 'hidden' , array(
                        'attr' => array( 'value' => $lastFormData['enrollment'] )
                    ) );
                if( $lastFormData['studentStatus'] != 'currentKindergartenStudent' ) {
                    $form = $form->add( 'wrongInfo' , 'submit' , array(
                        'label' => $this->container->get( 'translator' )->trans( 'forms.wrongInfo' , array() , 'IIABStudentTransferBundle' ) ,
                        'attr' => array(
                            'class' => 'button button-caution'
                        )
                    ) );
                }
                $form = $form->add( 'correctInfo' , 'submit' , array(
                        'label' => $this->container->get( 'translator' )->trans( 'forms.correctInfo' , array() , 'IIABStudentTransferBundle' ),
                        'attr' => array(
                            'class' => 'button button-action'
                        )
                    ) )
                    ->getForm();
                $form->handleRequest( $request );

                //Do not plus one 99 (PreSchools) because it will be greater than 12
                if( $student->getGrade() != 99 && $student->getGrade()+1 > 12 ) {
                    $form->remove( 'correctInfo' );
                    if( $lastFormData['studentStatus'] != 'currentKindergartenStudent' ) {
                        $form->remove( 'wrongInfo' );
                    }
                    $request->getSession()->getFlashBag()->add( 'notice' , $this->get('translator')->trans( 'errors.graduatingStudent' , array() , 'IIABStudentTransferBundle' ) );
                }

                $textFields = array();

                // If the student ID is temporary, do not show it or it's label. Else, display the Student ID number.
                if ( substr( $student->getStudentID() , 0 , 2 ) != "TS" ) { $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.studentID' , array() , 'IIABStudentTransferBundle' ) , 'value' => $student->getStudentID() ); }
                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.name' , array() , 'IIABStudentTransferBundle' ) , 'value' => $student->getFirstName() . ' ' . $student->getLastName() );
                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.dob' , array() , 'IIABStudentTransferBundle' ) , 'value' => $student->getDob() );

                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.primTelephone' , array() , 'IIABStudentTransferBundle' ) , 'value' => $student->getPrimTelephone() );
                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.secTelephone' , array() , 'IIABStudentTransferBundle' ) , 'value' => $student->getSecTelephone() );
                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.email' , array() , 'IIABStudentTransferBundle' ) , 'value' => $student->getEmail() );

                $race = $em->getRepository( 'IIABStudentTransferBundle:Race' )->find( $student->getRace() );
                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.race' , array() , 'IIABStudentTransferBundle' ) , 'value' => $race->getRace() );
                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.currentGrade' , array( '%current%' => '('.$currentSchoolYearText->getSettingValue().')' ) , 'IIABStudentTransferBundle' ) , 'value' => $student->getGrade() );
                if( $student->getGrade()+1 <= 12 ) {
                    $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.nextGrade' , array( '%next%' => '('.$nextSchoolYearText->getSettingValue().')' ) , 'IIABStudentTransferBundle' ) , 'value' => $student->getGrade()+1 );
                }
                if( $student->getGrade() == 99 ) {
                    $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.nextGrade' , array( '%next%' => '('.$nextSchoolYearText->getSettingValue().')' ) , 'IIABStudentTransferBundle' ) , 'value' => '00' );
                }
                if ( $student->getSchool() != NULL ) {
                    $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.currentSchool' , array() , 'IIABStudentTransferBundle' ) , 'value' => $student->getSchool() );
                }
                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.address' , array() , 'IIABStudentTransferBundle' ) , 'value' => $student->getAddress() . '<br />' . $student->getCity() . '<br />' . $student->getZip() );
                //$textFields[] = array( 'label' => $this->container->get( 'translator' )->trans( 'forms.lastName' , array() , 'IIABStudentTransferBundle' ) , 'value' =>  );
                //$textFields[] = array( 'label' => $this->container->get( 'translator' )->trans( 'forms.city' , array() , 'IIABStudentTransferBundle' ) , 'value' =>  );
                //$textFields[] = array( 'label' => $this->container->get( 'translator' )->trans( 'forms.zip' , array() , 'IIABStudentTransferBundle' ) , 'value' =>  );


                if( $form->isValid() ) {
                    $formData = $form->getData();

                    //Perform looking on Student ID HERE.
                    //$em = $this->getDoctrine()->getManager();
                    //$student = $em->getRepository( 'IIABStudentTransferBundle:Student' )->findOneBy( array( 'studentID' => $formData['studentID'] , 'dob' => $formData['dob']->format( 'Y-m-d' ) ) );
                    $formData = array_merge( $lastFormData , $formData );

                    if( $lastFormData['studentStatus'] != 'currentKindergartenStudent' ) {
                        if( $form->get( 'wrongInfo' )->isClicked() ) {
                            //User clicked the button because the information is incorrect.
                            $this->recordAudit( 18 , 0 , $student->getStudentID(), $request );
                            return $this->redirect( $this->generateUrl( 'stw_incorrect_info' ) );
                        }
                    }

                    if( $student == NULL ) {
                        $request->getSession()->getFlashBag()->add( 'error' , $this->get('translator')->trans( 'errors.noStudentFound' , array() , 'IIABStudentTransferBundle' ) );
                    } else {
                        $request->getSession()->set( 'stw-formData' , base64_encode( serialize( $formData ) ) );
                        $step++;
                    }
                    // Get whether the student's submission is currently on an M2M transfer. If so, redirect to step 5, else continue as normal.

                    $studentIsDuplicateSubmission = $em->getRepository( 'IIABStudentTransferBundle:Submission' )->findOneBy( [
                        'firstName' => $student->getFirstName() ,
                        'lastName' => $student->getLastName() ,
                        'dob' => $formData['dob']->format( 'n/j/Y' ) ,
                        'grade' => $student->getGrade() ,
                        'enrollmentPeriod' => $formData['enrollment']
                    ] );

                    if($studentIsDuplicateSubmission){
                        return $this->redirect( $this->generateUrl( 'stw_already_submitted' ) );
                    }

                    $studentIsCurrentlyOnM2MTransfer = $em->getRepository( 'IIABStudentTransferBundle:Expiration' )->findOneBy( array( 'studentID' => $lastFormData['studentID'] , 'openEnrollment' => $formData['enrollment'] ) );
                    if ( $studentIsCurrentlyOnM2MTransfer) {
                        return $this->redirect( $this->generateUrl( $request->get('_route') , array( 'step' => 5 ) ) );
                    } else {
                        return $this->redirect( $this->generateUrl( $request->get('_route') , array( 'step' => $step ) ) );
                    }
                }
                break;

            case 4:
                $student = $em->getRepository( 'IIABStudentTransferBundle:Student' )->findOneBy( array( 'studentID' => $lastFormData['studentID'] , 'dob' => $lastFormData['dob']->format( 'n/j/Y' ) ) );
                $student->setPrimTelephone( $lastFormData['primTelephone'] );
                if ( isset ( $lastFormData['secTelephone'] ) )
                    $student->setSecTelephone( $lastFormData['secTelephone'] );
                if ( isset ( $lastFormData['email'] ) )
                    $student->setEmail( $lastFormData['email'] );

                $openEnrollments = $this->getOpenEnrollmentPeriod( $lastFormData['enrollment'] );
                $checkMinorityCommand = new CheckMinorityCommand( );
                $checkMinorityCommand->setContainer( $this->container );

                $schools = $checkMinorityCommand->checkMinorityStatus( $student , false , $openEnrollments->getId() );

                $zonedSchools = unserialize( base64_decode( $request->getSession()->get( 'stw-formData-zoned' ) ) );

                $zonedOutput = array();
                if( is_array( $zonedSchools ) ) {
                    foreach( $zonedSchools as $type => $name ) {
                        $zonedOutput[] = $name;
                    }
                }

                /*
                 * If schools, provide drop down boxes. Also, remove choice if option one is selected.
                 * This will keep the end user from selecting the same school twice.
                 *
                 * Then enter in selections into the database and store all the student information
                 * Also store zoning information.
                 *
                 * Then return confirmation number for output.
                 */

                if( $student->getGrade() != 99 && $student->getGrade()+1 > 12 ) {
                    $request->getSession()->getFlashBag()->add( 'notice' , $this->get('translator')->trans( 'errors.graduatingStudent' , array() , 'IIABStudentTransferBundle' ) );
                }

                $textFields = array();
                // If student ID is temporary, do not show it or its label. Else, display the Student ID and label.
                if ( substr ( $student->getStudentID() , 0 , 2 ) != "TS" ) {
                    $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.studentID' , array() , 'IIABStudentTransferBundle' ) , 'value' => $student->getStudentID() );
                }
                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.name' , array() , 'IIABStudentTransferBundle' ) , 'value' => $student->getFirstName() . ' ' . $student->getLastName() );
                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.dob' , array() , 'IIABStudentTransferBundle' ) , 'value' => $student->getDob() );
                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.race' , array() , 'IIABStudentTransferBundle' ) , 'value' => $student->getRace() );

                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.primTelephone' , array() , 'IIABStudentTransferBundle' ) , 'value' => $student->getPrimTelephone() );
                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.secTelephone' , array() , 'IIABStudentTransferBundle' ) , 'value' => $student->getSecTelephone() );
                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.email' , array() , 'IIABStudentTransferBundle' ) , 'value' => $student->getEmail() );

                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.currentGrade' , array( '%current%' => '('.$currentSchoolYearText->getSettingValue().')' ) , 'IIABStudentTransferBundle' ) , 'value' => $student->getGrade() );
                if ( $student->getSchool() != NULL ) {
                    $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.currentSchool' , array() , 'IIABStudentTransferBundle' ) , 'value' => $student->getSchool() );
                }
                if( $student->getGrade()+1 <= 12 ) {
                    $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.nextGrade' , array( '%next%' => '('.$nextSchoolYearText->getSettingValue().')' ) , 'IIABStudentTransferBundle' ) , 'value' => $student->getGrade()+1 );
                }
                if( $student->getGrade() == 99 ) {
                    $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.nextGrade' , array( '%next%' => '('.$nextSchoolYearText->getSettingValue().')' ) , 'IIABStudentTransferBundle' ) , 'value' => '00' );
                }
                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.address' , array() , 'IIABStudentTransferBundle' ) , 'value' => $student->getAddress() . '<br />' . $student->getCity() . '<br />' . $student->getZip() );
                if( $zonedSchools )
                    $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.zonedSchools' , array() , 'IIABStudentTransferBundle' ) , 'value' => implode( '<br />' , $zonedOutput ) );
                //$textFields[] = array( 'label' => $this->container->get( 'translator' )->trans( 'forms.lastName' , array() , 'IIABStudentTransferBundle' ) , 'value' =>  );
                //$textFields[] = array( 'label' => $this->container->get( 'translator' )->trans( 'forms.city' , array() , 'IIABStudentTransferBundle' ) , 'value' =>  );
                //$textFields[] = array( 'label' => $this->container->get( 'translator' )->trans( 'forms.zip' , array() , 'IIABStudentTransferBundle' ) , 'value' =>  );


                if( $schools ) {
                    //if schools found, show form

                    $form = $this->createFormBuilder()
                        ->setAction( $this->generateUrl( $request->get('_route') , array( 'step' => $step ) ) )
                        ->add( 'last-step' , 'hidden' , array(
                            'attr' => array( 'value' => $step )
                        ) )
                        ->add( 'enrollment' , 'hidden' , array(
                            'attr' => array( 'value' => $lastFormData['enrollment'] )
                        ) )
                        ->add( 'choice1' , 'choice' , array(
                            'choices' => $schools,
                            'expanded' => false,
                            'required' => false,
                            'empty_data' => $this->get( 'translator' )->trans( 'forms.selectOption' , array() , 'IIABStudentTransferBundle' ),
                            'label' => $this->get( 'translator' )->trans( 'forms.choice1' , array() , 'IIABStudentTransferBundle' ),
                        ) )
                        ->add( 'choice2' , 'choice' , array(
                            'choices' => $schools,
                            'expanded' => false,
                            'required' => false,
                            'empty_data' => $this->get( 'translator' )->trans( 'forms.selectOption' , array() , 'IIABStudentTransferBundle' ),
                            'label' => $this->get( 'translator' )->trans( 'forms.choice2' , array() , 'IIABStudentTransferBundle' ),
                        ) )
                        ->add( 'choice3' , 'choice' , array(
                            'choices' => array( '-1' => $this->get( 'translator' )->trans( 'forms.noOption' , array() , 'IIABStudentTransferBundle' ) ),
                            'expanded' => true,
                            'multiple'  => true,
                            'required' => false,
                            'label' => $this->get( 'translator' )->trans( 'forms.choice3' , array() , 'IIABStudentTransferBundle' )
                        ) )
                        ->add( 'proceedOption' , 'submit' , array(
                            'label' => $this->container->get( 'translator' )->trans( 'forms.selectedSchools' , array() , 'IIABStudentTransferBundle' ),
                            'attr' => array(
                                'class' => 'button button-action' )
                        ) )
                        ->getForm();
                } else {
                    // If no schools with available slots are found, call a notice to inform the end-user.
                    $form = $this->createFormBuilder()
                        ->setAction( $this->generateUrl( $request->get('_route') , array( 'step' => $step ) ) )
                        ->add( 'last-step' , 'hidden' , array(
                            'attr' => array( 'value' => $step )
                        ) )
                        ->getForm();
                }
                $form->handleRequest( $request );
                if( $form->isValid() ) {
                    $formData = $form->getData();
                    /*
                     * If all three choices are not set ( All null, false, or empty ), do not allow user to proceed.
                     * Else if withdraw is not checked but only one school is selected, do not allow user to proceed.
                     */
                    if ( empty( $formData['choice3'] ) && ( $formData['choice1'] == NULL ) && ( $formData['choice2'] == NULL) ) {
                        //If nothing is selection, error out with no Selectionr or Withdraw message.
                        $request->getSession()->getFlashBag()->add( 'error' , $this->get('translator')->trans( 'errors.noSelectionOrWithdraw' , array() , 'IIABStudentTransferBundle' ) );
                        return $this->redirect( $this->generateURL( $request->get('_route') , array( 'step' => $step ) ) );
                    } elseif ( empty( $formData['choice3'] ) && ( ( $formData['choice1'] == NULL ) ) ) {
                        //No selection if Not Withdraw or First Choice is null.
                        //Removed the no Both Schools options because we are not REQUIRED to select both.
                        $request->getSession()->getFlashBag()->add( 'error' , $this->get('translator')->trans( 'errors.noSelectionOrWithdraw' , array() , 'IIABStudentTransferBundle' ) );
                        return $this->redirect( $this->generateURL( $request->get('_route') , array( 'step' => $step ) ) );
                    }

                    $student = $em->getRepository( 'IIABStudentTransferBundle:Student' )->findOneBy( array( 'studentID' => $lastFormData['studentID'] , 'dob' => $lastFormData['dob']->format( 'n/j/Y' ) ) );
                    $openEnrollments = $this->getOpenEnrollmentPeriod( $lastFormData['enrollment'] );
                    if( $openEnrollments != null && empty( $formData['choice3'] ) ) {
                        $lotteryNumber = new Lottery();
                        $lotteryNumber = $lotteryNumber->getLotteryNumber( $this->getDoctrine() );
                        $submissionsStatus = $em->getRepository( 'IIABStudentTransferBundle:SubmissionStatus' )->find( 1 );

                        if( $formData['choice1'] != null ) {
                            $firstChoice = $em->getRepository( 'IIABStudentTransferBundle:ADM' )->find( $formData['choice1'] );
                        } else {
                            $firstChoice = null;
                        }
                        if( $formData['choice2'] != null ) {
                            $secondChoice = $em->getRepository( 'IIABStudentTransferBundle:ADM' )->find( $formData['choice2'] );
                        } else {
                            $secondChoice = null;
                        }
                        $form = $em->getRepository( 'IIABStudentTransferBundle:Form' )->findOneBy( array(
                                'route' => $request->get('_route')
                        ) );

                        /*
                         * TODO: Check to see if the student ID has already been awarded a slot and used it.
                         * Before allowing the new submissions.
                         */
                        $submissions = $em->getRepository('IIABStudentTransferBundle:Submission')->createQueryBuilder('s')
                            ->where( 's.studentID = :ID' )
                            ->andWhere( 's.submissionStatus != 5' ) //Look for any other NOT denied Applications!
                            ->andWhere( 's.submissionStatus != 4' ) //Look for any other NOT declined Applications!
                            ->andWhere( 's.submissionStatus != 6' ) //Look for any other NOT overwritten Applications!
                            ->andWhere( 's.enrollmentPeriod = :enrollment' )
                            ->setParameter( ':ID' , $student->getStudentID() )
                            ->setParameter( ':enrollment', $openEnrollments->getId() )
                            ->getQuery()
                            ->getArrayResult()
                        ;


                        if( count( $submissions ) == 0 ) {
                            $submittedDateTime = new \DateTime();
                            $submission = new Submission();
                            $submission->setSubmissionDateTime( $submittedDateTime );
                            $submission->setEnrollmentPeriod( $openEnrollments );
                            if( $openEnrollments->getAfterLotteryBeginningDate() <= $submittedDateTime || $submittedDateTime >= $openEnrollments->getAfterLotteryEndingDate() ) {
                                $submission->setAfterLotterySubmission( true );
                            }

                            $submission->setFormID( $form );
                            $submission->setLotteryNumber( $lotteryNumber );
                            if( $firstChoice != null ) {
                                $submission->setFirstChoice( $firstChoice );
                            }
                            if( $secondChoice != null && $firstChoice != $secondChoice ) {
                                $submission->setSecondChoice( $secondChoice );
                            }

                            $race = $em->getRepository( 'IIABStudentTransferBundle:Race' )->findOneBy( array(
                                'race' => $student->getRace(),
                            ) );

                            // If our Student ID is a temporary ID, empty it so that it is not stored in the DB.
                            ( substr( $student->getStudentID() , 0 , 2 ) == "TS" ) ? $submission->setStudentID( '' ) : $submission->setStudentID( $student->getStudentID() );

                            $submission->setLastName( $student->getLastName() );
                            $submission->setFirstName( $student->getFirstName() );
                            $submission->setDob( $student->getDob() );
                            $submission->setAddress( $student->getAddress() );
                            $submission->setCity( $student->getCity() );
                            $submission->setZip( $student->getZip() );
                            $submission->setGrade( $student->getGrade() );
                            $submission->setRace( $race );
                            $submission->setPrimTelephone( $student->getPrimTelephone() );
                            $submission->setSecTelephone( $student->getSecTelephone() );
                            $submission->setEmail( $student->getEmail() );
                            $submission->setCurrentSchool( $student->getSchool() );

                            $submission->setSubmissionStatus( $submissionsStatus );
                            $submission->setHsvZonedSchools( unserialize( base64_decode( $request->getSession()->get( 'stw-formData-zoned' ) ) ) );
                            //var_dump($submission);die;
                            $em->persist( $submission );
                            $em->flush();
                            $confirmNumber = sprintf( '%s-%s-%d' , $form->getFormConfirmation() , $openEnrollments->getConfirmationStyle() , $submission->getId() );
                            $submission->setConfirmationNumber( $confirmNumber );

                            $em->persist( $submission );
                            $em->flush();
                            $em->clear();

                            $confirmation = array( 'confirmation' => $confirmNumber );

                            $this->get('stw.email')->sendConfirmationEmail( $submission );

                            $this->recordAudit( 1 , $submission->getId() , $student->getStudentID(), $request );

                            //Erase all other session data and just display the confirmation number.
                            $request->getSession()->set( 'stw-formData' , base64_encode( serialize( $confirmation ) ) );

                            //Send to successful page. ConfirmationNumber is set in the sessions.
                            return $this->redirect( $this->generateUrl( 'stw_success' ) );

                        }

                        $this->recordAudit( 21 , 0 , $student->getStudentID(), $request );
                        return $this->redirect( $this->generateUrl( 'stw_already_submitted' ) );

                    } else {
                        //No openEnrollments or they checked the box to not select a school.
                        $this->recordAudit( 19 , 0 , $student->getStudentID(), $request );
                        return $this->redirect( $this->generateUrl( 'stw_no_selection' ) );
                    }
                }
                break;

            case 5:
                $studentsExpirationInfo = $em->getRepository( 'IIABStudentTransferBundle:Expiration' )->findOneBy( array( 'studentID' => $lastFormData['studentID'] , 'openEnrollment' => $lastFormData['enrollment'] ) );
                if ( !empty( $studentsExpirationInfo ) && ( $studentsExpirationInfo->getExpiring() ) ) {
                    $form = $this->createFormBuilder()
                        ->setAction( $this->generateUrl( $request->get('_route') , array( 'step' => 5 ) ) )
                        ->setErrorBubbling(true)
                        ->add( 'expiring', 'submit' , array( 'label' => $this->get('translator')->trans('m2m.notExpiringButtonText' , array() , 'IIABStudentTransferBundle') , 'attr' => array( 'value' => $studentsExpirationInfo->getExpiring() , 'class' => 'button button-action' , 'style' => 'float:right;' ) ) )
                        //->add( 'submitAndNext' , 'submit' , array( 'label' => $this->get( 'translator' )->trans( 'forms.submitNext' , array() , 'IIABStudentTransferBundle' ), 'attr' => array( 'class' => 'button button-action') ) )
                        ->getForm();
                    $form->handleRequest( $request );
                    if( $form->isValid() ) {
                        return $this->redirect( $this->generateUrl( $request->get('_route') , array( 'step' => 4 ) ) );
                    }
                } elseif ( !empty( $studentsExpirationInfo ) && ( !$studentsExpirationInfo->getExpiring () ) ) {

                    $form = $this->createFormBuilder()
                        ->setAction( $this->generateUrl( $request->get('_route') , array( 'step' => 5 ) ) )
                        ->setErrorBubbling(true)
                        ->add( 'notExpiring', 'submit' , array( 'label' => $this->get('translator')->trans('m2m.notExpiringButtonText' , array() , 'IIABStudentTransferBundle') , 'attr' => array( 'value' => $studentsExpirationInfo->getExpiring() , 'class' => 'button button-action' , 'style' => 'float:right;' ) ) )
                        ->getForm();
                    $form->handleRequest( $request );

                    if( $form->isValid() ) {
                        //$formData = $form->getData();
                        //$request->getSession()->set( 'stw-formData' , base64_encode( serialize( $formData ) ) );
                        return $this->redirect( $this->generateUrl( $request->get('_route') , array( 'step' => 4 ) ) );
                    }
                } else {
                    return $this->redirect( $this->generateUrl( $request->get('_route') , array( 'step' => $step ) ) );
                }
                break;
        }
        return $this->render( '@IIABStudentTransfer/Default/transferM2M.html.twig' , array( 'form' => $form->createView() , 'step' => ( $step - 1 ) , 'nonFormFields' => $textFields , 'openEnrollment' => $openEnrollment ) );
    }
}