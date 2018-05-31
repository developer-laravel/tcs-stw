<?php
// src/IIAB/StudentTransferBundle/Controller/Traits/TransferControllerTraits.php

namespace IIAB\StudentTransferBundle\Controller\Traits;

use IIAB\StudentTransferBundle\Entity\Form;
use IIAB\StudentTransferBundle\Entity\Audit;

trait TransferControllerTraits {

    /**
     * return Symfony\Component\HttpFoundation\response;
     */
    public function footerAction() {

        return $this->render( '@IIABStudentTransfer/Default/footer.html.twig' , array( 'date' => date( 'Y' ) ) );

    }

    private function setOpenEnrollmentPeriodForForm( $request ) {

        $enrollmentParam = $request->get( 'enrollment' );

        if( !empty( $enrollmentParam ) ) {
            return $this->getOpenEnrollmentPeriod( $enrollmentParam );
        }
        return null;
    }

    /**
     * Looks for any openEnrollment periods going on. Returns Null if there aren't any available openEnrollments.
     *
     * @param int $enrollmentNumber
     *
     * @return \IIAB\StudentTransferBundle\Entity\OpenEnrollment|null|array
     */
    private function getOpenEnrollmentPeriod( $enrollmentNumber = 0 ) {

        $em = $this->getDoctrine();

        if( $enrollmentNumber ) {
            $openEnrollments = $em->getRepository( 'IIABStudentTransferBundle:OpenEnrollment' )->createQueryBuilder( 'o' )
                ->where( '( o.beginningDate <= :currentDate AND o.endingDate >= :currentDate ) OR ( o.afterLotteryBeginningDate <= :currentDate AND o.afterLotteryEndingDate >= :currentDate )')
                ->andWhere( 'o.id = :enrollment' )
                ->setParameter( 'currentDate' , date( 'Y-m-d H:i:s' ) )
                ->setParameter( 'enrollment' , $enrollmentNumber )
                ->setMaxResults( 1 )
                ->getQuery()
                ->getResult()
            ;
        } else {
            $openEnrollments = $em->getRepository( 'IIABStudentTransferBundle:OpenEnrollment' )->createQueryBuilder( 'o' )
                ->where( 'o.beginningDate <= :currentDate AND o.endingDate >= :currentDate')
                ->orWhere( 'o.afterLotteryBeginningDate <= :currentDate AND o.afterLotteryEndingDate >= :currentDate' )
                ->setParameter( 'currentDate' , date( 'Y-m-d H:i:s' ) )
                ->getQuery()
                ->getResult()
            ;
        }

        if( $openEnrollments == null )
            return null;

        if( count( $openEnrollments ) == 1 && $enrollmentNumber )
            return $openEnrollments[0];

        return $openEnrollments;
    }

    private function getSpecialEnrollmentPeriods( $formId = 0 ) {
        $em = $this->getDoctrine();

        $specialEnrollments = $em->getRepository( 'IIABStudentTransferBundle:SpecialEnrollment' )->createQueryBuilder( 'o' )
            ->where( 'o.beginningDate <= :currentDate AND o.endingDate >= :currentDate')
            ->setParameter( 'currentDate' , date( 'Y-m-d H:i:s' ) );


        if( $formId ){
            $specialEnrollments = $specialEnrollments->andWhere( 'o.form = :formId' )->setParameter( 'formId' , $formId );
        }

        $specialEnrollments = $specialEnrollments->getQuery()->getResult()
        ;

        if( $specialEnrollments == null )
            return null;

        return $specialEnrollments;
    }

    private function getRaceOptions() {

        $races = $this->getDoctrine()->getManager()->getRepository('IIABStudentTransferBundle:Race')
            ->findAll();

        $choice = ['Choose an option' => ''];
        foreach( $races as $race ){
            $choices[ $race->getRace() ] = $race->getId();
        }
        return $choices;

        return [
            '' => 'Choose an option',
            'American Indian/Alaskan Native' => $this->get('translator')->trans( 'American Indian/Alaskan Native' ) ,
            'American Indian/Alaskan Native- Hispanic' => $this->get('translator')->trans( 'American Indian/Alaskan Native- Hispanic' ) ,
            'Asian' => $this->get('translator')->trans( 'Asian' ) ,
            'Asian- Hispanic' => $this->get('translator')->trans( 'Asian- Hispanic' ) ,
            'Black/African American' => $this->get('translator')->trans( 'Black/African American' ) ,
            'Black/African American- Hispanic' => $this->get('translator')->trans( 'Black/African American- Hispanic' ) ,
            'Multi Race - Two or More Races' => $this->get('translator')->trans( 'Multi Race - Two or More Races' ) ,
            'Multi Race - Two or More Races- Hispanic' => $this->get('translator')->trans( 'Multi Race - Two or More Races- Hispanic' ) ,
            'Native Hawaiian or Other Pacific Islander- Hispanic' => $this->get('translator')->trans( 'Native Hawaiian or Other Pacific Islander- Hispanic' ) ,
            'Pacific Islander' => $this->get('translator')->trans( 'Pacific Islander' ) ,
            'White' => $this->get('translator')->trans( 'White' ) ,
            'White- Hispanic' => $this->get('translator')->trans( 'White- Hispanic' ) ,
        ];
    }

    /**
     * @param int $auditCode
     * @param int $studentID
     * @param int $submission
     */
    private function recordAudit( $auditCode = 0 , $submission = 0 , $studentID = 0, $request ) {
        $em = $this->getDoctrine()->getManager();
        //$user = $this->get( 'security.context' )->getToken()->getUser();
        $user = $this->get('security.token_storage')->getToken()->getUser();

        $auditCode = $em->getRepository( 'IIABStudentTransferBundle:AuditCode' )->find( $auditCode );

        $audit = new Audit();
        $audit->setAuditCodeID( $auditCode );
        $audit->setIpaddress( $request->getClientIp() );
        $audit->setSubmissionID( $submission );
        $audit->setStudentID( $studentID );
        $audit->setTimestamp( new \DateTime() );
        $audit->setUserID( ( $user == 'anon.' ? 0 : $user->getId() ) );

        $em->persist( $audit );
        $em->flush();
        $em->clear();
    }

    public function getRouteFormId(){

        $request = $this->get('request_stack')->getCurrentRequest();
        $routeName = $request->get('_route');

        $form_object = $this->getDoctrine()->getManager()->getRepository('IIABStudentTransferBundle:Form')
        ->findOneBy( [
            'route' => $routeName
        ]);

        if( empty( $form_object ) ){
            return null;
        }

        return $form_object->getId();
    }

    public function getSchoolsFromSetting( $key, $is_renewal=true ){

        $entity_manager = $this->getDoctrine()->getManager();

        $setting_value = $entity_manager->getRepository( 'IIABStudentTransferBundle:Settings' )
            ->findOneBy([ 'settingName' => $key ]);

        if( $setting_value == null ){
            return [];
        }

        $setting_value = json_decode( $setting_value->getSettingValue() );

        $schools = [];
        foreach( $setting_value as $school ){
            if( is_object( $school ) ){

                if( $is_renewal ){
                    $schools[] = $school->school;
                } else if( !$school->renewalOnly ){
                    $schools[] = $school->school;
                }

            } else {
                $schools[] = $school;
            }
        }
        return $schools;
    }

    public function limitSchoolsBySetting( $all_schools = [], $key, $is_renewal=true ){

        $entity_manager = $this->getDoctrine()->getManager();

        $setting_schools = $this->getSchoolsFromSetting( $key, $is_renewal );

        $schoolGroups = $entity_manager->getRepository( 'IIABStudentTransferBundle:SchoolGroup' )
            ->findBy([
                'name' => $all_schools
            ]);
        $school_group_hash = [];

        foreach( $schoolGroups as $group ){
            $school_group_hash[ $group->getName() ] = $group->getId();
        }

        $schools = [];
        foreach( $all_schools as $id => $school ){
            $school_group_id = $school_group_hash[$school];
            if( in_array($school_group_id, $setting_schools ) ){
                $schools[$id] = $school;
            }
        }

        return $schools;
    }

}