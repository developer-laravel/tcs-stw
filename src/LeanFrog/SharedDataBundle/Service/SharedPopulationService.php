<?php
namespace LeanFrog\SharedDataBundle\Service;

use LeanFrog\SharedDataBundle\Entity\Population;

class SharedPopulationService{

    /** @var EntityManager */
    private $shared_manager;

    private $population_history = [];

    private $date_format = 'Y-m-d H:i:s';

    private $spool = [];

    private $races = [
        'other' => 'Other',
        'none' => 'None',
        3 => 'Black',
        7 => 'White',
    ];

    function __construct( $doctrine ) {

        $this->shared_manager = $doctrine->getManager('shared');
    }

    public function getPopulationRecords( $school ){

        return $this->shared_manager
            ->getRepository('lfSharedDataBundle:Population')
            ->findBy([
                'programSchool' => $school,
            ],
            ['updateDateTime' => 'DESC']
        );
    }

    public function getCurrentPopulation( $school ){

        $population_records = $this->getPopulationRecords( $school );
        $current_population = [];

        foreach( $population_records as $population ){
            if( !isset( $current_population[$population->getTrackingColumn()][$this->races[ $population->getTrackingValue()] ] )
                || $current_population[$population->getTrackingColumn()][$this->races[$population->getTrackingValue()]]
                        ->getUpdateDateTime()
                    < $population->getUpdateDateTime()
            ){
                $current_population[$population->getTrackingColumn()][$this->races[$population->getTrackingValue()]] = $population;
           }
        }

        foreach( $this->races as $value ){
            if( empty( $current_population['Race'][$value] )){

                $new_population = new Population();
                $new_population
                    ->setProgramSchool( $school )
                    ->setTrackingColumn( 'Race' )
                    ->setTrackingValue( $value )
                    ->setCount( 0 );

                $current_population['Race'][$value] = $new_population;
            }
        }

        return $current_population;
    }

    public function getCurrentTotalPopulation( $school ){
        $current_population = $this->getCurrentPopulation( $school );

        $total = [];
        foreach( $current_population as $tracking_column => $populations ){
            $total[$tracking_column] = 0;
            foreach( $populations as $population ){
                $total[$tracking_column] += $population->getCount();
            }
        }

        return $total;
    }

    public function getPopulationHistory( $school){
        $population_records = $this->getPopulationRecords( $school );

        $history = [];
        foreach( $population_records as $population ){

            $date_time = $population->getUpdateDateTime();
            $date_key = $date_time->format( 'Y-m-d H:i:s' );
            if( !isset( $history[$date_key][$population->getTrackingColumn()][$population->getTrackingValue()] )){
                $history[$date_key][$population->getTrackingColumn()][$this->races[ $population->getTrackingValue()] ] = [];
            }
            $history[$date_key][$population->getTrackingColumn()][$this->races[ $population->getTrackingValue()] ][] = $population;
        }

        foreach( $history as $date_index => $row ){

            foreach( $this->races as $value ){

                if( empty( $row['Race'][$value][0] )){

                    $new_population = new Population();
                    $new_population
                        ->setProgramSchool( $school )
                        ->setTrackingColumn( 'Race' )
                        ->setTrackingValue( $value )
                        ->setCount( 0 );

                    $history[$date_index]['Race'][$value][] = $new_population;
                }
            }
        }

        return $history;
    }

    public function getRaceIndex( $race ){
        return array_search( $race, $this->races );
    }
}