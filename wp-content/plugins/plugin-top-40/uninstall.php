<?php
// Verificar acceso correcto
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

global $wpdb;

// Eliminar todas las tablas creadas por el plugin
$tablas = [
    $wpdb->prefix . 'top40_listas',
    $wpdb->prefix . 'top40_canciones',
    $wpdb->prefix . 'top40_votos',
    $wpdb->prefix . 'top40_ranking'
];

foreach ($tablas as $tabla) {
    $wpdb->query("DROP TABLE IF EXISTS $tabla");
}

// Opcional: Eliminar cualquier opción que hayas guardado
// delete_option('alguna_opcion_del_plugin');