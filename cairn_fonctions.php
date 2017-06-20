<?php
if (!defined('_ECRIRE_INC_VERSION')) return;

define('_CHEVRONA', '* [oo *');
define('_CHEVRONB', '* oo] *');


function filtre_cairn_texte($texte, $proteger = true) {

  $texte = preg_replace(',<(i|em)\b[^>]*>(.*)<\/\1>,UimsS', _CHEVRONA.'marquage typemarq="italique"'._CHEVRONB.'$2'._CHEVRONA.'/marquage'._CHEVRONB, $texte);

  $texte = preg_replace(',<(b|strong)\b[^>]*>(.*)<\/\1>,UimsS', _CHEVRONA.'marquage typemarq="gras"'._CHEVRONB.'$2'._CHEVRONA.'/marquage'._CHEVRONB, $texte);

  $texte = preg_replace(',<(ul)\b[^>]*>(.*)<\/\1>,UimsS', _CHEVRONA.'listenonord signe="disque"'._CHEVRONB.'$2'._CHEVRONA.'/listenonord'._CHEVRONB, $texte);

  $texte = preg_replace(',<(ol)\b[^>]*>(.*)<\/\1>,UimsS', _CHEVRONA.'listeord numeration="decimal"'._CHEVRONB.'$2'._CHEVRONA.'/listeord'._CHEVRONB, $texte);

  $texte = preg_replace(',<(li)\b[^>]*>(.*)<\/\1>,UimsS', _CHEVRONA.'elemliste'._CHEVRONB._CHEVRONA.'alinea'._CHEVRONB.'$2'._CHEVRONA.'/alinea'._CHEVRONB._CHEVRONA.'/elemliste'._CHEVRONB, $texte);

  if ($proteger) {
    $texte = proteger_amp(unicode_to_utf_8(html2unicode($texte)));
    $texte = str_replace('&#8217;', '’', $texte);
  }

  return $texte;
}


function filtre_cairn_traiter_notes($texte, $corps = false) {
  $texte = preg_replace('{<div[^>]*>\s*(.*?)\s*<\/div>}i', '\1', $texte);

  $texte = cairn_traiter_texte($texte, $reset = true, $reset_liens = false, $numero_dir = null, $corps);

  $texte = str_replace( array(_CHEVRONA, _CHEVRONB), array('<', '>'), $texte);

	return $texte;
}


function filtre_cairn_traitement_texte($texte, $reset, $reset_liens, $numero_dir, $corps = true) {

  if (!strlen(trim($texte))) return '';

  $texte = cairn_traiter_texte($texte, $reset, $reset_liens, $numero_dir, $corps);

  $texte = str_replace( array(_CHEVRONA, _CHEVRONB), array('<', '>'), $texte);

  return $texte;
}


function cairn_traiter_texte($texte, $reset, $reset_liens, $numero_dir, $corps) {
  static $cpt;

  if ($reset) {
    $cpt = 0;
  }

  // Utiliser le balisage des notes de SPIP
  $note_o_ref = _NOTES_OUVRE_REF;
  $note_f_ref = _NOTES_FERME_REF;

  $note_o_note = _NOTES_OUVRE_NOTE;
  $note_f_note = _NOTES_FERME_NOTE;

  //
  // supprimer les exergues
  //
  $texte = cairn_supprimer_exergues($texte);

  //
  // les sauts de ligne : changement d'alinea
  //
  if (stristr($texte, '<br')) {
    // d'abord supprimer les <p><br>
    $texte = preg_replace('{(<p[^>]*>)(<br[^>]*\/>\s+)}is', '\1', $texte);

    $texte = preg_replace('{<br[^>]*\/>}iu', _CHEVRONA . "/alinea". _CHEVRONB. "\n" . _CHEVRONA . "alinea" . _CHEVRONB, $texte);
  }


  //
  // Figure
  //
  // Les figure doivent être traitées avant car les balises pour le titre et
  // la légende sont spécifiques.
  //
  // Spécifique Vacarme :
  // - A noter que les images <imgXXX> sont traitées systématiquement
  // comme une figure par le modèle <doc>.
  // - Le logo d'article éventuel est inséré au début de l'article
  // au niveau du squelette, dans une <figure>
  //
  //
  if (preg_match('{<figure}i', $texte) AND $numero_dir) {

    // Traitement préalable :
    // les figure sans légende sont entourées d'un <p> inutile par SPIP.
    if (preg_match('{<p><figure}si', $texte)) {
      $texte = preg_replace('{(<p>)(<figure.*?)(<\/p>)}msi', '\2', $texte);
    }

    // Traitement principal :
    $texte = cairn_traiter_figure($texte, $reset, $numero_dir);
  }

  //
  // Poésie
  //
  if (preg_match('{<blockquote class=(\'|")spip_poesie}', $texte)) {
    $texte = preg_replace_callback(
      '{<(blockquote) class=(\'|")spip_poesie(\'|")>(.*?)<\/\1>}is',
      'cairn_traiter_poesie_callback',
      $texte
    );
  }

  //
  // blockquotes
  //
  if (preg_match('{<blockquote class=(\'|")spip}', $texte)) {
    // Les citations sont insérées dans un <p>
    $texte = preg_replace('{<blockquote[^>]*>}i', '<p>$0', $texte);
    $texte = preg_replace('{<\/blockquote>}i', '$0</p>', $texte);
    // Le traitement de la citation
    $texte = preg_replace_callback(
      '{(<blockquote[^>]*>)(.*?)(<\/blockquote>)}is',
      'cairn_traiter_quote_callback',
      $texte
    );
  }

  //
  // les intertitres
  //
  // il doit y avoir au moins une section1,
  // le traitement est toujours effectué.
  // Sauf si $corps = false, il s'agit alors de traiter le chapo ou les notes
  //
  if ($corps) {
    $texte = cairn_decouper_intertitres($texte, $reset);
  }

  //
  // les listes : ce sont des paragraphes.
  //
  if (preg_match('{<(o|u)l}i', $texte)) {
    $texte = preg_replace('{<(o|u)l}i', '<p>$0', $texte);
    $texte = preg_replace('{<\/(o|u)l>}i', '$0</p>', $texte);
  }

  //
  // <hr> : à supprimer
  //
  if (stristr($texte, '<hr')) {
    $texte = preg_replace('{<hr[^n]*\/>}i', '', $texte);
  }

  //
  // les liens
  //
  static $cpt_lien;

  if ($reset_liens) {
    $cpt_lien = 0;
  }
  foreach (extraire_balises($texte, 'a') as $lien) {

    // tous les liens mais pas les ancres
    if (preg_match('{href=(\'|")[^#]}i', $lien)) {

      $tag = $lien;

      // Les url internes sont de forme "espace privé".
      // Aucun moyen de forcer une forme "publique" à partir du formulaire d'export ?
      // Bricolage pour corriger.
      $url = extraire_attribut($tag, 'href');
      if (strstr($url, 'ecrire/')) {
        $_url = parse_url($url);
        $query = $_url['query'];

        $obj = explode('&', $query);
        $objet = substr(strstr($obj[0], '='), 1);
        $id_objet = $obj[1];

        $url_public = url_absolue(generer_url_public($objet, $id_objet));
        $url = ($url_public) ? $url_public : $url;

        $tag = inserer_attribut($tag, 'href', $url, false);
      }

      // type
      $type = extraire_attribut($tag, 'type');
      if ($type) {
        $typeXML = cairn_traiter_typemime($type);
        $p = '{type=(\'|")(.*?)(\'|")}i';
        $r = 'typemime="'.$typeXML.'"';
        $tag = preg_replace($p, $r, $tag);
      }

      // supprimer class et rel
      $tag = vider_attribut($tag, 'class');
      $tag = vider_attribut($tag, 'rel');
      $tag = inserer_attribut($tag, 'id', 'ls'.++$cpt_lien);

      $tag = preg_replace('{<a}i', '<liensimple', $tag);
      $tag = str_replace('href=', 'xlink:href=', $tag);
      $tag = preg_replace('{</a}i', '</liensimple', $tag);

      $tag = filtre_cairn_texte($tag, false);

      $tag = str_replace(array('<','>'), array(_CHEVRONA, _CHEVRONB), $tag);

      $lien_pattern = '/'.preg_quote($lien, '/').'/';

      $texte = preg_replace($lien_pattern, $tag, $texte, 1);
    }
  }

  //
  // Les notes:
  //
  // Les liens simples ont déjà été traités, il ne doit rester que les appels
  // de note.
  //
  // TODO: mais il pourrait rester des ancres...
  //

  if (strstr($texte, $note_o_ref)) {
    static $cpt_ref;

    if ($reset) {
      $cpt_ref = 0;
    }

    $texte = str_replace(array($note_o_ref, $note_f_ref), array('', ''), $texte);

    foreach(extraire_balises($texte, 'a') as $a) {
      $cpt_ref++;

      if (extraire_attribut($a, 'rel') == 'footnote') {
        $numero_note = supprimer_tags($a);
        // utiliser le compteur de la fonction et non le numéro de note de SPIP,
        // car cela peut-être un appel de type * (ou autre caractère non numérique)
        //  ou bien une note répétée...
        $note = _CHEVRONA . 'renvoi id="re' . $cpt_ref . 'no' . $cpt_ref .'" ';
        $note .= 'idref="no' . $cpt_ref . '" typeref="note"' . _CHEVRONB;
        $note .= $numero_note . "\n";
        $note .= _CHEVRONA . '/renvoi' . _CHEVRONB;

        $a_pattern = '/'.preg_quote($a, '/').'/';

        $texte = preg_replace($a_pattern, $note, $texte, 1);
      }
    }
  }

  if (strstr($texte, $note_o_note)) {
    static $cpt_note;

    if ($reset) $cpt_note = 0;

    foreach(extraire_balises($texte, 'p') as $note) {

      if ($a = extraire_balise($note, 'a') AND extraire_attribut($a, 'rev') == 'footnote') {
        $cpt_note++;

        $numero_note = supprimer_tags($a);

        $texte_note = str_replace(array($note_o_note, $note_f_note), array('', ''), $note);
        $texte_note = str_replace(array('<p>', '</p>'), array('', ''), $texte_note);
        $texte_note = preg_replace('{<a[^>]*>.*<\/a>}iU', '', $texte_note);
        $texte_note = filtre_cairn_texte($texte_note, false);

        $_note = "\n" . _CHEVRONA . "note id=\"no$cpt_note\"" . _CHEVRONB;
        $_note .= "\n" . _CHEVRONA . 'no' . _CHEVRONB . $numero_note . _CHEVRONA . '/no' . _CHEVRONB;
        $_note .= "\n" . _CHEVRONA . 'alinea' . _CHEVRONB . $texte_note . _CHEVRONA . '/alinea' . _CHEVRONB;
        $_note .= "\n" . _CHEVRONA . '/note' . _CHEVRONB;

        $note_pattern = '/'.preg_quote($note, '/').'/';

        $texte = preg_replace($note_pattern, $_note, $texte, 1);
      }
    }
  }

  //
  // Les paragraphes
  //
  if (stristr($texte, '<p')) {

    static $cpt_para;

    if ($reset) $cpt_para = 0;

    foreach(extraire_balises($texte, 'p') as $p) {
      $cpt_para++;

      $texte_para = preg_replace('{<p[^>]*>(.*?)<\/p>}is', '\1', $p);

      $para = _CHEVRONA . "para id=\"pa$cpt_para\"" . _CHEVRONB;

      //
      // Vérifier que le paragraphe ne contient pas une liste ou une citation
      // car ces éléments ne sont pas inclus dans <alinea>
      //
      if (preg_match('{<[u|o]l}is', $texte_para)) {
        $para .= filtre_cairn_texte($texte_para, false);
      } elseif (preg_match('{bloccitation}is', $texte_para)) {
        $para .= filtre_cairn_texte($texte_para, false);
      } else {
        $para .= "\n" . _CHEVRONA . "alinea" . _CHEVRONB . filtre_cairn_texte($texte_para) . _CHEVRONA . "/alinea" . _CHEVRONB;
      }

      $para .= "\n" . _CHEVRONA . "/para" . _CHEVRONB;

      $p_pattern = '/'.preg_quote($p, '/').'/';

      $texte = preg_replace($p_pattern, $para, $texte, 1);
    }

  }


  if (!$corps) {
    $texte = filtre_cairn_texte($texte, false);
  }

  //
  // supprimer lignes vides
  //
  $texte = preg_replace('{^\s*}m', '', $texte);

  $texte = proteger_amp(unicode_to_utf_8(html2unicode($texte)));
  $texte = str_replace('&#8217;', '’', $texte);

  return $texte;
}



// Transforme une chaîne en CamelCase et sans caractères exotiques
// le nom sera utilisé pour le répertoire de sauvegarde des images et des xml.
function filtre_cairn_corriger_nom($nom) {
  return $nom = preg_replace("/[^\w-]+/", "", ucwords($nom));
}


function filtre_cairn_prenom_nom($nom) {
  return preg_replace_callback('{(.*)[\*_](.*)}s', 'traiter_prenom_nom', $nom);
}


function traiter_prenom_nom($match) {
  $prenom = $match[2] ? '<prenom>'.filtre_cairn_texte($match[2]).'</prenom>' : '';
  $nom = $match[1] ? '<nomfamille>'.filtre_cairn_texte($match[1]).'</nomfamille>' : '';
  return $prenom.$nom;
}


function filtre_cairn_compter($texte) {
  $compte = array();

  if (!strlen($texte)) return '';

  // paragraphes
  $paragraphes = cairn_get_paragraphes($texte);
  $compte['paragraphes'] = count($paragraphes);

  // mots
  if (count($paragraphes) > 0) {
    $recherche = array(
      '{<liensimple[^>]*>(.*?)<\/liensimple>}',
      '{<renvoi[^>]*>.*?<\/renvoi>}s',
      '{<marquage[^>]*>(.*?)<\/marquage>}'
    );
    $remplace = array('\1', '', '\1');

    foreach($paragraphes as $tag) {
      $res .= preg_replace($recherche, $remplace, $tag);
    }

    $compte['mots'] = cairn_compte_mots_utf8(supprimer_tags($res));

  } else {

    $compte['mots'] = 0;

  }

  // figures
  $figures = extraire_balises($texte, 'figure');
  $compte['figures'] = count($figures);

  // images
  //
  // TODO: on compte seulement les images dans le texte,
  // mais il faudra compter également les images en documents associés (voir todo.md)
  //
  $images = extraire_balises($texte, 'image');
  $compte['images'] = count($images);

  // notes
  $notes = extraire_balises($texte, 'renvoi');
  $compte['notes'] = count($notes);

  return $compte;
}


function cairn_get_paragraphes($texte) {
  $paragraphes = extraire_balises($texte, 'para');
  preg_match_all('{<section[^>]*>\s*<titre>(.*?)<\/titre>}s', $texte, $intertitres);

  return array_merge($paragraphes, $intertitres[1]);
}


// Compter les mots
// ******************
// La fonction str_word_count de PHP surestime le total des mots.
// Donc, utilisation de
// http://ca3.php.net/manual/en/function.str-word-count.php#107363
// et, dans les caractères qui ne séparent pas les mots, ajout de :
// - l'apostrophe droit et courbé
// - du point médian
//
// Le compte est presque bon (à une centaine de mots près...)
//
function cairn_compte_mots_utf8($str) {
  return count(preg_split('{[^\p{L}\p{N}\’\'\xB7]+}u', $str));
}


function cairn_decouper_intertitres($texte, $reset) {
  //
  // Intertitres précédent et courant
  //
  $intertitre_prev = 0;
  $intertitre_curr = 1;

  //
  // Enregistrer dans un tableau les niveaux d'intertitres
  // pour les retrouver plus facilement et déterminer les niveaux
  // d'imbrication des sections.
  //
  // h2 => section1
  // h3 => section2
  // etc.
  //
  $intertitres_niveau = array(
    '2' => '1',
    '3' => '2',
    '4' => '3',
    '5' => '4',
    '6' => '5'
  );

  $sections = preg_split('{<h[2-6] class="spip">}i', $texte);

  $texte = array_shift($sections);

  //
  // le début du texte qui ne contient pas d'intertitre
  //
  // C'est une section1 sans balise titre imbriquée.
  // Cette section sera fermée plus bas.
  //
  if (strlen($texte)) {
    $intertitre_prev = 1;
    $debut = cairn_traiter_intertitres(false, 2, $texte, $reset);
    $texte = $debut;
  }

  foreach ($sections as $section) {

    list($intertitre, $niveau, $suite) = preg_split('{</h([2-6])>}i', $section, null, PREG_SPLIT_DELIM_CAPTURE);

    $intertitre_curr = $intertitres_niveau[$niveau];

    $direction = $intertitre_curr - $intertitre_prev;

    //
    // La section courante est la même que la précédente.
    // On ferme la section et on ouvre.
    //
    if ($direction == 0) {
      // $texte .= "1. direction: $direction, prev: $intertitre_prev, curr: $intertitre_curr \n";
      $texte .= "\n" . _CHEVRONA . "/section$intertitre_prev" . _CHEVRONB;
      $texte .= cairn_traiter_intertitres($intertitre, $niveau, $suite);
    }

    //
    // La section courante est imbriquée dans la précédente.
    // On ouvre donc directement.
    //
    if ($direction == 1) {
      // $texte .= "2. direction: $direction, prev: $intertitre_prev, curr: $intertitre_curr \n";
      $intertitre_prev = $intertitres_niveau[$niveau];
      $texte .= cairn_traiter_intertitres($intertitre, $niveau, $suite);
    }

    //
    // La section courante est d'un niveau supérieure.
    //
    if ($direction < 0) {
      //
      // Fermeture de la section précédente.
      //
      if ($direction == -1) {
        // $texte .= "3. direction: $direction, prev: $intertitre_prev, curr: $intertitre_curr \n";
        $texte .= "\n" . _CHEVRONA . "/section$intertitre_prev" . _CHEVRONB;
        $intertitre_prev = $intertitres_niveau[$niveau];

        //
        // Ne pas oublier de fermer un niveau d'imbrication.
        //
        if ($intertitre_curr - $intertitre_prev == 0) $texte .= "\n" . _CHEVRONA . "/section$intertitre_prev" . _CHEVRONB;

        //
        // On ouvre.
        //
        $texte .= cairn_traiter_intertitres($intertitre, $niveau, $suite);
      }

      //
      // Fermetures des sections précédentes.
      //
      if ($direction < -1) {
        // $texte .= "4. direction: $direction, prev: $intertitre_prev, curr: $intertitre_curr \n";
        for ($i=0; $i >= $direction ; $i--) {
          $texte .= "\n" . _CHEVRONA . "/section$intertitre_prev" . _CHEVRONB;
          $intertitre_prev--;
        }

        //
        // Puis ouverture de la suivante.
        //
        $texte .= cairn_traiter_intertitres($intertitre, $niveau, $suite);

      }
    }
    // le niveau le plus haut dans l'arbre est 1.
    if (!$intertitre_prev) $intertitre_prev = 1;
  }

  // $texte .= "5. direction: $direction, prev: $intertitre_prev, curr: $intertitre_curr \n";

  // Fermer les sections autant de fois que nécessaire.
  for ($i=0; $i < $intertitre_curr ; $i++) {
    $n = $intertitre_curr - $i;
    $texte .= "\n" . _CHEVRONA . "/section$n" . _CHEVRONB;
  }

  return $texte;
}


function cairn_traiter_intertitres($intertitre, $niveau, $suite, $reset = false) {
  static $h = array();

  if ($reset) { $h = array(); }

  // Le tableau stocke le nombre d'occurences d'un intertitre
  // afin de déterminer son rang dans l'ensemble du texte.
  if (array_key_exists($niveau, $h)) {
    $c = $h[$niveau];
    $h[$niveau] = ++$c;
  } else {
    $h[$niveau] = ++$c;
  }

  if ($intertitre) $intertitre = supprimer_tags($intertitre);

  $sectionNiv = $niveau - 1;
  $sectionRang = 'n' . $h[$niveau];

  $texte .= "\n" . _CHEVRONA . "section$sectionNiv id=\"s$sectionNiv$sectionRang\"" . _CHEVRONB;
  $texte .= ($intertitre) ? "\n" . _CHEVRONA . "titre" . _CHEVRONB . filtre_cairn_texte($intertitre, false) . _CHEVRONA . "/titre" . _CHEVRONB : '';
  $texte .= ($suite) ? "\n$suite" : '';

  // Pour mémoire, la fermeture de la section est assurée par la fonction englobante.

  return $texte;
}


function cairn_traiter_poesie_callback($matches) {

  $p = filtre_cairn_texte($matches[4], false);

  // convertir les éventuels &nbsp;
  // $p = unicode_to_utf_8(html2unicode($p, true));

  // une ligne vide <div>&nbsp;</div> : changement de bloc
  $p = preg_replace(
    '{<div>\s*<\/div>}usmi',
    _CHEVRONA.'/bloc'._CHEVRONB._CHEVRONA.'bloc'._CHEVRONB,
    $p
  );

  // une ligne contenant du texte
  $p = preg_replace(
    '{<div>(.*?)<\/div>}uims',
    _CHEVRONA.'ligne'._CHEVRONB.'\1'._CHEVRONA.'/ligne'._CHEVRONB,
    $p
  );

  return _CHEVRONA.'verbatim typeverb="poeme"'._CHEVRONB."\n"
    ._CHEVRONA.'bloc'._CHEVRONB."\n"
    .$p
    ._CHEVRONA.'/bloc'._CHEVRONB."\n"
    ._CHEVRONA.'/verbatim'._CHEVRONB."\n";
}


function cairn_traiter_quote_callback($matches) {
  $texte = filtre_cairn_texte($matches[2], false);
  $texte = preg_replace('{<p[^>]*>(.*?)<\/p>}ims', _CHEVRONA . 'alinea' . _CHEVRONB . '\1' . _CHEVRONA . '/alinea' . _CHEVRONB, $texte);

  $citation = "\n" . _CHEVRONA . 'bloccitation' . _CHEVRONB;
  $citation .= "\n" . $texte;
  $citation .= "\n" . _CHEVRONA . '/bloccitation' . _CHEVRONB;

  return $citation;
}


// échapper / et + pour compatibilité xml
function cairn_traiter_typemime($type) {
  return $type = str_replace(array('/','+'), array(':', '-'), $type);
}


function cairn_traiter_figure($texte, $reset, $numero_dir) {
  static $cpt;

  if ($reset) {$cpt = 0;}

  foreach(extraire_balises($texte, 'figure') as $figure) {

    $cpt++;

    // TODO: si l'objet n'est pas une image mais un fichier (pdf, doc),
    // faut-il le différencier par <objet> ?  Mais quel typeobj, la doc
    // fait référence uniquement à audio|video

    $tag_ouv = "\n" . _CHEVRONA . 'figure id="fi'.$cpt.'"' . _CHEVRONB;
    $tag_ferm = "\n" . _CHEVRONA . '/figure' . _CHEVRONB;
    //$fig = preg_replace('{<figure[^>]*>}i', $tag_ouv, $figure);
    //$fig = str_replace('</figure>', $tag_ferm, $fig);
    $f = $tag_ouv;

    foreach(extraire_balises($figure, 'figcaption') as $figcaption) {
      $legende = '';
      $titre = '';

      //
      // Titre
      //
      preg_match('{<h[1-6][^>](.*)>(.*)<\/h[1-6]>}', $figcaption, $matches);
      $titre = filtre_cairn_texte($matches[2], false);

      //
      // Descriptif et Crédits
      //
      // Sauf à utiliser un masque de recherche qui présuppose que 'descriptif'
      // et 'credits' sont utilisés dans les classes des paragraphes,
      // il est impossible de les différencier et d'utiliser <source>
      // pour le crédit. On met tout dans <legende>.
      //
      $alineas = '';

      foreach(extraire_balises($figcaption, 'p') as $alinea) {
        preg_match('{(<p[^>]*>)(.*?)(<\/p>)}ims', $alinea, $matches);
        $txt = filtre_cairn_texte($matches[2], false);
        $alineas .= "\n" . _CHEVRONA . 'alinea' . _CHEVRONB . $txt . _CHEVRONA . '/alinea' . _CHEVRONB;
      }

      if ($titre || $alineas) {
        $legende = _CHEVRONA . 'legende lang="fr"' . _CHEVRONB;
        ($titre) ? $legende .= "\n" . _CHEVRONA . 'titre' . _CHEVRONB . $titre . _CHEVRONA . '/titre' . _CHEVRONB : '';
        ($alineas) ? $legende .= $alineas : '';
        $legende .= "\n" . _CHEVRONA . '/legende' . _CHEVRONB;
      }

      // $fig = str_replace($figcaption, $legende, $fig);
      $f .= $legende;
    }

    if ($cpt <= 1) {
      $reset_image = true;
    } else {
      $reset_image = false;
    }

    $img = cairn_traiter_image($figure, $reset_image, $numero_dir);

    $f .= $img;
    $f .= $tag_ferm;

    $figure_pattern = '/'.preg_quote($figure, '/').'/';

    $texte = preg_replace($figure_pattern, $f, $texte);
  }

  return $texte;
}


function cairn_traiter_image($texte, $reset, $numero_dir) {
  static $cpt;

  if ($reset) { $cpt = 0; }

  foreach (extraire_balises($texte, 'img') as $img) {
    $cpt++;

    $src = extraire_attribut($img, 'src');
    $alt = extraire_attribut($img, 'alt');

    //
    // Les images sont éventuellement dans une taille modifiée par rapport
    // à l'originale. C'est la version modifiée que l'on garde.
    //
    if ($src AND $copie = copie_locale(url_absolue($src), 'modif')) {

      $fichier = basename($copie);
      $src_file = _DIR_RACINE . $copie;
      $dest_dir = sous_repertoire(_DIR_TMP . "cairn_export/$numero_dir", 'images');
      $dest_file = $dest_dir . $fichier;

      if (file_exists($dest_file)) {
        unlink($dest_file);
      }

      copy($src_file, $dest_file);

      $ext = preg_replace(',^.*\.,', '', $fichier);
      if ($ext == 'jpg') $ext = 'jpeg';

      $image = "\n" . _CHEVRONA . 'objetmedia flot="bloc"' . _CHEVRONB;
      $image .= "\n" . _CHEVRONA . 'image id="im'.$cpt.'" typeimage="figure" typemime="image:'.$ext.'" xlink:type="simple" xlink:href="'.$fichier.'" xlink:actuate="onRequest"/' . _CHEVRONB;
      $image .= "\n" . _CHEVRONA . '/objetmedia' . _CHEVRONB;

      // $texte = str_replace($img, $image, $texte);
    }

    return $image;
  }
}


// Supprimer les exergues
function cairn_supprimer_exergues($texte) {
  $exergues = '{<(blockquote) class=(\'|")exergue(\'|")>(.*?)<\/\1>}si';

  if (preg_match('{<blockquote class=(\'|")exergue}', $texte)) {
    $texte = preg_replace($exergues, '', $texte);
  }

  return $texte;
}
