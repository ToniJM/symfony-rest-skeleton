<?php


namespace App\Controller\Rest;


use App\Entity\User;
use App\Exception\ValidationException;
use App\Service\EntityMerger;
use FOS\RestBundle\Controller\Annotations as Rest;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use ReflectionException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class UsersController extends AbstractController
{
    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;
    /**
     * @var JWTEncoderInterface
     */
    private $JWTEncoder;
    /**
     * @var EntityMerger
     */
    private $merger;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder, JWTEncoderInterface $JWTEncoder, EntityMerger $merger)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->JWTEncoder = $JWTEncoder;
        $this->merger = $merger;
    }

    /**
     * @Security("is_granted('show', theUser)", message="Access denied")
     * @Rest\View(serializerGroups={"Default"})
     * @param User|null $theUser
     * @return User|null
     */
    public function getUserAction(?User $theUser)
    {
        if (null === $theUser) {
            throw new NotFoundHttpException();
        }

        return $theUser;
    }

    /**
     * @ParamConverter("user", converter="fos_rest.request_body", options={"deserializationContext"={"groups"={"Deserialize"}}})
     * @Rest\View(serializerGroups={"Default"})
     * @param User $user
     * @param ConstraintViolationListInterface $violationList
     * @return User
     */
    public function postUsersAction(User $user, ConstraintViolationListInterface $violationList)
    {
        if (count($violationList) > 0) {
            throw new ValidationException($violationList);
        }

        $this->encodePassword($user);

        $user->setRoles([User::ROLE_USER]);

        $this->persistUser($user);

        return $user;
    }

    /**
     * @ParamConverter("patch", converter="fos_rest.request_body",
     *     options={
     *          "deserializationContext"={"groups"={"Deserialize"}},
     *          "validator"={"groups"={"Patch"}}
     *     }
     * )
     * @Security("is_granted('edit', theUser)", message="Access denied")
     * @Rest\View(serializerGroups={"Default"})
     * @param User|null $theUser
     * @param User $patch
     * @param ConstraintViolationListInterface $violationList
     * @return User|null
     * @throws ReflectionException
     */
    public function patchUsersAction(?User $theUser, User $patch, ConstraintViolationListInterface $violationList)
    {
        if (null === $theUser) {
            throw new NotFoundHttpException();
        }

        if (count($violationList) > 0) {
            throw new ValidationException($violationList);
        }

        $this->merger->merge($theUser, $patch);
        $this->encodePassword($theUser);
        $this->persistUser($theUser);

        return $theUser;
    }

    /**
     * @param User $user
     */
    protected function encodePassword(User $user): void
    {
        $user->setPassword(
            $this->passwordEncoder->encodePassword($user, $user->getPassword())
        );
    }

    /**
     * @param User $user
     */
    protected function persistUser(User $user): void
    {
        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();
    }
}