<?php

namespace IIAB\StudentTransferBundle\Form\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidAge extends Constraint
{
    public $message = '%first_name% %last_name% is too young to enter the selected grade. The %grade% cut-off date is %date%.';

    public function validatedBy() {
        return 'validate_age';
    }
}