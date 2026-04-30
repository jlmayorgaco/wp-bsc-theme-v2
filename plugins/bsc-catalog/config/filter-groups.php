<?php

return [
    'price' => [
        'display_min' => 0,
        'display_max' => 200000,
        'step'        => 1000,
        'query_max'   => 999999,
    ],
    'groups' => [
        'group-skin-care' => [
            ['title' => 'Tipo de Piel', 'slug' => 'sk-tipo-piel', 'multiple' => true, 'name' => 'piel'],
            ['title' => 'Necesidad', 'slug' => 'sk-necesidades', 'multiple' => false, 'name' => 'necesidad'],
            ['title' => 'Ingredientes', 'slug' => 'sk-ingredientes', 'multiple' => true, 'name' => 'ingredientes'],
            ['title' => 'Marca', 'slug' => 'sk-marcas', 'multiple' => false, 'name' => 'marca'],
        ],
        'group-hair-care' => [
            ['title' => 'Necesidad', 'slug' => 'hc-necesidades', 'multiple' => false, 'name' => 'necesidad'],
            ['title' => 'Rutina', 'slug' => 'hc-rutina', 'multiple' => true, 'name' => 'rutina'],
            ['title' => 'Marca', 'slug' => 'hc-marca', 'multiple' => false, 'name' => 'marca'],
        ],
        'group-make-up' => [
            ['title' => 'Producto', 'slug' => 'mk-productos', 'multiple' => false, 'name' => 'producto'],
            ['title' => 'Marca', 'slug' => 'mk-marcas', 'multiple' => false, 'name' => 'marca'],
        ],
    ],
];
