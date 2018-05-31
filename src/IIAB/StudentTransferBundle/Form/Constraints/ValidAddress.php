<?php

namespace IIAB\StudentTransferBundle\Form\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidAddress extends Constraint
{
    public $message = 'The address you entered could not be found within the bounds of the Tuscaloosa City Schools. <br> Please confirm your address and try again. %string%';

    public function validatedBy() {
        return 'validate_address';
    }
}