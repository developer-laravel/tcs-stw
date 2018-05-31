<?php
/**
 * Created by PhpStorm.
 * User: michellegivens
 * Date: 12/26/14
 * Time: 1:29 PM
 */

namespace IIAB\StudentTransferBundle\Service;

use Doctrine\ORM\EntityManager;
use IIAB\StudentTransferBundle\Entity\AddressBound;
use IIAB\StudentTransferBundle\Service\ZoningAPIService;

class CheckAddressService {

    /** @var array */
    private $student;

    /** @var EntityManager */
    private $emLookup;

    private $useAPI = true;

    /**
     * @var \IIAB\MagnetBundle\Service\ZoningAPIService
     */
    private $zoningAPI;

    /**
     * Setup the services to be able to lookup any information needed.
     *
     * @param       $emLookup
     */
    public function __construct( EntityManager $emLookup ) {

        $this->setEmLookup( $emLookup );

        if( $this->useAPI ) {
            $this->zoningAPI = new ZoningAPIService($this->emLookup);
        }
    }

    /**
     * Check the address of the current student
     * to see if the student is in bound.
     *
     * @param array $student
     *
     * @return array | bool
     */
    public function checkAddress( array $student ) {
        $this->student = $student;

        if( $this->student['student_status'] == 'current' ) {
            //Do not check current TCS student against Zoning.
            //return true;
        }

        $lookupAddress = $this->student['address'];

        $zip = preg_split( '/-/' , trim( $this->student['zip'] ) , 2 );
        $zip = trim( $zip[0] );

        if( $this->useAPI ) {
            $lookupResponse = $this->zoningAPI->getZonedSchools( $lookupAddress , $zip );
        } else {
            $lookupResponse = $this->checkAddressAgainstDatabase( $lookupAddress , $zip );
        }

        if( $lookupResponse == false ) {
            $lookupAddress = $this->correctAddress( $this->student['address'] );
            if( $this->useAPI ) {
                $lookupResponse = $this->zoningAPI->getZonedSchools( $lookupAddress , $zip );
            } else {
                $lookupResponse = $this->checkAddressAgainstDatabase( $lookupAddress , $zip );
            }
        }

        if( $lookupResponse == false ) {
            //Change address and try again.
            $addressArray = explode( ' ' , $lookupAddress );

            //check the first two elements in address string against the HSV zoning site

            // Get street number and name.
            $secondTryAddressLookup = implode( ' ' , array_slice( $addressArray , 0 , 2 ) );

            if( $this->useAPI ) {
                $lookupResponse2 = $this->zoningAPI->getZonedSchools( $secondTryAddressLookup , $zip );
            } else {
                $lookupResponse2 = $this->checkAddressAgainstDatabase($secondTryAddressLookup, $zip);
            }

            if( $lookupResponse2 == false ) {
                // Get street number and name then last two in index.
                $thirdTryAddressLookup = implode( ' ' , array_slice( $addressArray , 0 , 2 ) ) . '%' . implode( ' ' , preg_replace('/[^a-zA-Z0-9\s]/', '', array_slice( $addressArray , -2 , 2 ) ) );

                if( $this->useAPI ) {
                    $lookupResponse3 = $this->zoningAPI->getZonedSchools( $thirdTryAddressLookup , $zip );
                } else {
                    $lookupResponse3 = $this->checkAddressAgainstDatabase($thirdTryAddressLookup, $zip);
                }

                if( $lookupResponse3 == false ) {
                    $fourthTryAddressLookup = implode( ' ' , array_slice( $addressArray , 0 , 2 ) ) . '%' . implode( ' UNIT ' , preg_replace('/[^a-zA-Z0-9\s]/', '', array_slice( $addressArray , -1 , 1 ) ) );

                    if( $this->useAPI ) {
                        $lookupResponse4 = $this->zoningAPI->getZonedSchools( $fourthTryAddressLookup , $zip );
                    } else {
                        $lookupResponse4 = $this->checkAddressAgainstDatabase($fourthTryAddressLookup, $zip);
                    }

                    if ( $lookupResponse4 == false ) {
                        return false;
                    } else {
                        $returnArray = array(
                            'Elementary' => $lookupResponse4['zoned']->getESBND(),
                            'Middle' => $lookupResponse4['zoned']->getMSBND(),
                            'High' => $lookupResponse4['zoned']->getHSBND(),
                            'choiceElementary' => $lookupResponse4['choice']->getESBND(),
                            'choiceMiddle' => $lookupResponse4['choice']->getMSBND(),
                            'choiceHigh' => $lookupResponse4['choice']->getHSBND(),
                        );
                        return $returnArray;
                    }
                } else {
                    $returnArray = array(
                        'Elementary' => $lookupResponse3['zoned']->getESBND(),
                        'Middle' => $lookupResponse3['zoned']->getMSBND(),
                        'High' => $lookupResponse3['zoned']->getHSBND(),
                        'choiceElementary' => $lookupResponse3['choice']->getESBND(),
                        'choiceMiddle' => $lookupResponse3['choice']->getMSBND(),
                        'choiceHigh' => $lookupResponse3['choice']->getHSBND(),
                    );
                    return $returnArray;
                }
            } else {
                $returnArray = array(
                    'Elementary' => $lookupResponse2['zoned']->getESBND(),
                    'Middle' => $lookupResponse2['zoned']->getMSBND(),
                    'High' => $lookupResponse2['zoned']->getHSBND(),
                    'choiceElementary' => $lookupResponse2['choice']->getESBND(),
                    'choiceMiddle' => $lookupResponse2['choice']->getMSBND(),
                    'choiceHigh' => $lookupResponse2['choice']->getHSBND(),
                );
                return $returnArray;
            }
        }

        $returnArray = array(
            'Elementary' => $lookupResponse['zoned']->getESBND(),
            'Middle' => $lookupResponse['zoned']->getMSBND(),
            'High' => $lookupResponse['zoned']->getHSBND(),
            'choiceElementary' => $lookupResponse['choice']->getESBND(),
            'choiceMiddle' => $lookupResponse['choice']->getMSBND(),
            'choiceHigh' => $lookupResponse['choice']->getHSBND(),
        );


        return $returnArray;
    }

    /**
     * Checks the address against the AddressBounds database.
     *
     * @param string $lookupAddress
     * @param string $zip
     *
     * @return bool|AddressBound
     */
    private function checkAddressAgainstDatabase( $lookupAddress = '' , $zip = '' ) {

        $addressFound = $this->getEmLookup()->getRepository( 'IIABStudentTransferBundle:AddressBound' )->findAddressLike( $lookupAddress , $zip );
        //IF the addressFound is null or there is more than one address (like apt, units, etc).
        //return false;

        if( count( $addressFound ) > 1 ) {
            $addressFound = $this->getEmLookup()->getRepository( 'IIABStudentTransferBundle:AddressBound' )->findSpecificAddress( $lookupAddress , $zip );
        }

        if( $addressFound == null || count( $addressFound ) > 1 ) {
            return false;
        }

        return $addressFound[0];
    }

    /**
     * Check the address against the HSV zoning website.
     *
     * @param string $lookupAddress
     *
     * @return bool
     */
    private function checkAddressAgainstHSVZoning( $lookupAddress = '' ) {

        //Make the address urlencoded so it is prepared for the URL.
        $lookupAddress = urlencode( "'" . $lookupAddress . "%'" );

        //TODO: Added in HSV Zoning Query from StudentTransfer Project

        return true;
    }



    /**
     * Correct the address to match all the street names that the city/bound file uses.
     * See function for more details on the changes.
     *
     * @param string $lookupAddress
     *
     * @return string
     */
    private function correctAddress( $lookupAddress = '' ) {

        $lookupAddress = trim( $lookupAddress );
        $lookupAddress = preg_replace( '/(\bSuite\b)|(\bLot\b)|(\bApartment\b)|(\bApt\b)|(\bAddress\b)/i' , 'Unit' , $lookupAddress );
        $lookupAddress = preg_replace( "/(\.)|(,)|(')|(#)/" , '' , $lookupAddress );
        $lookupAddress = preg_replace( '/(\bDrive\b)/i' , 'DR' , $lookupAddress );
        $lookupAddress = preg_replace( '/(\bCr\b)|(\bCircle\b)/i' , 'CIR' , $lookupAddress );
        $lookupAddress = preg_replace( '/(\bmc)/i' , 'Mc ' , $lookupAddress );
        $lookupAddress = preg_replace( '/(\bBlvd\b)/i' , 'BLV' , $lookupAddress );
        $lookupAddress = preg_replace( '/(\bAvenue\b)/i' , 'AVE' , $lookupAddress );
        $lookupAddress = preg_replace( '/(\bPlace\b)/i' , 'PL' , $lookupAddress );
        $lookupAddress = preg_replace( '/(\bLane\b)/i' , 'LN' , $lookupAddress );
        $lookupAddress = strtoupper( $lookupAddress ); //Bounds are 100% upper case.

        $addressArray = explode( ' ' , $lookupAddress );

        //Does the index:1 contain an number street. Example: 8th Street.
        if( isset( $addressArray[1] ) && preg_match( '/\d+/' , $addressArray[1] , $matches ) !== false ) {
            //Index:1 contains an number. Need to replace.
            //Add in switch statement to handle converting 1st - 17th to First - Seventeenth
            switch( strtoupper( $addressArray [1] ) ) {
                case '1ST':
                    $addressArray[1] = 'FIRST';
                    break;
                case '2ND':
                    $addressArray[1] = 'SECOND';
                    break;
                case '3RD':
                    $addressArray[1] = 'THIRD';
                    break;
                case '4TH':
                    $addressArray[1] = 'FOURTH';
                    break;
                case '5TH':
                    $addressArray[1] = 'FIFTH';
                    break;
                case '6TH':
                    $addressArray[1] = 'SIXTH';
                    break;
                case '7TH':
                    $addressArray[1] = 'SEVENTH';
                    break;
                case '8TH':
                    $addressArray[1] = 'EIGHTH';
                    break;
                case '9TH':
                    $addressArray[1] = 'NINTH';
                    break;
                case '10TH':
                    $addressArray[1] = 'TENTH';
                    break;
                case '11TH':
                    $addressArray[1] = 'ELEVENTH';
                    break;
                case '12TH':
                    $addressArray[1] = 'TWELFTH';
                    break;
                case '13TH':
                    $addressArray[1] = 'THIRTEENTH';
                    break;
                case '14TH':
                    $addressArray[1] = 'FOURTEENTH';
                    break;
                case '15TH':
                    $addressArray[1] = 'FIFTEENTH';
                    break;
                case '17TH':
                    $addressArray[1] = 'SEVENTEENTH';
                    break;
                default:
                    break;
            }
        }
        $lookupAddress = implode( ' ' , $addressArray );

        return $lookupAddress;
    }

    /**
     * @return array
     */
    public function getSuggestions( array $student = null , $maxSuggestions = 5 ) {
        $data = ( isset($student) ) ? $student : $this->student;

        $data['address'] = $this->correctAddress( $data['address'] );

        $addressParts = explode( ' ', trim( $data['address'] ) );
        $countParts = count($addressParts);

        $results = null;
        for( $useParts = $countParts; $useParts > 0; $useParts-- ){
            $searchAddress = implode( ' ', array_slice( $addressParts, 0, $useParts ) );

            if( $this->useAPI ) {
                $suggestions = $this->zoningAPI->getAddressCandidates( $searchAddress , $data['zip'], $maxSuggestions );
            } else {
                $suggestions = $this->getAddressFromDatabase( $searchAddress, $data['zip'], $maxSuggestions );
            }

            if($suggestions) {
                return ( is_array($suggestions) ) ? $suggestions : [$suggestions];
            }
        }
        return [];
    }

    /**
     * @return EntityManager
     */
    public function getEmLookup() {

        return $this->emLookup;
    }

    /**
     * @param EntityManager $emLookup
     */
    public function setEmLookup( $emLookup ) {

        $this->emLookup = $emLookup;
    }
}