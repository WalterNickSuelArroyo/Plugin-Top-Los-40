<?php
wp_enqueue_media();
wp_enqueue_script('jquery-ui-sortable');

global $wpdb;
$tabla_canciones = $wpdb->prefix . 'top40_canciones';
$tabla_listas = $wpdb->prefix . 'top40_listas';
$tabla_ranking = $wpdb->prefix . 'top40_ranking';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='error'><p>No se ha especificado una lista válida.</p></div>";
    return;
}

$lista_id = intval($_GET['id']);
$lista = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla_listas WHERE id = %d", $lista_id));
if (!$lista) {
    echo "<div class='error'><p>La lista no existe.</p></div>";
    return;
}

// Guardar nueva canción o actualizar existente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['titulo'])) {
    $data = [
        'lista_id'    => $lista_id,
        'titulo'      => sanitize_text_field($_POST['titulo']),
        'autor'       => sanitize_text_field($_POST['autor']),
        'youtube_url' => esc_url_raw($_POST['youtube_url']),
        'cover_url'   => esc_url_raw($_POST['cover_url']),
    ];
    
    // Si estamos editando una canción existente y se proporciona el campo votos
    if (!empty($_POST['cancion_id']) && isset($_POST['votos'])) {
        $data['votos'] = intval($_POST['votos']);
    }
    
    // Campos manuales adicionales para edición
    if (!empty($_POST['cancion_id'])) {
        if (isset($_POST['semanas_manual']) && $_POST['semanas_manual'] !== '') {
            $data['semanas_manual'] = intval($_POST['semanas_manual']);
        }
        if (isset($_POST['mejor_posicion_manual']) && $_POST['mejor_posicion_manual'] !== '') {
            $data['mejor_posicion_manual'] = intval($_POST['mejor_posicion_manual']);
        }
        if (isset($_POST['posicion_anterior_manual']) && $_POST['posicion_anterior_manual'] !== '') {
            $data['posicion_anterior_manual'] = intval($_POST['posicion_anterior_manual']);
        }
    }

    if (!empty($_POST['cancion_id'])) {
        $id = intval($_POST['cancion_id']);
        $wpdb->update($tabla_canciones, $data, ['id' => $id]);
        echo "<div class='updated'><p>Canción actualizada correctamente.</p></div>";
    } else {
        $max_orden = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(orden) FROM $tabla_canciones WHERE lista_id = %d",
            $lista_id
        ));
        $data['orden'] = ($max_orden + 1);
        $wpdb->insert($tabla_canciones, $data);
        echo "<div class='updated'><p>Canción añadida correctamente.</p></div>";
    }
}

// Eliminar canción
if (isset($_POST['eliminar_id'])) {
    $id = intval($_POST['eliminar_id']);
    $wpdb->delete($tabla_canciones, ['id' => $id]);
    echo "<div class='updated'><p>Canción eliminada correctamente.</p></div>";
}

// Actualización rápida de votos
if (isset($_POST['actualizar_votos']) && isset($_POST['cancion_id']) && isset($_POST['nuevos_votos'])) {
    $id = intval($_POST['cancion_id']);
    $nuevos_votos = intval($_POST['nuevos_votos']);
    $wpdb->update(
        $tabla_canciones,
        ['votos' => $nuevos_votos],
        ['id' => $id, 'lista_id' => $lista_id]
    );
    echo "<div class='updated'><p>Votos actualizados correctamente.</p></div>";
}

// Actualización rápida de estadísticas
if (isset($_POST['actualizar_estadisticas']) && isset($_POST['cancion_id'])) {
    $id = intval($_POST['cancion_id']);
    $datos_actualizacion = [];
    
    if (isset($_POST['nuevas_semanas']) && $_POST['nuevas_semanas'] !== '') {
        $datos_actualizacion['semanas_manual'] = intval($_POST['nuevas_semanas']);
    }
    if (isset($_POST['nueva_mejor_posicion']) && $_POST['nueva_mejor_posicion'] !== '') {
        $datos_actualizacion['mejor_posicion_manual'] = intval($_POST['nueva_mejor_posicion']);
    }
    if (isset($_POST['nueva_posicion_anterior']) && $_POST['nueva_posicion_anterior'] !== '') {
        $datos_actualizacion['posicion_anterior_manual'] = intval($_POST['nueva_posicion_anterior']);
    }
    
    if (!empty($datos_actualizacion)) {
        $wpdb->update(
            $tabla_canciones,
            $datos_actualizacion,
            ['id' => $id, 'lista_id' => $lista_id]
        );
        echo "<div class='updated'><p>Estadísticas actualizadas correctamente.</p></div>";
    }
}

// Limpiar valores manuales (volver a automático)
if (isset($_POST['limpiar_manuales']) && isset($_POST['cancion_id'])) {
    $id = intval($_POST['cancion_id']);
    $wpdb->update(
        $tabla_canciones,
        [
            'semanas_manual' => null,
            'mejor_posicion_manual' => null,
            'posicion_anterior_manual' => null
        ],
        ['id' => $id, 'lista_id' => $lista_id]
    );
    echo "<div class='updated'><p>Valores manuales eliminados. Ahora se calcularán automáticamente.</p></div>";
}

// Si se está editando
$edit_cancion = null;
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $edit_id = intval($_GET['editar']);
    $edit_cancion = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $tabla_canciones WHERE id = %d AND lista_id = %d",
        $edit_id,
        $lista_id
    ));
}
?>

<div class="wrap">
    <h1>Lista: <?= esc_html($lista->nombre); ?></h1>

    <h2><?= $edit_cancion ? 'Editar Canción' : 'Agregar Nueva Canción' ?></h2>
    <form method="POST">
        <?php if ($edit_cancion): ?>
        <input type="hidden" name="cancion_id" value="<?= $edit_cancion->id; ?>">
        <?php endif; ?>
        <table class="form-table">
            <tr>
                <th><label>Título</label></th>
                <td><input type="text" name="titulo" required class="regular-text"
                        value="<?= esc_attr($edit_cancion->titulo ?? '') ?>"></td>
            </tr>
            <tr>
                <th><label>Intérprete</label></th>
                <td><input type="text" name="autor" class="regular-text"
                        value="<?= esc_attr($edit_cancion->autor ?? '') ?>"></td>
            </tr>
            <tr>
                <th><label>URL de YouTube</label></th>
                <td><input type="url" name="youtube_url" required class="regular-text"
                        value="<?= esc_url($edit_cancion->youtube_url ?? '') ?>"></td>
            </tr>
            <tr>
                <th><label>Cover (imagen)</label></th>
                <td>
                    <input type="text" name="cover_url" id="cover_url" class="regular-text" readonly
                        value="<?= esc_url($edit_cancion->cover_url ?? '') ?>">
                    <button type="button" class="button" id="seleccionar_cover">Seleccionar imagen</button>
                </td>
            </tr>
            <?php if ($edit_cancion): ?>
            <tr>
                <th><label>Votos</label></th>
                <td>
                    <input type="number" name="votos" min="0" class="regular-text"
                        value="<?= esc_attr($edit_cancion->votos ?? 0) ?>">
                    <p class="description">
                        <strong>Nota:</strong> Puedes editar manualmente la cantidad de votos para esta canción. 
                        Los cambios se reflejarán inmediatamente en el ranking del Top 40.
                    </p>
                </td>
            </tr>
            <tr>
                <th><label>Semanas en Lista (Manual)</label></th>
                <td>
                    <input type="number" name="semanas_manual" min="0" class="regular-text"
                        value="<?= esc_attr(top40_get_property($edit_cancion, 'semanas_manual', '')) ?>" placeholder="Dejar vacío para cálculo automático">
                    <p class="description">
                        Si especificas un valor, se usará este en lugar del cálculo automático basado en el historial.
                    </p>
                </td>
            </tr>
            <tr>
                <th><label>Mejor Posición (Manual)</label></th>
                <td>
                    <input type="number" name="mejor_posicion_manual" min="1" max="40" class="regular-text"
                        value="<?= esc_attr(top40_get_property($edit_cancion, 'mejor_posicion_manual', '')) ?>" placeholder="Dejar vacío para cálculo automático">
                    <p class="description">
                        Especifica la mejor posición que ha alcanzado esta canción (1-40).
                    </p>
                </td>
            </tr>
            <tr>
                <th><label>Posición Anterior (Manual)</label></th>
                <td>
                    <input type="number" name="posicion_anterior_manual" min="1" max="40" class="regular-text"
                        value="<?= esc_attr(top40_get_property($edit_cancion, 'posicion_anterior_manual', '')) ?>" placeholder="Dejar vacío para cálculo automático">
                    <p class="description">
                        Especifica la posición de la semana anterior.
                    </p>
                </td>
            </tr>
            <?php endif; ?>
        </table>
        <button type="submit" class="button button-primary">
            <?= $edit_cancion ? 'Actualizar Canción' : 'Guardar Canción' ?>
        </button>
        <?php if ($edit_cancion): ?>
        <a href="<?= admin_url('admin.php?page=top40_lista&id=' . $lista_id); ?>" class="button">Cancelar Edición</a>
        <?php endif; ?>
    </form>

    <hr>

    <h2>Canciones de esta Lista</h2>
    <?php
    $canciones = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $tabla_canciones WHERE lista_id = %d ORDER BY votos DESC, orden ASC", $lista_id)
    );

    if ($canciones):
        // Agrupar canciones por votos
        $grupos = [];
        foreach ($canciones as $c) {
            $grupos[$c->votos][] = $c;
        }
        krsort($grupos);
    ?>
    <table id="sortable-canciones" class="widefat fixed striped">
        <thead>
            <tr>
                <th></th>
                <th>Título</th>
                <th>Intérprete</th>
                <th>Votos</th>
                <th>Semanas en Lista</th>
                <th>Mejor Posición</th>
                <th>Posición Anterior</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <?php foreach ($grupos as $votos => $lista): ?>
        <tbody data-votos="<?= $votos; ?>">
            <?php foreach ($lista as $c): ?>
            <?php
                        // Calcular estadísticas (usar valores manuales si están disponibles)
                        $semanas_manual = top40_get_property($c, 'semanas_manual');
                        if ($semanas_manual !== null) {
                            $semanas = $semanas_manual;
                        } else {
                            $semanas = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(DISTINCT semana_fecha) FROM $tabla_ranking WHERE lista_id = %d AND cancion_id = %d",
                                $lista_id,
                                $c->id
                            ));
                        }
                        
                        $mejor_posicion_manual = top40_get_property($c, 'mejor_posicion_manual');
                        if ($mejor_posicion_manual !== null) {
                            $mejor = $mejor_posicion_manual;
                        } else {
                            $mejor = $wpdb->get_var($wpdb->prepare(
                                "SELECT MIN(posicion) FROM $tabla_ranking WHERE lista_id = %d AND cancion_id = %d",
                                $lista_id,
                                $c->id
                            ));
                        }
                        
                        $posicion_anterior_manual = top40_get_property($c, 'posicion_anterior_manual');
                        if ($posicion_anterior_manual !== null) {
                            $anterior = $posicion_anterior_manual;
                        } else {
                            $anterior = $wpdb->get_var($wpdb->prepare(
                                "SELECT posicion FROM $tabla_ranking WHERE lista_id = %d AND cancion_id = %d ORDER BY semana_fecha DESC LIMIT 1",
                                $lista_id,
                                $c->id
                            ));
                        }
                        ?>
            <tr data-id="<?= $c->id; ?>">
                <td class="handle" style="cursor:move;">☰</td>
                <td><?= esc_html($c->titulo); ?></td>
                <td><?= esc_html($c->autor); ?></td>
                <td>
                    <form method="POST" class="top40-votos-form">
                        <input type="hidden" name="cancion_id" value="<?= $c->id; ?>">
                        <input type="number" name="nuevos_votos" value="<?= $c->votos; ?>" min="0">
                        <button type="submit" name="actualizar_votos" class="button button-small" title="Actualizar votos">💾</button>
                    </form>
                </td>
                <td>
                    <form method="POST" class="top40-votos-form">
                        <input type="hidden" name="cancion_id" value="<?= $c->id; ?>">
                        <input type="number" name="nuevas_semanas" value="<?= $semanas_manual ?? $semanas; ?>" min="0" title="Semanas en lista">
                        <button type="submit" name="actualizar_estadisticas" class="button button-small" title="Actualizar semanas">💾</button>
                    </form>
                    <?php if ($semanas_manual !== null): ?>
                    <small style="color: #0073aa;" title="Valor manual">📝</small>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="POST" class="top40-votos-form">
                        <input type="hidden" name="cancion_id" value="<?= $c->id; ?>">
                        <input type="number" name="nueva_mejor_posicion" value="<?= $mejor_posicion_manual ?? $mejor; ?>" min="1" max="40" title="Mejor posición">
                        <button type="submit" name="actualizar_estadisticas" class="button button-small" title="Actualizar mejor posición">💾</button>
                    </form>
                    <?php if ($mejor_posicion_manual !== null): ?>
                    <small style="color: #0073aa;" title="Valor manual">📝</small>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="POST" class="top40-votos-form">
                        <input type="hidden" name="cancion_id" value="<?= $c->id; ?>">
                        <input type="number" name="nueva_posicion_anterior" value="<?= $posicion_anterior_manual ?? $anterior; ?>" min="1" max="40" title="Posición anterior">
                        <button type="submit" name="actualizar_estadisticas" class="button button-small" title="Actualizar posición anterior">💾</button>
                    </form>
                    <?php if ($posicion_anterior_manual !== null): ?>
                    <small style="color: #0073aa;" title="Valor manual">📝</small>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="<?= admin_url('admin.php?page=top40_lista&id=' . $lista_id . '&editar=' . $c->id); ?>"
                        class="button button-small">Editar</a>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar esta canción?');">
                        <input type="hidden" name="eliminar_id" value="<?= $c->id; ?>">
                        <button type="submit" class="button button-small">Eliminar</button>
                    </form>
                    <?php if ($semanas_manual !== null || $mejor_posicion_manual !== null || $posicion_anterior_manual !== null): ?>
                    <br>
                    <form method="POST" style="display:inline; margin-top: 5px;" onsubmit="return confirm('¿Limpiar todos los valores manuales y volver al cálculo automático?');">
                        <input type="hidden" name="cancion_id" value="<?= $c->id; ?>">
                        <button type="submit" name="limpiar_manuales" class="button button-small" style="background: #ffcc00;" title="Limpiar valores manuales">🔄 Auto</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <?php endforeach; ?>
    </table>
    <?php else: ?>
    <p>No hay canciones en esta lista aún.</p>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('seleccionar_cover');
    const input = document.getElementById('cover_url');
    btn.addEventListener('click', function() {
        const frame = wp.media({
            title: 'Seleccionar imagen',
            multiple: false,
            library: {
                type: 'image'
            },
            button: {
                text: 'Usar esta imagen'
            }
        });
        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            input.value = attachment.url;
        });
        frame.open();
    });

    jQuery("#sortable-canciones tbody").each(function() {
        jQuery(this).sortable({
            handle: ".handle",
            update: function(event, ui) {
                let orden = [];
                jQuery(this).find("tr").each(function(index) {
                    orden.push({
                        id: jQuery(this).data('id'),
                        posicion: index
                    });
                });
                jQuery.post(ajaxurl, {
                    action: 'top40_guardar_orden',
                    orden: orden,
                    lista_id: <?= $lista_id; ?>,
                    _ajax_nonce: '<?= wp_create_nonce("top40_orden_nonce"); ?>'
                }, function(response) {
                    console.log('Orden guardado');
                });
            }
        });
    });
});
</script>