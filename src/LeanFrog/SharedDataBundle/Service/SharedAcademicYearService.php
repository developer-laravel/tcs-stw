<?php

namespace LeanFrog\SharedDataBundle\Service;

use IIAB\MagnetBundle\Service\MagnetAcademicYearService;
use IIAB\StudentTransferBundle\Service\StudentTransferAcademicYearService;

class SharedAcademicYearService {

    /** @var EntityManager **/
    private $doctrine;

    /** Academic Year is equal to the ending year of the acadmic period **/
    private $academic_year;

    public function __construct( \Doctrine\Bundle\DoctrineBundle\Registry $doctrine ) {

        $this->doctrine = $doctrine;
    }

    public function getAcademicYear(){

        if( empty( $this->academic_year ) ){

            $magnet_year = 0;
            if( class_exists( '\\IIAB\\MagnetBundle\\Service\\MagnetAcademicYearService') ){
                $academic_year_service = new MagnetAcademicYearService( $this->doctrine->getManager() );
                $magnet_year = $academic_year_service->getYearApplyingFrom();
            }

            $transfer_year = 0;
            if( class_exists( '\\IIAB\\StudentTransferBundle\\Service\\StudentTransferAcademicYearService') ){
                $academic_year_service = new StudentTransferBundle( $this->doctrine->getManager() );
                $transfer_year = $academic_year_service->getAcademicYear();

            }

            $current_year = intval( date('Y') );

            $this->academic_year = max($magnet_year, $transfer_year, $current_year);
        }

        return $this->academic_year;
    }
}