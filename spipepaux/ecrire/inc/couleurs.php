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
 * Couleurs de l'interface de l’espace privé de SPIP.
 *
 * @package SPIP\Core\Couleurs
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Obtenir ou définir les différents jeux de couleurs de l'espace privé
 *
 * - Appelée _sans argument_, cette fonction retourne un tableau décrivant les jeux les couleurs possibles.
 * - Avec un _argument numérique_, elle retourne les paramètres d'URL
 *   pour les feuilles de style calculées (cf. formulaire configurer_preferences)
 * - Avec un _argument de type tableau_ :
 *   - soit elle remplace le tableau par défaut par celui donné en argument
 *   - soit elle le complète, si `$ajouter` vaut `true`.
 *
 * @see formulaires_configurer_preferences_charger_dist()
 *
 * @staticvar array $couleurs_spip
 * @param null|int|array $choix
 * @param bool $ajouter
 * @return array|string
 */
function inc_couleurs_dist($choix = null, $ajouter = false) {
	static $couleurs_spip = [
		// Violet soutenu
		9 => ['couleur_theme' => '#9a6ef2'],
		// Violet rosé
		4 => ['couleur_theme' => '#c464cb'],
		// Rose interface SPIP
		2 => ['couleur_theme' =>  '#F02364'],
		// Rouge
		8 => ['couleur_theme' => '#ff4524'],
		// Orange
		3 => ['couleur_theme' => '#c97500'],
		// Vert SPIP
		1 => ['couleur_theme' => '#9dba00'],
		// Vert Troglo
		7 => ['couleur_theme' => '#419a2c'],
		// Bleu-vert
		12 => ['couleur_theme' => '#269681'],
		//  Bleu pastel
		5 => ['couleur_theme' => '#3190ae'],
		//  Bleu Kermesse
		11 => ['couleur_theme' => '#288bdd'],
		//  Gris bleuté
		6 => ['couleur_theme' => '#7d90a2'],
		//  Gris
		10 => ['couleur_theme' => '#909090'],
	];

	if (is_numeric($choix)) {
		$c = $couleurs_spip[$choix];
		// compat < SPIP 3.3
		include_spip('inc/filtres_images_mini');
		$c['couleur_foncee'] = $c['couleur_theme'];
		$c['couleur_claire'] = '#' . couleur_eclaircir($c['couleur_theme'], .5);

		return
			'couleur_theme=' . substr($c['couleur_theme'], 1)
			// compat < SPIP 3.3
			. '&couleur_claire=' . substr($c['couleur_claire'], 1)
			. '&couleur_foncee=' . substr($c['couleur_foncee'], 1);
	} else {
		if (is_array($choix)) {
			// compat < SPIP 3.3
			$compat_spip_33 = function ($c) {
				if (!isset($c['couleur_theme'])) {
					$c['couleur_theme'] = $c['couleur_foncee'];
					unset($c['couleur_foncee']);
					unset($c['couleur_claire']);
					unset($c['couleur_lien']);
					unset($c['couleur_lien_off']);
				}
				return $c;
			};
			if ($ajouter) {
				foreach ($choix as $c) {
					$couleurs_spip[] = $compat_spip_33($c);
				}

				return $couleurs_spip;
			} else {
				$choix = array_map($compat_spip_33, $choix);
				return $couleurs_spip = $choix;
			}
		}
	}

	return $couleurs_spip;
}
