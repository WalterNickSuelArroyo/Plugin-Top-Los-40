<?php
/*
Plugin Name: Plugin Top 40
Plugin URI: www.prueba.com
Description: Plugin para realizar votaciones y ordenarlos en un top 40
Version: 0.0.5
*/

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
            semana_fecha DATE NOT NULL,
            posicion INT NOT NULL,
            FOREIGN KEY (lista_id) REFERENCES $tabla_listas(id) ON DELETE CASCADE,
            FOREIGN KEY (cancion_id) REFERENCES $tabla_canciones(id) ON DELETE CASCADE
        ) $charset_collate;
    ";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function Desactivar() {}

register_activation_hook(__FILE__, 'Activar');
register_deactivation_hook(__FILE__, 'Desactivar');

// Admin
add_action('admin_menu', 'crearMenu');

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
    global $wpdb;
    $tabla_canciones = $wpdb->prefix . 'top40_canciones';
    $tabla_votos = $wpdb->prefix . 'top40_votos';
    $tabla_ranking = $wpdb->prefix . 'top40_ranking';

    $atts = shortcode_atts(['lista' => 0], $atts);
    $lista_id = intval($atts['lista']);
    if (!$lista_id) return "<p>No se ha especificado una lista válida.</p>";

    $ip = $_SERVER['REMOTE_ADDR'];

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
            echo "<p class='top40-mensaje'>¡Gracias por tu voto!</p>";
        } else {
            echo "<p class='top40-mensaje'>Ya votaste por esta canción.</p>";
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
<style>
.top40-contenedor {
    width: 80%;
    margin: auto;
}

.top40-item {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin-bottom: 15px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.top40-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-right: 10px;
}

.top40-posicion {
    background: #222;
    color: white;
    font-weight: bold;
    padding: 50px 0px;
    font-size: 22px;
    width: 90px;
    text-align: center;
}

.top40-cover {
    display: flex;
}

.top40-cover img {
    width: 100px;
    height: 133px;
    object-fit: cover;
}

.top40-info {
    flex-grow: 1;
    padding: 15px;
    display: flex;
    align-items: center;
    gap: 20px;
}

.top40-titulo {
    font-size: 18px;
    flex: 1;
}

.top40-autor {
    font-size: 14px;
    color: #555;
}

.top40-voto form {
    display: flex;
    align-items: center;
}

.top40-voto button {
    background: red;
    color: white;
    padding: 8px 14px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.top40-arrow {
    cursor: pointer;
    transition: transform 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-left: 10px;
}

.top40-arrow svg {
    width: 24px;
    height: 24px;
    transition: transform 0.3s ease;
}

.top40-arrow.rotado svg {
    transform: rotate(180deg);
}

.top40-extra {
    display: none;
    justify-content: space-between;
    background: #f9f9f9;
    border-top: 1px solid #ddd;
    padding: 10px;
    flex-wrap: wrap;
}

.top40-estadisticas {
    flex: 1;
    min-width: 220px;
    display: flex;
    gap: 20px;
    font-size: 14px;
    padding-top: 30px;
}

.top40-estadistica {
    flex: 1;
    text-align: center;
}

.top40-estadistica strong {
    display: block;
    font-size: 13px;
    color: #333;
}

.top40-estadistica svg {
    width: 24px;
    height: 24px;
    margin: 5px auto;
    display: block;
    fill: #666;
}

.top40-estadistica span {
    display: block;
    font-size: 16px;
    font-weight: bold;
    color: #222;
}

.top40-video iframe {
    width: 100%;
    max-width: 360px;
    height: 200px;
    border: none;
    border-radius: 5px;
}

.top40-mensaje {
    background: #dff0d8;
    padding: 10px;
    border-left: 5px solid #3c763d;
    margin: 10px auto;
    width: 80%;
}

.top40-estadistica img.top40-icono {
    width: 32px;
    height: 32px;
    margin: 6px auto;
    display: block;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.top40-arrow').forEach(btn => {
        btn.addEventListener('click', function() {
            const extra = this.closest('.top40-item').querySelector('.top40-extra');
            extra.style.display = extra.style.display === 'flex' ? 'none' : 'flex';
            this.classList.toggle('rotado');
        });
    });
});
</script>

<div class="top40-contenedor">
    <h2>🎵 Top 40 Musical</h2>
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
                "SELECT posicion FROM $tabla_ranking WHERE lista_id = %d AND cancion_id = %d ORDER BY semana_fecha DESC LIMIT 1",
                $lista_id,
                $cancion->id
            ));
            ?>
    <div class="top40-item">
        <div class="top40-header">
            <div class="top40-posicion">#<?= $pos ?></div>
            <?php if ($cancion->cover_url): ?>
            <div class="top40-cover">
                <img src="<?= esc_url($cancion->cover_url) ?>">
            </div>
            <?php endif; ?>
            <div class="top40-info">
                <div class="top40-titulo">
                    <?= esc_html($cancion->titulo) ?>
                    <div class="top40-autor"><?= esc_html($cancion->autor) ?></div>
                </div>
                <div class="top40-voto">
                    <form method="POST">
                        <input type="hidden" name="votar_id" value="<?= $cancion->id ?>">
                        <button type="submit">Votar</button>
                    </form>
                    <small><?= $cancion->votos ?> votos</small>
                </div>
            </div>
            <div class="top40-arrow">
                <svg viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </div>
        </div>
        <div class="top40-extra">
            <div class="top40-estadisticas">
                <div class="top40-estadistica">
                    <strong>Semanas en listas</strong>
                    <img src="<?= plugin_dir_url(__FILE__) . 'admin/img/Calendar.svg' ?>" alt="icono"
                        class="top40-icono">
                    <span><?= intval($semanas) ?></span>
                </div>
                <div class="top40-estadistica">
                    <strong>Mejor posición</strong>
                    <img src="<?= plugin_dir_url(__FILE__) . 'admin/img/Trophy.svg' ?>" alt="icono" class="top40-icono">
                    <span><?= $mejor_posicion ? intval($mejor_posicion) : '-' ?></span>
                </div>
                <div class="top40-estadistica">
                    <strong>Anterior posición</strong>
                    <img src="<?= plugin_dir_url(__FILE__) . 'admin/img/Arrow.svg' ?>" alt="icono" class="top40-icono">
                    <span><?= $anterior_posicion ? intval($anterior_posicion) : '-' ?></span>
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
    $fecha = current_time('mysql');
    $semana = date('Y-m-d', strtotime($fecha));

    foreach ($canciones as $c) {
        $wpdb->insert($tabla_ranking, [
            'lista_id' => $lista_id,
            'cancion_id' => $c->id,
            'semana_fecha' => $semana,
            'posicion' => $pos
        ]);
        $pos++;
    }

    echo "<div class='updated'><p>Se registraron las posiciones de esta semana.</p></div>";
}