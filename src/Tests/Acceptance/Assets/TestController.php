<?php

declare(strict_types=1);


namespace Profesia\Symfony\Psr15Bundle\Tests\Acceptance\Assets;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TestController extends AbstractController
{
    public function indexAction(Request $request): Response
    {
        $headers = implode(' ', $request->headers->all('x-developer'));

        return new JsonResponse(
            [
                'headers' => $headers,
            ]
        );
    }
}
