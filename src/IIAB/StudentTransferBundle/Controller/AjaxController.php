<?php
namespace IIAB\StudentTransferBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class AjaxController
 * @package IIAB\StudentTransferBundle\Controller
 * @Route("ajax/")
 */
class AjaxController extends Controller {

    /**
     * @Route( "keep-alive/" , name="ajax_keep_alive")
     */
    public function keepAliveAction(){

        $securityContext = $this->get('security.authorization_checker');

        if( $securityContext->isGranted('ROLE_ADMIN') ){
            return new JsonResponse(array('role' => 'admin'));
        }

        return new JsonResponse(array('role' => 'user'));
    }

    /**
    * @Route( "resend-email/" , name="ajax_resend_email")
    */
    public function resendEmailAction(){

        $response = [
            'error' => false,
            'success' => false
        ];

        $securityContext = $this->get('security.authorization_checker');
        if( $securityContext->isGranted('ROLE_ADMIN') ){

            $request = $this->get('request_stack')->getCurrentRequest();
            $action = strtolower( $request->get('email_type') );
            $submission = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Submission' )
                ->find( $request->get('submission_id') );

            if( empty( $submission ) ){
                $response['error'] = 'No Submission Found';
                    return new JsonResponse( $response );
            }

            if( $request->get('email_address') ){
                $submission->setEmail( $request->get('email_address') );
            }

            $mailer = $this->get('stw.email');
            switch( $action ){
                case '':
                    $response['error'] = 'No Action Selected';
                    break;
                case 'confirmation':

                    $mailer->sendConfirmationEmail( $submission );

                    $result_data = '';

                    $response['success'] = ': Confirmation Sent';
                    break;
            }

            return new JsonResponse( $response );
        }

        exit();
    }
}