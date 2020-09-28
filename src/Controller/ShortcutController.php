<?php

namespace App\Controller;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\NotNull;

abstract class ShortcutController extends AbstractController {
    public function get_entity_manager(): EntityManager {
        return $this->getDoctrine()->getManager();
    }

    /**
     * Retrieve a model by pk or throw a HTTP 404
     * 
     * @param repository The repository of the model to retrieve
     * @param pk The primary key for the model, may not be composite
     */
    public function get_or_404(
        $repository,
        $pk
    ) {
        $item = $repository->find($pk);

        if (!$item) {
            throw $this->createNotFoundException(
                "No item found for pk {$pk}"
            );
        }

        return $item;
    }

    /**
     * Run validations on a schema and throw a HTTP 422 if it fails
     * 
     * @param mixed schema 
     * A blank instance of the schema class
     * @param mixed body 
     * The json decoded request body
     * @param array args 
     * An optional array, containing either "partial" or "fields" params
     */
    public function valid_or_422(
        $schema,
        $body,
        $args = []
    ): void {
        // Check that body is not empty
        if (!sizeof($body) >= 1) {
            throw new HttpException(
                422,
                "Empty body"
            );
        }

        // Check that all keys in body are also in schema
        $schema_keys = get_object_vars($schema);
        foreach ($body as $key => $_) {
            try {
                $schema_keys[$key];
            } catch (\Throwable $th) {
                throw new HttpException(
                    422,
                    "Key {$key} not valid"
                );
            }
        }

        // Validate using the service
        $validator = Validation::createValidator();
        $schema = $this->_body_into_schema(
            $schema,
            $body
        );
        $exc = $validator->validate($schema);

        // If all validation instantly passes, end the function
        if (sizeof($exc) == 0) {
            return;
        }

        // Next, check the exceptions based on user supplied args

        // Not both of these conditions can apply at the same time
        // partial means we ignore NotNull errors as long as one field exists
        if ($args["partial"]) {
            $relevant_exc = array_diff(
                $exc,
                $exc->findByCodes(NotNull::IS_NULL_ERROR)
            );
            if (sizeof($relevant_exc) >= 1) {
                throw new HttpException(
                    422,
                    "Validation did not pass"
                );
            }
            return;
        }
        // fields means we only check for validation on specified keys
        else if ($args["fields"]) {
            $fields = $args["fields"];
            foreach ($exc as $err) {
                $key = $this->_get_validated_key($err);
                if (in_array($key, $fields)) {
                    throw new HttpException(
                        422,
                        "Validation did not pass for key {$key}"
                    );
                }
            }
            return;
        }

        // If neither argument is specified, use default behaviour
        if (sizeof($exc) > 0) {
            throw new HttpException(
                422,
                (string) $exc
            );
        }
        return;
    }

    private function _body_into_schema(
        $schema,
        $body
    ) {
        foreach ($body as $key => $value) {
            $k = ucfirst($key);
            call_user_func(
                [$schema, "set{$k}"],
                $value
            );
        }
        return $schema;
    }

    private function _get_validated_key($err): string {
        $fragments = explode(".", $err->getPropertyPath());
        $err_key = array_values(array_slice($fragments, -1))[0];
        return $err_key;
    }
}
