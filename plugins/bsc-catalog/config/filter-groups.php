<?php

return array(
	'price'  => array(
		'display_min' => 0,
		'display_max' => 200000,
		'step'        => 1000,
		'query_max'   => 999999,
	),
	'groups' => array(
		'group-skin-care' => array(
			array(
				'title'    => 'Tipo de Piel',
				'slug'     => 'sk-tipo-piel',
				'multiple' => false,
				'name'     => 'piel',
			),
			array(
				'title'    => 'Necesidad',
				'slug'     => 'sk-necesidades',
				'multiple' => false,
				'name'     => 'necesidad',
			),
			array(
				'title'    => 'Ingredientes',
				'slug'     => 'sk-ingredientes',
				'multiple' => false,
				'name'     => 'ingredientes',
			),
			array(
				'title'    => 'Marca',
				'slug'     => 'sk-marcas',
				'multiple' => false,
				'name'     => 'marca',
			),
		),
		'group-hair-care' => array(
			array(
				'title'    => 'Necesidad',
				'slug'     => 'hc-necesidades',
				'multiple' => false,
				'name'     => 'necesidad',
			),
			array(
				'title'    => 'Rutina',
				'slug'     => 'hc-rutina',
				'multiple' => true,
				'name'     => 'rutina',
			),
			array(
				'title'    => 'Marca',
				'slug'     => 'hc-marca',
				'multiple' => false,
				'name'     => 'marca',
			),
		),
		'group-make-up'   => array(
			array(
				'title'    => 'Producto',
				'slug'     => 'mk-productos',
				'multiple' => false,
				'name'     => 'producto',
			),
			array(
				'title'    => 'Marca',
				'slug'     => 'mk-marcas',
				'multiple' => false,
				'name'     => 'marca',
			),
		),
	),
);
