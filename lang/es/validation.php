<?php

/*
|--------------------------------------------------------------------------
| Líneas de idioma para validación (español)
|--------------------------------------------------------------------------
| Subconjunto de mensajes en español para las validaciones que usa el sitio.
*/

return [
    'accepted'   => 'El campo :attribute debe ser aceptado.',
    'between'    => [
        'array'   => 'El campo :attribute debe tener entre :min y :max elementos.',
        'numeric' => 'El campo :attribute debe estar entre :min y :max.',
        'string'  => 'El campo :attribute debe tener entre :min y :max caracteres.',
    ],
    'email'      => 'El campo :attribute debe ser una dirección de correo válida.',
    'in'         => 'El campo :attribute seleccionado no es válido.',
    'integer'    => 'El campo :attribute debe ser un número entero.',
    'max'        => [
        'array'   => 'El campo :attribute no debe tener más de :max elementos.',
        'numeric' => 'El campo :attribute no debe ser mayor que :max.',
        'string'  => 'El campo :attribute no debe ser mayor que :max caracteres.',
    ],
    'min'        => [
        'array'   => 'El campo :attribute debe tener al menos :min elementos.',
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'string'  => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'required'   => 'El campo :attribute es obligatorio.',
    'size'       => [
        'array'   => 'El campo :attribute debe contener :size elementos.',
        'numeric' => 'El campo :attribute debe ser :size.',
        'string'  => 'El campo :attribute debe tener :size caracteres.',
    ],
    'string'     => 'El campo :attribute debe ser una cadena de texto.',
    'array'      => 'El campo :attribute debe ser un conjunto.',

    'attributes' => [
        'name'    => 'nombre',
        'email'   => 'email',
        'phone'   => 'teléfono',
        'subject' => 'asunto',
        'message' => 'mensaje',
        'answers' => 'respuestas',
        'consent' => 'consentimiento',
        'website' => 'campo oculto',
    ],
];
