<?php

namespace Backend\Core;

/**
 * ValidatorService.php
 * ─────────────────────────────────────────────────────
 * Validación de tipos y esquemas de entrada.
 * ─────────────────────────────────────────────────────
 */
class ValidatorService
{
    /**
     * Validar esquema básico de un array asociativo
     */
    public static function validate(array $data, array $rules): array
    {
        $errors = [];
        foreach ($rules as $field => $rule) {
            if (!isset($data[$field]) && strpos($rule, 'required') !== false) {
                $errors[] = "El campo '$field' es obligatorio.";
                continue;
            }

            if (isset($data[$field])) {
                if (strpos($rule, 'numeric') !== false && !is_numeric($data[$field])) {
                    $errors[] = "El campo '$field' debe ser numérico.";
                }
                if (strpos($rule, 'email') !== false && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "El campo '$field' debe ser un email válido.";
                }
            }
        }

        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(' ', $errors));
        }

        return $data;
    }
}
