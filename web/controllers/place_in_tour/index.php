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

$app->match('/place_in_tour/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
		'place_in_tour_id', 
		'place_id', 
		'tour_id', 
		'order_number', 
		'day_number', 
		'start_time', 
		'stop_time', 
		'description', 

    );
    
    $table_columns_type = array(
		'int(11)', 
		'int(6)', 
		'int(6)', 
		'int(2)', 
		'int(2)', 
		'time', 
		'time', 
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
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `place_in_tour`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `place_in_tour`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    foreach($rows_sql as $row_key => $row_sql){
        for($i = 0; $i < count($table_columns); $i++){

			if($table_columns[$i] == 'place_id'){
			    $findexternal_sql = 'SELECT `name` FROM `place` WHERE `place_id` = ?';
			    $findexternal_row = $app['db']->fetchAssoc($findexternal_sql, array($row_sql[$table_columns[$i]]));
			    $rows[$row_key][$table_columns[$i]] = $findexternal_row['name'];
			}
			else if($table_columns[$i] == 'tour_id'){
			    $findexternal_sql = 'SELECT `name` FROM `tour` WHERE `tour_id` = ?';
			    $findexternal_row = $app['db']->fetchAssoc($findexternal_sql, array($row_sql[$table_columns[$i]]));
			    $rows[$row_key][$table_columns[$i]] = $findexternal_row['name'];
			}
			else{
			    $rows[$row_key][$table_columns[$i]] = $row_sql[$table_columns[$i]];
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
$app->match('/place_in_tour/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . place_in_tour . " WHERE ".$idfldname." = ?";
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



$app->match('/place_in_tour', function () use ($app) {
    
	$table_columns = array(
		'place_in_tour_id', 
		'place_id', 
		'tour_id', 
		'order_number', 
		'day_number', 
		'start_time', 
		'stop_time', 
		'description', 

    );

    $primary_key = "place_in_tour_id";	

    return $app['twig']->render('place_in_tour/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('place_in_tour_list');



$app->match('/place_in_tour/create', function () use ($app) {
    
    $initial_data = array(
		'place_id' => '', 
		'tour_id' => '', 
		'order_number' => '', 
		'day_number' => '', 
		'start_time' => '', 
		'stop_time' => '', 
		'description' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);

	$options = array();
	$findexternal_sql = 'SELECT `place_id`, `name` FROM `place`';
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['place_id']] = $findexternal_row['name'];
	}
	if(count($options) > 0){
	    $form = $form->add('place_id', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('place_id', 'text', array('required' => true));
	}

	$options = array();
	$findexternal_sql = 'SELECT `tour_id`, `name` FROM `tour`';
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['tour_id']] = $findexternal_row['name'];
	}
	if(count($options) > 0){
	    $form = $form->add('tour_id', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('tour_id', 'text', array('required' => true));
	}



	$form = $form->add('order_number', 'text', array('required' => true));
	$form = $form->add('day_number', 'text', array('required' => true));
	$form = $form->add('start_time', 'text', array('required' => true));
	$form = $form->add('stop_time', 'text', array('required' => true));
	$form = $form->add('description', 'textarea', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `place_in_tour` (`place_id`, `tour_id`, `order_number`, `day_number`, `start_time`, `stop_time`, `description`) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['place_id'], $data['tour_id'], $data['order_number'], $data['day_number'], $data['start_time'], $data['stop_time'], $data['description']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'place_in_tour created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('place_in_tour_list'));

        }
    }

    return $app['twig']->render('place_in_tour/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('place_in_tour_create');



$app->match('/place_in_tour/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `place_in_tour` WHERE `place_in_tour_id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('place_in_tour_list'));
    }

    
    $initial_data = array(
		'place_id' => $row_sql['place_id'], 
		'tour_id' => $row_sql['tour_id'], 
		'order_number' => $row_sql['order_number'], 
		'day_number' => $row_sql['day_number'], 
		'start_time' => $row_sql['start_time'], 
		'stop_time' => $row_sql['stop_time'], 
		'description' => $row_sql['description'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);

	$options = array();
	$findexternal_sql = 'SELECT `place_id`, `name` FROM `place`';
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['place_id']] = $findexternal_row['name'];
	}
	if(count($options) > 0){
	    $form = $form->add('place_id', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('place_id', 'text', array('required' => true));
	}

	$options = array();
	$findexternal_sql = 'SELECT `tour_id`, `name` FROM `tour`';
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['tour_id']] = $findexternal_row['name'];
	}
	if(count($options) > 0){
	    $form = $form->add('tour_id', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('tour_id', 'text', array('required' => true));
	}


	$form = $form->add('order_number', 'text', array('required' => true));
	$form = $form->add('day_number', 'text', array('required' => true));
	$form = $form->add('start_time', 'text', array('required' => true));
	$form = $form->add('stop_time', 'text', array('required' => true));
	$form = $form->add('description', 'textarea', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `place_in_tour` SET `place_id` = ?, `tour_id` = ?, `order_number` = ?, `day_number` = ?, `start_time` = ?, `stop_time` = ?, `description` = ? WHERE `place_in_tour_id` = ?";
            $app['db']->executeUpdate($update_query, array($data['place_id'], $data['tour_id'], $data['order_number'], $data['day_number'], $data['start_time'], $data['stop_time'], $data['description'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'place_in_tour edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('place_in_tour_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('place_in_tour/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('place_in_tour_edit');



$app->match('/place_in_tour/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `place_in_tour` WHERE `place_in_tour_id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `place_in_tour` WHERE `place_in_tour_id` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'place_in_tour deleted!',
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

    return $app->redirect($app['url_generator']->generate('place_in_tour_list'));

})
->bind('place_in_tour_delete');






