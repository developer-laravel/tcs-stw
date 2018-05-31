<?php

namespace IIAB\StudentTransferBundle\Service;

use IIAB\StudentTransferBundle\Entity\AddressBound;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ZoningAPIService {

    /**
     * URLs for Tuscaloosa Zoning API endpoints
     *
     * @var array
     */
    private $end_points = [

        'address_point' =>
            "https://arcgis.tuscaloosa-al.gov/arcgis/rest/services/Spillman/"
            ."Addresses_by_Status_Published/MapServer/3/query?"
            ."&geometryType=esriGeometryEnvelope"
            ."&spatialRel=esriSpatialRelIntersects"
            ."&outFields=address_basic"
            ."&returnGeometry=true"
            ."&returnTrueCurves=false"
            ."&returnIdsOnly=false"
            ."&returnCountOnly=false"
            ."&returnZ=false"
            ."&returnM=false"
            ."&returnDistinctValues=false"
            ."&returnExtentsOnly=false"
            ."&f=json"
            ."&where=address_basic+LIKE+",

        'schools' =>
            "https://arcgis.tuscaloosa-al.gov/arcgis/rest/services/CitySchools/"
            ."City_Schools_Districts_1819/MapServer/0/query?"
            ."&geometryType=esriGeometryPoint"
            ."&spatialRel=esriSpatialRelIntersects"
            ."&outFields=elem%2Cmiddle%2Chigh%2CES_choice%2CMS_choice%2CHS_choice"
            ."&returnGeometry=true"
            ."&returnTrueCurves=false"
            ."&returnIdsOnly=false"
            ."&returnCountOnly=false"
            ."&returnZ=false"
            ."&returnM=false"
            ."&returnDistinctValues=false"
            ."&returnExtentsOnly=false"
            ."&returnGeometry=false"
            ."&f=json"
            ."&geometry=",

        'possible_addresses' =>
            "https://arcgis.tuscaloosa-al.gov/arcgis/rest/services/Spillman/"
            ."Addresses_by_Status_Published/MapServer/3/query?"
            ."returnIdsOnly=false"
            ."&returnCountOnly=false"
            ."&returnGeometry=true"
            ."&returnTrueCurves=false"
            ."&spatialRel=esriSpatialRelIntersects"
            ."&geometryType=esriGeometryEnvelope"
            ."&f=json"
            ."&outFields=address_basic"
            ."&where=address_basic+LIKE+"
    ];

    /**
     * ZoningAPIService constructor.
     */
    function __construct() {
    }

    /**
     * Retrieves zoned schools for address
     *
     * @param $address
     * @param null $zip
     * @return bool|AddressBound
     */
    public function getZonedSchools( $address, $zip = null ){

        $address = strtoupper( $address );
        $address = explode( ' APT ', $address)[0];
        $address = explode( ' UNIT ', $address)[0];
        $address = explode( ' LOT ', $address)[0];
        $address = trim( $address );

        $end_point = $this->end_points['address_point'] . urlencode( "'" . $address . "%'" );
        $response = $this->getResponse( $end_point );

        if( count( $response->features ) == 0 ){
            $address = $this->prepareAddress( $address );

            $end_point = $this->end_points['address_point'] . urlencode( "'" . $address . "%'" );
            $response = $this->getResponse( $end_point );
        }

        if( count( $response->features ) == 0 ){
            return false;
        }

        $matching_index = 0;
        $multiple_matches = false;
        if( count( $response->features ) > 1 ){

            $matching_index = -1;
            $basic_addresses = [];
            foreach( $response->features as $index => $feature ){

                if( strtoupper( $address ) == strtoupper( $feature->attributes->address_basic ) ){
                    $multiple_matches = ( $matching_index > -1 );
                    $matching_index = $index;
                    $basic_addresses[] = $feature->attributes->address_basic;
                }
            }
        }

        if( $multiple_matches ){
            if( count( array_unique( $basic_addresses ) ) > 1 ){
                return false;
            }
        }

        if( $matching_index == -1 ){
            return false;
        }

        $end_point = $this->end_points['schools']. urlencode( $response->features[$matching_index]->geometry->x .",". $response->features[$matching_index]->geometry->y );
        $response = $this->getResponse( $end_point );

        if( empty( $response->features ) ){
            return false;
        }

        if(
            isset( $response->features[$matching_index]->attributes->elem )
            && isset( $response->features[$matching_index]->attributes->middle )
            && isset( $response->features[$matching_index]->attributes->high )
            && (
                strtoupper( $response->features[$matching_index]->attributes->elem ) == 'N/A'
                || strtoupper( $response->features[$matching_index]->attributes->middle ) == 'N/A'
                || strtoupper( $response->features[$matching_index]->attributes->high ) == 'N/A'
            )
        ){
            return false;
        }

        $addressBound = new AddressBound();
        $addressBound->setESBND( ( count( $response->features ) ) ? $response->features[0]->attributes->elem : '' );
        $addressBound->setMSBND( ( count( $response->features ) ) ? $response->features[0]->attributes->middle  : '' );
        $addressBound->setHSBND( ( count( $response->features ) ) ? $response->features[0]->attributes->high : '' );

        $choiceBound = new AddressBound();
        $choiceBound->setESBND( ( isset( $response->features[0]->attributes->ES_choice )  ) ? $response->features[0]->attributes->ES_choice : '' );
        $choiceBound->setMSBND( ( isset( $response->features[0]->attributes->MS_choice ) ) ? $response->features[0]->attributes->MS_choice  : '' );
        $choiceBound->setHSBND( ( isset( $response->features[0]->attributes->HS_choice ) ) ? $response->features[0]->attributes->HS_choice : '' );

        return [
            'zoned' => $addressBound,
            'choice' => $choiceBound,
        ];
    }

    /**
     * Returns array of possible street addresses
     *
     * @param $address
     * @param null $zip  //unused
     * @param null $maxAddresses //unused always 5
     * @return array|bool
     */
    public function getAddressCandidates( $address, $zip = null, $maxAddresses = null ){

        // Get possible addresses from API
        $response = $this->getResponse( $this->end_points['possible_addresses'] . urlencode( "'" . $this->prepareAddress( $address ) . "%'" ) );

        if( !$response->features ){
            return false;
        }

        $possible_addresses = [];
        $scoredList = [];

        //Build list of addresses with scores
        foreach( $response->features as $candidate ){

            $addressBound = new AddressBound();
            $addressBound->setAddressFu( $candidate->attributes->address_basic );
            $scoredList[] = [
                'score' => 0,
                'addressBound' => $addressBound
            ];
        }

        //Sort scored list by score descending
        usort($scoredList, function($a, $b) {
            if ($a['score'] == $b['score']) {
                return 0;
            }
            return ($a['score'] > $b['score']) ? -1 : 1;
        });

        //Remove duplicate addresses
        foreach( $scoredList as $index => $scoredAddress ){

            if( !in_array( $scoredAddress['addressBound']->getAddressFu(), $possible_addresses ) ) {
                $possible_addresses[] = $scoredAddress['addressBound']->getAddressFu();
            } else {
                unset( $scoredList[$index] );
            }
        }

        $returnAddresses = [];
        foreach( $scoredList as $address){
            $returnAddresses[] = $address['addressBound'];
        }

        return $returnAddresses;
    }

    /**
     * Retrieve response from API
     *
     * @param $end_point
     * @return array|mixed
     */
    public function getResponse( $end_point ){

        $curl = curl_init($end_point);

        curl_setopt( $curl , CURLOPT_URL , $end_point );
        curl_setopt( $curl , CURLOPT_SSL_VERIFYPEER , false );
        curl_setopt( $curl , CURLOPT_RETURNTRANSFER , true );
        curl_setopt( $curl , CURLOPT_HEADER , false );
        curl_setopt( $curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:13.0) Gecko/20100101 Firefox/13.0.1');    // mPDF 5.7.4
        $data = curl_exec($curl);
        curl_close($curl);

        if (!$data) {
            return [];
        }

        $decoded_data = json_decode($data);

        if (json_last_error() != JSON_ERROR_NONE) {

            writeln('JSON error: ' . json_last_error());
            return [];
        }

        return $decoded_data;
    }

    /**
     * Clean up address for use with Tuscaloosa City API
     *
     * @param $address
     * @return string
     */
    protected function prepareAddress( $address ){
        $address = trim( $address );
        //$address = preg_replace( '/(\bSuite\b)|(\bLot\b)|(\bApt\b)/i' , 'Unit' , $address );
        $address = preg_replace( "/(\.)|(,)|(')|(#)/" , '' , $address );
        $address = preg_replace( '/(\bDrive\b)/i' , 'DR' , $address );
        $address = preg_replace( '/(\bCr\b)/i' , 'CIR' , $address );
        //$address = preg_replace( '/(\bmc)/i' , 'Mc ' , $address );
        $address = preg_replace( '/(\bBlvd\b)/i' , 'BLV' , $address );
        $address = preg_replace( '/(\bAvenue\b)/i' , 'AVE' , $address );

        $address = preg_replace( '/(\bCRK\b)/i' , 'CREEK' , $address );
        $address = preg_replace( '/(\bRK\b)/i' , 'CREEK' , $address );
        $address = preg_replace( '/(\bPlace\b)/i' , 'PL' , $address );
        $address = preg_replace( '/(\bEast\b)/i' , 'E' , $address );
        $address = preg_replace( '/(\bWest\b)/i' , 'W' , $address );
        $address = preg_replace( '/(\bNorth\b)/i' , 'N' , $address );
        $address = preg_replace( '/(\bSouth\b)/i' , 'S' , $address );
        $address = preg_replace( '/(\bAL\b)/i' , 'HW' , $address );
        $address = preg_replace( '/(\bPKY\b)/i' , 'PKWY' , $address );
        $address = preg_replace( '/(\bTRCE\b)/i' , 'TRACE' , $address );
        $address = preg_replace( '/(\bMLK JR\b)/i' , 'MARTIN LUTHER KING JR' , $address );
        $address = preg_replace( '/(\bMLK\b)/i' , 'MARTIN LUTHER KING JR' , $address );
        $address = preg_replace( '/(\bML KING\b)/i' , 'MARTIN LUTHER KING' , $address );
        $addressArray = explode( ' ' , $address );

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
        return implode( ' ' , $addressArray );
    }
}
