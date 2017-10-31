<?php

use Larrock\ComponentWizard\AdminWizardController;

Route::group(['prefix' => 'admin', 'middleware'=> ['web', 'level:2', 'LarrockAdminMenu', 'SaveAdminPluginsData', 'SiteSearchAdmin']], function(){
    Route::get('/wizard', [
        'as' => 'admin.wizard', 'uses' => AdminWizardController::class .'@index'
    ]);
    Route::get('/wizard/config', [
        'as' => 'admin.wizard.config', 'uses' => AdminWizardController::class .'@aliases'
    ]);
    Route::get('/wizard/clear/{manual?}', [
        'as' => 'admin.wizard.clear', 'uses' => AdminWizardController::class .'@clear'
    ]);
    Route::post('/wizard/importrow', [
        'as' => 'admin.wizard.importrow', 'uses' => AdminWizardController::class .'@importrow'
    ]);
    Route::get('/wizard/sheetParse/{sheet}', [
        'as' => 'admin.wizard.sheetParse', 'uses' => AdminWizardController::class .'@sheetParse'
    ]);
    Route::post('/wizard/storeConfig', AdminWizardController::class .'@storeConfig');
    Route::get('/wizard/updateXLSX', [
        'as' => 'admin.wizard.updateXLSX', 'uses' => AdminWizardController::class .'@updateXLSX'
    ]);
    Route::post('/wizard/loadXLSX', [
        'as' => 'admin.wizard.loadXLSX', 'uses' => AdminWizardController::class .'@loadXLSX'
    ]);
    Route::post('/wizard/loadImages', [
        'as' => 'admin.wizard.loadImages', 'uses' => AdminWizardController::class .'@loadImages'
    ]);
    Route::get('/wizard/createMigration', [
        'as' => 'admin.wizard.createMigration', 'uses' => AdminWizardController::class .'@createMigration'
    ]);
    Route::get('/wizard/rollbackMigration', [
        'as' => 'admin.wizard.rollbackMigration', 'uses' => AdminWizardController::class .'@rollbackMigration'
    ]);
});