<?php

namespace App\Filament\Concerns;

use Illuminate\Validation\ValidationException;

trait HandlesServiceValidation
{
    protected function withFormValidation(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (ValidationException $exception) {
            $errors = [];
            foreach ($exception->errors() as $field => $messages) {
                $errors[$this->form->getStatePath().'.'.$field] = $messages;
            }
            throw ValidationException::withMessages($errors)->status($exception->status);
        }
    }
}
