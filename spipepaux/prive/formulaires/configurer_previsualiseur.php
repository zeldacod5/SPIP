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

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}
include_spip('inc/presentation');

function formulaires_configurer_previsualiseur_charger_dist() {
	$valeurs = [];
	$valeurs['preview'] = explode(',', $GLOBALS['meta']['preview']);

	return $valeurs;
}


function formulaires_configurer_previsualiseur_traiter_dist() {
	$res = ['editable' => true];

	if ($i = _request('preview') and is_array($i)) {
		$i = ',' . implode(',', $i) . ',';
	}

	ecrire_meta('preview', $i);

	$res['message_ok'] = _T('config_info_enregistree');

	return $res;
}
