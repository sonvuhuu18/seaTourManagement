<?php

/*
 * This file is part of the CRUD Admin Generator project.
 *
 * Author: Jon Segador <jonseg@gmail.com>
 * Web: http://crud-admin-generator.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


require_once __DIR__.'/../../../vendor/autoload.php';
require_once __DIR__.'/../../../src/app.php';

use Symfony\Component\Validator\Constraints as Assert;

$app->match('/place/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
    $start = 0;
    $vars = $request->query->all();
    $qsStart = (int)$vars["start"];
    $search = $vars["search"];
    $order = $vars["order"];
    $columns = $vars["columns"];
    $qsLength = (int)$vars["length"];    
    
    if($qsStart) {
        $start = $qsStart;
    }    
	
    $index = $start;   
    $rowsPerPage = $qsLength;
       
    $rows = array();
    
    $searchValue = $search['value'];
    $orderValue = $order[0];
    
    $orderClause = "";
    if($orderValue) {
        $orderClause = " ORDER BY ". $columns[(int)$orderValue['column']]['data'] . " " . $orderValue['dir'];
    }
    
    $table_columns = array(
		'place_id', 
		'name', 
		'address', 
		'type', 
		'description', 

    );
    
    $table_columns_type = array(
		'int(6)', 
		'varchar(32)', 
		'varchar(128)', 
		'varchar(16)', 
		'text', 

    );    
    
    $whereClause = "";
    
    $i = 0;
    foreach($table_columns as $col){
        
        if ($i == 0) {
           $whereClause = " WHERE";
        }
        
        if ($i > 0) {
            $whereClause =  $whereClause . " OR"; 
        }
        
        $whereClause =  $whereClause . " " . $col . " LIKE '%". $searchValue ."%'";
        
        $i = $i + 1;
    }
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `place`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `place`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    foreach($rows_sql as $row_key => $row_sql){
        for($i = 0; $i < count($table_columns); $i++){

		if( $table_columns_type[$i] != "blob") {
				$rows[$row_key][$table_columns[$i]] = $row_sql[$table_columns[$i]];
		} else {				if( !$row_sql[$table_columns[$i]] ) {
						$rows[$row_key][$table_columns[$i]] = "0 Kb.";
				} else {
						$rows[$row_key][$table_columns[$i]] = " <a target='__blank' href='menu/download?id=" . $row_sql[$table_columns[0]];
						$rows[$row_key][$table_columns[$i]] .= "&fldname=" . $table_columns[$i];
						$rows[$row_key][$table_columns[$i]] .= "&idfld=" . $table_columns[0];
						$rows[$row_key][$table_columns[$i]] .= "'>";
						$rows[$row_key][$table_columns[$i]] .= number_format(strlen($row_sql[$table_columns[$i]]) / 1024, 2) . " Kb.";
						$rows[$row_key][$table_columns[$i]] .= "</a>";
				}
		}

        }
    }    
    
    $queryData = new queryData();
    $queryData->start = $start;
    $queryData->recordsTotal = $recordsTotal;
    $queryData->recordsFiltered = $recordsTotal;
    $queryData->data = $rows;
    
    return new Symfony\Component\HttpFoundation\Response(json_encode($queryData), 200);
});




/* Download blob img */
$app->match('/place/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . place . " WHERE ".$idfldname." = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($rowid));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('menu_list'));
    }

    header('Content-Description: File Transfer');
    header('Content-Type: image/jpeg');
    header("Content-length: ".strlen( $row_sql[$fieldname] ));
    header('Expires: 0');
    header('Cache-Control: public');
    header('Pragma: public');
    ob_clean();    
    echo $row_sql[$fieldname];
    exit();
   
    
});



$app->match('/place', function () use ($app) {
    
	$table_columns = array(
		'place_id', 
		'name', 
		'address', 
		'type', 
		'description', 

    );

    $primary_key = "place_id";	

    return $app['twig']->render('place/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('place_list');



$app->match('/place/create', function () use ($app) {
    
    $initial_data = array(
		'name' => '', 
		'address' => '', 
		'type' => '', 
		'description' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);



	$form = $form->add('name', 'text', array('required' => true));
	$form = $form->add('address', 'text', array('required' => true));
	$form = $form->add('type', 'text', array('required' => true));
	$form = $form->add('description', 'textarea', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `place` (`name`, `address`, `type`, `description`) VALUES (?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['name'], $data['address'], $data['type'], $data['description']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'place created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('place_list'));

        }
    }

    return $app['twig']->render('place/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('place_create');



$app->match('/place/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `place` WHERE `place_id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('place_list'));
    }

    
    $initial_data = array(
		'name' => $row_sql['name'], 
		'address' => $row_sql['address'], 
		'type' => $row_sql['type'], 
		'description' => $row_sql['description'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);


	$form = $form->add('name', 'text', array('required' => true));
	$form = $form->add('address', 'text', array('required' => true));
	// $form = $form->add('type', 'text', array('required' => true));
    $form = $form->add('type', 'choice', array(
            'required' => true,
            'choices' => array('Airport' => 'Airport', 'Restaurant' => 'Restaurant', 'Attraction' => 'Attraction'),
            'expanded' => false,
            'constraints' => new Assert\Choice(array_keys($options))
        ));
	$form = $form->add('description', 'textarea', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `place` SET `name` = ?, `address` = ?, `type` = ?, `description` = ? WHERE `place_id` = ?";
            $app['db']->executeUpdate($update_query, array($data['name'], $data['address'], $data['type'], $data['description'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'place edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('place_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('place/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('place_edit');



$app->match('/place/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `place` WHERE `place_id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `place` WHERE `place_id` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'place deleted!',
            )
        );
    }
    else{
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );  
    }

    return $app->redirect($app['url_generator']->generate('place_list'));

})
->bind('place_delete');






