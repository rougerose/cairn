<?php
if (!defined('_ECRIRE_INC_VERSION')) return;

// autorisations

// pour le pipeline
function cairn_autoriser(){}

// autoriser le bouton dans le menu Publication
function autoriser_cairnbt_menu_dist($faire, $type, $id, $qui, $opt) {
  //spip_log ('autoriser_cairnbt_menu_dist '.$qui['statut'], 'cairn');
  return autoriser('webmestre', $type, $id, $qui, $opt);
}

// autoriser page configuration
function autoriser_configurer_cairn_dist($faire, $type, $id, $qui, $opt) {
  return autoriser('webmestre', $type, $id, $qui, $opt);
}

// autoriser page export
function autoriser_exporter_cairn_dist($faire, $type, $id, $qui, $opt) {
  //spip_log ('autoriser_cairn_exporter_dist'.$qui['statut'], 'cairn');
  return autoriser('webmestre', $type, $id, $qui, $opt);
}
