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


require_once __DIR__.'/../../vendor/autoload.php';
require_once __DIR__.'/../../src/app.php';


require_once __DIR__.'/admin/index.php';
require_once __DIR__.'/place/index.php';
require_once __DIR__.'/place_image/index.php';
require_once __DIR__.'/place_in_tour/index.php';
require_once __DIR__.'/tour/index.php';
require_once __DIR__.'/tour_date/index.php';
require_once __DIR__.'/user/index.php';



$app->match('/', function () use ($app) {

    return $app['twig']->render('ag_dashboard.html.twig', array());
        
})
->bind('dashboard');


$app->run();