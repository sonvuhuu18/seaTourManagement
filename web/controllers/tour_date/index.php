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

$app->match('/tour_date/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
		'tour_date_id', 
		'tour_id', 
		'date', 
		'slot_available', 

    );
    
    $table_columns_type = array(
		'int(6)', 
		'int(6)', 
		'date', 
		'int(3)', 

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
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `tour_date`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `tour_date`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    foreach($rows_sql as $row_key => $row_sql){
        for($i = 0; $i < count($table_columns); $i++){

			if($table_columns[$i] == 'tour_id'){
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
$app->match('/tour_date/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . tour_date . " WHERE ".$idfldname." = ?";
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



$app->match('/tour_date', function () use ($app) {
    
	$table_columns = array(
		'tour_date_id', 
		'tour_id', 
		'date', 
		'slot_available', 

    );

    $primary_key = "tour_date_id";	

    return $app['twig']->render('tour_date/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('tour_date_list');



$app->match('/tour_date/create', function () use ($app) {
    
    $initial_data = array(
		'tour_id' => '', 
		'date' => '', 
		'slot_available' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);

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



	$form = $form->add('date', 'text', array('required' => true));
	$form = $form->add('slot_available', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `tour_date` (`tour_id`, `date`, `slot_available`) VALUES (?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['tour_id'], $data['date'], $data['slot_available']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'tour_date created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('tour_date_list'));

        }
    }

    return $app['twig']->render('tour_date/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('tour_date_create');



$app->match('/tour_date/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `tour_date` WHERE `tour_date_id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('tour_date_list'));
    }

    
    $initial_data = array(
		'tour_id' => $row_sql['tour_id'], 
		'date' => $row_sql['date'], 
		'slot_available' => $row_sql['slot_available'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);

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


	$form = $form->add('date', 'text', array('required' => true));
	$form = $form->add('slot_available', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `tour_date` SET `tour_id` = ?, `date` = ?, `slot_available` = ? WHERE `tour_date_id` = ?";
            $app['db']->executeUpdate($update_query, array($data['tour_id'], $data['date'], $data['slot_available'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'tour_date edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('tour_date_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('tour_date/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('tour_date_edit');



$app->match('/tour_date/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `tour_date` WHERE `tour_date_id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `tour_date` WHERE `tour_date_id` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'tour_date deleted!',
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

    return $app->redirect($app['url_generator']->generate('tour_date_list'));

})
->bind('tour_date_delete');






