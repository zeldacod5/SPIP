<?php
/**
 * SPIP Loader recupere et installe SPIP
 * 
 * Configuration
 * -------------
 * Pour les mises a jour effectuees avec ce script,
 * toutes les constantes ci-dessous peuvent etre surchargees
 * dans config/mes_options.php
 */

if (file_exists('spip_loader_config.php')) {
	include_once('spip_loader_config.php');
}

/** 
 * Auteur(s) autorise(s) a proceder aux mises a jour : '1:2:3'
 * 
 * @note En tete, sinon defini trop tard !
 */
if (!defined('_SPIP_LOADER_UPDATE_AUTEURS')) {
	define('_SPIP_LOADER_UPDATE_AUTEURS', '1');
}


/**
 * Version de SPIP Loader
 * 
 * Historique
 * ----------
 * - [...]
 * - 3.0.10 : Compat PHP 7.4 & 8
 * - 4.0.0  : Utilisation d’un fichier spip_loader_list.json
 *            On ne rend disponible que les dernières versions maintenues.
 * - 4.1.0  : Si notre SPIP installé actuel n’est plus une version maintenue, on demande explicitement la branche
 * - 4.1.1  : Tolérance php < 5.4 du spip_loader !
 * - 4.2.0  : Mise en cache pour 10mn de spip_loader_list.json
 * - 4.3.0  : Correction pour retrouver l'analyse et déplacement des fichiers obsolètes
 *            SL Nécessite PHP 5.2 minimum (faut pas pousser... désolé les vieux SPIP 3.1...)
 * - 4.3.1  : Toutes les fonctions du loader son préfixées par SL. 
 *            On fait un tour au déballage fini, à 100% pour avoir tout le temps libre pour déplacer les fichiers et vérifier les superflus.
 * - 4.3.2  : Compat PHP de SPIP 4.0
 * - 4.3.3  : SPIP 4.0 par défaut
 * 
 * - 5.0.0  : Version 2 de l’api JSON qui inclut la branche par défaut et les requirements php.
 * - 5.0.1  : Montrer une erreur si on ne peut pas écrire la mise à jour de Spip Loader, invalider les caches fichiers du loader sur une mise à jour.
 */
define('_SPIP_LOADER_VERSION', '5.0.1');
define('_SPIP_LOADER_API', 2);


# Adresse des librairies necessaires a spip_loader
# (pclzip et fichiers de langue)
if (!defined('_URL_LOADER_DL')) {
	define('_URL_LOADER_DL', 'https://www.spip.net/spip-dev/INSTALL/');
}

// Url des versions proposées
if (!defined('_URL_SPIP_LOADER_LIST')) {
	define('_URL_SPIP_LOADER_LIST', _URL_LOADER_DL . 'spip_loader_list.json');
}



// Url du fichier spip_loader permettant de tester sa version distante
if (!defined('_URL_SPIP_LOADER')) {
	define('_URL_SPIP_LOADER', _URL_LOADER_DL . 'spip_loader.php');
}

# telecharger a travers un proxy
if (!defined('_URL_LOADER_PROXY')) {
	define('_URL_LOADER_PROXY', '');
}

# repertoires d'installation
if (!defined('_DIR_BASE')) {
	define('_DIR_BASE', './');
}

function SL_error_handler($exception) {
	$content = $exception->getMessage();
	$version = _SPIP_LOADER_VERSION;
	echo <<<EOS
<html>
<head>
<title>Error</title>
</head>
<body>
<style>
:root { --color: #F02364; --padding: 1em 2em; }
body{ display: grid; justify-content: center; align-content: center; height:100%; }
.page { min-width: 25em; }
.error { border: 3px solid var(--color); padding: var(--padding); font-weight: bold;}
h1 { font-size: 1.25em; }
</style>
<div class="page">
<span class="loader">SPIP Loader — $version</span>
<div class="error">
	<h1>Error</h1>
	<p>$content</p>
</div>
</div>
</body>
</html>
EOS;

}
set_exception_handler('SL_error_handler');
  

/** 
 * Notre branche de destination par défaut 
 * 
 * - ignoré si la constante _CHEMIN_FICHIER_ZIP est forcée
 * - ignoré si un SPIP est déjà installé (tentera de rester sur la même branche par défaut)
 */
$notre_branche = SL_determiner_branche_par_defaut();


if (!defined('_CHEMIN_FICHIER_ZIP')) {
	/**
	 * Chemin du zip installé par défaut
	 * 
	 * Si la constante _CHEMIN_FICHIER_ZIP est déjà définie, 
	 * alors le zip défini sera utilisé.
	 * 
	 * Sinon, on prend par défaut le zip de la branche installée par défaut.
	 */
	if (!$notre_branche) {
		define('_CHEMIN_FICHIER_ZIP', '');
	} else {
		define('_CHEMIN_FICHIER_ZIP', $notre_branche['zip']);
	}
} else {
	// éviter d’afficher le sélecteur de branche dans ces cas là.
	define('_CHEMIN_FICHIER_ZIP_FORCEE', true);
}

if (!defined('_DIR_PLUGINS')) {
	define('_DIR_PLUGINS', _DIR_BASE . 'plugins/');
}

# adresse du depot
if (!defined('_URL_SPIP_DEPOT')) {
	define('_URL_SPIP_DEPOT', 'https://files.spip.net/');
}


# surcharger le script
if (!defined('_NOM_PAQUET_ZIP')) {
	define('_NOM_PAQUET_ZIP', 'spip');
}
// par defaut le morceau de path a enlever est le nom : '' (anciennement 'spip/')
if (!defined('_REMOVE_PATH_ZIP')) {
	define('_REMOVE_PATH_ZIP', '');
}

if (!defined('_SPIP_LOADER_PLUGIN_RETOUR')) {
	define('_SPIP_LOADER_PLUGIN_RETOUR', 'ecrire/?exec=admin_plugin&voir=tous');
}

if (!defined('_SPIP_LOADER_SCRIPT')) {
	define('_SPIP_LOADER_SCRIPT', 'spip_loader.php');
}

// "habillage" optionnel
// liste separee par virgules de fichiers inclus dans spip_loader
// charges a la racine comme spip_loader.php et pclzip.php
// selon l'extension: include .php , .css et .js dans le <head> genere par spip_loader
if (!defined('_SPIP_LOADER_EXTRA')) {
	define('_SPIP_LOADER_EXTRA', '');
}


if (!defined('_DEST_PAQUET_ZIP')) {
	define('_DEST_PAQUET_ZIP', '');
}
if (!defined('_PCL_ZIP_SIZE')) {
	define('_PCL_ZIP_SIZE', 249587);
}
if (!defined('_PCL_ZIP_RANGE')) {
	define('_PCL_ZIP_RANGE', 200);
}
/** 
 * Le SPIP Loader ne place pas dans le répertoire obsolète
 * un répertoire qui contiendrait un fichier avec ce nom.
 */
if (!defined('_SPIP_LOADER_KEEP')) {
	define('_SPIP_LOADER_KEEP', '.spip_loader_keep');
}


#######################################################################

# langues disponibles
$langues = array (
	'ar' => "&#1593;&#1585;&#1576;&#1610;",
	'ast' => "asturianu",
	'br' => "brezhoneg",
	'ca' => "catal&#224;",
	'cs' => "&#269;e&#353;tina",
	'de' => "Deutsch",
	'en' => "English",
	'eo' => "Esperanto",
	'es' => "Espa&#241;ol",
	'eu' => "euskara",
	'fa' => "&#1601;&#1575;&#1585;&#1587;&#1609;",
	'fr' => "fran&#231;ais",
	'fr_tu' => "fran&#231;ais copain",
	'gl' => "galego",
	'hr' => "hrvatski",
	'id' => "Indonesia",
	'it' => "italiano",
	'km' => "Cambodian",
	'lb' => "L&euml;tzebuergesch",
	'nap' => "napulitano",
	'nl' => "Nederlands",
	'oc_lnc' => "&ograve;c lengadocian",
	'oc_ni' => "&ograve;c ni&ccedil;ard",
	'pt_br' => "Portugu&#234;s do Brasil",
	'ro' => "rom&#226;n&#259;",
	'sk' => "sloven&#269;ina",	// (Slovakia)
	'sv' => "svenska",
	'tr' => "T&#252;rk&#231;e",
	'wa' => "walon",
	'zh_tw' => "&#21488;&#28771;&#20013;&#25991;", // chinois taiwan (ecr. traditionnelle)
);

/**
 * Liste des versions possibles 
 * (avec l’adresse du zip et la version minimale de PHP)
 * 
 * @param string|null $branch 
 *     Pour retourner l’info d’une branche spécifique
 * @return array|false 
 *     Descriptif des branches, ou d’une seule branche
 */
function SL_lister_branches_proposees($branch = null) {
	static $branches = null;
	
	if ($branches === null) {
		$liste = SL_lister_versions_spip();
		$branches = array_column($liste['versions'], null, 'branche');
	}
	if (!is_null($branch)) {
		return isset($branches[$branch]) ? $branches[$branch] : false;
	}
	return $branches;
}

/**
 * Détermine quelle est la branche par défaut à utiliser.
 * 
 * @return sting|false 
 *     nom de la branche par défaut, ou false
 */
function SL_determiner_branche_par_defaut() {
	static $default = null;
	if ($default === null) {
		$branches = SL_lister_branches_proposees();
		$liste = SL_lister_versions_spip();
		$branch = $liste['default_branch'];

		$default = isset($branches[$branch]) ? $branches[$branch] : false;
	}
	return $default;
}


/**
 * Renvoie une définition des versions SPIP 
 *
 * Tableau
 * - defaut_branch => version
 * - versions => [ chemin => [ description de la version ]]
 * 
 * @return array
 */
function SL_lister_versions_spip() {
	// Récupération du fichier spip_loader_list.json
	$filename = 'spip_loader_list.json';
	$ttl = 10 * 60; // 10mn
	$local_spip_loader_list = _DIR_BASE . $filename;
	if (
		!file_exists($local_spip_loader_list)
		or (time() - filemtime($local_spip_loader_list) > $ttl)
	) {
		$contenu = SL_recuperer_page(_URL_SPIP_LOADER_LIST);
		if ($contenu) {
			if (! @file_put_contents($local_spip_loader_list, $contenu)) {
				throw new \Exception("Impossible d’écrire le fichier " . _URL_SPIP_LOADER_LIST);
			}
			SL_spip_clear_opcode_cache($local_spip_loader_list);
		} else {
			throw new \Exception("Impossible d’écrire le fichier " . _URL_SPIP_LOADER_LIST);
		}
	}

	$liste = file_get_contents($local_spip_loader_list);
	if (!$liste) {
		throw new \Exception("Impossible de lire le fichier $filename");
	}
	$liste = json_decode($liste, true);
	if (!is_array($liste)) {
		throw new \Exception("Impossible de décoder le fichier $filename");
	}

	$api = $liste['api'];
	if (!$api or $api !== _SPIP_LOADER_API) {
		return [
			'default_branch' => null,
			'versions' => [],
		];
	}

	$php = !empty($liste['requirements']['php']) ? $liste['requirements']['php'] : array();
	$versions = array();
	foreach ($liste['versions'] as $version => $path) {
		$branch = SL_getBranchNameFromTag($version);
		$php_min = !empty($php[$branch]) ? $php[$branch] : (!empty($php['master']) ? $php['master'] : null);
		$versions[$path] = array(
			'version' => $version,
			'branche' => ($version === 'dev' ? 'dev' : $branch),
			'etat' => ($version === 'master' ? 'dev' : 'stable'),
			'zip' => $path,
			'php_min' => $php_min,
		);
	}

	return [
		'default_branch' => !empty($liste['default_branch']) ? $liste['default_branch'] : null,
		'versions' => $versions,
	];
}

function SL_getBranchNameFromTag($tag) {
	if (!$tag) {
		return "";
	}
	if ($tag[0] === 'v') {
		$tag = substr($tag, 1);
	}
	$b = explode('.', $tag);
	$branch = array();
	$branch[] = array_shift($b);
	$branch[] = array_shift($b);
	$branch = implode('.', $branch); 
	return $branch;
}

function SL_branche_spip($version) {
	if (in_array($version, array('master', 'dev'))) {
		return 'dev';
	}
	$v = explode('.', $version);
	$branche = $v[0] . '.' . (isset($v[1]) ? $v[1] : '0');
	return $branche;
}

// faut il mettre à jour le spip_loader ?
function SL_necessite_maj() {
	return version_compare(_SPIP_LOADER_VERSION, SL_recupere_version(), '<');
}

// trouver le numéro de version du dernier spip_loader
function SL_recupere_version() {
	static $version = null;
	if (is_null($version)) {
		$version = false;
		$spip_loader = SL_recuperer_page(_URL_SPIP_LOADER);
		if (preg_match("/define\('_SPIP_LOADER_VERSION', '([0-9.]*)'\)/", $spip_loader, $m)) {
			$version = $m[1];
		}
	}
	return $version;
}


//
// Traduction des textes de SPIP
//
function SL_T($code, $args = array()) {
	global $lang;
	$code = str_replace('tradloader:', '', $code);
	$text = $GLOBALS['i18n_tradloader_'.$lang][$code];
	foreach ($args as $name => $value) {
		$text = str_replace("@$name@", $value, $text);
	}
	return $text;
}


function SL_move_all($src, $dest) {
	global $chmod;
	$dest = rtrim($dest, '/');

	if ($dh = opendir($src)) {
		while (($file = readdir($dh)) !== false) {
			if (in_array($file, array('.', '..'))) {
				continue;
			}
			$s = "$src/$file";
			$d = "$dest/$file";
			if (is_dir($s)) {
				if (!is_dir($d)) {
					if (!mkdir($d, $chmod, true)) {
						die("impossible de creer $d");
					}
				}
				SL_move_all($s, $d);
				rmdir($s);
				// verifier qu'on en a pas oublie (arrive parfois il semblerait ...)
				// si cela arrive, on fait un clearstatcache, et on recommence un move all...
				if (is_dir($s)) {
					clearstatcache();
					SL_move_all($s, $d);
					rmdir($s);
				}
			} else {
				if (is_file($s)) {
					rename($s, $d);
				}
			}
		}
		// liberer le pointeur sinon windows ne permet pas le rmdir eventuel
		closedir($dh);
	}
}

function SL_regler_langue_navigateur() {
	$accept_langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
	if (is_array($accept_langs)) {
		foreach ($accept_langs as $s) {
			if (preg_match('#^([a-z]{2,3})(-[a-z]{2,3})?(;q=[0-9.]+)?$#i', trim($s), $r)) {
				$lang = strtolower($r[1]);
				if (isset($GLOBALS['langues'][$lang])) {
					return $lang;
				}
			}
		}
	}
	return false;
}

function SL_menu_langues($lang, $script = '', $hidden = array()) {
	$r = '';
	if (preg_match(',action=([a-z_]+),', $script, $m)) {
		$r .= "<input type='hidden' name='action' value='".$m[1]."' />";
		$script .= '&amp;';
	} else {
		$script .= '?';
	}

	foreach ($hidden as $k => $v) {
		if ($v and $k!='etape') {
			$script .= "$k=$v&amp;";
		}
	}
	$r .= '<select name="lang"
		onchange="window.location=\''.$script.'lang=\'+this.value;">';

	foreach ($GLOBALS['langues'] as $l => $nom) {
		$r .= '<option value="'.$l.'"' . ($l == $lang ? ' selected="selected"' : '')
			. '>'.$nom."</option>\n";
	}
	$r .= '</select> <noscript><div><input type="submit" name="ok" value="ok" /></div></noscript>';
	return $r;
}

/**
 * Affiche un sélecteur de menu pour choisir le zip (la branche) à utiliser.
 * 
 * @param array $active Chemin du paquet à télécharger actuellement sélectionné
 * @param string $version_installee Version de SPIP actuellement installée
 * @return string
 */
function SL_menu_branches($active, $version_installee, $presente) {
	$select = '';
	if (!defined('_CHEMIN_FICHIER_ZIP_FORCEE')) {
		$script = _DIR_BASE . _SPIP_LOADER_SCRIPT . '?';
		$select .= "<div style='float:" . $GLOBALS['spip_lang_right'] . "'>";
		$select .= '<select name="chemin" onchange="window.location=\'' . $script . 'chemin=\'+this.value;">';

		foreach (SL_lister_branches_proposees() as $branche => $desc) {
			if ($branche == 'dev' or !$version_installee or version_compare(SL_branche_spip($version_installee), $branche, '<=')) {
				$_active = ($active == $desc['zip']) && ($presente);
				$select .= '<option value="' . $desc['zip'] . '"' . ($_active ? ' selected="selected"' : '') . '>'
					. 'SPIP ' . $branche 
					. "</option>\n";
			}
		}
		if (!$presente) {
			$select .= '<option value="" selected="selected">'
			. 'Sélectionnez...'
			. "</option>\n";
		}
		$select .= '</select> <noscript><div><input type="submit" name="ok" value="ok" /></div></noscript>';
		$select .= '</div>';
	}
	return $select;
}


//
// Gestion des droits d'acces
//
function SL_tester_repertoire() {
	global $chmod;

	$ok = false;
	$self = basename($_SERVER['PHP_SELF']);
	$uid = @fileowner('.');
	$uid2 = @fileowner($self);
	$gid = @filegroup('.');
	$gid2 = @filegroup($self);
	$perms = @fileperms($self);

	// Comparer l'appartenance d'un fichier cree par PHP
	// avec celle du script et du repertoire courant
	@rmdir('test');
	@unlink('test'); // effacer au cas ou
	@touch('test');
	if ($uid > 0 && $uid == $uid2 && @fileowner('test') == $uid) {
		$chmod = 0700;
	} else {
		if ($gid > 0 && $gid == $gid2 && @filegroup('test') == $gid) {
			$chmod = 0770;
		} else {
			$chmod = 0777;
		}
	}
	// Appliquer de plus les droits d'acces du script
	if ($perms > 0) {
		$perms = ($perms & 0777) | (($perms & 0444) >> 2);
		$chmod |= $perms;
	}
	@unlink('test');

	// Verifier que les valeurs sont correctes

	@mkdir('test', $chmod);
	@chmod('test', $chmod);
	$ok = (is_dir('test') && is_writable('test')) ? $chmod : false;
	@rmdir('test');

	return $ok;
}

// creer repertoire
function SL_creer_repertoires_plugins($chmod) {
	 // créer les répertoires plugins/auto et lib
	if (!is_dir('plugins')) {
		@mkdir('plugins', $chmod);
	}
	if (!is_dir('plugins/auto')) {
		@mkdir('plugins/auto', $chmod);
	}
	if (!is_dir('lib')) {
		@mkdir('lib', $chmod);
	}
	return 	'cretion des repertoires tentee';
}

//
// Demarre une transaction HTTP (s'arrete a la fin des entetes)
// retourne un descripteur de fichier
//
function SL_init_http($get, $url, $refuse_gz = false) {
	//global $http_proxy;
	$fopen = false;
	if (!preg_match(",^http://,i", _URL_LOADER_PROXY)) {
		$http_proxy = '';
	} else {
		$http_proxy = _URL_LOADER_PROXY;
	}

	$t = @parse_url($url);
	$host = $t['host'];
	if ($t['scheme'] == 'http') {
		$scheme = 'http';
		$scheme_fsock = '';
	} else {
		$scheme = $t['scheme'];
		$scheme_fsock = $scheme.'://';
	}
	if (!isset($t['port']) or !($port = $t['port'])) {
		$port = 80;
	}
	$query = isset($t['query']) ? $t['query'] : '';
	if (!isset($t['path']) or !($path = $t['path'])) {
		$path = "/";
	}

	if ($http_proxy) {
		$t2 = @parse_url($http_proxy);
		$proxy_host = $t2['host'];
		$proxy_user = $t2['user'];
		$proxy_pass = $t2['pass'];
		if (!($proxy_port = $t2['port'])) {
			$proxy_port = 80;
		}
		$f = @fsockopen($proxy_host, $proxy_port);
	} else {
		$f = @fsockopen($scheme_fsock.$host, $port);
	}

	if ($f) {
		if ($http_proxy) {
			fputs(
				$f,
				"$get $scheme://$host" . (($port != 80) ? ":$port" : "") .
				$path . ($query ? "?$query" : "") . " HTTP/1.0\r\n"
			);
		} else {
			fputs($f, "$get $path" . ($query ? "?$query" : "") . " HTTP/1.0\r\n");
		}
		$version_affichee = isset($GLOBALS['spip_version_affichee'])?$GLOBALS['spip_version_affichee']:"xx";
		fputs($f, "Host: $host\r\n");
		fputs($f, "User-Agent: SPIP-$version_affichee (https://www.spip.net/)\r\n");

		// Proxy authentifiant
		if (isset($proxy_user) and $proxy_user) {
			fputs($f, "Proxy-Authorization: Basic "
			. base64_encode($proxy_user . ":" . $proxy_pass) . "\r\n");
		}
	} elseif (!$http_proxy) {
		// fallback : fopen
		$f = @fopen($url, "rb");
		$fopen = true;
	} else {
		// echec total
		$f = false;
	}

	return array($f, $fopen);
}

//
// Recupere une page sur le net
// et au besoin l'encode dans le charset local
//
// options : get_headers si on veut recuperer les entetes
function SL_recuperer_page($url) {

	// Accepter les URLs au format feed:// ou qui ont oublie le http://
	$url = preg_replace(',^feed://,i', 'http://', $url);
	if (!preg_match(',^[a-z]+://,i', $url)) {
		$url = 'http://'.$url;
	}

	// dix tentatives maximum en cas d'entetes 301...
	for ($i = 0; $i < 10; $i++) {
		list($f, $fopen) = SL_init_http('GET', $url);

		// si on a utilise fopen() - passer a la suite
		if ($fopen) {
			break;
		} else {
			// Fin des entetes envoyees par SPIP
			fputs($f, "\r\n");

			// Reponse du serveur distant
			$s = trim(fgets($f, 16384));
			if (preg_match(',^HTTP/[0-9]+\.[0-9]+ ([0-9]+),', $s, $r)) {
				$status = $r[1];
			} else {
				return;
			}

			// Entetes HTTP de la page
			$headers = '';
			while ($s = trim(fgets($f, 16384))) {
				$headers .= $s."\n";
				if (preg_match(',^Location: (.*),i', $s, $r)) {
					$location = $r[1];
				}
				if (preg_match(",^Content-Encoding: .*gzip,i", $s)) {
					$gz = true;
				}
			}
			if ($status >= 300 and $status < 400 and $location) {
				$url = $location;
			} elseif ($status != 200) {
				return;
			} else {
				break; # ici on est content
			}
			fclose($f);
			$f = false;
		}
	}

	// Contenu de la page
	if (!$f) {
		return false;
	}

	$result = '';
	while (!feof($f)) {
		$result .= fread($f, 16384);
	}
	fclose($f);

	// Decompresser le flux
	if (isset($_GET['gz']) and $gz = $_GET['gz']) {
		$result = gzinflate(substr($result, 10));
	}

	return $result;
}

function SL_telecharger_langue($lang, $droits) {

	$fichier = 'tradloader_'.$lang.'.php';
	$GLOBALS['idx_lang'] = 'i18n_tradloader_'.$lang;
	if (!file_exists(_DIR_BASE.$fichier)) {
		$contenu = SL_recuperer_page(_URL_LOADER_DL.$fichier.".txt");
		if ($contenu and $droits) {
			file_put_contents(_DIR_BASE . $fichier, $contenu);
			include(_DIR_BASE.$fichier);
			return true;
		} elseif ($contenu and !$droits) {
			eval('?'.'>'.$contenu);
			return true;
		} else {
			return false;
		}
	} else {
		include(_DIR_BASE.$fichier);
		return true;
	}
}

function SL_selectionner_langue($droits) {
	global $langues; # langues dispo

	$lang = '';

	if (isset($_COOKIE['spip_lang_ecrire'])) {
		$lang = $_COOKIE['spip_lang_ecrire'];
	}

	if (isset($_REQUEST['lang'])) {
		$lang = $_REQUEST['lang'];
	}

	# reglage par defaut selon les preferences du brouteur
	if (!$lang or !isset($langues[$lang])) {
		$lang = SL_regler_langue_navigateur();
	}

	# valeur par defaut
	if (!isset($langues[$lang])) {
		$lang = 'fr';
	}

	# memoriser dans un cookie pour l'etape d'apres *et* pour l'install
	setcookie('spip_lang_ecrire', $lang);

	# RTL
	if ($lang == 'ar' or $lang == 'he' or $lang == 'fa') {
		$GLOBALS['spip_lang_right']='left';
		$GLOBALS['spip_lang_dir']='rtl';
	} else {
		$GLOBALS['spip_lang_right']='right';
		$GLOBALS['spip_lang_dir']='ltr';
	}

	# code de retour = capacite a telecharger le fichier de langue
	$GLOBALS['idx_lang'] = 'i18n_tradloader_'.$lang;
	return SL_telecharger_langue($lang, $droits) ? $lang : false;
}

function SL_debut_html($corps = '', $hidden = array()) {

	global $lang, $spip_lang_dir, $spip_lang_right, $version_installee;

	if ($version_installee) {
		$titre = SL_T('tradloader:titre_maj', array('paquet'=>strtoupper(_NOM_PAQUET_ZIP)));
	} else {
		$titre = SL_T('tradloader:titre', array('paquet'=>strtoupper(_NOM_PAQUET_ZIP)));
	}
	$css = $js = '';
	foreach (explode(',', _SPIP_LOADER_EXTRA) as $fil) {
		switch (strrchr($fil, '.')) {
			case '.css':
				$css .= '
	<!-- css pour tuning optionnel, au premier chargement, il manquera si pas droits ... -->
	<link rel="stylesheet" href="' . basename($fil) . '" type="text/css" media="all" />';
				break;
			case '.js':
				$js .= '
	<!-- js pour tuning optionnel, au premier chargement, il manquera... -->
	<script src="' . basename($fil) . '" type="text/javascript"></script>';
				break;
		}
	}

	$hid = '';
	foreach ($hidden as $k => $v) {
		$hid .= "<input type='hidden' name='$k' value='$v' />\n";
	}
	$script = _DIR_BASE . _SPIP_LOADER_SCRIPT;
	echo
	"<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'https://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>
	<html 'xml:lang=$lang' dir='$spip_lang_dir'>
	<head>
	<title>$titre</title>
	<meta http-equiv='Expires' content='0' />
	<meta http-equiv='cache-control' content='no-cache,no-store' />
	<meta http-equiv='pragma' content='no-cache' />
	<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
	<style type='text/css'>
	body {
		font-family:Verdana, Geneva, sans-serif;
		font-size:.9em;
		color: #222;
		background-color: #f8f7f3;
	}
	#main {
		margin:5em auto;
		padding:3em 2em;
		background-color:#fff;
		border-radius:2em;
		box-shadow: 0 0 20px #666;
		width:34em;
	}
	a {
		color: #E86519;
	}
	a:hover {
		color:#FF9900;
	}
	h1 {
		color:#5F4267;
		display:inline;
		font-size:1.6em;
	}
	h2 {
		font-weigth: normal;
		font-size: 1.2em;
	}
	div {
		line-height:140%;
	}
	div.progression {
		margin-top:2em;
		font-weight:bold;
		font-size:1.4em;
		text-align:center;
	}
	.bar {border:1px solid #aaa;}
	.bar div {background:#aaa;height:1em;}
	.version {background:#eee;margin:1em 0;padding:.5em;}
	.version-courante {color:#888;}
	.erreur {border-left:4px solid #f00; padding:1em 1em 1em 2em; background:#FCD4D4;}
	.info {border-left:4px solid #FFA54A; padding:1em 1em 1em 2em; background:#FFEED9; margin:1em 0;}
	</style>$css$js
	</head>
	<body>
	<div id='main'>
	<form action='" . $script . "' method='get'>" .
	"<div style='float:$spip_lang_right'>" .
	SL_menu_langues($lang, $script, $hidden) .
	"</div>
	<div>
	<h1>" . $titre . "</h1>". $corps .
	$hid .
	"</div></form>";
}

function SL_fin_html()
{
	global $taux;
	echo ($taux ? '
	<div id="taux" style="display:none">'.$taux.'</div>' : '') .
	'
	<p style="text-align:right;font-size:x-small;">spip_loader '
	. _SPIP_LOADER_VERSION
	.'</p>
	</div>
	</body>
	</html>
	';

	// forcer l'envoi du buffer par tous les moyens !
	echo(str_repeat("<br />\r\n", 256));
	while (@ob_get_level()) {
		@ob_flush();
		@flush();
		@ob_end_flush();
	}
}


function SL_nettoyer_racine($fichier) {
	@unlink($fichier);
	@unlink(_DIR_BASE.'pclzip.php');
	@unlink(_DIR_BASE.'spip_loader_list.json');
	@unlink(_DIR_BASE.'svn.revision');
	$d = opendir(_DIR_BASE);
	while (false !== ($f = readdir($d))) {
		if (preg_match('/^tradloader_(.+).php$/', $f)) {
			@unlink(_DIR_BASE.$f);
		}
	}
	closedir($d);
	return true;
}


/**
 * Déplace les fichiers qui sont en trop entre le contenu du zip et le répertoire destination.
 * 
 * @param array $content Liste des fichiers issus de pclZip
 * @param string $dir Répertoire où ils ont été copiés.
 */
function SL_nettoyer_superflus($content, $dir) {
	global $chmod;
	$diff = SL_comparer_contenus($content, $dir);
	if ($diff) {
		@mkdir($old = _DIR_BASE . 'fichiers_obsoletes_' . date('Ymd_His'), $chmod);
		if (!is_dir($old)) {
			return false;
		}
		$old .= '/';
		foreach ($diff as $file => $isDir) {
			$root = $isDir ? $file : dirname($file);
			if (!is_dir($old . $root)) {
				mkdir($old . $root, $chmod, true);
			}
			if ($isDir) {
				SL_move_all(_DIR_BASE . $root, $old . $root);
				rmdir(_DIR_BASE . $root);
			} else {
				rename(_DIR_BASE . $file, $old . $file);
			}
		}
	}
}

/**
 * Retourne la liste des fichiers/répertoires en trop entre le zip et la destination,
 * pour certains répertoires seulement.
 *
 * @param array $content Fichiers contenus dans le zip
 * @param string $dir Répertoire ou le zip a été dézippé
 */
function SL_comparer_contenus($content, $dir) {

	// On se considère dans SPIP et on vérifie seulement ces répertoires
	$repertoires_suivis = array(
		'ecrire', 
		'prive', 
		'plugins-dist', 
		'squelettes-dist',
		'extensions', // spip 2.1 hum.
	);

	$contenus_source_suivis = SL_lister_contenus_zip_suivis($content, $repertoires_suivis);
	$diff = SL_lister_contenus_superflus($contenus_source_suivis, $dir, $repertoires_suivis);
	return $diff;
}

/**
 * Retourne la liste des fichiers/répertoires suivis du zip
 *
 * @param array $content Fichiers contenus dans le zip
 * @param array $repertoires_suivis Répertoires que l'on vérifie (on ne s'occupe pas des autres)
 * @return array chemin => isDir ?
 */
function SL_lister_contenus_zip_suivis($content, $repertoires_suivis) {
	// avant le zip contenait un dossier "spip/"... (c'est tout à la racine maintenant) 
	$base = _REMOVE_PATH_ZIP;
	if ($base && $content[0]['filename'] !== $base) {
		return false;
	}

	$len = strlen($base);

	// Liste des contenus sources (chemin => isdir?)
	$contenus_source = array();
	foreach ($content as $c) {
		$fichier = substr($c['filename'], $len);
		$root = explode('/', $fichier, 2);
		$root = reset($root);
		if (!in_array($root, $repertoires_suivis)) {
			continue;
		}
		$contenus_source[$fichier] = $c['folder'];
	}

	// certains zips n'indiquent pas les répertoires ; on les ajoute...
	// on ne conserve pas le / final du coup...	
	foreach ($contenus_source as $fichier => $isDir) {
		if (!$isDir) {
			$_dir = dirname($fichier);
			if ($_dir === '.' or isset($contenus_source[$_dir])) {
				continue;
			}
			do {
				$contenus_source[$_dir] = true;
				$_dir = dirname($_dir);
			} while ($_dir && $_dir !== '.');
		}	
	}

	return $contenus_source;
}

/**
 * Liste les contenus en trop dans certains répertoires, en fonction d’une liste de fichiers 
 * 
 * Un répertoire superflu, mais contenant un fichier .spip_loader_keep est conservé,
 * c'est à dire qu’il ne sera pas retourné dans cette liste de fichiers/répertoire obsolètes.
 * 
 * @param array $contenus_source liste(chemin => isDir?)
 * @param string $dir Chemin du répertoire à tester
 * @param array|null $repertoires_suivis Liste de répertoires à uniquement parcourrir si défini.
 * @return array liste(chemin => isDir?) des fichiers/répertoire en trop.
 */
function SL_lister_contenus_superflus($contenus_source, $dir, $repertoires_suivis) {

	$iterator = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS);
	$iterator = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
	$iterator = new RegexIterator($iterator, '#^' . $dir . '(' . implode('|', $repertoires_suivis) . ')(/|$)#');
	$superflus = new SL_SuperflusFilterIterator($iterator, $contenus_source, $dir);

	$liste = array();
	$ignoreLen = strlen($dir);
	foreach ($superflus as $file) {
		$liste[ substr($file->getPathname(), $ignoreLen) ] = $file->isDir();
	}
	return $liste;
}

/** 
 * Iterateur des dossiers et fichiers superflus...
 * Note : utiliser CallbackFilterIterator quand PHP >= 5.4 pour SL.
 */
class SL_SuperflusFilterIterator extends FilterIterator
{
	private $contenus_source;
	private $ignoreLen;
	private $ignoreDirs = array();

	public function __construct($iterator, $contenus_source, $dir) {
		parent::__construct($iterator);
		$this->contenus_source = $contenus_source;
		$this->ignoreLen = strlen($dir);
	}

	public function accept() {
		$file = $this->getInnerIterator()->current();
		if ($this->isAcceptedFile($file)) {
			return false;
		}
		// on tente de ne pas mettre les fichiers et dossiers d'un dossier déjà ignoré...
		// ne fonctionne que si l'iterateur en entrée est bien trié (::SELF_FIRST)
		if ($file->isDir()) {
			$this->ignoreDirs[] = $file->getPathname();
		}
		$parent = $file->getPath();
		if (in_array($parent, $this->ignoreDirs)) {
			return false;
		}
		return true;
		
	}

	private function isAcceptedFile($file) {
		if ($file->getFileName() === '.ok') {
			return true;
		}
		$path = substr($file->getPathname(), $this->ignoreLen);
		if ($file->isDir()) {
			if (isset($this->contenus_source[$path])) {
				return true;
			}
			// ne pas rendre obsolète si un fichier de conservation est présent.
			if (file_exists($file->getPathname() . '/' . _SPIP_LOADER_KEEP)) {
				return true;
			}
			return false;
		} 
		if (isset($this->contenus_source[$path])) {
			return true;
		}
		return false;
	}
}


// un essai pour parer le probleme incomprehensible des fichiers pourris
function SL_touchCallBack($p_event, &$p_header)
{
	// bien extrait ?
	if ($p_header['status'] == 'ok') {
		// allez, on touche le fichier, le @ est pour les serveurs sous Windows qui ne comprennent pas touch()
		@touch($p_header['filename']);
	}
	return 1;
}
function SL_microtime_float()
{
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec);
}

function SL_verifie_zlib_ok()
{
	global $taux;
	if (!function_exists("gzopen") and !function_exists("gzopen64")) {
		return false;
	}

	if (!file_exists($f = _DIR_BASE . 'pclzip.php')) {
		$taux = SL_microtime_float();
		$contenu = SL_recuperer_page(_URL_LOADER_DL . 'pclzip.php.txt');
		if ($contenu) {
			file_put_contents($f, $contenu);
		}
		$taux = _PCL_ZIP_SIZE / (SL_microtime_float() - $taux);
	}
	include $f;
	$necessaire = array();
	foreach (explode(',', _SPIP_LOADER_EXTRA) as $fil) {
		$necessaire[$fil] = strrchr($fil, '.') == '.php' ? '.txt' : '';
	}
	foreach ($necessaire as $fil => $php) {
		if (!file_exists($f = _DIR_BASE . basename($fil))) {
			$contenu = SL_recuperer_page(_URL_LOADER_DL . $fil . $php);
			if ($contenu) {
				file_put_contents($f, $contenu);
			}
		}
		if ($php) {
			include $f;
		}
	}
	return true;
}

function SL_reinstalle() {
	if (!defined('_SPIP_LOADER_UPDATE_AUTEURS')) {
		define('_SPIP_LOADER_UPDATE_AUTEURS', '1');
	}
	if (!isset($GLOBALS['auteur_session']['statut']) or
		$GLOBALS['auteur_session']['statut'] != '0minirezo' or
		!in_array($GLOBALS['auteur_session']['id_auteur'], explode(':', _SPIP_LOADER_UPDATE_AUTEURS))) {
		include_spip('inc/headers');
		include_spip('inc/minipres');
		http_status('403');
		echo install_debut_html(_T('info_acces_interdit'));
		echo "<div style='text-align: center'>\n";
		echo _T('ecrire:avis_non_acces_page');
		echo '<br /><a href="' .  parametre_url(generer_url_public('login'), 'url', 'spip_loader.php') . '">' . _T('public:lien_connecter') . '</a>';
		echo "\n</div>";
		echo install_fin_html();
		exit;
	}
}

function SL_spip_deballe_paquet($paquet, $fichier, $dest, $range) {
	global $chmod;

	// le repertoire temporaire est invariant pour permettre la reprise
	@mkdir($tmp = _DIR_BASE.'zip_'.md5($fichier), $chmod);
	$ok = is_dir($tmp);

	$zip = new PclZip($fichier);
	$content = $zip->listContent();
	$max_index = count($content);
	$start_index = isset($_REQUEST['start']) ? intval($_REQUEST['start']) : 0;
	$ended = isset($_REQUEST['ended']) ? true : false;
	if (!$range) {
		$range = _PCL_ZIP_RANGE;
	}
	$end_index = min($start_index + $range, $max_index);

	if (!$ended && $start_index < $max_index) {
		$ok &= (bool) $zip->extractByIndex(
			"$start_index-$end_index",
			PCLZIP_OPT_PATH,
			$tmp,
			PCLZIP_OPT_SET_CHMOD,
			$chmod,
			PCLZIP_OPT_REPLACE_NEWER,
			PCLZIP_OPT_REMOVE_PATH,
			_REMOVE_PATH_ZIP,
			PCLZIP_CB_POST_EXTRACT,
			'SL_touchCallBack'
		);
	}

	if (!$ok or $zip->error_code < 0) {
		SL_debut_html();
		echo SL_T('tradloader:donnees_incorrectes', array('erreur' => $zip->errorInfo()));
		SL_fin_html();
	} else {
		// si l'extraction n'est pas finie, relancer
		$url = _DIR_BASE . _SPIP_LOADER_SCRIPT 
			. (strpos(_SPIP_LOADER_SCRIPT, '?') ? '&' : '?') 
			. "etape=fichier&chemin=$paquet&dest=$dest&range=$range";

		if (!$ended && $start_index < $max_index) {
			$progres = $start_index/$max_index;
			SL_spip_redirige_boucle($url . "&start=$end_index", $progres);
		} elseif (!$ended) {
			// on a fait le dernier tour, afficher 100% et relancer pour les déplacements et nettoyages
			SL_spip_redirige_boucle($url . "&ended=1", 1);
		}

		if ($dest) {
			@mkdir(_DIR_PLUGINS, $chmod);
			$dir = _DIR_PLUGINS . $dest;
			$url = _DIR_BASE . _SPIP_LOADER_PLUGIN_RETOUR;
		} else {
			$dir =  _DIR_BASE;
			$url = _DIR_BASE . _SPIP_LOADER_URL_RETOUR;
		}
		SL_move_all($tmp, $dir);
		rmdir($tmp);
		SL_nettoyer_superflus($content, $dir);
		SL_nettoyer_racine($fichier);
		header("Location: $url");
	}
}

function SL_spip_redirige_boucle($url, $progres = ''){
	//@apache_setenv('no-gzip', 1); // provoque page blanche chez certains hebergeurs donc ne pas utiliser
	@ini_set('zlib.output_compression', '0'); // pour permettre l'affichage au fur et a mesure
	@ini_set('output_buffering', 'off');
	@ini_set('implicit_flush', 1);
	@ob_implicit_flush(1);
	$corps = '<meta http-equiv="refresh" content="0;'.$url.'">';
	if ($progres) {
		$corps .="<div class='progression'>".round($progres*100)."%</div>
				  <div class='bar'><div style='width:".round($progres*100)."%'></div></div>
				";
	}
	SL_debut_html($corps);
	SL_fin_html();
	exit;
}

function SL_spip_presente_deballe($fichier, $paquet, $dest, $range) {
	global $version_installee;

	$nom = (_DEST_PAQUET_ZIP == '') ?
			SL_T('tradloader:ce_repertoire') :
			(SL_T('tradloader:du_repertoire').
				' <tt>'._DEST_PAQUET_ZIP.'</tt>');

	$hidden = array(
		'chemin' => $paquet,
		'dest' => $dest,
		'range' => $range,
		'etape' => file_exists($fichier) ? 'fichier' : 'charger'
	);

	// Version proposée à l'installation par défaut
	$versions_spip = SL_lister_versions_spip();
	$versions_spip = $versions_spip['versions'];
	$version_future = '';
	if (isset( $versions_spip[$paquet])) {
		$version_future = $versions_spip[$paquet]['version'];
		if ($versions_spip[$paquet]['etat'] == 'dev') {
			$version_future .= '-dev';
		}
	}
	$version_future_affichee = 'SPIP ' . $version_future;

	// notre branche est elle maintenue ? si non, on demande à sélectionner une autre branche
	// ou on n’a demandé déjà un spip spécifique...
	// ou on n’a pas de spip installé !
	if (
		!empty($_REQUEST['chemin'])
		or false !== strpos($version_installee, '-dev')
		or !$version_installee
	) {
		$presente = true;
	} else {
		$ma_branche = SL_getBranchNameFromTag($version_installee);
		$branches = SL_lister_branches_proposees();
		$presente = $ma_branche && isset($branches[$ma_branche]);
		if (!$presente) {
			$version_future_affichee = 'Sélectionnez…';
		}
	}

	if ($version_installee) {
		// Mise à jour
		$bloc_courant =
			'<div class="version-courante">'
			. SL_T('tradloader:titre_version_courante')
			. '<strong>'. 'SPIP ' . $version_installee .'</strong>'
			. '</div>';
		$bouton = SL_T('tradloader:bouton_suivant_maj');
	} else {
		// Installation nue
		$bloc_courant = '';
		$bouton = SL_T('tradloader:bouton_suivant');
	}

	// Détection d'une incompatibilité avec la version de PHP installée
	$php_incompatible = false;
	if (isset($versions_spip[$paquet]['version'])) {
		$branche_future = SL_branche_spip($versions_spip[$paquet]['version']);
		$version_php_installee = phpversion();
		$version_php_spip = SL_lister_branches_proposees($branche_future);
		$version_php_spip = $version_php_spip['php_min'];
		$php_incompatible = version_compare($version_php_spip, $version_php_installee, '>');
	}
	
	if ($php_incompatible) {
		$bouton =
			'<div class="erreur">'
			. SL_T('tradloader:echec_php', array('php1' => $version_php_installee, 'php2' => $version_php_spip))
			. '</div>';
	} elseif (version_compare($version_installee, $version_future, '>') and ($version_future !== 'dev')) {
		// Épargnons un downgrade aux personnes étourdies
		$bouton =
			"<div style='text-align:".$GLOBALS['spip_lang_right']."'>"
			. '<input type="submit" disabled="disabled" value="' . $bouton . '" />'
			. '</div>';
	} elseif (!$presente) {
		// Forcer à avoir une branche si la notre n’existe plus 
		$bouton =
			"<div style='text-align:".$GLOBALS['spip_lang_right']."'>"
			. '<input type="submit" disabled="disabled" value="' . $bouton . '" />'
			. '</div>';
	} else {
		$bouton =
			"<div style='text-align:".$GLOBALS['spip_lang_right']."'>"
			. '<input type="submit" value="' . $bouton . '" />'
			. '</div>';
	}

	// Construction du corps
	if ($versions_spip) {
		$corps =
			SL_T('tradloader:texte_intro', array('paquet'=>strtoupper(_NOM_PAQUET_ZIP),'dest'=> $nom))
			. '<div class="version">'
			. $bloc_courant
			. '<div class="version-future">'
			. SL_T('tradloader:titre_version_future')
			. '<strong>'. $version_future_affichee. '</strong>'
			. SL_menu_branches($paquet, $version_installee, $presente)
			. '</div>'
			. '</div>'
			. $bouton;
	} else {
		$corps = 
			'<div class="version">'
			. $bloc_courant
			. '<div class="version-future">'
			. 'Aucune version proposée ?<br><i>Probablement qu’une mise à jour de SPIP Loader s’impose !</i>' 
			. '</div>'
			. '</div>';
	}

	if (SL_necessite_maj()) {
		$corps .=
			"<div class='info'><a href='" . _URL_SPIP_LOADER . "'>"
			. SL_T('tradloader:spip_loader_maj', array('version' => SL_recupere_version()))
			. "</a>"
			. "<div style='margin-top:1rem;text-align:".$GLOBALS['spip_lang_right']."'>"
			. "<input type='submit' name='spip_loader_update' value='".SL_T('tradloader:bouton_suivant_maj')."' />"
			. "</div></div>";
	}

	SL_debut_html($corps, $hidden);
	SL_fin_html();
}

function SL_spip_recupere_paquet($paquet, $fichier, $dest, $range)
{
	$contenu = SL_recuperer_page(_URL_SPIP_DEPOT . $paquet);

	if (!($contenu and file_put_contents($fichier, $contenu))) {
		SL_debut_html();
		echo SL_T('tradloader:echec_chargement'), "$paquet, $fichier, $range" ;
		SL_fin_html();
	} else {
		// Passer a l'etape suivante (desarchivage)
		$sep = strpos(_SPIP_LOADER_SCRIPT, '?') ? '&' : '?';
		header("Location: "._DIR_BASE._SPIP_LOADER_SCRIPT.$sep."etape=fichier&chemin=$paquet&dest=$dest&range=$range");
	}
}

function SL_spip_deballe($paquet, $etape, $dest, $range)
{
	$fichier = _DIR_BASE . basename($paquet);

	if ($etape == 'fichier'	and file_exists($fichier)) {
		// etape finale: deploiement de l'archive
		SL_spip_deballe_paquet($paquet, $fichier, $dest, $range);

	} elseif ($etape == 'charger') {

		// etape intermediaire: charger l'archive
		SL_spip_recupere_paquet($paquet, $fichier, $dest, $range);

	} else {
		// etape intiale, afficher la page de presentation
		SL_spip_presente_deballe($fichier, $paquet, $dest, $range);
	}
}



/**
 * Invalidates a PHP file from any active opcode caches.
 *
 * If the opcode cache does not support the invalidation of individual files,
 * the entire cache will be flushed.
 * kudo : http://cgit.drupalcode.org/drupal/commit/?id=be97f50
 *
 * @param string $filepath
 *   The absolute path of the PHP file to invalidate.
 */
function SL_spip_clear_opcode_cache($filepath) {
	clearstatcache(true, $filepath);

	// Zend OPcache
	if (function_exists('opcache_invalidate')) {
		$invalidate = @opcache_invalidate($filepath, true);
		// si l'invalidation a echoue lever un flag
		if (!$invalidate and !defined('_spip_attend_invalidation_opcode_cache')) {
			define('_spip_attend_invalidation_opcode_cache',true);
		}
	} elseif (!defined('_spip_attend_invalidation_opcode_cache')) {
		// n'agira que si opcache est effectivement actif (il semble qu'on a pas toujours la fonction opcache_invalidate)
		define('_spip_attend_invalidation_opcode_cache',true);
	}
	// APC.
	if (function_exists('apc_delete_file')) {
		// apc_delete_file() throws a PHP warning in case the specified file was
		// not compiled yet.
		// @see http://php.net/apc-delete-file
		@apc_delete_file($filepath);
	}
}


///////////////////////////////////////////////
// debut du process
//

error_reporting(E_ALL ^ E_NOTICE);

// PHP >= 5.3 rale si cette init est absente du php.ini et consorts
// On force a defaut de savoir anticiper l'erreur (il doit y avoir mieux)
if (function_exists('date_default_timezone_set')) {
	date_default_timezone_set('Europe/Paris');
}
$GLOBALS['taux'] = 0; // calcul eventuel du taux de transfert+dezippage

// En cas de reinstallation, verifier que le demandeur a les droits avant tout
// definir _FILE_CONNECT a autre chose que machin.php si on veut pas
$version_installee = '';
if (@file_exists('ecrire/inc_version.php')) {
	define('_SPIP_LOADER_URL_RETOUR', "ecrire/?exec=accueil");
	include_once 'ecrire/inc_version.php';
	$version_installee = $GLOBALS['spip_version_branche'];
	if ((defined('_FILE_CONNECT') and
		_FILE_CONNECT and
		strpos(_FILE_CONNECT, '.php')) or
		defined('_SITES_ADMIN_MUTUALISATION')) {
		SL_reinstalle();
	}
} else {
	define('_SPIP_LOADER_URL_RETOUR', "ecrire/?exec=install");
	// _DIR_TMP n’existe pas encore
	if (!defined('PCLZIP_TEMPORARY_DIR')) {
		define('PCLZIP_TEMPORARY_DIR', '');
	}
}

$droits = SL_tester_repertoire();

$GLOBALS['lang'] = SL_selectionner_langue($droits);

if (!$GLOBALS['lang']) {
	//on ne peut pas telecharger
	$GLOBALS['lang'] = 'fr'; //francais par defaut
	$GLOBALS['i18n_tradloader_fr']['titre'] = 'T&eacute;l&eacute;chargement de SPIP';
	$GLOBALS['i18n_tradloader_fr']['echec_chargement'] = '<h4>Le chargement a &eacute;chou&eacute;.'.
	' Veuillez r&eacute;essayer, ou utiliser l\'installation manuelle.</h4>';
	SL_debut_html();
	echo SL_T('tradloader:echec_chargement');
	SL_fin_html();
} elseif (!$droits) {
	//on ne peut pas ecrire
	SL_debut_html();
	$q = $_SERVER['QUERY_STRING'];
	echo SL_T(
		'tradloader:texte_preliminaire',
		array(
			'paquet' => strtoupper(_NOM_PAQUET_ZIP),
			'href'   => ('spip_loader.php' . ($q ? "?$q" : '')),
			'chmod'  => sprintf('%04o', $chmod)
		)
	);
	SL_fin_html();
} elseif (!SL_verifie_zlib_ok()) {
	// on ne peut pas decompresser
	throw new Exception('Fonctions zip non disponibles');
} else {

	//Update himself
	if (!empty($_REQUEST['spip_loader_update'])) {
		$spip_loader = SL_recuperer_page(_URL_SPIP_LOADER);
		if (defined('_SPIP_LOADER_UPDATE_AUTEURS')) {
			$spip_loader = preg_replace(
				"/(define\(['\"]_SPIP_LOADER_UPDATE_AUTEURS['\"],).*/",
				"$1'" . _SPIP_LOADER_UPDATE_AUTEURS . "');",
				$spip_loader
			);
		}
		if (! @file_put_contents(_SPIP_LOADER_SCRIPT, $spip_loader)) {
			throw new Exception("Impossible d’écrire le nouveau fichier de SPIP Loader.");
		}
		SL_spip_clear_opcode_cache(_SPIP_LOADER_SCRIPT);
		SL_spip_redirige_boucle(_DIR_BASE._SPIP_LOADER_SCRIPT);
	}

	// y a tout ce qu'il faut pour que cela marche
	$dest = '';
	$paquet = _CHEMIN_FICHIER_ZIP;
	if (isset($_REQUEST['dest']) and preg_match('/^[\w_.-]+$/', $_REQUEST['dest'])) {
		$dest = $_REQUEST['dest'];
	}
	if (isset($_REQUEST['chemin']) and $_REQUEST['chemin']) {
		$paquet = urldecode($_REQUEST['chemin']);
	} elseif ($version_installee and !defined('_CHEMIN_FICHIER_ZIP_FORCEE')) {
		if ($branche = SL_lister_branches_proposees(SL_branche_spip($version_installee))) {
			$paquet = $branche['zip'];
		} elseif ((strpos($version_installee, '-dev') !== false) and $branche = SL_lister_branches_proposees('dev')) {
			$paquet = $branche['zip'];
		} else {
			// cette branche n’est plus maintenue...
		}
	}


	if ($paquet and ((strpos($paquet, '../') !== false) or ($paquet and substr($paquet, -4, 4) !== '.zip'))) {
		throw new Exception("chemin incorrect $paquet");
	} else {
		SL_spip_deballe(
			$paquet,
			(isset($_REQUEST['etape']) ? $_REQUEST['etape'] : ''),
			$dest,
			intval(isset($_REQUEST['range']) ? $_REQUEST['range'] : 0) 
		);
		
		SL_creer_repertoires_plugins($droits);
	}
}
