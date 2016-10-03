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
 * \file    class/actions_quicksupplierprice.class.php
 * \ingroup quicksupplierprice
 * \brief   This file is an example hook overload class file
 *          Put some comments here
 */

/**
 * Class Actionsquicksupplierprice
 */
class Actionsquicksupplierprice
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
	function formAddObjectLine($parameters, &$object, &$action, $hookmanager)
	{
		$TContext = explode(':', $parameters['context']);
		if (in_array('ordersuppliercard', $TContext) || in_array('invoicesuppliercard', $TContext))
		{
		    global $db,$conf,$mysoc;
            $form=new Form($db);

            $seller = new Societe($db);
            $seller->fetch($object->thirdparty->id);

            $colspan = in_array('ordersuppliercard', $TContext) ? 3 : 4;

            ?>
            <tr class="liste_titre nodrag nodrop">
                <td>Ajout nouvelle ligne avec prix à la volée</td>
                <td align="right">TVA</td>
                <td align="right">Qté</td>
                <td align="right">Total HT</td>
                <td align="right">Réf.</td>
                <td colspan="<?php echo $colspan+1 ?>">&nbsp;</td>
            </tr>
            <tr class="impair">
                <td><?php
                    $form->select_produits(GETPOST('idprod_qsp'), 'idprod_qsp', '', $conf->product->limit_size, 1, -1);
                    ?></td>
                <td align="right"><?php
                    echo $form->load_tva('tva_tx_qsp',(isset($_POST["tva_tx_qsp"])?$_POST["tva_tx_qsp"]:-1),$seller,$mysoc);
                ?></td>
                <td align="right"><input type="text" value="1" class="flat" id="qty_qsp" name="qty_qsp" size="2"></td>
                <td align="right"><input type="text" value="" class="flat" id="price_ht_qsp" name="price_ht_qsp" size="5"></td>
                <td align="right"><input type="text" value="" class="flat" id="ref_qsp" name="ref_qsp" size="5"></td>
                <td align="right">&nbsp;</td>
                <td colspan="<?php echo $colspan ?>"><input type="button" name="bt_add_qsp" id="bt_add_qsp" value="Créer le prix et ajouter" class="button"/></td>
            </tr>
            <script type="text/javascript">
                $(document).ready(function() {
                    $("#bt_add_qsp").click(function() {
                        $(this).fadeOut();

                        $.ajax({
                            url : "<?php echo dol_buildpath('/quicksupplierprice/script/interface.php',1) ?>"
                            ,data:{
                                put:'updateprice'
                                ,idprod:$("#idprod_qsp").val()
                                ,ref_search:$('#search_idprod_qsp').val()
                                ,fk_supplier:<?php echo !empty($object->socid) ? $object->socid : $object->fk_soc ?>
                                ,price:$("#price_ht_qsp").val()
                                ,qty:$("#qty_qsp").val()
                                ,tvatx:$("#tva_tx_qsp").val()
                                ,ref:$("#ref_qsp").val()
                            }
                            ,method:"post"
                            ,dataType:'json'
                        }).done(function(data) {
                            console.log(data);
                            if(data.id>0) {

                                setforpredef();

                                $("#dp_desc").val( data.dp_desc );
                                $("#idprodfournprice").replaceWith('<input type="hidden" name="idprodfournprice" id="idprodfournprice" value="'+data.id+'" />' );

                                $("#qty").val($("#qty_qsp").val());

                                $("#addline").click();
                            }
                            else{
                                alert("Il y a une erreur dans votre saisie : "+data.error);
                            }

                        });
                    });

                });




            </script>
            <?php

		}

		return 0; // or return 1 to replace standard code

	}
}