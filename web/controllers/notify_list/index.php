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

$app->match('/notify_list/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
		'id', 
		'week', 
		'hour', 
		'min', 
		'title', 
		'content', 
		'pic', 
		'important', 
		'valid', 
		'c_time', 
		'u_time', 

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
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `notify_list`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `notify_list`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    foreach($rows_sql as $row_key => $row_sql){
        for($i = 0; $i < count($table_columns); $i++){

		$rows[$row_key][$table_columns[$i]] = $row_sql[$table_columns[$i]];


        }
    }    
    
    $queryData = new queryData();
    $queryData->start = $start;
    $queryData->recordsTotal = $recordsTotal;
    $queryData->recordsFiltered = $recordsTotal;
    $queryData->data = $rows;
    
    return new Symfony\Component\HttpFoundation\Response(json_encode($queryData), 200);
});

$app->match('/notify_list', function () use ($app) {
    
	$table_columns = array(
		'id', 
		'week', 
		'hour', 
		'min', 
		'title', 
		'content', 
		'pic', 
		'important', 
		'valid', 
		'c_time', 
		'u_time', 

    );

    $primary_key = "id";	

    return $app['twig']->render('notify_list/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('notify_list_list');



$app->match('/notify_list/create', function () use ($app) {
    
    $initial_data = array(
		'week' => '', 
		'hour' => '', 
		'min' => '', 
		'title' => '', 
		'content' => '', 
		'pic' => '', 
		'important' => '', 
		'valid' => '', 
		'c_time' => '', 
		'u_time' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);



	$form = $form->add('week', 'text', array('required' => true));
	$form = $form->add('hour', 'text', array('required' => true));
	$form = $form->add('min', 'text', array('required' => true));
	$form = $form->add('title', 'text', array('required' => false));
	$form = $form->add('content', 'text', array('required' => true));
	$form = $form->add('pic', 'text', array('required' => false));
	$form = $form->add('important', 'text', array('required' => true));
	$form = $form->add('valid', 'text', array('required' => true));
	$form = $form->add('c_time', 'text', array('required' => true));
	$form = $form->add('u_time', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `notify_list` (`week`, `hour`, `min`, `title`, `content`, `pic`, `important`, `valid`, `c_time`, `u_time`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['week'], $data['hour'], $data['min'], $data['title'], $data['content'], $data['pic'], $data['important'], $data['valid'], $data['c_time'], $data['u_time']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'notify_list created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('notify_list_list'));

        }
    }

    return $app['twig']->render('notify_list/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('notify_list_create');



$app->match('/notify_list/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `notify_list` WHERE `id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('notify_list_list'));
    }

    
    $initial_data = array(
		'week' => $row_sql['week'], 
		'hour' => $row_sql['hour'], 
		'min' => $row_sql['min'], 
		'title' => $row_sql['title'], 
		'content' => $row_sql['content'], 
		'pic' => $row_sql['pic'], 
		'important' => $row_sql['important'], 
		'valid' => $row_sql['valid'], 
		'c_time' => $row_sql['c_time'], 
		'u_time' => $row_sql['u_time'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);


	$form = $form->add('week', 'text', array('required' => true));
	$form = $form->add('hour', 'text', array('required' => true));
	$form = $form->add('min', 'text', array('required' => true));
	$form = $form->add('title', 'text', array('required' => false));
	$form = $form->add('content', 'text', array('required' => true));
	$form = $form->add('pic', 'text', array('required' => false));
	$form = $form->add('important', 'text', array('required' => true));
	$form = $form->add('valid', 'text', array('required' => true));
	$form = $form->add('c_time', 'text', array('required' => true));
	$form = $form->add('u_time', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `notify_list` SET `week` = ?, `hour` = ?, `min` = ?, `title` = ?, `content` = ?, `pic` = ?, `important` = ?, `valid` = ?, `c_time` = ?, `u_time` = ? WHERE `id` = ?";
            $app['db']->executeUpdate($update_query, array($data['week'], $data['hour'], $data['min'], $data['title'], $data['content'], $data['pic'], $data['important'], $data['valid'], $data['c_time'], $data['u_time'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'notify_list edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('notify_list_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('notify_list/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('notify_list_edit');



$app->match('/notify_list/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `notify_list` WHERE `id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `notify_list` WHERE `id` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'notify_list deleted!',
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

    return $app->redirect($app['url_generator']->generate('notify_list_list'));

})
->bind('notify_list_delete');






