<?php

if (!defined("_ECRIRE_INC_VERSION")) return;

function cairn_upgrade($nom_meta_base_version, $version_cible) {
  $maj = array();

  include_spip('base/upgrade');

  maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

function cairn_vider_tables($nom_meta_base_version) {
  effacer_meta('cairn');
  effacer_meta($nom_meta_base_version);
}
