<?php


namespace App\Controller\Rest;


use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class UserController extends AbstractController
{
    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;
    /**
     * @var JWTEncoderInterface
     */
    private $JWTEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder, JWTEncoderInterface $JWTEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->JWTEncoder = $JWTEncoder;
    }

    /**
     * @Route("/user/token", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     * @throws JWTEncodeFailureException
     */
    public function tokenAction(Request $request)
    {
        $userRepository = $this->getDoctrine()->getRepository(User::class);
        $user = $userRepository->findOneBy(["username" => $request->getUser()]);

        if (!$user) {
            throw new BadCredentialsException();
        }

        $isPasswordValid = $this->passwordEncoder->isPasswordValid($user, $request->getPassword());
        if (!$isPasswordValid) {
            throw new BadCredentialsException();
        }


        $token = $this->JWTEncoder->encode([
            'username' => $user->getUsername(),
            'exp' => time() + 3600,
        ]);

        return new JsonResponse(['token' => $token]);
    }
}