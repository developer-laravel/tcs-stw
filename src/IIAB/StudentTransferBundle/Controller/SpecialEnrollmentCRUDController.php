<?php

namespace IIAB\StudentTransferBundle\Controller;


use IIAB\StudentTransferBundle\Entity\Submission;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sonata\AdminBundle\Controller\CRUDController;
use IIAB\StudentTransferBundle\Entity\Audit;
use IIAB\StudentTransferBundle\Command\CheckMinorityCommand;
use IIAB\StudentTransferBundle\Command\GetAvailableSchoolCommand;

class SpecialEnrollmentCRUDController extends CRUDController {


}