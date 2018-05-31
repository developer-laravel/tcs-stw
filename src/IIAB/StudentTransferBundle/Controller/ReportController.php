<?php

namespace IIAB\StudentTransferBundle\Controller;

use Exporter\Handler;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Exporter\Source\ArraySourceIterator;
use Exporter\Writer\CsvWriter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller {

	/**
	 * This is to handle the index of the reporting system.
	 *
	 * @Route("/admin/report" , name="stw_admin_report")
	 * @Template()
	 * @return \Symfony\Component\HttpFoundation\Response;
	 */
	public function reportAction() {

		//$this->awardedReport();
		//$this->acceptedReport();
		//$this->deniedReport();

		return $this->render( '@IIABStudentTransfer/Report/report.html.twig' );
	}

	/**
	 * Report builder for the number of Application Request by school to and from.
	 * M2M Application and Offered Counts
	 *
	 * @Route( "/admin/run-lottery/report-1" , name="stw_report-1" )
	 * @Template("IIABStudentTransferBundle:Report:report.html.twig")
	 *
	 * @param Request $request
	 *
	 * @return array|Response
	 */
	public function applicationRequestsBySchoolToAndFromReportAction( Request $request ) {

		$admin_pool = $this->get( 'sonata.admin.pool' );

		$form = $this->createFormBuilder()
			->setAction( $this->generateUrl( 'stw_report-1' ) )
			->setMethod( 'post' )
			->add( 'enrollmentPeriod' , 'entity' , array(
				'class' => 'IIABStudentTransferBundle:OpenEnrollment' ,
				'placeholder' => 'Select an Enrollment Period'
			) )
			->add( 'submit' , 'submit' )
			->getForm();;
		$form->handleRequest( $request );

		if( $request->getMethod() == 'POST' ) {
			$data = $form->getData();

			$submissions = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Submission' )->createQueryBuilder( 's' )
				->andWhere( 's.enrollmentPeriod = :enrollment' )
				->andWhere( 's.formID = :form' )
				->andWhere( 's.submissionStatus != :inactive AND s.submissionStatus != :overwritten' )
				->setParameters( array(
					':enrollment' => $data['enrollmentPeriod'] ,
					':form' => 1 ,
					':inactive' => 8 ,
					':overwritten' => 6 ,
				) )
				->getQuery()
				->getResult();

			$report = array(
				'FirstChoice' => array() ,
				'SecondChoice' => array() ,
				'FirstChoiceApproved' => array() ,
				'SecondChoiceApproved' => array() ,
			);
			foreach( $submissions as $submission ) {
				$currentSchool = $submission->getCurrentSchool();
				$currentSchool = ( !empty( $currentSchool ) ? $currentSchool : 'Not Provided' );

				if( !isset( $report['FirstChoice'][$currentSchool][$submission->getFirstChoice()->__toString()] ) ) {
					$report['FirstChoice'][$currentSchool][$submission->getFirstChoice()->__toString()] = 0;
				}

				if( $submission->getSecondChoice() != null ) {
					if( !isset( $report['SecondChoice'][$currentSchool][$submission->getSecondChoice()->__toString()] ) ) {
						$report['SecondChoice'][$currentSchool][$submission->getSecondChoice()->__toString()] = 0;
					}
				}

				$report['FirstChoice'][$currentSchool][$submission->getFirstChoice()->__toString()]++;
				if( $submission->getSecondChoice() != null ) {
					$report['SecondChoice'][$currentSchool][$submission->getSecondChoice()->__toString()]++;
				}


				//Non Awarded were not approved. So we only need to record the roundExpired and Awarded School ones.
				if( $submission->getAwardedSchoolID() != null && $submission->getRoundExpires() != null ) {
					if( $submission->getFirstChoice() == $submission->getAwardedSchoolID() ) {
						if( !isset( $report['FirstChoiceApproved'][$submission->getFirstChoice()->__toString()] ) ) {
							$report['FirstChoiceApproved'][$submission->getFirstChoice()->__toString()] = 0;
						}
						$report['FirstChoiceApproved'][$submission->getFirstChoice()->__toString()]++;

					} elseif( $submission->getFirstChoice() != $submission->getSecondChoice() && $submission->getSecondChoice() == $submission->getAwardedSchoolID() ) {
						if( $submission->getSecondChoice() != null ) {
							if( !isset( $report['SecondChoiceApproved'][$submission->getSecondChoice()->__toString()] ) ) {
								$report['SecondChoiceApproved'][$submission->getSecondChoice()->__toString()] = 0;
							}
							$report['SecondChoiceApproved'][$submission->getSecondChoice()->__toString()]++;
						}
					}
				}
				ksort( $report['FirstChoice'][$currentSchool] );
				if( isset( $report['SecondChoice'][$currentSchool] ) ) {
					ksort( $report['SecondChoice'][$currentSchool] );
				}
				ksort( $report['FirstChoiceApproved'] );
				ksort( $report['SecondChoiceApproved'] );
			}
			ksort( $report['FirstChoice'] );
			ksort( $report['SecondChoice'] );
			//echo '<pre>' . print_r( $report , true ) . '</pre>';

			$columnArray = array();
			$rows = array();
			foreach( $report as $count => $columns ) {
				if( $count != 'FirstChoice' && $count != 'SecondChoice' ) {
					continue;
				}

				$rows[$count] = array();
				foreach( $columns as $column => $awarded ) {
					if( !in_array( $column , $columnArray ) ) {
						$columnArray[] = $column;
					}

					foreach( $awarded as $name => $school ) {
						if( !in_array( $name , $rows[$count] ) ) {
							$rows[$count][] = $name;
						}
					}
				}
				sort( $rows[$count] );
			}
			//echo '<pre>' . print_r( $rows , true ) . '</pre>';
			//echo '<pre>' . print_r( $columnArray , true ) . '</pre>';

			$excelArray = array();
			$index = 1;

			foreach( $rows as $choice => $schools ) {

				if( $choice == 'FirstChoice' ) {
					$excelArray[$index]['School'] = $choice;
					$index++;
					$excelArray[$index]['School'] = '';
					foreach( $columnArray as $columnName ) {
						$excelArray[$index][$columnName] = $columnName;
					}
					$excelArray[$index]['Total'] = 'Total';
					$excelArray[$index]['Approved First Choice'] = 'Approved First Choice';
					$excelArray[$index]['Approved Second Choice'] = 'Approved Second Choice';
				} else {
					$excelArray[$index]['School'] = '';
					$index++;
					$excelArray[$index]['School'] = $choice;
				}
				$index++;

				foreach( $rows[$choice] as $name ) {
					$excelArray[$index]['School'] = $name;
					$total = 0;
					foreach( $columnArray as $columnName ) {
						$excelArray[$index][$columnName] = 0;
						if( isset( $report[$choice][$columnName][$name] ) ) {
							$excelArray[$index][$columnName] = $report[$choice][$columnName][$name];
							$total += $report[$choice][$columnName][$name];
						}
					}
					if( $choice == 'FirstChoice' ) {
						$excelArray[$index]['Total'] = $total;
						$excelArray[$index]['Approved First Choice'] = 0;
						$excelArray[$index]['Approved Second Choice'] = '';
						if( isset( $report['FirstChoiceApproved'][$name] ) ) {
							$excelArray[$index]['Approved First Choice'] = $report['FirstChoiceApproved'][$name];
						}
					}

					if( $choice == 'SecondChoice' ) {
						$excelArray[$index]['Total'] = $total;
						$excelArray[$index]['Approved First Choice'] = '';
						$excelArray[$index]['Approved Second Choice'] = 0;
						if( isset( $report['SecondChoiceApproved'][$name] ) ) {
							$excelArray[$index]['Approved Second Choice'] = $report['SecondChoiceApproved'][$name];
						}
					}

					$index++;
				}
			}
			//echo '<pre>' . print_r( $excelArray , true ) . '</pre>';

			$source = new ArraySourceIterator( $excelArray );

			$content = $this->get( 'kernel' )->getRootDir() . '/../web/uploads/submission-approved-m-2-m.csv';

			if( file_exists( $content ) ) {
				unlink( $content );
			}

			$writer = new CsvWriter( $content , $delimiter = ",", $enclosure = "\"", $escape = "\\" , false );

			Handler::create( $source , $writer )->export();

			$content = file_get_contents( $content );

			$response = new Response();

			$response->headers->set( 'Content-Type' , 'text/csv' );
			$response->headers->set( 'Content-Disposition' , 'attachment;filename="submission-approved-m-2-m.csv"' );

			$response->setContent( $content );
			unlink( $this->get( 'kernel' )->getRootDir() . '/../web/uploads/submission-approved-m-2-m.csv' );
			return $response;
		}

		return array(
			'admin_pool' => $admin_pool ,
			'form' => $form->createView()
		);

	}

	/**
	 * Report builder for the transfer outcomes of awarded spots with to and from school information.
	 *
	 * @Route( "/admin/reports/transfer-outcome-report" , name="transfer-outcome-report" )
	 * @Template("IIABStudentTransferBundle:Report:report.html.twig")
	 *
	 * @param Request $request
	 *
	 * @return array|Response
	 */
	public function outcomeReportAction( Request $request ) {

		$admin_pool = $this->get( 'sonata.admin.pool' );

		$form = $this->createFormBuilder()
			->setAction( $this->generateUrl( 'transfer-outcome-report' ) )
			->setMethod( 'post' )
			->add( 'enrollmentPeriod' , 'entity' , array(
				'class' => 'IIABStudentTransferBundle:OpenEnrollment' ,
				'placeholder' => 'What enrollment period do you want to run a report on?' ,
			) )
			->add( 'submissionStatus' , 'choice' , array(
				'choices' => array(
					'offered' => 'Offered' ,
					'offered and accepted' => 'Offered and Accepted',
					'offered and declined' => 'Offered and Declined'
				) ,
				'placeholder' => 'What outcome submission status would you to include in the report?'
			) )
			->add( 'formType' , 'entity' , array(
				'class' => 'IIABStudentTransferBundle:Form' ,
				'placeholder' => 'What transfer from would you to run the report for?'
			) )
			->add( 'submit' , 'submit' )
			->getForm();;
		$form->handleRequest( $request );

		if( $request->getMethod() == 'POST' ) {
			$data = $form->getData();

			if( $data['submissionStatus'] == 'offered' ) {

				$submissions = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Submission' )->createQueryBuilder( 's' )
					->andWhere( 's.enrollmentPeriod = :enrollment' )
					->andWhere( 's.formID = :form' )
					->andWhere( 's.submissionStatus = :offered OR s.submissionStatus = :accepted OR s.submissionStatus = :declined' )
					->setParameters( array(
						':enrollment' => $data['enrollmentPeriod'] ,
						':form' => $data['formType'] ,
						':offered' => 2 ,
						':accepted' => 3 ,
						':declined' => 4
					) )
					->getQuery()
					->getResult();
			} elseif( $data['submissionStatus'] == 'offered and declined' ) {

				$submissions = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Submission' )->createQueryBuilder( 's' )
					->andWhere( 's.enrollmentPeriod = :enrollment' )
					->andWhere( 's.formID = :form' )
					->andWhere( 's.submissionStatus = :declined' )
					->setParameters( array(
						':enrollment' => $data['enrollmentPeriod'] ,
						':form' => $data['formType'] ,
						':declined' => 4
					) )
					->getQuery()
					->getResult();
			} else {
				$submissions = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Submission' )->createQueryBuilder( 's' )
					->andWhere( 's.enrollmentPeriod = :enrollment' )
					->andWhere( 's.formID = :form' )
					->andWhere( 's.submissionStatus = :accepted' )
					->setParameters( array(
						':enrollment' => $data['enrollmentPeriod'] ,
						':form' => $data['formType'] ,
						':accepted' => 3
					) )
					->getQuery()
					->getResult();
			}

			$report = array();
			foreach( $submissions as $submission ) {
				$currentSchool = $submission->getCurrentSchool();
				$currentSchool = ( !empty( $currentSchool ) ? $currentSchool : 'Not Provided' );

				if( !isset( $report[$currentSchool][$submission->getAwardedSchoolID()->__toString()] ) ) {
					$report[$currentSchool][$submission->getAwardedSchoolID()->__toString()] = 0;
				}

				$report[$currentSchool][$submission->getAwardedSchoolID()->__toString()]++;

				ksort( $report[$currentSchool] );
			}
			ksort( $report );

			//echo '<pre>' . print_r( $report , true ) . '</pre>';

			//Building the column and row headers
			$currSchoolColHeader = array();
			$awardedSchoolRowHeader = array();

			foreach( $report as $currSchoolName => $awardedSchoolArray ) {
				if( !in_array( $currSchoolName , $currSchoolColHeader ) ) {
					$currSchoolColHeader[] = $currSchoolName;
				}

				foreach( $awardedSchoolArray as $awardedSchoolName => $count ) {
					if( !in_array( $awardedSchoolName , $awardedSchoolRowHeader ) ) {
						$awardedSchoolRowHeader[] = $awardedSchoolName;
					}
				}
			}
			sort( $awardedSchoolRowHeader );
			//echo '<pre>' . print_r( $rows , true ) . '</pre>';
			//echo '<pre>' . print_r( $columnArray , true ) . '</pre>';

			$excelArray = array();
			$index = 1;

			$excelArray[$index]['School'] = '';
			$index++;
			$excelArray[$index]['School'] = '';
			foreach( $currSchoolColHeader as $currentSchoolName ) {
				$excelArray[$index][$currentSchoolName] = $currentSchoolName;
			}
			$excelArray[$index]['Total'] = 'Total';
			$index++;
			foreach( $awardedSchoolRowHeader as $awardedSchoolName ) {
				$excelArray[$index]['School'] = $awardedSchoolName;
				$total = 0;
				foreach( $currSchoolColHeader as $columnName ) {
					$excelArray[$index][$columnName] = 0;
					if( isset( $report[$columnName][$awardedSchoolName] ) ) {
						$excelArray[$index][$columnName] = $report[$columnName][$awardedSchoolName];
						$total += $report[$columnName][$awardedSchoolName];
					}
				}
				$excelArray[$index]['Total'] = $total;

				$index++;
			}

			//echo '<pre>' . print_r( $excelArray , true ) . '</pre>';

			$source = new ArraySourceIterator( $excelArray );

			$content = $this->get( 'kernel' )->getRootDir() . '/../web/uploads/transfer-outcome-report.csv';

			if( file_exists( $content ) ) {
				unlink( $content );
			}

			$writer = new CsvWriter( $content , $delimiter = ",", $enclosure = "\"", $escape = "\\" , false );

			Handler::create( $source , $writer )->export();

			$content = file_get_contents( $content );

			$response = new Response();

			$response->headers->set( 'Content-Type' , 'text/csv' );
			$response->headers->set( 'Content-Disposition' , 'attachment;filename="transfer-outcome-report.csv"' );

			$response->setContent( $content );
			unlink( $this->get( 'kernel' )->getRootDir() . '/../web/uploads/transfer-outcome-report.csv' );
			return $response;
		}

		return array(
			'admin_pool' => $admin_pool ,
			'form' => $form->createView()
		);

	}

	/**
	 * Report Builder for the number of remaining slots after a lottery has ran.
	 *
	 * @Route( "/admin/reports/remaining-slot-report" , name="remaining-slot-report" )
	 * @Template("IIABStudentTransferBundle:Report:report.html.twig")
	 *
	 * @param Request $request
	 *
	 * @return array|Response
	 */
	public function remainingSlotsReport( Request $request ) {
		$admin_pool = $this->get( 'sonata.admin.pool' );

		$form = $this->createFormBuilder()
			->setAction( $this->generateUrl( 'remaining-slot-report' ) )
			->setMethod( 'post' )
			->add( 'enrollmentPeriod' , 'entity' , array(
				'class' => 'IIABStudentTransferBundle:OpenEnrollment' ,
				'placeholder' => 'What enrollment period do you want to run a report on?' ,
			) )
			->add( 'submit' , 'submit' )
			->getForm();
		$form->handleRequest( $request );

		if( $request->getMethod() == 'POST' ) {
			$data = $form->getData();

			$slots = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Slotting' )->createQueryBuilder( 's' )
				->leftJoin( 'IIABStudentTransferBundle:ADM' , 'adm' , 'WITH' , 's.schoolID = adm.schoolID AND s.grade = adm.grade AND s.enrollmentPeriod = adm.enrollmentPeriod' )
				->select( 's.schoolID' , 's.grade' , 's.availableSlots' , 'adm.hsvCityName' )
				->andWhere( 's.enrollmentPeriod = :enrollment' )
				->setParameters( array(
					':enrollment' => $data['enrollmentPeriod'] ,
				) )
				->orderBy( 'adm.hsvCityName' , 'ASC' )
				->addOrderBy( 'adm.grade' , 'ASC' )
				->getQuery()
				->getResult();

			//echo '<pre>' . print_r( $slots->getDQL() , true ) . '</pre>';
			//$slots->getResult();

			//echo '<pre>' . print_r( $slots , true ) . '</pre>';
			$excelArray = array();
			$index = 1;
			$excelArray[$index]['School'] = 'Remaining Slots Report';
			$excelArray[$index]['Slot'] = '';
			$index++;
			$excelArray[$index]['School'] = 'School - Grade';
			$excelArray[$index]['Slot'] = 'Remaining Slots';
			$index++;

			foreach( $slots as $slot ) {
				if( empty( $slot['hsvCityName'] ) )
					continue;
				$excelArray[$index]['School'] = $slot['hsvCityName'] . ' - Grade: ' . $slot['grade'];
				$excelArray[$index]['Slot'] = $slot['availableSlots'];
				$index++;
			}

			$source = new ArraySourceIterator( $excelArray );

			$content = $this->get( 'kernel' )->getRootDir() . '/../web/uploads/remaining-slots-report.csv';

			if( file_exists( $content ) ) {
				unlink( $content );
			}

			$writer = new CsvWriter( $content , $delimiter = ",", $enclosure = "\"", $escape = "\\" , false );

			Handler::create( $source , $writer )->export();

			$content = file_get_contents( $content );

			$response = new Response();

			$response->headers->set( 'Content-Type' , 'text/csv' );
			$response->headers->set( 'Content-Disposition' , 'attachment;filename="remaining-slots-report.csv"' );

			$response->setContent( $content );
			unlink( $this->get( 'kernel' )->getRootDir() . '/../web/uploads/remaining-slots-report.csv' );
			return $response;
		}

		return array(
			'admin_pool' => $admin_pool ,
			'form' => $form->createView()
		);
	}

	/**
	 * @param Request $request
	 *
	 * @Route("/admin/reports/export" , name="stw_admin_report_export" )
	 * @Template( "IIABStudentTransferBundle:Report:report.html.twig")
	 *
	 * @return \Symfony\Component\HttpFoundation\Response;
	 */
	public function SubmissionReportExportAction( Request $request ) {
		$admin_pool = $this->get('sonata.admin.pool');

		$content = $this->get('kernel')->getRootDir() . '/../web/uploads/submission-export.csv';

		if( file_exists( $content ) )
			unlink( $content );

		$form = $this->createFormBuilder()
			->setAction( $this->generateUrl( 'stw_admin_report_export' ) )
			->setMethod( 'post' )
			->add( 'enrollmentPeriod' , 'entity' , array(
				'class'	=> 'IIABStudentTransferBundle:OpenEnrollment',
				'required' => false,
				'placeholder'	=> 'All Enrollment Period'
			) )
			->add( 'form' , 'entity' , array(
				'class'	=> 'IIABStudentTransferBundle:Form',
				'required' => false,
				'placeholder'	=> 'All Forms'
			) )
			->add( 'status' , 'entity' , array(
				'class'	=> 'IIABStudentTransferBundle:SubmissionStatus',
				'required' => false,
				'placeholder'	=> 'All Statuses'
			) )
			->add( 'submit' , 'submit' )
			->getForm();
		;
		$form->handleRequest( $request );

		if( $request->getMethod() == 'POST' ) {
			if( $form->isValid() ) {
				$data = $form->getData();
				$source = $this->getDoctrine()->getRepository('IIABStudentTransferBundle:Submission')->createQueryBuilder('s');

				if( isset( $data['enrollmentPeriod'] ) && !empty( $data['enrollmentPeriod'] ) ) {
					$source->andWhere('s.enrollmentPeriod = :enrollment')->setParameter( 'enrollment' , $data['enrollmentPeriod']->getId() );
				}
				if( isset( $data['status'] ) && !empty( $data['status'] ) ) {
					$source->andWhere( 's.submissionStatus = :status' )->setParameter( 'status' , $data['status']->getId() );
				}
				if( isset( $data['form'] ) && !empty( $data['form'] ) ) {
					$source->andWhere( 's.formID = :form' )->setParameter( 'form' , $data['form']->getId() );
				}
				$source = $source->getQuery()->getResult();

				$excelArray = array();
				$index = 0;
				/**
				 * @var \IIAB\StudentTransferBundle\Entity\Submission $submission
				 */
				foreach( $source as $submission ) {
					if( $submission->getGrade() == 99 ) {
						$grade = sprintf( '%1$02d' , 0 );
					} else {
						$grade = sprintf( '%1$02d' , abs( $submission->getGrade() ) + 1 );
					}
					$awardedSchoolName = '';
					if( $submission->getAwardedSchoolID() != null ) {
						/*if( $submission->getAwardedSchoolID()->getId() == $submission->getFirstChoice()->getId() ) {
							$awardedSchoolName = $submission->getFirstChoice()->__toString();
						}
						if( $submission->getAwardedSchoolID()->getId() == $submission->getSecondChoice()->getId() ) {
							$awardedSchoolName = $submission->getSecondChoice()->__toString();
						}*/
						$awardedSchoolName = $submission->getAwardedSchoolID()->__toString();
					}

					$excelArray[$index] = array(
						'Submission Date'		=> $submission->getSubmissionDateTime()->format( 'Y-m-d H:i'),
						'Confirmation Number'	=> $submission->getConfirmationNumber(),
						'Enrollment Period'		=> $submission->getEnrollmentPeriod(),
						'Form'					=> $submission->getFormID(),
						'Student ID'			=> $submission->getStudentID(),
						'First Name'			=> $submission->getFirstName(),
						'Last Name'				=> $submission->getLastName(),
						'Date of Birth'			=> $submission->getDob(),
						'Address'				=> $submission->getAddress(),
						'City'					=> $submission->getCity(),
						'Zip'					=> $submission->getZip(),
						'Email Address'			=> $submission->getEmail(),
						'Status'				=> $submission->getSubmissionStatus()->__toString(),
						'Grade'					=> $grade,
						'Race'					=> $submission->getRace(),
						'Current School'        => $submission->getCurrentSchool(),
						'Zoned Schools'			=> $submission->getHsvZonedSchoolsString(),
						'First Choice'			=> $submission->getFirstChoice(),
						'Second Choice'			=> $submission->getSecondChoice(),
						'Awarded School'		=> $awardedSchoolName,
						'Submission was processed' => $submission->getAfterLotterySubmissionReportStyle(),
						'Employee ID'			=> '',
						'Employee First Name'	=> '',
						'Employee Last Name'	=> '',
						'Employee Location'		=> '',
						'SPED Student ID'		=> '',
						'SPED Current School'	=> '',
						'SPED Current Grade'	=> '',
						'Sibling Status'		=> '',
						'Transfer Reason'		=> '',
					);

					if( $submission->getFormID()->getId() == 2 ) {
						//Added columns for Personnel Transfer
						$newColumns = array(
							'Employee ID'			=> $submission->getEmployeeID(),
							'Employee First Name'	=> $submission->getEmployeeFirstName(),
							'Employee Last Name'	=> $submission->getEmployeeLastName(),
							'Employee Location'		=> $submission->getEmployeeLocation(),
						);
						$excelArray[$index] = array_merge( $excelArray[$index] , $newColumns );
					}

					if( $submission->getFormID()->getId() == 3 ) {
						//Added columns for Special Education Sibling Transfer
						$submissionData = $submission->getSubmissionData();
						$newColumns = array();
						foreach( $submissionData as $submissionDataObject ) {
							$newColumns[$submissionDataObject->getMetaKey()] = $submissionDataObject->getMetaValue();
						}

						$excelArray[$index] = array_merge( $excelArray[$index] , $newColumns );
					}
					if( $submission->getFormID()->getId() == 4 ) {
						//Added columns for Superintendent Assignment Transfer
						$submissionData = $submission->getSubmissionData();
						$newColumns = array();
						foreach( $submissionData as $submissionDataObject ) {
							$newColumns[$submissionDataObject->getMetaKey()] = $submissionDataObject->getMetaValue();
						}

						$excelArray[$index] = array_merge( $excelArray[$index] , $newColumns );

					}

					$index++;
				}

				$source = new ArraySourceIterator( $excelArray );

				$writer = new CsvWriter( $content );

				Handler::create( $source , $writer )->export();

				$content = file_get_contents( $content );

				$response = new Response();

				$response->headers->set('Content-Type', 'text/csv');
				$response->headers->set('Content-Disposition', 'attachment;filename="submission-export.csv"');

				$response->setContent($content);
				unlink( $this->get('kernel')->getRootDir() . '/../web/uploads/submission-export.csv' );
				return $response;
			}
		}
		return array(
			'admin_pool' => $admin_pool,
			'form' => $form->createView()
		);
	}

	/**
	 * Wait List and New Submissions Report.
	 * @Route("/admin/reports/wait-list-new-submission-export" , name="stw_admin_report_wait_list_new_submission_export" )
	 * @Template( "IIABStudentTransferBundle:Report:report.html.twig")
	 *
	 * @param Request $request
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function waitListAndNewApplicationReportAction ( Request $request ) {

		$admin_pool = $this->get('sonata.admin.pool');

		$content = $this->get('kernel')->getRootDir() . '/../web/uploads/submission-wait-list-export.csv';

		if( file_exists( $content ) ) {
			unlink( $content );
		}

		$form = $this->createFormBuilder()
			->add( 'enrollmentPeriod' , 'entity' , array(
				'class'	=> 'IIABStudentTransferBundle:OpenEnrollment',
				'required' => false,
				'placeholder'	=> 'All Enrollment Period'
			) )
			->add( 'submit' , 'submit' )
			->getForm();

		$form->handleRequest( $request );

		if( $form->isValid() ) {

			$data = $form->getData();

			$source = $this->getDoctrine()->getRepository('IIABStudentTransferBundle:Submission')->createQueryBuilder('s');

			if( isset( $data['enrollmentPeriod'] ) && !empty( $data['enrollmentPeriod'] ) ) {
				$source->andWhere('s.enrollmentPeriod = :enrollment')->setParameter( 'enrollment' , $data['enrollmentPeriod']->getId() );
			}
			$source->andWhere( 's.submissionStatus = :status' )->setParameter( 'status' , 10 ); //WaitList Submissions First
			$source->andWhere( 's.afterLotterySubmission = 0' ); //Only Lottery Submissions.
			$source->orderBy( 's.lotteryNumber' , 'ASC' );
			$source = $source->getQuery()->getResult();

			$excelArray = array();
			$index = 0;

			$submissionLotteryArray = array(
				'Priority' => array() ,
				'Expiring' => array() ,
				'Non-Priority' => array() ,
			);

			if( count( $source ) > 0 ) {
				/** @var \IIAB\StudentTransferBundle\Entity\Submission $submission */
				foreach( $source as $submission ) {
					try {
						/** @var \IIAB\StudentTransferBundle\Entity\Expiration $expiring */
						$expiring = $this->getDoctrine()->getRepository('IIABStudentTransferBundle:Expiration')->findOneBy( [ 'studentID' => $submission->getStudentID() , 'openEnrollment' => $data['enrollmentPeriod'] ] );

						if( $expiring == null ) {

							if( isset( $submissionLotteryArray['Non-Priority'][$submission->getLotteryNumber()] ) ) {
								throw new \Exception( 'Duplicate Lottery Numbers' , 100 );
							}

							$submissionLotteryArray['Non-Priority'][$submission->getLotteryNumber()] = $submission;
						} else {
							if( $expiring->getExpiring() == 1 ) {

								if( isset( $submissionLotteryArray['Priority'][$submission->getLotteryNumber()] ) ) {
									throw new \Exception( 'Duplicate Lottery Numbers' , 100 );
								}

								$firstChoiceHSVName = strtoupper( $submission->getFirstChoice()->getSchoolName() );
								$expireRequiredSchool = strtoupper( $this->formatName( $expiring->getFeederSchool() ) );

								if( $firstChoiceHSVName == $expireRequiredSchool ) {
									$submissionLotteryArray['Priority'][$submission->getLotteryNumber()] = $submission;
								} else {
									$submissionLotteryArray['Non-Priority'][$submission->getLotteryNumber()] = $submission;
									//$submissionLotteryArray['Expiring'][$submission->getLotteryNumber()] = $submission;
								}
							} else {
								$submissionLotteryArray['Non-Priority'][$submission->getLotteryNumber()] = $submission;
							}
						}

					} catch( \Exception $e ) {
						throw $e;
					}
				}
			}
			//$submissionLotteryArray is now sorted by lotteryNumber (lowest to highest).
			ksort( $submissionLotteryArray['Priority'] );
			ksort( $submissionLotteryArray['Expiring'] );
			ksort( $submissionLotteryArray['Non-Priority'] );

			/**
			 * Loop over all the Wait Listed - Priority Submissions
			 *
			 * @var \IIAB\StudentTransferBundle\Entity\Submission $submission
			 */
			foreach( $submissionLotteryArray['Priority'] as $submission ) {

				if( $submission->getFirstChoice()->getGrade() == 0 ) {
					$grade = 'K';
				} elseif( $submission->getFirstChoice()->getGrade() == 99 ) {
					$grade = 'PreK';
				} else {
					$grade = sprintf( '%1$02d' , abs( $submission->getGrade() ) + 1 );
				}

				$waitList = '';
				/** @var \IIAB\StudentTransferBundle\Entity\WaitList $waitListed */
				foreach( $submission->getWaitList() as $waitListed ) {
					$waitList = $waitListed->getChoiceSchool();
				}
				$excelArray[$index] = array(
					'Submission Date' => $submission->getSubmissionDateTime()->format( 'Y-m-d H:i' ) ,
					'Confirmation Number' => $submission->getConfirmationNumber() ,
					'Enrollment Period' => $submission->getEnrollmentPeriod() ,
					'Form' => $submission->getFormID() ,
					'Student ID' => $submission->getStudentID() ,
					'First Name' => $submission->getFirstName() ,
					'Last Name' => $submission->getLastName() ,
					'Date of Birth' => $submission->getDob() ,
					'Address' => $submission->getAddress() ,
					'City' => $submission->getCity() ,
					'Zip' => $submission->getZip() ,
					'Status' => $submission->getSubmissionStatus()->__toString() ,
					'Grade' => $grade ,
					'Race' => $submission->getRace() ,
					'Current School' => $submission->getCurrentSchool() ,
					'Zoned Schools' => $submission->getHsvZonedSchoolsString() ,
					'Lottery Number' => $submission->getLotteryNumber() ,
					'First Choice' => $submission->getFirstChoice() ,
					'Second Choice' => $submission->getSecondChoice() ,
					'Wait List Choice' => $waitList ,
					'Submission was processed' => $submission->getAfterLotterySubmissionReportStyle() ,
					'Priority' => 'Priority Expiring',
					'Employee ID' => '' ,
					'Employee First Name' => '' ,
					'Employee Last Name' => '' ,
					'Employee Location' => '' ,
					'SPED Student ID' => '' ,
					'SPED Current School' => '' ,
					'SPED Current Grade' => '' ,
					'Sibling Status' => '' ,
					'Transfer Reason' => '' ,
					'PT 2014-2015?' => '' ,
					'Zone Change?' => '' ,
					'Inside District?' => '' ,
					'Offered 1st Choice' => '' ,
					'Offered 2nd Choice' => '' ,
					'Denied Due to Ineligibility' => '' ,
					'Denied Due to Space' => '' ,
					'Denied Due to School Racial Composition' => '' ,
				);

				$index++;
			}

			/**
			 * Loop over all the Wait Listed - Expiring Submissions
			 *
			 * @var \IIAB\StudentTransferBundle\Entity\Submission $submission
			 */
			foreach( $submissionLotteryArray['Expiring'] as $submission ) {

				if( $submission->getFirstChoice()->getGrade() == 0 ) {
					$grade = 'K';
				} elseif( $submission->getFirstChoice()->getGrade() == 99 ) {
					$grade = 'PreK';
				} else {
					$grade = sprintf( '%1$02d' , abs( $submission->getGrade() ) + 1 );
				}

				$waitList = '';
				/** @var \IIAB\StudentTransferBundle\Entity\WaitList $waitListed */
				foreach( $submission->getWaitList() as $waitListed ) {
					$waitList = $waitListed->getChoiceSchool();
				}
				$excelArray[$index] = array(
					'Submission Date' => $submission->getSubmissionDateTime()->format( 'Y-m-d H:i' ) ,
					'Confirmation Number' => $submission->getConfirmationNumber() ,
					'Enrollment Period' => $submission->getEnrollmentPeriod() ,
					'Form' => $submission->getFormID() ,
					'Student ID' => $submission->getStudentID() ,
					'First Name' => $submission->getFirstName() ,
					'Last Name' => $submission->getLastName() ,
					'Date of Birth' => $submission->getDob() ,
					'Address' => $submission->getAddress() ,
					'City' => $submission->getCity() ,
					'Zip' => $submission->getZip() ,
					'Status' => $submission->getSubmissionStatus()->__toString() ,
					'Grade' => $grade ,
					'Race' => $submission->getRace() ,
					'Current School' => $submission->getCurrentSchool() ,
					'Zoned Schools' => $submission->getHsvZonedSchoolsString() ,
					'Lottery Number' => $submission->getLotteryNumber() ,
					'First Choice' => $submission->getFirstChoice() ,
					'Second Choice' => $submission->getSecondChoice() ,
					'Wait List Choice' => $waitList ,
					'Submission was processed' => $submission->getAfterLotterySubmissionReportStyle() ,
					'Priority' => 'Expiring',
					'Employee ID' => '' ,
					'Employee First Name' => '' ,
					'Employee Last Name' => '' ,
					'Employee Location' => '' ,
					'SPED Student ID' => '' ,
					'SPED Current School' => '' ,
					'SPED Current Grade' => '' ,
					'Sibling Status' => '' ,
					'Transfer Reason' => '' ,
					'PT 2014-2015?' => '' ,
					'Zone Change?' => '' ,
					'Inside District?' => '' ,
					'Offered 1st Choice' => '' ,
					'Offered 2nd Choice' => '' ,
					'Denied Due to Ineligibility' => '' ,
					'Denied Due to Space' => '' ,
					'Denied Due to School Racial Composition' => '' ,
				);

				$index++;
			}

			/**
			 * Loop over all the Wait Listed - Non-Priority Submissions
			 *
			 * @var \IIAB\StudentTransferBundle\Entity\Submission $submission
			 */
			foreach( $submissionLotteryArray['Non-Priority'] as $submission ) {

				if( $submission->getFirstChoice()->getGrade() == 0 ) {
					$grade = 'K';
				} elseif( $submission->getFirstChoice()->getGrade() == 99 ) {
					$grade = 'PreK';
				} else {
					$grade = sprintf( '%1$02d' , abs( $submission->getGrade() ) + 1 );
				}

				$waitList = '';
				/** @var \IIAB\StudentTransferBundle\Entity\WaitList $waitListed */
				foreach( $submission->getWaitList() as $waitListed ) {
					$waitList = $waitListed->getChoiceSchool();
				}
				$excelArray[$index] = array(
					'Submission Date' => $submission->getSubmissionDateTime()->format( 'Y-m-d H:i' ) ,
					'Confirmation Number' => $submission->getConfirmationNumber() ,
					'Enrollment Period' => $submission->getEnrollmentPeriod() ,
					'Form' => $submission->getFormID() ,
					'Student ID' => $submission->getStudentID() ,
					'First Name' => $submission->getFirstName() ,
					'Last Name' => $submission->getLastName() ,
					'Date of Birth' => $submission->getDob() ,
					'Address' => $submission->getAddress() ,
					'City' => $submission->getCity() ,
					'Zip' => $submission->getZip() ,
					'Status' => $submission->getSubmissionStatus()->__toString() ,
					'Grade' => $grade ,
					'Race' => $submission->getRace() ,
					'Current School' => $submission->getCurrentSchool() ,
					'Zoned Schools' => $submission->getHsvZonedSchoolsString() ,
					'Lottery Number' => $submission->getLotteryNumber() ,
					'First Choice' => $submission->getFirstChoice() ,
					'Second Choice' => $submission->getSecondChoice() ,
					'Wait List Choice' => $waitList ,
					'Submission was processed' => $submission->getAfterLotterySubmissionReportStyle() ,
					'Priority' => 'Non-Priority',
					'Employee ID' => '' ,
					'Employee First Name' => '' ,
					'Employee Last Name' => '' ,
					'Employee Location' => '' ,
					'SPED Student ID' => '' ,
					'SPED Current School' => '' ,
					'SPED Current Grade' => '' ,
					'Sibling Status' => '' ,
					'Transfer Reason' => '' ,
					'PT 2014-2015?' => '' ,
					'Zone Change?' => '' ,
					'Inside District?' => '' ,
					'Offered 1st Choice' => '' ,
					'Offered 2nd Choice' => '' ,
					'Denied Due to Ineligibility' => '' ,
					'Denied Due to Space' => '' ,
					'Denied Due to School Racial Composition' => '' ,
				);

				$index++;
			}

			$source = null;

			$source = $this->getDoctrine()->getRepository('IIABStudentTransferBundle:Submission')->createQueryBuilder('s');

			if( isset( $data['enrollmentPeriod'] ) && !empty( $data['enrollmentPeriod'] ) ) {
				$source->andWhere('s.enrollmentPeriod = :enrollment')->setParameter( 'enrollment' , $data['enrollmentPeriod']->getId() );
			}
			$source->andWhere( 's.submissionStatus IN (:status)' )->setParameter( 'status' , [ 1 , 10 ] ); //Active Submissions First
			$source->andWhere( 's.afterLotterySubmission = 1' ); //Only after Lottery Submissions.
			$source->orderBy( 's.submissionDateTime' , 'ASC' );
			$source->addOrderBy( 's.lotteryNumber' , 'ASC' );
			$source = $source->getQuery()->getResult();

			/**
			 * Loop over all the After Lottery Submissions
			 *
			 * @var \IIAB\StudentTransferBundle\Entity\Submission $submission
			 */
			foreach( $source as $submission ) {

				if( $submission->getFirstChoice()->getGrade() == 0 ) {
					$grade = 'K';
				} elseif( $submission->getFirstChoice()->getGrade() == 99 ) {
					$grade = 'PreK';
				} else {
					$grade = sprintf( '%1$02d' , abs( $submission->getGrade() ) + 1 );
				}

				$waitList = '';
				/** @var \IIAB\StudentTransferBundle\Entity\WaitList $waitListed */
				foreach( $submission->getWaitList() as $waitListed ) {
					$waitList = $waitListed->getChoiceSchool();
				}
				$excelArray[$index] = array(
					'Submission Date' => $submission->getSubmissionDateTime()->format( 'Y-m-d H:i' ) ,
					'Confirmation Number' => $submission->getConfirmationNumber() ,
					'Enrollment Period' => $submission->getEnrollmentPeriod() ,
					'Form' => 'Late ' . $submission->getFormID() ,
					'Student ID' => $submission->getStudentID() ,
					'First Name' => $submission->getFirstName() ,
					'Last Name' => $submission->getLastName() ,
					'Date of Birth' => $submission->getDob() ,
					'Address' => $submission->getAddress() ,
					'City' => $submission->getCity() ,
					'Zip' => $submission->getZip() ,
					'Status' => $submission->getSubmissionStatus()->__toString() ,
					'Grade' => $grade ,
					'Race' => $submission->getRace() ,
					'Current School' => $submission->getCurrentSchool() ,
					'Zoned Schools' => $submission->getHsvZonedSchoolsString() ,
					'Lottery Number' => $submission->getLotteryNumber(),
					'First Choice' => $submission->getFirstChoice() ,
					'Second Choice' => $submission->getSecondChoice() ,
					'Wait List Choice' => $waitList ,
					'Submission was processed' => $submission->getAfterLotterySubmissionReportStyle() ,
					'Priority' => '',
					'Employee ID' => '' ,
					'Employee First Name' => '' ,
					'Employee Last Name' => '' ,
					'Employee Location' => '' ,
					'SPED Student ID' => '' ,
					'SPED Current School' => '' ,
					'SPED Current Grade' => '' ,
					'Sibling Status' => '' ,
					'Transfer Reason' => '' ,
					'PT 2014-2015?' => '' ,
					'Zone Change?' => '' ,
					'Inside District?' => '' ,
					'Offered 1st Choice' => '' ,
					'Offered 2nd Choice' => '' ,
					'Denied Due to Ineligibility' => '' ,
					'Denied Due to Space' => '' ,
					'Denied Due to School Racial Composition' => '' ,
				);

				$index++;
			}

			$source = null;

			$source = $this->getDoctrine()->getRepository('IIABStudentTransferBundle:Submission')->createQueryBuilder('s');

			if( isset( $data['enrollmentPeriod'] ) && !empty( $data['enrollmentPeriod'] ) ) {
				$source->andWhere('s.enrollmentPeriod = :enrollment')->setParameter( 'enrollment' , $data['enrollmentPeriod']->getId() );
			}
			$source->andWhere( 's.submissionStatus = :status' )->setParameter( 'status' , 1 ); //Active Submissions First
			$source->andWhere( 's.formID != 1' ); //All other beside M2M.
			$source->orderBy( 's.submissionDateTime' , 'ASC' );
			$source->addOrderBy( 's.lotteryNumber' , 'ASC' );
			$source = $source->getQuery()->getResult();

			/**
			 * Loop over all Other Forms Submissions
			 *
			 * @var \IIAB\StudentTransferBundle\Entity\Submission $submission
			 */
			foreach( $source as $submission ) {

				if( $submission->getFirstChoice()->getGrade() == 0 ) {
					$grade = 'K';
				} elseif( $submission->getFirstChoice()->getGrade() == 99 ) {
					$grade = 'PreK';
				} else {
					$grade = sprintf( '%1$02d' , abs( $submission->getGrade() ) + 1 );
				}

				$waitList = '';
				/** @var \IIAB\StudentTransferBundle\Entity\WaitList $waitListed */
				foreach( $submission->getWaitList() as $waitListed ) {
					$waitList = $waitListed->getChoiceSchool();
				}
				$excelArray[$index] = array(
					'Submission Date' => $submission->getSubmissionDateTime()->format( 'Y-m-d H:i' ) ,
					'Confirmation Number' => $submission->getConfirmationNumber() ,
					'Enrollment Period' => $submission->getEnrollmentPeriod() ,
					'Form' => $submission->getFormID() ,
					'Student ID' => $submission->getStudentID() ,
					'First Name' => $submission->getFirstName() ,
					'Last Name' => $submission->getLastName() ,
					'Date of Birth' => $submission->getDob() ,
					'Address' => $submission->getAddress() ,
					'City' => $submission->getCity() ,
					'Zip' => $submission->getZip() ,
					'Status' => $submission->getSubmissionStatus()->__toString() ,
					'Grade' => $grade ,
					'Race' => $submission->getRace() ,
					'Current School' => $submission->getCurrentSchool() ,
					'Zoned Schools' => $submission->getHsvZonedSchoolsString() ,
					'Lottery Number' => $submission->getLotteryNumber(),
					'First Choice' => $submission->getFirstChoice() ,
					'Second Choice' => $submission->getSecondChoice() ,
					'Wait List Choice' => $waitList ,
					'Submission was processed' => '' ,
					'Priority' => '',
					'Employee ID' => '' ,
					'Employee First Name' => '' ,
					'Employee Last Name' => '' ,
					'Employee Location' => '' ,
					'SPED Student ID' => '' ,
					'SPED Current School' => '' ,
					'SPED Current Grade' => '' ,
					'Sibling Status' => '' ,
					'Transfer Reason' => '' ,
					'PT 2014-2015?' => '' ,
					'Zone Change?' => '' ,
					'Inside District?' => '' ,
					'Offered 1st Choice' => '' ,
					'Offered 2nd Choice' => '' ,
					'Denied Due to Ineligibility' => '' ,
					'Denied Due to Space' => '' ,
					'Denied Due to School Racial Composition' => '' ,
				);


				if( $submission->getFormID()->getId() == 2 ) {
					//Added columns for Personnel Transfer
					$newColumns = array(
						'Employee ID' => $submission->getEmployeeID() ,
						'Employee First Name' => $submission->getEmployeeFirstName() ,
						'Employee Last Name' => $submission->getEmployeeLastName() ,
						'Employee Location' => $submission->getEmployeeLocation() ,
					);
					$excelArray[$index] = array_merge( $excelArray[$index] , $newColumns );
				}

				if( $submission->getFormID()->getId() == 3 ) {
					//Added columns for Special Education Sibling Transfer
					$submissionData = $submission->getSubmissionData();
					$newColumns = array();
					foreach( $submissionData as $submissionDataObject ) {
						$newColumns[$submissionDataObject->getMetaKey()] = $submissionDataObject->getMetaValue();
					}

					$excelArray[$index] = array_merge( $excelArray[$index] , $newColumns );
				}
				if( $submission->getFormID()->getId() == 4 ) {
					//Added columns for Superintendent Assignment Transfer
					$submissionData = $submission->getSubmissionData();
					$newColumns = array();
					foreach( $submissionData as $submissionDataObject ) {
						$newColumns[$submissionDataObject->getMetaKey()] = $submissionDataObject->getMetaValue();
					}

					$excelArray[$index] = array_merge( $excelArray[$index] , $newColumns );

				}

				$index++;
			}

			$source = new ArraySourceIterator( $excelArray );

			$writer = new CsvWriter( $content );

			Handler::create( $source , $writer )->export();

			$content = file_get_contents( $content );

			$response = new Response();

			$response->headers->set('Content-Type', 'text/csv');
			$response->headers->set('Content-Disposition', 'attachment;filename="submission-wait-list-export.csv"');

			$response->setContent($content);
			unlink( $this->get('kernel')->getRootDir() . '/../web/uploads/submission-wait-list-export.csv' );
			return $response;

		}

		return array(
			'admin_pool' => $admin_pool,
			'form' => $form->createView()
		);
	}

	/**
	 * Builds the Court Report needed.
	 *
	 * @Route("/admin/reports/court", name="stw_admin_report_court")
	 * @Template( "IIABStudentTransferBundle:Report:report.html.twig")
	 *
	 * @param Request $request
	 *
	 * @return array|Response
	 */
	public function courtReport( Request $request ) {

		//
		$admin_pool = $this->get('sonata.admin.pool');

		$content = $this->get('kernel')->getRootDir() . '/../web/uploads/court-report-export.csv';

		if( file_exists( $content ) )
			unlink( $content );

		$endRange = date( 'Y' );
		if( strtotime( $endRange . '-10-01' ) <= strtotime( 'now' ) ) {
			$endRange = date( 'Y' , strtotime( '+1 year' ) );
		}
		$years = array();
		foreach( range( date( 'Y' , strtotime( '2014-01-01' ) ) , $endRange ) as $year ) {
			$years[$year] = $year;
		}

		$form = $this->createFormBuilder()
			->setAction( $this->generateUrl( 'stw_admin_report_court' ) )
			->setMethod( 'post' )
			->add( 'reportYear' , 'choice' , array(
				'choices'		=> $years,
				'required'		=> true,
				'placeholder'	=> 'Select Report Year'
			) )
			->add( 'submit' , 'submit' )
			->getForm();
		;
		$form->handleRequest( $request );

		if( $request->getMethod() == 'POST' ) {

			$data = $form->getData();

			$source = $this->getDoctrine()->getRepository('IIABStudentTransferBundle:Submission')->createQueryBuilder('s');

			$start = new \DateTime( ( $data['reportYear'] - 1) . '-10-01' );
			$end = new \DateTime( $data['reportYear'] . '-09-30 23:59:59' );

			$source->andWhere( 's.submissionDateTime >= :start' )->setParameter( 'start' , $start );
			$source->andWhere( 's.submissionDateTime <= :end' )->setParameter( 'end' , $end);

			//Excluding Overwritten and Inactive and Demo Enrollment
			$source->andWhere( 's.submissionStatus != 6')->andWhere( 's.submissionStatus != 8' )->andWhere( 's.enrollmentPeriod != 1' );
			$source->addOrderBy( 's.formID' , 'ASC' );
			$source->addOrderBy( 's.submissionStatus' , 'ASC' );
			$source->addOrderBy( 's.submissionDateTime' , 'ASC' );

			$source = $source->getQuery()->getResult();

			$excelArray = array();
			$index = 0;
			$excelArray[$index] = array( '' => 'Tuscaloosa City Schools Transfer Court Report - ' . $data['reportYear'] );
			$index++;
			$excelArray[$index] = array(
				'Type of Transfer',
				'Effective School Year',
				'Awarded School (includes grade)',
				'Status of Transfer Request',
				'Student ID' ,
				'First Name',
				'Last Name',
				'Address',
				'City',
				'Zip',
				'Race',
				'Current School',
				'Zoned School',
				'First Choice',
				'Second Choice',
				'Submission was processed'
			);
			$index++;

			/**
			 * @var \IIAB\StudentTransferBundle\Entity\Submission $submission
			 */
			foreach( $source as $submission ) {
				$excelArray[$index] = array(
					'Type of Transfer' => $submission->getFormId(),
					'Effective School Year' => $submission->getEnrollmentPeriod(),
					'Awarded School (includes grade)' => $submission->getAwardedSchoolID(),
					'Status of Transfer Request' => $submission->getSubmissionStatus(),
					'Student ID' => $submission->getStudentID() ,
					'First Name' => $submission->getFirstName(),
					'Last Name' => $submission->getLastName(),
					'Address' => $submission->getAddress(),
					'City' => $submission->getCity(),
					'Zip' => $submission->getZip(),
					'Race' => $submission->getRace(),
					'Current School' => $submission->getCurrentSchool(),
					'Zoned School' => $submission->getHsvZonedSchoolsString(),
					'First Choice' => $submission->getFirstChoice(),
					'Second Choice' => $submission->getSecondChoice(),
					'Submission was processed' => $submission->getAfterLotterySubmissionReportStyle(),
				);
				$index++;
			}
			$excelArray[$index] = array( '' => 'Last revised: ' . date( 'Y-m-d H:i' ) );
			$index++;

			$source = new ArraySourceIterator( $excelArray );

			$writer = new CsvWriter( $content , ',' , "\"" , "\\" , false );

			Handler::create( $source , $writer )->export();

			$content = file_get_contents( $content );

			$response = new Response();

			$response->headers->set('Content-Type', 'text/csv');
			$response->headers->set('Content-Disposition', 'attachment;filename="court-report-export.csv"');

			$response->setContent($content);
			unlink( $this->get('kernel')->getRootDir() . '/../web/uploads/court-report-export.csv' );
			return $response;
		}
		return array(
			'admin_pool' => $admin_pool,
			'form' => $form->createView()
		);
	}

	/**
	 * This function is to handle the building of the accepted Report.
	 */
	private function acceptedReport() {

	}

	/**
	 * This function is to handle the building of the denied Report.
	 */
	private function deniedReport() {

	}

	/**
	 * Changes a string values and removes specific words
	 *
	 * @param $name
	 * @return mixed
	 */
	private function formatName( $name ) {
		$name = str_replace( ' School' , '' , $name );
		return $name;
	}

	/**
	 * Builds the Before and After Population Report
	 *
	 * @Route( "/admin/reports/population-shift-report/", name="admin_population_shift_report")
	 * @Template( "IIABStudentTransferBundle:Report:report.html.twig")
	 *
	 * @return array
	 */
	public function generatePopulationShiftReport() {
		$request = $this->container->get( 'request' );

		$admin_pool = $this->get( 'sonata.admin.pool' );

		$title = 'Population Before and After Processing Report';

		$form = $this->createFormBuilder()
			->add( 'openEnrollment' , 'entity' , array(
				'class'	=> 'IIABStudentTransferBundle:OpenEnrollment',
				'required' => true,
				'placeholder'	=> 'Select the Enrollment Period'
			) )
			->add( 'submit' , 'submit' )
			->getForm();
		$form->handleRequest( $request );

		if( $form->isValid() ) {

			$data = $form->getData();
			/** @var \IIAB\StudentTransferBundle\Entity\OpenEnrollment $openEnrollment */
			$openEnrollment = $data['openEnrollment'];

			$excelArray = [
				[
					'',
					'',
					'Before',
					'', '', '',
					'After',
					'', '', '',
					'Change',
					'', '', '',
				],
				[
					'School',
					'Max Capacity',
					//before
					'Black',
					'White',
					'Other',
					'Total',
					//after
					'Black',
					'White',
					'Other',
					'Total',
					//change
					'Black',
					'White',
					'Other',
					'Total',

				]
			];

			$sql = "SELECT
					adm.groupId,
					SUM( IF( s.race LIKE '%black%', 1, 0 ) ) AS black,
					SUM( IF( s.race LIKE '%white%', 1, 0 ) ) AS white,
					SUM( IF( s.race IS NULL OR ( s.race NOT LIKE '%black%' AND s.race NOT LIKE '%white%' ), 1, 0 ) ) AS other,
					COUNT( s.awardedSchoolID ) AS total
				FROM stw_submission AS s
				LEFT JOIN stw_adm AS adm
					ON adm.id = s.awardedSchoolID
				WHERE s.enrollmentPeriod = ". $openEnrollment->getId() ."
				AND s.awardedSchoolID IS NOT NULL
				and submissionStatus IN( 2, 3, 11)
				GROUP BY adm.groupId
			";

			$query = $this->getDoctrine()->getConnection()->query($sql);
			$query_results = $query->fetchAll();

			$changes = [];
			foreach( $query_results as $change ){

				$changes[ $change[ 'groupId'] ] = $change;
			}

			$population_data = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:CurrentEnrollmentSettings' )->findBy(
				[ 'enrollmentPeriod' => $openEnrollment ],
				[ 'addedDateTime' => 'DESC']
			);

			$schools = [];
			foreach( $population_data as $population ){

				$group = $population->getGroupId();

				if( empty( $changes[ $group->getId() ] ) ){
					$changes[ $group->getId() ] = [
						'black' => 0,
						'white' => 0,
						'other' => 0,
						'total' => 0
					];
				}

				if( !isset( $schools[ $group->getId() ] ) ){
					$schools[ $group->getId() ] = [
						$group->getName(),
						$population->getMaxCapacity(),
						//before
						$population->getBlack() - $changes[ $group->getId() ][ 'black' ],
						$population->getWhite() - $changes[ $group->getId() ][ 'white' ],
						$population->getOther() - $changes[ $group->getId() ][ 'other' ],
						$population->getBlack() + $population->getWhite() + $population->getOther() - $changes[ $group->getId() ][ 'total' ],
						//after
						$population->getBlack(),
						$population->getWhite(),
						$population->getOther(),
						$population->getBlack() + $population->getWhite() + $population->getOther(),
						//change
						$changes[ $group->getId() ][ 'black' ],
						$changes[ $group->getId() ][ 'white' ],
						$changes[ $group->getId() ][ 'other' ],
						$changes[ $group->getId() ][ 'total' ],
					];
				}
			}

			usort($schools, function($a, $b) {
				return strcmp( $a[0], $b[0]);
			});

			$excelArray = array_merge( $excelArray, array_values( $schools ) );

			$source = new ArraySourceIterator( $excelArray );

			$file_name = 'population-shift-report.csv';

			$content = $this->get( 'kernel' )->getRootDir() . '/../web/uploads/' . $file_name;

			if( file_exists( $content ) ) {
				unlink( $content );
			}

			$writer = new CsvWriter( $content , $delimiter = ",", $enclosure = "\"", $escape = "\\" , false );

			Handler::create( $source , $writer )->export();

			$content = file_get_contents( $content );

			$response = new Response();

			$response->headers->set( 'Content-Type' , 'text/csv' );
			$response->headers->set( 'Content-Disposition' , 'attachment;filename="'. $file_name .'"' );

			$response->setContent( $content );
			unlink( $this->get( 'kernel' )->getRootDir() . '/../web/uploads/'. $file_name );
			return $response;
		}
		return array('form' => $form->createView(), 'admin_pool' => $admin_pool, 'title' => $title);
	}

	/**
	 * Builds the Proof of Residence by Current School Report
	 *
	 * @Route( "/admin/reports/proof-of-residence-by-current-school-report/", name="admin_proof_of_residence_by_current_school_report")
	 * @Template( "IIABStudentTransferBundle:Report:report.html.twig")
	 *
	 * @return array
	 */
	public function generateProofOfResidenceByCurrentSchoolReport( Request $request ) {

		$admin_pool = $this->get('sonata.admin.pool');

		$content = $this->get('kernel')->getRootDir() . '/../web/uploads/proof-of-residence-by-current-school-export.csv';

		if( file_exists( $content ) )
			unlink( $content );

		$form = $this->createFormBuilder()
			->setAction( $this->generateUrl( 'admin_proof_of_residence_by_current_school_report' ) )
			->setMethod( 'post' )
			->add( 'enrollmentPeriod' , 'entity' , array(
				'class'	=> 'IIABStudentTransferBundle:OpenEnrollment',
				'required' => false,
				'placeholder'	=> 'All Enrollment Period'
			) )
			->add( 'schoolGroup', 'entity',[
				'class'	=> 'IIABStudentTransferBundle:SchoolGroup',
				'label' => 'Current School',
			 	'required' => false,
			 	'placeholder'	=> 'All Schools'
			] )

			// ->add( 'form' , 'entity' , array(
			// 	'class'	=> 'IIABStudentTransferBundle:Form',
			// 	'required' => false,
			// 	'placeholder'	=> 'All Forms'
			// ) )
			->add( 'status' , 'entity' , array(
				'class'	=> 'IIABStudentTransferBundle:SubmissionStatus',
				'required' => false,
				'placeholder'	=> 'All Statuses'
			) )
			->add( 'submit' , 'submit' )
			->getForm();
		;
		$form->handleRequest( $request );

		if( $request->getMethod() == 'POST' ) {
			if( $form->isValid() ) {

				$school_groups = $this->getDoctrine()
					->getRepository('IIABStudentTransferBundle:SchoolGroup')
					->findAll();
				$system_schools = [];
				foreach( $school_groups as $school ){
					$system_schools[$school->getName()] = $school->getName();
				}

				$data = $form->getData();
				$source = $this->getDoctrine()
					->getRepository('IIABStudentTransferBundle:Submission')
					->createQueryBuilder('s')
					->where( 's.formID != 2');

				if( isset( $data['enrollmentPeriod'] ) && !empty( $data['enrollmentPeriod'] ) ) {
					$source
						->andWhere('s.enrollmentPeriod = :enrollment')
						->setParameter( 'enrollment' , $data['enrollmentPeriod']->getId() );
				}

				if( isset( $data['schoolGroup'] ) && !empty( $data['schoolGroup'] ) ) {
					$source
						->andWhere('s.currentSchool = :school')
						->setParameter( 'school' , $data['schoolGroup']->getName() );
				}

				if( isset( $data['status'] ) && !empty( $data['status'] ) ) {
					$source
						->andWhere( 's.submissionStatus = :status' )
						->setParameter( 'status' , $data['status']->getId() );
				}
				if( isset( $data['form'] ) && !empty( $data['form'] ) ) {
					$source
						->andWhere( 's.formID = :form' )
						->setParameter( 'form' , $data['form']->getId() );
				}
				$source = $source->getQuery()->getResult();

				$submission_data = $this->getDoctrine()
	        	->getRepository('IIABStudentTransferBundle:SubmissionData')
	        	->findBy([
	        		'submission' => $source
	        	]);

		        $keys = [
	    			'proof_of_residency' => '',
			        'proof_of_residency_date' => '',
	    		];

	    		$submission_hash = [];
	    		$submission_data_hash = [];
			    foreach( $source as $submission ){
			    	$submission_hash[ $submission->getId() ] = $submission;
			    	$submission_data_hash [ $submission->getId() ] = [];
			    }

			    $yes_no = [ 'No', 'Yes' ];
			    foreach( $submission_data as $datum ){

			    	if( in_array( $datum->getMetaKey(), array_keys( $keys ) ) ){
			    		$key_title = ucwords( str_replace('_', ' ', $datum->getMetaKey() ) );
			    		$submission_data_hash[ $datum->getSubmission()->getId() ][$key_title] =
			    			( isset( $yes_no[ $datum->getMetaValue() ] ) )
			    				? $yes_no[ $datum->getMetaValue() ]
			    				: $datum->getMetaValue();
			    	}
			    }

				$groupedArray = array();
				$index = 0;
				$column_titles = [];
				/**
				 * @var \IIAB\StudentTransferBundle\Entity\Submission $submission
				 */
				foreach( $source as $submission ) {
					if( $submission->getGrade() == 99 ) {
						$grade = sprintf( '%1$02d' , 0 );
					} else {
						$grade = sprintf( '%1$02d' , abs( $submission->getGrade() ) + 1 );
					}
					$awardedSchoolName = '';
					if( $submission->getAwardedSchoolID() != null ) {
						/*if( $submission->getAwardedSchoolID()->getId() == $submission->getFirstChoice()->getId() ) {
							$awardedSchoolName = $submission->getFirstChoice()->__toString();
						}
						if( $submission->getAwardedSchoolID()->getId() == $submission->getSecondChoice()->getId() ) {
							$awardedSchoolName = $submission->getSecondChoice()->__toString();
						}*/
						$awardedSchoolName = $submission->getAwardedSchoolID()->__toString();
					}

					$index = ( isset( $system_schools[ $submission->getCurrentSchool() ] ) )
						? $submission->getCurrentSchool()
						: 'other';

					if( empty( $groupedArray[$index] ) ){
						$groupedArray[$index] = [];
					}

					$columns = array(
						'Submission Date'		=> $submission->getSubmissionDateTime()->format( 'Y-m-d H:i'),
						'Confirmation Number'	=> $submission->getConfirmationNumber(),
						'Enrollment Period'		=> $submission->getEnrollmentPeriod(),
						'Form'					=> $submission->getFormID(),
						'Student ID'			=> $submission->getStudentID(),
						'First Name'			=> $submission->getFirstName(),
						'Last Name'				=> $submission->getLastName(),
						'Date of Birth'			=> $submission->getDob(),
						'Address'				=> $submission->getAddress(),
						'City'					=> $submission->getCity(),
						'Zip'					=> $submission->getZip(),
						'Email Address'			=> $submission->getEmail(),
						'Status'				=> $submission->getSubmissionStatus()->__toString(),
						'Grade'					=> $grade,
						'Race'					=> $submission->getRace(),
						'Current School'        => $submission->getCurrentSchool(),
						'Zoned Schools'			=> $submission->getHsvZonedSchoolsString(),
						'First Choice'			=> $submission->getFirstChoice(),
						'Awarded School'		=> $awardedSchoolName,
						'Employee ID'			=> '',
						'Employee First Name'	=> '',
						'Employee Last Name'	=> '',
						'Employee Location'		=> '',
						'Proof Of Residency' 	=> 'No',
						'Proof Of Residency Date' => '',
					);

					if( $submission->getFormID()->getId() == 2 ) {
						//Added columns for Personnel Transfer
						$columns = array_merge( $columns, array(
							'Employee ID'			=> $submission->getEmployeeID(),
							'Employee First Name'	=> $submission->getEmployeeFirstName(),
							'Employee Last Name'	=> $submission->getEmployeeLastName(),
							'Employee Location'		=> $submission->getEmployeeLocation(),
						) );
					}

					if( $submission->getFormID()->getId() == 3 ) {
						//Added columns for Special Education Sibling Transfer
						$submissionData = $submission->getSubmissionData();
						$newColumns = array();
						foreach( $submissionData as $submissionDataObject ) {
							$newColumns[$submissionDataObject->getMetaKey()] = $submissionDataObject->getMetaValue();
						}

						$columns = array_merge( $columns , $newColumns );
					}
					if( $submission->getFormID()->getId() == 4 ) {
						//Added columns for Superintendent Assignment Transfer
						$submissionData = $submission->getSubmissionData();
						$newColumns = array();
						foreach( $submissionData as $submissionDataObject ) {
							$newColumns[$submissionDataObject->getMetaKey()] = $submissionDataObject->getMetaValue();
						}

						$columns = array_merge( $columns , $newColumns );
					}

					$columns = array_merge( $columns, $submission_data_hash[ $submission->getId() ] );
					$groupedArray[$index][] = $columns;
				}

				foreach( $groupedArray as $rows ){
					foreach( $groupedArray as $school_group => $rows ){
						$excelArray[0]['School'] = '';
						foreach( $rows as $row ){
							$excelArray[0] = array_merge( $excelArray[0], array_fill_keys( array_keys( $row ), '' ) );
						}
					}
				}

				uksort( $groupedArray, function ( $a,$b ){
						if( strtoupper( $a ) == 'OTHER' ){
							return 1;
						} else if( strtoupper( $b ) == 'OTHER' ){
							return -1;
						}

						return strcasecmp($a, $b);
					}
				);

				$row_index = 0;
				foreach( $groupedArray as $school_group => $rows ){
					$excelArray[ $row_index ]['School'] = $school_group;
					foreach( $rows as $row ){
						$row_index ++;
						$excelArray[$row_index] = array_merge( [ 'School' => '' ], $row );
					}
					$row_index ++;
				}

				$source = new ArraySourceIterator( ( isset( $excelArray ) ) ? $excelArray : [['School' => 'No Applicants found']] );

				$writer = new CsvWriter( $content );

				Handler::create( $source , $writer )->export();

				$content = file_get_contents( $content );

				$response = new Response();

				$response->headers->set('Content-Type', 'text/csv');
				$response->headers->set('Content-Disposition', 'attachment;filename="proof-of-residence-by-current-school-export.csv"');

				$response->setContent($content);
				unlink( $this->get('kernel')->getRootDir() . '/../web/uploads/proof-of-residence-by-current-school-export.csv' );
				return $response;
			}
		}

		return array(
			'admin_pool' => $admin_pool,
			'form' => $form->createView()
		);
	}

	/**
	 * Builds the Employee Dependent Verify Report
	 *
	 * @Route( "/admin/reports/employee-dependent-report/", name="admin_employee_dependent_report")
	 * @Template( "IIABStudentTransferBundle:Report:report.html.twig")
	 *
	 * @return array
	 */
	public function generateEmployeeDependentReport( Request $request ) {

		$admin_pool = $this->get('sonata.admin.pool');

		$content = $this->get('kernel')->getRootDir() . '/../web/uploads/employee-dependent-verify-export.csv';

		if( file_exists( $content ) )
			unlink( $content );

		$form = $this->createFormBuilder()
			->setAction( $this->generateUrl( 'admin_employee_dependent_report' ) )
			->setMethod( 'post' )
			->add( 'enrollmentPeriod' , 'entity' , array(
				'class'	=> 'IIABStudentTransferBundle:OpenEnrollment',
				'required' => false,
				'placeholder'	=> 'All Enrollment Period'
			) )

			->add( 'status' , 'entity' , array(
				'class'	=> 'IIABStudentTransferBundle:SubmissionStatus',
				'required' => false,
				'placeholder'	=> 'All Statuses'
			) )
			->add( 'submit' , 'submit' )
			->getForm();
		;
		$form->handleRequest( $request );

		if( $request->getMethod() == 'POST' ) {
			if( $form->isValid() ) {

				$data = $form->getData();
				$source = $this->getDoctrine()
					->getRepository('IIABStudentTransferBundle:Submission')
					->createQueryBuilder('s')
					->where( 's.formID = 2' );

				if( isset( $data['enrollmentPeriod'] ) && !empty( $data['enrollmentPeriod'] ) ) {
					$source
						->andWhere('s.enrollmentPeriod = :enrollment')
						->setParameter( 'enrollment' , $data['enrollmentPeriod']->getId() );
				}

				if( isset( $data['status'] ) && !empty( $data['status'] ) ) {
					$source
						->andWhere( 's.submissionStatus = :status' )
						->setParameter( 'status' , $data['status']->getId() );
				}

				$source = $source->getQuery()->getResult();

				$submission_data = $this->getDoctrine()
	        		->getRepository('IIABStudentTransferBundle:SubmissionData')
	        		->findBy([
	        			'submission' => $source,
	        			'metaKey' => 'employee_verified'
	        		]);

	    		$submission_hash = [];
	    		$submission_data_hash = [];
			    foreach( $source as $submission ){
			    	$submission_hash[ $submission->getId() ] = $submission;
			    	$submission_data_hash [ $submission->getId() ] = [];
			    }

			    $yes_no = [ 'No', 'Yes' ];
			    foreach( $submission_data as $datum ){
					$key_title = ucwords( str_replace('_', ' ', $datum->getMetaKey() ) );
		    		$submission_data_hash[ $datum->getSubmission()->getId() ][$key_title] =
		    			( isset( $yes_no[ $datum->getMetaValue() ] ) )
		    				? $yes_no[ $datum->getMetaValue() ]
		    				: $datum->getMetaValue();
			    }

				$excelArray = array();
				$index = 0;
				$column_titles = [];
				/**
				 * @var \IIAB\StudentTransferBundle\Entity\Submission $submission
				 */
				foreach( $source as $submission ) {
					if( $submission->getGrade() == 99 ) {
						$grade = sprintf( '%1$02d' , 0 );
					} else {
						$grade = sprintf( '%1$02d' , abs( $submission->getGrade() ) + 1 );
					}
					$awardedSchoolName = '';
					if( $submission->getAwardedSchoolID() != null ) {
						/*if( $submission->getAwardedSchoolID()->getId() == $submission->getFirstChoice()->getId() ) {
							$awardedSchoolName = $submission->getFirstChoice()->__toString();
						}
						if( $submission->getAwardedSchoolID()->getId() == $submission->getSecondChoice()->getId() ) {
							$awardedSchoolName = $submission->getSecondChoice()->__toString();
						}*/
						$awardedSchoolName = $submission->getAwardedSchoolID()->__toString();
					}

					$columns = array(
						'Submission Date'		=> $submission->getSubmissionDateTime()->format( 'Y-m-d H:i'),
						'Confirmation Number'	=> $submission->getConfirmationNumber(),
						'Enrollment Period'		=> $submission->getEnrollmentPeriod(),
						'Form'					=> $submission->getFormID(),
						'Student ID'			=> $submission->getStudentID(),
						'First Name'			=> $submission->getFirstName(),
						'Last Name'				=> $submission->getLastName(),
						'Date of Birth'			=> $submission->getDob(),
						'Address'				=> $submission->getAddress(),
						'City'					=> $submission->getCity(),
						'Zip'					=> $submission->getZip(),
						'Email Address'			=> $submission->getEmail(),
						'Status'				=> $submission->getSubmissionStatus()->__toString(),
						'Grade'					=> $grade,
						'Race'					=> $submission->getRace(),
						'Current School'        => $submission->getCurrentSchool(),
						'Zoned Schools'			=> $submission->getHsvZonedSchoolsString(),
						'First Choice'			=> $submission->getFirstChoice(),
						'Awarded School'		=> $awardedSchoolName,
						'Employee ID'			=> $submission->getEmployeeID(),
						'Employee First Name'	=> $submission->getEmployeeFirstName(),
						'Employee Last Name'	=> $submission->getEmployeeLastName(),
						'Employee Work Site'	=> $submission->getEmployeeLocation(),
						'Employment Verified' 	=> 'No',
					);
					$excelArray[] = $columns;
				}

				$source = new ArraySourceIterator( ( isset( $excelArray ) ) ? $excelArray : [['School' => 'No Applicants found']] );

				$writer = new CsvWriter( $content );

				Handler::create( $source , $writer )->export();

				$content = file_get_contents( $content );

				$response = new Response();

				$response->headers->set('Content-Type', 'text/csv');
				$response->headers->set('Content-Disposition', 'attachment;filename="employee-dependent-verify-export.csv"');

				$response->setContent($content);
				unlink( $this->get('kernel')->getRootDir() . '/../web/uploads/employee-dependent-verify-export.csv' );
				return $response;
			}
		}

		return array(
			'admin_pool' => $admin_pool,
			'form' => $form->createView()
		);
	}
}
