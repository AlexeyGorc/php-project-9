<?php

namespace Hexlet\Code;

class Validator
{
    public function validate(string $url): array
    {
        $errors = [];

        if (mb_strlen($url) > 255) {
            $errors['urlLength'] = "URL should be less than 255 characters";
        }

        if ($url === '') {
            $errors['urlEmpty'] = 'URL is empty';
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            $errors['urlInvalid'] = 'Invalid URL';
        }

        return $errors;
    }
}