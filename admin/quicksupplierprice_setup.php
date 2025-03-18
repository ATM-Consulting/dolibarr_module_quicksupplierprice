<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		admin/quicksupplierprice.php
 * 	\ingroup	quicksupplierprice
 * 	\brief		This file is an example module setup page
 * 				Put some comments here
 */
// Dolibarr environment
$res = @include("../../main.inc.php"); // From htdocs directory
if (! $res) {
    $res = @include("../../../main.inc.php"); // From "custom" directory
}

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/quicksupplierprice.lib.php';

// Translations
$langs->load("quicksupplierprice@quicksupplierprice");

$newToken = function_exists('newToken') ? newToken() : $_SESSION['newtoken'];

// Access control
if (! $user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');

if(! class_exists('FormSetup')) {
	// une Pr est en cour pour fixer certains elements de la class en V16 (car c'est des fix/new)
	if(versioncompare(explode('.', DOL_VERSION), array(15)) < 0 && ! class_exists('FormSetup')) {
		require_once __DIR__.'/../backport/v16/core/class/html.formsetup.class.php';
	}
	else {
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsetup.class.php';
	}
}
$formSetup = new FormSetup($db);

$formSetup->newItem('QSP_SEARCH_PRICES')->setAsYesNo();

/*
 * Actions
 */

if($action == 'update' && ! empty($formSetup) && is_object($formSetup) && ! empty($user->admin)) {
	$formSetup->saveConfFromPost();
	header('Location:'.$_SERVER['PHP_SELF']);
	exit;
}

/*
 * View
 */

$form = new Form($db);

$help_url = '';
$page_name = 'quicksupplierpriceSetup';

llxHeader('', $langs->trans($page_name), $help_url);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans('BackToModuleList').'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = quicksupplierpriceAdminPrepareHead();
print dol_get_fiche_head($head, 'ndf', $langs->trans($page_name), -1, 'quicksupplierprice.svg@quicksupplierprice');

// Setup page goes here
echo '<span class="opacitymedium">'.$langs->trans('quickcustomerprice').'</span><br><br>';

if($action == 'edit') {
	print $formSetup->generateOutput(true);
	print '<br>';
}
else {
	if(! empty($formSetup->items)) {
		print $formSetup->generateOutput();

		print '<div class="tabsAction">';
		print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=edit&token='.newToken().'">'.$langs->trans('Modify').'</a>';
		print '</div>';
	}
	else {
		print '<br>'.$langs->trans('NothingToSetup');
	}
}

// Page end
print dol_get_fiche_end();

llxFooter();

$db->close();
