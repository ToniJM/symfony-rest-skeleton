<?php


namespace App\Controller\Rest;


use App\Entity\Date;
use App\Service\EntityMerger;
use App\Exception\ValidationException;
use App\Repository\DateRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\View\View;
use ReflectionException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * @Security("is_anonymous() or is_authenticated()")
 */
class DatesController extends AbstractFOSRestController
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var DateRepository
     */
    private $dateRepository;
    /**
     * @var EntityMerger
     */
    private $merger;

    /**
     * DatesController constructor.
     * @param DateRepository $dateRepository
     * @param EntityManagerInterface $entityManager
     * @param EntityMerger $merger
     */
    public function __construct(DateRepository $dateRepository, EntityManagerInterface $entityManager, EntityMerger $merger)
    {
        $this->entityManager = $entityManager;
        $this->dateRepository = $dateRepository;
        $this->merger = $merger;
    }

    public function getDateAction(Date $date = null)
    {
        if ($date === null) {
            return $this->view(null, Response::HTTP_NOT_FOUND);
        }
        return $this->view($date, Response::HTTP_OK);
    }

    /**
     * @ParamConverter("date", converter="fos_rest.request_body")
     * @param Date $date
     * @param ConstraintViolationListInterface $validationErrors
     * @return View
     */
    public function postDatesAction(Date $date, ConstraintViolationListInterface $validationErrors)
    {
        if (count($validationErrors) > 0) {
            throw new ValidationException($validationErrors);
        }

        $this->entityManager->persist($date);
        $this->entityManager->flush();

        return $this->view($date, Response::HTTP_CREATED);
    }

    public function deleteDatesAction(Date $date)
    {
        if ($date === null) {
            return $this->view(null, Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($date);
        $this->entityManager->flush();

        return $this->view(null, Response::HTTP_NO_CONTENT);
    }

    public function getDatesPersonasAction(Date $date)
    {
        return $this->view($date->getPersonas(), Response::HTTP_OK);
    }

    /**
     * @ParamConverter("patch", converter="fos_rest.request_body",
     *     options={
     *          "deserializationContext"={"groups"={"Deserialize"}},
     *          "validator" = {"groups"={"Patch"}}
     *     }
     * )
     * @Security("is_authenticated()")
     * @param Date|null $date
     * @param Date $patch
     * @param ConstraintViolationListInterface $violationList
     * @return View
     * @throws ReflectionException
     */
    public function patchDatesAction(Date $date = null, Date $patch, ConstraintViolationListInterface $violationList)
    {
        if ($date === null) {
            return $this->view(null, Response::HTTP_NOT_FOUND);
        }

        if (count($violationList) > 0) {
            throw new ValidationException($violationList);
        }

        $this->merger->merge($date, $patch);

        $this->entityManager->persist($date);
        $this->entityManager->flush();

        return $this->view($date, Response::HTTP_OK);
    }
}