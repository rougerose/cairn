<?php

if (!defined("_ECRIRE_INC_VERSION")) return;

function formulaires_configurer_cairn_saisies_dist() {
  include_spip('inc/config');
  $config = lire_config('cairn');

  return array(
    array(
      'saisie'  => 'input',
      'options' => array(
        'nom'         => 'revue_id',
        'label'       => _T('cairn:cfg_revue_id'),
        'defaut'      => $config['revue_id'],
        'explication' => _T('cairn:cfg_information_cairn'),
        'obligatoire' => 'oui',
      )
    ),
    array(
      'saisie'  => 'input',
      'options' => array(
        'nom'         => 'editeur_id',
        'label'       => _T('cairn:cfg_editeur_id'),
        'defaut'      => $config['editeur_id'],
        'explication' => _T('cairn:cfg_information_cairn'),
        'obligatoire' => 'oui'
      )
    ),
    array(
      'saisie'  => 'input',
      'options' => array(
        'nom'         => 'editeur_nom',
        'label'       => _T('cairn:cfg_editeur_nom'),
        'defaut'      => $config['editeur_nom'],
        'obligatoire' => 'oui'
      )
    ),
    array(
      'saisie'  => 'input',
      'options' => array(
        'nom'         => 'issn',
        'label'       => _T('cairn:cfg_issn'),
        'defaut'      => $config['issn'],
        'obligatoire' => 'oui'
       )
    ),
    array(
      'saisie'  => 'input',
      'options' => array(
        'nom'         => 'issn_num',
        'label'       => _T('cairn:cfg_issn_num'),
        'defaut'      => $config['issn_num'],
        'obligatoire' => 'oui'
      )
    ),
    array(
      'saisie'  => 'input',
      'options' => array(
        'nom'         => 'numeros',
        'label'       => _T('cairn:cfg_numeros'),
        'defaut'      => $config['numeros'],
        'obligatoire' => 'oui'
      )
     )
  );
}
