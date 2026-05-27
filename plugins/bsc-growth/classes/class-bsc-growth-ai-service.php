<?php
/**
 * Gemini-powered skin quiz recommendations.
 */
defined( 'ABSPATH' ) || exit;

class BSC_Growth_AI_Service {
	private const MAX_IMAGE_BYTES = 4194304;
	private const AFTER_IMAGE_UPSCALE_LONG_EDGE = 1536;
	private const MAX_GENERATED_IMAGE_BYTES = 8388608;

	public function recommend_with_image( array $answers, array $file ): array {
		$repository = new BSC_Growth_Bundle_Repository();
		$image      = $this->validate_image( $file );
		$signals    = (array) ( $answers['vision_signals'] ?? array() );
		$usage      = array();

		if ( ! $this->has_api_key() ) {
			return $this->fallback_response( $answers, $repository, 'AI pendiente de configurar.' );
		}

		try {
			$analysis = $this->analyze_image( $answers, $image, $repository );
			$usage[]  = (array) ( $analysis['_bsc_usage'] ?? array() );
			unset( $analysis['_bsc_usage'] );
		} catch ( Throwable $exception ) {
			return $this->fallback_response( $answers, $repository, 'AI no disponible: ' . sanitize_text_field( $exception->getMessage() ) );
		}

		try {
			$after = $this->generate_after_image( $image, $analysis, $signals );
			if ( isset( $after['usage'] ) && is_array( $after['usage'] ) ) {
				$usage[] = $after['usage'];
				unset( $after['usage'] );
			}
		} catch ( Throwable $exception ) {
			$after = array();
			$analysis['visible_notes'][] = 'Vista after preliminar activa mientras Gemini imagen tiene cuota disponible.';
		}

		return $this->build_ai_response( $answers, $analysis, $after, $repository, $this->combine_usage( $usage ) );
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
			throw new RuntimeException( 'Gemini no devolvió una rutina válida.' );
		}

		$json['_bsc_usage'] = $this->usage_summary( $result, $this->analysis_model(), 'analysis', false );

		return $json;
	}

	private function generate_after_image( array $image, array $analysis, array $vision_signals ): array {
		if ( ! (bool) get_option( 'bsc_gemini_generate_after_image', 1 ) ) {
			return array();
		}

		$result = $this->call_generate_content(
			$this->image_model(),
			array(
				array(
					'text' => $this->after_prompt( $analysis, $vision_signals ),
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

		$image_payload          = $this->enhance_generated_image( $this->extract_inline_image( $result ) );
		$image_payload['usage'] = $this->usage_summary( $result, $this->image_model(), 'after_image', ! empty( $image_payload['data'] ) );

		return $image_payload;
	}

	private function build_ai_response( array $answers, array $analysis, array $after, BSC_Growth_Bundle_Repository $repository, array $usage = array() ): array {
		$skin_profile = is_array( $analysis['skin_profile'] ?? null ) ? $analysis['skin_profile'] : array();
		$routine      = is_array( $analysis['routine'] ?? null ) ? $analysis['routine'] : array();
		$product_ids  = wp_parse_id_list( (array) ( $analysis['product_ids'] ?? array() ) );
		$signals      = is_array( $answers['vision_signals'] ?? null ) ? (array) $answers['vision_signals'] : array();

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

		if ( empty( $bundle['product_ids'] ) || empty( $bundle['products'] ) ) {
			$fallback    = $repository->recommend_bundles( $this->answers_from_profile( $answers, $skin_profile ), 1 );
			$product_ids = wp_parse_id_list( (array) ( $fallback[0]['product_ids'] ?? array() ) );
			$bundle      = $repository->format_dynamic_bundle_for_response(
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
		}

		return array(
			'bundles' => array( $bundle ),
			'ai'      => array(
				'enabled'             => true,
				'fallback'            => false,
				'skin_profile'        => $this->sanitize_profile( $skin_profile ),
				'diagnostic_scores'   => $this->sanitize_diagnostic_scores( (array) ( $analysis['diagnostic_scores'] ?? array() ), $signals, $skin_profile ),
				'assessment'          => $this->sanitize_assessment( $analysis['professional_assessment'] ?? array() ),
				'after_image_data_uri' => ! empty( $after['data'] ) ? 'data:' . $after['mime_type'] . ';base64,' . $after['data'] : '',
				'after_description'   => sanitize_text_field( (string) ( $analysis['after_description'] ?? 'Acabado hidratado, uniforme y luminoso después de la rutina.' ) ),
				'notes'               => array_values( array_map( 'sanitize_text_field', (array) ( $analysis['visible_notes'] ?? array() ) ) ),
				'usage'               => $usage,
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
				'diagnostic_scores'   => $this->sanitize_diagnostic_scores( array(), is_array( $answers['vision_signals'] ?? null ) ? (array) $answers['vision_signals'] : array(), array() ),
				'assessment'          => array(
					'headline' => 'Lectura preliminar',
					'summary'  => 'Armamos una rutina con tus respuestas y las señales visibles disponibles. Cuando Gemini esté activo, esta lectura incluirá una asesoría cosmética más profunda por zonas, prioridades y uso de producto.',
				),
				'after_image_data_uri' => '',
				'after_description'   => 'Simulación visual pendiente de IA.',
				'notes'               => array( $message ),
				'usage'               => $this->combine_usage( array() ),
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
			throw new RuntimeException( 'Gemini no respondió correctamente.' );
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
	 *
	 * @param array $answers Client answers.
	 * @param array $catalog Product catalog context.
	 * @param array $vision_signals Browser-side image signals.
	 * @return string
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
				'Actúa como una cosmetóloga profesional senior y asesora cosmética de K-beauty para un ecommerce llamado Bubbles Skin Care.',
				'Objetivo: entregar una asesoría cosmética útil, personalizada y comprable, no un diagnóstico médico.',
				'Tono de respuesta: profesional, cálido, específico y accionable. Escribe como una asesora experta que revisa la foto, las respuestas y la rutina posible en tienda. Evita frases genéricas como "piel bonita" o "rutina perfecta"; explica prioridades, zonas y orden de uso.',
				'Reglas de seguridad: no diagnostiques enfermedades, no identifiques a la persona, no menciones raza/género, no prometas curas, no uses lenguaje clínico fuerte como inflamación, lesión, rosácea, melasma o dermatitis. Usa "aparente", "visible", "tendencia" o "se percibe" cuando hables de la foto.',
				'Edad y piel: usa age_range solo porque la cliente lo declaró. No estimes edad aparente ni digas una "edad de piel" numérica. Si age_context.alignment es aligned_or_lower_support, puedes decir que la rutina va bien como mantenimiento/preventiva para su rango. Si es preventive_extra_support o extra_support_recommended, dilo con tacto como "para tu rango conviene reforzar..." y enfoca la rutina en antioxidantes, barrera, hidratación, SPF, tono o textura según las señales. Nunca digas "tu piel se ve vieja" ni "pareces mayor".',
				'Fuentes que debes cruzar: 1) selfie, 2) respuestas de la cliente, 3) señales computacionales no diagnósticas, 4) catálogo real disponible.',
				'Interpreta computer_vision_signals como pistas aproximadas de la zona tipo piel, no como verdad absoluta. Son resultados de un motor local BSC hecho con Canvas/WebGL: skin mask, mapas de borde/textura, clusters de manchas/sombras/rojeces y lectura por zonas faciales aproximadas. brightness/contrast/saturation/sharpness van de 0 a 1; skin_pixel_ratio y mask_skin_confidence indican si hay suficiente piel visible; skin_type_proxy sugiere tipo de piel probable por evidencia visual, pero debes cruzarlo con la respuesta declarada; skin_support_signal resume cuánto soporte cosmético parece pedir la piel sin convertirlo en edad; face_detection, face_count, face_area_ratio, face_center_score y face_box_* solo indican encuadre útil; lighting_temperature_proxy, lighting_tint_proxy, lighting_evenness_signal, lighting_cast_signal y lighting_shadow_bias indican si debes desconfiar de color/manchas por luz; symmetry_balance_signal y asymmetric_* indican si una marca puede ser sombra/pose; shine_signal, oil_control_signal, zone_forehead_shine, zone_nose_shine y zone_t_shine sugieren brillo visible; redness_signal, zone_cheek_redness, red_cluster_signal y barrier_stress_signal sugieren rojeces o brotes aparentes; dark_spot_signal, spot_cluster_signal, spot_area_signal, pigment_spot_proxy, lighting_shadow_proxy, spot_shadow_confidence, tone_unevenness_signal y spf_priority_signal sugieren manchas, sombras o tono desigual; texture_signal, fine_texture_signal, pores_proxy_signal y zone_chin_texture sugieren textura; dryness_signal e hydration_need_signal sugieren necesidad de hidratación/barrera; under_eye_shadow_signal es solo acabado cosmético de mirada descansada; corrected_skin_tone_* es mejor para colorimetría que skin_tone_* cuando hay cast de luz; makeup_profile_version=makeup_profile_v2, undertone_proxy y skin_depth_proxy son pistas de colorimetría cosmética, no identidad; makeup_base_finish, makeup_coverage_hint, makeup_color_family y makeup_*_v2 ayudan a recomendar un look sutil si hay maquillaje disponible; progress_tracking_ready, progress_signature y progress_context solo sirven para comparar futuras sesiones, no para prometer resultados; cosmetic_priority_flags y routine_focus_hint resumen prioridades; quality_flags puede indicar baja luz, sobreexposición, bajo contraste, blur o zona de piel poco clara.',
				'Usa las señales computacionales como ventaja diferencial: primero valida calidad de foto, luego cruza prioridades por zona y finalmente elige productos del catálogo. Si cosmetic_priority_flags incluye tone_evening o spf_priority, prioriza antioxidantes/iluminadores suaves y SPF. Si incluye hydration_barrier o calming, prioriza barrera, hidratación y calma. Si incluye tzone_shine o breakout_support, equilibra sebo sin resecar. Si incluye texture_refinement, usa exfoliación o renovación suave solo si el catálogo lo permite y con frecuencia moderada.',
				'Usa las respuestas nuevas para dar asesoría real: age_range y age_context ajustan nivel de prevención y soporte sin estimar edad; progress_context puede mencionar continuidad solo si hay snapshot previo comparable; skin_goal define la prioridad comercial y cosmética; sunscreen_habit define si debes reforzar SPF; post_cleanse_feel ayuda a inferir barrera/resequedad/brillo; breakout_frequency ajusta intensidad y evita recomendar rutinas agresivas.',
				'Prioriza una rutina facial equilibrada y simple: limpieza solo si hay producto facial claro, tónico/esencia, sérum/tratamiento, crema/hidratante y SPF si corresponde. Divide mentalmente la recomendación en mañana y noche, y especifica frecuencia si un paso no debe usarse diario. No recomiendes hair care, lash, lip, makeup, body o productos no faciales aunque aparezcan en el catálogo.',
				'Selecciona 3 a 5 product_ids reales del catálogo, en orden de uso. Evita duplicados funcionales salvo que la rutina lo justifique. Si no hay SPF facial disponible, no inventes producto.',
				'La rutina debe explicar por qué cada paso existe, qué prioridad cosmética cubre, cómo se integra con la piel percibida y qué resultado realista puede esperar la cliente. Sé concreta y evita claims exagerados.',
				'Si la foto tiene calidad limitada, dilo con delicadeza en visible_notes y apoya más la rutina en las respuestas. Si detectas posible sensibilidad, barrera comprometida o brotes aparentes, prioriza calma, hidratación y SPF antes que una rutina agresiva.',
				'diagnostic_scores debe medir prioridad cosmética de trabajo, no severidad médica. Cada valor va de 0.0 a 1.0 y debe estar alineado con foto, respuestas, señales locales y productos elegidos: sebum_balance = control de brillo/zona T; hydration_barrier = hidratación y barrera; tone_evenness = tono, manchas aparentes o uniformidad; calmness = calma, rojeces aparentes o brotes; texture_refinement = textura/poros visibles; spf_priority = importancia de protector solar.',
				'professional_assessment debe sentirse como asesoría real: headline corto y summary de 55 a 85 palabras en segunda persona. Debe cruzar al menos 3 datos: zona visible, señal computacional/respuesta, prioridad de rutina, y advertencia suave si la foto limita la lectura. No repitas exactamente visible_notes.',
				'visible_notes debe traer 3 a 5 observaciones accionables, cada una con zona/prioridad/cuidado. routine.summary debe ser más comercial y claro: qué vamos a trabajar y cómo se sentirá la rutina.',
				'Devuelve SOLO JSON válido, sin markdown, con esta forma exacta: {"skin_profile":{"skin_type":"grasa|mixta|seca|normal|sensible","needs":["acne","manchas","hidratacion","barrera","glow","protector-solar"],"confidence":0.0},"diagnostic_scores":{"sebum_balance":0.0,"hydration_barrier":0.0,"tone_evenness":0.0,"calmness":0.0,"texture_refinement":0.0,"spf_priority":0.0},"professional_assessment":{"headline":"lectura corta","summary":"asesoría cosmética profesional, profunda y accionable"},"visible_notes":["3 a 5 observaciones cosméticas accionables basadas en foto + señales + respuestas: zona, prioridad y cuidado"],"routine":{"title":"título específico","summary":"resumen de valor para cliente","steps":[{"time_of_day":"manana|noche|semanal","frequency":"diario|2-3 veces por semana|solo noche","product_id":123,"label":"paso + producto o categoría","amount":"cantidad sugerida breve","why":"razón cosmética concreta, frecuencia y beneficio realista","warning":"precaución suave si aplica"}]},"product_ids":[123,456],"after_description":"descripción realista, sutil y no clínica del acabado esperado, mencionando acabado de zona T, tono, textura o glow solo si aplica"}.',
				'Contexto JSON: ' . wp_json_encode( $context ),
			)
		);
	}

	private function after_prompt( array $analysis, array $vision_signals ): string {
		$description = sanitize_text_field( (string) ( $analysis['after_description'] ?? 'acabado hidratado y luminoso' ) );
		$flags       = array_slice( array_values( array_map( 'sanitize_key', (array) ( $vision_signals['cosmetic_priority_flags'] ?? array() ) ) ), 0, 8 );
		$focus       = sanitize_text_field( (string) ( $vision_signals['routine_focus_hint'] ?? '' ) );
		$finish      = sanitize_text_field( (string) ( $vision_signals['makeup_finish_hint'] ?? '' ) );
		$lip_hint    = sanitize_text_field( (string) ( $vision_signals['makeup_lip_hint'] ?? '' ) );
		$blush_hint  = sanitize_text_field( (string) ( $vision_signals['makeup_blush_hint'] ?? '' ) );
		$signal_json = wp_json_encode(
			array(
				'flags'                => $flags,
				'focus'                => $focus,
				'oil_control_signal'   => $vision_signals['oil_control_signal'] ?? null,
				'tone_unevenness'      => $vision_signals['tone_unevenness_signal'] ?? null,
				'dryness_signal'       => $vision_signals['dryness_signal'] ?? null,
				'barrier_stress'       => $vision_signals['barrier_stress_signal'] ?? null,
				'under_eye_shadow'     => $vision_signals['under_eye_shadow_signal'] ?? null,
				'lighting_evenness'    => $vision_signals['lighting_evenness_signal'] ?? null,
				'lighting_shadow_bias' => $vision_signals['lighting_shadow_bias'] ?? '',
				'pigment_spot_proxy'   => $vision_signals['pigment_spot_proxy'] ?? null,
				'lighting_shadow_proxy' => $vision_signals['lighting_shadow_proxy'] ?? null,
				'makeup_profile_v2'    => array(
					'base_finish'      => $vision_signals['makeup_base_finish'] ?? '',
					'coverage'         => $vision_signals['makeup_coverage_hint'] ?? '',
					'color_family'     => $vision_signals['makeup_color_family'] ?? '',
					'texture_strategy' => $vision_signals['makeup_texture_strategy'] ?? '',
				),
				'makeup_finish_hint'   => $finish,
				'makeup_lip_hint'      => $lip_hint,
				'makeup_blush_hint'    => $blush_hint,
			)
		);

		return 'Edita la foto como una simulación cosmética realista y natural del resultado visual después de una rutina de skincare: ' . $description . '. Mejora la imagen como entrega premium: upscale limpio, mayor nitidez percibida, reducción sutil de ruido, mejor iluminación facial, balance de blancos natural, contraste suave y resolución visual más alta. Usa estas señales locales BSC solo para guiar el acabado visual, no para cambiar identidad: ' . $signal_json . '. Conserva poros, textura real, líneas naturales, forma de labios, forma de ojos, nariz, cejas, lunares y estructura facial. Si hay brillo en zona T, reduce solo el exceso y conserva piel real. Si hay tono desigual o manchas/sombras aparentes, unifica suavemente sin borrar textura. Si hay rojez aparente, calma el color sin dejar piel plástica. Si hay resequedad/textura, agrega glow hidratado natural. Puede incluir maquillaje muy sutil según makeup_lip_hint y makeup_blush_hint, pero debe verse como prueba cosmética limpia, no filtro pesado. Mantén identidad, rasgos, edad, tono de piel, encuadre y luz original de forma coherente. No cambies estructura facial. No hagas piel perfecta irreal. No cambies expresión. Sin texto, sin logos.';
	}

	private function enhance_generated_image( array $image ): array {
		if ( empty( $image['data'] ) || ! is_string( $image['data'] ) || ! function_exists( 'imagecreatefromstring' ) || ! function_exists( 'imagescale' ) || ! function_exists( 'imagejpeg' ) ) {
			return $image;
		}

		$bytes = base64_decode( $image['data'], true );
		if ( false === $bytes || strlen( $bytes ) > self::MAX_GENERATED_IMAGE_BYTES ) {
			return $image;
		}

		$source = @imagecreatefromstring( $bytes );
		if ( ! $source ) {
			return $image;
		}

		$width  = imagesx( $source );
		$height = imagesy( $source );
		$longer = max( $width, $height );

		if ( $width <= 0 || $height <= 0 || $longer >= self::AFTER_IMAGE_UPSCALE_LONG_EDGE ) {
			imagedestroy( $source );
			return $image;
		}

		$scale  = min( 2, self::AFTER_IMAGE_UPSCALE_LONG_EDGE / $longer );
		$new_w  = max( 1, (int) round( $width * $scale ) );
		$new_h  = max( 1, (int) round( $height * $scale ) );
		$mode   = defined( 'IMG_BICUBIC_FIXED' ) ? IMG_BICUBIC_FIXED : ( defined( 'IMG_BILINEAR_FIXED' ) ? IMG_BILINEAR_FIXED : 1 );
		$scaled = @imagescale( $source, $new_w, $new_h, $mode );
		imagedestroy( $source );

		if ( ! $scaled ) {
			return $image;
		}

		ob_start();
		imagejpeg( $scaled, null, 92 );
		$upscaled = ob_get_clean();
		imagedestroy( $scaled );

		if ( ! is_string( $upscaled ) || '' === $upscaled ) {
			return $image;
		}

		return array(
			'mime_type' => 'image/jpeg',
			'data'      => base64_encode( $upscaled ),
		);
	}

	private function sanitize_profile( array $profile ): array {
		return array(
			'skin_type'  => sanitize_key( (string) ( $profile['skin_type'] ?? '' ) ),
			'needs'      => array_slice( array_values( array_map( 'sanitize_key', (array) ( $profile['needs'] ?? array() ) ) ), 0, 6 ),
			'confidence' => max( 0, min( 1, (float) ( $profile['confidence'] ?? 0 ) ) ),
		);
	}

	private function sanitize_assessment( $assessment ): array {
		if ( ! is_array( $assessment ) ) {
			return array();
		}

		return array_filter(
			array(
				'headline' => sanitize_text_field( (string) ( $assessment['headline'] ?? '' ) ),
				'summary'  => sanitize_textarea_field( (string) ( $assessment['summary'] ?? '' ) ),
			)
		);
	}

	private function sanitize_diagnostic_scores( array $scores, array $signals, array $profile ): array {
		$derived = $this->derive_diagnostic_scores( $signals, $profile );
		$keys    = array( 'sebum_balance', 'hydration_barrier', 'tone_evenness', 'calmness', 'texture_refinement', 'spf_priority' );
		$clean   = array();

		foreach ( $keys as $key ) {
			$value = $scores[ $key ] ?? $derived[ $key ] ?? 0;

			if ( is_array( $value ) ) {
				$value = $value['score'] ?? $value['value'] ?? 0;
			}

			$value         = is_numeric( $value ) ? (float) $value : 0;
			$clean[ $key ] = max( 0, min( 1, $value ) );
		}

		return $clean;
	}

	private function derive_diagnostic_scores( array $signals, array $profile ): array {
		$needs     = array_map( 'sanitize_key', (array) ( $profile['needs'] ?? array() ) );
		$skin_type = sanitize_key( (string) ( $profile['skin_type'] ?? '' ) );

		$need_boost = static fn( string $need ): float => in_array( $need, $needs, true ) ? 0.72 : 0;
		$type_boost = static fn( array $types ): float => in_array( $skin_type, $types, true ) ? 0.58 : 0;

		$score = static function ( array $keys ) use ( $signals ): float {
			$value = 0;

			foreach ( $keys as $key ) {
				$value = max( $value, is_numeric( $signals[ $key ] ?? null ) ? (float) $signals[ $key ] : 0 );
			}

			return $value;
		};

		return array(
			'sebum_balance'      => max( $score( array( 'oil_control_signal', 'shine_signal', 'zone_t_shine', 'zone_forehead_shine', 'zone_nose_shine' ) ), $type_boost( array( 'grasa', 'mixta' ) ), $need_boost( 'acne' ) * 0.72 ),
			'hydration_barrier'  => max( $score( array( 'hydration_need_signal', 'dryness_signal', 'barrier_stress_signal' ) ), $type_boost( array( 'seca', 'sensible' ) ), $need_boost( 'hidratacion' ), $need_boost( 'barrera' ) ),
			'tone_evenness'      => max( $score( array( 'tone_unevenness_signal', 'dark_spot_signal', 'pigment_spot_proxy', 'spot_cluster_signal' ) ), $need_boost( 'manchas' ), $need_boost( 'glow' ) * 0.62 ),
			'calmness'           => max( $score( array( 'redness_signal', 'zone_cheek_redness', 'red_cluster_signal', 'barrier_stress_signal' ) ), $type_boost( array( 'sensible' ) ), $need_boost( 'acne' ) * 0.62 ),
			'texture_refinement' => max( $score( array( 'texture_signal', 'fine_texture_signal', 'pores_proxy_signal', 'zone_chin_texture' ) ), $need_boost( 'glow' ) * 0.52 ),
			'spf_priority'       => max( $score( array( 'spf_priority_signal', 'tone_unevenness_signal', 'pigment_spot_proxy' ) ), $need_boost( 'protector-solar' ), $need_boost( 'manchas' ) * 0.82 ),
		);
	}

	private function sanitize_steps( array $steps ): array {
		return array_slice(
			array_map(
				static fn( array $step ): array => array(
					'time_of_day' => sanitize_key( (string) ( $step['time_of_day'] ?? '' ) ),
					'frequency'   => sanitize_text_field( (string) ( $step['frequency'] ?? '' ) ),
					'product_id'  => absint( $step['product_id'] ?? 0 ),
					'label'       => sanitize_text_field( (string) ( $step['label'] ?? '' ) ),
					'amount'      => sanitize_text_field( (string) ( $step['amount'] ?? '' ) ),
					'why'         => sanitize_text_field( (string) ( $step['why'] ?? '' ) ),
					'warning'     => sanitize_text_field( (string) ( $step['warning'] ?? '' ) ),
				),
				array_filter( $steps, 'is_array' )
			),
			0,
			6
		);
	}

	private function usage_summary( array $response, string $model, string $type, bool $generated_image ): array {
		$usage      = is_array( $response['usageMetadata'] ?? null ) ? $response['usageMetadata'] : array();
		$input      = (int) ( $usage['promptTokenCount'] ?? 0 );
		$output     = (int) ( $usage['candidatesTokenCount'] ?? 0 );
		$total      = (int) ( $usage['totalTokenCount'] ?? ( $input + $output ) );
		$input_rate = (float) get_option( 'bsc_skin_quiz_analysis_input_usd_m', 1.5 );
		$out_rate   = (float) get_option( 'bsc_skin_quiz_analysis_output_usd_m', 9 );
		$image_base = $generated_image ? (float) get_option( 'bsc_skin_quiz_image_base_usd', 0.039 ) : 0.0;
		$usd        = ( $input * $input_rate / 1000000 ) + ( $output * $out_rate / 1000000 ) + $image_base;

		return array(
			'type'          => $type,
			'model'         => sanitize_text_field( $model ),
			'input_tokens'  => $input,
			'output_tokens' => $output,
			'total_tokens'  => $total,
			'cost_usd'      => round( $usd, 6 ),
			'cost_cop'      => round( $usd * (float) get_option( 'bsc_skin_quiz_usd_to_cop', 4000 ), 2 ),
		);
	}

	private function combine_usage( array $items ): array {
		$total = array(
			'calls'         => array_values( array_filter( $items ) ),
			'input_tokens'  => 0,
			'output_tokens' => 0,
			'total_tokens'  => 0,
			'cost_usd'      => 0.0,
			'cost_cop'      => 0.0,
			'fallback'      => empty( $items ),
		);

		foreach ( $total['calls'] as $item ) {
			$total['input_tokens']  += (int) ( $item['input_tokens'] ?? 0 );
			$total['output_tokens'] += (int) ( $item['output_tokens'] ?? 0 );
			$total['total_tokens']  += (int) ( $item['total_tokens'] ?? 0 );
			$total['cost_usd']      += (float) ( $item['cost_usd'] ?? 0 );
			$total['cost_cop']      += (float) ( $item['cost_cop'] ?? 0 );
		}

		$total['cost_usd'] = round( $total['cost_usd'], 6 );
		$total['cost_cop'] = round( $total['cost_cop'], 2 );

		return $total;
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
