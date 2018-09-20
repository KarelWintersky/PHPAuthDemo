<?php
/**
 * User: Karel Wintersky
 * Date: 19.09.2018, time: 17:30
 */
 
use Pecee\SimpleRouter\SimpleRouter;

SimpleRouter::get('/', function () {
    return (new Template('index.html', '$/templates'))->render();
});

SimpleRouter::get('/registration', function (){
    $t = new Template('form.registration.html', '$/templates');

    $t->set('', [
        'is_strong_password'    =>  true,
        'is_activation_required'=>  true
    ]);

    return $t->render();
});

SimpleRouter::post('/registration', function (){

});
