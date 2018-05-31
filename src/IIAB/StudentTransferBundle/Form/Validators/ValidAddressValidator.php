<?php

namespace IIAB\StudentTransferBundle\Form\Validators;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Doctrine\ORM\EntityManager;
use IIAB\StudentTransferBundle\Service\CheckAddressService;

class ValidAddressValidator extends ConstraintValidator {

	/** @var EntityManager */
	private $emLookup;

	/**
	 * Setting up all the defaults needed for the Class.
	 *
	 * @param EntityManager $emLookup
	 */
	function __construct( EntityManager $emLookup ) {

		$this->emLookup = $emLookup;
	}

	public function validate( $value , Constraint $constraint ) {

		// Get submitted form data
		$data = $this->context->getRoot()->getData();

		$validateAddressService = new CheckAddressService( $this->emLookup );
		$suggestions = $validateAddressService->getSuggestions( $data , 5 );

		//Upper Casing and trim to help the getAddressFu column match.
		$value = trim( preg_replace('/\s+/', ' ', strtoupper( $value ) ) );



		if( $suggestions ) {

			$no_match_found = true;
			foreach( $suggestions as $suggestion ) {
				if ( strtoupper( trim( preg_replace('/\s+/', ' ', $suggestion->getAddressFu() ) ) ) == $value ) {
					$no_match_found = false;
				}
			}

			if( $no_match_found ){
				$addressList = '  Or, click on the correct address provided below to use that value.  Please be aware that your address might not be listed as an option if the <b>zip code</b> you entered is not correct.<ul style="float: none;">';
				foreach( $suggestions as $index => $suggestion ) {
					$addressList .= '<li><a href="#" class="set-address">' . $suggestion->getAddressFu() . '</a></li>';
				}
				$addressList .= '</ul>';

				$this->context->buildViolation( $constraint->message )
					->setParameter( '%string%' , $addressList )
					->addViolation();
				//$this->context->addViolationAt( 'property', $constraint->message . $addressList );
			}
		} else {
			$this->context->buildViolation( $constraint->message )
				->setParameter( '%string%' , '' )
				->addViolation();
			//$this->context->addViolationAt( 'property', $constraint->message );
		}

	}
}