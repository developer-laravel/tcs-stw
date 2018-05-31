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
//use Symfony\Component\Validator\Constraints\Null;
use IIAB\StudentTransferBundle\Form\Constraints\ValidAge;
use IIAB\StudentTransferBundle\Form\Constraints\ValidAddress;
use IIAB\StudentTransferBundle\Controller\Traits\TransferControllerTraits;

class DefaultController extends Controller {
	use TransferControllerTraits;

	/**
	 *
	 * @param Request $request
	 *
	 * @Route( "/" , name="stw_index" )
	 * @return \Symfony\Component\HttpFoundation\Response;
	 */
	public function indexAction( Request $request ) {

		$lastFormData = $request->getSession()->has( 'stw-formData' );
		if( $lastFormData === true ) {
			$request->getSession()->remove( 'stw-formData' );
			$request->getSession()->remove( 'stw-formData-zoned' );
		}

		$openEnrollments = $this->getOpenEnrollmentPeriod();
		$specialEnrollments = $this->getSpecialEnrollmentPeriods();

		return $this->render( 'IIABStudentTransferBundle:Default:index.html.twig' , array( 'openEnrollment' => $openEnrollments, 'specialEnrollments' => $specialEnrollments ) );
	}

	/**
	 * @Route( "/incorrect-info" , name="stw_incorrect_info")
	 * return \Symfony\Component\HttpFoundation\Response;
	 */
	public function incorrectInfoAction( Request $request ) {

		/**
		 * Always clear the formData in the session on the index page. Making sure to have clean session.
		 */
		$lastFormData = $request->getSession()->has( 'stw-formData' );
		if( $lastFormData === true ) {
			$lastFormData = unserialize( base64_decode( $request->getSession()->get( 'stw-formData' ) ) );
			$currentEnrollment = $lastFormData['enrollment'];
			$request->getSession()->remove( 'stw-formData' );
			$request->getSession()->remove( 'stw-formData-zoned' );
		}

		//Get the openEnrollmentPeriod and see if it is less than 72 hours before it closes.
		$openEnrollments = $this->getOpenEnrollmentPeriod( $currentEnrollment );
		if( $openEnrollments == null )
			return $this->redirect( $this->generateUrl( 'stw_index' ) , 301 );

		$endingDate = $openEnrollments->getEndingDate();
		$today = new \DateTime( 'now' );
		$interval = $endingDate->diff( $today );
		if( $interval->invert == 1 && $interval->days <= 3 )
			$lessThan72Hours = 'yes';
		else
			$lessThan72Hours = 'no';

		return $this->render( 'IIABStudentTransferBundle:Default:incorrectInfo.html.twig' , array( 'lessThan72Hours' => $lessThan72Hours ) );
	}

	/**
	 * @Route( "/no-selection" , name="stw_no_selection")
	 * return \Symfony\Component\HttpFoundation\Response;
	 */
	public function noSchoolAction( Request $request ) {

		/**
		 * Always clear the formData in the session on the index page. Making sure to have clean session.
		 */
		$lastFormData = $request->getSession()->has( 'stw-formData' );
		if( $lastFormData === true ) {
			$request->getSession()->remove( 'stw-formData' );
			$request->getSession()->remove( 'stw-formData-zoned' );
		}
		return $this->render( 'IIABStudentTransferBundle:Default:noSelection.html.twig' );
	}

	/**
	 * @Route( "/already-submitted" , name="stw_already_submitted")
	 * return \Symfony\Component\HttpFoundation\Response;
	 */
	public function alreadySubmittedAction( Request $request ) {

		/**
		 * Always clear the formData in the session on the index page. Making sure to have clean session.
		 */
		$lastFormData = $request->getSession()->has( 'stw-formData' );
		if( $lastFormData === true ) {
			$request->getSession()->remove( 'stw-formData' );
			$request->getSession()->remove( 'stw-formData-zoned' );
		}
		return $this->render( 'IIABStudentTransferBundle:Default:alreadySubmitted.html.twig' );
	}

	/**
	 * Display the successful submission
	 *
	 * @param Request $request
	 *
	 * @Route( "/success" , name="stw_success")
	 * @return \Symfony\Component\HttpFoundation\Response;
	 */
	public function successAction( Request $request ) {

		/**
		 * Always clear the formData in the session on the index page. Making sure to have clean session.
		 */
		$lastFormData = $request->getSession()->has( 'stw-formData' );
		$submission = false;

		if( $lastFormData !== false ) {
			$lastFormData = unserialize( base64_decode( $request->getSession()->get( 'stw-formData' ) ) );
			//empty all sessions data to keep data secure.
			$request->getSession()->remove( 'stw-formData' );
			$request->getSession()->remove( 'stw-formData-zoned' );

			if( isset( $lastFormData['confirmation'] ) ){
				$submission_id = explode('-', $lastFormData['confirmation']);
				$submission_id = end( $submission_id );
				$submission = $this->getDoctrine()->getRepository('IIABStudentTransferBundle:Submission')->find( $submission_id );
			}	
		}
		
		return $this->render( 'IIABStudentTransferBundle:Default:success.html.twig' , array(
			'confirmation' => $lastFormData['confirmation'],
			'submission' => $submission
		) );
	}

	/**
	 * Display the successful transfer submission
	 *
	 * @param Request $request
	 *
	 * @Route( "/transfer" , name="stw_transfer")
	 * @return \Symfony\Component\HttpFoundation\Response;
	 */
	public function transferAction( Request $request ) {

		/**
		 * Always clear the formData in the session on the index page. Making sure to have clean session.
		 */
		$lastFormData = $request->getSession()->has( 'stw-formData' );

		if( $lastFormData !== false ) {
			$lastFormData = unserialize( base64_decode( $request->getSession()->get( 'stw-formData' ) ) );
			//empty all sessions data to keep data secure.
			$request->getSession()->remove( 'stw-formData' );
			$request->getSession()->remove( 'stw-formData-zoned' );
		}


		return $this->render( 'IIABStudentTransferBundle:Default:transfer.html.twig' , array( 'confirmation' => $lastFormData['confirmation'] ) );
	}

	/**
     *
     * @Template()
     * @param string $setting
     *
     * @return array
     */
    public function messageAction( Request $request , $setting = '' ) {

        if( $setting == null ) {
            return [];
        }

        /** @var \IIAB\StudentTransferBundle\Entity\Settings $setting */
        $setting = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Settings' )->findOneBySettingName( $setting );

        if( $setting == null ) {
            return [];
        }

        if( $request->getLocale() == 'es' ) {
            $setting = $setting->getSettingValueEs();
        } else {
            $setting = $setting->getSettingValue();
        }

        return [ 'message' => $setting ];
    }
}
