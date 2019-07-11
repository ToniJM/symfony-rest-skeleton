<?php


namespace App\Controller\Rest;


use App\Entity\Date;
use App\Entity\Persona;
use App\Exception\ValidationException;
use App\Repository\PersonaRepository;
use App\Service\EntityMerger;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use ReflectionException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * @Rest\View(serializerEnableMaxDepthChecks=true)
 */
class PersonasController extends AbstractFOSRestController
{
    /**
     * @var PersonaRepository
     */
    private $personaRepository;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var EntityMerger
     */
    private $merger;

    public function __construct(PersonaRepository $personaRepository, EntityManagerInterface $entityManager, EntityMerger $merger)
    {
        $this->personaRepository = $personaRepository;
        $this->entityManager = $entityManager;
        $this->merger = $merger;
    }

    /**
     * @return View
     */
    public function getPersonasAction()
    {
        $data = $this->personaRepository->findAll();
        return $this->view($data, Response::HTTP_OK);
    }

    public function getPersonaAction(Persona $persona = null)
    {
        if ($persona === null) {
            return $this->view(null, Response::HTTP_NOT_FOUND);
        }
        return $this->view($persona, Response::HTTP_OK);
    }

    /**
     * @ParamConverter("persona", converter="fos_rest.request_body")
     * @param Persona $persona
     * @param ConstraintViolationListInterface $violationList
     * @return View
     */
    public function postPersonasAction(Persona $persona, ConstraintViolationListInterface $violationList)
    {
        if (count($violationList) > 0) {
            throw new ValidationException($violationList);
        }

        $this->entityManager->persist($persona);
        $this->entityManager->flush();

        return $this->view($persona, Response::HTTP_CREATED);
    }


    public function deletePersonasAction(Persona $persona)
    {
        if ($persona === null) {
            return $this->view(null, Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($persona);
        $this->entityManager->flush();

        return $this->view(null, Response::HTTP_NO_CONTENT);
    }

    public function getPersonasDatesAction(Persona $persona)
    {
        return $this->view($persona->getDates(), Response::HTTP_OK);
    }

    /**
     * @ParamConverter("date", converter="fos_rest.request_body", options={"deserializationContext"={"groups"={"Deserialize"}}})
     * @param Persona $persona
     * @param Date $date
     * @param ConstraintViolationListInterface $violationList
     * @return View
     */
    public function postPersonasDatesAction(Persona $persona, Date $date, ConstraintViolationListInterface $violationList)
    {
        if ($persona === null) {
            return $this->view(null, Response::HTTP_NOT_FOUND);
        }

        if (count($violationList) > 0) {
            throw new ValidationException($violationList);
        }

        // si esta seteada la misma persona que la url la quito
        // TODO testear el error (no debería dar el error desde que usamos un customHandler para deserializar
//        try {
//            foreach ($date->getPersonas() as $subPersona) {
//                if ($subPersona->getId() === $persona->getId()) {
//                    $date->removePersona($subPersona);
//                }
//            }
//        } catch (TypeError $error) { // este error lo tira cuando el array de Dates es null (viene del serializer)
//            // nueva entidad para evitar problemas con los arrays que vienen como null
//            $newDate = new Date();
//
//            // merge y si era null queda el array vacío
//            $this->merger->merge($newDate, $date);
//
//            $date = $newDate;
//        }

        $persona->addDate($date);
        $this->entityManager->persist($date);
        $this->entityManager->flush();

        return $this->view($date, Response::HTTP_CREATED);
    }

    /**
     * @ParamConverter("patch", converter="fos_rest.request_body",
     *     options={
     *          "deserializationContext"={"groups"={"Deserialize"}},
     *          "validator" = {"groups"={"Patch"}}
     *     }
     * )
     * @Security("is_authenticated() or is_anonymous()")
     * @param Persona|null $persona
     * @param Persona $patch
     * @param ConstraintViolationListInterface $violationList
     * @param Request $request
     * @return View
     * @throws ReflectionException
     */
    public function patchPersonasAction(Persona $persona = null, Persona $patch, ConstraintViolationListInterface $violationList, Request $request)
    {
        if ($persona === null) {
            return $this->view(null, Response::HTTP_NOT_FOUND);
        }

        if (count($violationList) > 0) {
            throw new ValidationException($violationList);
        }

        // paso los Dates
        $this->merger->merge($persona, $patch, $request->request->all(), true);

//        foreach ($persona->getDates() as $date) {
//            $persona->removeDate($date);
//            /** @var Date $date */
//            $date = $this->getDoctrine()->getRepository(get_class($date))->find($date->getId());
//            $persona->addDate($date);
//        }

        $this->entityManager->persist($persona);
        $this->entityManager->flush();

        return $this->view($persona, Response::HTTP_OK);
    }
}