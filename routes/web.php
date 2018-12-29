<?php

$router::get('/', 'TreeController@index')->name('tree.index');
$router::post('/championships/{championship}/trees', 'TreeController@store')->name('tree.store');
$router::put('/championships/{championship}/trees', 'TreeController@update')->name('tree.update');