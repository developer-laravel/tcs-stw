<?php

namespace IIAB\StudentTransferBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use IIAB\StudentTransferBundle\Entity\Student;
use IIAB\StudentTransferBundle\Entity\Audit;

class GetZonedSchoolsCommand extends ContainerAwareCommand {

	protected function configure() {

		$this
			->setName( 'iiab:check:zoned-schools' )
			->setDescription( 'Checks the Zoned Schools of a Student' )
			->addArgument( 'studentID' , InputArgument::REQUIRED , 'Student ID that you want to check it against' )
			->addOption( 'request' , null ,  InputOption::VALUE_OPTIONAL , 'Request variable' )
		;
	}

	protected function execute( InputInterface $input , OutputInterface $output ) {

		$em = $this->getContainer()->get('doctrine')->getManager();
		$inputID = $input->getArgument( 'studentID' );
		$request = $input->getOption( 'request' );

		$student = $em->getRepository('IIABStudentTransferBundle:Student')->findOneBy( array(
			'studentID' => $inputID
		) );

		$result = $this->getSchools( $student , $request );

		$output->writeln( $result );
	}

	/**
	 * @param Student $student
	 * @param         $request
	 *
	 * @return array|bool list of all the schools assigned to a persons address.
	 */
	public function getSchools( Student $student , $request ) {

		$agent = '"Mozilla/5.0 (HSV-k12 Student Transfer Website) CURL/Request ImageInABox/1.0"';

		$address = ( isset( $request ) ) ? $request : $student->getAddress();
		$baseURL = "http://maps.Tuscaloosaal.gov/ArcGIS/rest/services/Layers/Addresses/MapServer/3/query?where=address_full+LIKE+";
		$endURL = "&returnCountOnly=false&returnIdsOnly=false&returnGeometry=false&outFields=elem_sch_distr%2Cmid_sch_distr%2Chigh_sch_distr&f=json"; //address%2Cstreet%2C

		//HSV City System only used Unit, it changes Apt and Suite over to Unit.
		//We need to do the same. PREG_REPLACE Replaces either words with Unit.
		$address = trim( $address );
		$address = preg_replace( '/(\bSuite\b)|(\bLot\b)|(\bApt\b)/i' , 'Unit' , $address );
		$address = preg_replace( "/(\.)|(,)|(')|(#)/" , '' , $address );
		$address = preg_replace( '/(\bDrive\b)/i' , 'DR' , $address );
		$address = preg_replace( '/(\bCr\b)/i' , 'CIR' , $address );
		$address = preg_replace( '/(\bmc)/i' , 'Mc ' , $address );
		$address = preg_replace( '/(\bBlvd\b)/i' , 'BLV' , $address );
		$address = preg_replace( '/(\bAvenue\b)/i' , 'AVE' , $address );
		$addressLookup = urlencode( "'" . $address . "%'" );

		$url = $baseURL . $addressLookup . $endURL;

		$curl = curl_init();
		curl_setopt( $curl , CURLOPT_URL , $url );
		curl_setopt( $curl , CURLOPT_SSL_VERIFYPEER , false );
		curl_setopt( $curl , CURLOPT_RETURNTRANSFER , true );
		curl_setopt( $curl , CURLOPT_HEADER , false );
		curl_setopt( $curl , CURLOPT_USERAGENT , $agent );
		$curl_response = curl_exec( $curl );
		$http_status = curl_getinfo( $curl , CURLINFO_HTTP_CODE );
		$curl_error = curl_error( $curl );
		curl_close( $curl );
		unset( $url , $curl );

		$studentType = $student->getStudentID();
		if( stripos( $studentType , 'TS' ) === false ) {
			$studentType = false;
		} else {
			$studentType = true;
		}

		if( $curl_error ) {
			//AuditCode: submission error because HSV City zoning was down
			$this->recordAudit( 24 , 0 , $student->getStudentID() ); //<<<< Move auditing down into getSchools function! All HSV Audit tracking should be done in getSchools function
			$request->getSession()->getFlashBag()->add( 'error' , $this->getContainer()->get('translator')->trans( 'errors.hsvCityError2' , array() , 'IIABStudentTransferBundle' ) );
			return false;
		}

		if( $http_status != 200 ) {
			//AuditCode: submission error because HSV City zoning was down
			$this->recordAudit( 24 , 0 , $student->getStudentID() ); //<<<< Move auditing down into getSchools function! All HSV Audit tracking should be done in getSchools function
			$request->getSession()->getFlashBag()->add( 'error' , $this->getContainer()->get('translator')->trans( 'errors.hsvCityError2' , array() , 'IIABStudentTransferBundle' ) );
			return false;
		}

		$curl_response = json_decode( $curl_response );

		if( !isset( $curl_response->features ) || !is_array( $curl_response->features ) ) {
			//AuditCode: submission error because HSV City zoning information was not found
			$this->recordAudit( 25 , 0 , $student->getStudentID() ); //<<<< Move auditing down into getSchools function! All HSV Audit tracking should be done in getSchools function
			if( $studentType ) {
				$request->getSession()->getFlashBag()->add( 'error' , $this->getContainer()->get( 'translator' )->trans( 'errors.hsvCityError1New' , array( '%link_start%' => '<a target="_blank" href="http://maps.hsvcity.com/address/Default.aspx?tab=select">' , '%link_end%' => '</a>' ) , 'IIABStudentTransferBundle' ) );
			} else {
				$request->getSession()->getFlashBag()->add( 'error' , $this->getContainer()->get( 'translator' )->trans( 'errors.hsvCityError1Current' , array() , 'IIABStudentTransferBundle' ) );
			}
			return false;
		}

		$schools = ( count( $curl_response->features ) == 1 ? $curl_response->features[0] : new \stdClass() );

		unset( $curl_error , $http_status );

		if( !isset( $schools->attributes ) ) {
			//This means the results from HSV City Zoning was empty. Lets try again with a different combo.

			$addressArray = explode( ' ' , urldecode( $address ) );

			//Does the index:1 contain an number street. Example: 8th Street.
			if( isset( $addressArray[1] ) && preg_match( '/\d+/' , $addressArray[1] , $matches ) !== false ) {
				//Index:1 contains an number. Need to replace.
				//Add in switch statement to handle converting 1st - 17th to First - Seventeenth
				switch( strtoupper( $addressArray [1]) ){
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

			//check the first two elements in address string against the HSV zoning site
			$secondTryAddressLookup = implode( ' ' , array_slice( $addressArray , 0 , 2 ) );
			$secondTryAddressLookup = urlencode( "'" . $secondTryAddressLookup . "%'" );

			$url = $baseURL . $secondTryAddressLookup . $endURL;

			$curl = curl_init();
			curl_setopt( $curl , CURLOPT_URL , $url );
			curl_setopt( $curl , CURLOPT_SSL_VERIFYPEER , false );
			curl_setopt( $curl , CURLOPT_RETURNTRANSFER , true );
			curl_setopt( $curl , CURLOPT_HEADER , false );
			curl_setopt( $curl , CURLOPT_USERAGENT , $agent );
			$curl_response_second = curl_exec( $curl );
			$http_status_second = curl_getinfo( $curl , CURLINFO_HTTP_CODE );
			$curl_error_second = curl_error( $curl );
			curl_close( $curl );
			unset( $url , $curl );

			if( $curl_error_second ) {
				//AuditCode: submission error because HSV City zoning was down
				$this->recordAudit( 24 , 0 , $student->getStudentID() ); //<<<< Move auditing down into getSchools function! All HSV Audit tracking should be done in getSchools function
				$request->getSession()->getFlashBag()->add( 'error' , $this->getContainer()->get('translator')->trans( 'errors.hsvCityError2' , array() , 'IIABStudentTransferBundle' ) );
				return false;
			}

			if( $http_status_second != 200 ) {
				//AuditCode: submission error because HSV City zoning was down
				$this->recordAudit( 24 , 0 , $student->getStudentID() ); //<<<< Move auditing down into getSchools function! All HSV Audit tracking should be done in getSchools function
				$request->getSession()->getFlashBag()->add( 'error' , $this->getContainer()->get('translator')->trans( 'errors.hsvCityError2' , array() , 'IIABStudentTransferBundle' ) );
				return false;
			}

			$curl_response_second = json_decode( $curl_response_second );

			if( !isset( $curl_response_second->features ) || !is_array( $curl_response_second->features ) ) {
				//AuditCode: submission error because HSV City zoning information was not found
				$this->recordAudit( 25 , 0 , $student->getStudentID() ); //<<<< Move auditing down into getSchools function! All HSV Audit tracking should be done in getSchools function
				if( $studentType )
					$request->getSession()->getFlashBag()->add( 'error' , $this->getContainer()->get('translator')->trans( 'errors.hsvCityError1New' , array( '%link_start%' => '<a target="_blank" href="http://maps.hsvcity.com/address/Default.aspx?tab=select">' , '%link_end%' => '</a>' ) , 'IIABStudentTransferBundle' ) );
				else
					$request->getSession()->getFlashBag()->add( 'error' , $this->getContainer()->get('translator')->trans( 'errors.hsvCityError1Current' , array() , 'IIABStudentTransferBundle' ) );
				return false;
			}

			if( count( $curl_response_second->features ) == 0 ) {
				////Did not find any matches...address must be incorrect. Need to error out.
				if( !isset( $schools->attributes ) ) {
					//AuditCode: submission error because HSV City zoning information returned empty
					$this->recordAudit( 20 , 0 , $student->getStudentID() ); //<<<< Move auditing down into getSchools function! All HSV Audit tracking should be done in getSchools function
					if( $studentType )
						$request->getSession()->getFlashBag()->add( 'error' , $this->getContainer()->get('translator')->trans( 'errors.hsvCityError1New' , array( '%link_start%' => '<a target="_blank" href="http://maps.hsvcity.com/address/Default.aspx?tab=select">' , '%link_end%' => '</a>' ) , 'IIABStudentTransferBundle' ) );
					else
						$request->getSession()->getFlashBag()->add( 'error' , $this->getContainer()->get('translator')->trans( 'errors.hsvCityError1Current' , array() , 'IIABStudentTransferBundle' ) );
					return false;
				}

			} else {
				if( count( $curl_response_second->features ) == 1 ) {
					//Only found one match, this is our address!
					$schools = $curl_response_second->features[0];

				} else {
					if(  count( $curl_response_second->features ) > count( $curl_response->features ) ) {
						//if response is greater than one we found multiple address. Need to try for a third time.
						//This time with part front (first two items in the address) and part back (last two items in the address).
						$thirdTryAddressLookup = implode( ' ' , array_slice( $addressArray , 0 , 2 ) ) . '%' . implode( ' ' , array_slice( $addressArray , -2 , 2 ) );
						$thirdTryAddressLookup = urlencode( "'" . $thirdTryAddressLookup . "'" );

						$url = $baseURL . $thirdTryAddressLookup . $endURL;

						$curl = curl_init();
						curl_setopt( $curl , CURLOPT_URL , $url );
						curl_setopt( $curl , CURLOPT_SSL_VERIFYPEER , false );
						curl_setopt( $curl , CURLOPT_RETURNTRANSFER , true );
						curl_setopt( $curl , CURLOPT_HEADER , false );
						curl_setopt( $curl , CURLOPT_USERAGENT , $agent );
						$curl_response_third = curl_exec( $curl );
						$http_status_third = curl_getinfo( $curl , CURLINFO_HTTP_CODE );
						$curl_error_third = curl_error( $curl );
						curl_close( $curl );
						unset( $url , $curl );

						if( $curl_error_third ) {
							//AuditCode: submission error because HSV City zoning was down
							$this->recordAudit( 24 , 0 , $student->getStudentID() ); //<<<< Move auditing down into getSchools function! All HSV Audit tracking should be done in getSchools function
							$request->getSession()->getFlashBag()->add( 'error' , $this->getContainer()->get('translator')->trans( 'errors.hsvCityError2' , array() , 'IIABStudentTransferBundle' ) );
							return false;
						}

						if( $http_status_third != 200 ) {
							//AuditCode: submission error because HSV City zoning was down
							$this->recordAudit( 24 , 0 , $student->getStudentID() ); //<<<< Move auditing down into getSchools function! All HSV Audit tracking should be done in getSchools function
							$request->getSession()->getFlashBag()->add( 'error' , $this->getContainer()->get('translator')->trans( 'errors.hsvCityError2' , array() , 'IIABStudentTransferBundle' ) );
							return false;
						}

						$curl_response_third = json_decode( $curl_response_third );

						if( count( $curl_response_third->features ) == 1 ) {
							//Found the address!!
							$schools = $curl_response_third->features[0];
						} else {
							//Error out!!! Can't seem to find address still!!!
							//Return error.
							if( !isset( $schools->attributes ) ) {
								//AuditCode: submission error because HSV City zoning information returned empty
								$this->recordAudit( 20 , 0 , $student->getStudentID() ); //<<<< Move auditing down into getSchools function! All HSV Audit tracking should be done in getSchools function
								if( $studentType )
									$request->getSession()->getFlashBag()->add( 'error' , $this->getContainer()->get('translator')->trans( 'errors.hsvCityError1New' , array( '%link_start%' => '<a target="_blank" href="http://maps.hsvcity.com/address/Default.aspx?tab=select">' , '%link_end%' => '</a>' ) , 'IIABStudentTransferBundle' ) );
								else
									$request->getSession()->getFlashBag()->add( 'error' , $this->getContainer()->get('translator')->trans( 'errors.hsvCityError1Current' , array() , 'IIABStudentTransferBundle' ) );
								return false;
							}
						}


					} else {
						//Return error.
						if( !isset( $schools->attributes ) ) {
							//AuditCode: submission error because HSV City zoning information returned empty
							$this->recordAudit( 20 , 0 , $student->getStudentID() ); //<<<< Move auditing down into getSchools function! All HSV Audit tracking should be done in getSchools function
							if( $studentType )
								$request->getSession()->getFlashBag()->add( 'error' , $this->getContainer()->get('translator')->trans( 'errors.hsvCityError1New' , array( '%link_start%' => '<a target="_blank" href="http://maps.hsvcity.com/address/Default.aspx?tab=select">' , '%link_end%' => '</a>' ) , 'IIABStudentTransferBundle' ) );
							else
								$request->getSession()->getFlashBag()->add( 'error' , $this->getContainer()->get('translator')->trans( 'errors.hsvCityError1Current' , array() , 'IIABStudentTransferBundle' ) );
							return false;
						}

					}
				}
			}
		}

		if( !isset( $schools->attributes ) ) {
			//AuditCode: submission error because HSV City zoning information returned empty
			$this->recordAudit( 20 , 0 , $student->getStudentID() ); //<<<< Move auditing down into getSchools function! All HSV Audit tracking should be done in getSchools function
			if( $studentType )
				$request->getSession()->getFlashBag()->add( 'error' , $this->getContainer()->get('translator')->trans( 'errors.hsvCityError1New' , array( '%link_start%' => '<a target="_blank" href="http://maps.hsvcity.com/address/Default.aspx?tab=select">' , '%link_end%' => '</a>' ) , 'IIABStudentTransferBundle' ) );
			else
				$request->getSession()->getFlashBag()->add( 'error' , $this->getContainer()->get('translator')->trans( 'errors.hsvCityError1Current' , array() , 'IIABStudentTransferBundle' ) );
			return false;
		}

		$schools = $schools->attributes;

		if( !isset( $schools->elem_sch_distr ) || !isset( $schools->mid_sch_distr ) || !isset( $schools->high_sch_distr ) ) {
			//AuditCode: submission error because HSV City zoning information was not found
			$this->recordAudit( 25 , 0 , $student->getStudentID() ); //<<<< Move auditing down into getSchools function! All HSV Audit tracking should be done in getSchools function
			if( $studentType )
				$request->getSession()->getFlashBag()->add( 'error' , $this->getContainer()->get('translator')->trans( 'errors.hsvCityError1New' , array( '%link_start%' => '<a target="_blank" href="http://maps.hsvcity.com/address/Default.aspx?tab=select">' , '%link_end%' => '</a>' ) , 'IIABStudentTransferBundle' ) );
			else
				$request->getSession()->getFlashBag()->add( 'error' , $this->getContainer()->get('translator')->trans( 'errors.hsvCityError1Current' , array() , 'IIABStudentTransferBundle' ) );
			return false;
		}

		$returnSchools = array();
		foreach( $schools as $key => $school ) {
			$title = '';
			if( $key == 'elem_sch_distr' )
				$title = $this->getContainer()->get('translator')->trans( 'base.elementary' , array() , 'IIABStudentTransferBundle') ;
			if( $key == 'mid_sch_distr' )
				$title = $this->getContainer()->get('translator')->trans( 'base.middle' , array() , 'IIABStudentTransferBundle') ;
			if( $key == 'high_sch_distr' )
				$title = $this->getContainer()->get('translator')->trans( 'base.high' , array() , 'IIABStudentTransferBundle') ;

			$returnSchools[strtoupper( $title )] = strtoupper( $school );
		}
		return $returnSchools;
	}

	/**
	 * @param int $auditCode
	 * @param int $submission
	 * @param int $studentID
	 *
	 * @return void
	 */
	private function recordAudit( $auditCode = 0 , $submission = 0 , $studentID = 0 ) {

		$em = $this->getContainer()->get('doctrine.orm.entity_manager');
		$user = $this->getContainer()->get( 'security.context' )->getToken()->getUser();

		$auditCode = $em->getRepository( 'IIABStudentTransferBundle:AuditCode' )->find( $auditCode );

		$audit = new Audit();
		$audit->setAuditCodeID( $auditCode );
		$audit->setIpaddress( '::1' );
		$audit->setSubmissionID( $submission );
		$audit->setStudentID( $studentID );
		$audit->setTimestamp( new \DateTime() );
		$audit->setUserID( ( $user == 'anon.' ? 0 : $user->getId() ) );

		$em->persist( $audit );
		$em->flush();
	}

}