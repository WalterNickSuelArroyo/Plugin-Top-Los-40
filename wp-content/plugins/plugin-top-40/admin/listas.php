<?php
global $wpdb;
$tabla_listas = $wpdb->prefix . 'top40_listas';

// Crear nueva lista
if (isset($_POST['nueva_lista'])) {
    $nombre = sanitize_text_field($_POST['nombre_lista']);
    if (!empty($nombre)) {
        $wpdb->insert($tabla_listas, ['nombre' => $nombre]);
        echo "<div class='updated'><p>Lista creada correctamente.</p></div>";
    } else {
        echo "<div class='error'><p>El nombre de la lista no puede estar vacío.</p></div>";
    }
}

// Eliminar lista
if (isset($_POST['eliminar_lista_id'])) {
    $id = intval($_POST['eliminar_lista_id']);
    $wpdb->delete($tabla_listas, ['id' => $id]);
    echo "<div class='updated'><p>Lista eliminada correctamente.</p></div>";
}

// Obtener todas las listas
$listas = $wpdb->get_results("SELECT * FROM $tabla_listas");
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?= esc_html(get_admin_page_title()); ?></h1>

    <form method="POST" style="margin-top: 20px; margin-bottom: 30px;">
        <input type="text" name="nombre_lista" placeholder="Nombre de la nueva lista" required
            style="width: 300px; padding: 6px;">
        <button type="submit" name="nueva_lista" class="button button-primary">Agregar Lista</button>
    </form>

    <?php if ($listas): ?>
    <table class="widefat fixed striped">
        <thead>
            <tr>
                <th>Nombre de la Lista</th>
                <th>Shortcode</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($listas as $lista): ?>
            <tr>
                <td><?= esc_html($lista->nombre); ?></td>
                <td>
                    <code>[top40 lista="<?= intval($lista->id); ?>"]</code>
                </td>
                <td>
                    <a href="<?= esc_url(admin_url('admin.php?page=top40_lista&id=' . $lista->id)); ?>"
                        class="button button-secondary">Ver/Editar Lista</a>

                    <!-- Si quieres registrar semana manual, descomenta este enlace:
                        <a href="<?= esc_url(admin_url('admin.php?page=top40_lista&id=' . $lista->id . '&registrar_semana=1')); ?>"
                           class="button">Registrar Semana</a>
                        -->

                    <form method="POST" style="display:inline;"
                        onsubmit="return confirm('¿Eliminar esta lista? Todas las canciones y votaciones también se eliminarán.');">
                        <input type="hidden" name="eliminar_lista_id" value="<?= intval($lista->id); ?>">
                        <button type="submit" class="button button-link-delete">Eliminar Lista</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p>No hay listas creadas aún.</p>
    <?php endif; ?>
</div>

<style>
.button-link-delete {
    color: #a00;
}

.button-link-delete:hover {
    color: #dc3232;
}

code {
    background: #f3f3f3;
    padding: 3px 6px;
    border-radius: 3px;
    display: inline-block;
}
</style>