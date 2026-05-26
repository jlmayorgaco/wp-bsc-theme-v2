<?php
/**
 * Gemini-powered skin quiz recommendations.
 */
defined( 'ABSPATH' ) || exit;

class BSC_Growth_AI_Service {
	private const MAX_IMAGE_BYTES = 4194304;

	public function recommend_with_image( array $answers, array $file ): array {
		$repository = new BSC_Growth_Bundle_Repository();
		$image      = $this->validate_image( $file );

		if ( ! $this->has_api_key() ) {
			return $this->fallback_response( $answers, $repository, 'AI pendiente de configurar.' );
		}

		try {
			$analysis = $this->analyze_image( $answers, $image, $repository );
		} catch ( Throwable $exception ) {
			return $this->fallback_response( $answers, $repository, 'AI no disponible: ' . sanitize_text_field( $exception->getMessage() ) );
		}

		try {
			$after = $this->generate_after_image( $image, $analysis );
		} catch ( Throwable $exception ) {
			$after = array();
			$analysis['visible_notes'][] = 'Vista after preliminar activa mientras Gemini imagen tiene cuota disponible.';
		}

		return $this->build_ai_response( $answers, $analysis, $after, $repository );
	}

	public function has_api_key(): bool {
		return '' !== $this->api_key();
	}

	private function analyze_image( array $answers, array $image, BSC_Growth_Bundle_Repository $repository ): array {
		$catalog = $repository->get_catalog_context( 80 );
		$prompt  = $this->analysis_prompt( $answers, $catalog, (array) ( $answers['vision_signals'] ?? array() ) );
		$result  = $this->call_generate_content(
			$this->analysis_model(),
			array(
				array(
					'inline_data' => array(
						'mime_type' => $image['mime_type'],
						'data'      => $image['base64'],
					),
				),
				array(
					'text' => $prompt,
				),
			),
			array(
				'temperature'      => 0.2,
				'responseMimeType' => 'application/json',
			)
		);

		$text = $this->extract_text( $result );
		$json = $this->parse_json( $text );

		if ( empty( $json ) ) {
			throw new RuntimeException( 'Gemini no devolvio una rutina valida.' );
		}

		return $json;
	}

	private function generate_after_image( array $image, array $analysis ): array {
		if ( ! (bool) get_option( 'bsc_gemini_generate_after_image', 1 ) ) {
			return array();
		}

		$result = $this->call_generate_content(
			$this->image_model(),
			array(
				array(
					'text' => $this->after_prompt( $analysis ),
				),
				array(
					'inline_data' => array(
						'mime_type' => $image['mime_type'],
						'data'      => $image['base64'],
					),
				),
			),
			array(
				'temperature'        => 0.4,
				'responseModalities' => array( 'TEXT', 'IMAGE' ),
			)
		);

		return $this->extract_inline_image( $result );
	}

	private function build_ai_response( array $answers, array $analysis, array $after, BSC_Growth_Bundle_Repository $repository ): array {
		$skin_profile = is_array( $analysis['skin_profile'] ?? null ) ? $analysis['skin_profile'] : array();
		$routine      = is_array( $analysis['routine'] ?? null ) ? $analysis['routine'] : array();
		$product_ids  = wp_parse_id_list( (array) ( $analysis['product_ids'] ?? array() ) );

		if ( empty( $product_ids ) ) {
			$fallback    = $repository->recommend_bundles( $this->answers_from_profile( $answers, $skin_profile ), 1 );
			$product_ids = wp_parse_id_list( (array) ( $fallback[0]['product_ids'] ?? array() ) );
		}

		$bundle = $repository->format_dynamic_bundle_for_response(
			array(
				'id'             => 'ai-routine-' . substr( md5( wp_json_encode( $product_ids ) ), 0, 8 ),
				'title'          => sanitize_text_field( (string) ( $routine['title'] ?? 'Rutina AI BSC' ) ),
				'summary'        => sanitize_text_field( (string) ( $routine['summary'] ?? 'Rutina armada con la foto y respuestas del quiz.' ) ),
				'badge'          => 'AI Enhanced',
				'discount_label' => 'Carrito listo',
				'product_ids'    => array_slice( $product_ids, 0, 6 ),
				'steps'          => $this->sanitize_steps( (array) ( $routine['steps'] ?? array() ) ),
			)
		);

		return array(
			'bundles' => array( $bundle ),
			'ai'      => array(
				'enabled'             => true,
				'fallback'            => false,
				'skin_profile'        => $this->sanitize_profile( $skin_profile ),
				'after_image_data_uri' => ! empty( $after['data'] ) ? 'data:' . $after['mime_type'] . ';base64,' . $after['data'] : '',
				'after_description'   => sanitize_text_field( (string) ( $analysis['after_description'] ?? 'Acabado hidratado, uniforme y luminoso despues de la rutina.' ) ),
				'notes'               => array_values( array_map( 'sanitize_text_field', (array) ( $analysis['visible_notes'] ?? array() ) ) ),
			),
		);
	}

	private function fallback_response( array $answers, BSC_Growth_Bundle_Repository $repository, string $message ): array {
		$bundles = array_map(
			static fn( array $bundle ): array => $repository->format_bundle_for_response( $bundle ),
			$repository->recommend_bundles( $answers, 3 )
		);

		return array(
			'bundles' => $bundles,
			'ai'      => array(
				'enabled'             => false,
				'fallback'            => true,
				'skin_profile'        => array(),
				'after_image_data_uri' => '',
				'after_description'   => 'Simulacion visual pendiente de IA.',
				'notes'               => array( $message ),
			),
		);
	}

	private function validate_image( array $file ): array {
		$error = (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE );

		if ( UPLOAD_ERR_OK !== $error ) {
			throw new RuntimeException( 'Sube una foto para activar AI Enhanced.' );
		}

		$tmp_name = (string) ( $file['tmp_name'] ?? '' );
		$size     = (int) ( $file['size'] ?? 0 );

		if ( '' === $tmp_name || ! is_uploaded_file( $tmp_name ) ) {
			throw new RuntimeException( 'La foto no se pudo leer.' );
		}

		if ( $size <= 0 || $size > self::MAX_IMAGE_BYTES ) {
			throw new RuntimeException( 'La foto debe pesar menos de 4MB.' );
		}

		$bytes     = file_get_contents( $tmp_name );
		$imageinfo = @getimagesize( $tmp_name );
		$mime_type = is_array( $imageinfo ) ? (string) ( $imageinfo['mime'] ?? '' ) : '';
		$allowed   = array( 'image/jpeg', 'image/png', 'image/webp' );

		if ( false === $bytes || ! in_array( $mime_type, $allowed, true ) ) {
			throw new RuntimeException( 'Usa una foto JPG, PNG o WebP.' );
		}

		return array(
			'mime_type' => $mime_type,
			'base64'    => base64_encode( $bytes ),
		);
	}

	private function call_generate_content( string $model, array $parts, array $generation_config ): array {
		$response = wp_remote_post(
			'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode( $model ) . ':generateContent',
			array(
				'timeout' => 35,
				'headers' => array(
					'Content-Type'   => 'application/json',
					'x-goog-api-key' => $this->api_key(),
				),
				'body'    => wp_json_encode(
					array(
						'contents'         => array(
							array(
								'role'  => 'user',
								'parts' => $parts,
							),
						),
						'generationConfig' => $generation_config,
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( esc_html( $response->get_error_message() ) );
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$body   = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( $status < 200 || $status >= 300 || ! is_array( $body ) ) {
			throw new RuntimeException( 'Gemini no respondio correctamente.' );
		}

		return $body;
	}

	private function extract_text( array $response ): string {
		$text = '';

		foreach ( (array) ( $response['candidates'][0]['content']['parts'] ?? array() ) as $part ) {
			if ( isset( $part['text'] ) ) {
				$text .= (string) $part['text'];
			}
		}

		return $text;
	}

	private function extract_inline_image( array $response ): array {
		foreach ( (array) ( $response['candidates'][0]['content']['parts'] ?? array() ) as $part ) {
			$inline = $part['inlineData'] ?? $part['inline_data'] ?? null;

			if ( ! is_array( $inline ) || empty( $inline['data'] ) ) {
				continue;
			}

			return array(
				'mime_type' => (string) ( $inline['mimeType'] ?? $inline['mime_type'] ?? 'image/png' ),
				'data'      => (string) $inline['data'],
			);
		}

		return array();
	}

	private function parse_json( string $text ): array {
		$text = trim( preg_replace( '/^```(?:json)?|```$/m', '', $text ) ?? $text );
		$data = json_decode( $text, true );

		if ( is_array( $data ) ) {
			return $data;
		}

		if ( preg_match( '/\{.*\}/s', $text, $match ) ) {
			$data = json_decode( $match[0], true );
			return is_array( $data ) ? $data : array();
		}

		return array();
	}

	/**
	 * Build the AI instruction used to turn a selfie into cosmetic guidance.
	 */
	private function analysis_prompt( array $answers, array $catalog, array $vision_signals ): string {
		$context = array(
			'answers'                 => array_diff_key( $answers, array( 'vision_signals' => true ) ),
			'computer_vision_signals' => $vision_signals,
			'catalog'                 => $catalog,
		);

		return implode(
			"\n",
			array(
				'Actua como una asesora cosmetica senior de K-beauty para un ecommerce llamado Bubbles Skin Care.',
				'Objetivo: entregar una asesoria cosmetica util, personalizada y comprable, no un diagnostico medico.',
				'Reglas de seguridad: no diagnostiques enfermedades, no identifiques a la persona, no menciones edad/raza/genero, no prometas curas, no uses lenguaje clinico fuerte como inflamacion, lesion, rosacea, melasma o dermatitis. Usa "aparente", "visible", "tendencia" o "se percibe" cuando hables de la foto.',
				'Fuentes que debes cruzar: 1) selfie, 2) respuestas de la cliente, 3) senales computacionales no diagnosticas, 4) catalogo real disponible.',
				'Interpreta computer_vision_signals como pistas aproximadas de la zona tipo piel, no como verdad absoluta: brightness/contrast/saturation/sharpness van de 0 a 1; skin_pixel_ratio bajo sugiere que la foto no deja leer bien la piel; shine_signal sugiere brillo visible; redness_signal sugiere rojeces o brotes aparentes; dark_spot_signal sugiere manchas o sombras visibles; texture_signal sugiere textura/variacion; quality_flags puede indicar baja luz, sobreexposicion, bajo contraste, blur o zona de piel poco clara.',
				'Prioriza una rutina facial equilibrada y simple: limpieza solo si hay producto facial claro, tonico/esencia, serum/tratamiento, crema/hidratante y SPF si corresponde. Divide mentalmente la recomendacion en manana y noche, y especifica frecuencia si un paso no debe usarse diario. No recomiendes hair care, lash, lip, makeup, body o productos no faciales aunque aparezcan en el catalogo.',
				'Selecciona 3 a 5 product_ids reales del catalogo, en orden de uso. Evita duplicados funcionales salvo que la rutina lo justifique. Si no hay SPF facial disponible, no inventes producto.',
				'La rutina debe explicar por que cada paso existe, que prioridad cosmetica cubre, como se integra con la piel percibida y que resultado realista puede esperar la cliente. Se concreta y evita claims exagerados.',
				'Si la foto tiene calidad limitada, dilo con delicadeza en visible_notes y apoya mas la rutina en las respuestas. Si detectas posible sensibilidad, barrera comprometida o brotes aparentes, prioriza calma, hidratacion y SPF antes que una rutina agresiva.',
				'Devuelve SOLO JSON valido, sin markdown, con esta forma exacta: {"skin_profile":{"skin_type":"grasa|mixta|seca|normal|sensible","needs":["acne","manchas","hidratacion","barrera","glow","protector-solar"],"confidence":0.0},"visible_notes":["2 a 4 observaciones cosmeticas accionables: lectura de piel, prioridad, cuidado o nota de foto"],"routine":{"title":"titulo especifico","summary":"resumen de valor para cliente","steps":[{"label":"Manana/Noche - paso + producto o categoria","why":"razon cosmetica concreta, frecuencia y beneficio realista"}]},"product_ids":[123,456],"after_description":"descripcion realista, sutil y no clinica del acabado esperado"}.',
				'Contexto JSON: ' . wp_json_encode( $context ),
			)
		);
	}

	private function after_prompt( array $analysis ): string {
		$description = sanitize_text_field( (string) ( $analysis['after_description'] ?? 'acabado hidratado y luminoso' ) );

		return 'Edita la foto como una simulacion cosmetica realista del resultado visual despues de una rutina de skincare: ' . $description . '. Manten identidad, rasgos, edad, tono de piel, encuadre y luz. No cambies estructura facial. No hagas piel perfecta irreal. Muestra acabado hidratado, calmado y saludable. Sin texto, sin logos.';
	}

	private function sanitize_profile( array $profile ): array {
		return array(
			'skin_type'  => sanitize_key( (string) ( $profile['skin_type'] ?? '' ) ),
			'needs'      => array_slice( array_values( array_map( 'sanitize_key', (array) ( $profile['needs'] ?? array() ) ) ), 0, 6 ),
			'confidence' => max( 0, min( 1, (float) ( $profile['confidence'] ?? 0 ) ) ),
		);
	}

	private function sanitize_steps( array $steps ): array {
		return array_slice(
			array_map(
				static fn( array $step ): array => array(
					'label' => sanitize_text_field( (string) ( $step['label'] ?? '' ) ),
					'why'   => sanitize_text_field( (string) ( $step['why'] ?? '' ) ),
				),
				array_filter( $steps, 'is_array' )
			),
			0,
			6
		);
	}

	private function answers_from_profile( array $answers, array $profile ): array {
		if ( ! empty( $profile['skin_type'] ) ) {
			$answers['skin_type'] = sanitize_key( (string) $profile['skin_type'] );
		}

		if ( ! empty( $profile['needs'] ) ) {
			$answers['needs'] = array_map( 'sanitize_key', (array) $profile['needs'] );
		}

		return $answers;
	}

	private function api_key(): string {
		if ( defined( 'BSC_GEMINI_API_KEY' ) && BSC_GEMINI_API_KEY ) {
			return (string) BSC_GEMINI_API_KEY;
		}

		$env_key = getenv( 'GEMINI_API_KEY' );
		if ( is_string( $env_key ) && '' !== $env_key ) {
			return $env_key;
		}

		return (string) get_option( 'bsc_gemini_api_key', '' );
	}

	private function analysis_model(): string {
		return sanitize_text_field( (string) get_option( 'bsc_gemini_skin_model', 'gemini-3.5-flash' ) );
	}

	private function image_model(): string {
		return sanitize_text_field( (string) get_option( 'bsc_gemini_image_model', 'gemini-2.5-flash-image' ) );
	}
}
