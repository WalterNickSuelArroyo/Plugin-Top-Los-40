<?php
/*
Plugin Name: Plugin Top 40
Plugin URI: www.prueba.com
Description: Plugin para realizar votaciones y ordenarlos en un top 40
Version: 0.0.7
*/

// Configuración del intervalo de semanas (modo prueba o producción)
define('TOP40_TEST_MODE', false);
define('TOP40_TEST_INTERVAL', 180);
define('TOP40_PROD_INTERVAL', 604800);

function top40_enqueue_assets()
{
    // Solo cargar en páginas que contengan el shortcode
    if (is_singular() && has_shortcode(get_post()->post_content, 'top40')) {
        wp_enqueue_style(
            'top40-style',
            plugin_dir_url(__FILE__) . 'assets/css/style.css',
            array(),
            '0.0.7'
        );

        wp_enqueue_script(
            'top40-script',
            plugin_dir_url(__FILE__) . 'assets/js/script.js',
            array(),
            '0.0.7',
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'top40_enqueue_assets');

function top40_enqueue_assets_always()
{
    wp_enqueue_style(
        'top40-style',
        plugin_dir_url(__FILE__) . 'assets/css/style.css',
        array(),
        '0.0.7'
    );

    wp_enqueue_script(
        'top40-script',
        plugin_dir_url(__FILE__) . 'assets/js/script.js',
        array(),
        '0.0.7',
        true
    );
}

function Activar()
{
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $tabla_listas = $wpdb->prefix . 'top40_listas';
    $tabla_canciones = $wpdb->prefix . 'top40_canciones';
    $tabla_votos = $wpdb->prefix . 'top40_votos';
    $tabla_ranking = $wpdb->prefix . 'top40_ranking';

    $sql = "
        CREATE TABLE $tabla_listas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(255) NOT NULL
        ) $charset_collate;

        CREATE TABLE $tabla_canciones (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lista_id INT NOT NULL,
            titulo VARCHAR(255) NOT NULL,
            autor VARCHAR(255),
            youtube_url VARCHAR(255) NOT NULL,
            cover_url VARCHAR(255),
            votos INT DEFAULT 0,
            orden INT DEFAULT 0,
            fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (lista_id) REFERENCES $tabla_listas(id) ON DELETE CASCADE
        ) $charset_collate;

        CREATE TABLE $tabla_votos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            cancion_id INT NOT NULL,
            ip VARCHAR(100),
            UNIQUE KEY unique_vote (cancion_id, ip)
        ) $charset_collate;

        CREATE TABLE $tabla_ranking (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lista_id INT NOT NULL,
            cancion_id INT NOT NULL,
            semana_fecha DATETIME NOT NULL,
            posicion INT NOT NULL,
            FOREIGN KEY (lista_id) REFERENCES $tabla_listas(id) ON DELETE CASCADE,
            FOREIGN KEY (cancion_id) REFERENCES $tabla_canciones(id) ON DELETE CASCADE
        ) $charset_collate;
    ";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    if (!wp_next_scheduled('top40_actualizar_semanas')) {
        wp_schedule_event(time(), 'weekly', 'top40_actualizar_semanas');
    }
}

function Desactivar()
{
    wp_clear_scheduled_hook('top40_actualizar_semanas');
}

register_activation_hook(__FILE__, 'Activar');
register_deactivation_hook(__FILE__, 'Desactivar');

add_filter('cron_schedules', function ($schedules) {
    // Solo añadir el intervalo de prueba si estamos en modo prueba
    if (TOP40_TEST_MODE) {
        $schedules['top40_test_interval'] = array(
            'interval' => TOP40_TEST_INTERVAL,
            'display'  => __('Cada 3 minutos (modo prueba)')
        );
    }
    return $schedules;
});

// Función para actualizar el contador de semanas
add_action('top40_actualizar_semanas', 'top40_actualizar_semanas_callback');

function top40_actualizar_semanas_callback()
{
    global $wpdb;
    $tabla_canciones = $wpdb->prefix . 'top40_canciones';
    $tabla_ranking = $wpdb->prefix . 'top40_ranking';

    // Obtener todas las listas activas
    $listas = $wpdb->get_results("SELECT id FROM {$wpdb->prefix}top40_listas");

    foreach ($listas as $lista) {
        // Registrar nueva semana para la lista
        top40_registrar_semana($lista->id);
    }
}

// Admin
add_action('admin_menu', 'crearMenu');

// Cargar estilos CSS en la administración
function top40_admin_enqueue_styles($hook) {
    // Solo cargar en las páginas del plugin
    if (strpos($hook, 'top40') !== false) {
        wp_enqueue_style(
            'top40-admin-style',
            plugin_dir_url(__FILE__) . 'assets/css/style.css',
            array(),
            '0.0.7'
        );
        
        wp_enqueue_script(
            'top40-admin-script',
            plugin_dir_url(__FILE__) . 'assets/js/script.js',
            array(),
            '0.0.7',
            true
        );
    }
}
add_action('admin_enqueue_scripts', 'top40_admin_enqueue_styles');

add_action('wp_ajax_top40_guardar_orden', function () {
    check_ajax_referer('top40_orden_nonce');
    global $wpdb;
    $tabla = $wpdb->prefix . 'top40_canciones';
    if (!isset($_POST['orden']) || !is_array($_POST['orden'])) {
        wp_send_json_error('Datos inválidos');
    }
    foreach ($_POST['orden'] as $item) {
        $id = intval($item['id']);
        $pos = intval($item['posicion']);
        $wpdb->update($tabla, ['orden' => $pos], ['id' => $id]);
    }
    wp_send_json_success('Orden actualizado');
});

function crearMenu()
{
    add_menu_page(
        'Top 40 músicas',
        'Top 40',
        'manage_options',
        'top40_menu',
        'mostrarListasTop40',
        plugin_dir_url(__FILE__) . 'admin/img/icon-plugin.svg',
        1
    );

    add_submenu_page(
        null,
        'Editar Lista',
        'Editar Lista',
        'manage_options',
        'top40_lista',
        'mostrarCancionesLista'
    );
}

function mostrarListasTop40()
{
    include plugin_dir_path(__FILE__) . 'admin/listas.php';
}

function mostrarCancionesLista()
{
    include plugin_dir_path(__FILE__) . 'admin/formulario.php';
}

// Shortcode: [top40 lista="1"]
add_shortcode('top40', 'mostrarTop40');

function mostrarTop40($atts)
{
    // Desactivar caché temporal
    nocache_headers();
    wp_suspend_cache_addition(true);

    global $wpdb;
    $tabla_canciones = $wpdb->prefix . 'top40_canciones';
    $tabla_votos = $wpdb->prefix . 'top40_votos';
    $tabla_ranking = $wpdb->prefix . 'top40_ranking';

    $atts = shortcode_atts(['lista' => 0], $atts);
    $lista_id = intval($atts['lista']);
    if (!$lista_id) return "<p>No se ha especificado una lista válida.</p>";

    $ip = $_SERVER['REMOTE_ADDR'];

    // Array para almacenar canciones ya votadas
    $votadas = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['votar_id'])) {
        $id = intval($_POST['votar_id']);
        $ya_voto = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_votos WHERE cancion_id = %d AND ip = %s",
            $id,
            $ip
        ));

        if ($ya_voto == 0) {
            $wpdb->insert($tabla_votos, ['cancion_id' => $id, 'ip' => $ip]);
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_canciones SET votos = votos + 1 WHERE id = %d",
                $id
            ));
            $votadas[$id] = "¡Gracias por tu voto!";
        } else {
            $votadas[$id] = "Ya votaste por esta canción.";
        }
    }

    $canciones = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $tabla_canciones WHERE lista_id = %d ORDER BY votos DESC LIMIT 40",
            $lista_id
        )
    );

    ob_start();
?>
<div id="top40-container" class="top40-contenedor">
    <?php $pos = 1; ?>
    <?php foreach ($canciones as $cancion): ?>
    <?php
            preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^\&\?\/]+)/', $cancion->youtube_url, $match);
            $video_id = $match[1] ?? null;

            $semanas = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_ranking WHERE lista_id = %d AND cancion_id = %d",
                $lista_id,
                $cancion->id
            ));

            $mejor_posicion = $wpdb->get_var($wpdb->prepare(
                "SELECT MIN(posicion) FROM $tabla_ranking WHERE lista_id = %d AND cancion_id = %d",
                $lista_id,
                $cancion->id
            ));

            $anterior_posicion = $wpdb->get_var($wpdb->prepare(
                "SELECT posicion FROM $tabla_ranking
                 WHERE lista_id = %d AND cancion_id = %d
                 ORDER BY semana_fecha DESC
                 LIMIT 1 OFFSET 1",
                $lista_id,
                $cancion->id
            ));


            // Calcular tendencia - SOLO mostrar después del primer intervalo
            $tendencia = '';
            $mostrar_tendencia = false;

            // Verificar si ha pasado al menos un intervalo para esta lista
            $primera_semana = $wpdb->get_var($wpdb->prepare(
                "SELECT MIN(semana_fecha) FROM {$wpdb->prefix}top40_ranking WHERE lista_id = %d",
                $lista_id
            ));

            if ($primera_semana) {
                $intervalo = TOP40_TEST_MODE ? TOP40_TEST_INTERVAL : TOP40_PROD_INTERVAL;
                $ahora = current_time('timestamp');
                $primera_semana_timestamp = strtotime($primera_semana);

                if (($ahora - $primera_semana_timestamp) >= $intervalo) {
                    $mostrar_tendencia = true;
                    $tendencia = 'igual'; // Valor por defecto

                    if ($anterior_posicion) {
                        if ($pos < $anterior_posicion) {
                            $tendencia = 'sube';
                        } elseif ($pos > $anterior_posicion) {
                            $tendencia = 'baja';
                        }
                    }
                }
            }
            ?>
    <?php
            $clase_posicion = $pos === 1 ? 'posicion-1' : '';
            ?>

    <div id="cancion-<?= $cancion->id ?>" class="top40-item <?= $clase_posicion ?>">
        <div class="top40-header">
            <div class="top40-posicion">
                #<?= $pos ?>
                <?php if ($mostrar_tendencia && $tendencia): ?>
                <span class="tendencia <?= $tendencia ?>"><?= ucfirst($tendencia) ?></span>
                <?php endif; ?>
            </div>
            <?php if ($cancion->cover_url): ?>
            <div class="top40-cover">
                <img src="<?= esc_url($cancion->cover_url) ?>">
            </div>
            <?php endif; ?>
            <div class="top40-info">
                <div class="top40-info-row">
                    <div class="top40-titulo">
                        <?= esc_html($cancion->titulo) ?>
                        <div class="top40-autor"><?= esc_html($cancion->autor) ?></div>
                    </div>
                    <div class="top40-voto">
                        <form method="POST" action="#cancion-<?= $cancion->id ?>">
                            <input type="hidden" name="votar_id" value="<?= $cancion->id ?>">
                            <button type="submit" class="<?= isset($votadas[$cancion->id]) ? 'votado' : '' ?>">
                                <?= isset($votadas[$cancion->id]) ? '✓ Votado' : 'VOTAR' ?>
                            </button>
                        </form>
                        <small><?= $cancion->votos ?> votos</small>
                    </div>
                </div>
                <?php if (isset($votadas[$cancion->id])): ?>
                <div class="top40-mensaje"><?= esc_html($votadas[$cancion->id]) ?></div>
                <?php endif; ?>
            </div>
            <div class="top40-arrow">
                <svg viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </div>
        </div>
        <div class="top40-extra" style="display:none;">
            <div class="top40-estadisticas">
                <div class="top40-estadistica">
                    <div class="top40-estadistica-dato">
                        <img src="https://los40.com/pf/resources/dist/img/ico-cal-cl24.svg?d=818&mxId=00000000"
                            alt="Semanas">
                        <span><?= intval($semanas) ?></span>
                    </div>
                    <strong>Semanas en listas</strong>
                </div>
                <div class="top40-estadistica">
                    <div class="top40-estadistica-dato">
                        <img src="https://los40.com/pf/resources/dist/img/ico-mpos-cl24.svg?d=818&mxId=00000000"
                            alt="Mejor posición">
                        <span><?= $mejor_posicion ? intval($mejor_posicion) : '-' ?></span>
                    </div>
                    <strong>Mejor posición</strong>
                </div>
                <div class="top40-estadistica">
                    <div class="top40-estadistica-dato">
                        <img src="https://los40.com/pf/resources/dist/img/ico-apos-cl24.svg?d=818&mxId=00000000"
                            alt="Anterior posición">
                        <span><?= $anterior_posicion ? intval($anterior_posicion) : '-' ?></span>
                    </div>
                    <strong>Anterior posición</strong>
                </div>
            </div>
            <?php if ($video_id): ?>
            <div class="top40-video">
                <iframe src="https://www.youtube.com/embed/<?= esc_attr($video_id) ?>" allowfullscreen></iframe>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php $pos++; ?>
    <?php endforeach; ?>
</div>
<?php
    return ob_get_clean();
}


function top40_registrar_semana($lista_id)
{
    global $wpdb;
    $tabla_canciones = $wpdb->prefix . 'top40_canciones';
    $tabla_ranking = $wpdb->prefix . 'top40_ranking';

    $canciones = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id FROM $tabla_canciones WHERE lista_id = %d ORDER BY votos DESC",
            $lista_id
        )
    );

    $pos = 1;
    $semana = current_time('mysql'); // Usa la fecha/hora actual

    foreach ($canciones as $c) {
        $wpdb->insert($tabla_ranking, [
            'lista_id' => $lista_id,
            'cancion_id' => $c->id,
            'semana_fecha' => $semana,
            'posicion' => $pos
        ]);
        $pos++;
    }
}

// Función para forzar la actualización manual (opcional)
function top40_forzar_actualizacion_semanas()
{
    if (isset($_GET['top40_force_update']) && current_user_can('manage_options')) {
        top40_actualizar_semanas_callback();
        add_action('admin_notices', function () {
            echo "<div class='updated'><p>Se ha forzado la actualización de semanas.</p></div>";
        });
    }
}

function top40_es_primera_semana($lista_id)
{
    global $wpdb;
    $tabla_ranking = $wpdb->prefix . 'top40_ranking';

    $semanas = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT semana_fecha) FROM $tabla_ranking WHERE lista_id = %d",
        $lista_id
    ));

    return $semanas <= 1;
}


add_action('admin_notices', 'top40_forzar_actualizacion_semanas');