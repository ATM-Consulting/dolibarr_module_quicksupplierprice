<?php

    require("../config.php");
    dol_include_once('/product/class/product.class.php');
    dol_include_once('/fourn/class/fournisseur.product.class.php');

    $get = GETPOST('get');
    $put = GETPOST('put');
    
    $id_prod = (int)GETPOST('idprod');                  // id du produit demandé
    $ref_search= $db->escape(GETPOST('ref_search'));    // ref du produit demandé
    $ref = $db->escape(GETPOST('ref'));                 // ref entrée par l'utilisateur
    $fk_soc = $db->escape(GETPOST('fk_supplier'));      // id du fournisseur
    $price = $db->escape(GETPOST('price'));             // prix entré par l'utilisateur
    $qte = (int)GETPOST('qty');                         // quantité demandée
    $tva = (int)GETPOST('tvatx');                       // taux de tva saisi
    $id_commande = (int)GETPOST('idcmd');               // id de la commande en cours de modification
    
    switch($put){
        case 'updateprice': // création du prix fournisseur
            ob_start();
            
            // On vérifie si la ligne de tarif n'existe pas déjà pour ce fournisseur
            $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."product_fournisseur_price WHERE fk_product=" . $id_prod;
            $sql .= " AND fk_soc=" . $fk_soc;
            $sql .= " AND price=" . $price;
            $sql .= " AND quantity=" . $qte;
            $resq = $db->query($sql);
            
            if($resq->num_rows !== 0){ // s'il existe
                $obj = $db->fetch_object($resq);
                $ret = 0;
            } else { // si on ne trouve rien 
                $product = new ProductFournisseur($db);
                $product->fetch($id_prod, $ref_search);
                
                $fourn = new Fournisseur($db);
                $fourn->fetch($fk_soc);
                
                $ret=$product->update_buyprice($qte , $price, $user, 'HT', $fourn, 1, $ref, $tva, 0, 0, 0);
                $res = $db->query("SELECT MAX(rowid) as 'rowid' FROM ".MAIN_DB_PREFIX."product_fournisseur_price WHERE fk_product=".$product->id);
                $obj = $db->fetch_object($res); 
            }
            
            ob_clean();
                          
            if($ret!=0) print json_encode( array('id'=>$ret,'error'=> $product->error) );
            else {
                print json_encode(  array('id'=> $obj->rowid, 'error'=>'', 'dp_desc'=>$product->description ) );
            }
               
            break;
            
        case 'checkprice': // vérifie s'il y a des prix strictement inférieurs et on en renvoie le nombre
            ob_start();
                      
            $sql = 'SELECT COUNT(*) as total FROM llx_product_fournisseur_price as pfp , llx_societe as s WHERE pfp.entity = 1 AND pfp.fk_soc = s.rowid AND s.status=1 AND pfp.fk_product = '.$id_prod.' AND pfp.price < '.$price;
            $res = $db->query($sql);
            $obj = $db->fetch_object($res);
                                    
            ob_clean();
            print json_encode( array('nb' => $obj->total));
            
            break;
            
        case 'listeprice': // renvoie la liste des prix inférieurs
            
            ob_start();
            
            // on récupère la liste des prix fournisseur pour ce produit
            $product = new ProductFournisseur($db);
            $retour = $product->list_product_fournisseur_price($id_prod, 'pfp.price', 'DESC');
            
            // on génère la liste des prix inférieurs au prix demandé
            $liste = '<form action="'.dol_buildpath('/fourn/commande/card.php', 1).'?id='. $id_commande .'" method="POST">'."\n";
            $liste .= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            $liste .= '<input type="hidden" name="action" value="selectprice">';
            $liste .= '<input type="hidden" name="qty" value="'.$qte.'">';
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

