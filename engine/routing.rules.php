<?php
/**
 * User: Arris
 * Date: 19.09.2018, time: 17:30
 */
 
use Pecee\SimpleRouter\SimpleRouter;

SimpleRouter::get('/', function (){
    return '/';
});

SimpleRouter::get('/form:registration', function (){
    return 'form:registration';
});
