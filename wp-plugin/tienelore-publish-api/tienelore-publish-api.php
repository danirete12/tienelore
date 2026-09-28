<?php
/**
 * Plugin Name: TieneLore Publish API
 * Description: API REST propia (/wp-json/tienelore/v1/) para publicar artículos desde un script externo (Claude Code), con el mismo patrón que usa PlotTwist. Crear/editar/publicar/borrar posts va por aquí; subir imágenes y asignar categoría/autor/etiquetas va por la REST API estándar de WordPress con Application Passwords (ver pantalla de ajustes).
 * Version: 1.0.0
 * Author: TieneLore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TIENELORE_API_OPTION', 'tienelore_api_key' );

class TieneLore_Publish_API {

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'admin_menu', array( $this, 'register_settings_page' ) );
	}

	public static function on_activate() {
		if ( ! get_option( TIENELORE_API_OPTION ) ) {
			update_option( TIENELORE_API_OPTION, wp_generate_password( 40, false, false ) );
		}
	}

	/* ---------------------------------------------------------------
	 * Pantalla de ajustes: ver/regenerar la clave, y recordatorio de
	 * que además hace falta una Application Password de WordPress.
	 * ------------------------------------------------------------- */

	public function register_settings_page() {
		add_options_page(
			'TieneLore Publish API',
			'TieneLore Publish API',
			'manage_options',
			'tienelore-publish-api',
			array( $this, 'render_settings_page' )
		);
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( isset( $_POST['tienelore_regenerate'] ) && check_admin_referer( 'tienelore_regenerate_key' ) ) {
			update_option( TIENELORE_API_OPTION, wp_generate_password( 40, false, false ) );
			echo '<div class="updated"><p>Clave regenerada. Actualiza config/settings.json en el script de publicación con la nueva clave.</p></div>';
		}
		$key = get_option( TIENELORE_API_OPTION );
		if ( ! $key ) {
			$key = wp_generate_password( 40, false, false );
			update_option( TIENELORE_API_OPTION, $key );
		}
		?>
		<div class="wrap">
			<h1>TieneLore Publish API</h1>
			<p>Esta clave autentica las peticiones a <code>/wp-json/tienelore/v1/</code> (crear/editar/publicar/borrar posts) desde el script de publicación, en la cabecera <code>X-Tienelore-Key</code>.</p>
			<table class="form-table">
				<tr>
					<th>API Key</th>
					<td><code style="user-select:all;"><?php echo esc_html( $key ); ?></code></td>
				</tr>
				<tr>
					<th>Endpoint base</th>
					<td><code><?php echo esc_html( rest_url( 'tienelore/v1' ) ); ?></code></td>
				</tr>
			</table>
			<form method="post">
				<?php wp_nonce_field( 'tienelore_regenerate_key' ); ?>
				<button type="submit" name="tienelore_regenerate" class="button">Regenerar clave</button>
			</form>
			<hr>
			<h2>Además hace falta una Application Password</h2>
			<p>Esta clave solo cubre crear/editar/publicar/borrar el post en sí. Subir imágenes y asignar categoría, autor o etiquetas usa la REST API estándar de WordPress (<code>/wp-json/wp/v2/</code>), autenticada aparte con una <strong>Application Password</strong>:</p>
			<ol>
				<li>Usuarios → Tu perfil (del usuario que va a publicar) → sección "Contraseñas de aplicación"</li>
				<li>Nombre: <code>tienelore-claude</code>, pulsar "Añadir nueva contraseña de aplicación"</li>
				<li>Copiar la contraseña generada (con espacios) — WordPress solo la enseña una vez</li>
			</ol>
			<p>Ambas credenciales (esta clave y la Application Password) van en <code>config/settings.json</code> del script — ver <code>config/settings.example.json</code> en el repo.</p>
		</div>
		<?php
	}

	/* ---------------------------------------------------------------
	 * Rutas
	 * ------------------------------------------------------------- */

	public function register_routes() {
		register_rest_route(
			'tienelore/v1',
			'/posts',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_post' ),
				'permission_callback' => array( $this, 'check_key' ),
			)
		);

		register_rest_route(
			'tienelore/v1',
			'/posts/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_post' ),
					'permission_callback' => array( $this, 'check_key' ),
				),
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'update_post' ),
					'permission_callback' => array( $this, 'check_key' ),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_post' ),
					'permission_callback' => array( $this, 'check_key' ),
				),
			)
		);

		register_rest_route(
			'tienelore/v1',
			'/posts/(?P<id>\d+)/publish',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'publish_post' ),
				'permission_callback' => array( $this, 'check_key' ),
			)
		);

		register_rest_route(
			'tienelore/v1',
			'/media/from-url',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'media_from_url' ),
				'permission_callback' => array( $this, 'check_key' ),
			)
		);
	}

	/**
	 * Auth por clave compartida en la cabecera X-Tienelore-Key — mismo
	 * patrón que X-Plottwist-Key en PlotTwist. Comparación en tiempo
	 * constante (hash_equals) para no filtrar la clave por timing.
	 */
	public function check_key( WP_REST_Request $request ) {
		$key    = $request->get_header( 'x-tienelore-key' );
		$stored = get_option( TIENELORE_API_OPTION );
		return $stored && $key && hash_equals( (string) $stored, (string) $key );
	}

	/* ---------------------------------------------------------------
	 * Callbacks
	 * ------------------------------------------------------------- */

	public function create_post( WP_REST_Request $request ) {
		$title   = sanitize_text_field( (string) $request->get_param( 'title' ) );
		$content = (string) $request->get_param( 'content' );
		$excerpt = $request->get_param( 'excerpt' );
		$slug    = $request->get_param( 'slug' );

		if ( '' === $title || '' === $content ) {
			return new WP_Error( 'tienelore_missing_fields', 'title y content son obligatorios', array( 'status' => 400 ) );
		}

		$postarr = array(
			'post_title'   => $title,
			'post_content' => wp_kses_post( $content ),
			'post_status'  => 'draft',
			'post_type'    => 'post',
		);
		if ( $excerpt ) {
			$postarr['post_excerpt'] = sanitize_text_field( (string) $excerpt );
		}
		if ( $slug ) {
			$postarr['post_name'] = sanitize_title( (string) $slug );
		}

		$post_id = wp_insert_post( $postarr, true );
		if ( is_wp_error( $post_id ) ) {
			return new WP_Error( 'tienelore_insert_failed', $post_id->get_error_message(), array( 'status' => 500 ) );
		}

		return rest_ensure_response( $this->post_response( $post_id ) );
	}

	public function get_post( WP_REST_Request $request ) {
		$post = get_post( (int) $request['id'] );
		if ( ! $post ) {
			return new WP_Error( 'tienelore_not_found', 'Post no encontrado', array( 'status' => 404 ) );
		}
		return rest_ensure_response( $this->post_response( $post->ID ) );
	}

	/**
	 * PATCH parcial de verdad: solo toca los campos presentes en el
	 * cuerpo. Si "status" no viene explícito, no se toca — para
	 * cambiar de estado usar POST /posts/{id}/publish, o mandar
	 * "status" explícitamente aquí.
	 */
	public function update_post( WP_REST_Request $request ) {
		$post_id = (int) $request['id'];
		$post    = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error( 'tienelore_not_found', 'Post no encontrado', array( 'status' => 404 ) );
		}

		$body    = $request->get_json_params();
		$postarr = array( 'ID' => $post_id );

		if ( isset( $body['title'] ) ) {
			$postarr['post_title'] = sanitize_text_field( (string) $body['title'] );
		}
		if ( isset( $body['content'] ) ) {
			$postarr['post_content'] = wp_kses_post( (string) $body['content'] );
		}
		if ( isset( $body['excerpt'] ) ) {
			$postarr['post_excerpt'] = sanitize_text_field( (string) $body['excerpt'] );
		}
		if ( isset( $body['slug'] ) ) {
			$postarr['post_name'] = sanitize_title( (string) $body['slug'] );
		}
		if ( isset( $body['status'] ) ) {
			$postarr['post_status'] = sanitize_key( (string) $body['status'] );
		}

		if ( count( $postarr ) === 1 ) {
			// Nada que actualizar además del ID.
			return rest_ensure_response( $this->post_response( $post_id ) );
		}

		$result = wp_update_post( $postarr, true );
		if ( is_wp_error( $result ) ) {
			return new WP_Error( 'tienelore_update_failed', $result->get_error_message(), array( 'status' => 500 ) );
		}

		return rest_ensure_response( $this->post_response( $post_id ) );
	}

	public function publish_post( WP_REST_Request $request ) {
		$post_id = (int) $request['id'];
		$post    = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error( 'tienelore_not_found', 'Post no encontrado', array( 'status' => 404 ) );
		}

		$result = wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'publish',
			),
			true
		);
		if ( is_wp_error( $result ) ) {
			return new WP_Error( 'tienelore_publish_failed', $result->get_error_message(), array( 'status' => 500 ) );
		}

		return rest_ensure_response( $this->post_response( $post_id ) );
	}

	public function delete_post( WP_REST_Request $request ) {
		$post_id = (int) $request['id'];
		if ( ! get_post( $post_id ) ) {
			return new WP_Error( 'tienelore_not_found', 'Post no encontrado', array( 'status' => 404 ) );
		}
		$deleted = wp_delete_post( $post_id, true );
		if ( ! $deleted ) {
			return new WP_Error( 'tienelore_delete_failed', 'No se pudo borrar el post', array( 'status' => 500 ) );
		}
		return rest_ensure_response(
			array(
				'deleted' => true,
				'id'      => $post_id,
			)
		);
	}

	/**
	 * Descarga una imagen por URL y la adjunta a la biblioteca de
	 * medios (y, si se pasa post_id, como imagen destacada). Existe
	 * porque la REST API estándar de WordPress no soporta "sideload
	 * desde URL" — solo subida de bytes ya en el servidor.
	 */
	public function media_from_url( WP_REST_Request $request ) {
		$url     = esc_url_raw( (string) $request->get_param( 'url' ) );
		$post_id = (int) $request->get_param( 'post_id' );

		if ( '' === $url ) {
			return new WP_Error( 'tienelore_missing_url', 'url es obligatorio', array( 'status' => 400 ) );
		}

		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$media_id = media_sideload_image( $url, $post_id ?: 0, null, 'id' );
		if ( is_wp_error( $media_id ) ) {
			return new WP_Error( 'tienelore_sideload_failed', $media_id->get_error_message(), array( 'status' => 500 ) );
		}

		if ( $post_id ) {
			set_post_thumbnail( $post_id, $media_id );
		}

		return rest_ensure_response(
			array(
				'id'         => $media_id,
				'source_url' => wp_get_attachment_url( $media_id ),
			)
		);
	}

	private function post_response( int $post_id ): array {
		$post = get_post( $post_id );
		return array(
			'id'     => $post_id,
			'title'  => $post->post_title,
			'status' => $post->post_status,
			'link'   => get_permalink( $post_id ),
		);
	}
}

register_activation_hook( __FILE__, array( 'TieneLore_Publish_API', 'on_activate' ) );
new TieneLore_Publish_API();
