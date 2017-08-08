<?php

    require("../config.php");
    dol_include_once('/product/class/product.class.php');
    dol_include_once('/fourn/class/fournisseur.product.class.php');

    $get = GETPOST('get');
    $put = GETPOST('put');
    
    switch($put){
        case 'updateprice': // création du prix fournisseur
            ob_start();
            
            $id_prod = (int)GETPOST('idprod');
            $ref_search= GETPOST('ref_search');
            
            // On vérifie si la ligne de tarif n'existe pas déjà pour ce fournisseur
            // $sql = 'SELECT COUNT(*) as total FROM llx_product_fournisseur_price as pfp , llx_societe as s WHERE pfp.entity = 1 AND pfp.fk_soc = s.rowid AND s.status=1 AND pfp.fk_product = '.$id_prod.' AND pfp.price < '.$price;
            $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."product_fournisseur_price WHERE fk_product=" . $db->escape(GETPOST('idprod'));
            $sql .= " AND fk_soc=" . $db->escape(GETPOST('fk_supplier'));
            $sql .= " AND price=" . $db->escape(GETPOST('price'));
            $sql .= " AND quantity=" . $db->escape(GETPOST('qty'));
            $resq = $db->query($sql);
            
            if($resq){ // s'il existe
                $obj = $db->fetch_object($resq);
                $ret = 0;
            } else { // si on ne trouve rien 
                $product = new ProductFournisseur($db);
                $product->fetch($id_prod, $ref_search);
                
                $fourn = new Fournisseur($db);
                $fourn->fetch(GETPOST('fk_supplier'));
                
                $ret=$product->update_buyprice(GETPOST('qty'), GETPOST("price"), $user, 'HT', $fourn, 1, GETPOST('ref'), GETPOST('tvatx'), 0, 0, 0);
                $res = $db->query("SELECT MAX(rowid) as 'rowid' FROM ".MAIN_DB_PREFIX."product_fournisseur_price WHERE fk_product=".$product->id);
                $obj = $db->fetch_object($res); 
            }
            
            ob_clean();
                          
            if($ret!=0) print json_encode( array('id'=>$ret,'error'=> $product->error) );
            else {
                print json_encode(  array('id'=> $obj->rowid, 'error'=>'', 'dp_desc'=>$product->description ) );
            }
               
            break;
            
        case 'checkprice': // vérifie s'il y a des prix inférieurs 
            ob_start();
            
            // on récupère le produit
            $id_prod = (int)GETPOST('idprod');
            $ref_search= GETPOST('ref_search');
            $price = GETPOST('price');
                       
            $sql = 'SELECT COUNT(*) as total FROM llx_product_fournisseur_price as pfp , llx_societe as s WHERE pfp.entity = 1 AND pfp.fk_soc = s.rowid AND s.status=1 AND pfp.fk_product = '.$id_prod.' AND pfp.price < '.$price;
            $res = $db->query($sql);
            $obj = $db->fetch_object($res);
                                    
            ob_clean();
            print json_encode( array('nb' => $obj->total));
            
            break;
            
        case 'listeprice': // renvoie la liste des prix inférieurs
            
            ob_start();
            $product = new ProductFournisseur($db);
            
            // on récupère l'id produit
            $id_prod = (int)GETPOST('idprod');
            $ref_search= GETPOST('ref_search');
            $price = GETPOST('price');
            $retour = $product->list_product_fournisseur_price($id_prod, 'pfp.price', 'DESC');
            $liste = '<form action="'.dol_buildpath('/fourn/commande/card.php', 1).'?id='.GETPOST('idcmd').'" method="POST">'."\n";
            $liste .= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            $liste .= '<input type="hidden" name="action" value="selectprice">';
            $liste .= '<input type="hidden" name="qty" value="'.GETPOST('qty').'">';
            $liste .= '<div><p>Plusieurs prix sont disponibles pour ce produit veuillez valider le prix saisie ou choisir le produit chez un autre fournisseur</p></div>';
            $liste .= '<div class="div-table-responsive"><table class="noborder noshadow" width="100%"><thead>';
            $liste .= '<tr class="liste_titre"><td width="20%">Choix</td><td>Fournisseur</td><td>Prix</td></tr>';
            $liste .= '</thead><tbody>';
            
            $liste .= '<tr><td><input type="radio" name="prix" value="saisie"></td>';
            $liste .= '<td>le prix que vous avez saisi chez ...</td>';
            $liste .= '<td>' . $price . '</td></tr>';
            
            foreach ($retour as $prix){
                if($prix->fourn_price < $price){
                    $liste .= '<tr><td><input type="radio" name="prix" value="'.$prix->product_fourn_price_id.'"></td>';
                    $liste .= '<td>'. $prix->fourn_name .'</td>';
                    $liste .= '<td>' . number_format($prix->fourn_price, 2) . '</td></tr>';
                }
            }
            
            $liste .= '</tbody></table></div>';
            
            $liste .= '<div class="center">';
            $liste .= '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
            $liste .= '</div>';
            $liste .= '</form>';
            
            ob_clean();
            
            print json_encode(array('liste' => $liste));
            
            break;
        
    }

