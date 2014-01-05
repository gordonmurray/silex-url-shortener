<?php

require_once __DIR__ . '/../vendor/autoload.php';

class R extends RedBean_Facade
{
} // simple reference to facade

R::setup('mysql:host=localhost;dbname=sulner', 'root', '');

$app = new Silex\Application();

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app['debug'] = true;

$app->register(new Silex\Provider\TwigServiceProvider(), array('twig.path' => __DIR__ . '/views',));

$app->get(
    '',
    function () use ($app) {
        return $app['twig']->render('form.html.twig');
    }
);

$app->post(
    '/shorten',
    function (Request $request) use ($app) {

        /*
         * retrieve the long_url to shorten from the form
         */
        $long_url = $request->get('long_url');

        /*
         * check to see if the long_url already exists
         */
        $link_data = R::findOne(
            'links',
            ' long_url = ? ',
            array($long_url)
        );

        /*
         * if long_url doesn't already exist, generate a short url for it and save it
         */
        if (!isset($link_data->id)) {
            $short_url = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);
            $link_data = R::dispense('links');
            $link_data->long_url = $long_url;
            $link_data->short_url = $short_url;
            $id = R::store($link_data);
        }

        return $app->escape(
            $long_url
        ) . ' is shortened to <a href="http://localhost/sulner/web/redirect/' . $link_data->short_url . '">' . $link_data->short_url . '</a>';

    }
);


$app->get(
    '/redirect/{short_url}',
    function ($short_url) use ($app) {

        /*
         * check to see if the long_url already exists
         */
        $link_data = R::findOne(
            'links',
            ' short_url = ? ',
            array($app->escape($short_url))
        );

        if (isset($link_data->long_url)) {
            return $app->redirect($link_data->long_url);
        } else {
            return 'Sorry, short url ' . $app->escape($short_url) . ' not found. <a href="http://localhost/sulner/web/">Shorten a new URL</a>';
        }

    }
);

$app->run();