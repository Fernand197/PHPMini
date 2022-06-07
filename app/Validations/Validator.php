<?php

namespace App\Validations;

class Validator
{

    private $data;

    private $errors;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function validate(array $rules)
    {
        foreach ($rules as $name => $arrayRules) {
            if (array_key_exists($name, $this->data)) {
                foreach ($arrayRules as $rule) {
                    switch ($rule) {
                        case "Required":
                            $this->required($name, $this->data[$name]);
                            break;
                        case explode(':', $rule)[0] === 'Min':
                            $this->min($name, $this->data[$name], $rule);
                            break;
                        case explode(':', $rule)[0] === 'Max':
                            $this->max($name, $this->data[$name], $rule);
                            break;
                        case explode(':', $rule)[0] === 'MaxSize':
                            $this->maxSize($name, round((int)($this->data[$name]) / (1024 * 1024), 2), $rule);
                            break;
                        case "ExtVideo":
                            $this->ext($name, strtolower(pathinfo($this->data[$name], PATHINFO_EXTENSION)), ["mp4", "webm", "avi", "flv"]);
                            break;
                        case "ExtAudio":
                            $this->ext($name, strtolower(pathinfo($this->data[$name], PATHINFO_EXTENSION)), ["mp3", "wav"]);
                            break;
                        case "ExtImage":
                            $this->ext($name, strtolower(pathinfo($this->data[$name], PATHINFO_EXTENSION)), ["jpg", "png"]);
                            break;
                        case "EmailValid":
                            $this->emailValidate($name, $this->data[$name]);
                            break;
                        case "PasswordValid":
                            $this->passwordValidate($name, $this->data[$name]);
                            break;
                        case "UsernameValid":
                            $this->usernameValidate($name, $this->data[$name]);
                            break;
                        default:
                            break;
                    }
                }
            }
        }

        return $this->errors;
    }

    public function emailValidate(string $name, string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$name][] = 'Invalid email format';
        }
    }

    public function passwordValidate(string $name, string $value)
    {
        if (
            strlen($value) < 8
            || !preg_match('@[0-9]@', $value)
            || !preg_match('@[a-z]@', $value)
            || !preg_match('@[A-Z]@', $value)
            || !preg_match('@[^\W]@', $value)
        ) {
            $this->errors[$name][] = 'Password must be at least 8 characters in length and must contains numbers, uppercase, lowercase and specials characters';
        }
    }

    public function usernameValidate(string $name, string $value)
    {
        if (!preg_match("/^[a-zA-Z-]*$/", $value)) {
            $this->errors[$name][] = 'Only letters and white space allowed';
        }
    }

    public function required(string $name, string $value)
    {
        $value = trim($value);
        if (!isset($value) || is_null($value) || empty($value)) {
            $this->errors[$name][] = "{$name} is required.";
        }
    }


    public function min(string $name, string $value, string $rule)
    {
        preg_match_all("/(\d+)/", $rule, $matches);
        $limit = (int) $matches[0][0];

        if (strlen($value) < $limit) {
            $this->errors[$name][] = "{$name} required minimum of {$limit} characters";
        }
    }

    public function max(string $name, string $value, string $rule)
    {
        preg_match_all("/(\d+)/", $rule, $matches);
        $limit = (int) $matches[0][0];

        if (strlen($value) < $limit) {
            $this->errors[$name][] = "{$name} required minimum of {$limit} characters";
        }
    }

    public function maxSize(string $name, int $value, string $rule)
    {
        preg_match_all("/(\d+)/", $rule, $matches);
        $limit = (int) $matches[0][0];

        if ($value > $limit) {
            $this->errors[$name][] = "The maximum {$name} required is {$limit} Mo";
        }
    }

    public function ext(string $name, string $value, array $ext)
    {
        if (!in_array($value, $ext)) {
            $this->errors[$name][] = "{$value} extension aren't allowed";
        }
    }
}
