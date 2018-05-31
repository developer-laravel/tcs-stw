<?php
/**
 * Created by PhpStorm.
 * User: DerrickWales
 * Date: 4/24/15
 * Time: 9:10 AM
 */

namespace IIAB\StudentTransferBundle\Controller;

use IIAB\StudentTransferBundle\Entity\CurrentEnrollmentSettings;
use IIAB\StudentTransferBundle\Entity\SchoolGroup;
use IIAB\StudentTransferBundle\Entity\OpenEnrollment;
use IIAB\StudentTransferBundle\Form\CurrentEnrollmentSettingsForm;
use IIAB\StudentTransferBundle\Form\CurrentEnrollmentSettingsSelectPeriod;
use IIAB\StudentTransferBundle\Lottery\Lottery;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Class CurrentEnrollmentSettingsController
 * @package IIAB\StudentTransferBundle\Controller
 * @Route("/admin/current-enrollment-settings/")
 */
class CurrentEnrollmentSettingsController extends Controller {

	/**
	 * The default page shown for Current Enrollment Settings. Lays out all of the groups (schools) and provides a form to
	 * enter or update the enrollment amounts by race.
	 *
	 * @Route("{period}/", name="currentenrollmentsettings_index")
	 * @param int $period
	 *
	 * @Template("IIABStudentTransferBundle:CurrentEnrollment:currentenrollmentsettings.html.twig")
	 * @return array()
	 */
	public function indexAction( $period = 0 ) {

		$groupArray = [ ];
		$admin_pool = $this->get( 'sonata.admin.pool' );
		$updated_successfully = false;
		$request = $this->get('request_stack')->getCurrentRequest();

		if( $period == 0 ) {
			return $this->redirect( $this->generateUrl( 'currentenrollmentsettings_default' ) );
		}

		$openEnrollment = $this->getDoctrine()->getRepository('IIABStudentTransferBundle:OpenEnrollment')->find( $period );

		//Does the Open Enrollment REALLY Exist? If null, we need to redirect.
		if( $openEnrollment == null ) {

			return $this->redirect( $this->generateUrl( 'currentenrollmentsettings_default' ) );
		}

		$getGroups = $this->getDoctrine()
            ->getRepository('IIABStudentTransferBundle:SchoolGroup')
            ->getEnrollmentSchools( $openEnrollment );

		/* Cycle through individual groups comparing Ids and current enrollment period to contents of CurrentEnrollmentSettings table.
		If a school doesn't have an applicable result, create a new empty CurrentEnrollmentSettings table and fill with group Id and
		default values */
		/** @var SchoolGroup $group */
		$currentEnrollmentDateTime = new \DateTime();
		foreach ( $getGroups as $group ) {

			$groupKey = $this->getDoctrine()
                ->getRepository( 'IIABStudentTransferBundle:CurrentEnrollmentSettings' )
                ->findOneBy( [
                    'groupId' => $group ,
                    'enrollmentPeriod' => $openEnrollment
                ] , [
                    'addedDateTime' => 'DESC'
                ] );

			if ( $groupKey == null ) {
				$groupKey = new CurrentEnrollmentSettings();
				$groupKey->setGroupId( $group );
				$groupKey->setAddedDateTime( $currentEnrollmentDateTime );
				$groupKey->setEnrollmentPeriod( $openEnrollment );

				$this->getDoctrine()->getManager()->persist( $groupKey );
				$this->getDoctrine()->getManager()->flush();
			}
			$groupArray[] = $groupKey;
		}

        $form = $this->createFormBuilder( )
            ->add( 'currentEnrollment' , 'collection' , [
                'entry_type' => CurrentEnrollmentSettingsForm::class,
                'data' => $groupArray ] )
            ->add( 'saveCurrentEnrollment' , 'submit' , [
                'label' => 'Save Settings' ] )
		;
	    $form = $form->getForm();
	    $form->handleRequest( $request );

		if ( $form->isValid( ) ) {
			$data = $form->getData();
			if ( $form->get( 'saveCurrentEnrollment' )->isClicked() ) {
				$data = $data['currentEnrollment'];
				foreach( $data as $update ) {
					$this->getDoctrine()->getManager()->persist( $update );
					$this->getDoctrine()->getManager()->flush();
				}
				$updated_successfully = true;
			}
		}

		return [
            'admin_pool' => $admin_pool ,
            'openEnrollment' => $openEnrollment ,
            'update_successful' => $updated_successfully ,
            'form' => $form->createView()
        ];
	}

    /**
     * If an enrollment period is not specified in the url slug upon page load, the user will be redirected to a page
     * on which they can select the enrollment period they wish to view. This form should pass back to the indexAction
     * the specified enrollmentPeriod ID number.
     *
	 * @Route( "", name="currentenrollmentsettings_default")
     * @template( "IIABStudentTransferBundle:CurrentEnrollment:currentenrollmentsettings_selectperiod.html.twig" )
     */
    public function selectPeriodAction( ) {
        $admin_pool = $this->get( 'sonata.admin.pool' );
        $request = $this->get('request_stack')->getCurrentRequest();
        $form = $this->createFormBuilder()
            ->add( 'selectPeriod' , 'entity' , [
                'data' => new CurrentEnrollmentSettingsSelectPeriod(),
				'class' => 'IIABStudentTransferBundle:OpenEnrollment' ] )
            ->add( 'saveEnrollmentPeriod' , 'submit' , [
                'label' => 'Submit' ,
                'attr' => [ 'class' => 'btn btn-primary' ] ] )
            ->getForm( )
            ->handleRequest( $request );

        if ( $form->isValid( ) ) {
			$data = $form->getData();
			if( $data['selectPeriod'] ) {
				return $this->redirect( $this->generateUrl( 'currentenrollmentsettings_index' , array( 'period' => $data['selectPeriod']->getId() ) ) );
			}
        }
        return [ 'admin_pool' => $admin_pool , 'form' => $form->createView( ) ];
    }
}