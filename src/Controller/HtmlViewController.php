<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/** @Route("/frontend", name="frontend_") */
class HtmlViewController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index(ProductRepository $repository) {
        return $this->render(
            "index.html.twig",
            [
                'title' => 'Welcome to your new controller!',
                "products" => $repository->findAll()
            ]
        );
    }
}
