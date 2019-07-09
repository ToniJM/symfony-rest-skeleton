<?php


namespace App\Controller;


use App\Exception\ValidationException;
use FOS\RestBundle\Controller\ControllerTrait;
use FOS\RestBundle\View\View;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

class ExceptionController extends AbstractController
{
    use ControllerTrait;

    /**
     * @param Request $request
     * @param $exception
     * @param DebugLoggerInterface|null $logger
     * @return View
     */
    public function showAction(Request $request, $exception, DebugLoggerInterface $logger = null)
    {
        if ($exception instanceof ValidationException) {
            return $this->getView($exception->getStatusCode(), json_decode($exception->getMessage(), true));
        }

        if ($exception instanceof HttpException) {
            return $this->getView($exception->getStatusCode(), $exception->getMessage());
        }

        dump($exception);
        exit();

        return $this->getView(500, "Unexpecteeed error occurred");
    }

    /**
     * @param int|null $code
     * @param $message
     * @return View
     */
    private function getView(?int $code, $message): View
    {
        $data = [
            'code' => $code ?? 500,
            'message' => $message
        ];

        return $this->view($data, $code ?? 500);
    }
}