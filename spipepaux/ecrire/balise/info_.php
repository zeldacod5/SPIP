<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Fonctions génériques pour les balises `#INFO_XXXX`
 *
 * @package SPIP\Core\Compilateur\Balises
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Compile la balise dynamique `#INFO_xx` qui génère n'importe quelle
 * information pour un objet
 *
 * Signature : `#INFO_n{objet,id_objet}` où n est une colonne sur la table
 * SQL de l'objet.
 *
 * @balise
 * @link https://www.spip.net/5544
 * @uses generer_info_entite()
 * @example
 *     ```
 *     #INFO_TITRE{article, #ENV{id_article}}
 *     ```
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 */
function balise_INFO__dist($p) {
	$info = $p->nom_champ;
	$type_objet = interprete_argument_balise(1, $p);
	$id_objet = interprete_argument_balise(2, $p);
	if ($info === 'INFO_' or !$type_objet or !$id_objet) {
		$msg = _T('zbug_balise_sans_argument', ['balise' => ' INFO_']);
		erreur_squelette($msg, $p);
		$p->interdire_scripts = true;

		return $p;
	} else {
		// Récupérer tous les params à la suite de objet et id_objet
		$_params = '[';
		$nb_params = count($p->param[0]); // 1ère valeur vide donc 1 en plus
		for ($i = 3; $i < $nb_params; $i++) {
			$_params .= interprete_argument_balise($i, $p) . ',';
		}
		$_params .= ']';

		$info_sql = strtolower(substr($info, 5));
		$code = "generer_info_entite($id_objet, $type_objet, '$info_sql', " . ($p->etoile ? _q($p->etoile) : "''") . ", $_params)";
		$p->code = champ_sql($info, $p, $code);
		$p->interdire_scripts = true;

		return $p;
	}
}
