<?php

namespace IIAB\StudentTransferBundle\Controller;

use IIAB\StudentTransferBundle\Entity\OpenEnrollment;
use IIAB\StudentTransferBundle\Entity\Correspondence;
use IIAB\StudentTransferBundle\Entity\Process;
use IIAB\StudentTransferBundle\Form\CurrentEnrollmentSettingsSelectPeriod;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Doctrine\ORM\EntityRepository;
use Ivory\CKEditorBundle\Form\Type\CKEditorType;
use IIAB\StudentTransferBundle\Form\CurrentEnrollmentSettingsSelectForm;
use IIAB\StudentTransferBundle\Service\CorrespondenceVariablesService;

/**
 * Class PageLayoutController
 * @package IIAB\StudentTransferBundle\Controller
 * @Route("/admin/page-layouts/")
 */
class PageLayoutController extends Controller {

	/**
	 * The "success" view for the email and pdf reports page. If the period parameter ($period) is not set, it should redirect
	 * the end user to another page to explicitly choose a period for which to view a report. Generates PDF letters for all
	 * submissions with the status "Offered".
	 *
	 * @Route("success-tempate/", name="pagelayout_success")
	 *
	 * @param int $period
	 * @Template("IIABStudentTransferBundle:PageLayout:success.html.twig")
	 * @return array()
	 */
	public function successTemplateAction() {

        $admin_pool = $this->get( 'sonata.admin.pool' );
		$request = $this->get('request_stack')->getCurrentRequest();

		// Setup Page Template
		$pageCorrespondence = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
			'active' => 1,
			'name' => 'success',
			'type' => 'page'
		) );

		if($pageCorrespondence == null) {
			$pageCorrespondence = new Correspondence();
			$pageCorrespondence->setName('success');
			$pageCorrespondence->setType('page');
			$pageCorrespondence->setTemplate(file_get_contents($this->container->get('kernel')->getRootDir() . '/../src/IIAB/StudentTransferBundle/Resources/views/Default/success.html.twig'));
			$pageCorrespondence->setActive(1);
			$pageCorrespondence->setLastUpdateDateTime(new \DateTime());
		}
		$pageBlock = CorrespondenceVariablesService::dividePageBlocks($pageCorrespondence->getTemplate());

		
		$form = $this->createFormBuilder(null, [ 'attr' => [ ] ] )
			->add( 'pageTitle', CKEditorType::class, array(
				'label' => 'Page Title',
				'data' => $pageBlock['pageTitle'],
				'attr' => array('class' => 'plain-text single-line')
			))
			->add( 'body', CKEditorType::class, array(
				'label' => 'Body',
				'data' => $pageBlock['body'],
			))

			->add( 'saveChanges' , 'submit' , array(
				'label' => 'Save Template Changes' ,
				'attr' => array( 'class' => 'btn btn-info' ) ,
			) )
		;

		$form = $form->getForm( );

		$form->handleRequest( $request );

		$rootDIR = $this->container->get( 'kernel' )->getRootDir() . '/../web/reports/success/';
		if ( ! file_exists( $rootDIR ) ) {
			mkdir( $rootDIR , 0755 , true );
		}

		$lastGeneratedFiles = array_diff( scandir( $rootDIR ) , array( '..' , '.' , '.DS_Store' ) );
		rsort( $lastGeneratedFiles );
		$lastGeneratedFiles = array_slice( $lastGeneratedFiles , 0 , 5 );

		$process = new Process();

		if ( $form->isValid( ) ) {
			$data = $form->getData( );

			if(  $form->get( 'saveChanges' )->isClicked( )) {

				if( isset( $data['pageTitle'] ) ){
					$pageTemplate = CorrespondenceVariablesService::combineEmailBlocks(['subject' => $data['pageTitle'], 'body_html' => $data['body']]);
					$pageCorrespondence->setTemplate($pageTemplate);
					$this->getDoctrine()->getManager()->persist($pageCorrespondence);
					$this->getDoctrine()->getManager()->flush();
				}

				$pageTemplate = CorrespondenceVariablesService::combineEmailBlocks(['subject' => $data['pageTitle'], 'body_html' => $data['body']]);
				$pageCorrespondence->setTemplate($pageTemplate);
				$this->getDoctrine()->getManager()->persist($pageCorrespondence);
				$this->getDoctrine()->getManager()->flush();

			}

			
			return $this->redirect( $this->generateUrl( 'pagelayout_success' ) );
		}
		$info = array('type' => 'success','title'=>'Success Template' );
		return [ 'admin_pool' => $admin_pool , 'form' => $form->createView( ) , 'files' => $lastGeneratedFiles , 'process' => $process , 'info' => $info ];
	}



}