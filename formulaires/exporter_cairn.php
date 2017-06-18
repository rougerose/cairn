<?php

if (!defined('_ECRIRE_INC_VERSION')) {
  return;
}


function formulaires_exporter_cairn_charger_dist($numeros) {
  $valeurs = array(
    'numeros' => $numeros,
    'numero' => _request('numero')
  );

  return $valeurs;
}

function formulaires_exporter_cairn_verifier_dist($numeros) {

  $erreurs           = array();
  $msg_erreur_export = _T('cairn:erreur_export')."<br><br>";
  $id_rubrique       = _request('numero');

  // un numéro sélectionné ?
  if ($id_rubrique) {
    // récupérer les données attendues pour la rubrique (du numéro) et ses articles
    $erreurs_numero   = cairn_verifier_coherence_numero($id_rubrique);
    $where_articles   = cairn_where_articles($id_rubrique, true);
    $erreurs_articles = cairn_verifier_coherence_articles($where_articles);

    // messages d'erreur concernant les données abstente pour la rubrique (du numéro)
    if (count($erreurs_numero)) {
      foreach ($erreurs_numero as $cle => $valeur) {
        $msg_erreur_explication .= "<br>".$valeur;
      }

      $msg_erreur_numero = _T('cairn:erreur_numero', array('id' => $id_rubrique));

      $erreurs['message_erreur'] = $msg_erreur_export.$msg_erreur_numero.$msg_erreur_explication;
    }

    // même chose avec ses articles
    if (count($erreurs_articles)) {
      $cpt = 0;

      foreach ($erreurs_articles as $id => $messages) {
        if ($cpt) $msg_erreur_article .= "<br><br>";

        $msg_erreur_article .= _T('cairn:erreur_article', array('id' => $id));

        foreach($messages as $message) {
          $msg_erreur_article .= "<br>".$message;
        }

        $cpt++;
      }

      if (!isset($erreurs['message_erreur'])) {

        $erreurs['message_erreur'] = $msg_erreur_export;

      } else {

        $erreurs['message_erreur'] .= "<br><br>";
      }

      $erreurs['message_erreur'] .= $msg_erreur_article;
    }

  } else {
    // la sélection d'un numéro est l'info minimale attendue.
    $erreurs['numero'] = _T('info_obligatoire');

  }

  return $erreurs;
}


function formulaires_exporter_cairn_traiter_dist($numeros) {
  include_spip('inc/config');
  $config = lire_config('cairn');

  // les données nécessaires
  $revue_id = $config['revue_id']; // VACA
  $editeur_id = $config['editeur_id']; // AVACA
  $editeur_nom = $config['editeur_nom'];
  $issn = $config['issn'];
  $issn_num = $config['issn_num'];
  $id_rubrique = _request('numero');
  $numero = cairn_get_numero($id_rubrique);
  $where_articles = cairn_where_articles($id_rubrique, true);
  $articles = cairn_get_articles($where_articles);

  // créer le répertoire d'export
  sous_repertoire(_DIR_TMP, 'cairn_export');

  // créer le répertoire du numéro
  sous_repertoire(_DIR_TMP.'cairn_export/', $numero['numero_dir']);

  $articles_ok = '';
  $articles_erreur = '';

  $numero_volume = $numero['numero'];

  $numero_id = $revue_id . '_' . str_pad($numero['numero'], 3, 0, STR_PAD_LEFT);

  // exporter les articles
  foreach($articles as $article) {
    $page_debut = $article['page_debut'];
    $id_article = $article['id_article'];

    // proprio_id formaté selon nécéssités Cairn :
    // VACA_056_0000 => titre_revue _ numéro du volume sur 3 chiffres _ page_debut sur 4 chiffres
    $proprio_id = $numero_id . '_' . str_pad($page_debut, 4, 0, STR_PAD_LEFT);


    $export_article = recuperer_fond('exporter/article', array(
      'date_numero'   => $numero['date_numero'],
      'editeur_id'    => $editeur_id,
      'editeur_nom'   => $editeur_nom,
      'id_article'    => $id_article,
      'isbn'          => $numero['isbn'],
      'issn'          => $issn,
      'issn_num'      => $issn_num,
      'numero_dir'    => $numero['numero_dir'],
      'numero_id'     => $numero_id,
      'numero_volume' => $numero_volume,
      'proprio_id'    => $proprio_id,
      'revue_id'      => $revue_id
    ));

    // $fichier_nom = $revue_id . '_' .$page_debut.'-'.$id_article.'.xml';
    $fichier_nom = $proprio_id . '_' . $id_article . '.xml';

    if (ecrire_fichier(_DIR_TMP.'cairn_export/'.$numero['numero_dir'].'/'.$fichier_nom, $export_article)) {
      $articles_ok .= " ";
    } else {
      $articles_erreur .= "article numéro $id_article : erreur.<br />";
    }
  }

  if ($articles_ok AND !$articles_erreur) {
    return array('message_ok' => 'Les fichiers sont exportés');
  } else {
    return array('message_erreur' => 'Les fichiers suivants n\'ont pas pu être exportés : <br />' . $articles_erreur);
  }

  // TEST avec un seul article
/*
  $page_debut = $articles[0]['page_debut'];
  $id_article = $articles[0]['id_article'];

  $numero_id  = $revue_id.'_'.sprintf("%03s", $numero['numero']);

  // proprio_id formaté selon nécéssités Cairn :
  // VACA_056_0000 => titre_revue _ numéro du volume sur 3 chiffres _ page_debut sur 4 chiffres
  $proprio_id = $revue_id.'_'.sprintf("%04s", $page_debut);

  $export_article = recuperer_fond('exporter/article', array(
    'date_numero'   => $numero['date_numero'],
    'editeur_id'    => $editeur_id,
    'editeur_nom'   => $editeur_nom,
    'id_article'    => $id_article,
    'isbn'          => $numero['isbn'],
    'issn'          => $issn,
    'issn_num'      => $issn_num,
    'numero_dir'    => $numero['numero_dir'],
    'numero_id'     => $numero_id,
    'numero_numero' => $numero['numero'],
    'proprio_id'    => $proprio_id,
    'revue_id'      => $revue_id
  ));

  $fichier_nom = $numero['numero_dir'].'-'.$page_debut.'-'.$id_article.'.xml';


  if (ecrire_fichier(_DIR_TMP.'cairn_export/'.$numero['numero_dir'].'/'.$fichier_nom, $export_article)) {
    return array('message_ok' => 'fichier sauvegardé');
  } else {
    return array('message_erreur' => 'problème !');
  }
  */
}


function cairn_get_numero($id_rubrique) {
  $rows = sql_allfetsel('titre, redacteurchef, pages_total, isbn, date_numero', 'spip_rubriques', sql_in('id_rubrique', $id_rubrique));

  $numero = array();
  $numero['id_rubrique'] = $id_rubrique;

  foreach($rows as $row) {
    $numero['titre']         = $row['titre'];
    $numero['redacteurchef'] = $row['redacteurchef'];
    $numero['pages_total']   = $row['pages_total'];
    $numero['isbn']          = $row['isbn'];
    $numero['date_numero']   = $row['date_numero'];
  }
  include_spip('inc/filtres');

  // numéro du numéro
  $numero['numero'] = match($numero['titre'], '\d+?');

  $numero['numero_dir'] = filtre_cairn_corriger_nom($numero['titre']);

  return $numero;
}


function cairn_get_articles($where_articles) {
  $rows = sql_allfetsel('id_article, page_debut', 'spip_articles', $where_articles);

  foreach($rows as $row) {
    $articles[] = array(
      'id_article' => $row['id_article'],
      'page_debut' => $row['page_debut']
    );
  }

  return $articles;

}

// vérifier que la rubrique du numéro comporte :
// - rédac chef
// - nombre de pages
// - ISBN
// - date de sortie du numéro
function cairn_verifier_coherence_numero($id_rubrique) {
  $rows = sql_allfetsel('redacteurchef, pages_total, isbn, date_numero', 'spip_rubriques', sql_in('id_rubrique', $id_rubrique));

  $erreurs_numero = array();
  $numero = array();

  foreach($rows as $row) {
    $numero['redacteurchef'] = empty($row['redacteurchef'])? false : true;
    $numero['pages_total']   = empty($row['pages_total'])? false : true;
    $numero['isbn']          = empty($row['isbn'])? false : true;
    $numero['date_numero']   = cairn_valider_date($row['date_numero'])? true : false;
  }

  foreach($numero as $cle => $valeur) {
    if (!$valeur) {
      $erreurs_numero[] = _T('cairn:erreur_'.$cle);
    }
  }

  return $erreurs_numero;
}


// verifier que chaque article comporte :
// - page_debut
// - page_fin
// - page_total
function cairn_verifier_coherence_articles($where_articles) {
  $rows = sql_allfetsel('id_article, page_debut, page_fin, pages_total', 'spip_articles', $where_articles);

  $article = array();
  $erreurs_article = array();

  foreach ($rows as $row) {
    $article[$row['id_article']] = array(
      // 'page_debut'  => empty($row['page_debut'])? false : true,
      // 'page_fin'    => empty($row['page_fin'])? false : true,
      'pages_total' => empty($row['pages_total'])? false : true
    );
  }

  foreach ($article as $id => $valeurs) {
    foreach($valeurs as $cle => $valeur) {
      if(!$valeur) {
        $erreurs_article[$id][] = _T('cairn:erreur_'.$cle);
      }
    }
  }

  return $erreurs_article;
}


// trouver les articles d'une rubrique ou d'une branche
// repris du plugin Agenda/formulaires/migrer_agenda.php
function cairn_where_articles($id_rubrique, $branche = false) {
  $where = array();
  $where[] = 'statut='.sql_quote('publie');
  if ($branche) {
    include_spip('inc/rubriques');
    $where[] = sql_in('id_rubrique', calcul_branche_in($id_rubrique));
  } else {
    $where[] = 'id_rubrique='.intval($id_rubrique);
  }
  return $where;
}

// Vérifier que la date existe et qu'elle est au format mysql
// http://www.php.net/manual/en/function.checkdate.php#113205
function cairn_valider_date($date, $format = 'Y-m-d H:i:s') {
  $d = DateTime::createFromFormat($format, $date);
  return $d && $d->format($format) == $date;
}


// function cairn_corriger_nom($nom) {
//   return $nom = preg_replace("/[^\w-]+/", "", ucwords($nom));
// }
