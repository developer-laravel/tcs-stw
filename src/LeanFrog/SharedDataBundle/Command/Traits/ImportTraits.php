<?php

namespace LeanFrog\SharedDataBundle\Command\Traits;

use LeanFrog\SharedDataBundle\Service\SharedAcademicYearService;

trait ImportTraits {

    public function getYear(){

        if( empty( $this->year ) ){

            $academic_year_service = new SharedAcademicYearService( $this->getContainer()->get( 'doctrine' ) );
            $this->year = $academic_year_service->getAcademicYear();
        }
        return $this->year;
    }

    public function maybe_flush(){

        $this->flush_counter = ( empty( $this->flush_counter ) ) ? 1 : $this->flush_counter + 1;

        if( $this->flush_counter >= 5000 ){
            $this->commits = ( empty( $this->commits ) ) ? 1 : $this->commits + 1;
            var_dump( $this->commits . date(' H:i ') . memory_get_usage() );
            $this->entity_manager->flush();
            $this->flush_counter = 0;
        }
    }

}