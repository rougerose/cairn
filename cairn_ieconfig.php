<?php

if (!defined("_ECRIRE_INC_VERSION")) return;

function cairn_ieconfig_metas($table){
  $table['cairn']['titre'] = _T('cairn');
  $table['cairn']['icone'] = 'cairn-16.png';
  $table['cairn']['metas_serialize'] = 'cairn';
  return $table;
}
