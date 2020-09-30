<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Controller\NormalizingOrmController;
use App\Controller\ShortcutController;
use Doctrine\ORM\EntityManager;
use App\Schema\ProductSchema;

/**
 * @Route("/products", name="products_")
 */
class ProductController extends ShortcutController {
    public function format_price(float $price): string {
        // float to correct decimal format (expects 4)
        return number_format($price, 4, '.', ' ');
    }

    /**
     * @Route(
     *      "/",
     *      methods={"GET"},
     *      name="list"
     * )
     */
    public function list(ProductRepository $repository): Response {
        $items = $repository -> findAll();

        return $this->json($items);
    }

    /**
     * @Route(
     *      "/{pk}",
     *      methods={"GET"},
     *      name="retrieve"
     * )
     */
    public function retrieve(
        int $pk,
        ProductRepository $repository
    ): Response {
        $item = $this->get_or_404($repository, $pk);

        return $this->json($item);
    }

    /**
     * @Route(
     *      "/",
     *      methods={"POST"},
     *      name="create"
     * )
     */
    public function create(Request $request): Response {
        $body = json_decode($request->getContent(), true);
        $this->valid_or_422(
            new ProductSchema(),
            $body
        );

        $price = $this->format_price($body["price"]);

        $item = new Product();
        $item->setName($body["name"]);
        $item->setPrice($price);

        // Save Item
        $this->get_entity_manager()->persist($item);
        $this->get_entity_manager()->flush();

        return $this->json($item, 201);
    }
    /**
     * @Route(
     *      "/{pk}",
     *      methods={"PATCH"},
     *      name="update_partial"
     * )
     */
    public function update_partial(
        int $pk,
        Request $request,
        ProductRepository $repository
    ): Response {
        $body = json_decode($request->getContent(), true);
        $this->valid_or_422(
            new ProductSchema(),
            $body,
            ["partial" => true]
        );

        $item = $this->get_or_404($repository, $pk);
        foreach ($body as $key => $value) {
            if ($key == "price") {
                $value = $this->format_price($value);
            }
            $key_upper = ucfirst($key);
            $func_name = "set{$key_upper}";
            // Totally not safe for production but still neat :)
            call_user_func(
                [$item, $func_name],
                $value
            );
        }
        $this->get_entity_manager()->flush();
        return $this->json($item);
    }

    /**
     * @Route(
     *      "/{pk}",
     *      methods={"PUT"},
     *      name="update"
     * )
     */
    public function update(
        int $pk,
        Request $request,
        ProductRepository $repository
    ): Response {
        $body = json_decode($request->getContent(), true);
        $this->valid_or_422(
            new ProductSchema(),
            $body
        );

        $price = $this->format_price($body["price"]);

        $item = $this->get_or_404($repository, $pk);
        $item->setName($body["name"]);
        $item->setPrice($price);
        $this->get_entity_manager()->flush();

        return $this->json($item);
    }

    /**
     * @Route(
     *      "/{pk}",
     *      methods={"DELETE"},
     *      name="delete"
     * )
     */
    public function delete(
        int $pk,
        ProductRepository $repository
    ): Response {
        $item = $this->get_or_404($repository, $pk);
        $this->get_entity_manager()->remove($item);
        $this->get_entity_manager()->flush();

        return new Response();
    }
}
