<?php
/**
 * Created by PhpStorm.
 * User: michellegivens
 * Date: 4/13/14
 * Time: 9:58 PM
 */

namespace IIAB\StudentTransferBundle\Lottery;

use Doctrine\ORM\Query\Expr\Join;
use IIAB\StudentTransferBundle\Entity\CurrentEnrollmentSettings;
use IIAB\StudentTransferBundle\Entity\OpenEnrollment;
use IIAB\StudentTransferBundle\Entity\Audit;
use IIAB\StudentTransferBundle\Entity\SlottingReport;
use IIAB\StudentTransferBundle\Entity\Submission;
use Doctrine\Common\Persistence\ObjectManager;
use IIAB\StudentTransferBundle\Entity\WaitList;

class Lottery {

	/** @var \Symfony\Component\DependencyInjection\ContainerInterface */
	protected $container;

	/** @var array */
	private $slottingLotteryArray = array();

	public function setContainer( $container ) {

		$this->container = $container;
	}

	/**
	 * This function gets a unique lottery number to be assigned for an Accountability Act transfer request
	 */
	public function settings( $firstRoundDate , $secondRoundDate ) {

	}

	/**
	 * This function gets a unique lottery number to be assigned for an Accountability Act transfer request
	 */
	public function getLotteryNumber( $emLookup ) {

		$lotteryNumbers = Array( 0 , 0 , 0 , 0 , 0 , 0 );
		$lotteryNumber = 1;
		$uniqueLottoFlag = true;
		$breakOutIfInfiniteLoopCounter = 0;

		while( $uniqueLottoFlag ) {
			//create an array of 6 random numbers that range in value from 1 to 64
			for( $i = 0; $i < 6; $i++ ) {
				$lotteryNumbers[$i] = rand( 0 , 64 );
			}

			//concatenate all lottery numbers into one lottery number
			$lotteryNumber = intval( implode( $lotteryNumbers ) );

			//check to see if the lottery number has already been assigned during another submission
			$submission = $emLookup->getRepository( 'IIABStudentTransferBundle:Submission' )->findOneBy( array(
				'lotteryNumber' => $lotteryNumber
			) );
			//if not already assigned, break out of loop
			if( $submission == null ) {
				$uniqueLottoFlag = false;
			}

			//make sure we do not get in an infinite loop; report error if so
			$breakOutIfInfiniteLoopCounter++;
			if( $breakOutIfInfiniteLoopCounter > 1000 ) {
				die( "We are sorry, there was an error with your submission. Please visit Student Support Services for assistance, or try your submission again later." );
			}

		}

		return $lotteryNumber;

	}

	/**
	 * This function gets a unique lottery number to be assigned for an Accountability Act transfer request
	 *
	 * @param ObjectManager $emLookup
	 * @param OpenEnrollment $openEnrollmentPeriod
	 * @param Integer $roundNumber
	 *
	 * @return bool
	 * @throws \Exception
	 */
	/**
	 * This function gets a unique lottery number to be assigned for an Accountability Act transfer request
	 *
	 * @param ObjectManager $emLookup
	 * @param OpenEnrollment $openEnrollmentPeriod
	 * @param Integer $roundNumber
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function runLottery( ObjectManager $emLookup , OpenEnrollment $openEnrollmentPeriod , $roundNumber) {

		//if this is the first round of the lottery
		if( $roundNumber == 1 ) {
			//grab all active Accountability Act Submissions for this open enrollment period
			$status = 1; //Status Active (1)
			$submissions = $emLookup->getRepository('IIABStudentTransferBundle:Submission')->createQueryBuilder('s')
				->where('s.enrollmentPeriod = :enrollment')
				->andWhere('s.submissionStatus = :submissionStatus')
				->andWhere('s.afterLotterySubmission = :afterLotterySubmission') //Only pull in Submissions that were submitting during the Lottery Window
				->andWhere('s.formID = :form')
				->setParameters( array(
					'enrollment'		=> $openEnrollmentPeriod->getId(),
					'submissionStatus'	=> $status,
					'form'				=> 6, // Only Accountability Act
					'afterLotterySubmission' => false , // FALSE == ONLY During Lottery Window Submissions
				) )
				->getQuery()
				->getResult()
			;

		} else if( $roundNumber == 3 ) {
			$this->recordAudit( $emLookup , 35 );

			// grab all active and waitlisted submissions for After Lottery submission processing
			// during after lottery processing Personnel and SPED transfers and processed after the Accountability Act
			$status = [ 1, 7, 10 ]; //Status Active (1), Non-Awarded (7) AND Wait Listed (10)
			$submissions = $emLookup->getRepository('IIABStudentTransferBundle:Submission')->createQueryBuilder('s')
				->where('s.enrollmentPeriod = :enrollment')
				->andWhere('s.submissionStatus IN (:submissionStatus)')
				->andWhere('s.formID = :forms')
				->setParameters( array(
					'enrollment'		=> $openEnrollmentPeriod->getId(),
					'submissionStatus'	=> $status,
					'forms'				=> 6, // Accountability Act Transfers
				) )
				->getQuery()
				->getResult()
			;
		}else  {
			//else grab all non active Accountability submissions for this open enrollment period
			$status = array( 7 , 10 ); //Status Non-Awarded (7) AND Wait Listed (10)

			$AAForm = $emLookup->getRepository('IIABStudentTransferBundle:Form')->find(6);

			//Need to mark all Awarded submission as Declined since it is round 2!
			$awardedStatusEntity = $emLookup->getRepository('IIABStudentTransferBundle:SubmissionStatus')->find(2); //offered
			$declinedStatusEntity = $emLookup->getRepository('IIABStudentTransferBundle:SubmissionStatus')->find(4); //offered and declined

			$awardedSubmissions = $emLookup->getRepository('IIABStudentTransferBundle:Submission')->findBy( array(
				'submissionStatus'		 => $awardedStatusEntity,
				'enrollmentPeriod'		 => $openEnrollmentPeriod,
				'formID'				 => $AAForm, // Only Accountability Act Transfer go into the Lottery
				'afterLotterySubmission' => false,
			) );
			foreach( $awardedSubmissions as $awardedSubmission ) {
				$this->recordAudit( $emLookup , 5 , $awardedSubmission->getId() , $awardedSubmission->getStudentId() );
				$awardedSubmission->setSubmissionStatus( $declinedStatusEntity );
			}

			//No need to walk through the Awarded and Wait Listed.
			$offeredWaitListedStatusEntity = $emLookup->getRepository('IIABStudentTransferBundle:SubmissionStatus')->find(11); //offered and wait listed
			$waitListedStatusEntity = $emLookup->getRepository('IIABStudentTransferBundle:SubmissionStatus')->find(10); //wait listed

			$offeredWaitListedSubmissions = $emLookup->getRepository('IIABStudentTransferBundle:Submission')->findBy( array(
				'submissionStatus' => $offeredWaitListedStatusEntity,
				'enrollmentPeriod' => $openEnrollmentPeriod,
				'formID'			   => $AAForm, // Only Accountability Act Transfer go into the Lottery
				'afterLotterySubmission' => false,
			) );
			foreach( $offeredWaitListedSubmissions as $awardedSubmission ) {
				$this->recordAudit( $emLookup , 5 , $awardedSubmission->getId() , $awardedSubmission->getStudentId() );
				$awardedSubmission->setSubmissionStatus( $waitListedStatusEntity );
			}

			$emLookup->flush();

			$submissions = $emLookup->getRepository('IIABStudentTransferBundle:Submission')->createQueryBuilder('s')
				->where('s.enrollmentPeriod = :enrollment')
				->andWhere('s.submissionStatus IN (:submissionStatus)')
				->andWhere('s.afterLotterySubmission = :afterLotterySubmission') //Only pull in Submissions that were submitting during the Lottery Window
				->andWhere('s.formID = :form')
				->setParameters( array(
					'enrollment'		=> $openEnrollmentPeriod->getId(),
					'submissionStatus'	=> $status,
					'form'				=> 6, // Only Accountability Act Transfer go into the Lottery
					'afterLotterySubmission' => false , // FALSE == ONLY During Lottery Window Submissions
				) )
				->getQuery()
				->getResult()
			;
		}

		$submissionLotteryArray = array(
			'Priority' => array() ,
			'Non-Priority' => array() ,
		);

		if( count( $submissions ) > 0 ) {

			/** @var Submission $submission */
			foreach( $submissions as $submission ) {
				try {
					/** @var \IIAB\StudentTransferBundle\Entity\Expiration $expiring */
					$is_renewal = ( $submission->getIsRenewal() == 'Renewal' );
					$por_key = $submission->getSubmissionDataByKey('proof_of_residency_datex');
					$por_key = ( $por_key != null ) ? $por_key->getMetaValue() : false;

					if( $is_renewal ){
						$submissionLotteryArray['Priority'][$por_key .' '. $submission->getId()] = $submission;
					} else {
						$submissionLotteryArray['Non-Priority'][$por_key .' '. $submission->getId()] = $submission;
					}

				} catch( \Exception $e ) {
					throw $e;
				}
			}
		}

		//$submissionLotteryArray is now sorted by lotteryNumber (lowest to highest).
		ksort( $submissionLotteryArray['Priority'] );
		ksort( $submissionLotteryArray['Non-Priority'] );

		//Write the Logging file before combining into one list.
		$this->writeLoggingFile( $submissionLotteryArray , 'before' , 'lottery-round-' . $roundNumber );

		//Combine the Two Indexes (non-priority,expiring) into one list with expiring first.
		//$submissionLotteryArray = array_merge( $submissionLotteryArray['Priority'] , $submissionLotteryArray['Non-Priority'] );

		//Build the SlottingLotteryAvailable Slots Array in the function below:
		$this->slottingLotteryArray = $this->getSlottingInformation( $openEnrollmentPeriod );

		//TODO: Need to add in the ability to give priority to a zoned school.
		//Not sure if this is still needed.

		$fiveDays = strtotime( "5 weekdays" );
		$fiveDays = new \DateTime( date( 'm/d/Y' , $fiveDays ) );

		$completedLottery = array(
			'offered'				    => array(),
			'non-awarded'			    => array(),
			'denied due to space'	    => array(),
			'wait listed'			    => array(),
			'offered and wait listed'   => array(),
		);

		/*
		 * OuterLoop will allow us to restart the loop at the very beginning and make sure we walk the list 3 total complete times after the last success award.
		 */
		for( $outerLoopReset = 0; $outerLoopReset < 4; $outerLoopReset++ ) {

			//var_dump( 'Walking List from the Beginning: ' . count( $submissionLotteryArray ) . ' || OuterLoop: ' . $outerLoopReset );

			/**
			 * Walk all of the List as the list is now one complete list.
			 * @var Submission $submissions
			 */
			foreach ( $submissionLotteryArray as $maybe_priority => $submissions ) {

				foreach ( $submissions as $index => $submission ) {

					//check first choice against DOI table...is there an available slot?
					$firstChoiceGroupID = $submission->getFirstChoice()->getGroupID()->getId();
					$submissionRace = trim( strtolower( $submission->getRace() ) );
					switch ( $submissionRace ) {

						case 'white':
							$race = 'white';
							break;

						case 'black':
						case 'black/african american';
							$race = 'black';
							break;

						default:
							$race = 'other';
							break;
					}

					if ( !isset( $this->slottingLotteryArray[$firstChoiceGroupID] ) ) {
						throw new \Exception( 'First Choice School Group not found in Slotting Lottery Array' , 2000 );
					}

					//No need to run this through all the tests again because its already been awarded.
					if( isset( $completedLottery['offered'][$submission->getId() . '-' . $submission->getFirstChoice()->getId()] ) ) {
						continue;
					}

					//var_dump( 'Trying First Choice Submission: ' . $submission->getId() . ' GroupID: ' . $firstChoiceGroupID  );

					if( $maybe_priority == 'Priority'
						|| $this->slottingLotteryArray[$firstChoiceGroupID]['availableSlots'] > 0
					) {

						//var_dump( $firstChoiceGroupID . ' has available slots' );
						//yes, there is an available slot

						//No make sure the racial calculation will pass with the increase.
						if ( $this->passesRacialCalculationIncrease( $firstChoiceGroupID , $race ) ) {

							//Ensuring we haven't already awarded
							if( !isset( $completedLottery['offered'][$submission->getId().'-'.$submission->getFirstChoice()->getId()] ) ) {

								$awardFirstChoice = true;
								if( $submission->getSecondChoice() != null ) {

									//Check to see if Submission Choice #2 was already awarded.
									if( isset( $completedLottery['offered and wait listed'][$submission->getId().'-'.$submission->getSecondChoice()->getId()] ) ) {

										if( $this->passesRacialCalculationDecrease( $submission->getSecondChoice()->getGroupID()->getId() , $race ) ) {
											$awardFirstChoice = true;

											//Removing the SecondChoice awarded School because we are NOW awarding the First Choice
											unset( $completedLottery['offered and wait listed'][$submission->getId() . '-' . $submission->getSecondChoice()->getId()] );

											//Substact the Race and add back a slot.
											$this->slottingLotteryArray[$submission->getSecondChoice()->getGroupID()->getId()]['availableSlots']++;
											$this->slottingLotteryArray[$submission->getSecondChoice()->getGroupID()->getId()][$race]--;
										} else {
											$awardFirstChoice = false;
										}
									} else {
										$awardFirstChoice = true;
									}
								}

								if( $awardFirstChoice ) {
									//Second choice was null, so lets go ahead and Awarded.

									//mark submission status as awarded and store awarded school name and the acceptance URL
									$completedLottery['offered'][$submission->getId() . '-' . $submission->getFirstChoice()->getId()] = array( 'submission' => $submission , 'submissionID' => $submission->getId() , 'choiceID' => $submission->getFirstChoice() );
									$this->recordAudit( $emLookup , 2 , $submission->getId() , $submission->getStudentId() );

									if( $maybe_priority != 'Priority' ){
										$this->slottingLotteryArray[$firstChoiceGroupID]['availableSlots']--;
										$this->slottingLotteryArray[$firstChoiceGroupID][$race]++;
										$this->slottingLotteryArray[$firstChoiceGroupID]['changed'] = true;
									}
									//var_dump( 'Awarded Submission: ' . $submission->getId() );

									//Since we have awarded the First Choice, lets go ahead and remove it from the WAlking List.
									unset( $submissionLotteryArray[$maybe_priority][$index] );

									//Reset the OuterLoop to Start all over again at the very top.
									$outerLoopReset = -1;
									break;
								}
							}
						} else {

							//Doesn't pass RacialCalculationIncrease, so need to waitList for First Choice always if Round 2.
							if( (
									$roundNumber == 2
									|| $roundNumber == 3
								)
								&& !isset( $completedLottery['wait listed'][$submission->getId()] )
							) {
								$completedLottery['wait listed'][$submission->getId()] = array( 'submission' => $submission , 'submissionID' => $submission->getId() , 'wait listed' => $submission->getFirstChoice() );
							}
						}
					}

					//no, there is not an available slot

					//First Choice did not have any available Slots, so lets Wait lists the First Choice already if Round 2.
					if( (
							$roundNumber == 2
							|| $roundNumber == 3
						)
						&& !isset( $completedLottery['wait listed'][$submission->getId()] ) && !isset( $completedLottery['offered'][$submission->getId() . '-' . $submission->getFirstChoice()->getId()] )
					) {
						$completedLottery['wait listed'][$submission->getId()] = array( 'submission' => $submission , 'submissionID' => $submission->getId() , 'wait listed' => $submission->getFirstChoice() );
					}

					//Making sure there is a SecondChoice to Test.
					if ( $submission->getSecondChoice() != null ) {

						//No need to run this through all the tests again because its already been awarded.
						if( isset( $completedLottery['offered and wait listed'][$submission->getId() . '-' . $submission->getSecondChoice()->getId()] ) ) {
							continue;
						}

						//check student's second choice against the DOI table...is there an available slot?
						$secondChoiceGroupID = $submission->getSecondChoice()->getGroupID()->getId();
						//var_dump( 'Trying Second Choice Submission: ' . $submission->getId() . ' GroupID: ' . $secondChoiceGroupID );

						//TODO: Need to remove this once the import functions force all grades/schools to have slot information.
						if ( !isset( $this->slottingLotteryArray[$secondChoiceGroupID]['availableSlots'] ) ) {
							throw new \Exception( 'Second Choice School Group not found in Slotting Lottery Array' , 2001 );
						}

						if ( $this->slottingLotteryArray[$secondChoiceGroupID]['availableSlots'] > 0 ) {

							//var_dump( $secondChoiceGroupID . ' has available slots' );

							if( $this->passesRacialCalculationIncrease( $secondChoiceGroupID , $race ) ) {

								//If the current status is NOT Wait Listed already then provide a offered but wait list slot.
								if( $submission->getSubmissionStatus()->getId() != 10 ) {

									//yes, there is an available slot
									//mark submission status as awarded and store awarded school name and the acceptance URL
									if( !isset( $completedLottery['offered and wait listed'][$submission->getId() . '-' . $submission->getSecondChoice()->getId()] ) ) {

										$this->recordAudit( $emLookup , 2 , $submission->getId() , $submission->getStudentId() );
										if( $maybe_priority != 'Priority' ){
											$this->slottingLotteryArray[$secondChoiceGroupID]['availableSlots']--;
											$this->slottingLotteryArray[$secondChoiceGroupID][$race]++;
											$this->slottingLotteryArray[$secondChoiceGroupID]['changed'] = true;
										}

										//Awarding Second Choice, but we need to make sure to WaitList for First Choice.
										$completedLottery['offered and wait listed'][$submission->getId() . '-' . $submission->getSecondChoice()->getId()] = array( 'submission' => $submission , 'submissionID' => $submission->getId() , 'choiceID' => $submission->getSecondChoice() , 'wait listed' => $submission->getFirstChoice() );

										if( isset( $completedLottery['non-awarded'][$submission->getId()] ) ) {
											unset( $completedLottery['non-awarded'][$submission->getId()] );
										}
										//Reset the Outerloop to start over.
										$outerLoopReset = -1;
										break;
									}
								}
							} else {
								if ( $roundNumber == 1 ) {
									if( !isset( $completedLottery['non-awarded'][$submission->getId()] ) && !isset( $completedLottery['offered and wait listed'][$submission->getId() . '-' . $submission->getSecondChoice()->getId()] ) ) {
										$completedLottery['non-awarded'][$submission->getId()] = array( 'submission' => $submission , 'submissionID' => $submission->getId() , 'choiceID' => null );
										$this->recordAudit( $emLookup , 3 , $submission->getId() , $submission->getStudentId() );
									}
								} else {

									//No Need for this as they were already waitlisted for their first choice.

									//else, change status to denied due to space
									//if( !isset( $completedLottery['denied due to space'][$submission->getId()] ) ) {
									//	$completedLottery['denied due to space'][$submission->getId()] = array( 'submission' => $submission , 'submissionID' => $submission->getId() , 'choiceID' => null );
									//	$this->recordAudit( $emLookup , 6 , $submission->getId() , $submission->getStudentId() );
									//}
								}
							}
						} else {
							//no, there is not an available slot
							if ( $roundNumber == 1 ) {
								//if first round, change status to non awarded
								if( !isset( $completedLottery['non-awarded'][$submission->getId()] ) && !isset( $completedLottery['offered and wait listed'][$submission->getId() . '-' . $submission->getSecondChoice()->getId()] ) ) {
									$completedLottery['non-awarded'][$submission->getId()] = array( 'submission' => $submission , 'submissionID' => $submission->getId() , 'choiceID' => null );
									$this->recordAudit( $emLookup , 3 , $submission->getId() , $submission->getStudentId() );
								}
							} else {
								//No Need for this as they were already waitlisted for their first choice.

								//else, change status to denied due to space
								//if( !isset( $completedLottery['denied due to space'][$submission->getId()] ) ) {
								//	$completedLottery['denied due to space'][$submission->getId()] = array( 'submission' => $submission , 'submissionID' => $submission->getId() , 'choiceID' => null );
								//	$this->recordAudit( $emLookup , 6 , $submission->getId() , $submission->getStudentId() );
								//}
							}
						}
					} else {
						if ( $roundNumber == 1 ) {
							//if first round, change status to non awarded
							if( !isset( $completedLottery['non-awarded'][$submission->getId()] ) ) {
								$completedLottery['non-awarded'][$submission->getId()] = array( 'submission' => $submission , 'submissionID' => $submission->getId() , 'choiceID' => null );
								$this->recordAudit( $emLookup , 3 , $submission->getId() , $submission->getStudentId() );
							}
						} else {
							//NO LONGER NEEDED because they would have been Wait Listed for the First Choice.

							//else, change status to denied due to space
							//if( !isset( $completedLottery['denied due to space'][$submission->getId()] ) ) {
							//	$completedLottery['denied due to space'][$submission->getId()] = array( 'submission' => $submission , 'submissionID' => $submission->getId() , 'choiceID' => null );
							//	$this->recordAudit( $emLookup , 6 , $submission->getId() , $submission->getStudentId() );
							//}
						}
					}
				}
			}
		}

		$updatedDateTime = new \DateTime();
		foreach( $this->slottingLotteryArray as $groupID => $group ) {

			$schoolGroup = $emLookup->getRepository('IIABStudentTransferBundle:SchoolGroup')->find( $groupID );

			if( $schoolGroup != null ) {
				$newCurrentEnrollment = new CurrentEnrollmentSettings();
				$newCurrentEnrollment->setGroupId( $schoolGroup );
				$newCurrentEnrollment->setBlack( $group['black'] );
				$newCurrentEnrollment->setWhite( $group['white'] );
				$newCurrentEnrollment->setOther( $group['other'] );
				$newCurrentEnrollment->setMaxCapacity( $group['maxCapacity'] );
				$newCurrentEnrollment->setAddedDateTime( $updatedDateTime );
				$newCurrentEnrollment->setEnrollmentPeriod( $openEnrollmentPeriod );
				$emLookup->persist( $newCurrentEnrollment );
			} else {
				throw new \Exception( 'School Group not found some how. Please double check that the school group didn\'t get deleted while running lottery' , 2002 );
			}
		}

		//LotteryCompleted, now lets update the database to reflect the changes.
		foreach( $completedLottery as $status => $submissions ) {

			$statusEntity = $emLookup->getRepository('IIABStudentTransferBundle:SubmissionStatus')->findBy( array(
				'status' => $status
			) );
			if( $statusEntity != null ) {
				$statusEntity = $statusEntity[0];
				//Status is found, lets update the submission.
				foreach( $submissions as $submission ) {
					$submissionObject = $emLookup->getRepository( 'IIABStudentTransferBundle:Submission' )->find( $submission['submissionID'] );

					if( isset( $submission['choiceID'] ) &&  $submission['choiceID'] != null ) {
						//this is used to see when to update the url and the awarded SchoolID.

						$url = $submissionObject->getId() . '.' . rand( 10 , 999 );
						$submissionObject->setUrl( $url );
						$submissionObject->setAwardedSchoolID( $submission['choiceID'] );
						$submissionObject->setRoundExpires( $roundNumber );

						if( $roundNumber == 3 ){
							$submissionObject->setmanualAwardDate( new \DateTime() );
						}

						//Used to run the reporting so we can build the specific URL.
						$reporting = new SlottingReport();
						$reporting->setEnrollmentPeriod( $submissionObject->getEnrollmentPeriod() );
						$reporting->setRound( $roundNumber );
						$reporting->setSchoolID( $submissionObject->getAwardedSchoolID() );
						$reporting->setStatus( $status );
						$reporting->setCurrentSchool( $submissionObject->getCurrentSchool() );

						$emLookup->persist( $reporting );


					}

					if( isset( $submission['wait listed'] ) && $submission['wait listed'] != null ) {
						//Added in a new Wait Listed Entry.

						//If the current status is NOT Wait Listed Already then provide a wait list slot.
						if( $submissionObject->getSubmissionStatus()->getId() != 10 ) {
							//In Round 2, Round 1 Wait Listed People cannot be Wait Listed again.
							$waitList = new WaitList();
							$waitList->setOpenEnrollment( $openEnrollmentPeriod );
							$waitList->setWaitListDateTime( new \DateTime() );
							$waitList->setChoiceSchool( $submission['wait listed'] );
							$submissionObject->addWaitList( $waitList );

							$emLookup->persist( $waitList );
						}

					}
					$submissionObject->setSubmissionStatus( $statusEntity );
				}
			}
		}

		$emLookup->flush();

		if( $roundNumber == 3){
			$this->writeLoggingFile( $completedLottery , 'after' , 'after-lottery' );
		} else {
			$this->writeLoggingFile($completedLottery, 'after', 'lottery-round-' . $roundNumber);
		}

		return true;

		//email student services that the lottery is complete and that the reports can be downloaded
	}

	/**
	 * Looks for any expired URL for any awarded submission.
	 *
	 * @param \Doctrine\Common\Persistence\ObjectManager $emLookup
	 */
	public function checkForExpiredURLs( $emLookup ) {

		//grab all awarded Accountability Act Submissions for this open enrollment period
		$status = 2; //Status Awarded (2)
		$today = new \DateTime();

		$statusEntity = $emLookup->getRepository('IIABStudentTransferBundle:SubmissionStatus')->find(4); //offered and declined

		$lotteries = $emLookup->getRepository('IIABStudentTransferBundle:Lottery')->findAll();

		/** @var \IIAB\StudentTransferBundle\Entity\Lottery $lottery */
		foreach( $lotteries as $lottery ) {

			$firstMailDate = $lottery->getMailFirstRoundDate();
			$secondMailDate = $lottery->getMailSecondRoundDate();
			$round = 0;

			if( $firstMailDate == null && $secondMailDate == null )
				continue;

			if( $firstMailDate != null ) {

				$window_length = $lottery->getFirstRoundAcceptanceWindow();
				$window_length = ( $window_length != null && $window_length )
					? $window_length : 11;

				$firstDateInterval = $today->diff( $firstMailDate );

				if( $firstDateInterval->invert == 1
					&& $firstDateInterval->d >= $window_length
					&& $firstDateInterval->i >= 0 ) {
					$round = 1;
				}
			}

			//No need to run if there is not round to check, so skip over round 1;
			if( $round == 1 ) {
				$submissions = $emLookup->getRepository('IIABStudentTransferBundle:Submission')->createQueryBuilder('s')
					->where( 's.submissionStatus = :submission' )
					->andWhere( 's.enrollmentPeriod = :enrollment' )
					->andWhere( 's.roundExpires = :round' )
					->andWhere( 's.formID = :form' )
					->setParameters( array(
						'submission'	=> $status,
						'enrollment'	=> $lottery->getEnrollmentPeriod()->getId(),
						'round'			=> $round,
						'form'			=> 6 // Only Accountability Transfer go into the Lottery
					) )
					->getQuery()
					->getResult()
				;

				/** @var \IIAB\StudentTransferBundle\Entity\Submission $submission */
				foreach( $submissions as $submission ) {
					$submission->setSubmissionStatus( $statusEntity );
					//submission status changed from "offered" to "offered and declined"

					$this->container->get( 'stw.email' )->sendAutoDeclinedEmail( $submission );

					//Update the Current Enrollment to reflect the auto declined.
					$currentEnrollmentChanged = $emLookup->getRepository('IIABStudentTransferBundle:CurrentEnrollmentSettings')->findOneBy( array(
						'enrollmentPeriod' => $submission->getEnrollmentPeriod(),
						'groupId' => $submission->getAwardedSchoolID()->getGroupID()
					) , array( 'addedDateTime' => 'DESC' ) );

					if( $currentEnrollmentChanged != null ) {
						$race = trim( strtolower( $submission->getRace() ) );
						switch( $race ) {

							case 'black':
							case 'black/african american':
								$currentEnrollmentChanged->setBlack( $currentEnrollmentChanged->getBlack() - 1 );
								break;

							case 'other':
								$currentEnrollmentChanged->setOther( $currentEnrollmentChanged->getOther() - 1 );
								break;

							case 'white':
								$currentEnrollmentChanged->setWhite( $currentEnrollmentChanged->getWhite() - 1 );
								break;
						}
						$emLookup->persist( $currentEnrollmentChanged );
					}

					$this->recordAudit( $emLookup , 5 , $submission->getId() , $submission->getStudentId() );

					//Used to run the reporting so we can build the specific URL.
					$reporting = new SlottingReport();
					$reporting->setEnrollmentPeriod( $submission->getEnrollmentPeriod() );
					$reporting->setRound( $submission->getRoundExpires() );
					$reporting->setSchoolID( $submission->getAwardedSchoolID() );
					$reporting->setStatus( $statusEntity->getStatus() );

					$emLookup->persist( $reporting );
				}

				unset( $submissions );
			}

			if( $secondMailDate != null ) {

				$window_length = $lottery->getSecondRoundAcceptanceWindow();
				$window_length = ( $window_length != null && $window_length )
					? $window_length : 11;

				$secondDateInterval = $today->diff( $secondMailDate );
				if( $secondDateInterval->invert == 1 && $secondDateInterval->d >= $window_length && $secondDateInterval->i >= 0 ) {
					$round = 2;
				}
			}

			//No need to run if there is not round to check, so skip over round 2;
			if( $round == 2 ) {
				$submissionsRound2 = $emLookup->getRepository('IIABStudentTransferBundle:Submission')->createQueryBuilder('s')
					->where( 's.submissionStatus = :submission' )
					->andWhere( 's.enrollmentPeriod = :enrollment' )
					->andWhere( 's.roundExpires = :round' )
					->andWhere( 's.formID = :form' )
					->setParameters( array(
						'submission'	=> $status,
						'enrollment'	=> $lottery->getEnrollmentPeriod()->getId(),
						'round'			=> $round,
						'form'			=> 6 // Only Accountability Act Transfer go into the Lottery
					) )
					->getQuery()
					->getResult()
				;
				/** @var \IIAB\StudentTransferBundle\Entity\Submission $submission */
				foreach( $submissionsRound2 as $submission ) {
					$submission->setSubmissionStatus( $statusEntity );
					//submission status changed from "offered" to "offered and declined"

					$this->container->get( 'stw.email' )->sendAutoDeclinedEmail( $submission );

					//Update the Current Enrollment to reflect the auto declined.
					$currentEnrollmentChanged = $emLookup->getRepository('IIABStudentTransferBundle:CurrentEnrollmentSettings')->findOneBy( array(
						'enrollmentPeriod' => $submission->getEnrollmentPeriod(),
						'groupId' => $submission->getAwardedSchoolID()->getGroupID()
					) , array( 'addedDateTime' => 'DESC' ) );

					if( $currentEnrollmentChanged != null ) {
						$race = trim( strtolower( $submission->getRace() ) );
						switch( $race ) {

							case 'black':
							case 'black/african american':
								$currentEnrollmentChanged->setBlack( $currentEnrollmentChanged->getBlack() - 1 );
								break;

							case 'other':
								$currentEnrollmentChanged->setOther( $currentEnrollmentChanged->getOther() - 1 );
								break;

							case 'white':
								$currentEnrollmentChanged->setWhite( $currentEnrollmentChanged->getWhite() - 1 );
								break;
						}
						$emLookup->persist( $currentEnrollmentChanged );
					}

					$this->recordAudit( $emLookup , 5 , $submission->getId() , $submission->getStudentId() );

					//Used to run the reporting so we can build the specific URL.
					$reporting = new SlottingReport();
					$reporting->setEnrollmentPeriod( $submission->getEnrollmentPeriod() );
					$reporting->setRound( $submission->getRoundExpires() );
					$reporting->setSchoolID( $submission->getAwardedSchoolID() );
					$reporting->setStatus( $statusEntity->getStatus() );

					$emLookup->persist( $reporting );
				}
			}

			unset( $firstDateInterval , $secondDateInterval );

			/*****************************************************************
			 * Now processing the Awarded BUT Wait Listed Submission.
			 * To Flip them back to just "Wait Listed"
			 *****************************************************************/

			$firstAwardedButWaitListedMailDate = $lottery->getMailFirstRoundDate();
			$secondAwardedButWaitListedMailDate = $lottery->getMailSecondRoundDate();
			$round = 0;

			if( $firstMailDate == null && $secondMailDate == null )
				continue;

			if( $firstAwardedButWaitListedMailDate != null ) {

				$window_length = $lottery->getFirstRoundAcceptanceWindow();
				$window_length = ( $window_length != null && $window_length )
					? $window_length : 11;

				$firstDateInterval = $today->diff( $firstAwardedButWaitListedMailDate );
				if( $firstDateInterval->invert == 1 && $firstDateInterval->d >= $window_length && $firstDateInterval->i >= 0 ) {
					$round = 1;
				}
			}
			$statusEntityWaitListed = $emLookup->getRepository('IIABStudentTransferBundle:SubmissionStatus')->find(10); //Wait Listed

			$submissions = $emLookup->getRepository('IIABStudentTransferBundle:Submission')->createQueryBuilder('s')
				->where( 's.submissionStatus = :submission' )
				->andWhere( 's.enrollmentPeriod = :enrollment' )
				->andWhere( 's.roundExpires = :round' )
				->andWhere( 's.formID = :form' )
				->setParameters( array(
					'submission'	=> 11, //Awarded but Wait Listed
					'enrollment'	=> $lottery->getEnrollmentPeriod()->getId(),
					'round'			=> $round,
					'form'			=> 6 // Only Accountability Act Transfer go into the Lottery
				) )
				->getQuery()
				->getResult()
			;
			if( count( $submissions ) > 0 ) {
				/** @var \IIAB\StudentTransferBundle\Entity\Submission $submission */
				foreach( $submissions as $submission ) {
					$submission->setSubmissionStatus( $statusEntityWaitListed );
					//submission status changed from "offered" to "offered and declined"

					$this->container->get( 'stw.email' )->sendAutoDeclinedEmail( $submission );

					//Update the Current Enrollment to reflect the auto declined.
					$currentEnrollmentChanged = $emLookup->getRepository('IIABStudentTransferBundle:CurrentEnrollmentSettings')->findOneBy( array(
						'enrollmentPeriod' => $submission->getEnrollmentPeriod(),
						'groupId' => $submission->getAwardedSchoolID()->getGroupID()
					) , array( 'addedDateTime' => 'DESC' ) );

					if( $currentEnrollmentChanged != null ) {
						$race = trim( strtolower( $submission->getRace() ) );
						switch( $race ) {

							case 'black':
							case 'black/african american':
								$currentEnrollmentChanged->setBlack( $currentEnrollmentChanged->getBlack() - 1 );
								break;

							case 'other':
								$currentEnrollmentChanged->setOther( $currentEnrollmentChanged->getOther() - 1 );
								break;

							case 'white':
								$currentEnrollmentChanged->setWhite( $currentEnrollmentChanged->getWhite() - 1 );
								break;
						}
						$emLookup->persist( $currentEnrollmentChanged );
					}

					$this->recordAudit( $emLookup , 31 , $submission->getId() , $submission->getStudentId() );

					//Used to run the reporting so we can build the specific URL.
					$reporting = new SlottingReport();
					$reporting->setEnrollmentPeriod( $submission->getEnrollmentPeriod() );
					$reporting->setRound( $submission->getRoundExpires() );
					$reporting->setSchoolID( $submission->getAwardedSchoolID() );
					$reporting->setStatus( $statusEntity->getStatus() );

					$emLookup->persist( $reporting );
				}
			}
			$submissions = null;

			if( $secondAwardedButWaitListedMailDate != null ) {

				$window_length = $lottery->getSecondRoundAcceptanceWindow();
				$window_length = ( $window_length != null && $window_length )
					? $window_length : 11;

				$secondDateInterval = $today->diff( $secondAwardedButWaitListedMailDate );
				if( $secondDateInterval->invert == 1 && $secondDateInterval->d >= $window_length && $secondDateInterval->i >= 0 ) {
					$round = 2;
				}
			}
			$submissions = $emLookup->getRepository('IIABStudentTransferBundle:Submission')->createQueryBuilder('s')
				->where( 's.submissionStatus = :submission' )
				->andWhere( 's.enrollmentPeriod = :enrollment' )
				->andWhere( 's.roundExpires = :round' )
				->andWhere( 's.formID = :form' )
				->setParameters( array(
					'submission'	=> 11, //Awarded but Wait Listed
					'enrollment'	=> $lottery->getEnrollmentPeriod()->getId(),
					'round'			=> $round,
					'form'			=> 6 // Only Accountability Act Transfer go into the Lottery
				) )
				->getQuery()
				->getResult()
			;
			if( count( $submissions ) > 0 ) {
				/** @var \IIAB\StudentTransferBundle\Entity\Submission $submission */
				foreach( $submissions as $submission ) {
					$submission->setSubmissionStatus( $statusEntityWaitListed );
					//submission status changed from "offered" to "offered and declined"

					$this->container->get( 'stw.email' )->sendAutoDeclinedEmail( $submission );

					//Update the Current Enrollment to reflect the auto declined.
					$currentEnrollmentChanged = $emLookup->getRepository('IIABStudentTransferBundle:CurrentEnrollmentSettings')->findOneBy( array(
						'enrollmentPeriod' => $submission->getEnrollmentPeriod(),
						'groupId' => $submission->getAwardedSchoolID()->getGroupID()
					) , array( 'addedDateTime' => 'DESC' ) );

					if( $currentEnrollmentChanged != null ) {
						$race = trim( strtolower( $submission->getRace() ) );
						switch( $race ) {

							case 'black':
							case 'black/african american':
								$currentEnrollmentChanged->setBlack( $currentEnrollmentChanged->getBlack() - 1 );
								break;

							case 'other':
								$currentEnrollmentChanged->setOther( $currentEnrollmentChanged->getOther() - 1 );
								break;

							case 'white':
								$currentEnrollmentChanged->setWhite( $currentEnrollmentChanged->getWhite() - 1 );
								break;
						}
						$emLookup->persist( $currentEnrollmentChanged );
					}

					$this->recordAudit( $emLookup , 31 , $submission->getId() , $submission->getStudentId() );

					//Used to run the reporting so we can build the specific URL.
					$reporting = new SlottingReport();
					$reporting->setEnrollmentPeriod( $submission->getEnrollmentPeriod() );
					$reporting->setRound( $submission->getRoundExpires() );
					$reporting->setSchoolID( $submission->getAwardedSchoolID() );
					$reporting->setStatus( $statusEntity->getStatus() );

					$emLookup->persist( $reporting );
				}
			}
			$submissions = null;
		}

        /**** INSERT CODE HERE ****/

		// Manual Award Submissions
        $submissions = $emLookup
        	->getRepository('IIABStudentTransferBundle:Submission')
        	->createQueryBuilder('s')
            ->where( 's.submissionStatus = :submission' )
			->andWhere( 's.enrollmentPeriod = :enrollment' )
            ->andWhere( 's.roundExpires = :round' )
            ->setParameters( array(
                'submission'	=> 2, //Offered Status
				'enrollment'	=> $lottery->getEnrollmentPeriod()->getId(),
                'round'			=> 3, // Special Manual Award Id
            ) )
            ->getQuery()
            ->getResult()
        ;

        /** @var \IIAB\StudentTransferBundle\Entity\Submission $submission */
        foreach( $submissions as $submission ) {

			if( $submission->getManualAwardDate() != null ) {
				$days = ( $submission->getFormID()->getAcceptanceWindow() != null && $submission->getFormID()->getAcceptanceWindow() > 0 )
					? $submission->getFormID()->getAcceptanceWindow() : 10;
				$urlExpires = new \DateTime( $submission->getManualAwardDate()
					->format( 'm/d/Y 11:59:59' ) );
				$urlExpires->modify( '+'.$days.' days' );
			}

			if( $today > $urlExpires ) {
				$submission->setSubmissionStatus( $statusEntity );
				//submission status changed from "offered" to "offered and declined"

				$this->container->get( 'stw.email' )->sendAutoDeclinedEmail( $submission );

				//Update the Current Enrollment to reflect the auto declined.
				$currentEnrollmentChanged = $emLookup->getRepository( 'IIABStudentTransferBundle:CurrentEnrollmentSettings' )->findOneBy( array(
					'enrollmentPeriod' => $submission->getEnrollmentPeriod() ,
					'groupId' => $submission->getAwardedSchoolID()->getGroupID()
				) , array( 'addedDateTime' => 'DESC' ) );

				if( $currentEnrollmentChanged != null ) {
					$race = trim( strtolower( $submission->getRace() ) );
					switch( $race ) {

						case 'black':
						case 'black/african american':
							$currentEnrollmentChanged->setBlack( $currentEnrollmentChanged->getBlack() - 1 );
							break;

						case 'other':
							$currentEnrollmentChanged->setOther( $currentEnrollmentChanged->getOther() - 1 );
							break;

						case 'white':
							$currentEnrollmentChanged->setWhite( $currentEnrollmentChanged->getWhite() - 1 );
							break;
					}
					$emLookup->persist( $currentEnrollmentChanged );
				}

				$this->recordAudit( $emLookup , 5 , $submission->getId() , $submission->getStudentId() );

				//Used to run the reporting so we can build the specific URL.
				$reporting = new SlottingReport();
				$reporting->setEnrollmentPeriod( $submission->getEnrollmentPeriod() );
				$reporting->setRound( $submission->getRoundExpires() );
				$reporting->setSchoolID( $submission->getAwardedSchoolID() );
				$reporting->setStatus( $statusEntity->getStatus() );

				$emLookup->persist( $reporting );
			}
        }

		// Manual Award Submissions Wait List
		$submissions = $emLookup->getRepository('IIABStudentTransferBundle:Submission')->createQueryBuilder('s')
			->where( 's.submissionStatus = :submission' )
			->andWhere( 's.enrollmentPeriod = :enrollment' )
			->andWhere( 's.roundExpires = :round' )
			->setParameters( array(
				'submission'	=> 11, //Offered and Waitlisted
				'enrollment'	=> $lottery->getEnrollmentPeriod()->getId(),
				'round'			=> 3, // Special Manual Award Id
			) )
			->getQuery()
			->getResult()
		;

		/** @var \IIAB\StudentTransferBundle\Entity\Submission $submission */
		foreach( $submissions as $submission ) {

			if( $submission->getManualAwardDate() != null ) {

				$days = ( $submission->getFormID() != null && $submission->getFormID() > 0 )
					? $submission->getFormID()->getAcceptanceWindow() : 10;

				$urlExpires = new \DateTime( $submission->getManualAwardDate()
					->format( 'm/d/Y 11:59:59' ) );
				$urlExpires->modify( '+'.$days.' days' );
			}

			if( $today > $urlExpires ) {
				$submission->setSubmissionStatus($statusEntity);
				//submission status changed from "offered and waitlisted" to "offered and declined"

				$this->container->get( 'stw.email' )->sendAutoDeclinedEmail( $submission );

				//Update the Current Enrollment to reflect the auto declined.
				$currentEnrollmentChanged = $emLookup->getRepository( 'IIABStudentTransferBundle:CurrentEnrollmentSettings' )->findOneBy( array(
					'enrollmentPeriod' => $submission->getEnrollmentPeriod() ,
					'groupId' => $submission->getAwardedSchoolID()->getGroupID()
				) , array( 'addedDateTime' => 'DESC' ) );

				if( $currentEnrollmentChanged != null ) {
					$race = trim( strtolower( $submission->getRace() ) );
					switch( $race ) {

						case 'black':
						case 'black/african american':
							$currentEnrollmentChanged->setBlack( $currentEnrollmentChanged->getBlack() - 1 );
							break;

						case 'other':
							$currentEnrollmentChanged->setOther( $currentEnrollmentChanged->getOther() - 1 );
							break;

						case 'white':
							$currentEnrollmentChanged->setWhite( $currentEnrollmentChanged->getWhite() - 1 );
							break;
					}
					$emLookup->persist( $currentEnrollmentChanged );
				}

				$this->recordAudit( $emLookup , 5 , $submission->getId() , $submission->getStudentId() );

				//Used to run the reporting so we can build the specific URL.
				$reporting = new SlottingReport();
				$reporting->setEnrollmentPeriod( $submission->getEnrollmentPeriod() );
				$reporting->setRound( $submission->getRoundExpires() );
				$reporting->setSchoolID( $submission->getAwardedSchoolID() );
				$reporting->setStatus( $statusEntity->getStatus() );

				$emLookup->persist( $reporting );
			}
		}

        unset( $submissions );

		$emLookup->flush();
	}


	/**
	 * Get the available slots information from the Database.
	 *
	 * @param OpenEnrollment $openEnrollment
	 *
	 * @return array
	 */
	private function getSlottingInformation( OpenEnrollment $openEnrollment ) {

		$availableSlots = array();

		$getGroups = $this->container->get('doctrine')->getRepository('IIABStudentTransferBundle:SchoolGroup')->createQueryBuilder('s')
			->leftJoin('\IIAB\StudentTransferBundle\Entity\ADM' , 'adm' , Join::WITH , 'adm.groupID = s.id')
			->where('adm.enrollmentPeriod = :enrollment')
			->setParameter('enrollment' , $openEnrollment )
			->orderBy('s.name','ASC')
			->getQuery()
			->getResult();
		//$getGroups = $this->container->get('doctrine')->getRepository( 'IIABStudentTransferBundle:SchoolGroup' )->getEnrollmentSchools( $openEnrollment );

		/** @var \IIAB\StudentTransferBundle\Entity\SchoolGroup $group */
		foreach ( $getGroups as $group ) {

			$currentEnrollment = $this->container->get('doctrine')->getRepository( 'IIABStudentTransferBundle:CurrentEnrollmentSettings' )->findOneBy( [ 'groupId' => $group , 'enrollmentPeriod' => $openEnrollment ] , [ 'addedDateTime' => 'DESC' ] );

			if ( $currentEnrollment == null ) {
				$availableSlots[$group->getId()] = array(
					'availableSlots' => 0 ,
					'originalAvailableSlots' => 0 ,
					'lastAvailableSlots' => 0 ,
					'maxCapacity' => 0 ,
					'white' => 0 ,
					'black' => 0 ,
					'other' => 0 ,
					'majorityRace' => '' ,
					'changed' => false ,
					'percentages' => '' ,
				);
			} else {

				$groupID = $group->getId();

				//If the ID is not set, lets default everything to zero.
				if( !isset( $availableSlots[$groupID] ) ) {
					$availableSlots[$groupID] = array(
						'availableSlots' => 0 ,
						'originalAvailableSlots' => 0 ,
						'lastAvailableSlots' => 0 ,
						'maxCapacity' => 0 ,
						'white' => 0 ,
						'black' => 0 ,
						'other' => 0 ,
						'majorityRace' => '' ,
						'changed' => false ,
						'percentages' => '' ,
					);
				}

				$currentPopulationSum = $currentEnrollment->getSum();
				$maxCapacity = $currentEnrollment->getMaxCapacity();

				//Get the total Number of slots available.
				$totalSlots = $maxCapacity - $currentPopulationSum;

				//Ensure we have a zero or positive number.
				if( $totalSlots < 0 ) {
					$totalSlots = 0;
				}

				if( $currentPopulationSum > 0 ) {
					$blackPercent = number_format( ( $currentEnrollment->getBlack() / $currentPopulationSum ) * 100 , 1 );
					$otherPercent = number_format( ( $currentEnrollment->getOther() / $currentPopulationSum ) * 100 , 1 );
					$whitePercent = number_format( ( $currentEnrollment->getWhite() / $currentPopulationSum ) * 100 , 1 );
				} else {
					$blackPercent = number_format( 0 , 1 );
					$otherPercent = number_format( 0 , 1 );
					$whitePercent = number_format( 0 , 1 );
				}
				$majorityRace = '';

				if( $blackPercent >= 50.1 ) {
					$majorityRace = 'black';
				}
				if( $whitePercent >= 50.1 ) {
					$majorityRace = 'white';
				}

				$availableSlots[$groupID]['availableSlots'] = $totalSlots;
				$availableSlots[$groupID]['originalAvailableSlots'] = $totalSlots;
				$availableSlots[$groupID]['lastAvailableSlots'] = $totalSlots;
				$availableSlots[$groupID]['currentCapacity'] = $currentPopulationSum;
				$availableSlots[$groupID]['maxCapacity'] = $maxCapacity;
				$availableSlots[$groupID]['black'] = $currentEnrollment->getBlack();
				$availableSlots[$groupID]['other'] = $currentEnrollment->getOther();
				$availableSlots[$groupID]['white'] = $currentEnrollment->getWhite();
				$availableSlots[$groupID]['majorityRace'] = $majorityRace;
				$availableSlots[$groupID]['changed'] = false;
				$availableSlots[$groupID]['percentages'] = sprintf( 'Black: %s - Other: %s - White: %s' , $blackPercent , $otherPercent , $whitePercent );
			}
		}

		return $availableSlots;
	}

	/**
	 * Does the increase of a specific race say under the required Limits of 50.1%
	 *
	 * @param integer $groupID
	 * @param string $race
	 *
	 * @return bool
	 */
	private function passesRacialCalculationIncrease( $groupID = 0 , $race = '' ) {
		return true;
		if( $groupID == 0 || empty( $race ) ) {
			return false;
		}

		//var_dump( $groupID . ' - ' . $race );

		$currentBlackEnrollment = $this->slottingLotteryArray[$groupID]['black'];
		$currentOtherEnrollment = $this->slottingLotteryArray[$groupID]['other'];
		$currentWhiteEnrollment = $this->slottingLotteryArray[$groupID]['white'];

		$currentCapacity = $currentBlackEnrollment + $currentOtherEnrollment + $currentWhiteEnrollment;

		$majorityRace = $this->slottingLotteryArray[$groupID]['majorityRace'];

		if( $race == $majorityRace){
			return false;
		}


		//Increase the Individual Race and overall Capacity to get new calculations.
		switch( $race ) {

			case 'black':
				$currentBlackEnrollment++;
				break;

			case 'white':
				$currentWhiteEnrollment++;
				break;

			default:
				$currentOtherEnrollment++;
				break;
		}
		$currentCapacity++;

		//Need to increase the Racial numbers and recalculate everything.
		$blackPercent = number_format( ( $currentBlackEnrollment / $currentCapacity ) * 100 , 1 );
		$otherPercent = number_format( ( $currentOtherEnrollment / $currentCapacity ) * 100 , 1 );
		$whitePercent = number_format( ( $currentWhiteEnrollment / $currentCapacity ) * 100 , 1 );

		if( $majorityRace == 'black' ) {
			//Majority Race is black, so we need to make sure the increase did not bring the race below 50.1
			if( $blackPercent >= 50.1 && $whitePercent < 50.1 ) {
				$this->slottingLotteryArray[$groupID]['percentages'] = sprintf( 'Black: %s - Other: %s - White: %s' , $blackPercent , $otherPercent , $whitePercent );
				return true;
			}

		} elseif( $majorityRace == 'white' ) {
			//Majority Race is white, so we need to make sure the increase did not bring the race below 50.1
			if( $whitePercent >= 50.1 && $blackPercent < 50.1 ) {
				$this->slottingLotteryArray[$groupID]['percentages'] = sprintf( 'Black: %s - Other: %s - White: %s' , $blackPercent , $otherPercent , $whitePercent );
				return true;
			}

		} else {
			//There is not a majority RACE. So none of the percents should be GREATER than 50.1
			if( $blackPercent < 50.1 && $whitePercent < 50.1 ) {
				$this->slottingLotteryArray[$groupID]['percentages'] = sprintf( 'Black: %s - Other: %s - White: %s' , $blackPercent , $otherPercent , $whitePercent );
				return true;
			}
		}

		return false;
	}

	/**
	 * Does the decrease of a specific race say under the required Limits of 50.1%
	 *
	 * @param integer $groupID
	 * @param string $race
	 *
	 * @return bool
	 */
	private function passesRacialCalculationDecrease( $groupID = 0 , $race = '' ) {
		return true;
		if( $groupID == 0 || empty( $race ) ) {
			return false;
		}

		//var_dump( $groupID . ' - ' . $race );

		$currentBlackEnrollment = $this->slottingLotteryArray[$groupID]['black'];
		$currentOtherEnrollment = $this->slottingLotteryArray[$groupID]['other'];
		$currentWhiteEnrollment = $this->slottingLotteryArray[$groupID]['white'];

		$currentCapacity = $currentBlackEnrollment + $currentOtherEnrollment + $currentWhiteEnrollment;

		$majorityRace = $this->slottingLotteryArray[$groupID]['majorityRace'];

		//Increase the Individual Race and overall Capacity to get new calculations.
		switch( $race ) {

			case 'black':
				$currentBlackEnrollment--;
				break;

			case 'white':
				$currentWhiteEnrollment--;
				break;

			default:
				$currentOtherEnrollment--;
				break;
		}
		$currentCapacity--;

		//Need to increase the Racial numbers and recalculate everything.
		$blackPercent = number_format( ( $currentBlackEnrollment / $currentCapacity ) * 100 , 1 );
		$otherPercent = number_format( ( $currentOtherEnrollment / $currentCapacity ) * 100 , 1 );
		$whitePercent = number_format( ( $currentWhiteEnrollment / $currentCapacity ) * 100 , 1 );


		if( $majorityRace == 'black' ) {
			//Majority Race is black, so we need to make sure the increase did not bring the race below 50.1
			if( $blackPercent >= 50.1 && $whitePercent < 50.1 ) {
				$this->slottingLotteryArray[$groupID]['percentages'] = sprintf( 'Black: %s - Other: %s - White: %s' , $blackPercent , $otherPercent , $whitePercent );
				return true;
			}

		} elseif( $majorityRace == 'white' ) {
			//Majority Race is white, so we need to make sure the increase did not bring the race below 50.1
			if( $whitePercent >= 50.1 && $blackPercent < 50.1 ) {
				$this->slottingLotteryArray[$groupID]['percentages'] = sprintf( 'Black: %s - Other: %s - White: %s' , $blackPercent , $otherPercent , $whitePercent );
				return true;
			}

		} else {
			//There is not a majority RACE. So none of the percents should be GREATER than 50.1
			if( $blackPercent < 50.1 && $whitePercent < 50.1 ) {
				$this->slottingLotteryArray[$groupID]['percentages'] = sprintf( 'Black: %s - Other: %s - White: %s' , $blackPercent , $otherPercent , $whitePercent );
				return true;
			}
		}

		return false;
	}


	/**
	 * @param object $em
	 * @param int $auditCode
	 * @param int $studentID
	 * @param int $submission
	 */
	private function recordAudit( $em , $auditCode = 0 , $submission = 0 , $studentID = 0 ) {
		//$user = $this->get( 'security.context' )->getToken()->getUser();

		$auditCode = $em->getRepository( 'IIABStudentTransferBundle:AuditCode' )->find( $auditCode );

		$audit = new Audit();
		$audit->setAuditCodeID( $auditCode );
		$audit->setIpaddress( '::1' );
		$audit->setSubmissionID( $submission );
		$audit->setStudentID( $studentID );
		$audit->setTimestamp( new \DateTime() );
		$audit->setUserID( 0 );

		$em->persist( $audit );
		//$em->flush();
	}

	/**
	 * Writes out the Lottery Logging File before anything was awarded or denied.
	 *
	 * @param array  $lotteryData
	 * @param string $list
	 * @param string $fileName
	 * @param string $title
	 *
	 * @return array
	 */
	private function writeLoggingFile( $lotteryData = array() , $list = '' , $fileName = 'lottery' , $title = 'Lottery List' ) {

		$phpExcelObject = $this->container->get( 'phpexcel' )->createPHPExcelObject();
		$phpExcelObject->getProperties()->setCreator( "Image In A Box" )
			->setLastModifiedBy( "Image In A Box" )
			->setTitle( "TCS Lottery Debugging" )
			->setSubject( "Lottery Debugging" )
			->setDescription( "Document debugs the lottery" )
			->setKeywords( "mymagnetapp" )
			->setCategory( "lottery debugger" );

		$activeSheet = $phpExcelObject->getActiveSheet();

		if( $list == 'before' ) {

			$beforeData = $lotteryData;

			if( !empty( $beforeData ) ) {

				$lastGrade = '';

				$startingColumn = 0;
				$activeSheet->mergeCellsByColumnAndRow( 0 , 1 , 9 , 1 );
				$activeSheet->setCellValueByColumnAndRow( 0 , 1 , 'Before ' . $title );
				$activeSheet->setCellValueByColumnAndRow( 0 , 2 , 'Submission ID' );
				$activeSheet->setCellValueByColumnAndRow( 1 , 2 , 'Student ID' );
				$activeSheet->setCellValueByColumnAndRow( 2 , 2 , 'Race' );
				$activeSheet->setCellValueByColumnAndRow( 3 , 2 , 'First Choice' );
				$activeSheet->setCellValueByColumnAndRow( 4 , 2 , 'Second Choice' );
				$activeSheet->setCellValueByColumnAndRow( 5 , 2 , 'Current School');
				$activeSheet->setCellValueByColumnAndRow( 6 , 2 , 'Zoned School' );
				$activeSheet->setCellValueByColumnAndRow( 7 , 2 , 'Status' );
				$activeSheet->setCellValueByColumnAndRow( 8 , 2 , 'Expiring/Non-Priority' );
				$activeSheet->setCellValueByColumnAndRow( 9 , 2 , 'Lottery' );
				$startingColumn = 0;
				$row = 3;
				$originalStartingColumn = $startingColumn;

				foreach( $beforeData as $priorityList => $submissions ) {

					$activeSheet->setTitle( 'Before ' . $title );

					//Find Sheet by Name.

					/** @var Submission $submission */
					foreach( $submissions as $submission ) {

						$startingColumn = $originalStartingColumn;

						$activeSheet->setCellValueByColumnAndRow( $startingColumn , $row , $submission->getConfirmationNumber() );
						$startingColumn++;

						$activeSheet->setCellValueByColumnAndRow( $startingColumn , $row , $submission->getStudentID() );
						$startingColumn++;

						$activeSheet->setCellValueByColumnAndRow( $startingColumn , $row , $submission->getRace() );
						$startingColumn++;

						if( $submission->getFirstChoice() != null ) {
							$activeSheet->setCellValueByColumnAndRow( $startingColumn , $row , $submission->getFirstChoice()->__toString() );
						}
						$startingColumn++;

						if( $submission->getSecondChoice() != null ) {
							$activeSheet->setCellValueByColumnAndRow( $startingColumn , $row , $submission->getSecondChoice()->__toString() );
						}
						$startingColumn++;

						$activeSheet->setCellValueByColumnAndRow( $startingColumn, $row, $submission->getCurrentSchool() );
						$startingColumn++;

						$activeSheet->setCellValueByColumnAndRow( $startingColumn , $row , $submission->getHsvZonedSchoolsString() );
						$startingColumn++;

						$activeSheet->setCellValueByColumnAndRow( $startingColumn , $row , $submission->getSubmissionStatus()->__toString() );
						$startingColumn++;

						$activeSheet->setCellValueByColumnAndRow( $startingColumn , $row , $priorityList );
						$startingColumn++;

						$activeSheet->setCellValueByColumnAndRow( $startingColumn , $row , $submission->getLotteryNumber() );
						$startingColumn++;

						$row++;
					}
				}
			}
			$lotteryData[$list] = null;
		}

		if( $list == 'after' ) {

			$afterData = $lotteryData;

			if( $afterData != null ) {

				/*
				 * 'offered'				    => array(),
				 * 'non-awarded'			    => array(),
				 * 'denied due to space'	    => array(),
				 * 'wait listed'			    => array(),
				 * 'offered and wait listed'    => array(),
				 */
				$lastList = '';
				$originalStartingColumn = 0;
				foreach( $afterData as $listTitle => $submissions ) {

					if( $lastList != $listTitle ) {
						if( !empty( $lastList ) ) {
							$activeSheet = $phpExcelObject->createSheet();
						}
						$lastList = $listTitle;
						$activeSheet->setTitle( ucwords( $listTitle ) );
					}

					$activeSheet->mergeCellsByColumnAndRow( 0 , 1 , 10 , 1 );
					$activeSheet->setCellValueByColumnAndRow( 0 , 1 , 'After ' . ucwords( $listTitle ) );
					$activeSheet->setCellValueByColumnAndRow( 0 , 2 , 'Submission ID' );
					$activeSheet->setCellValueByColumnAndRow( 1 , 2 , 'Student ID' );
					$activeSheet->setCellValueByColumnAndRow( 2 , 2 , 'Race' );
					$activeSheet->setCellValueByColumnAndRow( 3 , 2 , 'First Choice' );
					$activeSheet->setCellValueByColumnAndRow( 4 , 2 , 'Second Choice' );
					$activeSheet->setCellValueByColumnAndRow( 5 , 2 , 'Current School');
					$activeSheet->setCellValueByColumnAndRow( 6 , 2 , 'Zoned School' );
					$activeSheet->setCellValueByColumnAndRow( 7 , 2 , 'Lottery' );
					$activeSheet->setCellValueByColumnAndRow( 8 , 2 , 'Status' );
					$activeSheet->setCellValueByColumnAndRow( 9 , 2 , 'Awarded School' );
					$activeSheet->setCellValueByColumnAndRow( 10 , 2 , 'Wait Listed School' );

					$row = 3;
					foreach( $submissions as $index => $submissionData ) {

						$submission = $submissionData['submission'];

						$startingColumn = $originalStartingColumn;

						$activeSheet->setCellValueByColumnAndRow( $startingColumn , $row , $submission->getConfirmationNumber() );
						$startingColumn++;

						$activeSheet->setCellValueByColumnAndRow( $startingColumn , $row , $submission->getStudentID() );
						$startingColumn++;

						$activeSheet->setCellValueByColumnAndRow( $startingColumn , $row , $submission->getRace() );
						$startingColumn++;

						if( $submission->getFirstChoice() != null ) {
							$activeSheet->setCellValueByColumnAndRow( $startingColumn , $row , $submission->getFirstChoice()->__toString() );
						}
						$startingColumn++;

						if( $submission->getSecondChoice() != null ) {
							$activeSheet->setCellValueByColumnAndRow( $startingColumn , $row , $submission->getSecondChoice()->__toString() );
						}
						$startingColumn++;

						$activeSheet->setCellValueByColumnAndRow( $startingColumn, $row, $submission->getCurrentSchool() );
						$startingColumn++;

						$activeSheet->setCellValueByColumnAndRow( $startingColumn , $row , $submission->getHsvZonedSchoolsString() );
						$startingColumn++;

						$activeSheet->setCellValueByColumnAndRow( $startingColumn , $row , $submission->getLotteryNumber() );
						$startingColumn++;

						$activeSheet->setCellValueByColumnAndRow( $startingColumn , $row , ucwords( $listTitle ) );
						$startingColumn++;

						if( $submission->getAwardedSchoolID() != null && $listTitle != 'wait listed' ) {
							$activeSheet->setCellValueByColumnAndRow( $startingColumn , $row , $submission->getAwardedSchoolID()->__toString() );
						}
						$startingColumn++;

						$waitList = $submission->getWaitList();
						if( !empty( $waitList ) ) {

							/** @var \IIAB\StudentTransferBundle\Entity\WaitList $entry */
							foreach( $waitList as $entry ) {
								$activeSheet->setCellValueByColumnAndRow( $startingColumn , $row , $entry->getChoiceSchool()->__toString() );
								$startingColumn++;
							}
						}

						$row++;

					}
				}

			}
			//$this->logging( $afterData );
			$lotteryData[$list] = null;
		}

		//Write out the file to save it to the system.
		$writer = $this->container->get( 'phpexcel' )->createWriter( $phpExcelObject , 'Excel2007' );

		$dir = $this->container->get( 'kernel' )->getRootDir() . '/../web/debugging/';
		if( !file_exists( $dir ) ) {
			mkdir( $dir );
		}
		$writer->save( $dir . $fileName . '-debug-' . $list . '-' . date( 'Y-m-d-H-i' ) . '.xlsx' );

		//Null out to release Memory.
		$writer = null;

		return $lotteryData;
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
}