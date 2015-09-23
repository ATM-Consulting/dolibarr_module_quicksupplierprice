<?php

    require("../config.php");
    dol_include_once('/product/class/product.class.php');
    dol_include_once('/fourn/class/fournisseur.product.class.php');

    $get = GETPOST('get');
    $put = GETPOST('put');
    
    switch($put){
        case 'updateprice':
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
            
              
            if($ret!=0) __out( array('id'=>$ret,'error'=> $product->error) ,'json');
            else {
                __out( array('id'=> $obj->rowid, 'error'=>'', 'dp_desc'=>$product->description ) ,'json' );
            }    
                
            break;
        
    }

