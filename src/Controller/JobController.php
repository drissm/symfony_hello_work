<?php

namespace App\Controller;

use App\JobiJoba\Client\ApiConsumer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class JobController extends AbstractController
{
    public function __construct(
        private readonly ApiConsumer $apiConsumer
    ) {
    }

    #[Route(
        path: '/jobs/{page}',
        name: 'app_jobs_list',
        methods: ['GET']
    )]
    public function list(int $page = 1): Response
    {
        return $this->render('job/list.html.twig', [
            'jobs' => $this->apiConsumer->search($page)
        ]);
    }
}
