<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
 * Copyright (C) 2018 Nicolas ZABOURI	<info@inovea-conseil.com>
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
 * \file    class/actions_quicksupplierprice.class.php
 * \ingroup quicksupplierprice
 * \brief   This file is an example hook overload class file
 *          Put some comments here
 */

/**
 * Class Actionsquicksupplierprice
 */
require_once __DIR__ . '/../backport/v19/core/class/commonhookactions.class.php';
class Actionsquicksupplierprice extends quicksupplierprice\RetroCompatCommonHookActions
{
	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var array Errors
	 */
	public $errors = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          &$action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function doActions($parameters, &$object, &$action, $hookmanager)
	{
	    $TContext = explode(':', $parameters['context']);
	    if (in_array('ordersuppliercard', $TContext) || in_array('invoicesuppliercard', $TContext))
	    {

            global $db, $user, $langs, $conf;
            $langs->load('quicksupplierprice@quicksupplierprice');

            $action = GETPOST('action','alpha');

            if($action == 'selectpriceQSP'){
                $ligneprix = GETPOST('prix', 'int'); // id de la ligne dans llx_product_fournisseur_price
                $qte = GETPOST('qty', 'int');        // quantité à commander
                $err = 0;

                if(empty($ligneprix)){
                    $err++;
                    setEventMessage($langs->trans('NoLinePrice'), 'errors');
                }
                if(empty($qte) || $qte == 0){
                    $err++;
                    setEventMessage($langs->trans('NoQte'), 'errors');
                }

                if($err){
                    return 1;
                }

                // récupère la ligne prix fournisseur avec son id
                $pfp = new ProductFournisseur($db);
                $pfp->fetch_product_fournisseur_price($ligneprix);

                // récupère le produit pour connaitre son type
                $product = new Product($db);
                $product->fetch($pfp->id);

                // si le fournisseur de la commande en cours est le même que la ligne produit sélectionnée, on ajoute une ligne à cette commande
                if($object->fourn_id == $pfp->fourn_id){
                    $object->addline(
                        ''
                    	, (((float)DOL_VERSION>=6)?$pfp->fourn_price:$pfp->price)
                        , $qte
                        ,$pfp->fourn_tva_tx
                        ,0
                        ,0
                        ,$pfp->fk_product
                        ,$pfp->id
                        ,$pfp->ref_supplier
                        ,$pfp->fourn_remise_percent
                        ,'HT'
                        ,''
                        ,$product->type
                        );

                    // regénérer le pdf pour que la ligne ajoutée apparaisse
                    $result=$object->generateDocument($object->modelpdf, $langs, $hidedetails, $hidedesc, $hideref);
                    if ($result < 0) dol_print_error($db,$result);

                    setEventMessage($langs->trans('CommandLineAdded'), 'mesgs');

                } else {
                    // crée une nouvelle commande fournisseur avec comme fournisseur celui de la ligne choisie
	                $commande = new CommandeFournisseur($db);
	                $commande->entity = $conf->entity;
	                $commande->socid = $pfp->fourn_id;

                    // crée la ligne produit dans cette commande
	                $commande->lines[0] = new CommandeFournisseurLigne($db);

	                $commande->lines[0]->qty = $qte;
	                $commande->lines[0]->tva_tx = $pfp->fourn_tva_tx;
	                $commande->lines[0]->fk_product = $pfp->fk_product;
	                $commande->lines[0]->ref_fourn = $pfp->ref_supplier;   // $this->lines[$i]->ref_fourn comes from field ref into table of lines. Value may ba a ref that does not exists anymore, so we first try with value of product
	                $commande->lines[0]->remise_percent = $pfp->fourn_remise_percent;
	                $commande->lines[0]->product_type = $product->type;
	                $commande->lines[0]->info_bits = 0;
	                $commande->lines[0]->fk_unit = $pfp->fk_unit;

	                if((float)DOL_VERSION>=6) {
	                	$commande->lines[0]->subprice= $pfp->fourn_price;
	                	$commande->lines[0]->price= $pfp->fourn_price;
	                }

	                $commande->create($user);
	                setEventMessage($langs->trans('NewCommandeGen') . ' ref : ' . $commande->getNomUrl(), 'warnings');
                }

            }
	    }
	}

	function formAddObjectLine($parameters, &$object, &$action, $hookmanager)
	{
		$TContext = explode(':', $parameters['context']);
		if (in_array('ordersuppliercard', $TContext) || in_array('invoicesuppliercard', $TContext))
		{
		    global $db,$conf,$mysoc,$langs;
            $form=new Form($db);

            $seller = new Societe($db);
            $seller->fetch($object->socid);

            $colspan = in_array('ordersuppliercard', $TContext) ? 3 : 4;

            ?>
            <tr class="liste_titre nodrag nodrop">
		<?php if (getDolGlobalInt('MAIN_VIEW_LINE_NUMBER')) { ?>
                    <td></td>
                <?php } ?>
                <td>Ajout nouvelle ligne avec prix à la volée</td>
                <td align="right">TVA</td>
                <td align="right">Qté</td>
                <td align="right">Total HT</td>
                <td align="right">Réf.</td>
                <td align="right">Remise</td>
                <td colspan="<?php echo $colspan+1 ?>">&nbsp;</td>
            </tr>
            <tr class="impair">
		<?php if (getDolGlobalInt('MAIN_VIEW_LINE_NUMBER')) { ?>
                    <td></td>
                <?php } ?>
                <td><?php
                    $form->select_produits(GETPOST('idprod_qsp','int'), 'idprod_qsp', '', $conf->product->limit_size, 1, -1);
                    ?></td>
                <td align="right"><?php
                    echo $form->load_tva('tva_tx_qsp',(isset($_POST["tva_tx_qsp"])?$_POST["tva_tx_qsp"]:-1),$seller,$mysoc,0,0,'',false,1);
                ?></td>
                <td align="right"><input type="text" value="1" class="flat" id="qty_qsp" name="qty_qsp" size="2"></td>
                <td align="right"><input type="text" value="" class="flat" id="price_ht_qsp" name="price_ht_qsp" size="5"></td>
                <td align="right"><input type="text" value="" class="flat" id="ref_qsp" name="ref_qsp" size="5"></td>
                <td align="right"><input type="text" value="" class="flat" id="remise" name="remise" size="5"></td>
                <td align="right">&nbsp;</td>
                <td colspan="<?php echo $colspan ?>"><input type="button" name="bt_add_qsp" id="bt_add_qsp" value="Créer le prix et ajouter" class="button"/></td>
            </tr>

            <script type="text/javascript">
				$(document).ready(function() {

					// Fonction qui gère l'action sur clic du bouton
					$("#bt_add_qsp").click(function() {
						// Vérifie si un produit est sélectionné
						if($("#idprod_qsp").val() == 0){
							alert('Aucun produit sélectionné');
						} else {
							<?php
							// Vérifie si la recherche de meilleurs prix est activée
							if(getDolGlobalInt('QSP_SEARCH_PRICES')) { ?>
							checkPrice(); // Si la recherche de prix est activée, on vérifie
							<?php } else { ?>
							updatePrice(); // Sinon on met à jour immédiatement
							<?php } ?>
						}

						// Vérifie si une option liée à un autre ordre de fabrication est sélectionnée
						if($("#options_linked_of1").val() !== 0){
							let secondOFSelectValue = $("#options_linked_of1").val();
							$("#options_linked_of").val(secondOFSelectValue);
						}
					});

					// Fonction de vérification des prix
					function checkPrice(){
						$.ajax({
							url: "<?php echo dol_buildpath('/quicksupplierprice/script/interface.php',1) ?>",
							data: {
								put: 'checkprice',
								idprod: $("#idprod_qsp").val(),
								ref_search: $('#search_idprod_qsp').val(),
								fk_supplier: <?php echo !empty($object->socid) ? $object->socid : $object->fk_soc ?>,
								fk_element: <?php echo $object->id ?>,
								price: $("#price_ht_qsp").val(),
								qty: $("#qty_qsp").val(),
								tvatx: $("#tva_tx_qsp").val(),
								ref: $("#ref_qsp").val(),
								remise: $("#remise").val(),
								element: "<?php echo isset($object->element) ? addslashes($object->element) : '' ?>"
							},
							method: "post",
							dataType: 'json'
						}).done(function(data) {
							if(data.nb == 0) { // Si aucun prix inférieur, on met à jour le prix
								updatePrice();
							} else { // Si le produit est moins cher ailleurs, on propose la liste des prix inférieurs
								listPrice(data);
							}
						});
					}

					// Fonction qui affiche la liste des prix inférieurs dans une popin (#selectfourn)
					function listPrice(data){
						if($('#selectFourn').length == 0) {
							$('body').append('<div id="selectFourn" title="<?php echo $langs->transnoentities('PriceSelection'); ?>"></div>');
						}

						$('#selectFourn').html(data.liste);

						// Gestion de la soumission du formulaire de la popin
						$('#selectFourn form').submit(function(e){
							if($('input[name="prix"]:checked').val() == 'saisie'){
								// Si l'utilisateur choisit de saisir son prix même s'il y en a de moins chers
								e.preventDefault();
								$('#selectFourn').dialog('close');
								updatePrice(); // Crée le prix
							}
						});

						$('#selectFourn').dialog({
							modal: true,
							width: '80%'
						});
					}

					// Fonction qui met à jour le prix
					function updatePrice(){
						$.ajax({
							url: "<?php echo dol_buildpath('/quicksupplierprice/script/interface.php',1) ?>",
							data: {
								put: 'updateprice',
								idprod: $("#idprod_qsp").val(),
								ref_search: $('#search_idprod_qsp').val(),
								fk_supplier: <?php echo !empty($object->socid) ? $object->socid : $object->fk_soc ?>,
								price: $("#price_ht_qsp").val(),
								qty: $("#qty_qsp").val(),
								tvatx: $("#tva_tx_qsp").val(),
								ref: $("#ref_qsp").val(),
								remise: $("#remise").val(),
								token: $("input[name='token']").val(),
								fk_element: <?php echo $object->id ?>,
								element: "<?php echo isset($object->element) ? addslashes($object->element) : '' ?>"
							},
							method: "post",
							dataType: 'json'
						}).done(function(data) {
							if(data.retour > 0) { // Si la réponse est positive, on a un ID du prix-produit-fournisseur
								setforpredef();

								$("#dp_desc").val(data.dp_desc);
								$("#idprodfournprice").replaceWith('<input type="hidden" name="idprodfournprice" id="idprodfournprice" value="'+data.retour+'" />');
								if($("#qty_qsp").val() > 0) {
									$("#price_ht").val($("#price_ht_qsp").val() / $("#qty_qsp").val());
								}
								$("#qty").val($("#qty_qsp").val());

								$("#addline").click(); // Ajoute la ligne à la commande
							} else { // Erreur dans la saisie
								alert("Veuillez vérifer votre saisie");
								console.log(data.retour, data.error); // Code erreur retourné par la méthode de création de ligne prix
							}
						});
					}

					// Détection de l'appui sur "Entrée" dans le champ #idprod_qsp
					$("#price_ht_qsp").keydown(function(event) {
						if (event.key === "Enter") {
							event.preventDefault(); // Empêche la soumission classique du formulaire
							// Appelle la même logique que le clic sur le bouton
							console.log($("#idprod_qsp").val());

							if($("#idprod_qsp").val() < 0){
								alert('Aucun produit sélectionné');
							} else {
								<?php
								// Vérifie si la recherche de meilleurs prix est activée
								if(getDolGlobalInt('QSP_SEARCH_PRICES')) { ?>
								checkPrice(); // Si la recherche de prix est activée, on vérifie
								<?php } else { ?>
								updatePrice(); // Sinon on met à jour immédiatement
								<?php } ?>
							}

							// Vérifie si une option liée à un autre ordre de fabrication est sélectionnée
							if($("#options_linked_of1").val() !== 0){
								let secondOFSelectValue = $("#options_linked_of1").val();
								$("#options_linked_of").val(secondOFSelectValue);
							}
						}
					});

				});



			</script>
            <?php
				$objectline = in_array('ordersuppliercard', $TContext) ? new CommandeFournisseurLigne($db) : new SupplierInvoiceLine($db);
				$extrafieldsline = new ExtraFields($db);
				$extralabelslines=$extrafieldsline->fetch_name_optionals_label($object->table_element_line);
				print $objectline->showOptionals($extrafieldsline, 'edit', array('style'=>'', 'colspan'=>6), !getDolGlobalInt('MAIN_EXTRAFIELDS_IN_ONE_TD')?'':1);
		}

		return 0; // or return 1 to replace standard code

	}

	function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
	{
		$TContext = explode(':', $parameters['context']);

		if (in_array('ordersuppliercard', $TContext) || in_array('invoicesuppliercard', $TContext))
		{
			if(version_compare(DOL_VERSION,'13.0.0') > 0)
			{
			?>
			<script>
				$(document).ready(function(){
					let qsp_tr = $("#search_idprod_qsp").closest("tr");
					let qsp_title = qsp_tr.prev();
					let qsp_script = qsp_title.next();
					let qsp_optionnals = qsp_script.next();

					let afterBlock = "#trlinefordates";
					if ($(afterBlock).length == 0) afterBlock = ".liste_titre_create";
					$(afterBlock).after(qsp_optionnals);
					$(afterBlock).after(qsp_script);
					$(afterBlock).after(qsp_tr);
					$(afterBlock).after(qsp_title);
				});

			</script>
			<?php
			}
		}

		return 0;
	}

}
