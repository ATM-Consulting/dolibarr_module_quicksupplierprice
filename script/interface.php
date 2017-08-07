<?php

    require("../config.php");
    dol_include_once('/product/class/product.class.php');
    dol_include_once('/fourn/class/fournisseur.product.class.php');

    $get = GETPOST('get');
    $put = GETPOST('put');
    
    switch($put){
        case 'updateprice': // création du prix fournisseur
            ob_start();
            $product = new ProductFournisseur($db);
            
            $id_prod = (int)GETPOST('idprod');
            $ref_search= GETPOST('ref_search');
            $product->fetch($id_prod, $ref_search);
            
            $npr = preg_match('/\*/', GETPOST('tvatx')) ? 1 : 0 ;
            
            $fourn = new Fournisseur($db);
            $fourn->fetch(GETPOST('fk_supplier'));
            
            $ret=$product->update_buyprice(GETPOST('qty'), GETPOST("price"), $user, 'HT', $fourn, 1, GETPOST('ref'), GETPOST('tvatx'), 0, 0, 0);
            
            $res = $db->query("SELECT MAX(rowid) as 'rowid' FROM ".MAIN_DB_PREFIX."product_fournisseur_price WHERE fk_product=".$product->id);
            $obj = $db->fetch_object($res);  
                
            ob_clean();
            
              
            if($ret!=0) print json_encode( array('id'=>$ret,'error'=> $product->error) );
            else {
                print json_encode(  array('id'=> $obj->rowid, 'error'=>'', 'dp_desc'=>$product->description ) );
            }
               
            break;
            
        case 'checkprice': // vérifie s'il y a des prix inférieurs 
            ob_start();
            $product = new ProductFournisseur($db); // pas utilisé dans cette partie
            
            // on récupère le produit
            $id_prod = (int)GETPOST('idprod');
            $ref_search= GETPOST('ref_search');
            $price = GETPOST('price');
            $product->list_product_fournisseur_price($id_prod);
                       
            $sql = 'SELECT COUNT(*) as total FROM llx_product_fournisseur_price as pfp , llx_societe as s WHERE pfp.entity = 1 AND pfp.fk_soc = s.rowid AND s.status=1 AND pfp.fk_product = '.$id_prod.' AND pfp.price < '.$price;
            $res = $db->query($sql);
            $obj = $db->fetch_object($res);
                                    
            ob_clean();
            print json_encode( array('nb' => $obj->total));
            
            break;
            
        case 'listeprice': // renvoie la liste des prix inférieurs
             /*
             $sql = 'SELECT s.nom as supplier_name, s.rowid as fourn_id, pfp.rowid as product_fourn_pri_id, pfp.ref_fourn, pfp.fk_product as product_fourn_id, pfp.fk_supplier_price_expression, pfp.price, pfp.quantity, pfp.unitprice, pfp.remise_percent, pfp.remise, pfp.tva_tx, pfp.fk_availability, pfp.charges, pfp.unitcharges, pfp.info_bits, pfp.delivery_time_days, pfp.supplier_reputation';
             $sql .= 'FROM llx_product_fournisseur_price as pfp , llx_societe as s';
             $sql .= 'WHERE pfp.entity IN ('.getEntity('productprice').')';
             $sql .= 'AND pfp.fk_soc = s.rowid';
             $sql .= 'AND s.status=1';
             $sql .= 'AND pfp.fk_product = ' . $id_prod;
             $sql .= 'AND pfp.price < ' . $price ;
             $sql .= 'ORDER BY pfp.price DESC';
             
             $res = $db->query($sql);
            */
            ob_start();
            $product = new ProductFournisseur($db);
            
            // on récupère le produit
            $id_prod = (int)GETPOST('idprod');
            $ref_search= GETPOST('ref_search');
            $price = GETPOST('price');
            $retour = $product->list_product_fournisseur_price($id_prod, 'pfp.price');
            $liste = '';
            
            
            ob_clean();
            
            print json_encode(array('liste' => $retour));
            
            break;
        
    }

